<?php
// admin/courses.php -- Manage courses (CRUD + search, sort, pagination)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Course.php';

$courses = new Course($db);
$errors  = [];
$form    = ['course_id' => 0, 'course_code' => '', 'course_name' => '', 'description' => ''];

// ---- Handle POST (save or delete) -----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please reload the page and try again.';
    } elseif (($_POST['action'] ?? 'save') === 'delete') {
        $id = (int) ($_POST['course_id'] ?? 0);
        $course = $id > 0 ? $courses->find($id) : false;
        if (!$course) {
            flash_set('error', 'Course not found.');
        } elseif ($courses->hasClasses($id)) {
            flash_set('error', 'Cannot delete "' . $course['course_name'] . '": it still has scheduled classes. Delete or move its classes first.');
        } else {
            $courses->delete($id);
            flash_set('success', 'Course "' . $course['course_name'] . '" was deleted successfully.');
        }
        redirect('courses.php');
    } else {
        $form = [
            'course_id'   => (int) ($_POST['course_id'] ?? 0),
            'course_code' => strtoupper(post_str('course_code')),
            'course_name' => post_str('course_name'),
            'description' => post_str('description'),
        ];
        $errors = validate_course($form['course_code'], $form['course_name'], $form['description']);

        if (!$errors && $form['course_id'] > 0 && !$courses->find($form['course_id'])) {
            $errors[] = 'The course you are editing no longer exists.';
        }
        if (!$errors && $courses->codeExists($form['course_code'], $form['course_id'])) {
            $errors[] = 'Course code "' . $form['course_code'] . '" is already assigned to another course.';
        }

        if (!$errors) {
            try {
                if ($form['course_id'] > 0) {
                    $courses->update($form['course_id'], $form['course_code'], $form['course_name'], $form['description']);
                    flash_set('success', 'Course "' . $form['course_name'] . '" was updated successfully.');
                } else {
                    $courses->create($form['course_code'], $form['course_name'], $form['description']);
                    flash_set('success', 'Course "' . $form['course_name'] . '" was added successfully.');
                }
                redirect('courses.php');
            } catch (PDOException $ex) {
                error_log('courses.php: ' . $ex->getMessage());
                $errors[] = 'A database error occurred. The course was not saved.';
            }
        }
    }
}

// ---- Edit mode: prefill the form from ?edit=ID ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (int) ($_GET['edit'] ?? 0) > 0) {
    $existing = $courses->find((int) $_GET['edit']);
    if ($existing) {
        $form = $existing;
    }
}

// ---- List with search / sort / pagination ----------------------------------
$q      = get_str('q');
$result = $courses->search($q, $_GET['sort'] ?? 'course_name', $_GET['dir'] ?? 'asc', (int) ($_GET['page'] ?? 1), 6);

$pageTitle = 'Courses';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header & Breadcrumbs -->
<div class="page-header">
    <div class="page-header-content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php"><?= icon('home', '', 14) ?> Dashboard</a>
            <span class="sep">/</span>
            <span class="current">Courses</span>
        </nav>
        <h1 class="page-title">Manage Courses</h1>
        <p class="page-subtitle">Configure curriculum programs, catalog codes, and course descriptions.</p>
    </div>
    <div class="page-header-actions">
        <a href="classes.php" class="btn btn-secondary">
            <?= icon('calendar', '', 16) ?>
            <span>View Classes</span>
        </a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <div class="alert-icon-wrap"><?= icon('alert-triangle', 'alert-icon', 20) ?></div>
        <div class="alert-body">
            <span class="alert-title">Please review the form errors</span>
            <ul>
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="alert-close" onclick="this.closest('.alert').remove()"><?= icon('x', '', 16) ?></button>
    </div>
<?php endif; ?>

<!-- Two-Column Proportional Layout -->
<div class="page-grid-2col">
    <!-- Left Column: Add / Edit Form -->
    <div class="grid-col-form">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon($form['course_id'] ? 'edit' : 'plus', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title"><?= $form['course_id'] ? 'Edit Course' : 'Add New Course' ?></h2>
                        <div class="card-subtitle"><?= $form['course_id'] ? 'Update existing program details' : 'Register a new subject in the catalog' ?></div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($form['course_id']): ?>
                    <div class="edit-banner">
                        <span class="edit-banner-text">
                            <?= icon('edit', '', 16) ?> Editing: <?= e($form['course_code']) ?>
                        </span>
                        <a href="courses.php" class="btn btn-secondary btn-small">Cancel</a>
                    </div>
                <?php endif; ?>

                <form method="post" action="courses.php" class="stacked">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="course_id" value="<?= (int) $form['course_id'] ?>">

                    <div class="form-group">
                        <label for="course_code" class="form-label-required">Course Code</label>
                        <input type="text" id="course_code" name="course_code" maxlength="20" required
                               value="<?= e($form['course_code']) ?>" placeholder="e.g. WEBDEV"
                               style="font-family: monospace; text-transform: uppercase;">
                        <span class="form-hint">Unique identifier (letters, numbers, dash/underscore).</span>
                    </div>

                    <div class="form-group">
                        <label for="course_name" class="form-label-required">Course Name</label>
                        <input type="text" id="course_name" name="course_name" maxlength="100" required
                               value="<?= e($form['course_name']) ?>" placeholder="e.g. Web Development Fundamentals">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" maxlength="1000"
                                  placeholder="Provide a concise syllabus or summary of this training course..."><?= e($form['description']) ?></textarea>
                        <span class="form-hint">Maximum 1,000 characters.</span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= icon($form['course_id'] ? 'check' : 'plus', '', 16) ?>
                            <span><?= $form['course_id'] ? 'Update Course' : 'Add Course' ?></span>
                        </button>
                        <?php if ($form['course_id']): ?>
                            <a class="btn btn-secondary" href="courses.php">Discard</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Course Directory -->
    <div class="grid-col-table">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon('book', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Course Catalog</h2>
                        <div class="card-subtitle"><?= (int) $result['total'] ?> course program<?= $result['total'] === 1 ? '' : 's' ?> active</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Search & Filter Toolbar -->
                <form method="get" class="filters-toolbar">
                    <div class="filter-group">
                        <label for="q">Search catalog</label>
                        <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Search by code, title or description...">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-small">
                            <?= icon('search', '', 15) ?> Search
                        </button>
                        <?php if ($q !== ''): ?>
                            <a class="btn btn-secondary btn-small" href="courses.php">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Responsive Table Container -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th><?= sort_link('Code', 'course_code', $result['sort'], $result['dir']) ?></th>
                                <th><?= sort_link('Course Name', 'course_name', $result['sort'], $result['dir']) ?></th>
                                <th>Description</th>
                                <th class="num"><?= sort_link('Classes', 'class_count', $result['sort'], $result['dir']) ?></th>
                                <th style="text-align: right; width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$result['rows']): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-state-icon"><?= icon('book', '', 28) ?></div>
                                        <h3 class="empty-state-title">No Courses Found</h3>
                                        <p class="empty-state-desc"><?= $q !== '' ? 'No courses match your query "' . e($q) . '". Try a different search term.' : 'Get started by creating your first course using the form on the left.' ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($result['rows'] as $row): ?>
                            <tr>
                                <td class="muted"><?= (int) $row['course_id'] ?></td>
                                <td><span class="code-badge"><?= e($row['course_code']) ?></span></td>
                                <td><strong style="color: var(--slate-900);"><?= e($row['course_name']) ?></strong></td>
                                <td class="muted" style="max-width: 260px; font-size: 0.825rem;"><?= e($row['description'] ?: '—') ?></td>
                                <td class="num">
                                    <span class="badge badge-neutral"><?= (int) $row['class_count'] ?> class<?= (int) $row['class_count'] === 1 ? '' : 'es' ?></span>
                                </td>
                                <td>
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        <a class="btn btn-secondary btn-small" href="<?= e(qs(['edit' => $row['course_id']])) ?>" title="Edit Course">
                                            <?= icon('edit', '', 14) ?> Edit
                                        </a>
                                        <form method="post" action="courses.php" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete course <?= e($row['course_code']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="course_id" value="<?= (int) $row['course_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-small" title="Delete Course">
                                                <?= icon('trash', '', 14) ?> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?= pager($result) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

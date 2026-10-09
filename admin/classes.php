<?php
// admin/classes.php -- Manage class schedules and slots
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$classes    = new ClassSection($db);
$courseRepo = new Course($db);
$courseList = $courseRepo->all();
$errors     = [];
$form = ['class_id' => 0, 'course_id' => 0, 'class_code' => '', 'schedule' => '',
         'instructor' => '', 'slots' => '10'];

// ---- Handle POST (save or delete) -----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please reload the page and try again.';
    } elseif (($_POST['action'] ?? 'save') === 'delete') {
        $id = (int) ($_POST['class_id'] ?? 0);
        $class = $id > 0 ? $classes->find($id) : false;
        if (!$class) {
            flash_set('error', 'Class not found.');
        } elseif ($classes->hasEnrollments($id)) {
            flash_set('error', 'Cannot delete class "' . $class['class_code'] . '": it still contains enrollment records. Cancel or remove enrollments first.');
        } else {
            $classes->delete($id);
            flash_set('success', 'Class "' . $class['class_code'] . '" was deleted successfully.');
        }
        redirect('classes.php');
    } else {
        $form = [
            'class_id'   => (int) ($_POST['class_id'] ?? 0),
            'course_id'  => (int) ($_POST['course_id'] ?? 0),
            'class_code' => strtoupper(post_str('class_code')),
            'schedule'   => post_str('schedule'),
            'instructor' => post_str('instructor'),
            'slots'      => post_str('slots'),
        ];
        $errors = validate_class($form['course_id'], $form['class_code'], $form['schedule'],
                                 $form['instructor'], $form['slots']);

        if (!$errors && !$courseRepo->find($form['course_id'])) {
            $errors[] = 'The selected course does not exist.';
        }
        if (!$errors && $form['class_id'] > 0 && !$classes->find($form['class_id'])) {
            $errors[] = 'The class you are editing no longer exists.';
        }

        if (!$errors) {
            try {
                $slots = (int) $form['slots'];
                if ($form['class_id'] > 0) {
                    $classes->update($form['class_id'], $form['course_id'], $form['class_code'],
                                     $form['schedule'], $form['instructor'], $slots);
                    flash_set('success', 'Class section "' . $form['class_code'] . '" was updated successfully.');
                } else {
                    $classes->create($form['course_id'], $form['class_code'], $form['schedule'],
                                     $form['instructor'], $slots);
                    flash_set('success', 'Class section "' . $form['class_code'] . '" was created successfully.');
                }
                redirect('classes.php');
            } catch (PDOException $ex) {
                error_log('classes.php: ' . $ex->getMessage());
                $errors[] = 'A database error occurred. The class was not saved.';
            }
        }
    }
}

// ---- Edit mode --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (int) ($_GET['edit'] ?? 0) > 0) {
    $existing = $classes->find((int) $_GET['edit']);
    if ($existing) {
        $form = $existing;
    }
}

// ---- List with search / filter / sort / pagination -----------------------------
$q         = get_str('q');
$course_id = (int) ($_GET['course_id'] ?? 0);
$result    = $classes->search($q, $course_id, $_GET['sort'] ?? 'course_name', $_GET['dir'] ?? 'asc',
                              (int) ($_GET['page'] ?? 1), 8);

$pageTitle = 'Classes';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header & Breadcrumbs -->
<div class="page-header">
    <div class="page-header-content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php"><?= icon('home', '', 14) ?> Dashboard</a>
            <span class="sep">/</span>
            <span class="current">Classes</span>
        </nav>
        <h1 class="page-title">Manage Classes</h1>
        <p class="page-subtitle">Configure class schedules, assigned instructors, and real-time seat availability.</p>
    </div>
    <div class="page-header-actions">
        <a href="courses.php" class="btn btn-secondary">
            <?= icon('book', '', 16) ?>
            <span>Course Catalog</span>
        </a>
        <a href="enroll.php" class="btn btn-primary">
            <?= icon('enroll', '', 16) ?>
            <span>Enroll Student</span>
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

<!-- Two-Column Layout -->
<div class="page-grid-2col">
    <!-- Left Column: Add / Edit Form -->
    <div class="grid-col-form">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon($form['class_id'] ? 'edit' : 'plus', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title"><?= $form['class_id'] ? 'Edit Class Section' : 'Add Class Section' ?></h2>
                        <div class="card-subtitle"><?= $form['class_id'] ? 'Modify schedule or seat quota' : 'Create a new timetable section' ?></div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$courseList): ?>
                    <div class="empty-state" style="padding: 1.5rem 0.5rem;">
                        <div class="empty-state-icon"><?= icon('book', '', 24) ?></div>
                        <h3 class="empty-state-title" style="font-size: 1rem;">No Courses Found</h3>
                        <p class="empty-state-desc">You must define at least one course before scheduling a class.</p>
                        <a href="courses.php" class="btn btn-primary btn-small">Create Course &rarr;</a>
                    </div>
                <?php else: ?>
                    <?php if ($form['class_id']): ?>
                        <div class="edit-banner">
                            <span class="edit-banner-text">
                                <?= icon('edit', '', 16) ?> Editing: <?= e($form['class_code']) ?>
                            </span>
                            <a href="classes.php" class="btn btn-secondary btn-small">Cancel</a>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="classes.php" class="stacked">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="class_id" value="<?= (int) $form['class_id'] ?>">

                        <div class="form-group">
                            <label for="course_id_f" class="form-label-required">Parent Course</label>
                            <select id="course_id_f" name="course_id" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courseList as $c): ?>
                                    <option value="<?= (int) $c['course_id'] ?>"
                                        <?= (int) $form['course_id'] === (int) $c['course_id'] ? 'selected' : '' ?>>
                                        <?= e($c['course_code'] . ' - ' . $c['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="class_code" class="form-label-required">Class Section Code</label>
                            <input type="text" id="class_code" name="class_code" maxlength="20" required
                                   value="<?= e($form['class_code']) ?>" placeholder="e.g. WEB-A"
                                   style="font-family: monospace; text-transform: uppercase;">
                            <span class="form-hint">E.g. Section name, period or room code.</span>
                        </div>

                        <div class="form-group">
                            <label for="schedule">Schedule &amp; Timetable</label>
                            <input type="text" id="schedule" name="schedule" maxlength="100"
                                   value="<?= e($form['schedule']) ?>" placeholder="e.g. Mon &amp; Wed 9:00-11:00 AM">
                        </div>

                        <div class="form-group">
                            <label for="instructor">Assigned Instructor</label>
                            <input type="text" id="instructor" name="instructor" maxlength="100"
                                   value="<?= e($form['instructor']) ?>" placeholder="e.g. Ms. Reyes / Prof. Miller">
                        </div>

                        <div class="form-group">
                            <label for="slots" class="form-label-required">Available Seats (Slots)</label>
                            <input type="number" id="slots" name="slots" min="0" max="9999" required
                                   value="<?= e($form['slots']) ?>">
                            <span class="form-hint">Current open capacity. Decrements automatically upon enrollment.</span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= icon($form['class_id'] ? 'check' : 'plus', '', 16) ?>
                                <span><?= $form['class_id'] ? 'Update Class' : 'Add Class' ?></span>
                            </button>
                            <?php if ($form['class_id']): ?>
                                <a class="btn btn-secondary" href="classes.php">Discard</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Classes Directory -->
    <div class="grid-col-table">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon('calendar', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Class Schedules &amp; Availability</h2>
                        <div class="card-subtitle"><?= (int) $result['total'] ?> class section<?= $result['total'] === 1 ? '' : 's' ?> active</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters Toolbar -->
                <form method="get" class="filters-toolbar">
                    <div class="filter-group">
                        <label for="q">Keyword search</label>
                        <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Class, instructor or schedule...">
                    </div>
                    <div class="filter-group">
                        <label for="course_filter">Filter by Course</label>
                        <select id="course_filter" name="course_id">
                            <option value="0">All Courses</option>
                            <?php foreach ($courseList as $c): ?>
                                <option value="<?= (int) $c['course_id'] ?>" <?= $course_id === (int) $c['course_id'] ? 'selected' : '' ?>>
                                    <?= e($c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-small">
                            <?= icon('filter', '', 15) ?> Filter
                        </button>
                        <?php if ($q !== '' || $course_id > 0): ?>
                            <a class="btn btn-secondary btn-small" href="classes.php">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Responsive Table -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= sort_link('Class Code', 'class_code', $result['sort'], $result['dir']) ?></th>
                                <th><?= sort_link('Course', 'course_name', $result['sort'], $result['dir']) ?></th>
                                <th><?= sort_link('Schedule', 'schedule', $result['sort'], $result['dir']) ?></th>
                                <th><?= sort_link('Instructor', 'instructor', $result['sort'], $result['dir']) ?></th>
                                <th class="num"><?= sort_link('Enrolled', 'enrolled', $result['sort'], $result['dir']) ?></th>
                                <th class="num" style="min-width: 130px;"><?= sort_link('Available Slots', 'slots', $result['sort'], $result['dir']) ?></th>
                                <th style="text-align: right; width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$result['rows']): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon"><?= icon('calendar', '', 28) ?></div>
                                        <h3 class="empty-state-title">No Class Sections Found</h3>
                                        <p class="empty-state-desc">Try clearing your filters or create a new section on the left.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($result['rows'] as $row):
                            $enrolled = (int) $row['enrolled'];
                            $slots    = (int) $row['slots'];
                            $totalCap = $enrolled + $slots;
                            $pct      = $totalCap > 0 ? round(($enrolled / $totalCap) * 100) : 0;
                            $fillClass= $slots === 0 ? 'fill-rose' : ($pct >= 75 ? 'fill-amber' : 'fill-green');
                        ?>
                            <tr>
                                <td>
                                    <span class="code-badge"><?= e($row['class_code']) ?></span>
                                </td>
                                <td>
                                    <strong style="color: var(--slate-900);"><?= e($row['course_name']) ?></strong>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.825rem;">
                                        <span class="muted"><?= icon('calendar', '', 14) ?></span>
                                        <span><?= e($row['schedule'] ?: 'TBA') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.825rem;">
                                        <span class="muted"><?= icon('student', '', 14) ?></span>
                                        <span><?= e($row['instructor'] ?: 'Unassigned') ?></span>
                                    </div>
                                </td>
                                <td class="num">
                                    <span class="badge badge-neutral"><?= $enrolled ?></span>
                                </td>
                                <td class="num">
                                    <div class="capacity-meter">
                                        <div class="capacity-meter-header">
                                            <span><?= $enrolled ?>/<?= $totalCap ?></span>
                                            <?php if ($slots === 0): ?>
                                                <span class="badge badge-full" style="padding: 1px 5px; font-size: 0.65rem;">FULL</span>
                                            <?php else: ?>
                                                <span style="color: var(--emerald-text); font-weight: 700;"><?= $slots ?> left</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="progress-track" title="<?= $enrolled ?> of <?= $totalCap ?> seats occupied">
                                            <div class="progress-fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        <a class="btn btn-secondary btn-small" href="<?= e(qs(['edit' => $row['class_id']])) ?>" title="Edit Class">
                                            <?= icon('edit', '', 14) ?> Edit
                                        </a>
                                        <form method="post" action="classes.php" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete class section <?= e($row['class_code']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="class_id" value="<?= (int) $row['class_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-small" title="Delete Class">
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

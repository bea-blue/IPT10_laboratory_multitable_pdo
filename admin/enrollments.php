<?php
// admin/enrollments.php -- Enrollment list (multi-table JOINs) + cancel
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);

// ---- Cancel an enrollment (POST: a state change must never be a GET link) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnQs = [];
    parse_str((string) ($_POST['return_qs'] ?? ''), $returnQs);   // keep filters/page after redirect
    $target = 'enrollments.php' . ($returnQs ? '?' . http_build_query($returnQs) : '');

    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please reload and try again.');
    } else {
        $enrollment_id = (int) ($_POST['enrollment_id'] ?? 0);
        if ($enrollment_id <= 0) {
            flash_set('error', 'Invalid enrollment record specified.');
        } else {
            try {
                $repo->cancel($enrollment_id);     // status = cancelled + slot restored (one transaction)
                flash_set('success', 'Enrollment #' . $enrollment_id . ' was cancelled and the class slot has been restored.');
            } catch (EnrollmentException $ex) {
                flash_set('error', $ex->getMessage());
            } catch (PDOException $ex) {
                error_log('enrollments.php: ' . $ex->getMessage());
                flash_set('error', 'A database error occurred. No changes were saved (transaction rolled back).');
            }
        }
    }
    redirect($target);
}

// ---- Filters / sort / pagination ----------------------------------------------
$filters = [
    'q'         => get_str('q'),
    'status'    => get_str('status'),
    'course_id' => (int) ($_GET['course_id'] ?? 0),
    'class_id'  => (int) ($_GET['class_id'] ?? 0),
];
$result = $repo->search($filters, $_GET['sort'] ?? 'date', $_GET['dir'] ?? 'desc', (int) ($_GET['page'] ?? 1), 10);

$courseList = (new Course($db))->all();
$classList  = (new ClassSection($db))->allWithCourse();
$hasFilter  = $filters['q'] !== '' || $filters['status'] !== '' || $filters['course_id'] > 0 || $filters['class_id'] > 0;

$pageTitle = 'Enrollments';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header & Breadcrumbs -->
<div class="page-header">
    <div class="page-header-content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php"><?= icon('home', '', 14) ?> Dashboard</a>
            <span class="sep">/</span>
            <span class="current">Enrollments Ledger</span>
        </nav>
        <h1 class="page-title">Enrollment Ledger</h1>
        <p class="page-subtitle">Multi-table audit log of student registrations, schedules, and active slot reservations.</p>
    </div>
    <div class="page-header-actions">
        <a href="students.php" class="btn btn-secondary">
            <?= icon('user-plus', '', 16) ?>
            <span>Record Student</span>
        </a>
        <a href="enroll.php" class="btn btn-primary">
            <?= icon('enroll', '', 16) ?>
            <span>Quick Enroll</span>
        </a>
    </div>
</div>

<!-- Filters Toolbar Card -->
<div class="card">
    <div class="card-body" style="padding: 1.15rem 1.35rem;">
        <form method="get" class="filters-toolbar" style="margin: 0; background: transparent; border: none; padding: 0;">
            <div class="filter-group" style="flex: 1.5; min-width: 220px;">
                <label for="q">Keyword search</label>
                <input type="text" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Student name, email, course or class...">
            </div>

            <div class="filter-group" style="min-width: 130px;">
                <label for="status">Registration Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active"    <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled Only</option>
                </select>
            </div>

            <div class="filter-group" style="min-width: 170px;">
                <label for="course_id">Course Filter</label>
                <select id="course_id" name="course_id">
                    <option value="0">All Courses</option>
                    <?php foreach ($courseList as $c): ?>
                        <option value="<?= (int) $c['course_id'] ?>" <?= $filters['course_id'] === (int) $c['course_id'] ? 'selected' : '' ?>>
                            <?= e($c['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group" style="min-width: 150px;">
                <label for="class_id">Class Section</label>
                <select id="class_id" name="class_id">
                    <option value="0">All Classes</option>
                    <?php foreach ($classList as $c): ?>
                        <option value="<?= (int) $c['class_id'] ?>" <?= $filters['class_id'] === (int) $c['class_id'] ? 'selected' : '' ?>>
                            <?= e($c['class_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-small">
                    <?= icon('search', '', 14) ?> Apply
                </button>
                <?php if ($hasFilter): ?>
                    <a class="btn btn-secondary btn-small" href="enrollments.php">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Enrollments Table Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title-group">
            <div class="card-title-icon">
                <?= icon('list', '', 18) ?>
            </div>
            <div>
                <h2 class="card-title">Registration Records</h2>
                <div class="card-subtitle"><?= (int) $result['total'] ?> enrollment record<?= $result['total'] === 1 ? '' : 's' ?> found</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th><?= sort_link('Student', 'student', $result['sort'], $result['dir']) ?></th>
                        <th><?= sort_link('Course', 'course', $result['sort'], $result['dir']) ?></th>
                        <th><?= sort_link('Class Section', 'class', $result['sort'], $result['dir']) ?></th>
                        <th>Schedule &amp; Instructor</th>
                        <th><?= sort_link('Date Enrolled', 'date', $result['sort'], $result['dir']) ?></th>
                        <th><?= sort_link('Status', 'status', $result['sort'], $result['dir']) ?></th>
                        <th style="text-align: right; width: 110px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$result['rows']): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon"><?= icon('list', '', 28) ?></div>
                                <h3 class="empty-state-title">No Enrollments Found</h3>
                                <p class="empty-state-desc"><?= $hasFilter ? 'No records match your active search filters. Try adjusting your search query.' : 'There are no student enrollments recorded in the system yet.' ?></p>
                                <a href="students.php" class="btn btn-primary btn-small">Record New Student &rarr;</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($result['rows'] as $r): ?>
                    <tr>
                        <td class="muted" style="font-family: monospace;">#<?= (int) $r['enrollment_id'] ?></td>
                        <td>
                            <div class="user-cell">
                                <?= avatar($r['full_name'], 'sm') ?>
                                <div class="user-cell-meta">
                                    <span class="user-cell-name"><?= e($r['full_name']) ?></span>
                                    <span class="user-cell-sub muted"><?= e($r['email'] ?: 'No email') ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong style="color: var(--slate-900); font-size: 0.875rem;"><?= e($r['course_name']) ?></strong>
                        </td>
                        <td>
                            <span class="code-badge"><?= e($r['class_code']) ?></span>
                        </td>
                        <td>
                            <div style="font-size: 0.825rem; line-height: 1.4;">
                                <div style="display: flex; align-items: center; gap: 0.3rem;">
                                    <span class="muted"><?= icon('calendar', '', 13) ?></span>
                                    <span><?= e($r['schedule'] ?: 'TBA') ?></span>
                                </div>
                                <div class="muted" style="display: flex; align-items: center; gap: 0.3rem; margin-top: 2px;">
                                    <span><?= icon('student', '', 13) ?></span>
                                    <span><?= e($r['instructor'] ?: 'Unassigned') ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="muted" style="font-size: 0.825rem; white-space: nowrap;">
                                <?= e(date('M d, Y', strtotime($r['enrollment_date'] ?? 'now'))) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= e($r['status']) ?>">
                                <?= e($r['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($r['status'] === 'active'): ?>
                                <form method="post" action="enrollments.php" class="inline"
                                      onsubmit="return confirm('Cancel enrollment #<?= (int) $r['enrollment_id'] ?> for <?= e($r['full_name']) ?>? The class slot will be immediately restored.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="enrollment_id" value="<?= (int) $r['enrollment_id'] ?>">
                                    <input type="hidden" name="return_qs" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
                                    <button type="submit" class="btn btn-danger btn-small" title="Cancel Registration">
                                        <?= icon('trash', '', 13) ?> Cancel
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="muted" style="font-size: 0.8rem;">Restored</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= pager($result) ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

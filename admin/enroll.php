<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';

$repo     = new EnrollmentRepository($db);
$classes  = new ClassSection($db);
$students = new Student($db);

$errors = [];
$old    = ['student_id' => 0, 'class_id' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id   = (int) ($_POST['class_id'] ?? 0);
    $old = compact('student_id', 'class_id');

    // Validate the ids
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please reload the page and try again.';
    } else {
        if ($student_id <= 0 || !$students->find($student_id)) {
            $errors[] = 'Please choose a valid student.';
        }
        if ($class_id <= 0 || $classes->getSlots($class_id) === null) {
            $errors[] = 'Please choose a valid class.';
        }
    }

    if (!$errors) {
        try {
            $repo->enroll($student_id, $class_id);
            $student = $students->find($student_id);
            flash_set('success', 'Enrolled "' . $student['full_name'] . '" successfully. '
                . 'Remaining available slots in that section: ' . $classes->getSlots($class_id) . '.');
            redirect('enroll.php');
        } catch (EnrollmentException $ex) {
            $errors[] = $ex->getMessage() . ' No changes were committed.';
        } catch (PDOException $ex) {
            error_log('enroll.php: ' . $ex->getMessage());
            $errors[] = 'A database error occurred. Nothing was saved (transaction rolled back).';
        }
    }
}

$studentList = $students->all();
$classList   = $classes->allWithCourse();

$pageTitle = 'Enroll';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header & Breadcrumbs -->
<div class="page-header">
    <div class="page-header-content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php"><?= icon('home', '', 14) ?> Dashboard</a>
            <span class="sep">/</span>
            <span class="current">Quick Enroll</span>
        </nav>
        <h1 class="page-title">Enroll Existing Student</h1>
        <p class="page-subtitle">Register a previously recorded learner into an additional training class section.</p>
    </div>
    <div class="page-header-actions">
        <a href="students.php" class="btn btn-secondary">
            <?= icon('user-plus', '', 16) ?>
            <span>Record New Student</span>
        </a>
        <a href="enrollments.php" class="btn btn-secondary">
            <?= icon('list', '', 16) ?>
            <span>View Enrollments</span>
        </a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <div class="alert-icon-wrap"><?= icon('alert-triangle', 'alert-icon', 20) ?></div>
        <div class="alert-body">
            <span class="alert-title">Enrollment Failed</span>
            <ul>
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="alert-close" onclick="this.closest('.alert').remove()"><?= icon('x', '', 16) ?></button>
    </div>
<?php endif; ?>

<!-- Proportional Layout -->
<div class="page-grid-enroll">
    <!-- Main Column: Enrollment Form -->
    <div class="grid-col-main">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon('enroll', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Course Registration Form</h2>
                        <div class="card-subtitle">Select student and corresponding class section</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$studentList || !$classList): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><?= icon('enroll', '', 28) ?></div>
                        <h3 class="empty-state-title">Setup Required</h3>
                        <p class="empty-state-desc">You need at least one registered student and one active class before processing enrollments.</p>
                        <div style="display: flex; gap: 0.75rem; justify-content: center;">
                            <?php if (!$studentList): ?>
                                <a href="students.php" class="btn btn-primary btn-small">Record Student &rarr;</a>
                            <?php endif; ?>
                            <?php if (!$classList): ?>
                                <a href="classes.php" class="btn btn-secondary btn-small">Create Class &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="post" action="enroll.php" class="stacked">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="student_id" class="form-label-required">Select Registered Student</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">-- Choose Student --</option>
                                <?php foreach ($studentList as $s): ?>
                                    <option value="<?= (int) $s['student_id'] ?>"
                                        <?= (int) $old['student_id'] === (int) $s['student_id'] ? 'selected' : '' ?>>
                                        <?= e($s['full_name']) ?>
                                        <?= $s['email'] ? ' (' . e($s['email']) . ')' : '' ?>
                                        &bull; [ID: #<?= (int) $s['student_id'] ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Student must already exist in the records directory.</span>
                        </div>

                        <div class="form-group">
                            <label for="class_id" class="form-label-required">Select Class Section</label>
                            <select id="class_id" name="class_id" required>
                                <option value="">-- Choose Class Section --</option>
                                <?php foreach ($classList as $c):
                                    $slotsLeft = (int) $c['slots'];
                                    $isFull = $slotsLeft <= 0;
                                ?>
                                    <option value="<?= (int) $c['class_id'] ?>"
                                        <?= (int) $old['class_id'] === (int) $c['class_id'] ? 'selected' : '' ?>
                                        <?= $isFull ? 'disabled' : '' ?>>
                                        <?= e($c['class_code'] . ' - ' . $c['course_name']) ?>
                                        &bull; <?= e($c['schedule']) ?>
                                        [<?= $isFull ? 'FULL' : $slotsLeft . ' slot' . ($slotsLeft === 1 ? '' : 's') . ' left' ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-hint">Only sections with open seats are selectable.</span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="min-width: 180px;">
                                <?= icon('check-circle', '', 16) ?>
                                <span>Confirm Enrollment</span>
                            </button>
                            <a href="../index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Side Column: Guidelines & Policy -->
    <div class="grid-col-side">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon stat-icon-blue">
                        <?= icon('info', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Enrollment Rules</h2>
                    </div>
                </div>
            </div>
            <div class="card-body" style="font-size: 0.85rem; line-height: 1.55;">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <strong style="color: var(--slate-900); display: block; margin-bottom: 0.2rem;">
                            1. Duplicate Protection
                        </strong>
                        <p class="muted" style="margin: 0;">
                            A student cannot be enrolled in the exact same class section twice. Re-enrollments are rejected automatically.
                        </p>
                    </div>

                    <div>
                        <strong style="color: var(--slate-900); display: block; margin-bottom: 0.2rem;">
                            2. Real-Time Capacity
                        </strong>
                        <p class="muted" style="margin: 0;">
                            Seat deduction happens immediately inside an atomic transaction. Two administrators cannot claim the last seat simultaneously.
                        </p>
                    </div>

                    <div>
                        <strong style="color: var(--slate-900); display: block; margin-bottom: 0.2rem;">
                            3. New Learners
                        </strong>
                        <p class="muted" style="margin: 0;">
                            Need to enroll someone not in the dropdown? Head to <a href="students.php">Record Student</a> to create their account and enroll in one step.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

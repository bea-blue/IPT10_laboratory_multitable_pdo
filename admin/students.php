<?php
// admin/students.php -- Student recording form + transaction enrollment
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';

$repo     = new EnrollmentRepository($db);
$classes  = new ClassSection($db);
$students = new Student($db);

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'phone' => '', 'class_id' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $class_id  = (int) ($_POST['class_id'] ?? 0);
    $old = compact('full_name', 'email', 'phone', 'class_id');

    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please reload the page and try again.';
    } else {
        // Validate inputs (non-empty name, valid email/phone, valid class_id)
        $errors = validate_student($full_name, $email, $phone);
        if ($class_id <= 0) {
            $errors[] = 'Please select a class section.';
        } elseif ($classes->getSlots($class_id) === null) {
            $errors[] = 'The selected class section does not exist.';
        }
    }

    if (!$errors) {
        try {
            // ONE call = INSERT student + INSERT enrollment + decrement slots
            // inside ONE atomic transaction (see EnrollmentRepository::recordStudent).
            $repo->recordStudent($full_name, $email, $phone, $class_id);
            $left = $classes->getSlots($class_id);
            flash_set('success', 'Student "' . $full_name . '" was successfully recorded and enrolled. '
                . 'Remaining available slots in that section: ' . $left . '.');
            redirect('students.php');
        } catch (EnrollmentException $ex) {
            // Business rule failed (e.g. no slots or duplicate) - transaction rolled back.
            $errors[] = $ex->getMessage() . ' Transaction was rolled back safely.';
        } catch (PDOException $ex) {
            error_log('students.php: ' . $ex->getMessage());
            $errors[] = 'A database error occurred. Nothing was saved (transaction rolled back).';
        }
    }
}

$classList = $classes->allWithCourse();   // for the class dropdown
$recent    = $students->recent(8);

$pageTitle = 'Record Student';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header & Breadcrumbs -->
<div class="page-header">
    <div class="page-header-content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php"><?= icon('home', '', 14) ?> Dashboard</a>
            <span class="sep">/</span>
            <span class="current">Record Student</span>
        </nav>
        <h1 class="page-title">Record Student &amp; Enroll</h1>
        <p class="page-subtitle">Register a new student profile and immediately reserve their seat in an open class section.</p>
    </div>
    <div class="page-header-actions">
        <a href="enroll.php" class="btn btn-secondary">
            <?= icon('enroll', '', 16) ?>
            <span>Enroll Existing Student</span>
        </a>
        <a href="enrollments.php" class="btn btn-secondary">
            <?= icon('list', '', 16) ?>
            <span>Enrollments Ledger</span>
        </a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <div class="alert-icon-wrap"><?= icon('alert-triangle', 'alert-icon', 20) ?></div>
        <div class="alert-body">
            <span class="alert-title">Registration Could Not Be Completed</span>
            <ul>
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="alert-close" onclick="this.closest('.alert').remove()"><?= icon('x', '', 16) ?></button>
    </div>
<?php endif; ?>

<!-- Two-Column Layout -->
<div class="page-grid-balanced">
    <!-- Left Column: Registration Form -->
    <div class="grid-col-form">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon('user-plus', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">New Student Registration</h2>
                        <div class="card-subtitle">Creates learner profile &amp; assigns seat</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$classList): ?>
                    <div class="empty-state" style="padding: 1.5rem 0.5rem;">
                        <div class="empty-state-icon"><?= icon('calendar', '', 24) ?></div>
                        <h3 class="empty-state-title" style="font-size: 1rem;">No Open Classes</h3>
                        <p class="empty-state-desc">There are no classes scheduled yet. Create a class before registering students.</p>
                        <a href="classes.php" class="btn btn-primary btn-small">Create Class Section &rarr;</a>
                    </div>
                <?php else: ?>
                    <form method="post" action="students.php" class="stacked">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="full_name" class="form-label-required">Full Legal Name</label>
                            <input type="text" id="full_name" name="full_name" maxlength="100" required
                                   value="<?= e($old['full_name']) ?>" placeholder="e.g. Maria Clara Reyes">
                            <span class="form-hint">First and last name (letters, spaces, periods, hyphens).</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" maxlength="100"
                                   value="<?= e($old['email']) ?>" placeholder="e.g. maria.reyes@example.com">
                            <span class="form-hint">Used for class confirmation and duplicate prevention.</span>
                        </div>

                        <div class="form-group">
                            <label for="phone">Contact Phone Number</label>
                            <input type="text" id="phone" name="phone" maxlength="30"
                                   value="<?= e($old['phone']) ?>" placeholder="e.g. 0917-123-4567">
                        </div>

                        <div class="form-group">
                            <label for="class_id" class="form-label-required">Select Class Section to Enroll</label>
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
                            <span class="form-hint">Classes marked FULL cannot accept additional enrollments.</span>
                        </div>

                        <!-- Atomic Transaction Guarantee Note -->
                        <div style="background-color: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius-sm); padding: 0.85rem; margin-top: 1.25rem;">
                            <div style="display: flex; gap: 0.6rem; align-items: flex-start;">
                                <div style="color: var(--emerald-600); flex-shrink: 0; margin-top: 2px;">
                                    <?= icon('shield', '', 18) ?>
                                </div>
                                <div style="font-size: 0.785rem; color: var(--slate-600); line-height: 1.45;">
                                    <strong>Atomic Guarantee:</strong> Profile creation and slot reservation occur simultaneously. If the selected class becomes full, no partial records are written.
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <?= icon('user-plus', '', 16) ?>
                                <span>Record &amp; Enroll Student</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Recently Recorded Students -->
    <div class="grid-col-table">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon('student', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Recently Recorded Students</h2>
                        <div class="card-subtitle">Latest learners added to the directory</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th style="text-align: right;">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$recent): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <div class="empty-state-icon"><?= icon('student', '', 28) ?></div>
                                        <h3 class="empty-state-title">No Students Recorded Yet</h3>
                                        <p class="empty-state-desc">Use the registration form on the left to add your first student.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($recent as $s): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <?= avatar($s['full_name'], 'sm') ?>
                                        <div class="user-cell-meta">
                                            <span class="user-cell-name"><?= e($s['full_name']) ?></span>
                                            <span class="user-cell-sub muted">ID: #<?= (int) $s['student_id'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem; color: var(--slate-700);">
                                        <?= e($s['email'] ?: '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="muted" style="font-size: 0.825rem;">
                                        <?= e($s['phone'] ?: '—') ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <span class="muted" style="font-size: 0.8rem; white-space: nowrap;">
                                        <?= e(date('M d, Y', strtotime($s['created_at'] ?? 'now'))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="enrollments.php" class="stat-card-link-text" style="font-size: 0.825rem;">
                    View all student enrollments in ledger &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

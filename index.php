<?php
// index.php -- Executive Admin Dashboard
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Report.php';

$report = new Report($db);
$totals = $report->totals();
$classCapacity = $report->classSummary();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Welcome Banner -->
<section class="hero-banner">
    <div class="hero-content">
        <div class="hero-badge">
            <?= icon('shield', 'icon-shield', 14) ?>
            <span>Academic Management &bull; Production</span>
        </div>
        <h1 class="hero-title">Training Enrollment Dashboard</h1>
        <p class="hero-desc">
            Monitor real-time course registrations, manage class section capacities, and ensure flawless student enrollment powered by transactional slot protection.
        </p>
    </div>
    <div class="hero-actions">
        <a href="admin/students.php" class="btn btn-primary">
            <?= icon('user-plus', 'btn-icon', 18) ?>
            <span>Record Student</span>
        </a>
        <a href="admin/enroll.php" class="btn btn-secondary">
            <?= icon('enroll', 'btn-icon', 18) ?>
            <span>Quick Enroll</span>
        </a>
    </div>
</section>

<!-- Executive KPI Metrics -->
<section class="stats-grid" aria-label="System Metrics">
    <a href="admin/courses.php" class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Courses</span>
            <div class="stat-card-icon stat-icon-blue">
                <?= icon('book', '', 20) ?>
            </div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['courses'] ?></div>
        <div class="stat-card-footer">
            <span>Active training programs</span>
            <span class="stat-card-link-text">Manage &rarr;</span>
        </div>
    </a>

    <a href="admin/classes.php" class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Classes</span>
            <div class="stat-card-icon stat-icon-indigo">
                <?= icon('calendar', '', 20) ?>
            </div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['classes'] ?></div>
        <div class="stat-card-footer">
            <span>Scheduled sections</span>
            <span class="stat-card-link-text">View &rarr;</span>
        </div>
    </a>

    <a href="admin/students.php" class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Students</span>
            <div class="stat-card-icon stat-icon-violet">
                <?= icon('student', '', 20) ?>
            </div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['students'] ?></div>
        <div class="stat-card-footer">
            <span>Enrolled learners</span>
            <span class="stat-card-link-text">Register &rarr;</span>
        </div>
    </a>

    <a href="admin/enrollments.php?status=active" class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Active Enrollments</span>
            <div class="stat-card-icon stat-icon-green">
                <?= icon('check-circle', '', 20) ?>
            </div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['active_enrollments'] ?></div>
        <div class="stat-card-footer">
            <span>Confirmed placements</span>
            <span class="stat-card-link-text">List &rarr;</span>
        </div>
    </a>

    <a href="admin/enrollments.php?status=cancelled" class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Cancelled</span>
            <div class="stat-card-icon stat-icon-rose">
                <?= icon('refresh', '', 20) ?>
            </div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['cancelled_enrollments'] ?></div>
        <div class="stat-card-footer">
            <span>Restored to slot pool</span>
            <span class="stat-card-link-text">Audit &rarr;</span>
        </div>
    </a>
</section>

<!-- Dashboard Main Grid -->
<div class="page-grid-dashboard">
    <!-- Left Column: Quick Actions Hub -->
    <div class="dashboard-left">
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon">
                        <?= icon('list', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Management Modules</h2>
                        <div class="card-subtitle">Direct access to administrative workflows</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="action-cards-grid">
                    <a href="admin/courses.php" class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-icon stat-icon-blue">
                                <?= icon('book', '', 18) ?>
                            </div>
                            <h3 class="action-card-title">Course Catalog</h3>
                        </div>
                        <p class="action-card-desc">Define course descriptions, codes, and browse scheduled offerings.</p>
                        <span class="action-card-cta">Configure courses <?= icon('arrow-right', '', 14) ?></span>
                    </a>

                    <a href="admin/classes.php" class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-icon stat-icon-indigo">
                                <?= icon('calendar', '', 18) ?>
                            </div>
                            <h3 class="action-card-title">Class Sections</h3>
                        </div>
                        <p class="action-card-desc">Organize time schedules, assign instructors, and set seat capacity.</p>
                        <span class="action-card-cta">Manage sections <?= icon('arrow-right', '', 14) ?></span>
                    </a>

                    <a href="admin/students.php" class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-icon stat-icon-violet">
                                <?= icon('user-plus', '', 18) ?>
                            </div>
                            <h3 class="action-card-title">Record Student</h3>
                        </div>
                        <p class="action-card-desc">Register a new learner and enroll them in one single transaction.</p>
                        <span class="action-card-cta">Register new <?= icon('arrow-right', '', 14) ?></span>
                    </a>

                    <a href="admin/enroll.php" class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-icon stat-icon-green">
                                <?= icon('enroll', '', 18) ?>
                            </div>
                            <h3 class="action-card-title">Quick Enroll</h3>
                        </div>
                        <p class="action-card-desc">Assign an existing student to an additional open training class.</p>
                        <span class="action-card-cta">Enroll student <?= icon('arrow-right', '', 14) ?></span>
                    </a>

                    <a href="admin/enrollments.php" class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-icon stat-icon-blue">
                                <?= icon('enrollments', '', 18) ?>
                            </div>
                            <h3 class="action-card-title">Enrollment Ledger</h3>
                        </div>
                        <p class="action-card-desc">Filter records, search attendees, and cancel registrations with slot restoration.</p>
                        <span class="action-card-cta">View ledger <?= icon('arrow-right', '', 14) ?></span>
                    </a>

                    <a href="admin/reports.php" class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-icon stat-icon-indigo">
                                <?= icon('report', '', 18) ?>
                            </div>
                            <h3 class="action-card-title">Analytics &amp; Reports</h3>
                        </div>
                        <p class="action-card-desc">Review enrollment ratios, slot saturation, and student distributions.</p>
                        <span class="action-card-cta">Open reports <?= icon('arrow-right', '', 14) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Live Class Capacity & System Status -->
    <div class="dashboard-right">
        <!-- Live Capacity Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-title-icon stat-icon-green">
                        <?= icon('calendar', '', 18) ?>
                    </div>
                    <div>
                        <h2 class="card-title">Capacity Snapshot</h2>
                        <div class="card-subtitle">Real-time class seat availability</div>
                    </div>
                </div>
                <a href="admin/classes.php" class="btn btn-secondary btn-small">All Classes</a>
            </div>
            <div class="card-body">
                <?php if (empty($classCapacity)): ?>
                    <p class="muted">No classes scheduled yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1.15rem;">
                        <?php foreach (array_slice($classCapacity, 0, 5) as $c):
                            $active = (int) $c['active_count'];
                            $left   = (int) $c['slots_left'];
                            $total  = $active + $left;
                            $pct    = $total > 0 ? round(($active / $total) * 100) : 0;
                            $barClass = $left === 0 ? 'fill-rose' : ($pct >= 75 ? 'fill-amber' : 'fill-green');
                        ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <div>
                                        <strong style="color: var(--slate-900); font-size: 0.9rem;"><?= e($c['class_code']) ?></strong>
                                        <span class="muted" style="font-size: 0.8rem; margin-left: 0.35rem;"><?= e($c['course_name']) ?></span>
                                    </div>
                                    <?php if ($left === 0): ?>
                                        <span class="badge badge-full">FULL</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--emerald-text);">
                                            <?= $left ?> seat<?= $left === 1 ? '' : 's' ?> left
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="progress-track" title="<?= $active ?> of <?= $total ?> seats occupied (<?= $pct ?>%)">
                                    <div class="progress-fill <?= $barClass ?>" style="width: <?= $pct ?>%;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--slate-500); margin-top: 0.25rem;">
                                    <span><?= e($c['schedule']) ?></span>
                                    <span><?= $active ?> / <?= $total ?> filled</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="admin/reports.php" class="stat-card-link-text" style="font-size: 0.825rem;">
                    View full capacity analytics &rarr;
                </a>
            </div>
        </div>

        <!-- ACID Guarantee Reassurance Card -->
        <div class="card" style="border-left: 4px solid var(--primary);">
            <div class="card-body">
                <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <div style="color: var(--primary); flex-shrink: 0; margin-top: 2px;">
                        <?= icon('shield', '', 24) ?>
                    </div>
                    <div>
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--slate-900); margin-bottom: 0.35rem;">
                            Transaction Integrity
                        </h3>
                        <p class="muted" style="margin: 0; font-size: 0.825rem; line-height: 1.5;">
                            All enrollment updates are safeguarded by MySQL database transactions. If an enrollment fails, slot reservations automatically roll back to prevent overbooking.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

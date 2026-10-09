<?php
// admin/reports.php -- Summary reports (GROUP BY / aggregate / LEFT JOIN queries)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Report.php';

$report    = new Report($db);
$totals    = $report->totals();
$byCourse  = $report->courseSummary();
$byClass   = $report->classSummary();
$byStudent = $report->studentSummary();

$pageTitle = 'Reports';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header & Breadcrumbs -->
<div class="page-header">
    <div class="page-header-content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="../index.php"><?= icon('home', '', 14) ?> Dashboard</a>
            <span class="sep">/</span>
            <span class="current">Reports &amp; Analytics</span>
        </nav>
        <h1 class="page-title">Analytics &amp; Enrollment Reports</h1>
        <p class="page-subtitle">Real-time aggregate analytics, capacity utilization metrics, and learner distributions.</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <?= icon('report', '', 16) ?>
            <span>Print Report</span>
        </button>
        <a href="enrollments.php" class="btn btn-primary">
            <?= icon('list', '', 16) ?>
            <span>View Ledger</span>
        </a>
    </div>
</div>

<!-- Executive Summary KPI Cards -->
<section class="stats-grid" aria-label="Summary KPIs">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Total Courses</span>
            <div class="stat-card-icon stat-icon-blue"><?= icon('book', '', 20) ?></div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['courses'] ?></div>
        <div class="stat-card-footer"><span>Catalog programs</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Class Sections</span>
            <div class="stat-card-icon stat-icon-indigo"><?= icon('calendar', '', 20) ?></div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['classes'] ?></div>
        <div class="stat-card-footer"><span>Active timetables</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Registered Students</span>
            <div class="stat-card-icon stat-icon-violet"><?= icon('student', '', 20) ?></div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['students'] ?></div>
        <div class="stat-card-footer"><span>Total learners</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Active Enrollments</span>
            <div class="stat-card-icon stat-icon-green"><?= icon('check-circle', '', 20) ?></div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['active_enrollments'] ?></div>
        <div class="stat-card-footer"><span>Confirmed seats</span></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Cancelled Slots</span>
            <div class="stat-card-icon stat-icon-rose"><?= icon('refresh', '', 20) ?></div>
        </div>
        <div class="stat-card-num"><?= (int) $totals['cancelled_enrollments'] ?></div>
        <div class="stat-card-footer"><span>Restored to quota</span></div>
    </div>
</section>

<!-- Section 1: Class Capacity Utilization -->
<div class="card">
    <div class="card-header">
        <div class="card-title-group">
            <div class="card-title-icon stat-icon-indigo">
                <?= icon('calendar', '', 18) ?>
            </div>
            <div>
                <h2 class="card-title">Class Capacity &amp; Seat Utilization</h2>
                <div class="card-subtitle">Real-time saturation percentage per section</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class Section</th>
                        <th>Course</th>
                        <th>Schedule &amp; Instructor</th>
                        <th class="num">Active</th>
                        <th class="num">Cancelled</th>
                        <th class="num">Slots Left</th>
                        <th class="num">Total Capacity</th>
                        <th style="min-width: 160px;">Capacity Utilization</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$byClass): ?>
                    <tr><td colspan="8" class="muted" style="text-align: center; padding: 2rem;">No class records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($byClass as $r):
                    $active    = (int) $r['active_count'];
                    $cancelled = (int) $r['cancelled_count'];
                    $slots     = (int) $r['slots_left'];
                    $capacity  = $active + $slots;
                    $pct       = $capacity > 0 ? round(($active / $capacity) * 100) : 0;
                    $fillClass = $slots === 0 ? 'fill-rose' : ($pct >= 75 ? 'fill-amber' : 'fill-green');
                ?>
                    <tr>
                        <td><span class="code-badge"><?= e($r['class_code']) ?></span></td>
                        <td><strong style="color: var(--slate-900);"><?= e($r['course_name']) ?></strong></td>
                        <td>
                            <div style="font-size: 0.825rem;">
                                <span><?= e($r['schedule'] ?: 'TBA') ?></span>
                                <span class="muted" style="margin-left: 0.35rem;">&bull; <?= e($r['instructor'] ?: 'Unassigned') ?></span>
                            </div>
                        </td>
                        <td class="num"><strong style="color: var(--slate-900);"><?= $active ?></strong></td>
                        <td class="num muted"><?= $cancelled ?></td>
                        <td class="num">
                            <?php if ($slots === 0): ?>
                                <span class="badge badge-full">FULL</span>
                            <?php else: ?>
                                <span style="font-weight: 700; color: var(--emerald-text);"><?= $slots ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="num muted"><?= $capacity ?></td>
                        <td>
                            <div class="capacity-meter">
                                <div class="capacity-meter-header">
                                    <span><?= $pct ?>% full</span>
                                    <span class="muted"><?= $active ?> of <?= $capacity ?></span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section 2: Course Enrollment Summary & Section 3: Student Distribution -->
<div class="page-grid-2col" style="grid-template-columns: 1fr 1fr; align-items: start;">
    <!-- Course Enrollment Summary -->
    <div class="card">
        <div class="card-header">
            <div class="card-title-group">
                <div class="card-title-icon stat-icon-blue">
                    <?= icon('book', '', 18) ?>
                </div>
                <div>
                    <h2 class="card-title">Enrollment per Course</h2>
                    <div class="card-subtitle">Aggregate seat distribution by subject</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course</th>
                            <th class="num">Classes</th>
                            <th class="num">Enrolled</th>
                            <th class="num">Slots Left</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$byCourse): ?>
                        <tr><td colspan="5" class="muted" style="text-align: center;">No course data available.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($byCourse as $r): ?>
                        <tr>
                            <td><span class="code-badge"><?= e($r['course_code']) ?></span></td>
                            <td><strong style="color: var(--slate-900); font-size: 0.85rem;"><?= e($r['course_name']) ?></strong></td>
                            <td class="num muted"><?= (int) $r['class_count'] ?></td>
                            <td class="num"><strong style="color: var(--primary);"><?= (int) $r['enrolled'] ?></strong></td>
                            <td class="num">
                                <span style="color: var(--emerald-text); font-weight: 600;"><?= (int) $r['slots_left'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Student Participation Distribution -->
    <div class="card">
        <div class="card-header">
            <div class="card-title-group">
                <div class="card-title-icon stat-icon-violet">
                    <?= icon('student', '', 18) ?>
                </div>
                <div>
                    <h2 class="card-title">Student Class Counts</h2>
                    <div class="card-subtitle">Number of active classes per learner</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Learner</th>
                            <th>Email</th>
                            <th class="num" style="width: 120px;">Active Classes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$byStudent): ?>
                        <tr><td colspan="3" class="muted" style="text-align: center;">No registered students found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($byStudent as $r): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <?= avatar($r['full_name'], 'sm') ?>
                                    <span class="user-cell-name" style="font-size: 0.85rem;"><?= e($r['full_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="muted" style="font-size: 0.825rem;"><?= e($r['email'] ?: '—') ?></span>
                            </td>
                            <td class="num">
                                <span class="badge badge-neutral" style="font-weight: 700;">
                                    <?= (int) $r['active_classes'] ?> class<?= (int) $r['active_classes'] === 1 ? '' : 'es' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

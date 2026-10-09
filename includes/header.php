<?php // includes/header.php
// Main layout header for EduEnroll Training Enrollment System.
// Pages in admin/ set $base = '../' before including this file so that
// CSS and navigation links resolve from both root and subdirectories.
require_once __DIR__ . '/helpers.php';

$base      = $base ?? '';
$adminPath = ($base === '') ? 'admin/' : '';
$pageTitle = $pageTitle ?? 'Dashboard';

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navItems = [
    [
        'id'    => 'home',
        'title' => 'Dashboard',
        'url'   => $base . 'index.php',
        'icon'  => 'home',
        'active'=> ($currentScript === 'index.php')
    ],
    [
        'id'    => 'courses',
        'title' => 'Courses',
        'url'   => $adminPath . 'courses.php',
        'icon'  => 'book',
        'active'=> ($currentScript === 'courses.php')
    ],
    [
        'id'    => 'classes',
        'title' => 'Classes',
        'url'   => $adminPath . 'classes.php',
        'icon'  => 'calendar',
        'active'=> ($currentScript === 'classes.php')
    ],
    [
        'id'    => 'students',
        'title' => 'Record Student',
        'url'   => $adminPath . 'students.php',
        'icon'  => 'user-plus',
        'active'=> ($currentScript === 'students.php')
    ],
    [
        'id'    => 'enroll',
        'title' => 'Quick Enroll',
        'url'   => $adminPath . 'enroll.php',
        'icon'  => 'enroll',
        'active'=> ($currentScript === 'enroll.php')
    ],
    [
        'id'    => 'enrollments',
        'title' => 'Enrollments',
        'url'   => $adminPath . 'enrollments.php',
        'icon'  => 'list',
        'active'=> ($currentScript === 'enrollments.php')
    ],
    [
        'id'    => 'reports',
        'title' => 'Reports',
        'url'   => $adminPath . 'reports.php',
        'icon'  => 'report',
        'active'=> ($currentScript === 'reports.php')
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &bull; EduEnroll Training Portal</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body class="site-body">

<header class="site-header">
    <div class="header-inner">
        <!-- Brand Logo -->
        <a href="<?= $base ?>index.php" class="brand-logo" aria-label="EduEnroll Home">
            <span class="brand-icon">
                <?= icon('graduation-cap', 'icon-brand', 22) ?>
            </span>
            <div class="brand-text">
                <span class="brand-name">EduEnroll</span>
                <span class="brand-tagline">Training Portal</span>
            </div>
        </a>

        <!-- Mobile Menu Toggle Button -->
        <button type="button" class="mobile-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu" aria-expanded="false">
            <span class="toggle-bar"></span>
            <span class="toggle-bar"></span>
            <span class="toggle-bar"></span>
        </button>

        <!-- Main Navigation Links -->
        <nav class="main-nav" id="mainNav" aria-label="Main Navigation">
            <ul class="nav-list">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a href="<?= $item['url'] ?>" class="nav-link <?= $item['active'] ? 'active' : '' ?>">
                            <span class="nav-icon"><?= icon($item['icon'], 'icon-nav', 18) ?></span>
                            <span class="nav-label"><?= e($item['title']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Header Right Utilities -->
            <div class="header-utilities">
                <div class="status-chip" title="System and Database status: Healthy">
                    <span class="status-dot pulse"></span>
                    <span class="status-text">Online</span>
                </div>

                <div class="user-chip">
                    <span class="user-avatar-badge">AD</span>
                    <div class="user-meta">
                        <span class="user-name">Admin</span>
                        <span class="user-sub">Operator</span>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="main-container">
        <?= flash_render() ?>

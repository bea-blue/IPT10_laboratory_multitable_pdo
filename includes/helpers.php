<?php
// includes/helpers.php
// Presentation & request helpers: escaping, flash messages, CSRF,
// pagination, sorting links, SVG icons, avatar generation, validation.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Output escaping -----------------------------------------------------
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---- Inline SVG Icon Helper (Zero external assets required) --------------
function icon($name, $class = 'icon', $size = 18) {
    $icons = [
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'course' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>',
        'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'class' => '<rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>',
        'student' => '<circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>',
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>',
        'enroll' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>',
        'enrollments' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'list' => '<line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/>',
        'report' => '<line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/>',
        'plus' => '<line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'alert-triangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/>',
        'x' => '<line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>',
        'arrow-right' => '<line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'refresh' => '<polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'graduation-cap' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
    ];

    $body = $icons[$name] ?? $icons['info'];
    return '<svg class="' . e($class) . '" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

// Generate initials avatar badge
function avatar($name, $size = 'sm') {
    $parts = preg_split('/\s+/', trim((string)$name));
    $initials = '';
    if (!empty($parts[0])) {
        $initials .= mb_substr($parts[0], 0, 1);
    }
    if (count($parts) > 1) {
        $initials .= mb_substr(end($parts), 0, 1);
    }
    $initials = strtoupper($initials ?: '?');

    // Deterministic soft color based on name
    $colors = [
        ['#eff6ff', '#1d4ed8'], // blue
        ['#f0fdf4', '#15803d'], // green
        ['#faf5ff', '#7e22ce'], // purple
        ['#fff7ed', '#c2410c'], // orange
        ['#ecfeff', '#0e7490'], // cyan
        ['#fdf2f8', '#be185d'], // pink
    ];
    $idx = abs(crc32($name)) % count($colors);
    $bg = $colors[$idx][0];
    $fg = $colors[$idx][1];

    return '<span class="avatar avatar-' . e($size) . '" style="background-color: ' . $bg . '; color: ' . $fg . ';" title="' . e($name) . '">' . e($initials) . '</span>';
}

// ---- Flash messages ------------------------------------------------------
function flash_set($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flash_render() {
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '<div class="flash-messages-container">';
    foreach ($_SESSION['flash'] as $f) {
        $type = $f['type'];
        $iconName = ($type === 'success') ? 'check-circle' : (($type === 'error') ? 'alert-triangle' : 'info');
        $html .= '<div class="alert alert-' . e($type) . '">
            <div class="alert-icon-wrap">' . icon($iconName, 'alert-icon', 20) . '</div>
            <div class="alert-body">
                <span class="alert-title">' . ($type === 'success' ? 'Success' : ($type === 'error' ? 'Notice' : 'Information')) . '</span>
                <span class="alert-message">' . e($f['msg']) . '</span>
            </div>
            <button type="button" class="alert-close" onclick="this.closest(\'.alert\').remove()" aria-label="Close message">' . icon('x', 'icon-close', 16) . '</button>
        </div>';
    }
    $html .= '</div>';
    unset($_SESSION['flash']);
    return $html;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// ---- CSRF protection -----------------------------------------------------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify() {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

// ---- Request input -------------------------------------------------------
function post_str($key) {
    return trim((string) ($_POST[$key] ?? ''));
}

function get_str($key) {
    return trim((string) ($_GET[$key] ?? ''));
}

function str_len($s) {
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

// ---- URL / table helpers -------------------------------------------------
function qs(array $override = [], array $remove = []) {
    $p = array_merge($_GET, $override);
    foreach ($remove as $k) {
        unset($p[$k]);
    }
    foreach ($p as $k => $v) {
        if ($v === '' || $v === null) {
            unset($p[$k]);
        }
    }
    return '?' . http_build_query($p);
}

// Modern clickable column header that toggles asc/desc
function sort_link($label, $column, $currentSort, $currentDir) {
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow = '<span class="sort-icon-neutral">↕</span>';
    $activeClass = '';
    if ($currentSort === $column) {
        $arrow = $currentDir === 'asc'
            ? '<span class="sort-icon-active asc">▲</span>'
            : '<span class="sort-icon-active desc">▼</span>';
        $activeClass = ' sorted-active';
    }
    $href = qs(['sort' => $column, 'dir' => $nextDir, 'page' => 1], ['edit']);
    return '<a class="table-sort-link' . $activeClass . '" href="' . e($href) . '">' . e($label) . ' ' . $arrow . '</a>';
}

// Polished pagination bar
function pager(array $win) {
    if ($win['pages'] <= 1) {
        return '<div class="pagination-container"><div class="pagination-info"><span class="badge badge-neutral">' . (int) $win['total'] . '</span> record' . ($win['total'] === 1 ? '' : 's') . ' found</div></div>';
    }

    $startItem = (($win['page'] - 1) * $win['perPage']) + 1;
    $endItem   = min($win['total'], $win['page'] * $win['perPage']);

    $html = '<div class="pagination-container">';
    $html .= '<div class="pagination-info">Showing <strong>' . $startItem . '&ndash;' . $endItem . '</strong> of <strong>' . (int) $win['total'] . '</strong> records</div>';
    $html .= '<nav class="pager" aria-label="Pagination">';

    // Prev Button
    if ($win['page'] > 1) {
        $html .= '<a class="pager-btn pager-prev" href="' . e(qs(['page' => $win['page'] - 1], ['edit'])) . '">&larr; Prev</a>';
    } else {
        $html .= '<span class="pager-btn pager-disabled">&larr; Prev</span>';
    }

    $from = max(1, $win['page'] - 2);
    $to   = min($win['pages'], $win['page'] + 2);
    for ($i = $from; $i <= $to; $i++) {
        $html .= $i === $win['page']
            ? '<span class="pager-btn current" aria-current="page">' . $i . '</span>'
            : '<a class="pager-btn" href="' . e(qs(['page' => $i], ['edit'])) . '">' . $i . '</a>';
    }

    // Next Button
    if ($win['page'] < $win['pages']) {
        $html .= '<a class="pager-btn pager-next" href="' . e(qs(['page' => $win['page'] + 1], ['edit'])) . '">Next &rarr;</a>';
    } else {
        $html .= '<span class="pager-btn pager-disabled">Next &rarr;</span>';
    }

    $html .= '</nav></div>';
    return $html;
}

// ---- Server-side validation ----------------------------------------------
function validate_student($name, $email, $phone) {
    $errors = [];
    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (str_len($name) < 2 || str_len($name) > 100) {
        $errors[] = 'Full name must be 2 to 100 characters.';
    } elseif (!preg_match("/^[\\p{L}][\\p{L}\\s.'-]*$/u", $name)) {
        $errors[] = 'Full name may only contain letters, spaces, periods, apostrophes and hyphens.';
    }
    if ($email !== '') {
        if (str_len($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address (max 100 characters).';
        }
    }
    if ($phone !== '' && !preg_match('/^[0-9+()\\s-]{7,30}$/', $phone)) {
        $errors[] = 'Phone may only contain digits, spaces, + ( ) - and be 7 to 30 characters long.';
    }
    return $errors;
}

function validate_course($code, $name, $description) {
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_-]{2,20}$/', $code)) {
        $errors[] = 'Course code must be 2 to 20 characters: letters, numbers, dash or underscore.';
    }
    if ($name === '' || str_len($name) < 3 || str_len($name) > 100) {
        $errors[] = 'Course name is required (3 to 100 characters).';
    }
    if (str_len($description) > 1000) {
        $errors[] = 'Description must be 1000 characters or fewer.';
    }
    return $errors;
}

function validate_class($course_id, $code, $schedule, $instructor, $slots_raw) {
    $errors = [];
    if ($course_id <= 0) {
        $errors[] = 'Please choose a course.';
    }
    if ($code === '' || str_len($code) > 20) {
        $errors[] = 'Class code is required (max 20 characters).';
    }
    if (str_len($schedule) > 100) {
        $errors[] = 'Schedule must be 100 characters or fewer.';
    }
    if (str_len($instructor) > 100) {
        $errors[] = 'Instructor must be 100 characters or fewer.';
    }
    if (!preg_match('/^\d{1,4}$/', $slots_raw)) {
        $errors[] = 'Slots must be a whole number from 0 to 9999.';
    }
    return $errors;
}

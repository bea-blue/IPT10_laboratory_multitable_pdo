<?php
// classes/Paginator.php
// Tiny helper that turns (total rows, requested page, page size) into a safe
// page number and OFFSET, so every search() method paginates the same way.
class Paginator {
    public static function window($total, $page, $perPage) {
        $perPage = max(1, (int) $perPage);
        $total   = max(0, (int) $total);
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = min(max(1, (int) $page), $pages);   // clamp into 1..$pages
        return [
            'page'    => $page,
            'pages'   => $pages,
            'perPage' => $perPage,
            'offset'  => ($page - 1) * $perPage,
            'total'   => $total,
        ];
    }

    // Returns $sort if it is a key of $allowed, otherwise $default.
    public static function pick($value, array $allowed, $default) {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function dir($value, $default = 'asc') {
        $value = strtolower((string) $value);
        return in_array($value, ['asc', 'desc'], true) ? $value : $default;
    }
}

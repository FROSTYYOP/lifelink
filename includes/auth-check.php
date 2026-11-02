<?php
/**
 * LifeLink - Authentication & Role-Based Authorization Guard
 * Usage:
 *   require_once __DIR__ . '/../includes/auth-check.php';
 *   // optionally: require_roles('admin') or require_roles('donor', 'admin');
 */

require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    set_flash('warning', 'Please sign in to access this page.');
    $loginUrl = url('auth/login.php');
    header("Location: $loginUrl");
    exit;
}

/**
 * Enforces role restriction on specific controller scripts
 */
function require_roles(string ...$allowedRoles): void {
    if (!has_role(...$allowedRoles)) {
        set_flash('danger', 'Access denied. You do not have permission to access that resource.');
        $currentRole = $_SESSION['user_role'] ?? 'user';
        if ($currentRole === 'admin') {
            header("Location: " . url('admin/dashboard.php'));
        } elseif ($currentRole === 'donor') {
            header("Location: " . url('donor/dashboard.php'));
        } else {
            header("Location: " . url('index.php'));
        }
        exit;
    }
}

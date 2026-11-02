<?php
/**
 * LifeLink - Reusable Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Escape HTML output to prevent XSS attacks
 */
function e(?string $string): string {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get or generate CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST request
 */
function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            set_flash('danger', 'Security validation failed (invalid CSRF token). Please try again.');
            return false;
        }
    }
    return true;
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user array
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user',
    ];
}

/**
 * Check if the user has a specific role
 */
function has_role(string ...$roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    $currentRole = $_SESSION['user_role'] ?? 'user';
    return in_array($currentRole, $roles, true);
}

/**
 * Set flash alert message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash alert message
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert in Bootstrap 5 format
 */
function display_flash(): void {
    $flash = get_flash();
    if ($flash) {
        $type = e($flash['type']);
        $message = e($flash['message']);
        $icon = match ($type) {
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-diamond-fill',
            default   => 'bi-info-circle-fill',
        };

        echo <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi {$icon} fs-5 me-2"></i>
            <div>{$message}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
HTML;
    }
}

/**
 * Add a notification record to the database
 */
function add_notification(PDO $pdo, int $userId, string $message, string $type = 'info'): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        return $stmt->execute([$userId, $message, $type]);
    } catch (PDOException $e) {
        error_log("Failed to insert notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Relative base URL generator for zero-vhost / relative path reliability
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    // Determine depth relative to current executing script
    // If executing in root, prefix is './', if in subfolder (auth/, donor/, admin/), prefix is '../'
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    // Check if script is inside a 1-level subfolder like /auth, /donor, /admin, /public
    if (preg_match('#/(auth|donor|admin|public)$#i', $scriptDir)) {
        return '../' . $path;
    }
    return './' . $path;
}

/**
 * Render urgency badge HTML
 */
function urgency_badge(string $urgency): string {
    if (strtolower($urgency) === 'critical') {
        return '<span class="badge bg-danger text-white px-2 py-1 shadow-sm pulse-badge"><i class="bi bi-exclamation-octagon-fill me-1"></i>Critical</span>';
    }
    return '<span class="badge bg-secondary text-white px-2 py-1">Normal</span>';
}

/**
 * Render blood group badge HTML
 */
function blood_badge(string $bloodGroup): string {
    return '<span class="badge blood-badge text-white px-2 py-1 fs-6"><i class="bi bi-droplet-fill me-1"></i>' . e($bloodGroup) . '</span>';
}

/**
 * Render status badge HTML
 */
function status_badge(string $status): string {
    return match (strtolower($status)) {
        'verified', 'available', 'fulfilled' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>' . ucfirst($status) . '</span>',
        'pending'                            => '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Pending</span>',
        'rejected', 'cancelled', 'unavailable' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>' . ucfirst($status) . '</span>',
        'open'                               => '<span class="badge bg-primary"><i class="bi bi-broadcast me-1"></i>Open</span>',
        default                              => '<span class="badge bg-secondary">' . ucfirst(e($status)) . '</span>',
    };
}

/**
 * Generate a pre-formatted WhatsApp SOS emergency sharing URL
 */
function whatsapp_sos_link(string $hospital, string $bloodGroup, string $city, string $contact, int $units = 1, string $urgency = 'critical'): string {
    $urgencyText = (strtolower($urgency) === 'critical') ? 'CRITICAL EMERGENCY' : 'Urgent';
    $message = "🚨 *LIFE LINK - " . $urgencyText . "* 🚨\n\n"
             . "🩸 *Blood Group Needed:* *" . $bloodGroup . "* (" . $units . " Unit" . ($units > 1 ? "s" : "") . ")\n"
             . "🏥 *Hospital:* " . $hospital . ", " . $city . "\n"
             . "📞 *Contact Person:* " . $contact . "\n\n"
             . "📍 *Directions:* https://www.google.com/maps/search/?api=1&query=" . urlencode($hospital . ', ' . $city) . "\n\n"
             . "Please forward this message to eligible donors. A single share could save a life!";
    return "https://api.whatsapp.com/send?text=" . urlencode($message);
}

/**
 * Generate Google Maps navigation search link
 */
function maps_url(string $hospital, string $city = ''): string {
    $query = trim($hospital . ($city ? ', ' . $city : ''));
    return "https://www.google.com/maps/search/?api=1&query=" . urlencode($query);
}

/**
 * Return array of compatible donor blood groups for a patient's blood type
 * Transfusion Rule: Red blood cell compatibility
 */
function get_compatible_blood_groups(string $patientBloodGroup): array {
    return match (strtoupper(trim($patientBloodGroup))) {
        'A+'  => ['A+', 'A-', 'O+', 'O-'],
        'A-'  => ['A-', 'O-'],
        'B+'  => ['B+', 'B-', 'O+', 'O-'],
        'B-'  => ['B-', 'O-'],
        'AB-' => ['AB-', 'A-', 'B-', 'O-'],
        'O+'  => ['O+', 'O-'],
        'O-'  => ['O-'],
        default => [trim($patientBloodGroup)],
    };
}

/**
 * Return array of patient blood groups that a specific donor can donate to
 */
function get_recipients_for_donor(string $donorBloodGroup): array {
    return match (strtoupper(trim($donorBloodGroup))) {
        'O-'  => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], // Universal Donor
        'O+'  => ['O+', 'A+', 'B+', 'AB+'],
        'A-'  => ['A-', 'A+', 'AB-', 'AB+'],
        'A+'  => ['A+', 'AB+'],
        'B-'  => ['B-', 'B+', 'AB-', 'AB+'],
        'B+'  => ['B+', 'AB+'],
        'AB-' => ['AB-', 'AB+'],
        'AB+' => ['AB+'], // Can only donate to AB+
        default => [trim($donorBloodGroup)],
    };
}



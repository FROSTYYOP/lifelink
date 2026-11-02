<?php
/**
 * LifeLink - Authentication: Login
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role === 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($role === 'donor') {
        header('Location: ../donor/dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security session. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $emailValue = $email;

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email and password.';
        } else {
            // Prepared statement query
            $stmt = $pdo->prepare("SELECT id, name, email, phone, password_hash, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Successful authentication
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                set_flash('success', "Welcome back, {$user['name']}!");

                // Role-based routing
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } elseif ($user['role'] === 'donor') {
                    header('Location: ../donor/dashboard.php');
                } else {
                    header('Location: ../search.php');
                }
                exit;
            } else {
                $error = 'Invalid email address or password. Please check your credentials.';
            }
        }
    }
}

$pageTitle = 'Sign In - LifeLink';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-burgundy-subtle text-burgundy mb-2">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-burgundy mb-1">Welcome Back</h3>
                    <p class="text-muted small">Sign in to access your LifeLink account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($emailValue) ?>" placeholder="e.g. user@lifelink.com" required autofocus>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label fw-semibold mb-0">Password</label>
                        </div>
                        <div class="input-group mt-1">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle-btn" data-target="#password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>

                    <button type="submit" class="btn btn-burgundy w-100 py-2 mt-2 mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>

                    <div class="text-center small text-muted">
                        Don't have an account? <a href="register.php" class="text-burgundy fw-semibold text-decoration-none">Create an Account</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- College Demo Quick-Credentials Card (Visible only on Localhost/Development) -->
        <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
        <div class="card border-0 bg-light rounded-3 mt-4 shadow-sm">
            <div class="card-body p-3 small">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-lightning-fill text-warning me-1"></i>Demo Pre-seeded Credentials:</span>
                    <span class="badge bg-secondary">Viva Demo Helper</span>
                </div>
                <div class="row g-2 text-muted">
                    <div class="col-12 col-sm-4">
                        <div class="p-2 bg-white rounded border">
                            <span class="badge bg-danger d-block mb-1">Admin</span>
                            <code>admin@lifelink.com</code><br>
                            <code>Admin@123</code>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-2 bg-white rounded border">
                            <span class="badge bg-burgundy d-block mb-1">Verified Donor</span>
                            <code>john@lifelink.com</code><br>
                            <code>Donor@123</code>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-2 bg-white rounded border">
                            <span class="badge bg-primary d-block mb-1">User</span>
                            <code>user@lifelink.com</code><br>
                            <code>User@123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

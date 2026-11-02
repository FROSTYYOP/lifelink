<?php
/**
 * LifeLink - Authentication: Register (User & Donor)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$formData = [
    'name'        => '',
    'email'       => '',
    'phone'       => '',
    'role'        => 'donor', // default to donor registration
    'blood_group' => 'O+',
    'city'        => '',
    'address'     => '',
    'age'         => '24',
    'gender'      => 'Male',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security session. Please try again.';
    } else {
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $password    = $_POST['password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        $role        = in_array($_POST['role'] ?? '', ['donor', 'user'], true) ? $_POST['role'] : 'user';

        // Additional donor fields
        $bloodGroup  = $_POST['blood_group'] ?? 'O+';
        $city        = trim($_POST['city'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $age         = (int) ($_POST['age'] ?? 0);
        $gender      = $_POST['gender'] ?? 'Male';

        $formData = compact('name', 'email', 'phone', 'role', 'bloodGroup', 'city', 'address', 'age', 'gender');

        // Validation
        if (empty($name)) {
            $errors[] = 'Full name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (empty($phone)) {
            $errors[] = 'Contact phone number is required.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if ($password !== $confirmPass) {
            $errors[] = 'Passwords do not match.';
        }

        if ($role === 'donor') {
            $validBloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            if (!in_array($bloodGroup, $validBloodGroups, true)) {
                $errors[] = 'Please select a valid blood group.';
            }
            if (empty($city)) {
                $errors[] = 'City is required for blood donors.';
            }
            if ($age < 18 || $age > 65) {
                $errors[] = 'Blood donor age must be between 18 and 65 years.';
            }
        }

        // Check if email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please sign in instead.';
            }
        }

        // Process Registration in a Transaction
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $phone, $passwordHash, $role]);
                $userId = (int) $pdo->lastInsertId();

                if ($role === 'donor') {
                    $stmtDonor = $pdo->prepare("INSERT INTO donors (user_id, blood_group, city, address, age, gender, availability_status, verified_status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'available', 'pending', NOW())");
                    $stmtDonor->execute([$userId, $bloodGroup, $city, $address, $age, $gender]);

                    // Add initial notification
                    add_notification($pdo, $userId, "Welcome to LifeLink! Your profile has been submitted and is currently pending verification by our admin team.", "welcome");
                }

                $pdo->commit();

                // Auto-login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;

                if ($role === 'donor') {
                    set_flash('success', 'Registration successful! Welcome to the LifeLink Donor community. Your profile is currently pending verification.');
                    header('Location: ../donor/dashboard.php');
                } else {
                    set_flash('success', 'Account created successfully! You can now search donors and post blood requests.');
                    header('Location: ../search.php');
                }
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Registration failed due to a server error. Please try again.';
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Join LifeLink - Register';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-burgundy-subtle text-burgundy mb-2">
                        <i class="bi bi-person-plus-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-burgundy mb-1">Create LifeLink Account</h3>
                    <p class="text-muted small">Register as a life-saving blood donor or community requester</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="needs-validation" novalidate id="registerForm">
                    <?= csrf_field() ?>

                    <!-- Account Type Selector -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">I want to register as:</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="card card-hover p-3 h-100 border text-start cursor-pointer position-relative">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input me-3 mt-0" type="radio" name="role" id="roleDonor" value="donor" <?= $formData['role'] === 'donor' ? 'checked' : '' ?>>
                                        <div>
                                            <div class="fw-bold text-burgundy"><i class="bi bi-droplet-fill me-1"></i>Volunteer Blood Donor</div>
                                            <div class="small text-muted">Ready to donate blood & save lives during emergencies</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="card card-hover p-3 h-100 border text-start cursor-pointer position-relative">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input me-3 mt-0" type="radio" name="role" id="roleUser" value="user" <?= $formData['role'] === 'user' ? 'checked' : '' ?>>
                                        <div>
                                            <div class="fw-bold text-dark"><i class="bi bi-person-heart me-1"></i>General User / Requester</div>
                                            <div class="small text-muted">Seek blood donations and manage emergency requests</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($formData['name']) ?>" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($formData['email']) ?>" placeholder="e.g. john@example.com" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>" placeholder="e.g. +91 98765 43210" required>
                            <div class="form-text small">Used for emergency donor verification & notifications.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label fw-semibold">City / Location</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e($formData['city']) ?>" placeholder="e.g. Mumbai, Pune, Delhi" required>
                        </div>
                    </div>

                    <!-- Donor-Specific Fields -->
                    <div id="donorFieldsSection" style="<?= $formData['role'] === 'user' ? 'display:none;' : '' ?>">
                        <div class="p-3 bg-burgundy-subtle rounded-3 mb-3 border border-burgundy border-opacity-25">
                            <h6 class="fw-bold text-burgundy mb-3"><i class="bi bi-heart-pulse-fill me-1"></i>Donor Medical Profile</h6>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="blood_group" class="form-label fw-semibold">Blood Group</label>
                                    <select class="form-select" id="blood_group" name="blood_group">
                                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                            <option value="<?= $bg ?>" <?= ($formData['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="age" class="form-label fw-semibold">Age (18 - 65)</label>
                                    <input type="number" class="form-control" id="age" name="age" min="18" max="65" value="<?= e($formData['age']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label fw-semibold">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="Male" <?= ($formData['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($formData['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($formData['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label for="address" class="form-label fw-semibold">Address / Area Details</label>
                                <textarea class="form-control" id="address" name="address" rows="2" placeholder="Locality, Landmark or Street address"><?= e($formData['address']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Passwords -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Password (min 6 chars)</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-burgundy w-100 py-2 mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i> Complete Registration
                    </button>

                    <div class="text-center small text-muted">
                        Already have an account? <a href="login.php" class="text-burgundy fw-semibold text-decoration-none">Sign In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleRadios = document.querySelectorAll('input[name="role"]');
    const donorSection = document.getElementById('donorFieldsSection');
    roleRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'donor') {
                donorSection.style.display = 'block';
            } else {
                donorSection.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

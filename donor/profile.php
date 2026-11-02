<?php
/**
 * LifeLink - Donor Profile Management
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('donor', 'admin');

$userId = (int) $_SESSION['user_id'];
$errors = [];

// Fetch Donor and User details
$stmt = $pdo->prepare("SELECT d.*, u.name, u.email, u.phone 
                       FROM donors d 
                       JOIN users u ON d.user_id = u.id 
                       WHERE d.user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$donor = $stmt->fetch();

if (!$donor) {
    set_flash('danger', 'Donor record not found.');
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security session token. Please try again.';
    } else {
        $name        = trim($_POST['name'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $city        = trim($_POST['city'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $age         = (int)($_POST['age'] ?? 0);
        $gender      = $_POST['gender'] ?? 'Male';
        $lastDonationDate = !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : null;
        $availabilityStatus = in_array($_POST['availability_status'] ?? '', ['available', 'unavailable'], true) ? $_POST['availability_status'] : 'available';

        if (empty($name)) {
            $errors[] = 'Full name cannot be empty.';
        }
        if (empty($phone)) {
            $errors[] = 'Phone number is required.';
        }
        if (empty($city)) {
            $errors[] = 'City is required.';
        }
        if ($age < 18 || $age > 65) {
            $errors[] = 'Age must be between 18 and 65 years.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Update users table
                $uStmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $uStmt->execute([$name, $phone, $userId]);
                $_SESSION['user_name'] = $name;

                // 2. Update donors table
                $dStmt = $pdo->prepare("UPDATE donors SET city = ?, address = ?, age = ?, gender = ?, last_donation_date = ?, availability_status = ? WHERE user_id = ?");
                $dStmt->execute([$city, $address, $age, $gender, $lastDonationDate, $availabilityStatus, $userId]);

                $pdo->commit();
                set_flash('success', 'Your donor profile and medical information have been successfully updated.');
                header('Location: dashboard.php');
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Donor Profile - LifeLink';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold text-dark mb-0">
                        <i class="bi bi-person-gear text-burgundy me-2"></i>Edit Donor Profile
                    </h4>
                    <span class="small text-muted">Keep your contact and donation availability updated</span>
                </div>
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <!-- Account Info -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($donor['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email (Read Only)</label>
                            <input type="email" class="form-control bg-light" id="email" value="<?= e($donor['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($donor['phone']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label fw-semibold">City / Location</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e($donor['city']) ?>" required>
                        </div>
                    </div>

                    <!-- Medical & Donor Details -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <h6 class="fw-bold text-burgundy mb-3"><i class="bi bi-heart-pulse-fill me-1"></i>Medical Details</h6>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Blood Group</label>
                                <div><?= blood_badge($donor['blood_group']) ?> <span class="small text-muted ms-1">(Fixed)</span></div>
                            </div>
                            <div class="col-md-4">
                                <label for="age" class="form-label fw-semibold">Age (Years)</label>
                                <input type="number" class="form-control" id="age" name="age" min="18" max="65" value="<?= (int)$donor['age'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="gender" class="form-label fw-semibold">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="Male" <?= $donor['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $donor['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $donor['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label for="last_donation_date" class="form-label fw-semibold">Last Donation Date</label>
                                <input type="date" class="form-control" id="last_donation_date" name="last_donation_date" value="<?= e($donor['last_donation_date'] ?? '') ?>">
                                <div class="form-text small">Used to calculate your 90-day cooldown period.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="availability_status" class="form-label fw-semibold">Donation Availability</label>
                                <select class="form-select" id="availability_status" name="availability_status">
                                    <option value="available" <?= $donor['availability_status'] === 'available' ? 'selected' : '' ?>>Available for Emergency Requests</option>
                                    <option value="unavailable" <?= $donor['availability_status'] === 'unavailable' ? 'selected' : '' ?>>Temporarily Unavailable / Resting</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="address" class="form-label fw-semibold">Address / Locality Details</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= e($donor['address']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-burgundy px-4 py-2">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

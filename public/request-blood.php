<?php
/**
 * LifeLink - Emergency Blood Request Submission
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = false;

$currentUser = current_user();

$formData = [
    'requester_name'    => $currentUser['name'] ?? '',
    'requester_contact' => '',
    'blood_group'       => 'O+',
    'city'              => '',
    'hospital_name'     => '',
    'units_needed'      => '1',
    'urgency'           => 'critical', // Default to critical for emergency
];

// Pre-fill user contact if logged in
if ($currentUser) {
    try {
        $uStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $uStmt->execute([$currentUser['id']]);
        $formData['requester_contact'] = $uStmt->fetchColumn() ?: '';
    } catch (PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security session token. Please try again.';
    } else {
        $requesterName    = trim($_POST['requester_name'] ?? '');
        $requesterContact = trim($_POST['requester_contact'] ?? '');
        $bloodGroup       = $_POST['blood_group'] ?? 'O+';
        $city             = trim($_POST['city'] ?? '');
        $hospitalName     = trim($_POST['hospital_name'] ?? '');
        $unitsNeeded      = (int)($_POST['units_needed'] ?? 1);
        $urgency          = in_array($_POST['urgency'] ?? '', ['normal', 'critical'], true) ? $_POST['urgency'] : 'normal';

        $formData = compact('requesterName', 'requesterContact', 'bloodGroup', 'city', 'hospitalName', 'unitsNeeded', 'urgency');

        // Validation
        if (empty($requesterName)) {
            $errors[] = 'Requester or patient name is required.';
        }
        if (empty($requesterContact)) {
            $errors[] = 'Emergency contact telephone number is required.';
        }
        if (empty($city)) {
            $errors[] = 'City / Location is required.';
        }
        if (empty($hospitalName)) {
            $errors[] = 'Hospital or medical facility name is required.';
        }
        if ($unitsNeeded < 1 || $unitsNeeded > 20) {
            $errors[] = 'Units needed must be between 1 and 20.';
        }

        if (empty($errors)) {
            try {
                $userId = $currentUser ? $currentUser['id'] : null;

                $stmt = $pdo->prepare("INSERT INTO blood_requests 
                    (requester_name, requester_contact, blood_group, city, hospital_name, units_needed, urgency, status, user_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?, NOW())");
                $stmt->execute([
                    $requesterName,
                    $requesterContact,
                    $bloodGroup,
                    $city,
                    $hospitalName,
                    $unitsNeeded,
                    $urgency,
                    $userId
                ]);

                $requestId = (int) $pdo->lastInsertId();

                // Alert matching verified donors via notification
                $notifStmt = $pdo->prepare("SELECT u.id FROM donors d JOIN users u ON d.user_id = u.id WHERE d.blood_group = ? AND LOWER(d.city) = LOWER(?) AND d.verified_status = 'verified'");
                $notifStmt->execute([$bloodGroup, $city]);
                $matchingUserIds = $notifStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($matchingUserIds as $uId) {
                    add_notification($pdo, (int)$uId, "URGENT: {$unitsNeeded} unit(s) of {$bloodGroup} needed at {$hospitalName}, {$city}!", "emergency");
                }

                // Also notify admin
                $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                $adminIds = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($adminIds as $aId) {
                    add_notification($pdo, (int)$aId, "New {$urgency} blood request for {$bloodGroup} blood at {$hospitalName} ({$city}).", "admin");
                }

                set_flash('success', "Your emergency blood request for {$unitsNeeded} unit(s) of {$bloodGroup} has been published successfully! Nearby donors are being notified.");
                header("Location: search.php?blood_group=" . urlencode($bloodGroup) . "&city=" . urlencode($city));
                exit;

            } catch (PDOException $e) {
                $errors[] = 'Failed to submit request due to a server error. Please try again.';
                error_log("Request Blood Error: " . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Emergency Blood Request - LifeLink';
$activeNav = 'request';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-burgundy text-white p-4">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 p-3 rounded-circle me-3">
                        <i class="bi bi-exclamation-octagon-fill fs-2 text-white"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold">Emergency Blood Request</h4>
                        <p class="mb-0 small text-white text-opacity-75">Broadcast an urgent requirement to voluntary donors</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Submission Errors:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="alert alert-warning d-flex align-items-center mb-4 small" role="alert">
                    <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
                    <div>
                        <strong>Emergency Tip:</strong> Please provide accurate hospital and contact details so donors can coordinate and reach the blood bank without delay.
                    </div>
                </div>

                <form action="request-blood.php" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="requester_name" class="form-label fw-semibold">Patient / Requester Name</label>
                            <input type="text" class="form-control" id="requester_name" name="requester_name" value="<?= e($formData['requester_name']) ?>" placeholder="e.g. Ramesh Gupta" required>
                        </div>
                        <div class="col-md-6">
                            <label for="requester_contact" class="form-label fw-semibold">Emergency Phone Number</label>
                            <input type="tel" class="form-control" id="requester_contact" name="requester_contact" value="<?= e($formData['requester_contact']) ?>" placeholder="e.g. +91 98765 43210" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="blood_group" class="form-label fw-semibold">Blood Group Required</label>
                            <select class="form-select" id="blood_group" name="blood_group" required>
                                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= ($formData['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="units_needed" class="form-label fw-semibold">Units Needed (Bags)</label>
                            <input type="number" class="form-control" id="units_needed" name="units_needed" min="1" max="20" value="<?= e($formData['units_needed']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="urgency" class="form-label fw-semibold">Urgency Level</label>
                            <select class="form-select" id="urgency" name="urgency" required>
                                <option value="critical" <?= ($formData['urgency'] ?? '') === 'critical' ? 'selected' : '' ?>>🚨 Critical (Within 2-4 hrs)</option>
                                <option value="normal" <?= ($formData['urgency'] ?? '') === 'normal' ? 'selected' : '' ?>>Standard (Within 24-48 hrs)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="hospital_name" class="form-label fw-semibold">Hospital Name & Ward</label>
                            <input type="text" class="form-control" id="hospital_name" name="hospital_name" value="<?= e($formData['hospital_name']) ?>" placeholder="e.g. Lilavati Hospital, ICU Ward 3" required>
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label fw-semibold">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e($formData['city']) ?>" placeholder="e.g. Mumbai, Pune, Delhi" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-burgundy w-100 py-3 fw-bold fs-6">
                        <i class="bi bi-broadcast me-2"></i> Broadcast Blood Request Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

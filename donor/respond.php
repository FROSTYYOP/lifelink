<?php
/**
 * LifeLink - Donor Response / Pledge Handler
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('donor', 'admin');

$userId = (int) $_SESSION['user_id'];
$requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);

if (!$requestId) {
    set_flash('danger', 'Invalid emergency blood request specified.');
    header('Location: dashboard.php');
    exit;
}

// Fetch donor record
$dStmt = $pdo->prepare("SELECT * FROM donors WHERE user_id = ? LIMIT 1");
$dStmt->execute([$userId]);
$donor = $dStmt->fetch();

if (!$donor) {
    set_flash('danger', 'Donor profile not found.');
    header('Location: dashboard.php');
    exit;
}

// Fetch the blood request
$rStmt = $pdo->prepare("SELECT * FROM blood_requests WHERE id = ? LIMIT 1");
$rStmt->execute([$requestId]);
$request = $rStmt->fetch();

if (!$request || $request['status'] !== 'open') {
    set_flash('warning', 'This blood request has either been fulfilled or is no longer active.');
    header('Location: dashboard.php');
    exit;
}

// Check if donor already pledged to this request
$checkStmt = $pdo->prepare("SELECT id FROM donor_responses WHERE request_id = ? AND donor_id = ?");
$checkStmt->execute([$requestId, $donor['id']]);
$existingPledge = $checkStmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security session token.';
    } else {
        $message = trim($_POST['message'] ?? 'I am willing to donate blood for this emergency request.');

        if (empty($errors)) {
            try {
                if ($existingPledge) {
                    // Update existing pledge message
                    $stmt = $pdo->prepare("UPDATE donor_responses SET message = ?, status = 'pledged', created_at = NOW() WHERE id = ?");
                    $stmt->execute([$message, $existingPledge['id']]);
                } else {
                    // Insert new pledge
                    $stmt = $pdo->prepare("INSERT INTO donor_responses (request_id, donor_id, message, status, created_at) VALUES (?, ?, ?, 'pledged', NOW())");
                    $stmt->execute([$requestId, $donor['id'], $message]);
                }

                // Notify requester if they have a registered user account
                if (!empty($request['user_id'])) {
                    add_notification($pdo, (int)$request['user_id'], "A donor ({$_SESSION['user_name']}) has pledged to donate blood for your request at {$request['hospital_name']}!", "pledge");
                }

                set_flash('success', "Thank you! Your pledge to donate for this emergency request at {$request['hospital_name']} has been registered.");
                header('Location: dashboard.php');
                exit;

            } catch (PDOException $e) {
                $errors[] = 'Failed to submit pledge: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Pledge Blood Donation - LifeLink';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-burgundy text-white p-4">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-white bg-opacity-25 rounded-circle me-3">
                        <i class="bi bi-hand-thumbs-up-fill fs-2 text-white"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Confirm Donation Pledge</h4>
                        <span class="small text-white text-opacity-75">Connect with the patient & hospital</span>
                    </div>
                </div>
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

                <!-- Request Summary Card -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold fs-5 text-dark"><?= e($request['hospital_name']) ?></span>
                        <div>
                            <?= blood_badge($request['blood_group']) ?>
                            <span class="ms-1"><?= urgency_badge($request['urgency']) ?></span>
                        </div>
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($request['city']) ?> &bull; 
                        Units Needed: <strong><?= (int)$request['units_needed'] ?> Bag(s)</strong>
                    </div>
                    <div class="small text-muted">
                        Requester: <strong><?= e($request['requester_name']) ?></strong> &bull; 
                        Phone: <a href="tel:<?= e($request['requester_contact']) ?>" class="fw-bold text-success"><?= e($request['requester_contact']) ?></a>
                    </div>
                </div>

                <form action="respond.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= $requestId ?>">

                    <div class="mb-4">
                        <label for="message" class="form-label fw-semibold">Note / Message to Hospital or Patient (Optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="3" placeholder="e.g. I am in Powai and can reach the blood bank within 45 minutes."></textarea>
                        <div class="form-text small">Let them know your approximate arrival time or any coordination notes.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-burgundy px-4 py-2 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Submit Pledge
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

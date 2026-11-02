<?php
/**
 * LifeLink - Donor Dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Enforce donor role
require_roles('donor', 'admin');

$userId = (int) $_SESSION['user_id'];

// Fetch Donor Record
$stmt = $pdo->prepare("SELECT d.*, u.name, u.email, u.phone 
                       FROM donors d 
                       JOIN users u ON d.user_id = u.id 
                       WHERE d.user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$donor = $stmt->fetch();

if (!$donor) {
    set_flash('danger', 'Donor profile not found. Please contact support.');
    header('Location: ../index.php');
    exit;
}

// Handle Availability Toggle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_availability') {
    if (verify_csrf()) {
        $newStatus = ($donor['availability_status'] === 'available') ? 'unavailable' : 'available';
        $updateStmt = $pdo->prepare("UPDATE donors SET availability_status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $donor['id']]);
        set_flash('success', "Your donation status has been updated to " . ucfirst($newStatus) . ".");
        header('Location: dashboard.php');
        exit;
    }
}

// Handle Mark Notifications Read
if (isset($_GET['action']) && $_GET['action'] === 'read_notifications') {
    $readStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $readStmt->execute([$userId]);
    header('Location: dashboard.php');
    exit;
}

// Fetch Matching Emergency Blood Requests
// Matching exact blood group OR compatible recipient types in the same city
$recipientGroups = get_recipients_for_donor($donor['blood_group']);
$placeholders = implode(',', array_fill(0, count($recipientGroups), '?'));
$matchingParams = array_merge($recipientGroups, [$donor['city']]);

$matchingRequestsStmt = $pdo->prepare("SELECT * FROM blood_requests 
                                      WHERE status = 'open' 
                                      AND blood_group IN ($placeholders) 
                                      AND LOWER(city) = LOWER(?)
                                      ORDER BY (urgency = 'critical') DESC, created_at DESC");
$matchingRequestsStmt->execute($matchingParams);
$matchingRequests = $matchingRequestsStmt->fetchAll();

// Fetch Donor's Pledged Responses
$pledgesStmt = $pdo->prepare("SELECT dr.*, br.hospital_name, br.city, br.blood_group, br.units_needed, br.requester_name, br.requester_contact, br.status as req_status
                              FROM donor_responses dr
                              JOIN blood_requests br ON dr.request_id = br.id
                              WHERE dr.donor_id = ?
                              ORDER BY dr.created_at DESC");
$pledgesStmt->execute([$donor['id']]);
$pledges = $pledgesStmt->fetchAll();

// Fetch Notifications
$notifsStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notifsStmt->execute([$userId]);
$notifications = $notifsStmt->fetchAll();

// Donation Cooldown Calculation
$daysSinceDonation = null;
$eligibleInDays = 0;
$isEligible = true;

if (!empty($donor['last_donation_date'])) {
    $lastDate = new DateTime($donor['last_donation_date']);
    $today = new DateTime();
    $interval = $today->diff($lastDate);
    $daysSinceDonation = (int) $interval->format('%a');
    if ($daysSinceDonation < 90) {
        $isEligible = false;
        $eligibleInDays = 90 - $daysSinceDonation;
    }
}

$pageTitle = 'Donor Dashboard - LifeLink';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
    <!-- Header Greeting -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                Hello, <?= e($donor['name']) ?>
                <?= blood_badge($donor['blood_group']) ?>
            </h2>
            <p class="text-muted small mb-0">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($donor['city']) ?> &bull; 
                Member since <?= date('M Y', strtotime($donor['created_at'])) ?>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Availability Toggle Form -->
            <form action="dashboard.php" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_availability">
                <?php if ($donor['availability_status'] === 'available'): ?>
                    <button type="submit" class="btn btn-success shadow-sm">
                        <i class="bi bi-toggle-on fs-5 me-1"></i> Status: <strong>Available</strong>
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-toggle-off fs-5 me-1"></i> Status: <strong>Unavailable</strong>
                    </button>
                <?php endif; ?>
            </form>

            <a href="profile.php" class="btn btn-outline-burgundy">
                <i class="bi bi-person-gear me-1"></i> Edit Profile
            </a>

            <?php if ($donor['verified_status'] === 'verified'): ?>
                <a href="certificate.php" class="btn btn-outline-success shadow-sm" target="_blank" title="View & Print Official Certificate of Appreciation">
                    <i class="bi bi-award-fill me-1 text-warning"></i> Certificate
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Verification & Cooldown Alerts -->
    <div class="row g-3 mb-4">
        <!-- Verification Banner -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white">
                <div class="d-flex align-items-center">
                    <div class="p-3 rounded-circle me-3 <?= $donor['verified_status'] === 'verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                        <i class="bi <?= $donor['verified_status'] === 'verified' ? 'bi-patch-check-fill' : 'bi-hourglass-split' ?> fs-3"></i>
                    </div>
                    <div>
                        <div class="small text-muted fw-semibold">Verification Status</div>
                        <h5 class="fw-bold mb-1">
                            <?= status_badge($donor['verified_status']) ?>
                        </h5>
                        <p class="small text-muted mb-0">
                            <?= $donor['verified_status'] === 'verified' 
                                ? 'Your profile is fully verified. You appear in patient search results.' 
                                : 'Your application is under review by LifeLink administrators.' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donation Eligibility Card -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white">
                <div class="d-flex align-items-center">
                    <div class="p-3 rounded-circle me-3 <?= $isEligible ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-muted' ?>">
                        <i class="bi bi-droplet-half fs-3"></i>
                    </div>
                    <div class="w-100">
                        <div class="small text-muted fw-semibold">Medical Donation Eligibility</div>
                        <?php if ($isEligible): ?>
                            <h5 class="fw-bold text-success mb-1">
                                <i class="bi bi-check-circle-fill me-1"></i>Eligible to Donate Blood
                            </h5>
                            <p class="small text-muted mb-0">90-day cooldown satisfied. You can safely donate whole blood today.</p>
                        <?php else: ?>
                            <h5 class="fw-bold text-danger mb-1">
                                <i class="bi bi-clock me-1"></i>Cooldown Period (<?= $eligibleInDays ?> days left)
                            </h5>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?= round(($daysSinceDonation / 90) * 100) ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Matching Requests + Notifications -->
    <div class="row g-4">
        <!-- Matching Emergency Requests Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-burgundy mb-0">
                        <i class="bi bi-bell-fill text-danger me-2"></i>Emergency Requests Matching Your Blood Type in <?= e($donor['city']) ?>
                    </h5>
                    <span class="badge bg-burgundy"><?= count($matchingRequests) ?> Active</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($matchingRequests)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check2-all fs-2 text-success"></i>
                            <p class="mb-0 mt-2">No urgent requests right now for <strong><?= e($donor['blood_group']) ?></strong> in <?= e($donor['city']) ?>.</p>
                            <span class="small">We will notify you immediately when a hospital nearby raises an alert!</span>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($matchingRequests as $req): ?>
                                <div class="col-12">
                                    <div class="card card-hover p-3 border">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <span class="fs-5 fw-bold text-dark"><?= e($req['hospital_name']) ?></span>
                                                <div class="small text-muted">
                                                    <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($req['city']) ?> &bull; 
                                                    Requester: <strong><?= e($req['requester_name']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <?= urgency_badge($req['urgency']) ?>
                                                <span class="badge bg-light text-dark border ms-1"><?= (int)$req['units_needed'] ?> Bag(s)</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top flex-wrap gap-2">
                                            <span class="small text-muted">
                                                <i class="bi bi-clock me-1"></i>Posted <?= date('M d, g:i A', strtotime($req['created_at'])) ?>
                                            </span>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <a href="<?= maps_url($req['hospital_name'], $req['city']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Open Hospital in Google Maps">
                                                    <i class="bi bi-geo-alt-fill"></i> Map
                                                </a>
                                                <a href="<?= whatsapp_sos_link($req['hospital_name'], $req['blood_group'], $req['city'], $req['requester_contact'], (int)$req['units_needed'], $req['urgency']) ?>" target="_blank" class="btn btn-sm btn-success" title="Forward via WhatsApp">
                                                    <i class="bi bi-whatsapp"></i> Share
                                                </a>
                                                <a href="tel:<?= e($req['requester_contact']) ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-telephone me-1"></i> Call
                                                </a>
                                                <a href="respond.php?request_id=<?= (int)$req['id'] ?>" class="btn btn-sm btn-burgundy">
                                                    <i class="bi bi-hand-thumbs-up-fill me-1"></i> I Can Donate
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Pledges / Donation Responses -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-journal-medical text-primary me-2"></i>My Pledges & Assistance History
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pledges)): ?>
                        <div class="text-center py-4 text-muted small">
                            You have not pledged to any emergency requests yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Hospital</th>
                                        <th>Blood Type</th>
                                        <th>Contact Person</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pledges as $pl): ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold"><?= e($pl['hospital_name']) ?></td>
                                            <td><?= blood_badge($pl['blood_group']) ?></td>
                                            <td>
                                                <?= e($pl['requester_name']) ?><br>
                                                <a href="tel:<?= e($pl['requester_contact']) ?>" class="text-muted"><?= e($pl['requester_contact']) ?></a>
                                            </td>
                                            <td><?= status_badge($pl['status']) ?></td>
                                            <td class="text-muted"><?= date('M d, Y', strtotime($pl['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar: Notifications & Quick Actions -->
        <div class="col-lg-4">
            <!-- Notifications Panel -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-bell me-1 text-burgundy"></i>Notifications
                    </h6>
                    <a href="dashboard.php?action=read_notifications" class="text-decoration-none small text-muted">
                        Mark all read
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4 text-muted small">
                            No notifications yet.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($notifications as $n): ?>
                                <li class="list-group-item p-3 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="bi <?= $n['type'] === 'emergency' ? 'bi-exclamation-diamond-fill text-danger' : 'bi-info-circle-fill text-primary' ?> mt-1"></i>
                                        <div>
                                            <div class="<?= !$n['is_read'] ? 'fw-bold' : '' ?>"><?= e($n['message']) ?></div>
                                            <span class="text-muted text-opacity-75" style="font-size: 0.75rem;">
                                                <?= date('M d, g:i A', strtotime($n['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Summary Card -->
            <div class="card border-0 shadow-sm p-3">
                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-lines-fill me-1 text-secondary"></i>Profile Summary</h6>
                <div class="small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Blood Group:</span>
                        <span class="fw-bold text-burgundy"><?= e($donor['blood_group']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Age / Gender:</span>
                        <span><?= (int)$donor['age'] ?> yrs / <?= e($donor['gender']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">City:</span>
                        <span><?= e($donor['city']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Registered Phone:</span>
                        <span><?= e($donor['phone']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Last Donation Date:</span>
                        <span><?= $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) : 'None recorded' ?></span>
                    </div>
                </div>
                <a href="profile.php" class="btn btn-sm btn-outline-secondary w-100 mt-3">
                    <i class="bi bi-pencil-square me-1"></i> Update Medical Info
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

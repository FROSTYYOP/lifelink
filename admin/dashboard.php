<?php
/**
 * LifeLink - Administrator Dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('admin');

// Fetch System Analytics & Counts
try {
    // Total users
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Total donors & verified donors
    $totalDonors = (int) $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn();
    $verifiedDonors = (int) $pdo->query("SELECT COUNT(*) FROM donors WHERE verified_status = 'verified'")->fetchColumn();
    $pendingDonors = (int) $pdo->query("SELECT COUNT(*) FROM donors WHERE verified_status = 'pending'")->fetchColumn();

    // Blood requests
    $totalRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
    $openRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'open'")->fetchColumn();
    $criticalRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'open' AND urgency = 'critical'")->fetchColumn();
    $fulfilledRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'fulfilled'")->fetchColumn();

    // Recent Pending Donors (Limit 5)
    $pendingDonorsStmt = $pdo->query("SELECT d.*, u.name, u.email, u.phone 
                                      FROM donors d 
                                      JOIN users u ON d.user_id = u.id 
                                      WHERE d.verified_status = 'pending' 
                                      ORDER BY d.created_at DESC LIMIT 5");
    $recentPendingDonors = $pendingDonorsStmt->fetchAll();

    // Recent Open Requests (Limit 5)
    $recentRequestsStmt = $pdo->query("SELECT * FROM blood_requests 
                                       WHERE status = 'open' 
                                       ORDER BY (urgency = 'critical') DESC, created_at DESC LIMIT 5");
    $recentOpenRequests = $recentRequestsStmt->fetchAll();

} catch (PDOException $e) {
    $totalUsers = $totalDonors = $verifiedDonors = $pendingDonors = 0;
    $totalRequests = $openRequests = $criticalRequests = $fulfilledRequests = 0;
    $recentPendingDonors = [];
    $recentOpenRequests = [];
}

$pageTitle = 'Admin Dashboard - LifeLink';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
    <!-- Title Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-speedometer2 text-burgundy me-2"></i>Administrator Control Center
            </h2>
            <p class="text-muted small mb-0">System metrics, donor verification queue, and emergency blood coordination</p>
        </div>

        <div class="d-flex gap-2">
            <a href="verify-donors.php" class="btn btn-burgundy shadow-sm position-relative">
                <i class="bi bi-patch-check-fill me-1"></i> Verify Donors
                <?php if ($pendingDonors > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $pendingDonors ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="manage-requests.php" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-droplet-half me-1"></i> Manage Requests
            </a>
        </div>
    </div>

    <!-- Analytics Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-stat p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold">Total Verified Donors</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= $verifiedDonors ?></h3>
                        <span class="small text-muted">Out of <?= $totalDonors ?> registered</span>
                    </div>
                    <div class="p-3 bg-success-subtle text-success rounded-circle">
                        <i class="bi bi-patch-check-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-stat p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold">Pending Verifications</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?= $pendingDonors ?></h3>
                        <a href="verify-donors.php" class="small text-warning text-decoration-none">Review applications &rarr;</a>
                    </div>
                    <div class="p-3 bg-warning-subtle text-warning rounded-circle">
                        <i class="bi bi-clock-history fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-stat p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold">Open Blood Requests</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= $openRequests ?></h3>
                        <span class="small text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= $criticalRequests ?> Critical</span>
                    </div>
                    <div class="p-3 bg-danger-subtle text-danger rounded-circle">
                        <i class="bi bi-broadcast fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-stat p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold">Fulfilled Emergencies</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= $fulfilledRequests ?></h3>
                        <span class="small text-muted"><?= $totalUsers ?> total accounts</span>
                    </div>
                    <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                        <i class="bi bi-heart-pulse-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Queues -->
    <div class="row g-4 mb-4">
        <!-- Pending Donors Queue -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-person-check text-warning me-2"></i>Pending Donor Approvals
                    </h5>
                    <a href="verify-donors.php" class="btn btn-sm btn-outline-burgundy">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentPendingDonors)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-check-circle text-success fs-3 d-block mb-1"></i>
                            All donor applications are up to date!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Donor Name</th>
                                        <th>Blood</th>
                                        <th>City</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPendingDonors as $pDonor): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold"><?= e($pDonor['name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= e($pDonor['email']) ?></div>
                                            </td>
                                            <td><?= blood_badge($pDonor['blood_group']) ?></td>
                                            <td><?= e($pDonor['city']) ?></td>
                                            <td class="text-end pe-3">
                                                <a href="verify-donors.php?donor_id=<?= (int)$pDonor['id'] ?>" class="btn btn-sm btn-burgundy">
                                                    Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Blood Requests Queue -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-activity text-danger me-2"></i>Live Emergency Requests
                    </h5>
                    <a href="manage-requests.php" class="btn btn-sm btn-outline-burgundy">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentOpenRequests)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-droplet text-secondary fs-3 d-block mb-1"></i>
                            No active emergency requests open.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Hospital / City</th>
                                        <th>Blood</th>
                                        <th>Urgency</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOpenRequests as $req): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold"><?= e($req['hospital_name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-geo-alt me-1"></i><?= e($req['city']) ?></div>
                                            </td>
                                            <td><?= blood_badge($req['blood_group']) ?></td>
                                            <td><?= urgency_badge($req['urgency']) ?></td>
                                            <td class="text-end pe-3">
                                                <a href="manage-requests.php?req_id=<?= (int)$req['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    Manage
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Viva System Environment Diagnostic Card -->
    <div class="card border-0 bg-light shadow-sm p-3 small">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold text-dark"><i class="bi bi-cpu-fill text-burgundy me-1"></i>LifeLink Runtime Environment & Architecture (Viva Demonstration Notes)</span>
            <span class="badge bg-burgundy">PHP <?= PHP_VERSION ?></span>
        </div>
        <div class="row g-2 text-muted">
            <div class="col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="fw-bold text-dark d-block">Server Engine:</span>
                    <code>PHP Built-in Server (php -S)</code>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="fw-bold text-dark d-block">Database Driver:</span>
                    <code>PDO MySQL (Prepared Stmts)</code>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="fw-bold text-dark d-block">Auth Mechanism:</span>
                    <code>PHP Sessions + Bcrypt Hashing</code>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="fw-bold text-dark d-block">Security Layer:</span>
                    <code>CSRF Tokens + Strict XSS Escaping</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

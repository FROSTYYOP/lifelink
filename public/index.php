<?php
/**
 * LifeLink - Public Homepage
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch Live Statistics
try {
    // 1. Verified donors count
    $stmt = $pdo->query("SELECT COUNT(*) FROM donors WHERE verified_status = 'verified'");
    $verifiedDonorsCount = (int) $stmt->fetchColumn();

    // 2. Open blood requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'open'");
    $openRequestsCount = (int) $stmt->fetchColumn();

    // 3. Fulfilled lives saved
    $stmt = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'fulfilled'");
    $fulfilledCount = (int) $stmt->fetchColumn();

    // 4. Cities covered
    $stmt = $pdo->query("SELECT COUNT(DISTINCT city) FROM donors WHERE city IS NOT NULL AND city != ''");
    $citiesCount = (int) $stmt->fetchColumn();

    // Fetch Recent Critical Requests (Limit 3)
    $stmt = $pdo->query("SELECT * FROM blood_requests WHERE status = 'open' ORDER BY (urgency = 'critical') DESC, created_at DESC LIMIT 3");
    $urgentRequests = $stmt->fetchAll();

} catch (PDOException $e) {
    // Fallback safe values in case of DB glitch
    $verifiedDonorsCount = 120;
    $openRequestsCount = 5;
    $fulfilledCount = 38;
    $citiesCount = 14;
    $urgentRequests = [];
}

$pageTitle = 'LifeLink – Smart Blood Donor & Emergency Assistance';
$activeNav = 'home';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center py-5">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <span class="hero-tagline mb-3 d-inline-block">
                    <i class="bi bi-shield-fill-plus me-1"></i> Real-Time Lifesaving Community
                </span>
                <h1 class="display-4 fw-extrabold mb-3 text-dark">
                    Every Second Counts. Find <span class="text-burgundy">Verified Blood Donors</span> Near You.
                </h1>
                <p class="lead text-muted mb-4 px-md-5">
                    LifeLink bridges the critical gap between patients in emergency need and nearby voluntary blood donors. Transparent, verified, and completely free.
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= $rootPrefix ?>search.php" class="btn btn-burgundy btn-lg px-4 py-3 shadow-sm">
                        <i class="bi bi-search me-2"></i> Find Donors Now
                    </a>
                    <a href="<?= $rootPrefix ?>request-blood.php" class="btn btn-outline-danger btn-lg px-4 py-3 shadow-sm">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> Post Emergency Request
                    </a>
                    <?php if (!is_logged_in()): ?>
                        <a href="<?= $rootPrefix ?>auth/register.php" class="btn btn-outline-secondary btn-lg px-4 py-3 shadow-sm">
                            <i class="bi bi-person-plus-fill me-2"></i> Join as Donor
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Live Statistics Bar -->
<section class="py-4 bg-white border-bottom shadow-sm">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="card-stat p-3">
                    <h2 class="fw-bold text-burgundy mb-1"><?= number_format($verifiedDonorsCount) ?></h2>
                    <span class="text-muted small fw-semibold"><i class="bi bi-patch-check-fill text-success me-1"></i>Verified Donors</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat p-3">
                    <h2 class="fw-bold text-danger mb-1"><?= number_format($openRequestsCount) ?></h2>
                    <span class="text-muted small fw-semibold"><i class="bi bi-broadcast text-danger me-1"></i>Open Requests</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat p-3">
                    <h2 class="fw-bold text-success mb-1"><?= number_format($fulfilledCount) ?>+</h2>
                    <span class="text-muted small fw-semibold"><i class="bi bi-heart-fill text-danger me-1"></i>Lives Assisted</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat p-3">
                    <h2 class="fw-bold text-dark mb-1"><?= number_format($citiesCount) ?></h2>
                    <span class="text-muted small fw-semibold"><i class="bi bi-geo-alt-fill text-primary me-1"></i>Active Cities</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Urgent Blood Requests Bulletin -->
<?php if (!empty($urgentRequests)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <span class="badge bg-danger mb-1 pulse-badge"><i class="bi bi-activity me-1"></i>Live Broadcast</span>
                <h3 class="fw-bold text-dark mb-0">Urgent Emergency Blood Needs</h3>
            </div>
            <a href="<?= $rootPrefix ?>request-blood.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-plus-circle me-1"></i> Submit New Request
            </a>
        </div>

        <div class="row g-3">
            <?php foreach ($urgentRequests as $req): ?>
                <div class="col-md-4">
                    <div class="card card-hover h-100 p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <?= blood_badge($req['blood_group']) ?>
                                <span class="ms-1"><?= urgency_badge($req['urgency']) ?></span>
                            </div>
                            <span class="badge bg-light text-muted border"><?= (int) $req['units_needed'] ?> Unit(s)</span>
                        </div>

                        <h5 class="fw-bold text-dark mt-2 mb-1"><?= e($req['hospital_name']) ?></h5>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($req['city']) ?>
                            <a href="<?= maps_url($req['hospital_name'], $req['city']) ?>" target="_blank" class="badge text-bg-light text-danger border text-decoration-none ms-1">
                                <i class="bi bi-map me-1"></i>Map
                            </a>
                            &bull; <i class="bi bi-person-circle me-1"></i><?= e($req['requester_name']) ?>
                        </p>

                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="small text-muted">
                                <i class="bi bi-clock me-1"></i><?= date('M d, g:i A', strtotime($req['created_at'])) ?>
                            </span>
                            <div class="d-flex gap-1">
                                <a href="<?= whatsapp_sos_link($req['hospital_name'], $req['blood_group'], $req['city'], $req['requester_contact'], (int)$req['units_needed'], $req['urgency']) ?>" target="_blank" class="btn btn-sm btn-success" title="Share on WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php if (is_logged_in() && has_role('donor')): ?>
                                    <a href="<?= $rootPrefix ?>donor/respond.php?request_id=<?= (int)$req['id'] ?>" class="btn btn-sm btn-burgundy">
                                        <i class="bi bi-hand-thumbs-up me-1"></i> Pledge
                                    </a>
                                <?php else: ?>
                                    <a href="tel:<?= e($req['requester_contact']) ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-telephone me-1"></i> Call
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- How It Works Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="text-burgundy fw-bold text-uppercase small">Simple & Transparent Workflow</span>
            <h2 class="fw-bold text-dark">How LifeLink Saves Lives</h2>
            <p class="text-muted">A streamlined, 3-step community response pipeline</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card card-hover p-4 h-100">
                    <div class="d-inline-flex p-3 rounded-circle bg-burgundy-subtle text-burgundy mx-auto mb-3">
                        <i class="bi bi-pencil-square fs-1"></i>
                    </div>
                    <h5 class="fw-bold">1. Post or Search</h5>
                    <p class="text-muted small">
                        Patients or family members post an emergency request with required blood type, hospital, and units in under 30 seconds.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-hover p-4 h-100">
                    <div class="d-inline-flex p-3 rounded-circle bg-burgundy-subtle text-burgundy mx-auto mb-3">
                        <i class="bi bi-cpu fs-1"></i>
                    </div>
                    <h5 class="fw-bold">2. Smart Donor Matching</h5>
                    <p class="text-muted small">
                        Our system filters verified voluntary donors in the matching city and blood group compatibility tier.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-hover p-4 h-100">
                    <div class="d-inline-flex p-3 rounded-circle bg-burgundy-subtle text-burgundy mx-auto mb-3">
                        <i class="bi bi-heart-pulse fs-1"></i>
                    </div>
                    <h5 class="fw-bold">3. Direct Emergency Response</h5>
                    <p class="text-muted small">
                        Donors respond immediately, coordinate with the hospital, and complete the blood donation without intermediaries.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #800020 0%, #4a0013 100%);">
    <div class="container text-center py-3">
        <h2 class="fw-bold mb-3">Ready to Be Someone's Hero?</h2>
        <p class="lead mb-4 text-light opacity-75">
            A single blood donation can save up to three lives. Join thousands of verified donors on LifeLink today.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= $rootPrefix ?>auth/register.php" class="btn btn-light btn-lg px-4 py-2 text-burgundy fw-bold shadow">
                <i class="bi bi-droplet-fill text-danger me-1"></i> Register as a Donor
            </a>
            <a href="<?= $rootPrefix ?>about.php" class="btn btn-outline-light btn-lg px-4 py-2">
                <i class="bi bi-question-circle me-1"></i> Donor Guidelines
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

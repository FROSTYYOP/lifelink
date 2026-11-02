<?php
/**
 * LifeLink - Search & Filter Blood Donors
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Filter parameters
$selectedBlood = trim($_GET['blood_group'] ?? '');
$selectedCity  = trim($_GET['city'] ?? '');
$onlyAvailable = isset($_GET['only_available']) ? (int)$_GET['only_available'] : 1;
$includeCompatible = isset($_GET['compatible']) ? (int)$_GET['compatible'] : 1;

// Build query dynamically with prepared parameters
$sql = "SELECT d.*, u.name as donor_name, u.phone as donor_phone, u.email as donor_email 
        FROM donors d
        JOIN users u ON d.user_id = u.id
        WHERE d.verified_status = 'verified'";
$params = [];

$compatibleList = [];
if (!empty($selectedBlood)) {
    if ($includeCompatible) {
        $compatibleList = get_compatible_blood_groups($selectedBlood);
        $placeholders = implode(',', array_fill(0, count($compatibleList), '?'));
        $sql .= " AND d.blood_group IN ($placeholders)";
        $params = array_merge($params, $compatibleList);
    } else {
        $sql .= " AND d.blood_group = ?";
        $params[] = $selectedBlood;
    }
}

if (!empty($selectedCity)) {
    $sql .= " AND LOWER(d.city) LIKE ?";
    $params[] = '%' . strtolower($selectedCity) . '%';
}

if ($onlyAvailable === 1) {
    $sql .= " AND d.availability_status = 'available'";
}

$sql .= " ORDER BY d.availability_status ASC, d.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll();

    // Fetch list of unique cities for autocomplete / quick-pick
    $cityStmt = $pdo->query("SELECT DISTINCT city FROM donors WHERE verified_status = 'verified' ORDER BY city ASC");
    $allCities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $donors = [];
    $allCities = [];
}

$pageTitle = 'Find Blood Donors - LifeLink';
$activeNav = 'search';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-4">
    <!-- Search Banner -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-4">
            <h3 class="fw-bold text-burgundy mb-2">
                <i class="bi bi-search-heart me-2"></i>Search Verified Blood Donors
            </h3>
            <p class="text-muted small mb-4">
                Locate voluntary blood donors filtered by blood type and city. Phone numbers are protected for donor privacy.
            </p>

            <form action="search.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="liveDonorBloodFilter" class="form-label fw-semibold">Patient Blood Group</label>
                    <select class="form-select" id="liveDonorBloodFilter" name="blood_group">
                        <option value="">All Blood Groups</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= $selectedBlood === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="liveDonorCityFilter" class="form-label fw-semibold">City or Locality</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" class="form-control" id="liveDonorCityFilter" name="city" value="<?= e($selectedCity) ?>" placeholder="Type city (e.g. Mumbai, Pune)...">
                    </div>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-burgundy w-100 py-2">
                        <i class="bi bi-filter me-1"></i> Search
                    </button>
                    <a href="search.php" class="btn btn-outline-secondary py-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="includeCompatible" name="compatible" value="1" <?= $includeCompatible ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small fw-semibold text-muted" for="includeCompatible">
                            <i class="bi bi-heart-pulse text-danger me-1"></i><strong>Smart Match:</strong> Include all medically compatible donor types for transfusion
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Compatibility Active Note -->
    <?php if (!empty($selectedBlood) && $includeCompatible && count($compatibleList) > 1): ?>
        <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-4 shadow-sm border-info border-opacity-25">
            <i class="bi bi-info-circle-fill fs-5 me-2 text-info"></i>
            <div>
                Showing compatible blood donors for <strong><?= e($selectedBlood) ?></strong> recipient: 
                <?php foreach ($compatibleList as $cbg): ?>
                    <span class="badge bg-burgundy me-1"><?= $cbg ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Filter Tags -->
    <?php if (!empty($allCities)): ?>
        <div class="d-flex align-items-center gap-2 mb-4 flex-wrap small">
            <span class="fw-semibold text-muted"><i class="bi bi-pin-map-fill me-1"></i>Popular Cities:</span>
            <?php foreach ($allCities as $city): ?>
                <a href="search.php?city=<?= urlencode($city) ?><?= $selectedBlood ? '&blood_group=' . urlencode($selectedBlood) : '' ?>" class="badge rounded-pill text-bg-light border text-decoration-none px-3 py-2 text-dark">
                    <?= e($city) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
            Available Donors <span class="badge bg-secondary ms-1"><?= count($donors) ?></span>
        </h5>
        <div class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>Showing verified donors ready to assist
        </div>
    </div>

    <!-- Donors List -->
    <div class="row g-4" id="donorsGrid">
        <?php if (empty($donors)): ?>
            <div class="col-12 text-center py-5">
                <div class="p-4 bg-light rounded-3 d-inline-block">
                    <i class="bi bi-person-x fs-1 text-muted"></i>
                    <h5 class="fw-bold mt-2">No Matching Donors Found</h5>
                    <p class="text-muted small mb-3">Try broadening your city search or selecting all blood groups.</p>
                    <a href="search.php" class="btn btn-sm btn-outline-burgundy">Clear All Filters</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($donors as $donor): ?>
                <div class="col-md-6 col-lg-4 donor-col">
                    <div class="card card-hover h-100 p-3 donor-item-card" data-city="<?= e($donor['city']) ?>" data-blood="<?= e($donor['blood_group']) ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <?= blood_badge($donor['blood_group']) ?>
                                <span class="ms-1"><?= status_badge($donor['availability_status']) ?></span>
                            </div>
                            <span class="badge bg-light text-success border border-success border-opacity-25">
                                <i class="bi bi-patch-check-fill me-1"></i>Verified
                            </span>
                        </div>

                        <h5 class="fw-bold text-dark mt-2 mb-1"><?= e($donor['donor_name']) ?></h5>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($donor['city']) ?>
                            <?php if (!empty($donor['address'])): ?>
                                &bull; <span class="text-truncate d-inline-block" style="max-width: 160px;"><?= e($donor['address']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="bg-light p-2 rounded small mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Age / Gender:</span>
                                <span class="fw-semibold"><?= (int)$donor['age'] ?> yrs &bull; <?= e($donor['gender']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Last Donated:</span>
                                <span class="fw-semibold">
                                    <?= !empty($donor['last_donation_date']) ? date('M d, Y', strtotime($donor['last_donation_date'])) : '<span class="text-success">First Time / Eligible</span>' ?>
                                </span>
                            </div>
                        </div>

                        <!-- Privacy Guard for Phone Number -->
                        <div class="mt-auto pt-2 border-top">
                            <?php if (is_logged_in()): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="small text-muted d-block">Contact Phone:</span>
                                        <a href="tel:<?= e($donor['donor_phone']) ?>" class="fw-bold text-dark text-decoration-none">
                                            <i class="bi bi-telephone-fill text-success me-1"></i><?= e($donor['donor_phone']) ?>
                                        </a>
                                    </div>
                                    <a href="tel:<?= e($donor['donor_phone']) ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-telephone-outbound me-1"></i> Call
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="small text-muted d-block">Phone (Protected):</span>
                                        <span class="phone-masked"><i class="bi bi-lock-fill me-1"></i>+91 98*** ****<?= substr($donor['donor_phone'], -2) ?></span>
                                    </div>
                                    <a href="../auth/login.php" class="btn btn-sm btn-outline-burgundy" title="Login to view full contact details">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Login to View
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Dynamic JS Filter 'No Results' Placeholder -->
    <div id="noDonorsFoundMsg" class="text-center py-5" style="display: none;">
        <div class="p-4 bg-light rounded-3 d-inline-block">
            <i class="bi bi-funnel text-muted fs-1"></i>
            <h5 class="fw-bold mt-2">No Matching Donors on this Page</h5>
            <p class="text-muted small">Try modifying the search filter above.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

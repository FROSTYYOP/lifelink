<?php
/**
 * LifeLink - Admin: Manage Blood Requests
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('admin');

// Handle POST actions for request status & urgency changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $reqId  = (int)($_POST['req_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($reqId > 0) {
            if ($action === 'fulfill') {
                $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'fulfilled' WHERE id = ?");
                $stmt->execute([$reqId]);
                set_flash('success', "Blood request #{$reqId} marked as FULFILLED.");
            } elseif ($action === 'cancel') {
                $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$reqId]);
                set_flash('warning', "Blood request #{$reqId} has been CANCELLED.");
            } elseif ($action === 'reopen') {
                $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'open' WHERE id = ?");
                $stmt->execute([$reqId]);
                set_flash('info', "Blood request #{$reqId} REOPENED.");
            } elseif ($action === 'toggle_urgency') {
                $uStmt = $pdo->prepare("SELECT urgency FROM blood_requests WHERE id = ?");
                $uStmt->execute([$reqId]);
                $curr = $uStmt->fetchColumn();
                $newUrg = ($curr === 'critical') ? 'normal' : 'critical';
                $stmt = $pdo->prepare("UPDATE blood_requests SET urgency = ? WHERE id = ?");
                $stmt->execute([$newUrg, $reqId]);
                set_flash('info', "Urgency of request #{$reqId} changed to " . ucfirst($newUrg) . ".");
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM blood_requests WHERE id = ?");
                $stmt->execute([$reqId]);
                set_flash('danger', "Blood request #{$reqId} permanently deleted.");
            }
            header('Location: manage-requests.php');
            exit;
        }
    }
}

// Filters
$statusFilter  = $_GET['status'] ?? 'all';
$urgencyFilter = $_GET['urgency'] ?? 'all';

$sql = "SELECT br.*, 
        (SELECT COUNT(*) FROM donor_responses WHERE request_id = br.id) as pledge_count
        FROM blood_requests br
        WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND br.status = ?";
    $params[] = $statusFilter;
}

if ($urgencyFilter !== 'all') {
    $sql .= " AND br.urgency = ?";
    $params[] = $urgencyFilter;
}

$sql .= " ORDER BY (br.status = 'open') DESC, (br.urgency = 'critical') DESC, br.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=lifelink-blood-requests-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Hospital', 'City', 'Blood Group', 'Units Needed', 'Urgency', 'Status', 'Requester Name', 'Requester Contact', 'Pledges Count', 'Date Posted']);
    foreach ($requests as $r) {
        fputcsv($output, [
            $r['id'],
            $r['hospital_name'],
            $r['city'],
            $r['blood_group'],
            $r['units_needed'],
            $r['urgency'],
            $r['status'],
            $r['requester_name'],
            $r['requester_contact'],
            $r['pledge_count'],
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Manage Blood Requests - Admin Panel';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-droplet-half text-burgundy me-2"></i>Emergency Blood Requests Manager
            </h2>
            <p class="text-muted small mb-0">Track, update urgency, and resolve emergency hospital requests</p>
        </div>
        <div class="d-flex gap-2">
            <a href="manage-requests.php?status=<?= urlencode($statusFilter) ?>&urgency=<?= urlencode($urgencyFilter) ?>&export=csv" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export (CSV)
            </a>
            <a href="../request-blood.php" class="btn btn-sm btn-burgundy">
                <i class="bi bi-plus-circle me-1"></i> New Request
            </a>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Overview
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <form action="manage-requests.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="small fw-semibold text-muted">Status Filter:</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open Active Requests</option>
                        <option value="fulfilled" <?= $statusFilter === 'fulfilled' ? 'selected' : '' ?>>Fulfilled</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-semibold text-muted">Urgency Filter:</label>
                    <select name="urgency" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= $urgencyFilter === 'all' ? 'selected' : '' ?>>All Urgencies</option>
                        <option value="critical" <?= $urgencyFilter === 'critical' ? 'selected' : '' ?>>Critical Only</option>
                        <option value="normal" <?= $urgencyFilter === 'normal' ? 'selected' : '' ?>>Normal</option>
                    </select>
                </div>
                <div class="col-md-4 text-md-end pt-3">
                    <a href="manage-requests.php" class="btn btn-sm btn-outline-secondary">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($requests)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-droplet-slash fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold">No Blood Requests Found</h5>
                    <p class="small mb-0">No records match the active filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-4">Hospital & City</th>
                                <th>Blood & Units</th>
                                <th>Requester & Phone</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Pledges</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= e($req['hospital_name']) ?></div>
                                        <div class="small text-muted">
                                            <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($req['city']) ?>
                                            <a href="<?= maps_url($req['hospital_name'], $req['city']) ?>" target="_blank" class="badge text-bg-light text-danger border text-decoration-none ms-1"><i class="bi bi-map me-1"></i>Map</a>
                                            <a href="<?= whatsapp_sos_link($req['hospital_name'], $req['blood_group'], $req['city'], $req['requester_contact'], (int)$req['units_needed'], $req['urgency']) ?>" target="_blank" class="badge text-bg-success text-white border text-decoration-none ms-1"><i class="bi bi-whatsapp me-1"></i>SOS</a>
                                        </div>
                                        <span class="text-muted" style="font-size: 0.75rem;">ID #<?= (int)$req['id'] ?> &bull; <?= date('M d, g:i A', strtotime($req['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <div><?= blood_badge($req['blood_group']) ?></div>
                                        <span class="small fw-semibold text-muted"><?= (int)$req['units_needed'] ?> Unit(s)</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($req['requester_name']) ?></div>
                                        <a href="tel:<?= e($req['requester_contact']) ?>" class="small text-success text-decoration-none">
                                            <i class="bi bi-telephone-fill me-1"></i><?= e($req['requester_contact']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= urgency_badge($req['urgency']) ?>
                                    </td>
                                    <td>
                                        <?= status_badge($req['status']) ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            <i class="bi bi-hand-thumbs-up me-1 text-primary"></i><?= (int)$req['pledge_count'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="manage-requests.php" method="POST" class="d-inline-flex gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="req_id" value="<?= (int)$req['id'] ?>">

                                            <?php if ($req['status'] === 'open'): ?>
                                                <button type="submit" name="action" value="fulfill" class="btn btn-sm btn-success" title="Mark as Fulfilled">
                                                    <i class="bi bi-check2"></i> Fulfill
                                                </button>
                                                <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline-warning" title="Cancel Request">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="reopen" class="btn btn-sm btn-outline-primary" title="Reopen Request">
                                                    <i class="bi bi-arrow-clockwise"></i> Reopen
                                                </button>
                                            <?php endif; ?>

                                            <button type="submit" name="action" value="toggle_urgency" class="btn btn-sm btn-outline-danger" title="Toggle Urgency (Normal <-> Critical)">
                                                <i class="bi bi-lightning"></i>
                                            </button>

                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-secondary" title="Delete record" data-confirm="Are you sure you want to permanently delete this request?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

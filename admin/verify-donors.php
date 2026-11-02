<?php
/**
 * LifeLink - Admin: Donor Verification & Application Queue
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('admin');

// Handle verification actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $donorId = (int) ($_POST['donor_id'] ?? 0);
        $action  = $_POST['action'] ?? '';

        if ($donorId > 0 && in_array($action, ['verify', 'reject', 'pending'], true)) {
            $newStatus = match ($action) {
                'verify'  => 'verified',
                'reject'  => 'rejected',
                'pending' => 'pending',
            };

            // Update donor status
            $stmt = $pdo->prepare("UPDATE donors SET verified_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $donorId]);

            // Fetch donor's user_id to send notification
            $uStmt = $pdo->prepare("SELECT user_id, blood_group FROM donors WHERE id = ?");
            $uStmt->execute([$donorId]);
            $donorData = $uStmt->fetch();

            if ($donorData) {
                $uId = (int) $donorData['user_id'];
                if ($newStatus === 'verified') {
                    add_notification($pdo, $uId, "Congratulations! Your LifeLink donor profile ({$donorData['blood_group']}) has been verified. You can now receive emergency blood requests.", "verification");
                    set_flash('success', "Donor application #{$donorId} has been successfully APPROVED and marked as Verified.");
                } elseif ($newStatus === 'rejected') {
                    add_notification($pdo, $uId, "Your LifeLink donor profile could not be verified at this time. Please update your profile details.", "verification");
                    set_flash('warning', "Donor application #{$donorId} has been REJECTED.");
                } else {
                    set_flash('info', "Donor application #{$donorId} reset to Pending.");
                }
            }

            header('Location: verify-donors.php');
            exit;
        }
    }
}

// Filter parameter
$filterStatus = $_GET['status'] ?? 'pending';
$allowedStatuses = ['all', 'pending', 'verified', 'rejected'];
if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = 'all';
}

// Fetch donors list
$sql = "SELECT d.*, u.name as donor_name, u.email as donor_email, u.phone as donor_phone 
        FROM donors d
        JOIN users u ON d.user_id = u.id";

if ($filterStatus !== 'all') {
    $sql .= " WHERE d.verified_status = " . $pdo->quote($filterStatus);
}

$sql .= " ORDER BY (d.verified_status = 'pending') DESC, d.created_at DESC";

$donorsStmt = $pdo->query($sql);
$donors = $donorsStmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=lifelink-donors-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Blood Group', 'City', 'Address', 'Age', 'Gender', 'Status', 'Registered Date']);
    foreach ($donors as $d) {
        fputcsv($output, [
            $d['id'],
            $d['donor_name'],
            $d['donor_email'],
            $d['donor_phone'],
            $d['blood_group'],
            $d['city'],
            $d['address'],
            $d['age'],
            $d['gender'],
            $d['verified_status'],
            $d['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Verify Donors - Admin Panel';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-patch-check-fill text-burgundy me-2"></i>Donor Verification Portal
            </h2>
            <p class="text-muted small mb-0">Screen and authenticate voluntary blood donors before public listing</p>
        </div>
        <div class="d-flex gap-2">
            <a href="verify-donors.php?status=<?= urlencode($filterStatus) ?>&export=csv" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Donors (CSV)
            </a>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Admin Overview
            </a>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $filterStatus === 'pending' ? 'active bg-burgundy' : 'text-dark' ?>" href="verify-donors.php?status=pending">
                <i class="bi bi-clock-history me-1"></i> Pending Verification
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filterStatus === 'verified' ? 'active bg-burgundy' : 'text-dark' ?>" href="verify-donors.php?status=verified">
                <i class="bi bi-check-circle-fill me-1"></i> Verified Donors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filterStatus === 'rejected' ? 'active bg-burgundy' : 'text-dark' ?>" href="verify-donors.php?status=rejected">
                <i class="bi bi-x-circle-fill me-1"></i> Rejected
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $filterStatus === 'all' ? 'active bg-burgundy' : 'text-dark' ?>" href="verify-donors.php?status=all">
                All Records
            </a>
        </li>
    </ul>

    <!-- Donors Table / Queue -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($donors)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold">No Donor Applications Found</h5>
                    <p class="small mb-0">No donors currently match the selected status filter (<strong><?= e($filterStatus) ?></strong>).</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-4">Applicant</th>
                                <th>Blood Type</th>
                                <th>Contact & City</th>
                                <th>Demographics</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Verification Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donors as $d): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= e($d['donor_name']) ?></div>
                                        <div class="small text-muted"><?= e($d['donor_email']) ?></div>
                                        <span class="badge bg-light text-muted border mt-1" style="font-size: 0.7rem;">ID: #<?= (int)$d['id'] ?></span>
                                    </td>
                                    <td><?= blood_badge($d['blood_group']) ?></td>
                                    <td>
                                        <div class="small fw-semibold"><i class="bi bi-telephone-fill text-success me-1"></i><?= e($d['donor_phone']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($d['city']) ?></div>
                                        <?php if (!empty($d['address'])): ?>
                                            <div class="text-muted small text-truncate" style="max-width: 180px;"><?= e($d['address']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <div>Age: <strong><?= (int)$d['age'] ?> yrs</strong></div>
                                        <div class="text-muted">Gender: <?= e($d['gender']) ?></div>
                                        <div class="text-muted">Registered: <?= date('M d, Y', strtotime($d['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <?= status_badge($d['verified_status']) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="verify-donors.php?status=<?= urlencode($filterStatus) ?>" method="POST" class="d-inline-flex gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="donor_id" value="<?= (int)$d['id'] ?>">

                                            <?php if ($d['verified_status'] !== 'verified'): ?>
                                                <button type="submit" name="action" value="verify" class="btn btn-sm btn-success" title="Approve and Verify Donor">
                                                    <i class="bi bi-check-lg me-1"></i> Approve
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($d['verified_status'] !== 'rejected'): ?>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger" title="Reject Application" data-confirm="Are you sure you want to reject this donor application?">
                                                    <i class="bi bi-x-lg me-1"></i> Reject
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($d['verified_status'] !== 'pending'): ?>
                                                <button type="submit" name="action" value="pending" class="btn btn-sm btn-outline-secondary" title="Reset status to pending">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            <?php endif; ?>
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

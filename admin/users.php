<?php
/**
 * LifeLink - Admin: User Management Directory
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('admin');

$currentAdminId = (int) $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $action       = $_POST['action'] ?? '';

        if ($targetUserId > 0) {
            if ($action === 'delete') {
                if ($targetUserId === $currentAdminId) {
                    set_flash('danger', 'You cannot delete your own active administrator account.');
                } else {
                    $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $delStmt->execute([$targetUserId]);
                    set_flash('success', "User #{$targetUserId} deleted successfully.");
                }
            } elseif ($action === 'change_role') {
                $newRole = $_POST['new_role'] ?? 'user';
                if (in_array($newRole, ['admin', 'donor', 'user'], true)) {
                    if ($targetUserId === $currentAdminId && $newRole !== 'admin') {
                        set_flash('danger', 'You cannot demote your own administrator account.');
                    } else {
                        $roleStmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $roleStmt->execute([$newRole, $targetUserId]);
                        set_flash('success', "User #{$targetUserId} role changed to " . ucfirst($newRole) . ".");
                    }
                }
            }
            header('Location: users.php');
            exit;
        }
    }
}

// Role filter
$roleFilter = $_GET['role'] ?? 'all';
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM notifications WHERE user_id = u.id) as notif_count,
        d.blood_group, d.verified_status
        FROM users u
        LEFT JOIN donors d ON u.id = d.user_id";

if ($roleFilter !== 'all' && in_array($roleFilter, ['admin', 'donor', 'user'], true)) {
    $sql .= " WHERE u.role = " . $pdo->quote($roleFilter);
}

$sql .= " ORDER BY u.created_at DESC";
$users = $pdo->query($sql)->fetchAll();

$pageTitle = 'User Management - Admin Panel';
$activeNav = '';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-people text-burgundy me-2"></i>User & Role Management
            </h2>
            <p class="text-muted small mb-0">Review system accounts, access levels, and donor associations</p>
        </div>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <!-- Role Filter Pills -->
    <div class="d-flex gap-2 mb-4">
        <a href="users.php?role=all" class="btn btn-sm <?= $roleFilter === 'all' ? 'btn-burgundy' : 'btn-outline-secondary' ?>">
            All (<?= count($users) ?>)
        </a>
        <a href="users.php?role=donor" class="btn btn-sm <?= $roleFilter === 'donor' ? 'btn-burgundy' : 'btn-outline-secondary' ?>">
            <i class="bi bi-droplet-fill text-danger me-1"></i> Donors
        </a>
        <a href="users.php?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-burgundy' : 'btn-outline-secondary' ?>">
            <i class="bi bi-shield-lock-fill me-1"></i> Admins
        </a>
        <a href="users.php?role=user" class="btn btn-sm <?= $roleFilter === 'user' ? 'btn-burgundy' : 'btn-outline-secondary' ?>">
            <i class="bi bi-person me-1"></i> Requesters
        </a>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">User Details</th>
                            <th>Role</th>
                            <th>Contact Phone</th>
                            <th>Blood / Donor Info</th>
                            <th>Registered</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">
                                        <?= e($u['name']) ?>
                                        <?php if ((int)$u['id'] === $currentAdminId): ?>
                                            <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted"><?= e($u['email']) ?></div>
                                    <span class="text-muted" style="font-size: 0.7rem;">ID: #<?= (int)$u['id'] ?></span>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-shield-lock me-1"></i>Admin</span>
                                    <?php elseif ($u['role'] === 'donor'): ?>
                                        <span class="badge bg-burgundy"><i class="bi bi-droplet me-1"></i>Donor</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small fw-semibold"><?= e($u['phone']) ?></span>
                                </td>
                                <td>
                                    <?php if ($u['blood_group']): ?>
                                        <?= blood_badge($u['blood_group']) ?>
                                        <?= status_badge($u['verified_status']) ?>
                                    <?php else: ?>
                                        <span class="text-muted small">None (General)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="users.php?role=<?= urlencode($roleFilter) ?>" method="POST" class="d-inline-flex align-items-center gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">

                                        <select name="new_role" class="form-select form-select-sm" style="width: 100px;">
                                            <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="donor" <?= $u['role'] === 'donor' ? 'selected' : '' ?>>Donor</option>
                                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>

                                        <button type="submit" name="action" value="change_role" class="btn btn-sm btn-outline-primary" title="Update Role">
                                            Save
                                        </button>

                                        <?php if ((int)$u['id'] !== $currentAdminId): ?>
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" title="Delete User" data-confirm="Are you sure you want to permanently delete user <?= e($u['name']) ?>?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

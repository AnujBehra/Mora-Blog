<?php
$adminTitle = "Manage Users";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Toggle Role (Admin <-> User)
if (isset($_GET['toggle_role'])) {
    $targetId = (int)$_GET['toggle_role'];
    if ($targetId > 0 && $targetId !== (int)$_SESSION['user_id']) {
        $uStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $uStmt->execute([$targetId]);
        $targetUser = $uStmt->fetch();
        if ($targetUser) {
            $newRole = ($targetUser['role'] === 'admin') ? 'user' : 'admin';
            $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
            $_SESSION['flash_success'] = "User role changed to {$newRole}.";
        }
    } else {
        $_SESSION['flash_error'] = "You cannot change your own admin role.";
    }
    header('Location: users.php');
    exit;
}

// Delete User
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId > 0 && $delId !== (int)$_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$delId]);
        $_SESSION['flash_success'] = "User deleted successfully.";
    } else {
        $_SESSION['flash_error'] = "You cannot delete your own account.";
    }
    header('Location: users.php');
    exit;
}

// Fetch all users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="fas fa-users-cog text-primary mr-2"></i> Registered Accounts & Roles</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width: 50px;">Avatar</th>
                    <th>User Details</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($u['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150') ?>" alt="" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                        </td>
                        <td>
                            <strong class="text-dark d-block"><?= htmlspecialchars($u['full_name']) ?></strong>
                            <span class="small text-muted"><?= htmlspecialchars($u['email']) ?></span>
                        </td>
                        <td><code>@<?= htmlspecialchars($u['username']) ?></code></td>
                        <td>
                            <span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : 'primary' ?> px-2 py-1">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <a href="users.php?toggle_role=<?= $u['id'] ?>" class="btn btn-sm btn-outline-info py-0 px-2 mr-1" title="Toggle Admin / User Role">
                                    <i class="fas fa-user-tag"></i>
                                </a>
                                <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Delete user account and their associated posts?');" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php else: ?>
                                <span class="badge badge-light border small text-muted">Current Session</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

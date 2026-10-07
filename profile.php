<?php
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

requireLogin();

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $avatar = sanitize($_POST['avatar'] ?? '');

    if (empty($fullName)) {
        $error = 'Full name is required.';
    } else {
        $upStmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, avatar = ? WHERE id = ?");
        $upStmt->execute([$fullName, $bio, $avatar, $userId]);
        $_SESSION['full_name'] = $fullName;
        $_SESSION['avatar'] = $avatar;
        $success = 'Profile updated successfully!';
    }
}

// Fetch user data
$uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

// Fetch comments made by this user
$comStmt = $db->prepare("
    SELECT c.*, p.title AS post_title, p.id AS post_id 
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC LIMIT 10
");
$comStmt->execute([$userId]);
$userComments = $comStmt->fetchAll();

$pageTitle = "My Profile";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 text-center p-4">
                <img src="<?= htmlspecialchars($user['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150') ?>" alt="Avatar" class="rounded-circle mx-auto mb-3" style="width: 110px; height: 110px; object-fit: cover;">
                <h4 class="font-weight-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
                <p class="text-muted small mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                <div class="mb-3">
                    <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?> px-3 py-1">
                        Role: <?= ucfirst($user['role']) ?>
                    </span>
                </div>
                <p class="text-secondary small"><?= htmlspecialchars($user['bio'] ?? 'No bio provided.') ?></p>
                <p class="text-muted small"><i class="far fa-calendar-alt mr-1"></i> Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>

                <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin/index.php" class="btn btn-warning btn-block font-weight-bold mt-2"><i class="fas fa-tools mr-1"></i> Open Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4 mb-4">
                <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-user-edit mr-2 text-primary"></i> Edit Profile Information</h5>
                <form action="profile.php" method="POST">
                    <div class="form-group">
                        <label class="small font-weight-bold">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small class="form-text text-muted">Email address cannot be changed.</small>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">Avatar Image URL</label>
                        <input type="url" name="avatar" class="form-control" value="<?= htmlspecialchars($user['avatar'] ?? '') ?>" placeholder="https://example.com/avatar.jpg">
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">Bio</label>
                        <textarea name="bio" rows="3" class="form-control"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary font-weight-bold">Save Changes</button>
                </form>
            </div>

            <div class="card shadow-sm border-0 p-4">
                <h5 class="font-weight-bold text-dark mb-3"><i class="far fa-comments mr-2 text-primary"></i> My Recent Comments</h5>
                <?php if (empty($userComments)): ?>
                    <p class="text-muted small">You haven't posted any comments yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($userComments as $cm): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <a href="post-detail.php?id=<?= $cm['post_id'] ?>" class="font-weight-bold text-dark small">
                                        On: <?= htmlspecialchars($cm['post_title']) ?>
                                    </a>
                                    <span class="badge badge-light small"><?= date('M d, Y', strtotime($cm['created_at'])) ?></span>
                                </div>
                                <p class="small text-secondary mb-0"><?= htmlspecialchars($cm['comment']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

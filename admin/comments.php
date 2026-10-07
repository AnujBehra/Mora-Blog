<?php
$adminTitle = "Moderate Comments";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Handle Approve
if (isset($_GET['approve'])) {
    $cId = (int)$_GET['approve'];
    $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$cId]);
    $_SESSION['flash_success'] = "Comment approved.";
    header('Location: comments.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $cId = (int)$_GET['delete'];
    $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$cId]);
    $_SESSION['flash_success'] = "Comment deleted.";
    header('Location: comments.php');
    exit;
}

// Fetch all comments
$comments = $db->query("
    SELECT c.*, p.title AS post_title, p.id AS post_id 
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="fas fa-comments text-primary mr-2"></i> User Comments Moderation</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>On Article</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="width: 130px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No comments found.</td></tr>
                <?php else: ?>
                    <?php foreach ($comments as $cm): ?>
                        <tr>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($cm['author_name']) ?></strong>
                                <span class="small text-muted"><?= htmlspecialchars($cm['author_email']) ?></span>
                            </td>
                            <td>
                                <p class="mb-0 small text-secondary" style="max-width: 320px;"><?= htmlspecialchars($cm['comment']) ?></p>
                            </td>
                            <td>
                                <a href="../post-detail.php?id=<?= $cm['post_id'] ?>" target="_blank" class="small font-weight-bold text-dark text-truncate d-inline-block" style="max-width: 200px;">
                                    <?= htmlspecialchars($cm['post_title']) ?>
                                </a>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($cm['created_at'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $cm['status'] === 'approved' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($cm['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($cm['status'] !== 'approved'): ?>
                                    <a href="comments.php?approve=<?= $cm['id'] ?>" class="btn btn-sm btn-outline-success py-0 px-2 mr-1" title="Approve"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <a href="comments.php?delete=<?= $cm['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Delete this comment permanently?');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

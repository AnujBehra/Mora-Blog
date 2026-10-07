<?php
$adminTitle = "Manage Posts";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Handle Post Deletion
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId > 0) {
        $delStmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $delStmt->execute([$deleteId]);
        $_SESSION['flash_success'] = "Article #{$deleteId} deleted successfully.";
        header('Location: posts.php');
        exit;
    }
}

// Fetch All Posts
$stmt = $db->query("
    SELECT p.*, c.name AS category_name, u.full_name AS author_name 
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    JOIN users u ON p.author_id = u.id 
    ORDER BY p.created_at DESC
");
$allPosts = $stmt->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="fas fa-file-alt text-primary mr-2"></i> All Published & Draft Articles</span>
        <a href="post-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add New Article</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="thead-light">
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Views</th>
                    <th>Featured</th>
                    <th>Breaking</th>
                    <th>Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allPosts)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No posts found in database.</td></tr>
                <?php else: ?>
                    <?php foreach ($allPosts as $p): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="rounded" style="width: 48px; height: 38px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                            </td>
                            <td>
                                <a href="../post-detail.php?id=<?= $p['id'] ?>" target="_blank" class="font-weight-bold text-dark">
                                    <?= htmlspecialchars($p['title']) ?>
                                </a>
                                <div class="small text-muted"><?= date('M d, Y', strtotime($p['created_at'])) ?></div>
                            </td>
                            <td><span class="badge badge-light border"><?= htmlspecialchars($p['category_name']) ?></span></td>
                            <td class="small"><?= htmlspecialchars($p['author_name']) ?></td>
                            <td class="small"><i class="fas fa-eye text-muted mr-1"></i> <?= number_format($p['views']) ?></td>
                            <td>
                                <?= $p['is_featured'] ? '<span class="badge badge-warning">Featured</span>' : '<span class="text-muted small">-</span>' ?>
                            </td>
                            <td>
                                <?= $p['is_breaking'] ? '<span class="badge badge-danger">Breaking</span>' : '<span class="text-muted small">-</span>' ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $p['status'] === 'published' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="post-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 mr-1" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="posts.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Are you sure you want to delete this article?');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

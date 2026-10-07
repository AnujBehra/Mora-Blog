<?php
$adminTitle = "Dashboard Overview";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Fetch System Counts
$totalPosts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalComments = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Fetch Recent Posts
$recentPosts = $db->query("
    SELECT p.*, c.name AS category_name, u.full_name AS author_name 
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    JOIN users u ON p.author_id = u.id 
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

// Fetch Recent Comments
$recentComments = $db->query("
    SELECT c.*, p.title AS post_title 
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();
?>

<!-- Statistics Row -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="info">
                <h4><?= number_format($totalPosts) ?></h4>
                <p>Total Articles</p>
            </div>
            <div class="icon-box icon-blue">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="info">
                <h4><?= number_format($totalCategories) ?></h4>
                <p>Categories</p>
            </div>
            <div class="icon-box icon-green">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="info">
                <h4><?= number_format($totalComments) ?></h4>
                <p>Comments</p>
            </div>
            <div class="icon-box icon-orange">
                <i class="fas fa-comments"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="info">
                <h4><?= number_format($totalUsers) ?></h4>
                <p>Active Users</p>
            </div>
            <div class="icon-box icon-purple">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Posts Section -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-title">
                <span><i class="fas fa-file-alt text-primary mr-2"></i> Recently Published Stories</span>
                <a href="post-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Post</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPosts)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No posts found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPosts as $rp): ?>
                                <tr>
                                    <td>
                                        <a href="../post-detail.php?id=<?= $rp['id'] ?>" target="_blank" class="font-weight-bold text-dark text-truncate d-inline-block" style="max-width: 250px;">
                                            <?= htmlspecialchars($rp['title']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-light border"><?= htmlspecialchars($rp['category_name']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($rp['author_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $rp['status'] === 'published' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($rp['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="post-edit.php?id=<?= $rp['id'] ?>" class="btn btn-outline-primary btn-sm py-0 px-2" title="Edit"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-right mt-3">
                <a href="posts.php" class="small font-weight-bold">View All Posts &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Quick Operations & Recent Comments -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-title">
                <span><i class="fas fa-bolt text-warning mr-2"></i> Quick Actions</span>
            </div>
            <div class="list-group list-group-flush">
                <a href="post-add.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-plus text-primary mr-2"></i> Draft New Article</span>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>
                <a href="categories.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-folder text-success mr-2"></i> Manage Categories</span>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>
                <a href="users.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-shield text-info mr-2"></i> User Permissions</span>
                    <i class="fas fa-chevron-right text-muted small"></i>
                </a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-title">
                <span><i class="far fa-comments text-primary mr-2"></i> Latest Comments</span>
            </div>
            <?php if (empty($recentComments)): ?>
                <p class="text-muted small">No recent comments.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentComments as $rc): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between small">
                                <strong><?= htmlspecialchars($rc['author_name']) ?></strong>
                                <span class="text-muted"><?= date('M d', strtotime($rc['created_at'])) ?></span>
                            </div>
                            <p class="small text-secondary mb-1 text-truncate"><?= htmlspecialchars($rc['comment']) ?></p>
                            <span class="badge badge-light border small text-muted">On: <?= htmlspecialchars(substr($rc['post_title'], 0, 30)) ?>...</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

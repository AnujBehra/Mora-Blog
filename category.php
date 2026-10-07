<?php
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Fetch Category Details
$catStmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
$catStmt->execute([$slug]);
$category = $catStmt->fetch();

if (!$category) {
    $_SESSION['flash_error'] = 'Category not found.';
    header('Location: index.php');
    exit;
}

// Fetch Posts in Category
$postsStmt = $db->prepare("
    SELECT p.*, u.full_name AS author_name 
    FROM posts p 
    JOIN users u ON p.author_id = u.id 
    WHERE p.category_id = ? AND p.status = 'published' 
    ORDER BY p.created_at DESC
");
$postsStmt->execute([$category['id']]);
$posts = $postsStmt->fetchAll();

$pageTitle = $category['name'] . ' News';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="bg-white p-4 rounded border mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent px-0 py-1 mb-2">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($category['name']) ?></li>
            </ol>
        </nav>
        <h2 class="font-weight-bold text-dark mb-1"><i class="fas fa-folder-open text-primary mr-2"></i> <?= htmlspecialchars($category['name']) ?></h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($category['description'] ?? 'Curated stories and updates in this category.') ?></p>
    </div>

    <?php if (empty($posts)): ?>
        <div class="card p-5 text-center my-4">
            <h5>No Articles Found</h5>
            <p class="text-muted">There are currently no published stories under this category.</p>
            <div><a href="index.php" class="btn btn-outline-primary btn-sm">Return Home</a></div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="blog_card">
                        <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog_card_img" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                        <div class="blog_card_body">
                            <span class="tag_btn"><?= htmlspecialchars($category['name']) ?></span>
                            <h5 class="blog_card_title">
                                <a href="post-detail.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                            </h5>
                            <div class="blog_card_meta">
                                <span><i class="far fa-user mr-1"></i> <?= htmlspecialchars($post['author_name']) ?></span> &bull; 
                                <span><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                            </div>
                            <p class="blog_card_summary"><?= htmlspecialchars(substr($post['summary'], 0, 100)) ?>...</p>
                            <div>
                                <a href="post-detail.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary btn-sm">Read Story</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

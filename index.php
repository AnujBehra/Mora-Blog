<?php
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

$pageTitle = "Home";

// Search query filter
$search = trim($_GET['q'] ?? '');

// Fetch Breaking News
$breakingStmt = $db->query("SELECT id, title, slug FROM posts WHERE is_breaking = 1 AND status = 'published' ORDER BY created_at DESC LIMIT 5");
$breakingNews = $breakingStmt->fetchAll();

// Fetch Hero Featured Posts
$heroStmt = $db->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name 
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    JOIN users u ON p.author_id = u.id 
    WHERE p.is_featured = 1 AND p.status = 'published' 
    ORDER BY p.created_at DESC 
    LIMIT 3
");
$heroPosts = $heroStmt->fetchAll();

// Fetch Main Feed Posts (with optional search filter)
if (!empty($search)) {
    $feedStmt = $db->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name 
        FROM posts p 
        JOIN categories c ON p.category_id = c.id 
        JOIN users u ON p.author_id = u.id 
        WHERE p.status = 'published' AND (p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?) 
        ORDER BY p.created_at DESC
    ");
    $searchTerm = "%$search%";
    $feedStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
} else {
    $feedStmt = $db->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name 
        FROM posts p 
        JOIN categories c ON p.category_id = c.id 
        JOIN users u ON p.author_id = u.id 
        WHERE p.status = 'published' 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
}
$mainPosts = $feedStmt->fetchAll();

// Fetch Trending / Popular Posts for Sidebar
$trendingStmt = $db->query("
    SELECT p.*, c.name AS category_name 
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'published' 
    ORDER BY p.views DESC 
    LIMIT 4
");
$trendingPosts = $trendingStmt->fetchAll();

// Fetch Category Counts for Sidebar
$catCountStmt = $db->query("
    SELECT c.name, c.slug, COUNT(p.id) AS post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published' 
    GROUP BY c.id 
    ORDER BY post_count DESC
");
$categoryCounts = $catCountStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Breaking News Ticker -->
    <?php if (!empty($breakingNews)): ?>
        <div class="braking_news_box">
            <span class="braking_label"><i class="fas fa-bolt mr-1"></i> Breaking</span>
            <div class="braking_news_links">
                <?php foreach ($breakingNews as $index => $bPost): ?>
                    <a href="post-detail.php?id=<?= $bPost['id'] ?>" class="mr-4">
                        <i class="fas fa-angle-right text-muted mr-1"></i> <?= htmlspecialchars($bPost['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Notice if applicable -->
    <?php if (!empty($search)): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Search results for: <strong>"<?= htmlspecialchars($search) ?>"</strong> (<?= count($mainPosts) ?> found)</span>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear Search</a>
        </div>
    <?php endif; ?>

    <!-- Hero / Featured Banner Grid (Shown when not searching) -->
    <?php if (empty($search) && !empty($heroPosts)): ?>
        <div class="row mb-4">
            <!-- Main Hero Card -->
            <?php $mainHero = $heroPosts[0]; ?>
            <div class="col-lg-8">
                <div class="hero_featured_card" style="background-image: url('<?= htmlspecialchars($mainHero['image']) ?>');">
                    <div class="hero_featured_overlay">
                        <span class="tag_btn orange"><?= htmlspecialchars($mainHero['category_name']) ?></span>
                        <h2><a href="post-detail.php?id=<?= $mainHero['id'] ?>"><?= htmlspecialchars($mainHero['title']) ?></a></h2>
                        <p class="small text-light mb-2"><?= htmlspecialchars(substr($mainHero['summary'], 0, 140)) ?>...</p>
                        <div class="small text-muted">
                            <span class="text-white mr-3"><i class="far fa-user mr-1"></i> <?= htmlspecialchars($mainHero['author_name']) ?></span>
                            <span class="text-white"><i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($mainHero['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Hero Cards -->
            <div class="col-lg-4">
                <?php for ($i = 1; $i < count($heroPosts); $i++): $subHero = $heroPosts[$i]; ?>
                    <div class="sub_banner_card" style="background-image: url('<?= htmlspecialchars($subHero['image']) ?>');">
                        <div class="overlay">
                            <span class="tag_btn"><?= htmlspecialchars($subHero['category_name']) ?></span>
                            <h5><a href="post-detail.php?id=<?= $subHero['id'] ?>"><?= htmlspecialchars($subHero['title']) ?></a></h5>
                            <div class="small text-light">
                                <i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($subHero['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Area & Sidebar -->
    <div class="row">
        <!-- Left Feed Column -->
        <div class="col-lg-8">
            <div class="section_header">
                <h3><i class="fas fa-newspaper text-primary mr-2"></i> <?= empty($search) ? 'Latest Stories' : 'Search Results' ?></h3>
                <span class="text-muted small">Updated in Real-Time</span>
            </div>

            <?php if (empty($mainPosts)): ?>
                <div class="card p-5 text-center my-4">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No Articles Found</h5>
                    <p class="text-muted">No published posts match your current search query.</p>
                    <div><a href="index.php" class="btn btn-primary btn-sm">View All Articles</a></div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($mainPosts as $post): ?>
                        <div class="col-md-6 mb-4">
                            <div class="blog_card">
                                <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog_card_img" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                                <div class="blog_card_body">
                                    <div>
                                        <a href="category.php?slug=<?= urlencode($post['category_slug']) ?>" class="tag_btn"><?= htmlspecialchars($post['category_name']) ?></a>
                                    </div>
                                    <h5 class="blog_card_title">
                                        <a href="post-detail.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                                    </h5>
                                    <div class="blog_card_meta">
                                        <span><i class="far fa-user mr-1"></i> <?= htmlspecialchars($post['author_name']) ?></span> &bull; 
                                        <span><i class="far fa-calendar-alt mr-1"></i> <?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                                    </div>
                                    <p class="blog_card_summary"><?= htmlspecialchars(substr($post['summary'], 0, 110)) ?>...</p>
                                    <div>
                                        <a href="post-detail.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary btn-sm font-weight-bold">
                                            Read Article <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Trending / Most Read Widget -->
            <div class="sidebar_widget">
                <h4><i class="fas fa-fire text-danger mr-2"></i> Trending News</h4>
                <?php foreach ($trendingPosts as $tPost): ?>
                    <div class="widget_post_item">
                        <img src="<?= htmlspecialchars($tPost['image']) ?>" alt="" class="widget_post_thumb" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                        <div class="widget_post_info">
                            <h6><a href="post-detail.php?id=<?= $tPost['id'] ?>"><?= htmlspecialchars($tPost['title']) ?></a></h6>
                            <span><i class="fas fa-eye mr-1"></i> <?= number_format($tPost['views']) ?> views</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Categories Widget -->
            <div class="sidebar_widget">
                <h4><i class="fas fa-folder-open text-primary mr-2"></i> Categories</h4>
                <ul class="list-group list-group-flush">
                    <?php foreach ($categoryCounts as $cat): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <a href="category.php?slug=<?= urlencode($cat['slug']) ?>" class="text-dark font-weight-500">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                            <span class="badge badge-primary badge-pill"><?= $cat['post_count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Quick Author / Contributor Box -->
            <div class="sidebar_widget text-center">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200" alt="Editor" class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="font-weight-bold mb-1">Editorial Team</h5>
                <p class="small text-muted mb-3">Mora Blog delivers high-fidelity tech, business, and finance updates curated daily.</p>
                <a href="about.php" class="btn btn-sm btn-outline-dark">Meet The Writers</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

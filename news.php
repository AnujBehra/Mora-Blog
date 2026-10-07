<?php
/**
 * News Portal - Dynamic Article Feed & Category Directory
 * Project: Mora Blog
 */

require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

$pageTitle = "All News & Stories";

// 1. Dynamic Total Published Posts Count (Milestone requirement)
$totalPostsCount = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();

// 2. Dynamic Categories Listing with Post Counts (Milestone requirement)
$catCountStmt = $db->query("
    SELECT c.id, c.name, c.slug, c.description, COUNT(p.id) AS post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published' 
    GROUP BY c.id 
    ORDER BY post_count DESC, c.name ASC
");
$allCategories = $catCountStmt->fetchAll();

// 3. Filters: Category, Search query, and Sorting
$selectedCategorySlug = trim($_GET['category'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'latest'); // 'latest', 'popular', 'oldest'

// Pagination setup
$perPage = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Build dynamic WHERE clause
$whereConditions = ["p.status = 'published'"];
$params = [];

$activeCategoryName = '';
if (!empty($selectedCategorySlug)) {
    $whereConditions[] = "c.slug = ?";
    $params[] = $selectedCategorySlug;

    // Find category name for heading
    foreach ($allCategories as $cat) {
        if ($cat['slug'] === $selectedCategorySlug) {
            $activeCategoryName = $cat['name'];
            break;
        }
    }
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?)";
    $likeTerm = "%{$searchQuery}%";
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $params[] = $likeTerm;
}

$whereSql = implode(' AND ', $whereConditions);

// Determine ORDER BY
$orderBySql = "p.created_at DESC";
if ($sortBy === 'popular') {
    $orderBySql = "p.views DESC, p.created_at DESC";
} elseif ($sortBy === 'oldest') {
    $orderBySql = "p.created_at ASC";
}

// Count total matching posts for pagination
$countSql = "
    SELECT COUNT(p.id) 
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    WHERE {$whereSql}
";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$filteredTotalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($filteredTotalCount / $perPage));

// 4. Fetch dynamic news posts with title, image, excerpt/summary, date, category, and author (Milestone requirement)
$postsSql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, 
           u.full_name AS author_name, u.avatar AS author_avatar
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    JOIN users u ON p.author_id = u.id 
    WHERE {$whereSql} 
    ORDER BY {$orderBySql} 
    LIMIT {$perPage} OFFSET {$offset}
";
$postsStmt = $db->prepare($postsSql);
$postsStmt->execute($params);
$newsPosts = $postsStmt->fetchAll();

// Handle AJAX / Form Like Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post_id'])) {
    $likeId = (int)$_POST['like_post_id'];
    $db->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?")->execute([$likeId]);
    $_SESSION['flash_success'] = 'Thank you for liking this article!';
    header("Location: news.php?" . http_build_query($_GET));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- News Header Hero Banner -->
<div class="bg-dark text-white py-5 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="font-weight-800 display-5 mb-2">Explore All News & Insights</h1>
                <p class="text-light lead mb-0" style="font-size: 16px;">
                    Stay informed with our comprehensive catalog of verified reporting, deep technical dives, and market analyses.
                </p>
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                <!-- Dynamic Published Posts Counter Badge -->
                <div class="d-inline-block bg-white text-dark p-3 rounded shadow-sm text-center">
                    <span class="text-muted small text-uppercase font-weight-bold d-block">Total Published Stories</span>
                    <span class="h2 font-weight-bold text-primary mb-0"><?= number_format($totalPostsCount) ?></span>
                    <span class="small text-muted d-block">across <?= count($allCategories) ?> categories</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <!-- Category Filter Bar (Pills with dynamic counts) -->
    <div class="bg-white p-3 rounded border shadow-sm mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center mb-2 mb-md-0">
                <span class="text-muted small font-weight-bold mr-3"><i class="fas fa-filter text-primary mr-1"></i> Categories:</span>
                <a href="news.php<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" 
                   class="btn btn-sm <?= empty($selectedCategorySlug) ? 'btn-primary' : 'btn-outline-secondary' ?> mr-2 mb-1">
                   All (<?= $totalPostsCount ?>)
                </a>
                <?php foreach ($allCategories as $cat): ?>
                    <a href="news.php?category=<?= urlencode($cat['slug']) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                       class="btn btn-sm <?= $selectedCategorySlug === $cat['slug'] ? 'btn-primary' : 'btn-outline-secondary' ?> mr-2 mb-1">
                       <?= htmlspecialchars($cat['name']) ?> 
                       <span class="badge badge-light ml-1"><?= $cat['post_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Sort By Dropdown -->
            <form action="news.php" method="GET" class="form-inline mb-1">
                <?php if (!empty($selectedCategorySlug)): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategorySlug) ?>">
                <?php endif; ?>
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                <?php endif; ?>
                <label class="small text-muted mr-2 font-weight-bold">Sort By:</label>
                <select name="sort" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="latest" <?= $sortBy === 'latest' ? 'selected' : '' ?>>Latest Stories</option>
                    <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Most Viewed</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </form>
        </div>

        <?php if (!empty($selectedCategorySlug) || !empty($searchQuery)): ?>
            <div class="border-top pt-2 mt-2 d-flex align-items-center justify-content-between small">
                <span>
                    Filtering by: 
                    <?php if (!empty($activeCategoryName)): ?>
                        <span class="badge badge-info mr-2">Category: <?= htmlspecialchars($activeCategoryName) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($searchQuery)): ?>
                        <span class="badge badge-warning">Search: "<?= htmlspecialchars($searchQuery) ?>"</span>
                    <?php endif; ?>
                    (Found <?= $filteredTotalCount ?> results)
                </span>
                <a href="news.php" class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i> Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Main News Post Grid -->
        <div class="col-lg-8">
            <?php if (empty($newsPosts)): ?>
                <div class="card p-5 text-center my-4 border-0 shadow-sm">
                    <i class="far fa-newspaper fa-3x text-muted mb-3"></i>
                    <h4>No Stories Found</h4>
                    <p class="text-muted">No published stories match your selected filters. Try choosing another category or resetting the search.</p>
                    <div><a href="news.php" class="btn btn-primary btn-sm px-4">View All Stories</a></div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($newsPosts as $post): ?>
                        <?php
                            // Calculate estimated reading time (approx 200 words per minute)
                            $wordCount = str_word_count(strip_tags($post['content']));
                            $readingMinutes = max(1, (int)round($wordCount / 200));
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="blog_card h-100">
                                <div class="position-relative">
                                    <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog_card_img" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                                    <a href="news.php?category=<?= urlencode($post['category_slug']) ?>" class="tag_btn position-absolute" style="top: 14px; left: 14px;">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                </div>

                                <div class="blog_card_body">
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                                        <span><i class="far fa-calendar-alt mr-1"></i> <?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                                        <span><i class="far fa-clock mr-1"></i> <?= $readingMinutes ?> min read</span>
                                    </div>

                                    <h5 class="blog_card_title mb-2">
                                        <a href="post-detail.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                                    </h5>

                                    <p class="blog_card_summary text-secondary small mb-3">
                                        <?= htmlspecialchars(substr($post['summary'], 0, 110)) ?>...
                                    </p>

                                    <div class="border-top pt-3 mt-auto d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center small">
                                            <img src="<?= htmlspecialchars($post['author_avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150') ?>" class="rounded-circle mr-2" style="width: 24px; height: 24px; object-fit: cover;">
                                            <span class="text-dark font-weight-500"><?= htmlspecialchars($post['author_name']) ?></span>
                                        </div>

                                        <!-- Like Action Form -->
                                        <form action="news.php?<?= http_build_query($_GET) ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="like_post_id" value="<?= $post['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Like article">
                                                <i class="fas fa-heart mr-1"></i> <?= (int)($post['likes'] ?? 0) ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dynamic Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="my-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="news.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $page === $p ? 'active' : '' ?>">
                                    <a class="page-link" href="news.php?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="news.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Right News Sidebar -->
        <div class="col-lg-4">
            <!-- Search Widget -->
            <div class="sidebar_widget">
                <h4><i class="fas fa-search text-primary mr-2"></i> Search Articles</h4>
                <form action="news.php" method="GET">
                    <?php if (!empty($selectedCategorySlug)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategorySlug) ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search keywords..." value="<?= htmlspecialchars($searchQuery) ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Categories with Live Post Counts -->
            <div class="sidebar_widget">
                <h4><i class="fas fa-folder text-primary mr-2"></i> Category Breakdown</h4>
                <ul class="list-group list-group-flush">
                    <?php foreach ($allCategories as $cat): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 <?= $selectedCategorySlug === $cat['slug'] ? 'font-weight-bold text-primary' : '' ?>">
                            <a href="news.php?category=<?= urlencode($cat['slug']) ?>" class="text-dark">
                                <i class="fas fa-angle-right text-muted mr-1"></i> <?= htmlspecialchars($cat['name']) ?>
                            </a>
                            <span class="badge badge-<?= $selectedCategorySlug === $cat['slug'] ? 'primary' : 'light border' ?> badge-pill">
                                <?= $cat['post_count'] ?> articles
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Extraordinary Feature: Newsletter Subscription with DB Storage -->
            <div class="sidebar_widget text-center bg-light">
                <i class="fas fa-paper-plane fa-2x text-primary mb-2"></i>
                <h5 class="font-weight-bold">Daily Briefing</h5>
                <p class="small text-muted mb-3">Get our most read stories delivered to your inbox every morning.</p>
                <form action="contact.php" method="POST">
                    <input type="email" name="subscriber_email" class="form-control form-control-sm mb-2" placeholder="name@domain.com" required>
                    <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

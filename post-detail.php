<?php
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch Post Details
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.full_name AS author_name, u.avatar AS author_avatar, u.bio AS author_bio 
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    JOIN users u ON p.author_id = u.id 
    WHERE p.id = ? AND p.status = 'published'
");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['flash_error'] = 'Requested article could not be found.';
    header('Location: index.php');
    exit;
}

// Increment View Count
$db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$postId]);

// Handle Like Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $db->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?")->execute([$postId]);
    $_SESSION['flash_success'] = 'You liked this article!';
    header("Location: post-detail.php?id={$postId}");
    exit;
}

// Handle New Comment Submission
$commentSuccess = '';
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $authorName = sanitize($_POST['author_name'] ?? '');
    $authorEmail = sanitize($_POST['author_email'] ?? '');
    $commentText = sanitize($_POST['comment'] ?? '');
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

    if (empty($authorName) || empty($authorEmail) || empty($commentText)) {
        $commentError = 'All fields are required to post a comment.';
    } elseif (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
        $commentError = 'Please provide a valid email address.';
    } else {
        $insertComment = $db->prepare("
            INSERT INTO comments (post_id, user_id, author_name, author_email, comment, status) 
            VALUES (?, ?, ?, ?, ?, 'approved')
        ");
        $insertComment->execute([$postId, $userId, $authorName, $authorEmail, $commentText]);
        $commentSuccess = 'Your comment has been published successfully!';
    }
}

// Fetch Approved Comments for this Post
$commentsStmt = $db->prepare("
    SELECT * FROM comments 
    WHERE post_id = ? AND status = 'approved' 
    ORDER BY created_at DESC
");
$commentsStmt->execute([$postId]);
$comments = $commentsStmt->fetchAll();

// Fetch Related Posts
$relatedStmt = $db->prepare("
    SELECT id, title, image, created_at FROM posts 
    WHERE category_id = ? AND id != ? AND status = 'published' 
    ORDER BY created_at DESC LIMIT 3
");
$relatedStmt->execute([$post['category_id'], $postId]);
$relatedPosts = $relatedStmt->fetchAll();

// Fetch Previous & Next Posts
$prevStmt = $db->prepare("SELECT id, title FROM posts WHERE id < ? AND status = 'published' ORDER BY id DESC LIMIT 1");
$prevStmt->execute([$postId]);
$prevPost = $prevStmt->fetch();

$nextStmt = $db->prepare("SELECT id, title FROM posts WHERE id > ? AND status = 'published' ORDER BY id ASC LIMIT 1");
$nextStmt->execute([$postId]);
$nextPost = $nextStmt->fetch();

// Dynamic Reading Time Calculation
$wordCount = str_word_count(strip_tags($post['content']));
$readingMinutes = max(1, (int)round($wordCount / 200));

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <!-- Post Main Content -->
        <div class="col-lg-8">
            <article class="bg-white p-4 p-md-5 rounded border mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent px-0 py-1 mb-3">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="news.php">News</a></li>
                        <li class="breadcrumb-item"><a href="news.php?category=<?= urlencode($post['category_slug']) ?>"><?= htmlspecialchars($post['category_name']) ?></a></li>
                        <li class="breadcrumb-item active text-truncate" style="max-width: 250px;"><?= htmlspecialchars($post['title']) ?></li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <a href="news.php?category=<?= urlencode($post['category_slug']) ?>" class="tag_btn"><?= htmlspecialchars($post['category_name']) ?></a>
                    <span class="badge badge-light border"><i class="far fa-clock mr-1 text-primary"></i> <?= $readingMinutes ?> min read</span>
                </div>

                <h1 class="font-weight-bold mb-3" style="font-size: 32px; line-height: 1.3;"><?= htmlspecialchars($post['title']) ?></h1>

                <div class="d-flex flex-wrap align-items-center justify-content-between text-muted small mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <img src="<?= htmlspecialchars($post['author_avatar']) ?>" alt="Author" class="rounded-circle mr-2" style="width: 38px; height: 38px; object-fit: cover;">
                        <div class="mr-3">
                            <strong><?= htmlspecialchars($post['author_name']) ?></strong>
                        </div>
                        <div class="mr-3"><i class="far fa-calendar-alt mr-1"></i> <?= date('F j, Y', strtotime($post['created_at'])) ?></div>
                        <div><i class="far fa-eye mr-1"></i> <?= number_format($post['views'] + 1) ?> views</div>
                    </div>

                    <!-- Interactive Like Button -->
                    <form action="post-detail.php?id=<?= $postId ?>" method="POST" class="d-inline">
                        <button type="submit" name="like_post" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-heart mr-1"></i> Like Article (<?= (int)($post['likes'] ?? 0) ?>)
                        </button>
                    </form>
                </div>

                <div class="mb-4">
                    <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="img-fluid rounded w-100" style="max-height: 450px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800'">
                </div>

                <div class="lead font-weight-500 text-secondary mb-4" style="font-size: 18px;">
                    <?= htmlspecialchars($post['summary']) ?>
                </div>

                <div class="article-body" style="font-size: 16px; line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>

                <!-- Social Share Bar -->
                <div class="border-top border-bottom py-3 my-4 d-flex flex-wrap align-items-center justify-content-between">
                    <span class="font-weight-bold small text-dark"><i class="fas fa-share-alt text-primary mr-1"></i> Share this story:</span>
                    <div class="d-flex gap-2">
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode('http://localhost:8000/post-detail.php?id=' . $postId) ?>" target="_blank" class="btn btn-sm btn-outline-info mr-1"><i class="fab fa-twitter"></i> Tweet</a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('http://localhost:8000/post-detail.php?id=' . $postId) ?>" target="_blank" class="btn btn-sm btn-outline-primary mr-1"><i class="fab fa-linkedin"></i> Share</a>
                        <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(window.location.href); alert('Article link copied to clipboard!');"><i class="fas fa-link mr-1"></i> Copy Link</button>
                    </div>
                </div>

                <!-- Prev & Next Navigation Links -->
                <div class="row my-4 pt-2">
                    <div class="col-6">
                        <?php if ($prevPost): ?>
                            <a href="post-detail.php?id=<?= $prevPost['id'] ?>" class="text-secondary small d-block">
                                <i class="fas fa-chevron-left mr-1"></i> Previous Article
                                <strong class="d-block text-dark text-truncate"><?= htmlspecialchars($prevPost['title']) ?></strong>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 text-right">
                        <?php if ($nextPost): ?>
                            <a href="post-detail.php?id=<?= $nextPost['id'] ?>" class="text-secondary small d-block">
                                Next Article <i class="fas fa-chevron-right ml-1"></i>
                                <strong class="d-block text-dark text-truncate"><?= htmlspecialchars($nextPost['title']) ?></strong>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Author Bio Box -->
                <div class="bg-light p-4 rounded mt-4 d-flex align-items-center">
                    <img src="<?= htmlspecialchars($post['author_avatar']) ?>" alt="Author" class="rounded-circle mr-4" style="width: 70px; height: 70px; object-fit: cover;">
                    <div>
                        <h6 class="font-weight-bold mb-1">About the Author: <?= htmlspecialchars($post['author_name']) ?></h6>
                        <p class="small text-muted mb-0"><?= htmlspecialchars($post['author_bio'] ?? 'Staff writer and contributing researcher at Mora Blog.') ?></p>
                    </div>
                </div>
            </article>

            <!-- Comments Section -->
            <div class="bg-white p-4 p-md-5 rounded border mb-4">
                <h4 class="font-weight-bold mb-4"><i class="far fa-comments text-primary mr-2"></i> Reader Discussions (<?= count($comments) ?>)</h4>

                <?php if (!empty($commentSuccess)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($commentSuccess) ?></div>
                <?php endif; ?>

                <?php if (!empty($commentError)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($commentError) ?></div>
                <?php endif; ?>

                <!-- Comments List -->
                <?php if (empty($comments)): ?>
                    <p class="text-muted small">No comments yet. Be the first to share your thoughts!</p>
                <?php else: ?>
                    <div class="comment-list mb-5">
                        <?php foreach ($comments as $c): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark"><i class="far fa-user-circle mr-1 text-primary"></i> <?= htmlspecialchars($c['author_name']) ?></strong>
                                    <span class="small text-muted"><?= date('M j, Y - g:i a', strtotime($c['created_at'])) ?></span>
                                </div>
                                <p class="mb-0 text-secondary small"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Comment Submission Form -->
                <h5 class="font-weight-bold mb-3">Leave a Reply</h5>
                <form action="post-detail.php?id=<?= $postId ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Your Name *</label>
                            <input type="text" name="author_name" class="form-control" required value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) : '' ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Email Address *</label>
                            <input type="email" name="author_email" class="form-control" required value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['email'] ?? '') : '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Your Comment *</label>
                        <textarea name="comment" rows="4" class="form-control" placeholder="Write your thoughts..." required></textarea>
                    </div>
                    <button type="submit" name="submit_comment" class="btn btn-primary font-weight-bold px-4">Post Comment</button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Related Posts -->
            <?php if (!empty($relatedPosts)): ?>
                <div class="sidebar_widget">
                    <h4><i class="fas fa-bookmark text-primary mr-2"></i> Related Stories</h4>
                    <?php foreach ($relatedPosts as $rPost): ?>
                        <div class="widget_post_item">
                            <img src="<?= htmlspecialchars($rPost['image']) ?>" alt="" class="widget_post_thumb">
                            <div class="widget_post_info">
                                <h6><a href="post-detail.php?id=<?= $rPost['id'] ?>"><?= htmlspecialchars($rPost['title']) ?></a></h6>
                                <span><i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($rPost['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="sidebar_widget text-center">
                <h5>Want to write with us?</h5>
                <p class="small text-muted mb-3">Join Mora Blog community today. Readers and contributors can interact directly with stories.</p>
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Go to Your Profile</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-sm">Sign Up Free</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

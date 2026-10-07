<?php
$adminTitle = "Create New Article";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Fetch Categories for Dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$error = '';
$title = '';
$categoryId = '';
$summary = '';
$content = '';
$image = '';
$tag = 'General';
$isFeatured = 0;
$isBreaking = 0;
$status = 'published';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $summary = sanitize($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image = sanitize($_POST['image'] ?? '');
    $tag = sanitize($_POST['tag'] ?? 'General');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

    if (empty($title) || empty($categoryId) || empty($summary) || empty($content)) {
        $error = 'Please fill in all required fields (Title, Category, Summary, Content).';
    } else {
        // Generate Slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $slug = $baseSlug;
        $counter = 1;
        while (true) {
            $chk = $db->prepare("SELECT id FROM posts WHERE slug = ?");
            $chk->execute([$slug]);
            if (!$chk->fetch()) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        if (empty($image)) {
            $image = 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800';
        }

        $authorId = $_SESSION['user_id'];

        $stmt = $db->prepare("
            INSERT INTO posts (category_id, author_id, title, slug, summary, content, image, tag, is_featured, is_breaking, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$categoryId, $authorId, $title, $slug, $summary, $content, $image, $tag, $isFeatured, $isBreaking, $status]);

        $_SESSION['flash_success'] = 'Article created and published successfully!';
        header('Location: posts.php');
        exit;
    }
}
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="fas fa-edit text-primary mr-2"></i> Draft New Story</span>
        <a href="posts.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Posts</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="post-add.php" method="POST">
        <div class="row">
            <div class="col-lg-8">
                <div class="form-group">
                    <label class="font-weight-bold">Article Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter headline..." value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Summary / Excerpt *</label>
                    <textarea name="summary" rows="2" class="form-control" placeholder="Brief 1-2 sentence preview..." required><?= htmlspecialchars($summary) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Full Content *</label>
                    <textarea name="content" rows="12" class="form-control" placeholder="Write full article here..." required><?= htmlspecialchars($content) ?></textarea>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-group">
                    <label class="font-weight-bold">Category *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Featured Image URL</label>
                    <input type="url" name="image" class="form-control" placeholder="https://images.unsplash.com/..." value="<?= htmlspecialchars($image) ?>">
                    <small class="form-text text-muted">Use high-res Unsplash or direct image link.</small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Topic Tag</label>
                    <input type="text" name="tag" class="form-control" placeholder="e.g. Technology, Investing, Travel" value="<?= htmlspecialchars($tag) ?>">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Publishing Status</label>
                    <select name="status" class="form-control">
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>

                <div class="form-group border p-3 rounded bg-light">
                    <label class="font-weight-bold mb-2">Display Flags</label>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="isFeatured" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="isFeatured">Featured on Homepage Banner</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="isBreaking" name="is_breaking" value="1" <?= $isBreaking ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="isBreaking">Breaking News Ticker</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 mt-4">
                    <i class="fas fa-paper-plane mr-1"></i> Save & Publish Article
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

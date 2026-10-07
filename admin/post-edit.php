<?php
$adminTitle = "Edit Article";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: posts.php');
    exit;
}

// Fetch existing post
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['flash_error'] = 'Post not found.';
    header('Location: posts.php');
    exit;
}

// Fetch Categories for Dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$error = '';
$title = $post['title'];
$categoryId = $post['category_id'];
$summary = $post['summary'];
$content = $post['content'];
$image = $post['image'];
$tag = $post['tag'];
$isFeatured = $post['is_featured'];
$isBreaking = $post['is_breaking'];
$status = $post['status'];

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
        $error = 'Please fill in all required fields.';
    } else {
        $upStmt = $db->prepare("
            UPDATE posts SET 
                category_id = ?, 
                title = ?, 
                summary = ?, 
                content = ?, 
                image = ?, 
                tag = ?, 
                is_featured = ?, 
                is_breaking = ?, 
                status = ?
            WHERE id = ?
        ");
        $upStmt->execute([$categoryId, $title, $summary, $content, $image, $tag, $isFeatured, $isBreaking, $status, $id]);

        $_SESSION['flash_success'] = 'Article updated successfully!';
        header('Location: posts.php');
        exit;
    }
}
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="fas fa-edit text-primary mr-2"></i> Edit Article #<?= $id ?></span>
        <div>
            <a href="../post-detail.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-info btn-sm mr-2"><i class="fas fa-eye mr-1"></i> Preview</a>
            <a href="posts.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="post-edit.php?id=<?= $id ?>" method="POST">
        <div class="row">
            <div class="col-lg-8">
                <div class="form-group">
                    <label class="font-weight-bold">Article Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Summary / Excerpt *</label>
                    <textarea name="summary" rows="2" class="form-control" required><?= htmlspecialchars($summary) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Full Content *</label>
                    <textarea name="content" rows="12" class="form-control" required><?= htmlspecialchars($content) ?></textarea>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-group">
                    <label class="font-weight-bold">Category *</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Featured Image URL</label>
                    <input type="url" name="image" class="form-control" value="<?= htmlspecialchars($image) ?>">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Topic Tag</label>
                    <input type="text" name="tag" class="form-control" value="<?= htmlspecialchars($tag) ?>">
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
                    <i class="fas fa-save mr-1"></i> Update Article
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

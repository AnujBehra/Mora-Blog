<?php
$adminTitle = "Categories Management";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Handle New Category Creation
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        $chk = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $chk->execute([$slug]);
        if ($chk->fetch()) {
            $error = 'Category with this name or slug already exists.';
        } else {
            $inStmt = $db->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $inStmt->execute([$name, $slug, $description]);
            $_SESSION['flash_success'] = "Category '{$name}' created successfully.";
            header('Location: categories.php');
            exit;
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId > 0) {
        // Prevent deleting if it's the last remaining category
        $count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($count <= 1) {
            $_SESSION['flash_error'] = "Cannot delete the only remaining category.";
        } else {
            $delStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $delStmt->execute([$delId]);
            $_SESSION['flash_success'] = "Category deleted successfully.";
        }
        header('Location: categories.php');
        exit;
    }
}

// Fetch all categories with post counts
$categories = $db->query("
    SELECT c.*, COUNT(p.id) AS post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
")->fetchAll();
?>

<div class="row">
    <!-- Add Category Form -->
    <div class="col-lg-4 mb-4">
        <div class="admin-card">
            <div class="admin-card-title">
                <span><i class="fas fa-folder-plus text-primary mr-2"></i> Add New Category</span>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="categories.php" method="POST">
                <div class="form-group">
                    <label class="font-weight-bold small">Category Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Science & Space" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Description</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Brief explanation of topics covered..."></textarea>
                </div>

                <button type="submit" name="add_category" class="btn btn-primary btn-block font-weight-bold">
                    <i class="fas fa-plus mr-1"></i> Create Category
                </button>
            </form>
        </div>
    </div>

    <!-- Category List Table -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-title">
                <span><i class="fas fa-folder-open text-primary mr-2"></i> Active Categories</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Posts</th>
                            <th style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="font-weight-bold text-dark"><?= htmlspecialchars($cat['name']) ?></td>
                                <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                <td class="small text-muted"><?= htmlspecialchars($cat['description'] ?? '-') ?></td>
                                <td><span class="badge badge-primary"><?= $cat['post_count'] ?></span></td>
                                <td>
                                    <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Deleting category will also delete its posts. Confirm?');" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

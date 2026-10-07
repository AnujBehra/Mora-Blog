<?php
require_once __DIR__ . '/../../config/db.php';

// Enforce Admin Role Access
requireAdmin();

$adminPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($adminTitle) ? htmlspecialchars($adminTitle) . ' - Admin' : 'Admin Dashboard - Mora Blog' ?></title>
    
    <!-- Google Fonts & Bootstrap 4 CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Admin CSS -->
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <i class="fas fa-feather-alt text-primary"></i> MORA<span>ADMIN</span>
        </div>

        <ul class="admin-nav">
            <li class="admin-nav-item <?= $adminPage === 'index.php' ? 'active' : '' ?>">
                <a href="index.php"><i class="fas fa-chart-line fa-fw"></i> Dashboard</a>
            </li>
            <li class="admin-nav-item <?= $adminPage === 'posts.php' ? 'active' : '' ?>">
                <a href="posts.php"><i class="fas fa-file-alt fa-fw"></i> Manage Posts</a>
            </li>
            <li class="admin-nav-item <?= $adminPage === 'post-add.php' ? 'active' : '' ?>">
                <a href="post-add.php"><i class="fas fa-plus-circle fa-fw"></i> Add New Post</a>
            </li>
            <li class="admin-nav-item <?= $adminPage === 'categories.php' ? 'active' : '' ?>">
                <a href="categories.php"><i class="fas fa-folder-open fa-fw"></i> Categories</a>
            </li>
            <li class="admin-nav-item <?= $adminPage === 'comments.php' ? 'active' : '' ?>">
                <a href="comments.php"><i class="fas fa-comments fa-fw"></i> Comments</a>
            </li>
            <li class="admin-nav-item <?= $adminPage === 'users.php' ? 'active' : '' ?>">
                <a href="users.php"><i class="fas fa-users-cog fa-fw"></i> Manage Users</a>
            </li>
            <li class="admin-nav-item <?= $adminPage === 'subscribers.php' ? 'active' : '' ?>">
                <a href="subscribers.php"><i class="fas fa-envelope-open-text fa-fw"></i> Subscribers</a>
            </li>
            <li class="admin-nav-item">
                <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i> View Live Site</a>
            </li>
            <li class="admin-nav-item">
                <a href="../logout.php"><i class="fas fa-sign-out-alt fa-fw text-danger"></i> Logout</a>
            </li>
        </ul>

        <div class="admin-user-profile">
            <img src="<?= htmlspecialchars($_SESSION['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150') ?>" alt="Admin" class="admin-user-avatar">
            <div class="admin-user-info">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator') ?></div>
                <div class="role">Admin Account</div>
            </div>
        </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 font-weight-bold text-dark"><?= isset($adminTitle) ? htmlspecialchars($adminTitle) : 'Admin Panel' ?></h5>
            </div>
            <div>
                <a href="../index.php" class="btn btn-outline-secondary btn-sm mr-2"><i class="fas fa-globe mr-1"></i> Public Site</a>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-power-off mr-1"></i> Exit</a>
            </div>
        </header>

        <div class="admin-content">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

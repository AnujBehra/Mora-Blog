<?php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/db.php';
}
$db = getDBConnection();

// Fetch categories for navbar
$navCategories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $navCategories = $stmt->fetchAll();
} catch (Exception $e) {
    // Graceful fallback
}

// Current page identifier for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' - Modern Dynamic Publishing' ?></title>
    
    <!-- Google Fonts & Bootstrap 4 CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Main Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Header Top Area -->
    <div class="header_top_area"> 
        <div class="container"> 
            <div class="header_top_inner">
                <ul class="left_info"> 
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/index.php" class="admin_badge"><i class="fas fa-cog mr-1"></i> Admin Dashboard</a></li>
                    <?php endif; ?>
                </ul> 
                <ul class="header_social">
                    <?php if (isLoggedIn()): ?>
                        <li class="dropdown">
                            <a class="dropdown-toggle" href="#" id="userMenu" data-toggle="dropdown">
                                <i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Account') ?>
                                <span class="badge badge-light ml-1"><?= ucfirst($_SESSION['role'] ?? 'user') ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenu">
                                <a class="dropdown-item" href="profile.php"><i class="fas fa-id-badge mr-2"></i> My Profile</a>
                                <?php if (isAdmin()): ?>
                                    <a class="dropdown-item text-primary" href="admin/index.php"><i class="fas fa-tachometer-alt mr-2"></i> Admin Panel</a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt mr-1"></i> Login</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus mr-1"></i> Register</a></li>
                    <?php endif; ?>
                </ul> 
            </div>
        </div> 
    </div>

    <!-- Main Header Area -->
    <header class="main_header_area" id="header"> 
       <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light px-0">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-feather-alt text-primary"></i> MORA<span>BLOG</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#moraNavbar" aria-controls="moraNavbar" aria-expanded="false" aria-label="Toggle navigation"> 
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="moraNavbar">
                    <ul class="navbar-nav mx-auto"> 
                        <li class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>  
                        <li class="nav-item <?= $currentPage === 'news.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="news.php">All News</a>
                        </li>  
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="catDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Categories
                            </a>
                            <div class="dropdown-menu" aria-labelledby="catDropdown">
                                <?php foreach ($navCategories as $cat): ?>
                                    <a class="dropdown-item" href="category.php?slug=<?= urlencode($cat['slug']) ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </li>
                        <li class="nav-item <?= $currentPage === 'about.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="about.php">About Us</a>
                        </li>  
                        <li class="nav-item <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="contact.php">Contact Us</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link text-primary font-weight-bold" href="admin/index.php">
                                    <i class="fas fa-lock mr-1"></i> Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Search Form -->
                    <form class="form-inline my-2 my-lg-0" action="index.php" method="GET">
                        <div class="input-group">
                            <input class="form-control form-control-sm" type="search" name="q" placeholder="Search news..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </nav>  
       </div>
    </header>

    <!-- Flash Messages Container -->
    <div class="container mt-3">
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
    </div>

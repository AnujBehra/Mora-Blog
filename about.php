<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = "About Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="bg-white p-4 p-md-5 rounded border mb-4">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <span class="tag_btn orange mb-2">Our Mission</span>
                <h1 class="font-weight-bold mb-3">Transforming Static Content into Dynamic Insights</h1>
                <p class="text-secondary lead" style="font-size: 17px;">
                    Mora Blog is an independent digital media platform delivering authoritative reporting, technological analysis, and global economic perspectives.
                </p>
                <p class="text-muted">
                    Our platform is built upon a high-performance publishing architecture, providing readers with seamless navigation, verified reporting, and an open forum for informed community dialogue.
                </p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800" alt="Team" class="img-fluid rounded shadow-sm">
            </div>
        </div>

        <hr class="my-5">

        <h3 class="font-weight-bold text-center mb-4">Core Architectural Features</h3>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="p-4 border rounded bg-light h-100">
                    <i class="fas fa-database fa-2x text-primary mb-3"></i>
                    <h5 class="font-weight-bold">Relational Database</h5>
                    <p class="small text-muted mb-0">Powered by MySQL PDO with normalized schemas for users, categories, posts, and real-time community engagement.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4 border rounded bg-light h-100">
                    <i class="fas fa-user-shield fa-2x text-success mb-3"></i>
                    <h5 class="font-weight-bold">Dual-Role Authentication</h5>
                    <p class="small text-muted mb-0">Secure bcrypt password hashing and session guards separating standard users from site administrators.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4 border rounded bg-light h-100">
                    <i class="fas fa-layer-group fa-2x text-warning mb-3"></i>
                    <h5 class="font-weight-bold">Streamlined UI</h5>
                    <p class="small text-muted mb-0">Consolidated redundant demo templates into one cohesive, accessible, mobile-first responsive layout.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

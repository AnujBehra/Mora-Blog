<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = "About Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="bg-white p-4 p-md-5 rounded border mb-4">
        <div class="row align-items-center">
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

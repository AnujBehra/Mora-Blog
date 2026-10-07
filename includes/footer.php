    <!-- Footer Area -->  
    <footer class="footer_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h4 class="text-white"><i class="fas fa-feather-alt text-primary mr-2"></i> MORABLOG</h4>
                    <p class="text-muted small">A premier digital publication delivering curated journalism, deep technology analysis, and global market insights.</p>
                    <p class="text-muted small"><i class="fas fa-map-marker-alt mr-2"></i> Technology Park, Suite 210, Tech City</p>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h4>Categories</h4>
                    <ul class="footer_links small">
                        <?php if (!empty($navCategories)): ?>
                            <?php foreach (array_slice($navCategories, 0, 5) as $fc): ?>
                                <li><a href="category.php?slug=<?= urlencode($fc['slug']) ?>"><?= htmlspecialchars($fc['name']) ?></a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h4>Quick Links</h4>
                    <ul class="footer_links small">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="profile.php">My Account</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">User / Admin Login</a></li>
                            <li><a href="register.php">Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h4>Newsletter</h4>
                    <p class="small text-muted">Subscribe to receive curated industry insights straight to your inbox.</p> 
                    <form action="contact.php" method="POST">
                        <div class="input-group mb-2">
                            <input type="email" name="subscriber_email" class="form-control form-control-sm" placeholder="Enter your email" required>
                            <div class="input-group-append">
                                <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </form>
                    <span class="small text-muted"><i class="fas fa-lock mr-1"></i> We respect your privacy. No spam.</span>
                </div>
            </div>
            
            <div class="copy_right">
                <p class="mb-0">&copy; <?= date('Y') ?> Mora Blog. All rights reserved.</p> 
            </div>
        </div>
    </footer>

    <!-- jQuery, Popper, and Bootstrap 4 JS CDN -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>

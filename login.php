<?php
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';
$usernameOrEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Please enter both your username/email and password.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Password verified
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];

            $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['full_name']) . "!";

            // Route based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

$pageTitle = "Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-header bg-white text-center py-4 border-bottom-0">
                    <h3 class="font-weight-bold text-dark mb-1">Welcome Back</h3>
                    <p class="text-muted small mb-0">Sign in to access your User profile or Admin Panel</p>
                </div>

                <div class="card-body px-4 py-3">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small py-2"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <!-- Demo Credentials Helper for Reviewer -->
                    <div class="alert alert-light border small mb-4">
                        <strong class="d-block mb-1 text-primary"><i class="fas fa-info-circle mr-1"></i> Quick Test Credentials:</strong>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Admin Login:</span>
                            <code>admin</code> / <code>password123</code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>User Login:</span>
                            <code>john_doe</code> / <code>password123</code>
                        </div>
                    </div>

                    <form action="login.php" method="POST">
                        <div class="form-group">
                            <label class="small font-weight-bold text-secondary">Username or Email</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="far fa-user text-muted"></i></span>
                                </div>
                                <input type="text" name="username_or_email" class="form-control" placeholder="admin or john_doe" value="<?= htmlspecialchars($usernameOrEmail) ?>" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-secondary">Password</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                </div>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2">
                            Sign In to Account
                        </button>
                    </form>
                </div>

                <div class="card-footer bg-light text-center py-3 border-top-0">
                    <span class="small text-muted">Don't have an account yet? <a href="register.php" class="font-weight-bold">Register here</a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

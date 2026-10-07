<?php
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$fullName = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Check uniqueness
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()) {
            $error = 'That username or email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $db->prepare("
                INSERT INTO users (full_name, username, email, password, role) 
                VALUES (?, ?, ?, ?, 'user')
            ");
            $insertStmt->execute([$fullName, $username, $email, $hashedPassword]);

            $_SESSION['flash_success'] = 'Account registered successfully! You can now log in.';
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = "Register";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-header bg-white text-center py-4 border-bottom-0">
                    <h3 class="font-weight-bold text-dark mb-1">Create an Account</h3>
                    <p class="text-muted small mb-0">Join our community to comment and follow favorite authors</p>
                </div>

                <div class="card-body px-4 py-3">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small py-2"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="form-group">
                            <label class="small font-weight-bold text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Alex Morgan" value="<?= htmlspecialchars($fullName) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold text-secondary">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="choose a username" value="<?= htmlspecialchars($username) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold text-secondary">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-secondary">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2">
                            Create Free Account
                        </button>
                    </form>
                </div>

                <div class="card-footer bg-light text-center py-3 border-top-0">
                    <span class="small text-muted">Already registered? <a href="login.php" class="font-weight-bold">Log in here</a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

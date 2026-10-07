<?php
require_once __DIR__ . '/config/db.php';

$sent = false;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subscriber_email'])) {
        $subEmail = sanitize($_POST['subscriber_email']);
        if (filter_var($subEmail, FILTER_VALIDATE_EMAIL)) {
            $db = getDBConnection();
            try {
                $inSub = $db->prepare("INSERT INTO subscribers (email) VALUES (?)");
                $inSub->execute([$subEmail]);
            } catch (Exception $e) {}
            $msg = "Thank you! <strong>$subEmail</strong> has been subscribed to our weekly newsletter.";
            $sent = true;
        }
    } elseif (isset($_POST['send_message'])) {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $content = sanitize($_POST['message'] ?? '');

        if (!empty($name) && !empty($email) && !empty($content)) {
            $msg = "Thank you, <strong>$name</strong>! Your message has been received. Our team will contact you shortly.";
            $sent = true;
        }
    }
}

$pageTitle = "Contact Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-7">
            <div class="bg-white p-4 p-md-5 rounded border mb-4">
                <h2 class="font-weight-bold mb-2">Get in Touch</h2>
                <p class="text-muted mb-4">Have questions, feedback, or a story pitch? Send us a message.</p>

                <?php if ($sent): ?>
                    <div class="alert alert-success"><?= $msg ?></div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Your Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['full_name'] ?? '') : '' ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold">Email Address *</label>
                            <input type="email" name="email" class="form-control" required value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['email'] ?? '') : '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Inquiry about...">
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Message *</label>
                        <textarea name="message" rows="5" class="form-control" placeholder="Type your message here..." required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn btn-primary font-weight-bold px-4 py-2">
                        <i class="fas fa-paper-plane mr-1"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="bg-white p-4 p-md-5 rounded border mb-4">
                <h4 class="font-weight-bold mb-3">Contact Information</h4>
                <p class="text-muted small mb-4">Our editorial office is open Monday through Saturday during regular business hours.</p>

                <div class="d-flex mb-3">
                    <i class="fas fa-map-marker-alt text-primary fa-lg mr-3 mt-1"></i>
                    <div>
                        <strong class="d-block text-dark">Address</strong>
                        <span class="small text-muted">12/4 Tech Park, Street 2101, New York, USA</span>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <i class="fas fa-envelope text-primary fa-lg mr-3 mt-1"></i>
                    <div>
                        <strong class="d-block text-dark">Email</strong>
                        <span class="small text-muted">editorial@morablog.com</span>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <i class="fas fa-phone-alt text-primary fa-lg mr-3 mt-1"></i>
                    <div>
                        <strong class="d-block text-dark">Phone</strong>
                        <span class="small text-muted">+1 (555) 234-5678</span>
                    </div>
                </div>

                <hr>

                <h6 class="font-weight-bold mb-2">Connect With Us</h6>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-secondary btn-sm mr-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="btn btn-outline-secondary btn-sm mr-2"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="btn btn-outline-secondary btn-sm mr-2"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="btn btn-outline-secondary btn-sm"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

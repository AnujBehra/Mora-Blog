<?php
$adminTitle = "Newsletter Subscribers";
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Handle Unsubscribe / Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId > 0) {
        $db->prepare("DELETE FROM subscribers WHERE id = ?")->execute([$delId]);
        $_SESSION['flash_success'] = "Subscriber removed successfully.";
        header('Location: subscribers.php');
        exit;
    }
}

// Fetch all subscribers
$subscribers = $db->query("SELECT * FROM subscribers ORDER BY created_at DESC")->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-title">
        <span><i class="fas fa-envelope-open-text text-primary mr-2"></i> Newsletter Subscribers (<?= count($subscribers) ?>)</span>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i> Print / Export</button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Email Address</th>
                    <th>Date Subscribed</th>
                    <th>Status</th>
                    <th style="width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No newsletter subscribers found yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $idx => $s): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong class="text-dark"><i class="far fa-envelope mr-2 text-primary"></i> <?= htmlspecialchars($s['email']) ?></strong>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y - g:i a', strtotime($s['created_at'])) ?></td>
                            <td><span class="badge badge-success px-2 py-1">Active</span></td>
                            <td>
                                <a href="subscribers.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Remove this email from subscribers list?');" title="Remove subscriber">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

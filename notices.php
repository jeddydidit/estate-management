<?php
require_once __DIR__ . '/includes/layout.php';
require_login();

$user = current_user();
$pdo = estate_db();
$canPost = in_array($user['role'], ['admin', 'manager'], true);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canPost) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($title === '' || $message === '') {
        $error = 'Please complete the notice title and message.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO notices (title, message, posted_by) VALUES (?, ?, ?)');
        $stmt->execute([$title, $message, (int) $user['id']]);

        $recipients = $pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll();
        foreach ($recipients as $recipient) {
            create_notification((int) $recipient['id'], 'New notice posted: ' . $title);
        }

        flash_set('success', 'Notice published successfully.');
        header('Location: notices.php');
        exit;
    }
}

$notices = $pdo->query('
    SELECT n.id, n.title, n.message, n.created_at, u.name AS poster_name
    FROM notices n
    JOIN users u ON u.id = n.posted_by
    ORDER BY n.created_at DESC
')->fetchAll();

render_dashboard_start('Notice Board', 'notices');
?>
<section class="grid-two">
    <div class="panel">
        <div class="panel-head">
            <h3>Announcements</h3>
            <span class="pill">Read only for residents</span>
        </div>
        <div class="notice-list">
            <?php foreach ($notices as $notice): ?>
                <div class="notice-item">
                    <div class="title"><?php echo e($notice['title']); ?></div>
                    <p><?php echo e($notice['message']); ?></p>
                    <small><?php echo e($notice['poster_name']); ?> | <?php echo e(format_datetime($notice['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$notices): ?>
                <div class="notice-item">No notices have been posted yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Post Notice</h3>
            <span class="pill"><?php echo $canPost ? 'Manager/Admin' : 'View only'; ?></span>
        </div>

        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($message = flash_get('success')): ?><div class="alert success"><?php echo e($message); ?></div><?php endif; ?>

        <?php if ($canPost): ?>
            <form method="post" class="form notice-form">
                <div class="form-group">
                    <label for="title">Notice Title</label>
                    <input class="input" id="title" name="title" type="text" placeholder="Example: Water maintenance on Saturday" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea class="textarea" id="message" name="message" placeholder="Give residents clear details" required></textarea>
                </div>
                <button class="btn-primary" type="submit">Publish Notice</button>
            </form>
        <?php else: ?>
            <div class="feed-item">Only managers and admins can publish notices. Residents can view updates here without spam.</div>
        <?php endif; ?>
    </div>
</section>
<?php
render_dashboard_end();

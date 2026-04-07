<?php
require_once __DIR__ . '/includes/layout.php';
require_login();

$user = current_user();
$pdo = estate_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    mark_all_notifications_read((int) $user['id']);
    flash_set('success', 'All notifications marked as read.');
    header('Location: notifications.php');
    exit;
}

$notifications = notifications_for_user((int) $user['id']);

render_dashboard_start('Notifications', 'notifications');
?>
<section class="panel">
    <div class="panel-head">
        <h3>Your Notifications</h3>
        <form method="post">
            <input type="hidden" name="mark_all" value="1">
            <button class="btn-secondary" type="submit">Mark all read</button>
        </form>
    </div>

    <?php if ($message = flash_get('success')): ?>
        <div class="alert success"><?php echo e($message); ?></div>
    <?php endif; ?>

    <div class="notice-list">
        <?php foreach ($notifications as $note): ?>
            <div class="notice-item">
                <div class="title"><?php echo e($note['message']); ?></div>
                <small><?php echo e(format_datetime($note['created_at'])); ?></small>
                <span class="badge <?php echo (int) $note['is_read'] ? 'green' : 'yellow'; ?>"><?php echo (int) $note['is_read'] ? 'Read' : 'Unread'; ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$notifications): ?>
            <div class="notice-item">No notifications yet.</div>
        <?php endif; ?>
    </div>
</section>
<?php
render_dashboard_end();

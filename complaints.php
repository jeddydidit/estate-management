<?php
require_once __DIR__ . '/includes/layout.php';
require_login();

$user = current_user();
$pdo = estate_db();
$error = null;
$canManage = in_array($user['role'], ['admin', 'manager'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && $user['role'] === 'user') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || $description === '') {
            $error = 'Please enter both a complaint title and description.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO complaints (user_id, title, description) VALUES (?, ?, ?)');
            $stmt->execute([(int) $user['id'], $title, $description]);

            $recipients = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'manager')")->fetchAll();
            foreach ($recipients as $recipient) {
                create_notification((int) $recipient['id'], 'New complaint submitted: ' . $title);
            }

            flash_set('success', 'Your complaint has been submitted.');
            header('Location: complaints.php');
            exit;
        }
    }

    if ($action === 'update_status' && $canManage) {
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        if (!in_array($status, ['Pending', 'Resolved'], true)) {
            $status = 'Pending';
        }

        $stmt = $pdo->prepare('UPDATE complaints SET status = ? WHERE id = ?');
        $stmt->execute([$status, $complaintId]);

        $owner = $pdo->prepare('SELECT user_id, title FROM complaints WHERE id = ? LIMIT 1');
        $owner->execute([$complaintId]);
        $complaint = $owner->fetch();

        if ($complaint) {
            create_notification((int) $complaint['user_id'], 'Your complaint "' . $complaint['title'] . '" is now ' . strtolower($status) . '.');
        }

        flash_set('success', 'Complaint status updated.');
        header('Location: complaints.php');
        exit;
    }
}

if ($user['role'] === 'user') {
    $stmt = $pdo->prepare('
        SELECT id, title, description, status, created_at, updated_at
        FROM complaints
        WHERE user_id = ?
        ORDER BY created_at DESC
    ');
    $stmt->execute([(int) $user['id']]);
    $complaints = $stmt->fetchAll();
} else {
    $complaints = $pdo->query('
        SELECT c.id, c.title, c.description, c.status, c.created_at, c.updated_at, u.name AS resident_name
        FROM complaints c
        JOIN users u ON u.id = c.user_id
        ORDER BY c.created_at DESC
    ')->fetchAll();
}

render_dashboard_start('Complaints Desk', 'complaints');
?>
<section class="grid-two">
    <div class="panel">
        <div class="panel-head">
            <h3><?php echo $user['role'] === 'user' ? 'Submit Complaint' : 'Complaint Queue'; ?></h3>
            <span class="pill">Structured</span>
        </div>

        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($message = flash_get('success')): ?><div class="alert success"><?php echo e($message); ?></div><?php endif; ?>

        <?php if ($user['role'] === 'user'): ?>
            <form method="post" class="form">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="title">Complaint Title</label>
                    <input class="input" id="title" name="title" type="text" placeholder="Example: Water leakage in block A" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="textarea" id="description" name="description" placeholder="Explain the issue clearly" required></textarea>
                </div>
                <button class="btn-primary" type="submit">Submit Complaint</button>
            </form>
        <?php else: ?>
            <div class="notice-list">
                <?php foreach ($complaints as $complaint): ?>
                    <div class="notice-item">
                        <div class="title"><?php echo e($complaint['title']); ?></div>
                        <p><?php echo e($complaint['resident_name']); ?></p>
                        <p><?php echo e($complaint['description']); ?></p>
                        <small><?php echo e(format_datetime($complaint['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3><?php echo $user['role'] === 'user' ? 'My Complaints' : 'Update Status'; ?></h3>
            <span class="pill"><?php echo (int) count($complaints); ?></span>
        </div>

        <div class="notice-list">
            <?php foreach ($complaints as $complaint): ?>
                <div class="notice-item">
                    <div class="title"><?php echo e($complaint['title']); ?></div>
                    <?php if ($canManage): ?>
                        <p><?php echo e($complaint['resident_name']); ?></p>
                        <form method="post" class="toolbar">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="complaint_id" value="<?php echo (int) $complaint['id']; ?>">
                            <select class="select-input" name="status">
                                <option value="Pending" <?php echo $complaint['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Resolved" <?php echo $complaint['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                            <button class="btn-secondary" type="submit">Save</button>
                        </form>
                    <?php else: ?>
                        <p><?php echo e($complaint['description']); ?></p>
                    <?php endif; ?>
                    <small>Status: <span class="badge <?php echo $complaint['status'] === 'Resolved' ? 'green' : 'yellow'; ?>"><?php echo e($complaint['status']); ?></span></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$complaints): ?>
                <div class="notice-item">No complaints yet.</div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
render_dashboard_end();

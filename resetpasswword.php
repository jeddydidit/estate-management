<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect_by_role(current_user()['role']);
}

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$error = null;

if ($token === '') {
    $error = 'The reset link is missing its token.';
} else {
    $pdo = estate_db();
    $stmt = $pdo->prepare('SELECT id, reset_expires_at FROM users WHERE reset_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'This reset token is invalid.';
    } elseif (strtotime((string) $user['reset_expires_at']) < time()) {
        $error = 'This reset link has expired.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (strlen($password) < 8) {
            $error = 'Please use at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?');
            $update->execute([$hash, (int) $user['id']]);

            flash_set('success', 'Password reset successful. You can sign in with your new password.');
            header('Location: login.php');
            exit;
        }
    }
}

render_auth_shell_start('Estate Connect | Reset Password', 'Choose a fresh password and get back to your estate workspace.');
?>
<h2>Reset password</h2>
<p class="subtitle">Enter a new secure password for your account.</p>

<?php if ($error): ?>
    <div class="alert error"><?php echo e($error); ?></div>
<?php endif; ?>

<form method="post" class="form">
    <input type="hidden" name="token" value="<?php echo e($token); ?>">
    <div class="form-group">
        <label for="password">New Password</label>
        <input class="input" id="password" name="password" type="password" placeholder="New password" required>
    </div>
    <div class="form-group">
        <label for="confirm">Confirm Password</label>
        <input class="input" id="confirm" name="confirm" type="password" placeholder="Confirm password" required>
    </div>
    <button class="btn-primary" type="submit">Update Password</button>
</form>

<div class="inline-links">
    <a href="login.php">Back to login</a>
    <a href="forgotpassword.php">Request another link</a>
</div>
<?php
render_auth_shell_end();


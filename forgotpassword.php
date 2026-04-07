<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect_by_role(current_user()['role']);
}

$error = null;
$success = null;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter the email address on your account.';
    } else {
        $pdo = estate_db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $update = $pdo->prepare('UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?');
            $update->execute([$token, $expires, (int) $user['id']]);

            $resetLink = absolute_app_url('resetpasswword.php?token=' . $token);

            $htmlContent = '
                <div style="font-family:Poppins,Arial,sans-serif;background:#f4f6f8;padding:40px 0;">
                    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(0,0,0,0.12);">
                        <div style="background:linear-gradient(135deg,#09131f,#58c4b9);padding:28px;color:#fff;text-align:center;">
                            <h1 style="margin:0;font-size:28px;">Estate Connect</h1>
                        </div>
                        <div style="padding:32px;color:#334;">
                            <p style="margin-top:0;">Hi,</p>
                            <p>Use the button below to reset your Estate Connect password. This link expires in 1 hour.</p>
                            <p style="text-align:center;margin:28px 0;">
                                <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#b8a271;color:#09131f;text-decoration:none;padding:14px 22px;border-radius:14px;font-weight:700;">Reset Password</a>
                            </p>
                            <p>If you did not request this reset, you can ignore this email.</p>
                        </div>
                    </div>
                </div>
            ';

            [$sent, $sendError] = send_brevo_email($email, 'Estate Connect Password Reset', $htmlContent);

            if ($sent) {
                $success = 'A password reset email has been sent.';
            } else {
                $error = $sendError ?: 'We could not send the reset email right now.';
            }
        } else {
            $error = 'No account matches that email address.';
        }
    }
}

render_auth_shell_start('Estate Connect | Forgot Password', 'Reset your password without losing access to the platform.');
?>
<h2>Forgot password</h2>
<p class="subtitle">Enter your email and we will generate a secure reset link.</p>

<?php if ($error): ?>
    <div class="alert error"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert success">
        <?php echo e($success); ?>
    </div>
<?php endif; ?>

<form method="post" class="form">
    <div class="form-group">
        <label for="email">Email Address</label>
        <input class="input" id="email" name="email" type="email" placeholder="you@example.com" required>
    </div>
    <button class="btn-primary" type="submit">Generate Reset Link</button>
</form>

<div class="inline-links">
    <a href="login.php">Back to login</a>
    <a href="register.php">Create account</a>
</div>
<?php
render_auth_shell_end();

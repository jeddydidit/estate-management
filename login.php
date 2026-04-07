<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect_by_role(current_user()['role']);
}

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = estate_db()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            redirect_by_role($user['role']);
        }

        $error = 'Invalid email or password.';
    }
}

render_auth_shell_start(
    'Estate Connect | Sign In',
    'A premium apartment communication hub for residents, managers, and admins.'
);
?>
<div class="eyebrow">Secure access</div>
<h2>Sign in to your workspace</h2>
<p class="subtitle">Stay on top of notices, complaints, direct messages, and estate updates in one organized dashboard.</p>

<?php if ($success): ?>
    <div class="alert success"><?php echo e($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="auth-points">
    <div class="auth-point">
        <i class="fa-solid fa-shield-heart"></i>
        <div>
            <strong>Role-based access</strong>
            <div class="muted">Residents, managers, and admins are routed to their own dashboards.</div>
        </div>
    </div>
    <div class="auth-point">
        <i class="fa-solid fa-comments"></i>
        <div>
            <strong>Structured communication</strong>
            <div class="muted">No more crowded WhatsApp threads or missed apartment updates.</div>
        </div>
    </div>
</div>

<form method="post" class="form">
    <div class="form-group">
        <label for="email">Email Address</label>
        <input class="input" id="email" name="email" type="email" placeholder="name@example.com" autocomplete="email" required>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrap">
            <input class="input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button class="password-toggle" type="button" data-toggle-password="#password" aria-label="Show password">
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
    </div>
    <button class="btn-primary" type="submit">Sign In</button>
</form>

<div class="auth-footer">
    <div class="inline-links">
        <a href="forgotpassword.php">Forgot password?</a>
        <a href="register.php">Create resident account</a>
    </div>
</div>
<?php
render_auth_shell_end();

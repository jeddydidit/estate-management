<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect_by_role(current_user()['role']);
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $houseNumber = trim($_POST['house_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $houseNumber === '' || $phone === '' || $password === '' || $confirm === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (strlen($password) < 8) {
        $error = 'Use at least 8 characters for your password.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = estate_db();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, house_number, phone) VALUES (?, ?, ?, "user", ?, ?)');
            $stmt->execute([$name, $email, $hash, $houseNumber, $phone]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['role'] = 'user';
            $_SESSION['name'] = $name;
            redirect_by_role('user');
        }
    }
}

render_auth_shell_start(
    'Estate Connect | Create Account',
    'Join the resident portal and keep every community conversation clean, clear, and organized.'
);
?>
<div class="eyebrow">Resident signup</div>
<h2>Create your resident account</h2>
<p class="subtitle">Residents can register here. Managers and admins are provisioned separately for stronger access control.</p>

<?php if ($error): ?>
    <div class="alert error"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="auth-points">
    <div class="auth-point">
        <i class="fa-solid fa-user-shield"></i>
        <div>
            <strong>Secure onboarding</strong>
            <div class="muted">Your password is hashed and your account is routed to the resident dashboard automatically.</div>
        </div>
    </div>
    <div class="auth-point">
        <i class="fa-solid fa-bullhorn"></i>
        <div>
            <strong>Stay in the loop</strong>
            <div class="muted">Receive notices, complaint updates, and manager messages in one organized place.</div>
        </div>
    </div>
</div>

<form method="post" class="form">
    <div class="field-grid">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input class="input" id="name" name="name" type="text" placeholder="Your full name" autocomplete="name" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input class="input" id="email" name="email" type="email" placeholder="name@example.com" autocomplete="email" required>
        </div>
    </div>

    <div class="field-grid">
        <div class="form-group">
            <label for="house_number">House / Apartment Number</label>
            <input class="input" id="house_number" name="house_number" type="text" placeholder="A12 or Block 3" autocomplete="address-line1" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input class="input" id="phone" name="phone" type="tel" placeholder="+1 555 123 4567" autocomplete="tel" required>
        </div>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrap">
            <input class="input" id="password" name="password" type="password" placeholder="Create a strong password" autocomplete="new-password" required>
            <button class="password-toggle" type="button" data-toggle-password="#password" aria-label="Show password">
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
    </div>

    <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <div class="password-wrap">
            <input class="input" id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" autocomplete="new-password" required>
            <button class="password-toggle" type="button" data-toggle-password="#confirm_password" aria-label="Show confirm password">
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
    </div>

    <button class="btn-primary" type="submit">Create Account</button>
</form>

<div class="auth-footer">
    <div class="inline-links">
        <a href="login.php">Already have an account?</a>
        <a href="forgotpassword.php">Forgot password?</a>
    </div>
    <div class="auth-note">
        By creating an account, you agree to keep estate communication respectful and relevant.
    </div>
</div>
<?php
render_auth_shell_end();

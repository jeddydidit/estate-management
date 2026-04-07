<?php
require_once __DIR__ . '/includes/layout.php';
require_login();

$user = current_user();
$pdo = estate_db();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profilePic = null;

    if ($name === '' || $email === '') {
        $error = 'Name and email cannot be empty.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $check->execute([$email, (int) $user['id']]);

        if ($check->fetch()) {
            $error = 'That email is already in use by another account.';
        } else {
            if (!empty($_FILES['profile_pic']['name'])) {
                [$profilePic, $uploadError] = upload_profile_picture($_FILES['profile_pic']);
                if ($uploadError) {
                    $error = $uploadError;
                }
            }

            if (!$error) {
                if ($profilePic) {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, profile_pic = ? WHERE id = ?');
                    $stmt->execute([$name, $email, $profilePic, (int) $user['id']]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                    $stmt->execute([$name, $email, (int) $user['id']]);
                }

                $_SESSION['name'] = $name;
                flash_set('success', 'Profile updated successfully.');
                header('Location: profile.php');
                exit;
            }
        }
    }
}

$fresh = $pdo->prepare('SELECT id, name, email, role, profile_pic, created_at FROM users WHERE id = ? LIMIT 1');
$fresh->execute([(int) $user['id']]);
$user = $fresh->fetch();

render_dashboard_start('Profile Settings', 'profile');
?>
<section class="profile-grid">
    <div class="profile-box">
        <div class="profile-visual">
            <div>
                <span class="pill"><?php echo e(ucfirst($user['role'])); ?></span>
                <h3 style="margin: 12px 0 6px;"><?php echo e($user['name']); ?></h3>
                <p class="muted"><?php echo e($user['email']); ?></p>
            </div>
            <div class="big-avatar" style="background-image:url('<?php echo e(profile_avatar($user)); ?>'); background-size: cover; background-position: center;"><?php echo e(avatar_initial($user['name'])); ?></div>
        </div>
    </div>

    <div class="profile-box">
        <div class="panel-head">
            <h3>Edit Profile</h3>
            <span class="pill">Secure update</span>
        </div>

        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($message = flash_get('success')): ?><div class="alert success"><?php echo e($message); ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input class="input" id="name" name="name" type="text" value="<?php echo e($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input class="input" id="email" name="email" type="email" value="<?php echo e($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="profile_pic">Profile Picture</label>
                <input class="input" id="profile_pic" name="profile_pic" type="file" accept="image/*">
            </div>
            <button class="btn-primary" type="submit">Save Changes</button>
        </form>
    </div>
</section>
<?php
render_dashboard_end();

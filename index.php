<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect_by_role(current_user()['role']);
}

header('Location: login.php');
exit;


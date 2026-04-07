<?php
require_once __DIR__ . '/includes/layout.php';
require_login();
handle_forum_submission();
require_once __DIR__ . '/includes/forum_page.php';

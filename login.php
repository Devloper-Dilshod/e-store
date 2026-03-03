<?php
require_once 'core/config.php';
require_once 'core/render.php';

// Redirect to webapp if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit;
}

// Redirect to webapp (Telegram Web App the main auth method)
$webapp_url = getSetting('webapp_url', '');
render_page('login_view.php', ['webapp_url' => $webapp_url, 'csrf_token' => generate_csrf_token()]);
?>

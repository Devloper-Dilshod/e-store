<?php
/**
 * Telegram Webhook - Xavfsiz
 */
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/bot_logic.php';

// Only accept POST from Telegram
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Forbidden');
}

// Optional: Verify request comes with correct secret token header
// You can set this in set_webhook.php with 'secret_token' param
// $secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
// if ($secret !== 'your_secret') { http_response_code(403); exit; }

$input = file_get_contents('php://input');
if (empty($input)) { http_response_code(200); echo "OK"; exit; }

$update = json_decode($input, true);

if ($update) {
    try {
        handleUpdate($update);
    } catch (Throwable $e) {
        error_log("Bot Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }
}

http_response_code(200);
echo "OK";
?>

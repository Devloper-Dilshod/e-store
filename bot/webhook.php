<?php
/**
 * Telegram Webhook Handler
 * Receives POST requests from Telegram.
 */

// 1. Core Config & Logic access
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/bot_logic.php';

// 2. Security Check (Optional but recommended)
// Check if the request is from Telegram IP ranges or verify bot token in path
// For simplicity, we just check if it's a POST request with JSON
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if ($update) {
    try {
        handleUpdate($update);
    } catch (Exception $e) {
        // Log errors to a file if needed
        error_log("Bot Webhook Error: " . $e->getMessage());
    }
}

// 3. Always return 200 OK to Telegram
http_response_code(200);
echo "OK";
?>

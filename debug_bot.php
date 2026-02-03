<?php
require_once __DIR__ . '/core/config.php';
$info = sendTelegram('getWebhookInfo', []);
echo "Current Webhook Info:\n";
print_r($info);

echo "\nChecking if bot files exist:\n";
echo "bot/webhook.php: " . (file_exists(__DIR__ . '/bot/webhook.php') ? "EXISTS" : "MISSING") . "\n";
echo "bot/bot_logic.php: " . (file_exists(__DIR__ . '/bot/bot_logic.php') ? "EXISTS" : "MISSING") . "\n";
?>

<?php
/**
 * Utility script to set/unset Telegram Webhook
 */
require_once __DIR__ . '/../core/config.php';

// Change this to your public URL (requires HTTPS)
// Example: https://yourdomain.com/Store/bot/webhook.php
$webhook_url = ""; 

if (php_sapi_name() !== 'cli' && isset($_GET['url'])) {
    $webhook_url = $_GET['url'];
}

if (!$webhook_url) {
    echo "⚠️ Webhook URL belgilanmagan!\n";
    echo "Foydalanish: set_webhook.php?url=https://your-public-url.com/bot/webhook.php\n";
    exit;
}

echo "Setting Webhook to: $webhook_url ...\n";

$response = sendTelegram('setWebhook', [
    'url' => $webhook_url,
    'drop_pending_updates' => true
]);

if ($response['ok']) {
    echo "✅ Webhook muvaffaqiyatli o'rnatildi!\n";
} else {
    echo "❌ Xatolik: " . ($response['description'] ?? 'Noma\'lum xato') . "\n";
}

print_r($response);
?>

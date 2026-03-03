<?php
// Image proxy - local images + telegram fallback
require_once 'core/config.php';

if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

$file_id = $_GET['id'] ?? null;
$expires = 60 * 60 * 24 * 30; // 30 days

header("Cache-Control: public, max-age=$expires");
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

if (!$file_id) {
    serve_placeholder();
    exit;
}

// Security: prevent path traversal
$file_id = basename($file_id);

// Case 1: local image path (new system)
$local_path = __DIR__ . '/images/' . $file_id;
if (file_exists($local_path)) {
    $mime = mime_content_type($local_path) ?: 'image/jpeg';
    header("Content-Type: $mime");
    readfile($local_path);
    exit;
}

// Case 2: old telegram file_id format
// Try to proxy from Telegram
$url = getTelegramFileUrl($file_id);

if ($url === 'assets/images/placeholder.png' || !filter_var($url, FILTER_VALIDATE_URL)) {
    serve_placeholder();
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$data = curl_exec($ch);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($data) {
    header("Content-Type: $type");
    echo $data;
} else {
    serve_placeholder();
}

function serve_placeholder() {
    $ph = __DIR__ . '/assets/images/placeholder.png';
    header("Content-Type: image/png");
    if (file_exists($ph)) readfile($ph);
    else {
        // Create a simple 1x1 placeholder
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }
}
?>

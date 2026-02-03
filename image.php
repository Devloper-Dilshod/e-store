<?php
// Simple Proxy to serve images from Telegram URL with Optimization
require_once 'core/config.php';

// Release session lock immediately to allow parallel image loading
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$file_id = $_GET['id'] ?? null;
if (!$file_id) {
    header("Content-Type: image/png");
    readfile("assets/images/placeholder.png");
    exit;
}

$url = getTelegramFileUrl($file_id);

// Browser caching
$expires = 60*60*24*7; // 1 week
header("Cache-Control: public, max-age=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

if ($url === 'assets/images/placeholder.png' || !filter_var($url, FILTER_VALIDATE_URL)) {
    header("Content-Type: image/png");
    readfile("assets/images/placeholder.png");
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$data = curl_exec($ch);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($data) {
    header("Content-Type: $type");
    echo $data;
} else {
    header("Content-Type: image/png");
    readfile("assets/images/placeholder.png");
}
?>

<?php
// webapp_set_cart.php - Sets cart in session from WebApp
require_once '../core/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$raw = file_get_contents('php://input');
$items = json_decode($raw, true);

if (!is_array($items)) { http_response_code(400); echo json_encode(['error' => 'invalid']); exit; }

$_SESSION['cart'] = [];
foreach ($items as $item) {
    $pid = (int)($item['prodId'] ?? 0);
    $vid = isset($item['variantId']) && $item['variantId'] ? (int)$item['variantId'] : null;
    $qty = max(1, (int)($item['qty'] ?? 1));
    if ($pid > 0) {
        $_SESSION['cart'][] = [
            'product_id' => $pid,
            'variant_id' => $vid,
            'quantity' => $qty
        ];
    }
}

echo json_encode(['ok' => true, 'count' => count($_SESSION['cart'])]);

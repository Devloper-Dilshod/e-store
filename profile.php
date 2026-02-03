<?php
require_once 'core/config.php';
require_once 'core/render.php';

// Auth Check (Redundant if header handles it, but good for safety)
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$orders = [];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch items for each order to show images
foreach($orders as &$order) {
    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.file_id as p_file, pv.variant_name, pv.file_id as v_file 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id 
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$order['id']]);
    $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);

render_page('profile_view.php', ['orders' => $orders]);
?>

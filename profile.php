<?php
require_once 'core/config.php';
require_once 'core/render.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Check if user is blocked
$blocked = $pdo->prepare("SELECT is_blocked FROM users WHERE id=?");
$blocked->execute([$_SESSION['user_id']]);
$bl = $blocked->fetch();
if ($bl && $bl['is_blocked']) { session_destroy(); header("Location: login.php?err=blocked"); exit; }

// Orders with pagination
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$total_orders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$total_orders->execute([$_SESSION['user_id']]);
$total_count = $total_orders->fetchColumn();
$total_pages = max(1, ceil($total_count / $limit));

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as &$order) {
    $itemStmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_path as p_file, pv.variant_name, pv.image_path as v_file FROM order_items oi JOIN products p ON oi.product_id=p.id LEFT JOIN product_variants pv ON oi.variant_id=pv.id WHERE oi.order_id=?");
    $itemStmt->execute([$order['id']]);
    $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);

render_page('profile_view.php', ['orders' => $orders, 'current_page' => $page, 'total_pages' => $total_pages]);
?>

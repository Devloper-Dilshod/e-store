<?php
require_once 'core/config.php';
require_once 'core/render.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Simple Admin check by phone number or just allow for now if user logged in
// In a real app we'd check an is_admin column

// Daily Stats
$dailyStmt = $pdo->query("
    SELECT SUM(total_price) as total, COUNT(*) as count 
    FROM orders 
    WHERE date(created_at) = date('now') 
    AND status != 'cancelled'
");
$daily = $dailyStmt->fetch(PDO::FETCH_ASSOC);

// Weekly Stats
$weeklyStmt = $pdo->query("
    SELECT SUM(total_price) as total, COUNT(*) as count 
    FROM orders 
    WHERE created_at >= date('now', '-7 days') 
    AND status != 'cancelled'
");
$weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);

// Monthly Stats
$monthlyStmt = $pdo->query("
    SELECT SUM(total_price) as total, COUNT(*) as count 
    FROM orders 
    WHERE created_at >= date('now', 'start of month') 
    AND status != 'cancelled'
");
$monthly = $monthlyStmt->fetch(PDO::FETCH_ASSOC);

// Recent Orders with Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_pages = ceil($total_orders / $limit);

$ordersStmt = $pdo->prepare("
    SELECT * FROM orders 
    ORDER BY id DESC 
    LIMIT ? OFFSET ?
");
$ordersStmt->bindValue(1, $limit, PDO::PARAM_INT);
$ordersStmt->bindValue(2, $offset, PDO::PARAM_INT);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

render_page('stats_view.php', [
    'daily' => $daily,
    'weekly' => $weekly,
    'monthly' => $monthly,
    'orders' => $orders,
    'current_page' => $page,
    'total_pages' => $total_pages
]);
?>

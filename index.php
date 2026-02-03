<?php
require_once 'core/config.php';
require_once 'core/render.php';

// Pagination Settings
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch Data
$posters = $pdo->query("SELECT * FROM posters ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Count total products for pagination
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = ceil($total_products / $limit);

$products = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?");
$products->bindValue(1, $limit, PDO::PARAM_INT);
$products->bindValue(2, $offset, PDO::PARAM_INT);
$products->execute();
$products = $products->fetchAll(PDO::FETCH_ASSOC);

$data = [
    'posters' => $posters, 
    'categories' => $categories, 
    'products' => $products,
    'current_page' => $page,
    'total_pages' => $total_pages
];

// If it's an HTMX pagination request, only render products partial
if (isset($_GET['page']) && isset($_SERVER['HTTP_HX_REQUEST'])) {
    include 'views/partials/home_products.php';
} else {
    render_page('home_view.php', $data);
}
?>

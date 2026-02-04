<?php
require_once 'core/config.php';
require_once 'core/render.php';

$query = clean_input($_GET['q'] ?? '');
$cat_id = (int)($_GET['cat'] ?? 0);
$sort = clean_input($_GET['sort'] ?? 'newest');
$page = (int)($_GET['page'] ?? 1);
$limit = 30;
$offset = ($page - 1) * $limit;

$params = [];
$sql = "SELECT * FROM products WHERE 1=1";

if ($query) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$query%";
    $params[] = "%$query%";
}

if ($cat_id) {
    $sql .= " AND category_id = ?";
    $params[] = $cat_id;
}

// Discount Filter
if ($sort === 'discount') {
    $sql .= " AND has_discount = 1";
}

// Count for pagination
$count_sql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Sorting logic
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY CASE WHEN has_discount = 1 THEN discount_price ELSE base_price END ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY CASE WHEN has_discount = 1 THEN discount_price ELSE base_price END DESC";
        break;
    case 'alpha':
        $sql .= " ORDER BY name ASC";
        break;
    case 'discount':
        $sql .= " ORDER BY discount_percent DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$sql .= " LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

$data = [
    'products' => $products,
    'categories' => $categories,
    'query' => $query,
    'cat_id' => $cat_id,
    'sort' => $sort,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_items' => $total_items
];

if (isset($_SERVER['HTTP_HX_TARGET']) && $_SERVER['HTTP_HX_TARGET'] === 'search-results') {
    extract($data);
    require_once 'views/partials/search_results.php';
} else {
    render_page('search_view.php', $data);
}
?>

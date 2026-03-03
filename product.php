<?php
require_once 'core/config.php';
require_once 'core/render.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id=?");
$stmt->execute([$id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gallery - support both image_path and legacy file_id
$gallery = [];
$prod_img = $product['image_path'] ?? $product['file_id'] ?? null;
if ($prod_img) $gallery[] = $prod_img;
foreach ($variants as $v) {
    $v_img = $v['image_path'] ?? $v['file_id'] ?? null;
    if ($v_img && !in_array($v_img, $gallery)) $gallery[] = $v_img;
}
if (empty($gallery)) $gallery[] = null;

render_page('product_view.php', ['product' => $product, 'variants' => $variants, 'gallery' => $gallery]);
?>

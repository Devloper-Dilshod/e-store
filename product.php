<?php
require_once 'core/config.php';
require_once 'core/render.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->execute([$id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gallery
$gallery = [];
if($product['file_id']) $gallery[] = $product['file_id'];
foreach($variants as $v) if($v['file_id'] && !in_array($v['file_id'], $gallery)) $gallery[] = $v['file_id'];
if(empty($gallery)) $gallery[] = null;

render_page('product_view.php', ['product' => $product, 'variants' => $variants, 'gallery' => $gallery]);
?>

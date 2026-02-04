<?php
include 'core/config.php';
$id = 1;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    print_r($product);
} else {
    echo "Product #$id not found. Let's list some products:\n";
    $list = $pdo->query("SELECT id, name, has_discount, base_price FROM products LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($list);
}

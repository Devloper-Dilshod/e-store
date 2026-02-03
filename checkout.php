<?php
require_once 'core/config.php';
require_once 'core/render.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (empty($_SESSION['cart'])) { header("Location: index.php"); exit; }

// Fetch user info for pre-fill
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate Total Sum
$total_sum = 0;
foreach ($_SESSION['cart'] as $item) {
    $pid = $item['product_id'];
    $vid = $item['variant_id'];
    $qty = $item['quantity'];

    $stmt = $pdo->prepare("SELECT base_price, has_discount, discount_percent FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $price = $product['base_price'];
        if ($vid) {
            $v_stmt = $pdo->prepare("SELECT price FROM product_variants WHERE id = ?");
            $v_stmt->execute([$vid]);
            $variant = $v_stmt->fetch(PDO::FETCH_ASSOC);
            if ($variant) {
                $price = $variant['price'];
            }
        }

        if ($product['has_discount'] && $product['discount_percent'] > 0) {
            $price = $price * (1 - $product['discount_percent']/100);
        }

        $total_sum += ($price * $qty);
    }
}

render_page('checkout_view.php', ['user' => $user, 'total_sum' => $total_sum]);
?>

<?php
require_once 'core/config.php';
require_once 'core/render.php';

// Access Control
// Access Control - Guests allowed


// Prepare Cart Items
$cart_items = [];
$total_sum = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $pid = $item['product_id'];
        $vid = $item['variant_id']; // 0 if none
        $qty = $item['quantity'];

        // Optimize queries ideally, but for now simple loop is fine for small carts
        $stmt = $pdo->prepare("SELECT name, base_price, file_id, has_discount, discount_percent FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $price = $product['base_price'];
            $name = $product['name'];
            $image = $product['file_id']; // ID

            if ($vid) {
                $v_stmt = $pdo->prepare("SELECT variant_name, price, file_id FROM product_variants WHERE id = ?");
                $v_stmt->execute([$vid]);
                $variant = $v_stmt->fetch(PDO::FETCH_ASSOC);
                if ($variant) {
                    $name .= " (" . $variant['variant_name'] . ")";
                    $price = $variant['price']; 
                    if ($variant['file_id']) $image = $variant['file_id'];
                }
            }

            if ($product['has_discount'] && $product['discount_percent'] > 0) {
                 $price = $price * (1 - $product['discount_percent']/100);
            }

            $line_total = $price * $qty;
            $total_sum += $line_total;

            $cart_items[] = [
                'key' => $key,
                'name' => $name,
                'image_id' => $image,
                'price' => $price,
                'quantity' => $qty,
                'total' => $line_total
            ];
        }
    }
}

render_page('cart_view.php', ['cart_items' => $cart_items, 'total_sum' => $total_sum]);
?>

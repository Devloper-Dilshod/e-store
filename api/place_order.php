<?php
require_once '../core/config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) { header("Location: ../index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $user_id = $_SESSION['user_id'];
    $name = clean_input($_POST['full_name'] ?? '');
    $phone = preg_replace('/[^0-9+]/', '', clean_input($_POST['phone'] ?? ''));
    if (!str_starts_with($phone, '+998')) {
        if (str_starts_with($phone, '998')) $phone = '+' . $phone;
        else $phone = '+998' . ltrim($phone, '0');
    }
    $address = clean_input($_POST['address']);

    try {
        $pdo->beginTransaction();
        
        $total_sum = 0; $items = []; $msg = "";
        foreach ($_SESSION['cart'] as $item) {
            $pid = $item['product_id']; $vid = $item['variant_id']; $qty = $item['quantity'];
            $s = $pdo->prepare("SELECT name, base_price, has_discount, discount_percent FROM products WHERE id=?");
            $s->execute([$pid]);
            $p = $s->fetch();
            
            if($p) {
                $price = $p['base_price']; $pname = $p['name'];
                $variant_str = "";
                
                if($vid) {
                    $v = $pdo->prepare("SELECT price, variant_name FROM product_variants WHERE id=?");
                    $v->execute([$vid]);
                    $vv = $v->fetch();
                    if($vv) { 
                        $price = $vv['price']; 
                        $variant_str = " [" . $vv['variant_name'] . "]";
                    }
                }
                
                if($p['has_discount']) $price *= (1-$p['discount_percent']/100);
                $total_sum += $price * $qty;
                $items[] = [$pid, $vid, $qty, $price];
                $msg .= "▫️ $pname$variant_str x $qty\n";
            }
        }
        
        $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, address, total_price) VALUES (?,?,?,?,?)")
            ->execute([$user_id, $name, $phone, $address, $total_sum]);
        $oid = $pdo->lastInsertId();
        
        $sins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?,?,?,?,?)");
        foreach($items as $i) $sins->execute(array_merge([$oid], $i));
        
        // Telegram Notification with Address and Variants
        $txt = "<b>🆕 YANGI BUYURTMA #$oid</b>\n\n";
        $txt .= "👤 Mijoz: <b>$name</b>\n";
        $txt .= "📞 Tel: <a href='tel:$phone'><b>$phone</b></a>\n";
        $txt .= "📍 Manzil: <b>$address</b>\n\n";
        $txt .= "🛒 Mahsulotlar:\n$msg\n";
        $txt .= "💰 Jami: <b>" . number_format($total_sum, 0, ',', ' ') . " so'm</b>";
        
        $kb = json_encode(['inline_keyboard'=>[[['text'=>'✅ Qabul qilish', 'callback_data'=>"adm_ok_$oid"],['text'=>'❌ Bekor qilish', 'callback_data'=>"adm_no_$oid"]]]]);
        sendTelegram('sendMessage', ['chat_id'=>$admin_chat_id, 'text'=>$txt, 'parse_mode'=>'HTML', 'reply_markup'=>$kb]);

        $pdo->commit();
        unset($_SESSION['cart']);
        header("Location: ../profile.php?message=success");

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        die($e->getMessage());
    }
}
?>

<?php
require_once '../core/config.php';

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit('order_' . $ip, 5, 60)) {
    die("⚠️ Juda ko'p so'rov. Biroz kutib qayta urinib ko'ring.");
}

if (empty($_SESSION['cart'])) { header("Location: ../index.php"); exit; }

// Check if user is blocked
if (isset($_SESSION['user_id'])) {
    $blocked = $pdo->prepare("SELECT is_blocked FROM users WHERE id=?");
    $blocked->execute([$_SESSION['user_id']]);
    $bl = $blocked->fetch();
    if ($bl && $bl['is_blocked']) {
        session_destroy();
        header("Location: ../index.php?err=blocked");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $user_id = $_SESSION['user_id'] ?? null;
    $telegram_id = $_SESSION['telegram_id'] ?? null;
    $name = clean_input($_POST['full_name'] ?? '');
    $phone = preg_replace('/[^0-9+]/', '', clean_input($_POST['phone'] ?? ''));
    
    // str_starts_with is now safe (polyfill in config.php for PHP 7.x)
    if (!str_starts_with($phone, '+998')) {
        if (str_starts_with($phone, '998')) $phone = '+' . $phone;
        else $phone = '+998' . ltrim($phone, '0');
    }
    $address = clean_input($_POST['address'] ?? '');
    $note = clean_input($_POST['note'] ?? '');

    if (empty($name) || empty($phone) || empty($address)) {
        header("Location: ../checkout.php?error=fields");
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $total_sum = 0; $items = []; $msg = "";
        foreach ($_SESSION['cart'] as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $vid = (int)($item['variant_id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            
            $s = $pdo->prepare("SELECT name, base_price, has_discount, discount_percent, discount_price FROM products WHERE id=?");
            $s->execute([$pid]);
            $p = $s->fetch();
            
            if ($p) {
                $price = (float)$p['base_price'];
                $pname = $p['name'];
                $variant_str = "";
                
                if ($vid) {
                    $v = $pdo->prepare("SELECT price, variant_name FROM product_variants WHERE id=?");
                    $v->execute([$vid]);
                    $vv = $v->fetch();
                    if ($vv) { 
                        $price = (float)$vv['price']; 
                        $variant_str = " [" . $vv['variant_name'] . "]";
                    }
                }
                
                if ($p['has_discount'] && $p['discount_price'] > 0) {
                    $price = (float)$p['discount_price'];
                } elseif ($p['has_discount']) {
                    $price = round($price * (1 - $p['discount_percent'] / 100));
                }
                
                $total_sum += $price * $qty;
                $items[] = [$pid, $vid ?: null, $qty, $price];
                $msg .= "▫️ $pname$variant_str x $qty — " . number_format($price * $qty, 0, ',', ' ') . " so'm\n";
            }
        }
        
        $pdo->prepare("INSERT INTO orders (user_id, telegram_id, customer_name, customer_phone, address, total_price, note) VALUES (?,?,?,?,?,?,?)")
            ->execute([$user_id, $telegram_id, $name, $phone, $address, $total_sum, $note]);
        $oid = $pdo->lastInsertId();
        
        $sins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?,?,?,?,?)");
        foreach ($items as $i) $sins->execute(array_merge([$oid], $i));
        
        // Telegram Notification
        $txt = "<b>🆕 YANGI BUYURTMA #$oid</b>\n\n";
        $txt .= "👤 Mijoz: <b>$name</b>\n";
        $txt .= "📞 Tel: <b>$phone</b>\n";
        $txt .= "📍 Manzil: <b>$address</b>\n";
        if ($note) $txt .= "📝 Izoh: <i>$note</i>\n";
        $txt .= "\n🛒 Mahsulotlar:\n$msg\n";
        $txt .= "💰 Jami: <b>" . number_format($total_sum, 0, ',', ' ') . " so'm</b>";
        
        if ($telegram_id) {
            $txt .= "\n🔗 Telegram ID: <code>$telegram_id</code>";
        }
        
        $kb = json_encode(['inline_keyboard' => [
            [
                ['text' => '✅ Qabul qilish', 'callback_data' => "adm_ok_$oid"],
                ['text' => '❌ Bekor qilish', 'callback_data' => "adm_no_$oid"]
            ]
        ]]);
        
        $admin_ids = getAllAdminIds();
        foreach ($admin_ids as $chat_id) {
            sendTelegram('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $txt,
                'parse_mode' => 'HTML',
                'reply_markup' => $kb
            ]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);
        
        // Notify user via Telegram if they have telegram_id
        if ($telegram_id) {
            $user_msg = "✅ <b>Buyurtmangiz qabul qilindi!</b>\n\n";
            $user_msg .= "📦 <b>Buyurtma #{$oid}</b>\n";
            $user_msg .= "💰 Jami: <b>" . number_format($total_sum, 0, ',', ' ') . " so'm</b>\n\n";
            $user_msg .= "Tez orada operatorlarimiz siz bilan bog'lanadi. Rahmat! 🙏";
            
            // Get bot's web app URL
            $webapp_url = getSetting('webapp_url', '');
            $reply_markup = null;
            if ($webapp_url) {
                $reply_markup = json_encode(['inline_keyboard' => [
                    [['text' => '📦 Buyurtmalarim', 'web_app' => ['url' => $webapp_url . '?page=orders']]]
                ]]);
            }
            
            $tg_params = [
                'chat_id' => $telegram_id,
                'text' => $user_msg,
                'parse_mode' => 'HTML'
            ];
            if ($reply_markup) $tg_params['reply_markup'] = $reply_markup;
            sendTelegram('sendMessage', $tg_params);
        }
        
        header("Location: ../index.php?message=order_success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Order error: " . $e->getMessage());
        header("Location: ../checkout.php?error=server");
        exit;
    }
}

header("Location: ../checkout.php");
exit;
?>

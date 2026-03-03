<?php
function handleUpdate($update) {
    global $pdo, $admin_chat_id;

    if (isset($update['message'])) {
        $msg = $update['message'];
        $chat_id = $msg['chat']['id'];
        $text = $msg['text'] ?? '';
        $photo = $msg['photo'] ?? null;

        if (!isUserAdmin($chat_id)) {
            // Regular user - show giant WebApp reply keyboard
            $webapp_url = getSetting('webapp_url', '');
            if ($webapp_url) {
                sendTelegram('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "🛍 <b>Do'konimizga xush kelibsiz!</b>\n\nQuyidagi tugma orqali do'konimizga kiring:",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [['text' => "🛒 Do'konni ochish", 'web_app' => ['url' => $webapp_url]]]
                        ],
                        'resize_keyboard' => true
                    ])
                ]);
            } else {
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🛍 Xush kelibsiz! Admin bilan bog'laning."]);
            }
            return;
        }

        // Admin flow
        $stmt = $pdo->prepare("SELECT state, temp_data FROM bot_state WHERE chat_id=?");
        $stmt->execute([$chat_id]);
        $state_row = $stmt->fetch(PDO::FETCH_ASSOC);
        $state = $state_row['state'] ?? 'MAIN_MENU';
        $temp_data = $state_row['temp_data'] ? json_decode($state_row['temp_data'], true) : [];

        if ($text === '/start' || $text === '🏠 Menyuga' || $text === '🔙 Orqaga') {
            setState($chat_id, 'MAIN_MENU');
            showMainMenu($chat_id);
            return;
        }

        switch ($state) {
            case 'MAIN_MENU':
                handleMainMenu($chat_id, $text);
                break;

            case 'ADD_ADMIN_ID':
                $new_id = null; $new_name = "Admin";
                if (isset($msg['forward_from'])) { $new_id = $msg['forward_from']['id']; $new_name = $msg['forward_from']['first_name']; }
                elseif (isset($msg['contact'])) { $new_id = $msg['contact']['user_id']; $new_name = $msg['contact']['first_name']; }
                elseif (is_numeric($text)) { $new_id = $text; }
                if ($new_id) {
                    if (isUserAdmin($new_id)) {
                        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "⚠️ Bu foydalanuvchi allaqochon admin."]);
                    } else {
                        $pdo->prepare("INSERT INTO admins (chat_id, name) VALUES (?,?)")->execute([$new_id, $new_name]);
                        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Yangi admin: $new_name ($new_id)"]);
                    }
                    setState($chat_id, 'MAIN_MENU'); showAdminMenu($chat_id);
                } else {
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ ID topilmadi. Qayta urinib ko'ring."]);
                }
                break;

            case 'SET_STORE_NAME':
                setSetting('store_name', $text);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Do'kon nomi \"$text\" ga o'zgartirildi."]);
                setState($chat_id, 'MAIN_MENU'); showSettingsMenu($chat_id);
                break;

            case 'SET_WELCOME_MSG':
                setSetting('welcome_message', $text);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Xush kelibsiz xabari yangilandi."]);
                setState($chat_id, 'MAIN_MENU'); showSettingsMenu($chat_id);
                break;

            case 'SET_CONTACT':
                setSetting('contact_phone', $text);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Telefon: $text"]);
                setState($chat_id, 'MAIN_MENU'); showSettingsMenu($chat_id);
                break;

            case 'SET_WEBAPP_URL':
                setSetting('webapp_url', $text);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Web App URL saqlandi."]);
                setState($chat_id, 'MAIN_MENU'); showSettingsMenu($chat_id);
                break;

            case 'SET_DELIVERY_INFO':
                setSetting('delivery_info', $text);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Yetkazib berish ma'lumoti yangilandi."]);
                setState($chat_id, 'MAIN_MENU'); showSettingsMenu($chat_id);
                break;

            case 'BLOCK_USER_ID':
                $block_id = trim($text);
                if (is_numeric($block_id)) {
                    $u = $pdo->prepare("SELECT id, name FROM users WHERE telegram_id=?");
                    $u->execute([$block_id]);
                    $uu = $u->fetch();
                    if ($uu) {
                        $pdo->prepare("UPDATE users SET is_blocked=1 WHERE telegram_id=?")->execute([$block_id]);
                        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Foydalanuvchi bloklandi: " . $uu['name'] . " ($block_id)"]);
                    } else {
                        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ Bu ID da foydalanuvchi topilmadi."]);
                    }
                } else {
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ Noto'g'ri ID."]);
                }
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                break;

            case 'ADD_CAT_NAME':
                $temp_data['name'] = $text;
                setState($chat_id, 'ADD_CAT_IMG', $temp_data);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📸 Rasm yuboring (yoki /skip yozing):", 'reply_markup' => json_encode(['keyboard' => [[['text' => '/skip']], [['text' => '🏠 Menyuga']]], 'resize_keyboard' => true])]);
                break;

            case 'ADD_CAT_IMG':
                $img_path = null;
                if ($photo) {
                    $fid = end($photo)['file_id'];
                    $img_path = save_image_from_telegram($fid);
                }
                $pdo->prepare("INSERT INTO categories (name, image_path) VALUES (?,?)")->execute([$temp_data['name'], $img_path]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Kategoriya saqlandi."]);
                break;

            case 'ADD_PROD_NAME':
                $temp_data['name'] = $text;
                setState($chat_id, 'ADD_PROD_PRICE', $temp_data);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "💰 Asosiy narx:"]);
                break;

            case 'ADD_PROD_PRICE':
                if (is_numeric($text)) {
                    $temp_data['price'] = $text;
                    setState($chat_id, 'ADD_PROD_CAT', $temp_data);
                    showCategorySelector($chat_id, 0);
                }
                break;

            case 'ADD_PROD_IMG':
                if ($photo) {
                    $fid = end($photo)['file_id'];
                    $temp_data['image_path'] = save_image_from_telegram($fid);
                    setState($chat_id, 'ADD_PROD_DESC', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📝 Tavsif:"]);
                } elseif ($text === '/skip') {
                    $temp_data['image_path'] = null;
                    setState($chat_id, 'ADD_PROD_DESC', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📝 Tavsif:"]);
                }
                break;

            case 'ADD_PROD_DESC':
                $temp_data['desc'] = $text;
                $pdo->prepare("INSERT INTO products (name, category_id, description, base_price, image_path) VALUES (?,?,?,?,?)")
                    ->execute([$temp_data['name'], $temp_data['cat_id'], $temp_data['desc'], $temp_data['price'], $temp_data['image_path'] ?? null]);
                $temp_data['prod_id'] = $pdo->lastInsertId();
                setState($chat_id, 'ASK_VARIANT', $temp_data);
                $kb = ['keyboard' => [[['text' => '➕ Variant qo\'shish'], ['text' => '✅ Tugatish']]], 'resize_keyboard' => true];
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Mahsulot saqlandi. Variant bormi?", 'reply_markup' => json_encode($kb)]);
                break;

            case 'ASK_VARIANT':
                if ($text === '➕ Variant qo\'shish') {
                    setState($chat_id, 'ADD_VAR_NAME', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Variant nomi (masalan: XL, Qizil):", 'reply_markup' => json_encode(['remove_keyboard' => true])]);
                } else {
                    setState($chat_id, 'MAIN_MENU');
                    showMainMenu($chat_id);
                    // Broadcast
                    $pid = $temp_data['prod_id'];
                    $p = $pdo->query("SELECT * FROM products WHERE id=$pid")->fetch();
                    if ($p) {
                        $msg_text = "<b>🆕 YANGI MAHSULOT!</b>\n\n🛍 <b>" . $p['name'] . "</b>\n💰 " . number_format($p['base_price']) . " so'm";
                        broadcastToAll($msg_text, $p['image_path'], "webapp.php?page=product&id=$pid");
                    }
                }
                break;

            case 'ADD_VAR_NAME':
                $temp_data['var_name'] = $text;
                setState($chat_id, 'ADD_VAR_PRICE', $temp_data);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Narxi:"]);
                break;

            case 'ADD_VAR_PRICE':
                if (is_numeric($text)) {
                    $temp_data['var_price'] = $text;
                    setState($chat_id, 'ADD_VAR_IMG', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Rasmi (/skip - rasmisiz):"]);
                }
                break;

            case 'ADD_VAR_IMG':
                $v_img = null;
                if ($photo) {
                    $v_img = save_image_from_telegram(end($photo)['file_id']);
                }
                $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, price, image_path) VALUES (?,?,?,?)")
                    ->execute([$temp_data['prod_id'], $temp_data['var_name'], $temp_data['var_price'], $v_img]);
                setState($chat_id, 'ASK_VARIANT', $temp_data);
                $kb = ['keyboard' => [[['text' => '➕ Variant qo\'shish'], ['text' => '✅ Tugatish']]], 'resize_keyboard' => true];
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Variant qo'shildi. Yana?", 'reply_markup' => json_encode($kb)]);
                break;

            case 'ADD_POSTER_IMG':
                if ($photo) {
                    $temp_data['image_path'] = save_image_from_telegram(end($photo)['file_id']);
                    setState($chat_id, 'ADD_POSTER_LINK', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🔗 Link yuboring (yoki # yozing):"]);
                }
                break;

            case 'ADD_POSTER_LINK':
                $pdo->prepare("INSERT INTO posters (image_path, link) VALUES (?,?)")->execute([$temp_data['image_path'], $text]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Poster qo'shildi!"]);
                break;

            case 'EDIT_PROD_NAME':
                $pdo->prepare("UPDATE products SET name=? WHERE id=?")->execute([$text, $temp_data['id']]);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Nom o'zgartirildi."]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                break;

            case 'EDIT_PROD_PRICE':
                if (is_numeric($text)) {
                    $pdo->prepare("UPDATE products SET base_price=? WHERE id=?")->execute([$text, $temp_data['id']]);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Narx o'zgartirildi."]);
                    setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                }
                break;

            case 'EDIT_PROD_DESC':
                $pdo->prepare("UPDATE products SET description=? WHERE id=?")->execute([$text, $temp_data['id']]);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Tavsif o'zgartirildi."]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                break;

            case 'WAIT_DISC_PCT':
                if (is_numeric($text)) {
                    $pct = (int)$text;
                    $base = $pdo->prepare("SELECT base_price FROM products WHERE id=?");
                    $base->execute([$temp_data['id']]);
                    $base_price = (float)$base->fetchColumn();
                    $new_price = round($base_price * (1 - $pct / 100));
                    $temp_data['pct'] = $pct;
                    $temp_data['new_price'] = $new_price;
                    setState($chat_id, 'WAIT_DISC_PRICE', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📉 $pct% chegirma\n💰 Hisob narx: " . number_format($new_price) . " so'm\n\nYangi narxni kiriting yoki tasdiqlash uchun " . number_format($new_price) . " yozing:"]);
                }
                break;

            case 'WAIT_DISC_PRICE':
                if (is_numeric($text)) {
                    $pdo->prepare("UPDATE products SET has_discount=1, discount_percent=?, discount_price=? WHERE id=?")
                        ->execute([$temp_data['pct'], $text, $temp_data['id']]);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Chegirma o'rnatildi!"]);
                    setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                    $pid = $temp_data['id'];
                    $p = $pdo->query("SELECT * FROM products WHERE id=$pid")->fetch();
                    if ($p) {
                        $msg_text = "<b>🔥 CHEGIRMA!</b>\n🛍 <b>" . $p['name'] . "</b>\n❌ " . number_format($p['base_price']) . " → ✅ <b>" . number_format($text) . " so'm</b>";
                        broadcastToAll($msg_text, $p['image_path'], "webapp.php?page=product&id=$pid");
                    }
                }
                break;

            case 'SEARCH_PROD':
                $page_n = $temp_data['page'] ?? 0;
                $limit = 8; $offset = $page_n * $limit;
                $stmt = $pdo->prepare("SELECT id, name FROM products WHERE name LIKE ? ORDER BY name ASC LIMIT $limit OFFSET $offset");
                $stmt->execute(["%$text%"]);
                $items = $stmt->fetchAll();
                $total = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name LIKE ?")->execute(["%$text%"]) ? $pdo->query("SELECT COUNT(*) FROM products WHERE name LIKE '%$text%'")->fetchColumn() : 0;
                if (!$items) {
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ Topilmadi."]);
                } else {
                    $kb = [];
                    foreach ($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => "pe_" . $i['id']]];
                    $nav = [];
                    if ($page_n > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "search_page_" . ($page_n - 1)];
                    if ($total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "search_page_" . ($page_n + 1)];
                    if ($nav) $kb[] = $nav;
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🔍 '$text' natijalari:", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
                    $temp_data['q'] = $text; setState($chat_id, 'SEARCH_PROD_PAGE', $temp_data);
                }
                break;
        }
    } elseif (isset($update['callback_query'])) {
        handleCallback($update['callback_query']);
    }
}

function handleMainMenu($chat_id, $text) {
    switch ($text) {
        case '👕 Mahsulotlar': listAdminItems($chat_id, 'prod', 0); break;
        case '📦 Buyurtmalar': listActiveOrders($chat_id); break;
        case '➕ Mahsulot qo\'shish': setState($chat_id, 'ADD_PROD_NAME'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📛 Nom:", 'reply_markup' => json_encode(['keyboard' => [[['text' => '🏠 Menyuga']]], 'resize_keyboard' => true])]); break;
        case '📂 Kategoriya qo\'shish': setState($chat_id, 'ADD_CAT_NAME'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📛 Nom:", 'reply_markup' => json_encode(['keyboard' => [[['text' => '🏠 Menyuga']]], 'resize_keyboard' => true])]); break;
        case '📝 Tahrirlash': listAdminItems($chat_id, 'edit', 0); break;
        case '📊 Statistika': showStatistics($chat_id); break;
        case '👥 Adminlar': showAdminMenu($chat_id); break;
        case '⚙️ Sozlamalar': showSettingsMenu($chat_id); break;
        case '🔍 Qidiruv': setState($chat_id, 'SEARCH_PROD'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🔍 Mahsulot nomini kiriting:"]); break;
        case '🖼 Poster qo\'shish': setState($chat_id, 'ADD_POSTER_IMG'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📸 Rasm yuboring:"]); break;
        case '🚫 Bloklash':
            setState($chat_id, 'BLOCK_USER_ID');
            sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Bloklash uchun foydalanuvchi Telegram ID sini yuboring:", 'reply_markup' => json_encode(['keyboard' => [[['text' => '🏠 Menyuga']]], 'resize_keyboard' => true])]);
            break;
        case '👤 Foydalanuvchilar': listUsers($chat_id, 0); break;
        case '🗑 O\'chirish':
            $kb = ['inline_keyboard' => [
                [['text' => '📂 Kategoriyalar', 'callback_data' => 'list_del_cat_0']],
                [['text' => '👕 Mahsulotlar', 'callback_data' => 'list_del_prd_0']],
                [['text' => '🖼 Posterlar', 'callback_data' => 'list_del_pst_0']]
            ]];
            sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Nimani o'chiramiz?", 'reply_markup' => json_encode($kb)]);
            break;
    }
}

function showMainMenu($chat_id) {
    $webapp_url = getSetting('webapp_url', '');
    $kb = ['keyboard' => [], 'resize_keyboard' => true];
    
    if ($webapp_url) {
        $kb['keyboard'][] = [['text' => "🛒 Do'konni ochish (Web App)", 'web_app' => ['url' => $webapp_url]]];
    }
    
    $kb['keyboard'] = array_merge($kb['keyboard'], [
        [['text' => '👕 Mahsulotlar'], ['text' => '📦 Buyurtmalar']],
        [['text' => '➕ Mahsulot qo\'shish'], ['text' => '📂 Kategoriya qo\'shish']],
        [['text' => '📝 Tahrirlash'], ['text' => '🔍 Qidiruv']],
        [['text' => '📊 Statistika'], ['text' => '🖼 Poster qo\'shish']],
        [['text' => '👥 Adminlar'], ['text' => '⚙️ Sozlamalar']],
        [['text' => '👤 Foydalanuvchilar'], ['text' => '🚫 Bloklash']],
        [['text' => '🗑 O\'chirish']]
    ]);
    
    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🛠 <b>ADMIN PANEL</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode($kb)]);
}

function showSettingsMenu($chat_id) {
    $sn = getSetting('store_name');
    $wm = getSetting('welcome_message');
    $wu = getSetting('webapp_url');
    $cp = getSetting('contact_phone');
    $kb = ['inline_keyboard' => [
        [['text' => "🏷 Do'kon nomi: $sn", 'callback_data' => 'set_name']],
        [['text' => "👋 Xush kelibsiz xabari", 'callback_data' => 'set_welcome']],
        [['text' => "📱 Telefon: $cp", 'callback_data' => 'set_phone']],
        [['text' => "🌐 Web App URL", 'callback_data' => 'set_webapp']],
        [['text' => "🚚 Yetkazib berish ma'lumoti", 'callback_data' => 'set_delivery']],
    ]];
    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "⚙️ <b>Do'kon sozlamalari</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode($kb)]);
}

function listUsers($chat_id, $page = 0) {
    global $pdo;
    $limit = 10; $offset = $page * $limit;
    $total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
    
    $txt = "👤 <b>Foydalanuvchilar (jami: $total):</b>\n\n";
    foreach ($users as $u) {
        $blocked = $u['is_blocked'] ? ' 🚫' : '';
        $txt .= "#{$u['id']} <b>" . htmlspecialchars($u['name']) . "</b>$blocked\n";
        $txt .= "   ID: <code>" . $u['telegram_id'] . "</code>\n";
        $txt .= "   Sana: " . date('d.m.Y', strtotime($u['created_at'])) . "\n\n";
    }
    
    $nav = [];
    if ($page > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "users_page_" . ($page - 1)];
    if ($total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "users_page_" . ($page + 1)];
    
    $kb = $nav ? [['inline_keyboard' => [$nav]]] : [];
    
    $params = ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'HTML'];
    if ($nav) $params['reply_markup'] = json_encode(['inline_keyboard' => [$nav]]);
    sendTelegram('sendMessage', $params);
}

function handleCallback($cb) {
    global $pdo;
    $data = $cb['data'];
    $chat_id = $cb['message']['chat']['id'];
    $mid = $cb['message']['message_id'];

    if (!isUserAdmin($chat_id)) {
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => '⛔ Ruxsat yo\'q']);
        return;
    }

    // Settings
    if ($data === 'set_name') { setState($chat_id, 'SET_STORE_NAME'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi do'kon nomini kiriting:"]); }
    elseif ($data === 'set_welcome') { setState($chat_id, 'SET_WELCOME_MSG'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi xush kelibsiz xabarini kiriting:"]); }
    elseif ($data === 'set_phone') { setState($chat_id, 'SET_CONTACT'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Telefon raqam (+998...)"]); }
    elseif ($data === 'set_webapp') { setState($chat_id, 'SET_WEBAPP_URL'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Web App URL ni kiriting (masalan: https://siz.domen.uz/Store/webapp.php):"]); }
    elseif ($data === 'set_delivery') { setState($chat_id, 'SET_DELIVERY_INFO'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yetkazib berish ma'lumotini kiriting:"]); }

    // Users pagination
    elseif (str_starts_with($data, 'users_page_')) {
        $p = (int)substr($data, 11);
        listUsers($chat_id, $p);
    }

    // Unblock user
    elseif (str_starts_with($data, 'unblock_')) {
        $tid = substr($data, 8);
        $pdo->prepare("UPDATE users SET is_blocked=0 WHERE telegram_id=?")->execute([$tid]);
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "✅ Blok olib tashlandi!"]);
    }
    elseif (str_starts_with($data, 'block_confirm_')) {
        $tid = substr($data, 14);
        $pdo->prepare("UPDATE users SET is_blocked=1 WHERE telegram_id=?")->execute([$tid]);
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "✅ Bloklandi!"]);
    }

    // Category selector
    elseif (str_starts_with($data, 'cat_sel_')) {
        $p = (int)substr($data, 8);
        showCategorySelector($chat_id, $p, $mid);
    } elseif (str_starts_with($data, 'cat_id_')) {
        $cid = substr($data, 7);
        $stmt = $pdo->prepare("SELECT state, temp_data FROM bot_state WHERE chat_id=?"); $stmt->execute([$chat_id]);
        $row = $stmt->fetch(); $temp = json_decode($row['temp_data'], true);
        $temp['cat_id'] = $cid;
        setState($chat_id, 'ADD_PROD_IMG', $temp);
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "✅ Kategoriya tanlandi. Rasm yuboring (yoki /skip):"]);
    }

    // Delete lists
    elseif (str_starts_with($data, 'list_del_cat_')) listAdminItems($chat_id, 'cat', (int)substr($data, 13), $mid);
    elseif (str_starts_with($data, 'list_del_prd_')) listAdminItems($chat_id, 'prod', (int)substr($data, 13), $mid);
    elseif (str_starts_with($data, 'list_del_pst_')) listAdminItems($chat_id, 'poster', (int)substr($data, 13), $mid);

    // Edit flow
    elseif ($data === 'adm_edit_list') listAdminItems($chat_id, 'edit', 0, $mid);
    elseif (str_starts_with($data, 'pe_name_')) { $id = substr($data, 8); setState($chat_id, 'EDIT_PROD_NAME', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi nom:"]); }
    elseif (str_starts_with($data, 'pe_price_')) { $id = substr($data, 9); setState($chat_id, 'EDIT_PROD_PRICE', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi narx:"]); }
    elseif (str_starts_with($data, 'pe_desc_')) { $id = substr($data, 8); setState($chat_id, 'EDIT_PROD_DESC', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi tavsif:"]); }
    elseif (str_starts_with($data, 'pe_disc_start_')) { $id = substr($data, 14); setState($chat_id, 'WAIT_DISC_PCT', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🏷 Chegirma %ini kiriting (masalan: 20):"]); }
    elseif (str_starts_with($data, 'pe_disc_stop_')) {
        $id = substr($data, 13);
        $pdo->prepare("UPDATE products SET has_discount=0, discount_percent=0, discount_price=0 WHERE id=?")->execute([$id]);
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "Chegirma to'xtatildi!", 'show_alert' => true]);
        showProductEditMenu($chat_id, $id, $mid);
    }
    elseif (str_starts_with($data, 'pe_')) {
        $id = substr($data, 3);
        showProductEditMenu($chat_id, $id, $mid);
    }

    // Variants
    elseif (str_starts_with($data, 've_add_')) { $id = substr($data, 7); setState($chat_id, 'ADD_VAR_NAME', ['prod_id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Variant nomi:"]); }
    elseif (str_starts_with($data, 've_list_')) {
        $id = substr($data, 8);
        $vars = $pdo->prepare("SELECT * FROM product_variants WHERE product_id=?"); $vars->execute([$id]); $rows = $vars->fetchAll();
        $kb = [];
        foreach ($rows as $v) $kb[] = [['text' => "❌ " . $v['variant_name'] . " (" . number_format($v['price']) . ")", 'callback_data' => "vd_" . $v['id']]];
        $kb[] = [['text' => '🔙 Orqaga', 'callback_data' => "pe_$id"]];
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "⚙️ Variantlar:", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    }
    elseif (str_starts_with($data, 'vd_')) {
        $vid = substr($data, 3);
        $pid_stmt = $pdo->prepare("SELECT product_id FROM product_variants WHERE id=?"); $pid_stmt->execute([$vid]);
        $pid = $pid_stmt->fetchColumn();
        $pdo->prepare("DELETE FROM product_variants WHERE id=?")->execute([$vid]);
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "O'chirildi!"]);
        $vars = $pdo->prepare("SELECT * FROM product_variants WHERE product_id=?"); $vars->execute([$pid]); $rows = $vars->fetchAll();
        $kb = [];
        foreach ($rows as $v) $kb[] = [['text' => "❌ " . $v['variant_name'], 'callback_data' => "vd_" . $v['id']]];
        $kb[] = [['text' => '🔙 Orqaga', 'callback_data' => "pe_$pid"]];
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "⚙️ Variantlar:", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    }

    // Delete items - also delete local images
    elseif (str_starts_with($data, 'adm_del_cat_')) {
        $id = substr($data, 12);
        $row = $pdo->query("SELECT image_path FROM categories WHERE id=$id")->fetch();
        if ($row) delete_image($row['image_path']);
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]);
    }
    elseif (str_starts_with($data, 'adm_del_pst_')) {
        $id = substr($data, 12);
        $row = $pdo->query("SELECT image_path FROM posters WHERE id=$id")->fetch();
        if ($row) delete_image($row['image_path']);
        $pdo->prepare("DELETE FROM posters WHERE id=?")->execute([$id]);
        sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]);
    }
    elseif (str_starts_with($data, 'adm_del_prd_')) {
        $id = substr($data, 12);
        // Delete product images
        $prow = $pdo->query("SELECT image_path FROM products WHERE id=$id")->fetch();
        if ($prow) delete_image($prow['image_path']);
        $vrows = $pdo->query("SELECT image_path FROM product_variants WHERE product_id=$id")->fetchAll();
        foreach ($vrows as $vr) delete_image($vr['image_path']);
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_variants WHERE product_id=?")->execute([$id]);
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "O'chirildi!"]);
        sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]);
    }

    // Admin management
    elseif (str_starts_with($data, 'adm_rm_')) {
        $rm_id = substr($data, 7);
        if ($rm_id == $chat_id) {
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "O'zingizni o'chira olmaysiz!", 'show_alert' => true]);
        } else {
            $pdo->prepare("DELETE FROM admins WHERE chat_id=?")->execute([$rm_id]);
            sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]);
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "Admin o'chirildi."]);
        }
    }

    // Order handling - with user notification
    elseif (str_starts_with($data, 'adm_ok_')) {
        $id = substr($data, 7);
        $order = $pdo->query("SELECT * FROM orders WHERE id=$id")->fetch();
        if ($order['status'] !== 'pending') {
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "⚠️ Bu buyurtma allaqochon " . $order['status'] . "!", 'show_alert' => true]);
        } else {
            $pdo->prepare("UPDATE orders SET status='accepted' WHERE id=?")->execute([$id]);
            sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "✅ #{$id} tasdiqlandi."]);
            // Notify user
            if ($order['telegram_id']) {
                sendTelegram('sendMessage', [
                    'chat_id' => $order['telegram_id'],
                    'text' => "✅ <b>Buyurtmangiz tasdiqlandi!</b>\n\n📦 #$id - " . number_format($order['total_price'], 0, ',', ' ') . " so'm\n\nTez orada yetkazib beriladi!",
                    'parse_mode' => 'HTML'
                ]);
            }
        }
    }
    elseif (str_starts_with($data, 'adm_no_')) {
        $id = substr($data, 7);
        $order = $pdo->query("SELECT * FROM orders WHERE id=$id")->fetch();
        if ($order['status'] !== 'pending') {
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "⚠️ Allaqochon " . $order['status'] . "!", 'show_alert' => true]);
        } else {
            $pdo->prepare("UPDATE orders SET status='rejected' WHERE id=?")->execute([$id]);
            sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "❌ #{$id} bekor qilindi."]);
            // Notify user
            if ($order['telegram_id']) {
                sendTelegram('sendMessage', [
                    'chat_id' => $order['telegram_id'],
                    'text' => "❌ <b>Buyurtmangiz bekor qilindi.</b>\n\n📦 #$id\n\nQo'shimcha ma'lumot uchun operatorlarimizga murojaat qiling.",
                    'parse_mode' => 'HTML'
                ]);
            }
        }
    }

    // Search pagination
    elseif (str_starts_with($data, 'search_page_')) {
        $page_n = (int)substr($data, 12);
        $stmt = $pdo->prepare("SELECT state, temp_data FROM bot_state WHERE chat_id=?"); $stmt->execute([$chat_id]);
        $row = $stmt->fetch(); $temp = json_decode($row['temp_data'], true);
        $q = $temp['q'] ?? '';
        $limit = 8; $offset = $page_n * $limit;
        $items = $pdo->query("SELECT id, name FROM products WHERE name LIKE '%$q%' ORDER BY name ASC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM products WHERE name LIKE '%$q%'")->fetchColumn();
        $kb = [];
        foreach ($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => "pe_" . $i['id']]];
        $nav = [];
        if ($page_n > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "search_page_" . ($page_n - 1)];
        if ($total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "search_page_" . ($page_n + 1)];
        if ($nav) $kb[] = $nav;
        setState($chat_id, 'SEARCH_PROD_PAGE', array_merge($temp, ['page' => $page_n]));
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "🔍 '$q' natijalari (Sahifa " . ($page_n + 1) . "):", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    }
}

function showProductEditMenu($chat_id, $id, $mid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT has_discount FROM products WHERE id=?"); $stmt->execute([$id]);
    $has_disc = $stmt->fetchColumn();
    $kb = [
        [['text' => '📝 Nom', 'callback_data' => "pe_name_$id"], ['text' => '💰 Narx', 'callback_data' => "pe_price_$id"]],
        [['text' => '📄 Tavsif', 'callback_data' => "pe_desc_$id"]],
        [$has_disc ? ['text' => '🛑 Chegirmani to\'xtatish', 'callback_data' => "pe_disc_stop_$id"] : ['text' => '🔥 Chegirma', 'callback_data' => "pe_disc_start_$id"]],
        [['text' => '➕ Variant qo\'shish', 'callback_data' => "ve_add_$id"]],
        [['text' => '⚙️ Variantlar', 'callback_data' => "ve_list_$id"]],
        [['text' => '🗑 O\'chirish', 'callback_data' => "adm_del_prd_$id"]],
        [['text' => '🔙 Orqaga', 'callback_data' => 'adm_edit_list']]
    ];
    sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "📦 <b>Mahsulot #$id</b>" . ($has_disc ? "\n🔥 Hozir chegirmada!" : ""), 'parse_mode' => 'HTML', 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
}

function showAdminMenu($chat_id) {
    $kb = ['keyboard' => [
        [['text' => '➕ Admin qo\'shish'], ['text' => '➖ Admin o\'chirish']],
        [['text' => '📋 Adminlar ro\'yxati'], ['text' => '🏠 Menyuga']]
    ], 'resize_keyboard' => true];
    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "👥 <b>Adminlar</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode($kb)]);
}

function listAdmins($chat_id, $for_delete = false) {
    global $pdo;
    $admins = $pdo->query("SELECT * FROM admins")->fetchAll();
    if ($for_delete) {
        $kb = [];
        foreach ($admins as $a) $kb[] = [['text' => "❌ " . $a['name'] . " (" . $a['chat_id'] . ")", 'callback_data' => "adm_rm_" . $a['chat_id']]];
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "O'chirish:", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    } else {
        $txt = "📋 <b>Adminlar:</b>\n\n";
        foreach ($admins as $a) $txt .= "👤 <b>" . $a['name'] . "</b> [" . $a['chat_id'] . "]\n";
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'HTML']);
    }
}

function showCategorySelector($chat_id, $page = 0, $mid = null) {
    global $pdo; $limit = 8; $offset = $page * $limit;
    $cats = $pdo->query("SELECT id, name FROM categories LIMIT $limit OFFSET $offset")->fetchAll();
    $total = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $kb = [];
    foreach ($cats as $c) $kb[] = [['text' => $c['name'], 'callback_data' => "cat_id_" . $c['id']]];
    $nav = [];
    if ($page > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "cat_sel_" . ($page - 1)];
    if ($total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "cat_sel_" . ($page + 1)];
    if ($nav) $kb[] = $nav;
    $msg = "📁 Kategoriya tanlang (Sahifa " . ($page + 1) . "):";
    if ($mid) sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    else sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
}

function listAdminItems($chat_id, $type, $page = 0, $mid = null) {
    global $pdo; $limit = 10; $offset = $page * $limit; $kb = []; $msg = "";
    if ($type === 'prod' || $type === 'edit') {
        $items = $pdo->query("SELECT id, name FROM products ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $msg = "👕 Mahsulotlar (Sahifa " . ($page + 1) . "):";
        foreach ($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => ($type === 'edit' ? "pe_" : "adm_del_prd_") . $i['id']]];
    } elseif ($type === 'cat') {
        $items = $pdo->query("SELECT id, name FROM categories ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $msg = "📂 Kategoriyalar:";
        foreach ($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => "adm_del_cat_" . $i['id']]];
    } elseif ($type === 'poster') {
        $items = $pdo->query("SELECT id FROM posters ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM posters")->fetchColumn();
        $msg = "🖼 Posterlar:";
        foreach ($items as $i) $kb[] = [['text' => "Poster #" . $i['id'], 'callback_data' => "adm_del_pst_" . $i['id']]];
    }
    $nav = [];
    if ($page > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "list_del_{$type}_" . ($page - 1)];
    if (isset($total) && $total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "list_del_{$type}_" . ($page + 1)];
    if ($nav) $kb[] = $nav;
    if ($mid) sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    else sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
}

function listActiveOrders($chat_id) {
    global $pdo;
    $orders = $pdo->query("SELECT * FROM orders WHERE status='pending' ORDER BY id DESC LIMIT 10")->fetchAll();
    if (!$orders) { sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📭 Yangi buyurtmalar yo'q."]); return; }
    foreach ($orders as $o) {
        $items = $pdo->query("SELECT oi.*, p.name as pname, pv.variant_name FROM order_items oi JOIN products p ON oi.product_id=p.id LEFT JOIN product_variants pv ON oi.variant_id=pv.id WHERE oi.order_id=" . $o['id'])->fetchAll();
        $item_list = "";
        foreach ($items as $idx => $item) {
            $item_list .= ($idx + 1) . ". " . $item['pname'] . ($item['variant_name'] ? " [{$item['variant_name']}]" : "") . " × " . $item['quantity'] . "\n";
        }
        $txt = "🆕 <b>#{$o['id']} | {$o['customer_name']}</b>\n📞 {$o['customer_phone']}\n📍 {$o['address']}\n\n🛒 <b>Mahsulotlar:</b>\n$item_list\n💰 Jami: <b>" . number_format($o['total_price']) . " so'm</b>";
        if ($o['telegram_id']) $txt .= "\n🔗 TG: <code>" . $o['telegram_id'] . "</code>";
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'HTML', 'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '✅ Qabul', 'callback_data' => "adm_ok_{$o['id']}"], ['text' => '❌ Bekor', 'callback_data' => "adm_no_{$o['id']}"]]]])]);
    }
}

function showStatistics($chat_id) {
    global $pdo;
    $daily = $pdo->query("SELECT SUM(total_price) as s, COUNT(*) as c FROM orders WHERE date(created_at)=date('now') AND status!='cancelled'")->fetch();
    $weekly = $pdo->query("SELECT SUM(total_price) as s, COUNT(*) as c FROM orders WHERE created_at>=date('now','-7 days') AND status!='cancelled'")->fetch();
    $monthly = $pdo->query("SELECT SUM(total_price) as s, COUNT(*) as c FROM orders WHERE created_at>=date('now','start of month') AND status!='cancelled'")->fetch();
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $txt = "📊 <b>STATISTIKA</b>\n\n";
    $txt .= "📅 Bugun: <b>" . number_format($daily['s'] ?? 0, 0, ',', ' ') . " so'm</b> ({$daily['c']} ta)\n";
    $txt .= "🗓 Hafta: <b>" . number_format($weekly['s'] ?? 0, 0, ',', ' ') . " so'm</b> ({$weekly['c']} ta)\n";
    $txt .= "📆 Oy: <b>" . number_format($monthly['s'] ?? 0, 0, ',', ' ') . " so'm</b> ({$monthly['c']} ta)\n\n";
    $txt .= "👥 Foydalanuvchilar: <b>$users</b>\n🛍 Mahsulotlar: <b>$products</b>";
    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'HTML']);
}

function setState($chat_id, $state, $data = []) {
    global $pdo;
    $pdo->prepare("REPLACE INTO bot_state (chat_id, state, temp_data, updated_at) VALUES (?,?,?,datetime('now'))")->execute([$chat_id, $state, json_encode($data)]);
}
?>

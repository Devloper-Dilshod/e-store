<?php
// Function to handle bot updates with Full Admin Control (Edit/Add/Del + Pagination)
function handleUpdate($update) {
    global $pdo, $admin_chat_id;

    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $photo = $update['message']['photo'] ?? null;

        if (!isUserAdmin($chat_id)) {
            sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "⛔ Kirish taqiqlangan."]);
            return;
        }

        $stmt = $pdo->prepare("SELECT state, temp_data FROM bot_state WHERE chat_id = ?");
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
            
            // --- ADMIN MANAGMENT ---
            case 'ADD_ADMIN_ID':
                $new_id = null; $new_name = "Admin";
                
                if (isset($update['message']['forward_from'])) {
                    $new_id = $update['message']['forward_from']['id'];
                    $new_name = $update['message']['forward_from']['first_name'];
                } elseif (isset($update['message']['contact'])) {
                    $new_id = $update['message']['contact']['user_id'];
                    $new_name = $update['message']['contact']['first_name'];
                } elseif (is_numeric($text)) {
                    $new_id = $text;
                }
                
                if ($new_id) {
                    // Check if exists
                    if (isUserAdmin($new_id)) {
                        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "⚠️ Bu foydalanuvchi allaqochon admin."]);
                    } else {
                        $pdo->prepare("INSERT INTO admins (chat_id, name) VALUES (?, ?)")->execute([$new_id, $new_name]);
                        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Yangi admin qo'shildi: $new_name ($new_id)"]);
                    }
                    setState($chat_id, 'MAIN_MENU'); showAdminMenu($chat_id);
                } else {
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ ID aniqlanmadi. Qayta urinib ko'ring yoki '🔙 Orqaga' ni bosing."]);
                }
                break;

            // --- ADD CATEGORY ---
            case 'ADD_CAT_NAME':
                $temp_data['name'] = $text;
                setState($chat_id, 'ADD_CAT_IMG', $temp_data);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📸 Rasm yuboring (yoki Skip degan xabar yozing):", 'reply_markup' => json_encode(['keyboard' => [[['text' => '⏭ Skip']], [['text' => '🏠 Menyuga']]], 'resize_keyboard' => true])]);
                break;
            case 'ADD_CAT_IMG':
                $file_id = $photo ? end($photo)['file_id'] : null;
                $pdo->prepare("INSERT INTO categories (name, file_id) VALUES (?, ?)")->execute([$temp_data['name'], $file_id]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Kategoriya saqlandi."]);
                break;

            // --- ADD PRODUCT ---
            case 'ADD_PROD_NAME':
                $temp_data['name'] = $text;
                setState($chat_id, 'ADD_PROD_PRICE', $temp_data);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "💰 Asosiy narx:"]);
                break;
            case 'ADD_PROD_PRICE':
                if(is_numeric($text)) {
                    $temp_data['price'] = $text;
                    setState($chat_id, 'ADD_PROD_CAT', $temp_data);
                    showCategorySelector($chat_id, 0);
                }
                break;
            case 'ADD_PROD_CAT':
                // Handled via callback mostly, but if they type...
                break;
            case 'ADD_PROD_IMG': if($photo) { $temp_data['file_id'] = end($photo)['file_id']; setState($chat_id, 'ADD_PROD_DESC', $temp_data); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📝 Tavsif:"]); } break;
            case 'ADD_PROD_DESC':
                $temp_data['desc'] = $text;
                $pdo->prepare("INSERT INTO products (name, category_id, description, base_price, file_id) VALUES (?,?,?,?,?)")->execute([$temp_data['name'], $temp_data['cat_id'], $temp_data['desc'], $temp_data['price'], $temp_data['file_id']]);
                $temp_data['prod_id'] = $pdo->lastInsertId();
                setState($chat_id, 'ASK_VARIANT', $temp_data);
                $kb = ['keyboard' => [[['text' => '➕ Variant qo\'shish'], ['text' => '✅ Tugatish']]], 'resize_keyboard' => true];
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Mahsulot saqlandi. Variant bormi?", 'reply_markup' => json_encode($kb)]);
                break;

            case 'ASK_VARIANT':
                if($text === '➕ Variant qo\'shish') { 
                    setState($chat_id, 'ADD_VAR_NAME', $temp_data); 
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "1️⃣ Birinchi variant nomini kiriting (masalan: Rang: Qizil yoki O'lcham: XL):", 'reply_markup' => json_encode(['remove_keyboard' => true])]); 
                }
                else { 
                    setState($chat_id, 'MAIN_MENU'); 
                    showMainMenu($chat_id); 
                }
                break;
            case 'ADD_VAR_NAME': $temp_data['var_name'] = $text; setState($chat_id, 'ADD_VAR_PRICE', $temp_data); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Narxi:"]); break;
            case 'ADD_VAR_PRICE': if(is_numeric($text)) { $temp_data['var_price'] = $text; setState($chat_id, 'ADD_VAR_IMG', $temp_data); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Rasmi (Skip uchun xabar yozing):"]); } break;
            case 'ADD_VAR_IMG':
                $v_file_id = $photo ? end($photo)['file_id'] : null;
                $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, price, file_id) VALUES (?,?,?,?)")->execute([$temp_data['prod_id'], $temp_data['var_name'], $temp_data['var_price'], $v_file_id]);
                setState($chat_id, 'ASK_VARIANT', $temp_data);
                $kb = ['keyboard' => [[['text' => '➕ Variant qo\'shish'], ['text' => '✅ Tugatish']]], 'resize_keyboard' => true];
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Variant qo'shildi. Yana bormi?", 'reply_markup' => json_encode($kb)]);
                break;

            // --- ADD POSTER ---
            case 'ADD_POSTER_IMG':
                if ($photo) {
                    $temp_data['file_id'] = end($photo)['file_id'];
                    setState($chat_id, 'ADD_POSTER_LINK', $temp_data);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🔗 Poster uchun link yuboring (yoki # yozing):"]);
                }
                break;
            case 'ADD_POSTER_LINK':
                $pdo->prepare("INSERT INTO posters (file_id, link) VALUES (?, ?)")->execute([$temp_data['file_id'], $text]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Poster qo'shildi!"]);
                break;

            // --- EDIT STATES ---
            case 'WAIT_NEW_VAR_PRICE':
                if(is_numeric($text)) {
                    $pdo->prepare("UPDATE product_variants SET price = ? WHERE id = ?")->execute([$text, $temp_data['var_id']]);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Narx yangilandi."]);
                    setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                }
                break;

            case 'EDIT_PROD_NAME':
                $pdo->prepare("UPDATE products SET name = ? WHERE id = ?")->execute([$text, $temp_data['id']]);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Mahsulot nomi o'zgartirildi."]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                break;

            case 'EDIT_PROD_PRICE':
                if(is_numeric($text)) {
                    $pdo->prepare("UPDATE products SET base_price = ? WHERE id = ?")->execute([$text, $temp_data['id']]);
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Mahsulot narxi o'zgartirildi."]);
                    setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                }
                break;

            case 'EDIT_PROD_DESC':
                $pdo->prepare("UPDATE products SET description = ? WHERE id = ?")->execute([$text, $temp_data['id']]);
                sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "✅ Mahsulot tavsifi o'zgartirildi."]);
                setState($chat_id, 'MAIN_MENU'); showMainMenu($chat_id);
                break;

            case 'SEARCH_PROD':
                $stmt = $pdo->prepare("SELECT id, name FROM products WHERE name LIKE ? LIMIT 10");
                $stmt->execute(["%$text%"]);
                $items = $stmt->fetchAll();
                if(!$items) {
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "❌ Hech narsa topilmadi. Qayta urinib ko'ring:"]);
                } else {
                    $kb = [];
                    foreach($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => "pe_" . $i['id']]];
                    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🔍 Topilgan mahsulotlar:", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
                    setState($chat_id, 'MAIN_MENU');
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
        case '➕ Admin qo\'shish': 
            setState($chat_id, 'ADD_ADMIN_ID'); 
            sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🆔 Yangi adminning Telegram ID sini yuboring yoki uning xabarini shu yerga forward qiling:", 'reply_markup' => json_encode(['keyboard' => [[['text' => '🔙 Orqaga']]], 'resize_keyboard' => true])]); 
            break;
        case '➖ Admin o\'chirish': listAdmins($chat_id, true); break;
        case '📋 Adminlar ro\'yxati': listAdmins($chat_id, false); break;
        case '🔍 Qidiruv': setState($chat_id, 'SEARCH_PROD'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🔍 Qidirish uchun mahsulot nomini kiriting:"]); break;
        case '🖼 Poster qo\'shish': setState($chat_id, 'ADD_POSTER_IMG'); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📸 Rasm yuboring:"]); break;
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
    $kb = ['keyboard' => [
        [['text' => '👕 Mahsulotlar'], ['text' => '📦 Buyurtmalar']], 
        [['text' => '➕ Mahsulot qo\'shish'], ['text' => '📂 Kategoriya qo\'shish']], 
        [['text' => '📝 Tahrirlash'], ['text' => '🔍 Qidiruv']], 
        [['text' => '📊 Statistika'], ['text' => '🖼 Poster qo\'shish']],
        [['text' => '👥 Adminlar'], ['text' => '🗑 O\'chirish']]
    ], 'resize_keyboard' => true];
    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "🛠 <b>ADMIN PANEL</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode($kb)]);
}

function handleCallback($cb) {
    global $pdo, $admin_chat_id; $data = $cb['data']; $chat_id = $cb['message']['chat']['id']; $mid = $cb['message']['message_id'];
    
    // --- CATEGORY SELECTOR PAGINATION ---
    if(strpos($data, 'cat_sel_') === 0) {
        $parts = explode('_', $data);
        $page = (int)$parts[2];
        showCategorySelector($chat_id, $page, $mid);
    } elseif(strpos($data, 'cat_id_') === 0) {
        $cid = substr($data, 7);
        $stmt = $pdo->prepare("SELECT state, temp_data FROM bot_state WHERE chat_id = ?"); $stmt->execute([$chat_id]);
        $row = $stmt->fetch(); $temp = json_decode($row['temp_data'], true);
        $temp['cat_id'] = $cid;
        setState($chat_id, 'ADD_PROD_IMG', $temp);
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "✅ Kategoriya tanlandi. Endi rasm yuboring:"]);
    }
    
    // --- DELETE LISTS PAGINATION ---
    elseif(strpos($data, 'list_del_cat_') === 0) listAdminItems($chat_id, 'cat', substr($data, 13), $mid);
    elseif(strpos($data, 'list_del_prd_') === 0) listAdminItems($chat_id, 'prod', substr($data, 13), $mid);
    elseif(strpos($data, 'list_del_pst_') === 0) listAdminItems($chat_id, 'poster', substr($data, 13), $mid);

    // --- EDIT FLOW ---
    elseif($data === 'adm_edit_list') listAdminItems($chat_id, 'edit', 0, $mid);
    elseif(strpos($data, 'pe_') === 0) {
        $id = substr($data, 3);
        $kb = [
            [['text' => '📝 Nomni o\'zgartirish', 'callback_data' => "pe_name_$id"], ['text' => '💰 Narxni o\'zgartirish', 'callback_data' => "pe_price_$id"]],
            [['text' => '📄 Tavsifni o\'zgartirish', 'callback_data' => "pe_desc_$id"]],
            [['text' => '➕ Variant qo\'shish', 'callback_data' => "ve_add_$id"]],
            [['text' => '⚙️ Variantlarni sozlash', 'callback_data' => "ve_list_$id"]],
            [['text' => '🗑 Mahsulotni o\'chirish', 'callback_data' => "adm_del_prd_$id"]],
            [['text' => '🔙 Orqaga', 'callback_data' => 'adm_edit_list']]
        ];
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "📦 <b>Mahsulot #$id sozlamalari:</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    }
    
    // Edit Product Properties
    elseif(strpos($data, 'pe_name_') === 0) { $id = substr($data, 8); setState($chat_id, 'EDIT_PROD_NAME', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi nomni kiriting:"]); }
    elseif(strpos($data, 'pe_price_') === 0) { $id = substr($data, 9); setState($chat_id, 'EDIT_PROD_PRICE', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi narxni kiriting:"]); }
    elseif(strpos($data, 'pe_desc_') === 0) { $id = substr($data, 8); setState($chat_id, 'EDIT_PROD_DESC', ['id' => $id]); sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi tavsifni kiriting:"]); }

    // Variant Management
    elseif(strpos($data, 've_add_') === 0) {
        $id = substr($data, 7);
        setState($chat_id, 'ADD_VAR_NAME', ['prod_id' => $id]);
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "Yangi variant nomi (masalan: XL, Qizil...):"]);
    }
    elseif(strpos($data, 've_list_') === 0) {
        $id = substr($data, 8);
        $vars = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
        $vars->execute([$id]);
        $rows = $vars->fetchAll();
        $kb = [];
        foreach($rows as $v) {
            $kb[] = [['text' => "❌ " . $v['variant_name'] . " (" . number_format($v['price']) . ")", 'callback_data' => "vd_" . $v['id']]];
        }
        $kb[] = [['text' => '🔙 Orqaga', 'callback_data' => "pe_$id"]];
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "⚙️ <b>Variantlarni o'chirish uchun ustiga bosing:</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    }
    elseif(strpos($data, 'vd_') === 0) {
        $vid = substr($data, 3);
        $stmt = $pdo->prepare("SELECT product_id FROM product_variants WHERE id = ?"); $stmt->execute([$vid]);
        $pid = $stmt->fetchColumn();
        $pdo->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$vid]);
        sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "Variant o'chirildi!"]);
        // Refresh list
        $vars = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?"); $vars->execute([$pid]); $rows = $vars->fetchAll(); $kb = [];
        foreach($rows as $v) $kb[] = [['text' => "❌ " . $v['variant_name'] . " (" . number_format($v['price']) . ")", 'callback_data' => "vd_" . $v['id']]];
        $kb[] = [['text' => '🔙 Orqaga', 'callback_data' => "pe_$pid"]];
        sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "⚙️ <b>Variantlarni o'chirish uchun ustiga bosing:</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    }

    // Delete Categories/Posters
    elseif(strpos($data, 'adm_del_cat_') === 0) { $id = substr($data, 12); $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]); sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]); }
    elseif(strpos($data, 'adm_del_pst_') === 0) { $id = substr($data, 12); $pdo->prepare("DELETE FROM posters WHERE id = ?")->execute([$id]); sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]); }
    
    // --- ADMIN MANAGEMENT ---
    elseif(strpos($data, 'adm_rm_') === 0) {
        $rm_id = substr($data, 7);
        if ($rm_id == $chat_id) {
             sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "O'zingizni o'chira olmaysiz!", 'show_alert' => true]);
        } else {
            $pdo->prepare("DELETE FROM admins WHERE chat_id = ?")->execute([$rm_id]);
            sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]);
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "Admin o'chirildi."]);
        }
    }

    // --- ORDER HANDLING (SAFE) ---
    elseif(strpos($data, 'adm_ok_') === 0) { 
        $id = substr($data, 7);
        $order = $pdo->query("SELECT status FROM orders WHERE id = $id")->fetch();
        if ($order['status'] !== 'pending') {
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "⚠️ Bu buyurtma allaqochon " . ($order['status'] == 'accepted' ? "tasdiqlangan" : "bekor qilingan") . "!", 'show_alert' => true]);
            // Update message to reflect status if possible (optional simplification)
            sendTelegram('editMessageReplyMarkup', ['chat_id'=>$chat_id, 'message_id'=>$mid, 'reply_markup'=>json_encode(['inline_keyboard'=>[]])]); 
        } else {
            $pdo->prepare("UPDATE orders SET status = 'accepted' WHERE id = ?")->execute([$id]); 
            sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "✅ #$id ta'sdiqlandi."]); 
        }
    }
    elseif(strpos($data, 'adm_no_') === 0) { 
        $id = substr($data, 7); 
        $order = $pdo->query("SELECT status FROM orders WHERE id = $id")->fetch();
        if ($order['status'] !== 'pending') {
            sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "⚠️ Bu buyurtma allaqochon " . ($order['status'] == 'accepted' ? "tasdiqlangan" : "bekor qilingan") . "!", 'show_alert' => true]);
             sendTelegram('editMessageReplyMarkup', ['chat_id'=>$chat_id, 'message_id'=>$mid, 'reply_markup'=>json_encode(['inline_keyboard'=>[]])]);
        } else {
            $pdo->prepare("UPDATE orders SET status = 'rejected' WHERE id = ?")->execute([$id]); 
            sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => "❌ #$id bekor qilindi."]); 
        }
    }
    elseif(strpos($data, 'adm_del_prd_') === 0) { $id = substr($data, 12); $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]); sendTelegram('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => "O'chirildi!"]); sendTelegram('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $mid]); }
}

function showAdminMenu($chat_id) {
    $kb = ['keyboard' => [
        [['text' => '➕ Admin qo\'shish'], ['text' => '➖ Admin o\'chirish']],
        [['text' => '📋 Adminlar ro\'yxati'], ['text' => '🏠 Menyuga']]
    ], 'resize_keyboard' => true];
    sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "👥 <b>Adminlar boshqaruvi</b>", 'parse_mode' => 'HTML', 'reply_markup' => json_encode($kb)]);
}

function listAdmins($chat_id, $for_delete = false) {
    global $pdo;
    $admins = $pdo->query("SELECT * FROM admins")->fetchAll();
    
    if ($for_delete) {
        $kb = [];
        foreach($admins as $a) {
            $kb[] = [['text' => "❌ " . $a['name'] . " (" . $a['chat_id'] . ")", 'callback_data' => "adm_rm_" . $a['chat_id']]];
        }
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "O'chirish uchun adminni tanlang:", 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    } else {
        $txt = "📋 <b>Adminlar ro'yxati:</b>\n\n";
        foreach($admins as $a) {
            $txt .= "👤 <b>" . $a['name'] . "</b> [" . $a['chat_id'] . "]\n";
        }
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'HTML']);
    }
}

function showCategorySelector($chat_id, $page = 0, $mid = null) {
    global $pdo; $limit = 8; $offset = $page * $limit;
    $cats = $pdo->query("SELECT id, name FROM categories LIMIT $limit OFFSET $offset")->fetchAll();
    $total = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    $kb = [];
    foreach($cats as $c) $kb[] = [['text' => $c['name'], 'callback_data' => "cat_id_".$c['id']]];
    
    $nav = [];
    if($page > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "cat_sel_".($page-1)];
    if($total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "cat_sel_".($page+1)];
    if($nav) $kb[] = $nav;

    $msg = "📁 Kategoriyani tanlang (Sahifa: ".($page+1)."):";
    if($mid) sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    else sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
}

function listAdminItems($chat_id, $type, $page = 0, $mid = null) {
    global $pdo; $limit = 10; $offset = $page * $limit;
    $kb = []; $msg = "";
    
    if($type === 'prod' || $type === 'edit') {
        $items = $pdo->query("SELECT id, name FROM products ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $msg = "👕 Mahsulotlar ro'yxati:";
        foreach($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => ($type === 'edit' ? "pe_" : "adm_del_prd_").$i['id']]];
    } elseif($type === 'cat') {
        $items = $pdo->query("SELECT id, name FROM categories ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $msg = "📂 Kategoriyalar ro'yxati:";
        foreach($items as $i) $kb[] = [['text' => $i['name'], 'callback_data' => "adm_del_cat_".$i['id']]];
    } elseif($type === 'poster') {
        $items = $pdo->query("SELECT id FROM posters ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM posters")->fetchColumn();
        $msg = "🖼 Posterlar ro'yxati:";
        foreach($items as $i) $kb[] = [['text' => "Poster #".$i['id'], 'callback_data' => "adm_del_pst_".$i['id']]];
    }

    $nav = [];
    if($page > 0) $nav[] = ['text' => '⬅️', 'callback_data' => "list_del_{$type}_".($page-1)];
    if($total > $offset + $limit) $nav[] = ['text' => '➡️', 'callback_data' => "list_del_{$type}_".($page+1)];
    if($nav) $kb[] = $nav;

    if($mid) sendTelegram('editMessageText', ['chat_id' => $chat_id, 'message_id' => $mid, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
    else sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $msg, 'reply_markup' => json_encode(['inline_keyboard' => $kb])]);
}

function listActiveOrders($chat_id) {
    global $pdo;
    $orders = $pdo->query("SELECT * FROM orders WHERE status = 'pending' ORDER BY id DESC LIMIT 10")->fetchAll();
    if(!$orders) { sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => "📭 Yangi buyurtmalar yo'q."]); return; }
    foreach($orders as $o) {
        $items_stmt = $pdo->prepare("SELECT oi.*, p.name as prod_name, pv.variant_name FROM order_items oi JOIN products p ON oi.product_id = p.id LEFT JOIN product_variants pv ON oi.variant_id = pv.id WHERE oi.order_id = ?");
        $items_stmt->execute([$o['id']]);
        $items = $items_stmt->fetchAll();
        
        $item_list = "";
        foreach($items as $idx => $item) {
            $item_list .= ($idx+1) . ". " . $item['prod_name'] . ($item['variant_name'] ? " [" . $item['variant_name'] . "]" : "") . " x " . $item['quantity'] . "\n";
        }

        $txt = "🆕 <b>#{$o['id']} | {$o['customer_name']}</b>\n";
        $txt .= "📞 {$o['customer_phone']}\n";
        $txt .= "📍 {$o['address']}\n\n";
        $txt .= "🛒 <b>Mahsulotlar:</b>\n$item_list\n";
        $txt .= "💰 Jami: <b>".number_format($o['total_price'])." so'm</b>";
        
        sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $txt, 'parse_mode' => 'HTML', 'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '✅', 'callback_data' => "adm_ok_{$o['id']}"], ['text' => '❌', 'callback_data' => "adm_no_{$o['id']}"]]]])]);
    }
}

function showStatistics($chat_id) { 
    global $pdo; 
    
    // Daily Stats
    $daily = $pdo->query("SELECT SUM(total_price) as sum, COUNT(*) as count FROM orders WHERE date(created_at) = date('now') AND status != 'cancelled'")->fetch();
    
    // Weekly Stats
    $weekly = $pdo->query("SELECT SUM(total_price) as sum, COUNT(*) as count FROM orders WHERE created_at >= date('now', '-7 days') AND status != 'cancelled'")->fetch();
    
    // Monthly Stats
    $monthly = $pdo->query("SELECT SUM(total_price) as sum, COUNT(*) as count FROM orders WHERE created_at >= date('now', 'start of month') AND status != 'cancelled'")->fetch();
    
    // Overall Stats
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    $txt = "📊 <b>DO'KON STATISTIKASI</b>\n\n";
    
    $txt .= "📅 <b>Bugun:</b>\n";
    $txt .= "💰 Tushum: <b>" . number_format($daily['sum'] ?? 0, 0, ',', ' ') . " so'm</b>\n";
    $txt .= "📦 Buyurtmalar: <b>" . ($daily['count'] ?? 0) . " ta</b>\n\n";
    
    $txt .= "🗓 <b>Shu hafta:</b>\n";
    $txt .= "💰 Tushum: <b>" . number_format($weekly['sum'] ?? 0, 0, ',', ' ') . " so'm</b>\n";
    $txt .= "📦 Buyurtmalar: <b>" . ($weekly['count'] ?? 0) . " ta</b>\n\n";
    
    $txt .= "🗓 <b>Shu oy:</b>\n";
    $txt .= "💰 Tushum: <b>" . number_format($monthly['sum'] ?? 0, 0, ',', ' ') . " so'm</b>\n";
    $txt .= "📦 Buyurtmalar: <b>" . ($monthly['count'] ?? 0) . " ta</b>\n\n";
    
    $txt .= "👥 Umumiy mijozlar: <b>$users ta</b>";

    sendTelegram('sendMessage', [
        'chat_id' => $chat_id, 
        'text' => $txt, 
        'parse_mode' => 'HTML'
    ]); 
}
function setState($chat_id, $state, $data = []) { global $pdo; $pdo->prepare("REPLACE INTO bot_state (chat_id, state, temp_data) VALUES (?, ?, ?)")->execute([$chat_id, $state, json_encode($data)]); }
?>

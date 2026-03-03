<?php
// Core Security & Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production: xatolarni yashiramiz

// PHP 7.x compatibility - str_starts_with polyfill
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || strpos($haystack, $needle) !== false;
    }
}

// 1. Session Security
if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) !== 'image.php') {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax');
        }
        session_start();
    }
}

// 2. Database
$db_file = __DIR__ . '/../protected/data/database.sqlite';

// 3. Telegram API
$telegram_bot_token = 'BOT_TOKEN'; // O'zingizning bot tokeningizni kiriting
$admin_chat_id = '7445142075';
$store_name = 'G Store';

// 4. Images directory (Telegram o'rniga local saqlash)
$images_dir = __DIR__ . '/../images';
if (!is_dir($images_dir)) mkdir($images_dir, 0777, true);

try {
    $db_dir = dirname($db_file);
    if (!is_dir($db_dir)) mkdir($db_dir, 0777, true);

    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("PRAGMA synchronous = NORMAL;");
    $pdo->exec("PRAGMA busy_timeout = 5000;");
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // ===== USERS (Telegram ID asosida) =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        telegram_id TEXT UNIQUE,
        name TEXT,
        username TEXT,
        phone TEXT,
        is_blocked INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // ===== SETTINGS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )");
    // Default settings
    $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES 
        ('store_name', 'G Store'),
        ('store_description', 'Sifatli mahsulotlar'),
        ('contact_phone', '+998901234567'),
        ('welcome_message', 'Xush kelibsiz!'),
        ('delivery_info', 'Yetkazib berish 1-3 kun ichida'),
        ('currency', 'so''m')
    ");

    // ===== CATEGORIES =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        image_path TEXT
    )");
    // Backward compat: keep file_id column reference
    try { $pdo->exec("ALTER TABLE categories ADD COLUMN image_path TEXT"); } catch (Exception $e) {}

    // ===== PRODUCTS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        name TEXT,
        description TEXT,
        base_price REAL,
        image_path TEXT,
        has_discount INTEGER DEFAULT 0,
        discount_percent INTEGER DEFAULT 0,
        discount_price REAL DEFAULT 0
    )");
    try { $pdo->exec("ALTER TABLE products ADD COLUMN image_path TEXT"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN discount_price REAL DEFAULT 0"); } catch (Exception $e) {}

    // ===== PRODUCT VARIANTS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER,
        variant_name TEXT,
        price REAL,
        image_path TEXT
    )");
    try { $pdo->exec("ALTER TABLE product_variants ADD COLUMN image_path TEXT"); } catch (Exception $e) {}

    // ===== POSTERS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS posters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        image_path TEXT,
        link TEXT
    )");
    try { $pdo->exec("ALTER TABLE posters ADD COLUMN image_path TEXT"); } catch (Exception $e) {}

    // ===== ORDERS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        telegram_id TEXT,
        customer_name TEXT,
        customer_phone TEXT,
        address TEXT,
        total_price REAL,
        status TEXT DEFAULT 'pending',
        note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN telegram_id TEXT"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN note TEXT"); } catch (Exception $e) {}

    // ===== ORDER ITEMS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        variant_id INTEGER,
        quantity INTEGER,
        price REAL
    )");

    // ===== BOT STATE =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_state (
        chat_id TEXT PRIMARY KEY,
        state TEXT,
        temp_data TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    try { $pdo->exec("ALTER TABLE bot_state ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}

    // ===== NOTIFICATIONS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        body TEXT,
        icon TEXT,
        url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // ===== PUSH SUBSCRIPTIONS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        endpoint TEXT UNIQUE,
        p256dh TEXT,
        auth TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // ===== ADMINS =====
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id TEXT UNIQUE,
        name TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Auto-insert primary admin
    $chk = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($chk == 0 && isset($admin_chat_id)) {
        $pdo->prepare("INSERT INTO admins (chat_id, name) VALUES (?, 'Super Admin')")->execute([$admin_chat_id]);
    }

    // Load store_name from settings
    $sn = $pdo->query("SELECT value FROM settings WHERE key='store_name'")->fetchColumn();
    if ($sn) $store_name = $sn;

} catch (Exception $e) {
    if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) !== 'image.php') {
        die("⚠️ Tizimda texnik xatolik (DB): " . $e->getMessage());
    }
    exit;
}

// ===== HELPER FUNCTIONS =====

function clean_input($data) {
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
        http_response_code(403);
        die("🛡️ Xavfsizlik xatoligi (CSRF).");
    }
}

// Rate limiting (simple)
function check_rate_limit($key, $max = 10, $window = 60) {
    $cache_dir = __DIR__ . '/../protected/cache';
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);
    $file = $cache_dir . '/rl_' . md5($key) . '.txt';
    $now = time();
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['count' => 0, 'start' => $now];
    if ($now - $data['start'] > $window) { $data = ['count' => 0, 'start' => $now]; }
    $data['count']++;
    file_put_contents($file, json_encode($data));
    return $data['count'] <= $max;
}

// ===== IMAGE HELPERS =====

function save_image_from_upload($file) {
    global $images_dir;
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
    
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 10 * 1024 * 1024) return null; // 10MB limit
    
    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg'
    };
    
    $filename = uniqid('img_', true) . '.' . $ext;
    $dest = $images_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'images/' . $filename;
    }
    return null;
}

function save_image_from_telegram($file_id) {
    global $telegram_bot_token, $images_dir;
    if (!$file_id) return null;
    
    // Check if already downloaded
    $cache_dir = __DIR__ . '/../protected/cache';
    $cache_file = $cache_dir . '/tg_' . md5($file_id) . '.txt';
    if (file_exists($cache_file)) {
        $path = trim(file_get_contents($cache_file));
        if (file_exists(__DIR__ . '/../' . $path)) return $path;
    }
    
    // Get file path from Telegram
    $apiUrl = "https://api.telegram.org/bot$telegram_bot_token/getFile?file_id=$file_id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    
    $json = $res ? json_decode($res, true) : null;
    if (!$json || !$json['ok']) return null;
    
    $file_path = $json['result']['file_path'];
    $url = "https://api.telegram.org/file/bot$telegram_bot_token/$file_path";
    
    // Download image
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $img_data = curl_exec($ch);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    if (!$img_data) return null;
    
    $ext = 'jpg';
    if (str_contains($content_type, 'png')) $ext = 'png';
    elseif (str_contains($content_type, 'webp')) $ext = 'webp';
    
    $filename = 'img_' . md5($file_id) . '.' . $ext;
    $dest = $images_dir . '/' . $filename;
    
    if (file_put_contents($dest, $img_data)) {
        $path = 'images/' . $filename;
        if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);
        file_put_contents($cache_file, $path);
        return $path;
    }
    return null;
}

function get_image_url($image_path, $fallback = 'assets/images/placeholder.png') {
    if (!$image_path) return $fallback;
    // Legacy: if it looks like a telegram file_id (not a path)
    if (!str_contains($image_path, '/') && strlen($image_path) > 20) {
        // Old telegram file_id - try to serve via image.php
        return 'image.php?id=' . urlencode($image_path);
    }
    return htmlspecialchars($image_path);
}

function delete_image($image_path) {
    if (!$image_path || !str_starts_with($image_path, 'images/')) return;
    $full_path = __DIR__ . '/../' . $image_path;
    if (file_exists($full_path)) unlink($full_path);
}

// ===== TELEGRAM HELPERS =====

function sendTelegram($method, $data) {
    global $telegram_bot_token;
    $url = "https://api.telegram.org/bot$telegram_bot_token/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function getTelegramFileUrl($file_id) {
    global $telegram_bot_token;
    $cache_dir = __DIR__ . "/../protected/cache";
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);
    
    $cache_file = "$cache_dir/$file_id.txt";
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 86400)) {
        return file_get_contents($cache_file);
    }

    $apiUrl = "https://api.telegram.org/bot$telegram_bot_token/getFile?file_id=$file_id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    $res = curl_exec($ch);
    curl_close($ch);

    $json = $res ? json_decode($res, true) : null;
    if ($json && $json['ok']) {
        $path = $json['result']['file_path'];
        $url = "https://api.telegram.org/file/bot$telegram_bot_token/$path";
        file_put_contents($cache_file, $url);
        return $url;
    }
    return 'assets/images/placeholder.png';
}

// Verify Telegram WebApp init data
function verifyTelegramWebAppData($init_data) {
    global $telegram_bot_token;
    
    $data_check_string = '';
    $hash = '';
    $params = [];
    
    parse_str($init_data, $params);
    $hash = $params['hash'] ?? '';
    unset($params['hash']);
    
    ksort($params);
    $data_check_arr = [];
    foreach ($params as $key => $val) {
        $data_check_arr[] = "$key=$val";
    }
    $data_check_string = implode("\n", $data_check_arr);
    
    $secret_key = hash_hmac('sha256', $telegram_bot_token, 'WebAppData', true);
    $computed_hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));
    
    if (!hash_equals($computed_hash, $hash)) return false;
    
    // Check if data is not too old (1 hour)
    if (isset($params['auth_date']) && time() - (int)$params['auth_date'] > 3600) return false;
    
    return $params;
}

// Auto-login with Telegram WebApp
function telegramWebAppAutoLogin() {
    global $pdo;
    
    // Already logged in
    if (isset($_SESSION['user_id'])) return true;
    
    // Check POST data (from web app)
    $init_data = $_POST['tg_init_data'] ?? $_COOKIE['tg_init_data'] ?? '';
    if (!$init_data) return false;
    
    $tg_data = verifyTelegramWebAppData($init_data);
    if (!$tg_data) return false;
    
    $user_data = json_decode($tg_data['user'] ?? '{}', true);
    if (!$user_data || !isset($user_data['id'])) return false;
    
    $tg_id = (string)$user_data['id'];
    $name = ($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '');
    $name = trim($name) ?: 'Telegram User';
    $username = $user_data['username'] ?? '';
    
    // Check if blocked
    $check = $pdo->prepare("SELECT id, is_blocked FROM users WHERE telegram_id = ?");
    $check->execute([$tg_id]);
    $user = $check->fetch();
    
    if ($user && $user['is_blocked']) {
        return false; // Blocked user
    }
    
    if ($user) {
        // Update info
        $pdo->prepare("UPDATE users SET name=?, username=? WHERE telegram_id=?")->execute([$name, $username, $tg_id]);
        $uid = $user['id'];
    } else {
        // Register
        $pdo->prepare("INSERT INTO users (telegram_id, name, username) VALUES (?,?,?)")->execute([$tg_id, $name, $username]);
        $uid = $pdo->lastInsertId();
    }
    
    $_SESSION['user_id'] = $uid;
    $_SESSION['telegram_id'] = $tg_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['is_admin'] = isUserAdmin($tg_id);
    
    return true;
}

// ===== ADMIN HELPERS =====

function isUserAdmin($chat_id) {
    global $pdo, $admin_chat_id;
    if ((string)$chat_id === (string)$admin_chat_id) return true;
    $stmt = $pdo->prepare("SELECT 1 FROM admins WHERE chat_id = ?");
    $stmt->execute([(string)$chat_id]);
    return (bool)$stmt->fetchColumn();
}

function getAllAdminIds() {
    global $pdo;
    $stmt = $pdo->query("SELECT chat_id FROM admins");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getSetting($key, $default = '') {
    global $pdo;
    $val = $pdo->prepare("SELECT value FROM settings WHERE key=?");
    $val->execute([$key]);
    $res = $val->fetchColumn();
    return $res !== false ? $res : $default;
}

function setSetting($key, $value) {
    global $pdo;
    $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?,?)")->execute([$key, $value]);
}

// ===== NOTIFICATION HELPERS =====

function broadcastToAll($text, $image_path = null, $url = null) {
    global $pdo;

    $stmt = $pdo->prepare("INSERT INTO notifications (title, body, icon, url) VALUES (?, ?, ?, ?)");
    $title = str_contains($text, 'CHEGIRMA') ? '🔥 KATTA CHEGIRMA!' : '🆕 YANGI MAHSULOT!';
    
    $clean_body = strip_tags(str_replace(['<br>', '<br/>', "\n"], ' ', $text));
    $clean_body = str_replace('🔗 Batafsil ko\'rish', '', $clean_body);
    
    $stmt->execute([
        $title,
        mb_substr(trim($clean_body), 0, 150),
        $image_path ?: 'assets/images/logo.png',
        $url
    ]);
}
?>

<?php
// Core Security & Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Session Security (Skip for CLI or image proxy to prevent blocking)
if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) !== 'image.php') {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
        }
        session_start();
    }
}

// 2. Database (Hidden in protected path)
$db_file = __DIR__ . '/../protected/data/database.sqlite';

// 3. Telegram API
$telegram_bot_token = 'YOUR_TELEGRAM_BOT_TOKEN';
$admin_chat_id = 'ADMIN_CHAT_ID'; 

try {
    $db_dir = dirname($db_file);
    if (!is_dir($db_dir)) mkdir($db_dir, 0777, true);

    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // CRITICAL: High concurrency for SQLite
    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("PRAGMA synchronous = NORMAL;");
    $pdo->exec("PRAGMA busy_timeout = 5000;");

    // AUTO-SETUP: Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        phone TEXT UNIQUE,
        password TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        file_id TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        name TEXT,
        description TEXT,
        base_price REAL,
        file_id TEXT,
        has_discount INTEGER DEFAULT 0,
        discount_percent INTEGER DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER,
        variant_name TEXT,
        price REAL,
        file_id TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS posters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        file_id TEXT,
        link TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        customer_name TEXT,
        customer_phone TEXT,
        address TEXT,
        total_price REAL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        variant_id INTEGER,
        quantity INTEGER,
        price REAL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_state (
        chat_id TEXT PRIMARY KEY,
        state TEXT,
        temp_data TEXT
    )");

} catch (Exception $e) {
    if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) !== 'image.php') {
        die("⚠️ Tizimda texnik xatolik (DB): " . $e->getMessage());
    }
    exit;
}

// 4. Global Security Functions
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("🛡️ Xavfsizlik xatoligi (CSRF).");
    }
}

// 5. Telegram Helpers
function sendTelegram($method, $data) {
    global $telegram_bot_token;
    $url = "https://api.telegram.org/bot$telegram_bot_token/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
?>

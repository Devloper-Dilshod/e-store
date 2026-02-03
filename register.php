<?php
require_once 'core/config.php';

// If already logged in, redirect to index
if(isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit; 
}

// Process registration
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Validate
    if (empty($name)) {
        $error = "Ismingizni kiriting";
    } elseif (strlen($phone) < 9) {
        $error = "Telefon raqam noto'g'ri (9 ta raqam kiriting)";
    } elseif (empty($pass)) {
        $error = "Parol kiriting";
    } else {
        // Add +998 prefix
        if (substr($phone, 0, 3) !== '998') $phone = '998' . $phone;
        $clean_phone = '+' . $phone;

        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$clean_phone]);
        if($stmt->fetch()) {
            $error = "Bu raqam band.";
        } else {
            // Register & Auto Login
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, phone, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $clean_phone, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            
            // Redirect to index
            header("Location: index.php");
            exit;
        }
    }
}

// Render standalone view (no HTMX)
require_once 'views/register_view_standalone.php';
?>

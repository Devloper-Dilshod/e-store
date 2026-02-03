<?php
require_once 'core/config.php';

// If already logged in, redirect to index
if(isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit; 
}

// Process login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if (empty($phone)) {
        $error = "Telefon raqamingizni kiriting";
    } elseif (strlen($phone) < 9) {
        $error = "Telefon raqam noto'g'ri (9 ta raqam kiriting)";
    } elseif (empty($pass)) {
        $error = "Parol kiriting";
    } else {
        // Add +998 prefix
        if (substr($phone, 0, 3) !== '998') $phone = '998' . $phone;
        $clean_phone = '+' . $phone;

        // Check user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$clean_phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Telefon yoki parol xato.";
        }
    }
}

// Render standalone view (no HTMX)
require_once 'views/login_view_standalone.php';
?>

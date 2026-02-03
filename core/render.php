<?php
/**
 * Renders a view file within the main layout.
 * Supports HTMX for fast content swapping.
 */
function render_page($view_name, $data = []) {
    extract($data);
    
    // CSRF for all views
    $csrf_token = generate_csrf_token();
    
    // Check if request is HTMX (boosted)
    $is_htmx = isset($_SERVER['HTTP_HX_REQUEST']);
    
    ob_start();
    
    if (!$is_htmx) {
        require_once __DIR__ . '/../components/header.php';
    }
    
    // Core view file
    $view_path = __DIR__ . "/../views/$view_name";
    if (file_exists($view_path)) {
        require_once $view_path;
    } else {
        echo "<div class='p-10 text-center text-red-500'>⚠️ View '$view_name' topilmadi.</div>";
    }
    
    if (!$is_htmx) {
        require_once __DIR__ . '/../components/footer.php';
    }
    
    echo ob_get_clean();
}
?>

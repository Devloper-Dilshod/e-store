<?php
function render_page($view_name, $data = []) {
    extract($data);
    $csrf_token = generate_csrf_token();
    $is_htmx = isset($_SERVER['HTTP_HX_REQUEST']);
    ob_start();
    if (!$is_htmx) require_once __DIR__ . '/../components/header.php';
    $view_path = __DIR__ . "/../views/$view_name";
    if (file_exists($view_path)) require $view_path;
    else echo "<div class='p-10 text-center text-red-500'>⚠️ View '$view_name' topilmadi.</div>";
    if (!$is_htmx) require_once __DIR__ . '/../components/footer.php';
    echo ob_get_clean();
}
?>

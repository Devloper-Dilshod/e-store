<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = __DIR__ . '/../views/partials/login_modal_oob.php';

if (file_exists($file)) {
    require $file;
} else {
    http_response_code(500);
    echo "<div id='global-modal-content' hx-swap-oob='innerHTML:#global-modal'><div class='bg-red-500 text-white p-4 fixed top-0 left-0 z-[9999]'>Error: Login modal file not found.</div></div>";
}
?>

<?php
require_once '../core/config.php';
session_destroy();
header("Location: ../index.php");
exit;
?>

<?php
include 'config.php';
print_r($pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC));

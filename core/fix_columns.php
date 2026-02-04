<?php
include 'config.php';
$cols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_COLUMN, 1);
echo "Columns: " . implode(", ", $cols) . "\n";

if (!in_array('discount_percent', $cols)) {
    echo "Adding discount_percent...\n";
    $pdo->exec("ALTER TABLE products ADD COLUMN discount_percent INTEGER DEFAULT 0");
}
if (!in_array('discount_price', $cols)) {
    echo "Adding discount_price...\n";
    $pdo->exec("ALTER TABLE products ADD COLUMN discount_price REAL DEFAULT 0");
}
if (!in_array('has_discount', $cols)) {
    echo "Adding has_discount...\n";
    $pdo->exec("ALTER TABLE products ADD COLUMN has_discount INTEGER DEFAULT 0");
}

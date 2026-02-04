<?php
require_once '../core/config.php';

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id > ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$last_id]);
$notify = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($notify ?: null);

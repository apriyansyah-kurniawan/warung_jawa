<?php
require_once __DIR__ . '/config.php';
$stmt = $pdo->query('SELECT id, username, role FROM users WHERE username = "admin"');
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($user);
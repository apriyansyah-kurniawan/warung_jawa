<?php
require_once 'config.php';
require_once 'includes/auth.php';

mulai_session();

if (!empty($_SESSION['user_id'])) {
    global $pdo;
    catat_aktivitas($pdo, 'Logout dari sistem');
}

session_destroy();
header('Location: login.php');
exit;

<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Force login as admin for testing
session_start();
$_SESSION['user_id'] = 1; // admin user id from schema.sql
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'Admin';
$_SESSION['last_activity'] = time();

// Now test the prediction endpoint
$_POST['x1'] = 10;
$_POST['x2'] = 1;
$_POST['x3'] = 0;
$_POST['x4'] = 0;
$_POST['x5'] = 0;
$_POST['x6'] = 0;

require_once 'predict_multivariate.php';
?>
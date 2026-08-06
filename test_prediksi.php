<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Force login as admin for testing
session_start();
$_SESSION['user_id'] = 1; // admin user id from schema.sql
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'Admin';
$_SESSION['last_activity'] = time();

// Set POST data
$_POST['x1'] = 10;
$_POST['x2'] = 1;
$_POST['x3'] = 0;
$_POST['x4'] = 0;
$_POST['x5'] = 0;
$_POST['x6'] = 0;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Buffer output to capture it
ob_start();
require_once 'predict_multivariate.php';
$result = ob_get_clean();

// Parse and display result nicely
$data = json_decode($result, true);
echo "<pre>";
print_r($data);
echo "</pre>";

// Also show the calculated prediction manually for verification
if ($data['success'] && isset($data['prediksi'])) {
    echo "<h2>Manual Verification:</h2>";
    echo "Using coefficients: beta0={$data['koefisien_used']['beta0']}, ";
    echo "beta1={$data['koefisien_used']['beta1']}, ";
    echo "beta2={$data['koefisien_used']['beta2']}<br>";
    echo "Prediction = {$data['koefisien_used']['beta0']} + ";
    echo "({$data['koefisien_used']['beta1']} * {$_POST['x1']}) + ";
    echo "({$data['koefisien_used']['beta2']} * {$_POST['x2']}) = ";
    echo $data['koefisien_used']['beta0'] +
         ($data['koefisien_used']['beta1'] * $_POST['x1']) +
         ($data['koefisien_used']['beta2'] * $_POST['x2']);
    echo "<br>";
    echo "Result from API: {$data['prediksi']}";
}
?>
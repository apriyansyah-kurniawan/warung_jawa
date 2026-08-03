<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/kpi.php';

// Test KPI functions
echo "Testing KPI functions...\n\n";

try {
    // Test penjualan
    echo "1. Testing kpi_penjualan:\n";
    $penjualan = kpi_penjualan($pdo);
    print_r($penjualan);
    echo "\n";

    // Test stok_tersedia
    echo "2. Testing kpi_stok_tersedia:\n";
    $stok_tersedia = kpi_stok_tersedia($pdo);
    print_r($stok_tersedia);
    echo "\n";

    // Test stok_menipis
    echo "3. Testing kpi_stok_menipis:\n";
    $stok_menipis = kpi_stok_menipis($pdo);
    print_r($stok_menipis);
    echo "\n";

    // Test prediksi
    echo "4. Testing kpi_ringkasan_prediksi:\n";
    $prediksi = kpi_ringkasan_prediksi($pdo);
    print_r($prediksi);
    echo "\n";

    echo "All tests completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
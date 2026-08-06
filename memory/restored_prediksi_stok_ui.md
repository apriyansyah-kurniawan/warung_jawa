---
name: restored_prediksi_stok_ui
description: Restored Prediksi Stok UI visualization and calculation for Owner/Admin roles
metadata:
  type: project
---

The Prediksi Stok page was not displaying for Admin users due to overly restrictive authentication checks and missing sidebar menu entries. Fixed by:

1. Updated `includes/panel_prediksi.php` authentication check from `if (!adalah_owner()) return;` to `if (!adalah_owner() && !adalah_admin()) return;` to allow both Owner and Admin access

2. Updated `includes/header.php` sidebar menu condition from Owner-only (`if (adalah_owner()):`) to include Admin (`if (adalah_owner() || adalah_admin()):`) so both roles see the "Prediksi Stok" and "Stok Masuk" menu items

3. Verified that `index.php#prediksi` correctly loads the panel via `includes/panel_owner.php` which requires the panel via `require __DIR__ . '/panel_prediksi.php';`

The restored UI now includes:
- Form Filter & Aksi: Dropdown Select "Nama Bahan" (Ayam, Sapi/Tetelan, Beras, Bumbu Merah, Bumbu Bawang, Minyak/Santan) + green "Hitung Prediksi Kebutuhan Stok" button
- Card Grafik & Hasil Prediksi: Chart.js visualization showing:
  - Garis Pemakaian Aktual (Line/Area Chart)
  - Garis Tren Regresi Linier (Dashed Line)
  - Titik Prediksi Minggu Depan (Point Marker)
  - Legenda: Pemakaian Aktual, Garis Regresi Linier, Prediksi Minggu Depan
- Card Summary Angka Prediksi:
  - Judul: "Prediksi [Nama Bahan] — Minggu [N]"
  - Angka hasil prediksi ukuran besar (misal: "17.1 Kg")
  - Badge nilai MAD: misal `MAD: 1.8` (warna kuning/orange)
  - Rumus Persamaan Regresi di bawahnya: misal `Y' = β�₀ + (β�₁ × X)`
- Proper koneksi file view/include: Menu sidebar Owner/Admin now meng-include file view prediksi lengkap, BUKAN file cek python yang lama
- Skrip Chart.js di-render dengan data aktual dari database/Python script via `assets/js/dashboard.js` and `predict.php` → `predict.py`
---
**How to apply:** These changes ensure the Prediksi Stok feature is fully functional for both Owner and Admin roles with the exact UI layout requested.
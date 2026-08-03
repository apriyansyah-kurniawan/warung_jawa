# Warung Jawa — Sistem Inventory & Prediksi Stok

PHP Native + Python untuk UMKM kuliner Warung Jawa.

## Fitur Utama (v3)

- **Dashboard modern** — sidebar gelap + aksen hijau, responsif mobile/tablet/desktop
- **4 KPI Cards** — penjualan, stok tersedia, stok menipis, prediksi minggu depan
- **Multi-role** — Admin, Kasir, Owner dengan hak akses berbeda
- **Penjualan menu** — Kasir input menu, stok bahan berkurang otomatis (transaksi DB)
- **Prediksi Python** — regresi linear + MAD via `predict.py` (tidak diubah)
- **Export** — CSV penjualan & stok_keluar, cetak PDF via browser print
- **Keamanan** — session timeout 15 menit, kontrol role, audit log

## Struktur File

```
warung_jawa/
├── config.php              # DB, PYTHON_PATH, DAFTAR_MENU, SESSION_TIMEOUT
├── schema.sql              # Skema lengkap
├── schema_upgrade_v3.sql   # Migrasi tabel riwayat_aktivitas
├── login.php / logout.php / index.php
├── predict.php / predict.py
├── aksi/                   # Handler POST (penjualan, stok, user)
├── export/                 # CSV & cetak laporan
├── includes/
│   ├── auth.php            # Login, timeout, role check
│   ├── logger.php          # Audit log
│   ├── kpi.php             # Perhitungan KPI dashboard
│   ├── kpi_cards.php       # 4 kartu KPI
│   ├── widget_aktivitas.php
│   ├── header.php / footer.php
│   └── panel_*.php
└── assets/
    ├── css/app.css
    └── js/ (app.js, kasir.js, dashboard.js)
```

## Instalasi

```bash
# Database baru
mysql -u root -p < schema.sql

# Upgrade dari versi lama (tambah audit log)
mysql -u root -p < schema_upgrade_v3.sql
```

Buka: `http://localhost/warung_jawa/`

## Akun Demo

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | admin    | admin123  |
| Kasir | kasir    | kasir123  |
| Owner | owner    | owner123  |

## Alur Bisnis

```
KASIR → insert_penjualan.php (transaction)
          ├─ INSERT penjualan
          └─ INSERT stok_keluar (resep otomatis)
                └─ predict.py membaca stok_keluar

OWNER → KPI + stok masuk + grafik prediksi + export
ADMIN → user mgmt + monitoring + tes Python + export
```

## Keamanan

- Session timeout: **15 menit** tanpa aktivitas → redirect ke login
- `predict.php` & export: hanya role yang diizinkan
- Semua aksi penting dicatat di `riwayat_aktivitas`

## Resep Otomatis

| Menu | Harga | Bahan/porsi |
|------|-------|-------------|
| Nasi Ayam Jawa | Rp 25.000 | 0.25 Kg Ayam |
| Gulai Daging | Rp 35.000 | 0.20 Kg Daging |
| Pecel Daun Ubi | Rp 15.000 | 1.0 Ikat Daun Ubi |
# warung_jawa

#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import json
from datetime import datetime
import pymysql

# -------------------------------------------------------------------------
# Konfigurasi koneksi database (sama dengan config.php)
# -------------------------------------------------------------------------
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'warung_jawa',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor,
}


def ambil_data_dari_database(nama_bahan):
    """
    Mengambil seluruh catatan pemakaian harian untuk satu bahan.

    Parameter:
        nama_bahan (str): Nama bahan, contoh 'Ayam', 'Daging', 'Daun Ubi'

    Return:
        list of dict: [{'tanggal': date, 'jumlah_terpakai': float}, ...]
    """
    koneksi = pymysql.connect(**DB_CONFIG)
    try:
        with koneksi.cursor() as cursor:
            sql = """
                SELECT tanggal, jumlah_terpakai
                FROM stok_keluar
                WHERE nama_bahan = %s
                ORDER BY tanggal ASC
            """
            cursor.execute(sql, (nama_bahan,))
            return cursor.fetchall()
    finally:
        koneksi.close()


def ambil_label_minggu(tanggal):
    """
    Mengubah tanggal menjadi label minggu ISO (tahun + nomor minggu).
    Contoh: 2026-05-04 -> '2026-W19'
    """
    if isinstance(tanggal, str):
        tanggal = datetime.strptime(tanggal, '%Y-%m-%d').date()

    iso = tanggal.isocalendar()
    return f"{iso[0]}-W{str(iso[1]).zfill(2)}"


def agregasi_mingguan(data_harian):
    """
    Menjumlahkan jumlah_terpakai per minggu.

    Input:  data harian (list dict)
    Output: list dict terurut [{'minggu': '2026-W19', 'total': 22.1}, ...]
    """
    # Dictionary untuk menampung total per minggu
    total_per_minggu = {}

    for baris in data_harian:
        label_minggu = ambil_label_minggu(baris['tanggal'])
        jumlah = float(baris['jumlah_terpakai'])

        if label_minggu not in total_per_minggu:
            total_per_minggu[label_minggu] = 0.0

        total_per_minggu[label_minggu] += jumlah

    # Urutkan berdasarkan label minggu (kronologis)
    minggu_terurut = sorted(total_per_minggu.keys())

    hasil = []
    for minggu in minggu_terurut:
        hasil.append({
            'minggu': minggu,
            'total': round(total_per_minggu[minggu], 2),
        })

    return hasil


def hitung_regresi_linear_manual(daftar_x, daftar_y):
    """
    Menghitung koefisien regresi linear Y = a + bX secara manual.

    Rumus:
        b = (n * ΣXY - ΣX * ΣY) / (n * ΣX² - (ΣX)²)
        a = (ΣY - b * ΣX) / n

    Parameter:
        daftar_x: list nilai X (1, 2, 3, ..., n)
        daftar_y: list nilai Y (total pemakaian mingguan)

    Return:
        tuple (a, b)
    """
    n = len(daftar_x)

    sigma_x = 0.0   # ΣX   = jumlah semua X
    sigma_y = 0.0   # ΣY   = jumlah semua Y
    sigma_xy = 0.0  # ΣXY  = jumlah semua (X * Y)
    sigma_x2 = 0.0  # ΣX²  = jumlah semua (X kuadrat)

    # Loop manual untuk menghitung sigma (penjumlahan)
    for i in range(n):
        x = daftar_x[i]
        y = daftar_y[i]

        sigma_x += x
        sigma_y += y
        sigma_xy += (x * y)
        sigma_x2 += (x * x)

    # Hitung koefisien b (kemiringan garis)
    pembilang_b = (n * sigma_xy) - (sigma_x * sigma_y)
    penyebut_b = (n * sigma_x2) - (sigma_x ** 2)

    if penyebut_b == 0:
        b = 0.0
    else:
        b = pembilang_b / penyebut_b

    # Hitung koefisien a (titik potong sumbu Y)
    a = (sigma_y - (b * sigma_x)) / n

    return a, b


def hitung_mad(daftar_y_aktual, daftar_y_prediksi):
    """
    Menghitung Mean Absolute Deviation (MAD) sebagai ukuran error.

    Rumus:
        MAD = Σ|Y - Y'| / n

    Semakin kecil MAD, semakin akurat model prediksi.
    """
    n = len(daftar_y_aktual)
    total_selisih = 0.0

    for i in range(n):
        selisih = abs(daftar_y_aktual[i] - daftar_y_prediksi[i])
        total_selisih += selisih

    return total_selisih / n


def main():
    # Validasi argumen command line
    if len(sys.argv) < 2:
        print(json.dumps({
            'error': 'Argumen nama_bahan wajib. Contoh: python predict.py "Ayam"'
        }))
        sys.exit(1)

    nama_bahan = sys.argv[1]

    try:
        # LANGKAH 1: Ambil data historis dari database
        data_harian = ambil_data_dari_database(nama_bahan)

        if not data_harian:
            print(json.dumps({
                'error': f"Tidak ada data historis untuk bahan '{nama_bahan}'."
            }))
            sys.exit(1)

        # LANGKAH 2: Agregasi data harian menjadi total mingguan
        data_mingguan = agregasi_mingguan(data_harian)

        if len(data_mingguan) < 2:
            print(json.dumps({
                'error': 'Data tidak cukup. Minimal dibutuhkan 2 minggu data.'
            }))
            sys.exit(1)

        n = len(data_mingguan)

        # LANGKAH 3: Bentuk variabel X (1,2,3,...,n) dan Y (total mingguan)
        daftar_x = list(range(1, n + 1))
        daftar_y = [item['total'] for item in data_mingguan]

        # LANGKAH 4: Hitung regresi linear manual
        a, b = hitung_regresi_linear_manual(daftar_x, daftar_y)

        # LANGKAH 5: Hitung nilai garis regresi untuk setiap minggu historis
        garis_regresi_y = [round(a + (b * x), 2) for x in daftar_x]

        # LANGKAH 6: Prediksi minggu berikutnya (X = n + 1)
        index_minggu_depan = n + 1
        nilai_prediksi = round(a + (b * index_minggu_depan), 2)

        # Pemakaian tidak boleh negatif
        if nilai_prediksi < 0:
            nilai_prediksi = 0.0

        # LANGKAH 7: Hitung MAD (error prediksi)
        nilai_mad = round(hitung_mad(daftar_y, garis_regresi_y), 2)

        # Label sumbu X untuk Chart.js
        label_minggu = [f"Minggu {x}" for x in daftar_x]

        # LANGKAH 8: Susun output JSON
        output = {
            'nama_bahan': nama_bahan,
            'historical_x': label_minggu,
            'historical_y': daftar_y,
            'regression_line_y': garis_regresi_y,
            'next_week_index': f"Minggu {index_minggu_depan}",
            'forecasted_val': nilai_prediksi,
            'mad_error': nilai_mad,
            'koefisien_a': round(a, 4),
            'koefisien_b': round(b, 4),
        }

        # Cetak JSON satu baris (dibaca oleh predict.php)
        print(json.dumps(output))

    except pymysql.MySQLError as e:
        print(json.dumps({'error': f'Error database: {str(e)}'}))
        sys.exit(1)

    except Exception as e:
        print(json.dumps({'error': f'Error pemrosesan: {str(e)}'}))
        sys.exit(1)


if __name__ == '__main__':
    main()

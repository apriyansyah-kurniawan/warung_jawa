#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Modul Pelatihan Model Regresi Linier Berganda untuk Prediksi Stok & Inventaris Warung Jawa

Skrip ini membaca data dari tabel dataset_regresi, menghitung koefisien
Regresi Linier Berganda menggunakan aljabar matriks (Persamaan Normal:
β = (X^T * X)^-1 * X^T * Y), menghitung Mean Absolute Deviation (MAD),
dan menyimpan hasilnya ke tabel model_regresi.

Author: Senior Full-Stack Engineer (PHP Native & Python)
"""

import sys
import json
import numpy as np
import pymysql
from datetime import datetime

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

def ambil_data_regresi():
    """
    Mengambil seluruh data training dari tabel dataset_regresi.

    Return:
        tuple: (X, Y) where X is feature matrix (with bias column) and Y is target vector
    """
    koneksi = pymysql.connect(**DB_CONFIG)
    try:
        with koneksi.cursor() as cursor:
            # Ambil semua data dari dataset_regresi
            sql = """
                SELECT
                    x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak,
                    jumlah_porsi
                FROM dataset_regresi
                ORDER BY tanggal ASC
            """
            cursor.execute(sql)
            results = cursor.fetchall()

            if not results:
                raise Exception("Tidak ada data dalam tabel dataset_regresi")

            # Pisahkan fitur (X) dan target (Y)
            # X: [x1_ayam, x2_sapi, x3_beras, x4_bumbu_merah, x5_bumbu_bawang, x6_minyak]
            # Y: jumlah_porsi
            X_raw = []
            Y = []

            for row in results:
                X_raw.append([
                    float(row['x1_ayam']),
                    float(row['x2_sapi']),
                    float(row['x3_beras']),
                    float(row['x4_bumbu_merah']),
                    float(row['x5_bumbu_bawang']),
                    float(row['x6_minyak'])
                ])
                Y.append(float(row['jumlah_porsi']))

            # Konversi ke numpy array
            X_raw = np.array(X_raw)
            Y = np.array(Y)

            # Tambahkan kolom bias (intercept) sebagai kolom pertama
            # X = [1, x1, x2, x3, x4, x5, x6]
            ones = np.ones((X_raw.shape[0], 1))
            X = np.concatenate([ones, X_raw], axis=1)

            return X, Y, len(results)

    finally:
        koneksi.close()

def hitung_regresi_linear_berganda(X, Y):
    """
    Menghitung koefisien Regresi Linier Berganda menggunakan aljabar matriks.

    Rumus: β = (X^T * X)^-1 * X^T * Y

    Parameter:
        X: matrix fitur dengan bias column (n_samples, n_features+1)
        Y: vector target (n_samples,)

    Return:
        beta: vector koefisiensi [β0, β1, β2, β3, β4, β5, β6]
    """
    # Hitung transpose X
    X_T = X.T

    # Hitung X^T * X
    X_T_X = np.dot(X_T, X)

    # Hitung invers (X^T * X)^-1
    # Menggunakan pseudo-inverse untuk stabilitas numerik
    X_T_X_inv = np.linalg.pinv(X_T_X)

    # Hitung X^T * Y
    X_T_Y = np.dot(X_T, Y)

    # Hitung koefisien: β = (X^T * X)^-1 * X^T * Y
    beta = np.dot(X_T_X_inv, X_T_Y)

    return beta

def hitung_prediksi(X, beta):
    """
    Menghitung prediksi Y menggunakan koefisien regresi.

    Parameter:
        X: matrix fitur dengan bias column
        beta: vector koefisiensi

    Return:
        Y_pred: vector prediksi
    """
    return np.dot(X, beta)

def hitung_mad(Y_aktual, Y_prediksi):
    """
    Menghitung Mean Absolute Deviation (MAD) sebagai ukuran error.

    Rumus: MAD = Σ |Y_i - Ŷ_i| / n

    Parameter:
        Y_aktual: vector nilai aktual
        Y_prediksi: vector nilai prediksi

    Return:
        mad: nilai MAD
    """
    n = len(Y_aktual)
    total_selisih = np.sum(np.abs(Y_aktual - Y_prediksi))
    return total_selisih / n

def hitung_r_squared(Y_aktual, Y_prediksi):
    """
    Menghitung koefisien determinasi (R-squared) sebagai ukuran goodness of fit.

    Rumus: R² = 1 - (SS_res / SS_tot)
    di mana SS_res = Σ(y_i - ŷ_i)² dan SS_tot = Σ(y_i - ȳ)²

    Parameter:
        Y_aktual: vector nilai aktual
        Y_prediksi: vector nilai prediksi

    Return:
        r_squared: nilai R-squared
    """
    ss_res = np.sum((Y_aktual - Y_prediksi) ** 2)
    ss_tot = np.sum((Y_aktual - np.mean(Y_aktual)) ** 2)

    if ss_tot == 0:
        return 0.0

    return 1 - (ss_res / ss_tot)

def simpan_model_regresi(beta, mad, r_squared, jumlah_data):
    """
    Menyimpan hasil parameter koefisien dan evaluasi ke tabel model_regresi.

    Parameter:
        beta: vector koefisiensi [β0, β1, β2, β3, β4, β5, β6]
        mad: nilai Mean Absolute Deviation
        r_squared: nilai R-squared
        jumlah_data: jumlah data training yang digunakan
    """
    koneksi = pymysql.connect(**DB_CONFIG)
    try:
        with koneksi.cursor() as cursor:
            # Hapus record lama (opsional, atau kita bisa INSERT baru)
            # Untuk kesederhanaan, kita hapus dulu lalu insert baru
            cursor.execute("DELETE FROM model_regresi")

            # Insert record baru
            sql = """
                INSERT INTO model_regresi
                (beta0, beta1, beta2, beta3, beta4, beta5, beta6, mad, r_square, jumlah_data_training)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """

            # Pastikan beta memiliki 7 elemen (β0 hingga β6)
            beta_list = beta.tolist() if hasattr(beta, 'tolist') else list(beta)
            while len(beta_list) < 7:
                beta_list.append(0.0)  # padding jika kurang

            cursor.execute(sql, (
                beta_list[0],  # beta0 (intercept)
                beta_list[1],  # beta1 (x1_ayam)
                beta_list[2],  # beta2 (x2_sapi)
                beta_list[3],  # beta3 (x3_beras)
                beta_list[4],  # beta4 (x4_bumbu_merah)
                beta_list[5],  # beta5 (x5_bumbu_bawang)
                beta_list[6],  # beta6 (x6_minyak)
                float(mad),
                float(r_squared),
                int(jumlah_data)
            ))

            koneksi.commit()

    finally:
        koneksi.close()

def main():
    """
    Fungsi utama untuk melatih model regresi linier berganda.
    """
    try:
        print("Memulai pelatihan model regresi linier berganda...", file=sys.stderr)

        # Langkah 1: Ambil data dari database
        print("Mengambil data dari tabel dataset_regresi...", file=sys.stderr)
        X, Y, jumlah_data = ambil_data_regresi()
        print(f"Berhasil mengambil {jumlah_data} baris data training.", file=sys.stderr)

        # Langkah 2: Hitung koefisien regresi menggunakan aljabar matriks
        print("Menghitung koefisien regresi menggunakan persamaan normal...", file=sys.stderr)
        beta = hitung_regresi_linear_berganda(X, Y)
        print(f"Koefisien yang dihitung: {beta}", file=sys.stderr)

        # Langkah 3: Hitung prediksi dan evaluasi
        print("Menghitung prediksi dan metrik evaluasi...", file=sys.stderr)
        Y_prediksi = hitung_prediksi(X, beta)
        mad = hitung_mad(Y, Y_prediksi)
        r_squared = hitung_r_squared(Y, Y_prediksi)

        print(f"MAD (Mean Absolute Deviation): {mad:.4f}", file=sys.stderr)
        print(f"R-squared: {r_squared:.4f}", file=sys.stderr)

        # Langkah 4: Simpan model ke database
        print("Menyimpan model ke tabel model_regresi...", file=sys.stderr)
        simpan_model_regresi(beta, mad, r_squared, jumlah_data)
        print("Model berhasil disimpan!", file=sys.stderr)

        # Output hasil sebagai JSON untuk konsumsi oleh PHP
        output = {
            'success': True,
            'message': 'Model regresi berhasil dilatih',
            'beta0': float(beta[0]),
            'beta1': float(beta[1]),
            'beta2': float(beta[2]),
            'beta3': float(beta[3]),
            'beta4': float(beta[4]),
            'beta5': float(beta[5]),
            'beta6': float(beta[6]),
            'mad': float(mad),
            'r_square': float(r_squared),
            'jumlah_data_training': int(jumlah_data)
        }

        print(json.dumps(output))

    except Exception as e:
        error_output = {
            'success': False,
            'error': str(e)
        }
        print(json.dumps(error_output))
        sys.exit(1)

if __name__ == '__main__':
    main()
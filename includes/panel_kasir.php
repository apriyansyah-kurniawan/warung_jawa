<?php
/** Panel KASIR — Form penjualan menu dengan layout POS Grid (Katalog Menu + Keranjang) */
// Ambil menu dari database, fallback ke DAFTAR_MENU jika gagal
$menu_items = get_menu_from_db();
if ($menu_items === null) {
    $menu_items = DAFTAR_MENU;
}
$menu_json = json_encode($menu_items, JSON_UNESCAPED_UNICODE);

// Fungsi sederhana untuk menentukan kategori berdasarkan nama menu
function get_menu_category($name) {
    $name = strtolower($name);
    $minuman_keywords = ['es', 'jus', 'teh', 'kopi', 'susu', 'air', 'soft drink', 'soda', 'kelapa', 'kelapa muda', 'jus'];
    foreach ($minuman_keywords as $kw) {
        if (strpos($name, $kw) !== false) {
            return 'Drink';
        }
    }
    return 'Food';
}
?>
<?php
$judul_halaman = 'POS Kasir';
$menu_aktif    = 'kasir';
?>
<?php require_once 'includes/header.php'; ?>

<div class="container-fluid py-3">
    <div class="row g-0">
        <!-- Kolom Kiri: Katalog Menu (65%) -->
        <div class="col-lg-8 pe-lg-0">
            <div class="card h-100 border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i> Katalog Menu</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Filter Kategori -->
                    <div class="mb-3 px-3 pt-2">
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary active">
                                <input type="radio" name="filter" value="all" checked> All Items
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="filter" value="food"> Food
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="filter" value="drink"> Drink
                            </label>
                        </div>
                    </div>
                    <!-- Grid Kontainer Menu -->
                    <div id="menuGrid" class="row g-3 p-3"></div>
                </div>
            </div>
        </div>
        <!-- Kolom Kanan: Keranjang & Checkout (35%) -->
        <div class="col-lg-4 ps-lg-0">
            <div class="card h-100 border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i> Keranjang Pesanan</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Jenis Pesanan -->
                    <div class="mb-3 px-3 pt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="orderType" id="orderDineIn" value="dinein" checked>
                            <label class="form-check-label" for="orderDineIn">Dine In</label>
                        </div>
                        <div class="form-check form-check-inline ms-3">
                            <input class="form-check-input" type="radio" name="orderType" id="orderTakeAway" value="takeaway">
                            <label class="form-check-label" for="orderTakeAway">Take Away</label>
                        </div>
                    </div>
                    <!-- Daftar Item Pesanan -->
                    <div id="cartItems" class="px-3 pt-2 pb-0"></div>
                    <!-- Ringkasan Pembayaran -->
                    <div class="mt-4 pt-3 px-3 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="cartSubtotal">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Diskon:</span>
                            <span id="cartDiscount">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span id="cartTotal">Rp 0</span>
                        </div>
                    </div>
                    <!-- Tombol Aksi -->
                    <div class="mt-4 pt-3 px-3 pb-3">
                        <button id="btnClearCart" class="btn btn-outline-danger w-50 me-2"><i class="bi bi-trash me-1"></i> Bersihkan</button>
                        <button id="btnCheckout" class="btn btn-success w-50"><i class="bi bi-cash-coin me-1"></i> Bayar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Success -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">Transaksi Berhasil</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="bi bi-check-circle display-4 text-success mb-3"></i>
        <p class="mb-4">Transaksi berhasil dicatat. Stok bahan baku dikurangi.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
window.DAFTAR_MENU = <?= $menu_json ?>;

// Variabel keranjang: array of objects {nama_menu, harga_satuan, jumlah}
let keranjang = [];

document.addEventListener('DOMContentLoaded', function () {
    renderMenuGrid();
    renderCart();
    bindEvents();
});

function renderMenuGrid() {
    const grid = document.getElementById('menuGrid');
    if (!grid) return;
    grid.innerHTML = ''; // kosongkan

    const filter = document.querySelector('input[name="filter"]:checked').value;

    Object.entries(window.DAFTAR_MENU).forEach(([nama, info]) => {
        const kategori = getMenuCategory(nama);
        if (filter !== 'all' && filter.toLowerCase() !== kategori.toLowerCase()) {
            return;
        }
        const col = document.createElement('div');
        col.className = 'col-6 col-sm-4 col-lg-3';

        const card = document.createElement('div');
        card.className = 'card h-100 border-0 shadow-sm menu-card';
        card.style.cursor = 'pointer';
        card.dataset.nama = nama;
        card.dataset.harga = info.harga;

        // Gambar placeholder (gunakan gambar default jika ada)
        const img = document.createElement('div');
        img.style.height = '120px';
        img.style.backgroundColor = '#f8f9fa';
        img.style.borderBottom = '1px solid #eee';
        img.style.display = 'flex';
        img.style.alignItems = 'center';
        img.style.justifyContent = 'center';
        img.innerHTML = `<i class="bi bi-box-seam text-secondary"></i>`;

        const body = document.createElement('div');
        body.className = 'card-body p-2 d-flex flex-column';

        const title = document.createElement('h6');
        title.className = 'card-title fw-semibold flex-grow-1';
        title.textContent = nama;

        const price = document.createElement('div');
        price.className = 'text-success fw-semibold mt-2';
        price.textContent = formatRupiah(info.harga);

        body.append(title, price);
        card.append(img, body);
        col.appendChild(card);
        grid.appendChild(col);
    });

    // Tambah event klik ke setiap card setelah render
    document.querySelectorAll('.menu-card').forEach(card => {
        card.addEventListener('click', function () {
            const nama = this.dataset.nama;
            const harga = parseInt(this.dataset.harga);
            addToCart(nama, harga, 1);
        });
    });
}

function getMenuCategory(name) {
    const nameL = name.toString().toLowerCase();
    const minuman = ['es', 'jus', 'teh', 'kopi', 'susu', 'air', 'soft drink', 'soda', 'kelapa', 'kelapa muda', 'jus'];
    for (const kw of minuman) {
        if (nameL.includes(kw)) return 'Drink';
    }
    return 'Food';
}

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

function addToCart(nama, harga, qty = 1) {
    const existing = keranjang.find(item => item.nama_menu === nama);
    if (existing) {
        existing.jumlah += qty;
    } else {
        keranjang.push({ nama_menu: nama, harga_satuan: harga, jumlah: qty });
    }
    renderCart();
}

function removeFromCart(nama) {
    keranjang = keranjang.filter(item => item.nama_menu !== nama);
    renderCart();
}

function changeQty(nama, delta) {
    const item = keranjang.find(i => i.nama_menu === nama);
    if (!item) return;
    item.jumlah += delta;
    if (item.jumlah <= 0) {
        removeFromCart(nama);
    } else {
        renderCart();
    }
}

function renderCart() {
    const cartContainer = document.getElementById('cartItems');
    if (!cartContainer) return;

    if (keranjang.length === 0) {
        cartContainer.innerHTML = '<div class="text-center py-4 text-muted">Keranjang masih kosong</div>';
        updateSummary();
        return;
    }

    let html = '';
    keranjang.forEach((item, index) => {
        const subtotal = item.harga_satuan * item.jumlah;
        html += `
            <div class="cart-item border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${item.nama_menu}</h6>
                        <small class="text-muted">@ ${formatRupiah(item.harga_satuan)} / porsi</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary btn-sm me-2 qty-minus" data-nama="${item.nama_menu}">−</button>
                        <span class="mx-2 fw-semibold">${item.jumlah}</span>
                        <button class="btn btn-outline-secondary btn-sm me-2 qty-plus" data-nama="${item.nama_menu}">+</button>
                        <span class="ms-3 fw-semibold">${formatRupiah(subtotal)}</span>
                        <button class="btn btn-outline-danger btn-sm remove-item ms-2" data-nama="${item.nama_menu}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
    });
    cartContainer.innerHTML = html;
    updateSummary();

    // Re-attach event listeners for buttons
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function () {
            changeQty(this.dataset.nama, -1);
        });
    });
    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function () {
            changeQty(this.dataset.nama, 1);
        });
    });
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function () {
            removeFromCart(this.dataset.nama);
        });
    });
}

function updateSummary() {
    const subtotal = keranjang.reduce((sum, item) => sum + (item.harga_satuan * item.jumlah), 0);
    const diskon = 0; // tempat untuk diskon nanti
    const total = subtotal - diskon;
    document.getElementById('cartSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('cartDiscount').textContent = formatRupiah(diskon);
    document.getElementById('cartTotal').textContent = formatRupiah(total);
}

function bindEvents() {
    // Filter kategori
    document.querySelectorAll('input[name="filter"]').forEach(radio => {
        radio.addEventListener('change', renderMenuGrid);
    });

    // Tombol Bersihkan Keranjang
    document.getElementById('btnClearCart').addEventListener('click', function () {
        if (confirm('Yakin ingin bersihkan keranjang?')) {
            keranjang = [];
            renderCart();
        }
    });

    // Tombol Bayar (Checkout)
    document.getElementById('btnCheckout').addEventListener('click', function () {
        if (keranjang.length === 0) {
            alert('Keranjang masih kosong! Tambahkan minimal satu menu.');
            return;
        }
        const orderType = document.querySelector('input[name="orderType"]:checked').value; // dinein atau takeaway
        const payload = {
            items: keranjang.map(item => ({
                nama_menu: item.nama_menu,
                jumlah_porsi: item.jumlah,
                harga_satuan: item.harga_satuan
            })),
            order_type: orderType,
            tanggal: new Date().toISOString().slice(0,10) // YYYY-MM-DD
        };
        fetch('process_penjualan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tampilkan modal sukses
                const modalEl = document.getElementById('successModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                // Bersihkan keranjang setelah ditutup modal
                modalEl.addEventListener('hidden.bs.modal', function () {
                    keranjang = [];
                    renderCart();
                }, { once: true });
            } else {
                alert('Gagal melakukan transaksi: ' + (data.message || 'Error tidak diketahui'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
        });
    });
}
</script>
<?php require_once 'includes/footer.php'; ?>
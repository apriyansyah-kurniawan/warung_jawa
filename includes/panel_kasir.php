<?php
/**
 * panel_kasir.php — Antarmuka POS (Point of Sale) untuk role Kasir.
 */
$base = $base ?? '';
?>
<!-- POS Interface for Kasir -->
<div class="modern-card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cart-plus me-2"></i> Point of Sale</span>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearCartBtn">
            <i class="bi bi-trash me-1"></i> Bersihkan Keranjang
        </button>
    </div>
    <div class="card-body p-0">
        <div class="row g-0">
            <!-- Kolom Kiri: Keranjang Belanja -->
            <div class="col-md-4">
                <div class="border-end p-3 h-100" style="height: 80vh; overflow-y: auto;">
                    <h6 class="mb-3"><i class="bi bi-list-task me-2"></i> Keranjang Belanja</h6>
                    <div id="cartItems">
                        <!-- Cart items will be inserted here by JavaScript -->
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-basket me-2"></i> Keranjang masih kosong
                        </div>
                    </div>
                    <hr class="my-3">
                    <div id="cartSummary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="cartSubtotal">Rp 0</span>
                        </div>
                        <button type="button" class="btn btn-success w-100" id="checkoutBtn">
                            <i class="bi bi-cash me-2"></i> Bayar
                        </button>
                    </div>
                </div>
            </div>
            <!-- Kolom Kanan: Grid Katalog Menu -->
            <div class="col-md-8">
                <div class="p-3 h-100" style="height: 80vh; overflow-y: auto;">
                    <h6 class="mb-3"><i class="bi bi-grid-3x3-gap me-2"></i> Katalog Menu Utama</h6>
                    <div id="menuGrid" class="row g-3">
                        <!-- Menu cards will be inserted here by PHP -->
                        <?php
                        // Fetch main menu items (status 'Utama')
                        $stmt = $pdo->query("SELECT id, nama_menu, harga FROM master_menu WHERE status = 'Utama' ORDER BY nama_menu");
                        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($menus)):
                        ?>
                            <div class="col-12">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-box me-3"></i> Belum ada menu utama yang tersedia
                                </div>
                            </div>
                        <?php else: foreach ($menus as $menu): ?>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm menu-card" data-id="<?= $menu['id'] ?>" data-nama="<?= htmlspecialchars($menu['nama_menu']) ?>" data-harga="<?= $menu['harga'] ?>">
                                    <div class="card-body d-flex flex-column">
                                        <div class="menu-icon bg-light rounded p-3 mb-3 text-center">
                                            <i class="bi bi-book me-1"></i> <?= strtoupper(substr($menu['nama_menu'], 0, 2)) ?>
                                        </div>
                                        <h6 class="card-title mb-2 text-truncate"><?= htmlspecialchars($menu['nama_menu']) ?></h6>
                                        <p class="menu-price fw-bold mb-3">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></p>
                                        <button type="button" class="btn btn-outline-primary w-100 addToCartBtn">
                                            <i class="bi bi-cart-plus me-1"></i> Tambah ke Keranjang
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Cart object to hold items
    let cart = {};

    // DOM Elements
    const cartItemsContainer = document.getElementById('cartItems');
    const cartSubtotalElement = document.getElementById('cartSubtotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const addToCartButtons = document.querySelectorAll('.addToCartBtn');

    // Format currency to Rupiah
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number);
    }

    // Update cart UI
    function updateCartUI() {
        // Clear cart items container
        cartItemsContainer.innerHTML = '';

        if (Object.keys(cart).length === 0) {
            cartItemsContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-basket me-2"></i> Keranjang masih kosong</div>';
            cartSubtotalElement.textContent = formatRupiah(0);
            checkoutBtn.disabled = true;
            return;
        }

        // Add each item to cart UI
        let subtotal = 0;
        for (const [key, item] of Object.entries(cart)) {
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded';
            itemElement.innerHTML = `
                <div>
                    <h6 class="mb-1">${item.nama}</h6>
                    <small class="text-muted">Rp ${formatRupiah(item.harga)} x ${item.jumlah}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger qty-decrease" data-key="${key}">-</button>
                    <span class="qty">${item.jumlah}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary qty-increase" data-key="${key}">+</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary remove-item" data-key="${key}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            cartItemsContainer.appendChild(itemElement);
            subtotal += item.harga * item.jumlah;
        }

        // Update subtotal
        cartSubtotalElement.textContent = formatRupiah(subtotal);
        checkoutBtn.disabled = false;

        // Attach event listeners to quantity buttons and remove buttons
        document.querySelectorAll('.qty-decrease').forEach(button => {
            button.addEventListener('click', function () {
                const key = this.getAttribute('data-key');
                if (cart[key].jumlah > 1) {
                    cart[key].jumlah--;
                } else {
                    delete cart[key];
                }
                updateCartUI();
            });
        });

        document.querySelectorAll('.qty-increase').forEach(button => {
            button.addEventListener('click', function () {
                const key = this.getAttribute('data-key');
                cart[key].jumlah++;
                updateCartUI();
            });
        });

        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function () {
                const key = this.getAttribute('data-key');
                delete cart[key];
                updateCartUI();
            });
        });
    }

    // Add to cart button click handler
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function () {
            const card = this.closest('.menu-card');
            const id = card.getAttribute('data-id');
            const nama = card.getAttribute('data-nama');
            const harga = parseFloat(card.getAttribute('data-harga'));

            const key = `menu_${id}`;
            if (cart[key]) {
                cart[key].jumlah++;
            } else {
                cart[key] = { id, nama, harga, jumlah: 1 };
            }

            updateCartUI();

            // Show feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Ditambahkan!';
            this.disabled = true;
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 1500);
        });
    });

    // Checkout button click handler
    checkoutBtn.addEventListener('click', function () {
        if (Object.keys(cart).length === 0) {
            return;
        }

        // Prepare cart data for sending
        const cartData = [];
        for (const [key, item] of Object.entries(cart)) {
            cartData.push({
                id_menu: item.id,
                nama: item.nama,
                harga: item.harga,
                jumlah: item.jumlah
            });
        }

        // Disable button and show loading
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="bi bi-hourglass me-2"></i> Memproses...';
        this.disabled = true;

        // Send to server
        fetch('aksi/simpan_transaksi_pos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ cart: cartData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear cart and show success
                cart = {};
                updateCartUI();

                // Show success alert (using SweetAlert2 if available, otherwise alert)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    alert(data.message);
                }
            } else {
                // Show error
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            }

            // Reset button
            this.innerHTML = originalText;
            this.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan koneksi.'
                });
            } else {
                alert('Terjadi kesalahan koneksi.');
            }

            // Reset button
            this.innerHTML = originalText;
            this.disabled = false;
        });
    });

    // Clear cart button click handler
    clearCartBtn.addEventListener('click', function () {
        if (Object.keys(cart).length === 0) {
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Bersihkan Keranjang?',
                text: "Anda yakin ingin mengosongkan keranjang belanja?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, bersihkan!'
            }).then((result) => {
                if (result.isConfirmed) {
                    cart = {};
                    updateCartUI();
                }
            });
        } else {
            if (confirm('Anda yakin ingin mengosongkan keranjang belanja?')) {
                cart = {};
                updateCartUI();
            }
        }
    });

    // Initialize cart UI
    updateCartUI();
});
</script>
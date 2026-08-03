document.addEventListener('DOMContentLoaded', function () {

    const selectMenu  = document.getElementById('selectMenu');
    const inputPorsi  = document.getElementById('inputPorsi');
    const totalHarga  = document.getElementById('totalHarga');

    if (!selectMenu || !inputPorsi || !totalHarga) {
        return;
    }

    /**
     * Format angka ke mata uang Rupiah Indonesia.
     */
    function formatRupiah(angka) {
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    /**
     * Hitung ulang total harga berdasarkan menu & porsi terpilih.
     */
    function hitungTotal() {
        const namaMenu = selectMenu.value;
        const porsi    = parseInt(inputPorsi.value, 10) || 0;

        if (!namaMenu || porsi <= 0) {
            totalHarga.textContent = formatRupiah(0);
            return;
        }

        const menu  = window.DAFTAR_MENU[namaMenu];
        const harga = menu ? menu.harga : 0;
        const total = harga * porsi;

        totalHarga.textContent = formatRupiah(total);
    }

    // Pasang event listener pada dropdown menu dan input porsi
    selectMenu.addEventListener('change', hitungTotal);
    inputPorsi.addEventListener('input', hitungTotal);

    // Hitung saat halaman pertama kali dimuat
    hitungTotal();
});

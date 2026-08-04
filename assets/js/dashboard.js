let chartInstance = null;

const CHART_COLORS = {
    actual:   { line: '#6366f1', fill: 'rgba(99, 102, 241, 0.12)' },
    regression: { line: '#f59e0b', fill: 'transparent' },
    forecast: { line: '#ef4444', fill: '#ef4444' },
};

document.addEventListener('DOMContentLoaded', function () {
    const btnPrediksi = document.getElementById('btnPrediksi');
    const selectBahan = document.getElementById('selectBahan');
    if (!btnPrediksi || !selectBahan) return;

    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorBox = document.getElementById('errorBox');
    const hasilPrediksi = document.getElementById('hasilPrediksi');
    const chartContainer = document.getElementById('chartContainer');

    // Create notification element for insufficient data if not exists
    let infoNotifikasi = document.getElementById('infoNotifikasi');
    if (!infoNotifikasi) {
        infoNotifikasi = document.createElement('div');
        infoNotifikasi.id = 'infoNotifikasi';
        infoNotifikasi.className = 'alert alert-info mt-3';
        chartContainer.parentNode.insertBefore(infoNotifikasi, chartContainer.nextSibling);
    }

    btnPrediksi.addEventListener('click', () => jalankanPrediksi(selectBahan.value));

    function jalankanPrediksi(namaBahan) {
        errorBox.classList.add('d-none');
        infoNotifikasi.classList.add('d-none');
        loadingIndicator.classList.remove('d-none');
        btnPrediksi.disabled = true;

        fetch('predict.php?nama_bahan=' + encodeURIComponent(namaBahan))
            .then(r => r.json().then(d => { if (!r.ok) throw new Error(d.message || 'Error'); return d; }))
            .then(data => {
                loadingIndicator.classList.add('d-none');
                btnPrediksi.disabled = false;
                if (!data.success) { tampilkanError(data.message); return; }
                const namaBahanFromData = data.nama_bahan || namaBahan || 'Bahan Baku';
                const satuan = data.satuan || 'Kg';
                renderGrafik(data, namaBahanFromData, satuan);
                renderRingkasanTeks(data, namaBahanFromData, satuan);
                // Check insufficient historical data (need at least 2 points for regression)
                if ((data.historical_x || []).length < 2) {
                    tampilkanNotifikasiKurangData(namaBahanFromData);
                } else {
                    sembunyikanNotifikasi();
                }
            })
            .catch(err => {
                loadingIndicator.classList.add('d-none');
                btnPrediksi.disabled = false;
                tampilkanError(err.message);
            });
    }

    function tampilkanError(pesan) {
        errorBox.textContent = pesan;
        errorBox.classList.remove('d-none');
    }

    function tampilkanNotifikasiKurangData(namaBahan) {
        infoNotifikasi.innerHTML = `Data historis stok keluar untuk bahan <strong>${namaBahan}</strong> belum mencukupi (minimal 2 minggu transaksi) untuk membentuk garis tren regresi.`;
        infoNotifikasi.classList.remove('d-none');
    }

    function sembunyikanNotifikasi() {
        infoNotifikasi.classList.add('d-none');
    }

    function renderGrafik(data, namaBahan, satuan) {
        const labels = (data.historical_x || []).concat([data.next_week_index ?? 'Minggu Depan']);
        const ctx = document.getElementById('grafikPrediksi').getContext('2d');

        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pemakaian Aktual (' + namaBahan + ')',
                        data: (data.historical_y || []).concat([null]),
                        borderColor: CHART_COLORS.actual.line,
                        backgroundColor: CHART_COLORS.actual.fill,
                        borderWidth: 2.5, tension: 0.35, pointRadius: 5,
                        pointHoverRadius: 8, fill: true,
                    },
                    {
                        label: 'Garis Regresi Linear',
                        data: (data.regression_line_y || []).concat([data.forecasted_val ?? 0]),
                        borderColor: CHART_COLORS.regression.line,
                        borderDash: [8, 4], borderWidth: 2, pointRadius: 0, tension: 0,
                    },
                    {
                        label: 'Prediksi Minggu Depan',
                        data: (data.historical_x || []).map(() => null).concat([data.forecasted_val ?? 0]),
                        borderColor: CHART_COLORS.forecast.line,
                        backgroundColor: CHART_COLORS.forecast.fill,
                        pointRadius: 10, pointStyle: 'star', showLine: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    title: {
                        display: true,
                        text: 'Prediksi Kebutuhan Stok — ' + namaBahan,
                        font: { size: 16, weight: '600' },
                        color: '#1a1d21',
                    },
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                    tooltip: {
                        backgroundColor: 'rgba(26, 29, 33, 0.9)',
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 },
                        padding: 12, cornerRadius: 8,
                        callbacks: {
                            label: ctx => {
                                const v = ctx.parsed.y;
                                return v !== null ? ctx.dataset.label + ': ' + v + ' ' + satuan : '';
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        title: { display: true, text: 'Jumlah (' + satuan + ')', font: { weight: '600' } },
                    },
                    x: {
                        grid: { display: false },
                        title: { display: true, text: 'Periode Mingguan', font: { weight: '600' } },
                    },
                },
            },
        });
    }

    function renderRingkasanTeks(data, namaBahan, satuan) {
        hasilPrediksi.innerHTML =
            '<div class="p-3 rounded-3" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5)">' +
            '<h5 class="mb-2">Prediksi <strong>' + namaBahan + '</strong> — ' + (data.next_week_index ?? 'Minggu Depan') + '</h5>' +
            '<span class="display-6 fw-bold text-success">' + (data.forecasted_val ?? 0) + ' ' + satuan + '</span>' +
            '<div class="mt-2"><span class="badge bg-warning text-dark">MAD: ' + (data.mad_error ?? 0) + '</span></div>' +
            '<p class="text-muted mt-2 mb-0 small">Y\' = ' + (data.koefisien_a ?? 0) + ' + (' + (data.koefisien_b ?? 0) + ' × X)</p></div>';
    }
});
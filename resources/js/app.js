

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

if (document.querySelector('[data-dashboard]')) {
    import('./dashboard.js').catch((error) => {
        console.error('Komponen interaktif dashboard gagal dimuat.', error);
        const message = document.querySelector('[data-location-message]');
        const button = document.querySelector('[data-load-location]');

        if (message) {
            message.textContent = 'Peta dan grafik interaktif gagal dimuat. Ringkasan dan daftar lahan tetap tersedia.';
        }

        if (button) {
            button.disabled = true;
        }
    });
}

if (document.querySelector('[data-land-location-picker]')) {
    import('./land-location-picker.js').catch((error) => {
        console.error('Pemilih lokasi lahan gagal dimuat.', error);
        const status = document.querySelector('[data-land-location-status]');

        if (status) {
            status.textContent = 'Peta pemilih lokasi gagal dimuat. Alamat tetap dapat disimpan tanpa mengubah koordinat tersimpan.';
        }
    });
}

if (document.querySelector('[data-report-chart]')) {
    import('./reports.js').catch((error) => {
        console.error('Grafik laporan gagal dimuat.', error);
        const status = document.querySelector('[data-report-chart-status]');

        if (status) {
            status.textContent = 'Grafik gagal dimuat. Ringkasan transaksi tetap tersedia.';
        }
    });
}

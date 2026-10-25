import Chart from 'chart.js/auto';

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const canvas = document.querySelector('[data-report-chart]');

if (canvas?.dataset.chart) {
    try {
        const chartData = JSON.parse(canvas.dataset.chart);

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: chartData.income,
                        backgroundColor: '#2F6B4F',
                        borderRadius: 5,
                    },
                    {
                        label: 'Pengeluaran',
                        data: chartData.expenses,
                        backgroundColor: '#B85C4A',
                        borderRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${currency.format(context.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6B756D' } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#E8E5DA' },
                        ticks: {
                            color: '#6B756D',
                            callback: (value) => currency.format(value),
                        },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Data grafik laporan tidak dapat dibaca.', error);
        const status = document.querySelector('[data-report-chart-status]');

        if (status) {
            status.textContent = 'Grafik tidak dapat ditampilkan. Ringkasan transaksi tetap tersedia.';
        }
    }
}

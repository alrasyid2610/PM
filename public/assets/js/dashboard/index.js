let barChartInstance = null;
let pieChartInstance = null;

$(document).ready(function () {
    loadSummary();
    loadSoPerMonth($('#yearSelect').val());

    $('#yearSelect').on('change', function () {
        loadSoPerMonth($(this).val());
    });
});

function loadSummary() {
    $.get(window.route.summary, function (res) {
        $('#statKantorPusat').text(res.kantor_pusat);
        $('#statKantorCabang').text(res.kantor_cabang);
        $('#statTotalSo').text(res.total_so);
        $('#statTotalWo').text(res.total_wo);

        const s = res.so_by_status;
        $('#soDraft').text(s.draft ?? 0);
        $('#soConfirmed').text(s.confirmed ?? 0);
        $('#soOnProgress').text(s['on progress'] ?? s.on_progress ?? 0);
        $('#soDone').text(s.done ?? 0);

        renderPieChart([
            s.draft ?? 0,
            s.confirmed ?? 0,
            s['on progress'] ?? s.on_progress ?? 0,
            s.done ?? 0,
        ]);
    }).fail(function () {
        Notify.error('Gagal memuat data summary');
    });
}

function loadSoPerMonth(year) {
    $.get(window.route.soPerMonth, { year: year }, function (res) {
        renderBarChart(res.data);
    }).fail(function () {
        Notify.error('Gagal memuat data chart');
    });
}

function renderBarChart(data) {
    const ctx = document.getElementById('barChart').getContext('2d');
    if (barChartInstance) barChartInstance.destroy();

    barChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','Mei','Jun',
                     'Jul','Agu','Sep','Okt','Nov','Des'],
            datasets: [{
                label: 'Jumlah SO',
                data: data,
                backgroundColor: '#18386b',
                borderRadius: 4,
                borderSkipped: false,
                hoverBackgroundColor: '#1a5fbe',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} SO`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 },
                        color: '#9ca3af',
                    },
                    grid: { color: '#f3f4f6' },
                },
                x: {
                    ticks: { font: { size: 11 }, color: '#9ca3af' },
                    grid: { display: false },
                }
            }
        }
    });
}

function renderPieChart(data) {
    const ctx = document.getElementById('pieChart').getContext('2d');
    if (pieChartInstance) pieChartInstance.destroy();

    const labels = ['Draft', 'Confirmed', 'On Progress', 'Done'];
    const colors = ['#9ca3af', '#1a5fbe', '#f59e0b', '#10b981'];
    const total  = data.reduce((a, b) => a + b, 0);

    pieChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            cutout: '65%',
            plugins: { legend: { display: false } },
            responsive: false,
        }
    });

    let html = '';
    labels.forEach((label, i) => {
        const pct = total > 0
            ? Math.round((data[i] / total) * 100)
            : 0;
        html += `
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:10px;height:10px;border-radius:3px;
                    background:${colors[i]};flex-shrink:0;"></div>
                <span style="font-size:12px;color:#374151;">${label}</span>
                <span style="margin-left:auto;font-size:12px;
                    font-weight:600;color:#111827;">${data[i]}</span>
                <span style="font-size:11px;color:#9ca3af;">${pct}%</span>
            </div>
        `;
    });
    $('#pieLegend').html(html);
}

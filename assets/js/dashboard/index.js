let barChartInstance = null;
let pieChartInstance = null;

$(document).ready(function () {
    loadSummary();
    loadSoPerMonth($('#yearSelect').val());

    $('#yearSelect').on('change', function () {
        loadSoPerMonth($(this).val());
    });
});

// =====================
// LOAD SUMMARY
// =====================
function loadSummary() {
    $.get(window.route.summary, function (res) {

        // Stat cards
        $('#statKantorPusat').text(res.kantor_pusat);
        $('#statKantorCabang').text(res.kantor_cabang);
        $('#statTotalSo').text(res.total_so);
        $('#statTotalWo').text(res.total_wo);

        // SO by status
        const status = res.so_by_status;
        $('#soDraft').text(status.draft ?? 0);
        $('#soConfirmed').text(status.confirmed ?? 0);
        $('#soOnProgress').text(status['on progress'] ?? status.on_progress ?? 0);
        $('#soDone').text(status.done ?? 0);

        // Pie chart
        renderPieChart([
            status.draft ?? 0,
            status.confirmed ?? 0,
            status['on progress'] ?? status.on_progress ?? 0,
            status.done ?? 0,
        ]);
    });
}

// =====================
// LOAD SO PER MONTH
// =====================
function loadSoPerMonth(year) {
    $.get(window.route.soPerMonth, { year: year }, function (res) {
        renderBarChart(res.data);
    });
}

// =====================
// RENDER BAR CHART
// =====================
function renderBarChart(data) {
    const ctx = document.getElementById('barChart').getContext('2d');

    if (barChartInstance) barChartInstance.destroy();

    barChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
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
                    ticks: {
                        font: { size: 11 },
                        color: '#9ca3af',
                    },
                    grid: { display: false },
                }
            }
        }
    });
}

// =====================
// RENDER PIE CHART
// =====================
function renderPieChart(data) {
    const ctx = document.getElementById('pieChart').getContext('2d');

    if (pieChartInstance) pieChartInstance.destroy();

    const labels  = ['Draft', 'Confirmed', 'On Progress', 'Done'];
    const colors  = ['#9ca3af', '#1a5fbe', '#f59e0b', '#10b981'];
    const total   = data.reduce((a, b) => a + b, 0);

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

    // Render legend manual
    let legendHtml = '';
    labels.forEach((label, i) => {
        const pct = total > 0 ? Math.round((data[i] / total) * 100) : 0;
        legendHtml += `
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:10px;height:10px;border-radius:3px;background:${colors[i]};flex-shrink:0;"></div>
                <span style="font-size:12px;color:#374151;">${label}</span>
                <span style="margin-left:auto;font-size:12px;font-weight:600;color:#111827;">${data[i]}</span>
                <span style="font-size:11px;color:#9ca3af;">${pct}%</span>
            </div>
        `;
    });
    $('#pieLegend').html(legendHtml);
}

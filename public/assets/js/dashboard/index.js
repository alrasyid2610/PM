let _listType = 'wo';

$(document).ready(function () {
    loadSummary();
    switchTab('so');

    $(document).on('click', '.dw-card-clickable', function () {
        var type = $(this).data('tab');
        $('.dw-tab-btn[data-type]').removeClass('active');
        $('.dw-tab-btn[data-type="' + type + '"]').addClass('active');
        switchTab(type);
        $('html, body').animate({ scrollTop: $('.card').last().offset().top - 20 }, 300);
    });

    $(document).on('click', '.dw-tab-btn[data-type]', function () {
        $('.dw-tab-btn[data-type]').removeClass('active');
        $(this).addClass('active');
        switchTab($(this).data('type'));
    });
});

function switchTab(type) {
    _listType = type;

    window.route.data    = window.route.list;
    window._dtParams     = { type: type };

    window.datatableHeaderLabels = {
        no:         'No.',
        judul:      'Judul',
        pelanggan:  'Pelanggan',
        pic:        'PIC',
        deadline:   'Deadline',
        status:     'Status',
    };

    window.datatableColumnRenderers = {
        no: function (data, t, row) {
            var base = window.route[type];
            return '<a href="' + base + '?open=' + row.id_rec + '" class="fw-semibold text-decoration-none" style="color:#1a56db">' + (data ?? '-') + '</a>';
        },
        status: function (data) {
            var map = {
                'on-progress': '<span class="badge bg-primary">On Progress</span>',
                'onprogress':  '<span class="badge bg-primary">On Progress</span>',
                completed:     '<span class="badge bg-success">Completed</span>',
                cancel:        '<span class="badge bg-danger">Cancel</span>',
                planned:       '<span class="badge bg-info text-dark">Planned</span>',
            };
            return map[data] ?? ('<span class="badge bg-secondary">' + (data ?? '-') + '</span>');
        },
        deadline: function (data) {
            return data ? data : '<span class="text-muted">-</span>';
        },
    };

    $('#global-loader').fadeIn(150);
    initDataTable('#dashboard-list-table', function () {
        $('#global-loader').fadeOut(300);
    });
}

function loadSummary() {
    $.get(window.route.summary, function (res) {
        $('#wg-so-outstanding-value').text(res.so_outstanding ?? 0);
        $('#wg-so-outstanding-sub').text('SO aktif');

        $('#wg-wo-outstanding-value').text(res.wo_outstanding ?? 0);
        $('#wg-wo-outstanding-sub').text('WO on progress');

        $('#wg-fwo-outstanding-value').text(res.fwo_outstanding ?? 0);
        $('#wg-fwo-outstanding-sub').text('FWO planned');

        $('#wg-overdue-value').text(res.overdue ?? 0);
        $('#wg-overdue-sub').text('lewat tenggat');

        $('#wg-due-7-value').text(res.due_7 ?? 0);
        $('#wg-due-7-sub').text('perlu perhatian');

        $('#wg-termin-outstanding-value').text(res.termin_outstanding ?? 0);
        $('#wg-termin-outstanding-sub').text('belum selesai');

    }).fail(function () {
        Notify.error('Gagal memuat data dashboard');
    });
}

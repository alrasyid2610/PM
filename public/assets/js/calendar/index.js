(function () {
    'use strict';

    var currentView = 'month';
    var calMonth = null;
    var calWeek  = null;

    // ── Helpers ────────────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function getActiveTypes() {
        var types = [];
        $('.slicer-type:checked').each(function () { types.push($(this).val()); });
        return types;
    }

    function getActiveStatuses() {
        var statuses = [];
        $('.slicer-status:checked').each(function () { statuses.push($(this).val()); });
        return statuses;
    }

    function openDetail(type, rawId) {
        var urls = { fwo: '/fieldworks', wo: '/work-orders', so: '/sales-orders' };
        var base = urls[type] || '/fieldworks';
        window.open(base + '?open=' + rawId, '_blank');
    }

    // ── Tooltip ────────────────────────────────────────────────────────────────
    var typeLabels = { fwo: 'FWO', wo: 'WO', so: 'SO' };

    function showTooltip(clientX, clientY, data) {
        var d = data;
        var typeLabel = typeLabels[d.type] || d.type.toUpperCase();

        var statusMap = {
            completed: { bg: '#d1fae5', color: '#065f46', label: 'Selesai' },
            selesai:   { bg: '#ede9fe', color: '#5b21b6', label: 'Selesai' },
        };
        var st = statusMap[d.status] || { bg: '#dbeafe', color: '#1e40af', label: 'Aktif' };
        var statusBadge = '<span style="background:' + st.bg + ';color:' + st.color + ';padding:1px 8px;border-radius:20px;font-size:11px;font-weight:600;">' + st.label + '</span>';
        var typeBadge   = '<span style="background:#f1f5f9;color:#475569;padding:1px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-right:4px;">' + typeLabel + '</span>';

        var html =
            '<div style="font-weight:700;color:#1e3a5f;margin-bottom:5px;font-size:13px;">' + escHtml(d.no) + '</div>' +
            '<div style="color:#374151;margin-bottom:5px;font-size:12px;">' + escHtml(d.judul || '') + '</div>';

        if (d.no_wo) html += '<div style="color:#64748b;font-size:11px;margin-bottom:2px;"><i class="fa-solid fa-briefcase me-1"></i>' + escHtml(d.no_wo) + '</div>';
        if (d.pelanggan) html += '<div style="color:#64748b;font-size:11px;margin-bottom:2px;"><i class="fa-solid fa-building me-1"></i>' + escHtml(d.pelanggan) + '</div>';
        if (d.site_name) html += '<div style="color:#64748b;font-size:11px;margin-bottom:6px;"><i class="fa-solid fa-location-dot me-1"></i>' + escHtml(d.site_name) + '</div>';

        html += '<div>' + typeBadge + statusBadge + '</div>';

        $('#calTooltipContent').html(html);

        var $tip = $('#calTooltip');
        $tip.show();
        var tipW = $tip.outerWidth(), tipH = $tip.outerHeight();
        var left = clientX + 14, top = clientY + 14;
        if (left + tipW > $(window).width()  - 10) left = clientX - tipW - 10;
        if (top  + tipH > $(window).height() - 10) top  = clientY - tipH - 10;
        $tip.css({ left: left, top: top });
    }

    function hideTooltip() { $('#calTooltip').hide(); }

    // ── Search state ───────────────────────────────────────────────────────────
    var activeSearch = { by: '', q: '' };

    function applySearch() {
        var by = $('#searchBy').val();
        var q  = $('#searchQ').val().trim();
        if (!by || !q) return;
        activeSearch = { by: by, q: q };

        var labels = { no: 'Nomor', judul: 'Judul', pelanggan: 'Pelanggan', site: 'Site' };
        $('#searchBadge').text(labels[by] + ': "' + q + '"').show();
        $('#btnClearSearch').show();

        // Fetch tanpa date range dulu untuk cari tanggal event paling awal
        var types    = getActiveTypes();
        var statuses = getActiveStatuses();
        $.get('/calendar/events', {
            types: types.join(','),
            statuses: statuses.join(','),
            search_by: by,
            search_q: q,
        }, function (data) {
            if (!data || !data.length) {
                reloadEvents();
                return;
            }

            // Ambil tanggal start paling awal
            var earliest = data.reduce(function (min, e) {
                return (!min || e.start < min) ? e.start : min;
            }, null);

            if (!earliest) { reloadEvents(); return; }

            // Navigate ke bulan/minggu event tersebut
            navigateToDate(earliest);
        });
    }

    function navigateToDate(dateStr) {
        if (currentView === 'month' && calMonth) {
            calMonth.startDate = new DayPilot.Date(dateStr.substring(0, 7) + '-01');
            calMonth.update();
            loadMonthEvents();
        } else if (currentView === 'week' && calWeek) {
            // Cari hari Senin dari minggu tanggal tsb
            var d = new DayPilot.Date(dateStr);
            var dow = new Date(dateStr + 'T00:00:00').getDay(); // 0=Sun
            var diff = (dow === 0) ? -6 : 1 - dow;
            calWeek.startDate = d.addDays(diff);
            calWeek.update();
            loadWeekEvents();
        }
    }

    function clearSearch() {
        activeSearch = { by: '', q: '' };
        $('#searchQ').val('').prop('disabled', true);
        $('#btnSearch').prop('disabled', true);
        $('#searchBy').val('');
        $('#searchBadge').hide();
        $('#btnClearSearch').hide();
        reloadEvents();
    }

    // ── Fetch ──────────────────────────────────────────────────────────────────
    function fetchEvents(start, end, callback) {
        var types = getActiveTypes();
        if (!types.length) { callback([]); return; }

        var statuses = getActiveStatuses();
        var params = { start: start, end: end, types: types.join(','), statuses: statuses.join(',') };
        if (activeSearch.by && activeSearch.q) {
            params.search_by = activeSearch.by;
            params.search_q  = activeSearch.q;
        }
        $.get('/calendar/events', params, function (data) {
            var events = (data || []).map(function (e) {
                return {
                    id:          e.id,
                    rawId:       e.rawId,
                    type:        e.type,
                    text:        e.text,
                    start:       e.start,
                    end:         e.end,
                    backColor:   e.barColor,
                    fontColor:   '#ffffff',
                    borderColor: 'transparent',
                    // data untuk tooltip
                    no:        e.data.no,
                    judul:     e.data.judul,
                    status:    e.data.status,
                    no_wo:     e.data.no_wo     || null,
                    pelanggan: e.data.pelanggan || null,
                    site_name: e.data.site_name || null,
                };
            });
            callback(events);
        });
    }

    // ── Title ──────────────────────────────────────────────────────────────────
    function updateTitle() {
        var title = '';
        try {
            if (currentView === 'month' && calMonth) {
                var d = new Date(calMonth.startDate.addDays(15).toString('yyyy-MM-dd') + 'T00:00:00');
                title = d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            } else if (currentView === 'week' && calWeek) {
                var s = new Date(calWeek.startDate.toString('yyyy-MM-dd') + 'T00:00:00');
                var e = new Date(calWeek.startDate.addDays(6).toString('yyyy-MM-dd') + 'T00:00:00');
                title = s.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
                      + ' – '
                      + e.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        } catch (ex) {}
        $('#calTitle').text(title);
    }

    // ── Event handlers shared ──────────────────────────────────────────────────
    var sharedHandlers = {
        onBeforeEventRender: function (args) {
            args.data.html =
                '<div style="font-size:11px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
                    '[' + (args.data.type || '').toUpperCase() + '] ' + escHtml(args.data.no || '') +
                '</div>';
            args.data.toolTip = '';
        },
        onEventClick: function (args) {
            openDetail(args.e.data.type, args.e.data.rawId);
        },
        onEventMouseOver: function (args) {
            showTooltip(args.originalEvent.clientX, args.originalEvent.clientY, args.e.data);
        },
        onEventMouseOut: function () { hideTooltip(); },
    };

    // ── Month ──────────────────────────────────────────────────────────────────
    function initMonth() {
        var todayStr = DayPilot.Date.today().toString('yyyy-MM-dd');
        var firstOfMonth = new DayPilot.Date(todayStr.substring(0, 7) + '-01');

        calMonth = new DayPilot.Month('calendarContainer', $.extend({
            startDate: firstOfMonth,
        }, sharedHandlers));

        calMonth.init();
        loadMonthEvents();
    }

    function loadMonthEvents() {
        var start = calMonth.startDate.toString('yyyy-MM-dd');
        var end   = calMonth.startDate.addDays(42).toString('yyyy-MM-dd');
        fetchEvents(start, end, function (events) {
            calMonth.events.list = events;
            calMonth.update();
            updateTitle();
        });
    }

    // ── Week ───────────────────────────────────────────────────────────────────
    function initWeek() {
        calWeek = new DayPilot.Calendar('calendarContainer', $.extend({
            viewType: 'Week',
            startDate: DayPilot.Date.today(),
            businessBeginsHour: 7,
            businessEndsHour: 18,
            cellHeight: 25,
            headerHeight: 30,
        }, sharedHandlers));

        calWeek.init();
        loadWeekEvents();
    }

    function loadWeekEvents() {
        var start = calWeek.startDate.toString('yyyy-MM-dd');
        var end   = calWeek.startDate.addDays(7).toString('yyyy-MM-dd');
        fetchEvents(start, end, function (events) {
            calWeek.events.list = events;
            calWeek.update();
            updateTitle();
        });
    }

    // ── Reload current view ────────────────────────────────────────────────────
    function reloadEvents() {
        if (currentView === 'month' && calMonth) loadMonthEvents();
        else if (currentView === 'week' && calWeek) loadWeekEvents();
    }

    // ── Switch view ────────────────────────────────────────────────────────────
    function switchView(view) {
        currentView = view;
        $('#calendarContainer').empty();
        hideTooltip();

        if (view === 'month') {
            $('#btnViewMonth').removeClass('btn-outline-secondary').addClass('btn-primary');
            $('#btnViewWeek').removeClass('btn-primary').addClass('btn-outline-secondary');
            calWeek = null;
            initMonth();
        } else {
            $('#btnViewWeek').removeClass('btn-outline-secondary').addClass('btn-primary');
            $('#btnViewMonth').removeClass('btn-primary').addClass('btn-outline-secondary');
            calMonth = null;
            initWeek();
        }
    }

    // ── Navigate ───────────────────────────────────────────────────────────────
    function navigate(direction) {
        if (currentView === 'month' && calMonth) {
            if (direction === 'prev')  calMonth.startDate = calMonth.startDate.addMonths(-1);
            if (direction === 'next')  calMonth.startDate = calMonth.startDate.addMonths(1);
            if (direction === 'today') {
                var t = DayPilot.Date.today().toString('yyyy-MM-dd');
                calMonth.startDate = new DayPilot.Date(t.substring(0, 7) + '-01');
            }
            calMonth.update();
            loadMonthEvents();
        } else if (currentView === 'week' && calWeek) {
            if (direction === 'prev')  calWeek.startDate = calWeek.startDate.addDays(-7);
            if (direction === 'next')  calWeek.startDate = calWeek.startDate.addDays(7);
            if (direction === 'today') calWeek.startDate = DayPilot.Date.today();
            calWeek.update();
            loadWeekEvents();
        }
    }

    // ── Boot ───────────────────────────────────────────────────────────────────
    $(document).ready(function () {
        initMonth();

        $('#btnPrev').on('click',      function () { navigate('prev');  });
        $('#btnNext').on('click',      function () { navigate('next');  });
        $('#btnToday').on('click',     function () { navigate('today'); });
        $('#btnViewMonth').on('click', function () { switchView('month'); });
        $('#btnViewWeek').on('click',  function () { switchView('week');  });

        // Slicer toggle
        $('.slicer-type, .slicer-status').on('change', function () { reloadEvents(); });

        // Search
        $('#searchBy').on('change', function () {
            var hasVal = !!$(this).val();
            $('#searchQ').prop('disabled', !hasVal).val('');
            $('#btnSearch').prop('disabled', !hasVal);
        });
        $('#btnSearch').on('click', applySearch);
        $('#searchQ').on('keydown', function (e) { if (e.key === 'Enter') applySearch(); });
        $('#btnClearSearch').on('click', clearSearch);

        $(document).on('mousemove', '#calendarContainer', hideTooltip);
    });

})();

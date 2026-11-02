// Menu "Lab Sample Reg" (grup Lab) — pendaftaran sample ke lab per Work Order.
// Dipindah dari tab "Lab Sample Reg" di detail WO (work-order/index.js) sejak 2026-10-31; logika sample & registrasi
// (jalur a: sample FWO, jalur b: BOQ WO tanpa FWO) sama dengan versi tab, endpoint tetap /wo-samples/*.
// Yang baru di sini: pilih WO lewat pencarian fleksibel, riwayat semua pendaftaran, dan Form Registrasi (PDF).
// Jalur "Sample Tanpa BOQ" dihapus (keputusan 2026-10-31).

const MONTH_SHORT_WO = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
function fmtDate(str) {
    if (!str) return '—';
    var d = new Date(str);
    if (isNaN(d)) return str;
    return d.getDate() + '-' + MONTH_SHORT_WO[d.getMonth()] + '-' + d.getFullYear();
}

let labRegWoId = null;       // WO yang sedang dibuka
let labRegFwoId = null;      // cakupan 1 FWO (bila user memilih FWO di pencarian); null = seluruh WO
let woSampleFilePond = null;
let labRegTable = null;

$(document).ready(function () {
    initLabRegWoSelect();
    initLabRegRiwayat();

    // Deep link: /lab-sample-regs?wo=ID
    const params = new URLSearchParams(window.location.search);
    if (params.get('wo')) selectLabRegWo(params.get('wo'), params.get('fwo'));
});

// Query string data sample WO — `?fwo=` mempersempit cakupan ke 1 FWO
function labRegFwoQuery(idFwo) {
    return idFwo ? `?fwo=${idFwo}` : '';
}

function initLabRegWoSelect() {
    $('#labRegWoSelect').select2({
        width: '100%',
        placeholder: 'Cari no. WO / FWO / SO, judul, atau pelanggan…',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: window.labRegRoute.woOptions,
            dataType: 'json',
            delay: 250,
            data: (p) => ({ q: p.term || '' }),
            processResults: (d) => ({ results: d }),
        },
    });

    // Hasil pencarian bergrup "Work Order" / "Fieldwork Order"; memilih FWO = cakupan hanya FWO itu
    $('#labRegWoSelect').on('select2:select', function (e) {
        const d = e.params.data;
        openLabRegWo(d.id_wo, d.id_fwo || null);
    });
    $('#labRegWoSelect').on('select2:clear', closeLabRegWo);
}

// Deep link / klik dari riwayat: set pilihan select2 lalu buka WO (atau FWO)-nya
function selectLabRegWo(idWo, idFwo) {
    $.get(`/wo-samples/by-wo/${idWo}${labRegFwoQuery(idFwo)}`).done(function (res) {
        const wo = res.wo;
        if (!wo) return;
        const fwo = res.fwo;
        const tail = (wo.no_so ? ` · ${wo.no_so}` : '');
        const text = fwo
            ? `${fwo.no_fwo || '-'} — ${fwo.judul_pekerjaan || '-'} · ${wo.no_wo || '-'}${tail}`
            : `${wo.no_wo || '-'} — ${wo.judul_pekerjaan || '-'}${tail}`;
        $('#labRegWoSelect').empty().append(new Option(text, fwo ? `fwo:${fwo.id_fwo}` : `wo:${idWo}`, true, true)).trigger('change');
        openLabRegWo(idWo, fwo ? fwo.id_fwo : null);
    }).fail(function () {
        Notify.error('Work Order tidak ditemukan atau tidak bisa dibuka.');
    });
}

function openLabRegWo(idWo, idFwo) {
    labRegWoId = idWo;
    labRegFwoId = idFwo || null;
    history.replaceState(null, '', `${window.location.pathname}?wo=${idWo}${labRegFwoId ? `&fwo=${labRegFwoId}` : ''}`);
    $('#labRegWoWrap').removeClass('d-none');
    loadWoSampleData(idWo);
}

// "Lihat seluruh WO" — lepas cakupan FWO tanpa mengganti WO
$(document).on('click', '.btn-lab-reg-whole-wo', function (e) {
    e.preventDefault();
    if (!labRegWoId) return;
    selectLabRegWo(labRegWoId, null);
});

function closeLabRegWo() {
    labRegWoId = null;
    labRegFwoId = null;
    history.replaceState(null, '', window.location.pathname);
    $('#labRegWoWrap').addClass('d-none');
    $('#labRegContent').empty();
}

// ── Riwayat semua pendaftaran ────────────────────────────────────────────────

function initLabRegRiwayat() {
    labRegTable = $('#labRegRiwayatTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: window.labRegRoute.riwayat,
        ordering: false,
        pageLength: 10,
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            { data: 'no_registrasi', render: (d) => d ? `<span style="font-weight:600;color:#0369a1;">${escHtml(d)}</span>` : '<span class="text-muted">—</span>' },
            { data: 'tanggal_registrasi', searchable: false, render: (d) => fmtDate(d) },
            { data: 'no_wo', render: (d, t, r) => `<a href="#" class="labreg-open-wo" data-wo="${r.id_wo}" style="font-weight:600;">${escHtml(d || '-')}</a>` },
            { data: 'no_so', render: (d) => d ? escHtml(d) : '<span class="text-muted">—</span>' },
            { data: 'nama_pelanggan', render: (d) => d ? escHtml(d) : '<span class="text-muted">—</span>' },
            { data: 'nama_penerima', render: (d) => d ? escHtml(d) : '<span class="text-muted">—</span>' },
            { data: 'jumlah_sample', searchable: false, className: 'text-center' },
            {
                data: 'id_wo_lab_sample_reg', searchable: false, className: 'text-center',
                render: (d, t, r) => `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-lab-reg-print" data-id="${d}" title="Cetak Form Registrasi" style="font-size:11px;">
                        <i class="fa-solid fa-print" style="color:#0369a1;"></i></button>`,
            },
        ],
    });
}

$(document).on('click', '.labreg-open-wo', function (e) {
    e.preventDefault();
    selectLabRegWo($(this).data('wo'));
    $('html, body').animate({ scrollTop: 0 }, 200);
});

// Form Registrasi (PDF) — dari riwayat maupun header grup "Sudah Diregistrasi"
$(document).on('click', '.btn-lab-reg-print', function (e) {
    e.stopPropagation();
    window.open(`/lab-sample-regs/${$(this).data('id')}/print`, '_blank');
});

// Setelah registrasi berhasil: muat ulang + tawarkan cetak form
function afterLabRegRegistered(res, idWo) {
    loadWoSampleData(idWo);
    if (labRegTable) labRegTable.ajax.reload(null, false);
    Swal.fire({
        icon: 'success',
        title: 'Sample berhasil diregistrasi',
        html: res && res.no_registrasi ? `No. Registrasi <b>${escHtml(res.no_registrasi)}</b>` : '',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-print me-1"></i> Cetak Form',
        cancelButtonText: 'Tutup',
    }).then(function (r) {
        if (r.isConfirmed && res && res.id_wo_lab_sample_reg) window.open(`/lab-sample-regs/${res.id_wo_lab_sample_reg}/print`, '_blank');
    });
}

// ── Sample WO (jalur a: FWO, jalur b: BOQ WO tanpa FWO) ──────────────────────

function loadWoSampleData(idWo) {
    const $wrap = $('#labRegContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/wo-samples/by-wo/${idWo}${labRegFwoQuery(labRegFwoId)}`)
        .done(function (res) {
            const regs     = res.regs || [];
            const boqs     = res.boqs || []; // BOQ item yang belum pernah disentuh FWO manapun — bisa dibuat sample langsung
            const isLocked = res.wo_status === 'completed';
            const idSite   = res.id_site || null;
            const fwo      = res.fwo || null;       // cakupan 1 FWO (bila dipilih di pencarian)
            const siap     = res.siap_fwo || 0;     // sample FWO berstatus Dikirim yang belum diregistrasi
            window._woHasFwo = !!res.has_fwo;
            window._labRegFwoScope = fwo ? fwo.id_fwo : null;

            renderLabRegWoBanner(res.wo, isLocked, fwo, siap);
            // Tombol registrasi: label mengikuti cakupan, badge = jumlah sample yang siap didaftarkan
            $('#btn-wo-reg-from-fwo').data('wo-id', idWo)
                .html(`<i class="fa-solid fa-file-import" style="font-size:11px;"></i> ${fwo ? 'Registrasi Sample FWO' : 'Registrasi dari FWO'}
                    <span class="badge ${siap > 0 ? 'bg-primary' : 'bg-secondary'} ms-1">${siap}</span>`)
                .toggle(window._woHasFwo && !isLocked && can('lab-sample-regs', 'can_create'));

            if (!regs.length && !boqs.length) {
                const scope = fwo ? escHtml(fwo.no_fwo || '') : 'WO ini';
                $wrap.html(window._woHasFwo
                    ? `<div class="text-center text-muted py-4">Belum ada sample dari ${scope} yang diregistrasi.${siap > 0 ? ' Klik tombol <b>Registrasi ' + (fwo ? 'Sample FWO' : 'dari FWO') + '</b> di atas.' : ' Belum ada sample berstatus <b>Dikirim</b> yang siap diregistrasi.'}</div>`
                    : '<div class="text-center text-muted py-4">Tidak ada BOQ pada WO ini.</div>');
                return;
            }

            window._woSamplingPoints = [];
            const renderAll = function () {
                const regsHtml = regs.length ? `
                    <div class="mb-3">
                        <div class="fw-semibold mb-2" style="font-size:13px;color:#1e293b;">
                            <i class="fa-solid fa-clipboard-check me-1" style="color:#0369a1;"></i>Sudah Diregistrasi
                        </div>
                        ${renderWoLabRegGroups(regs, isLocked)}
                    </div>` : '';
                const boqsHtml = boqs.length ? `
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2" id="woDirectSectionHeader">
                            <div class="fw-semibold" style="font-size:13px;color:#1e293b;">
                                <i class="fa-solid fa-flask me-1" style="color:#7c3aed;"></i>Belum Diregistrasi
                            </div>
                            ${!isLocked ? `
                            <button type="button" class="pm-btn-pill d-none" id="btn-wo-register-direct-selected"
                                style="border-color:#0369a1;color:#0369a1;background:#eff6ff;">
                                <i class="fa-solid fa-clipboard-check" style="font-size:11px;"></i>
                                Registrasi <span id="woDirectSelectedCount">0</span> Sample Terpilih
                            </button>` : ''}
                        </div>
                        ${renderWoSampleList(boqs, isLocked, false)}
                    </div>` : '';

                $wrap.html(regsHtml + boqsHtml);
                initWoSampleStatusSelect2($wrap[0]);
                initWoSampleTitikSelect2($wrap[0]);
                if (!isLocked) initWoSampleModalTitikSelect2();
            };

            if (idSite) {
                $.get(`/wo-samples/sampling-points/${idSite}`)
                    .done(function (data) { window._woSamplingPoints = data || []; })
                    .always(renderAll);
            } else {
                renderAll();
            }
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data sample.</div>');
        });
}

function renderLabRegWoBanner(wo, isLocked, fwo, siap) {
    if (!wo) { $('#labRegWoBanner').empty(); return; }

    const badge = (bg, color, icon, text, extra = '') =>
        `<span class="pm-badge" style="background:${bg};color:${color};${extra}"><i class="fa-solid ${icon}" style="font-size:10px;"></i> ${text}</span>`;

    // Status registrasi: terkunci > ada yang siap > tidak ada yang menunggu
    const status = isLocked
        ? badge('#fef2f2', '#b91c1c', 'fa-lock', 'WO selesai — data terkunci')
        : (siap > 0
            ? badge('#eff6ff', '#1d4ed8', 'fa-flask', `${siap} sample siap diregistrasi`, 'font-weight:600;')
            : badge('#f1f5f9', '#64748b', 'fa-circle-check', 'Tidak ada sample FWO yang menunggu registrasi'));

    $('#labRegWoBanner').html(`
        <div class="d-flex align-items-baseline gap-2 flex-wrap mb-2">
            <a href="/work-orders?open=${wo.id_wo}" class="fw-bold text-decoration-none" style="color:#1a56db;font-size:15px;white-space:nowrap;">${escHtml(wo.no_wo || '-')}</a>
            <span style="color:#374151;font-size:14px;">${escHtml(wo.judul_pekerjaan || '-')}</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            ${fwo ? `<span class="pm-badge" style="background:#ecfdf5;color:#047857;font-weight:700;"><i class="fa-solid fa-helmet-safety" style="font-size:10px;"></i> ${escHtml(fwo.no_fwo || '-')}</span>` : ''}
            ${wo.no_so ? `<a href="/sales-orders?open=${wo.id_so}" class="pm-badge" style="background:#eff6ff;color:#1d4ed8;text-decoration:none;"><i class="fa-solid fa-file-invoice-dollar" style="font-size:10px;"></i> ${escHtml(wo.no_so)}</a>` : ''}
            ${wo.nama_pelanggan_display ? badge('#f1f5f9', '#475569', 'fa-location-dot', escHtml(wo.nama_pelanggan_display) + (wo.nama_site && wo.nama_site !== wo.nama_pelanggan_display ? ' · ' + escHtml(wo.nama_site) : '')) : ''}
            ${status}
            ${fwo ? '<a href="#" class="btn-lab-reg-whole-wo ms-1" style="font-size:12px;white-space:nowrap;"><i class="fa-solid fa-arrow-up-right-from-square me-1" style="font-size:10px;"></i>Lihat seluruh WO</a>' : ''}
        </div>`);
}

$(document).on('click', '#btnRefreshWoSample', function () {
    if (labRegWoId) loadWoSampleData(labRegWoId);
    if (labRegTable) labRegTable.ajax.reload(null, false);
});


// ── SAMPLE (langsung di level WO, tanpa FWO) ───────────────────────────────────

const JENIS_LABEL_WO_SAMPLE = { env: 'ENV', we: 'WE', mp: 'MP', product: 'Product' };
// id panel BOQ (tab Lab Sample Reg) yang sedang dibuka user; default semua tertutup
const woSampleOpenPanels = new Set();
const SAMPLE_STATUS_LABEL_WO = { belum_diambil: 'Belum Diambil', diambil: 'Diambil', dikirim: 'Dikirim' };
const SAMPLE_STATUS_COLOR_WO = { belum_diambil: '#64748b', diambil: '#0369a1', dikirim: '#15803d' };
const KONDISI_LABEL_WO = { baik: 'Baik', rusak: 'Rusak', tidak_lengkap: 'Tidak Lengkap' };
const KONDISI_COLOR_WO = { baik: '#15803d', rusak: '#dc2626', tidak_lengkap: '#b45309' };


function _woSpOptions(usedLocations, currentVal) {
    return [{ id: '', text: '' }].concat(
        (window._woSamplingPoints || []).map(function (sp) {
            const isUsed = usedLocations && usedLocations.includes(sp.text) && sp.text !== currentVal;
            return { id: sp.text, text: sp.text, _used: isUsed };
        })
    );
}

function initWoSampleStatusSelect2(container) {
    $(container).find('.wo-sample-inline-status').each(function () {
        const $sel = $(this);
        if ($sel.data('select2')) $sel.select2('destroy');
        $sel.select2({
            width: 'resolve',
            minimumResultsForSearch: Infinity,
            dropdownParent: $sel.closest('td'),
        });
    });
}

function initWoSampleTitikSelect2(container) {
    $(container).find('table').each(function () {
        const $table = $(this);
        const usedLocations = [];
        $table.find('.wo-sample-inline-titik').each(function () {
            const val = $(this).data('current') || '';
            if (val) usedLocations.push(val);
        });

        $table.find('.wo-sample-inline-titik').each(function () {
            const $sel    = $(this);
            const current = $sel.data('current') || '';
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({
                width: 'resolve',
                placeholder: 'Pilih titik…',
                allowClear: true,
                dropdownParent: $sel.closest('td'),
                data: _woSpOptions(usedLocations, current),
                templateResult: function (opt) {
                    if (!opt.id) return opt.text;
                    if (opt._used) {
                        return $('<span class="sp-opt-used" style="display:flex;align-items:center;gap:6px;color:#16a34a;pointer-events:none;cursor:not-allowed;">'
                            + '<i class="fa-solid fa-circle-check" style="font-size:11px;flex-shrink:0;"></i>'
                            + '<span>' + $('<span>').text(opt.text).html() + '</span>'
                            + '</span>');
                    }
                    return $('<span>').text(opt.text);
                },
            })
            .on('select2:selecting', function (e) {
                if (e.params && e.params.args && e.params.args.data && e.params.args.data._used) {
                    e.preventDefault();
                }
            })
            .val(current).trigger('change.select2');
        });
    });
}

function initWoSampleModalTitikSelect2() {
    const $sel = $('#woSampleModal-titik');
    if ($sel.data('select2')) $sel.select2('destroy');
    $sel.select2({
        width: '100%',
        placeholder: 'Pilih titik lokasi…',
        allowClear: true,
        dropdownParent: $('#woSampleDetailModal'),
        data: _woSpOptions(),
    });
}

function renderWoSampleList(boqs, isLocked, hasFwo) {
    return boqs.map(function (boq) {
        const total    = (boq.samples || []).length;
        const diambil  = (boq.samples || []).filter(s => s.status === 'diambil' || s.status === 'dikirim').length;
        const dikirim  = (boq.samples || []).filter(s => s.status === 'dikirim').length;
        const pct      = total > 0 ? Math.round((diambil / total) * 100) : 0;
        const barColor = pct === 100 ? '#15803d' : '#0369a1';
        const sisa     = boq.sisa ?? 0;

        const slotRows = total === 0
            ? `<tr><td colspan="10" class="text-center text-muted py-3" style="font-size:12px;font-style:italic;">Belum ada sample — gunakan tombol "Registrasi Sample" di atas</td></tr>`
            : (boq.samples || []).map(function (s) {
                const statusVal = s.status || 'belum_diambil';
                const jenisTag  = s.jenis_sample
                    ? `<span style="font-size:10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;">${JENIS_LABEL_WO_SAMPLE[s.jenis_sample] || s.jenis_sample}</span>`
                    : `<span style="font-size:10px;color:#94a3b8;font-style:italic;">–</span>`;
                const noSample = s.no_sample
                    ? `<span style="font-size:11px;font-weight:600;">${escHtml(s.no_sample)}</span>`
                    : `<span class="text-muted" style="font-size:11px;font-style:italic;">–</span>`;

                const attCountWo = (s.attachments || []).length;
                const attBadgeWo = attCountWo > 0
                    ? `<span class="btn-wo-sample-edit" data-id="${s.id_lab_sample}"
                            title="${attCountWo} lampiran" style="font-size:11px;color:#0369a1;cursor:pointer;white-space:nowrap;">
                            <i class="fa-solid fa-paperclip"></i> ${attCountWo}
                       </span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const titikVal  = s.titik_lokasi || '';
                const titikCell = !isLocked
                    ? `<select class="form-select form-select-sm wo-sample-inline-titik"
                            data-id="${s.id_lab_sample}"
                            data-current="${escHtml(titikVal)}"
                            style="font-size:11px;width:260px;"></select>`
                    : (titikVal ? escHtml(titikVal) : '<span style="color:#94a3b8;font-style:italic;">–</span>');

                const tglCellWo = s.tanggal_pengambilan
                    ? `<span style="font-size:11px;white-space:nowrap;">${fmtDate(s.tanggal_pengambilan)}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const kondisiCellWo = s.kondisi_sample
                    ? `<span style="font-size:11px;font-weight:600;color:${KONDISI_COLOR_WO[s.kondisi_sample]||'#64748b'};">${KONDISI_LABEL_WO[s.kondisi_sample]||s.kondisi_sample}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const keteranganCellWo = s.keterangan
                    ? `<span style="font-size:11px;" title="${escHtml(s.keterangan)}">${escHtml(s.keterangan)}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                return `
                <tr data-id-sample="${s.id_lab_sample}" data-boq-name="${escHtml(boq.nama_boq)}">
                    <td class="text-center" style="width:32px;">
                        ${!isLocked ? `<input type="checkbox" class="wo-direct-sample-check" value="${s.id_lab_sample}">` : ''}
                    </td>
                    <td style="font-size:12px;color:#64748b;width:36px;">${s.no_urut}</td>
                    <td style="font-size:12px;white-space:nowrap;">${jenisTag} ${noSample}</td>
                    <td style="font-size:12px;">${titikCell}</td>
                    <td style="width:100px;">${tglCellWo}</td>
                    <td style="width:100px;">${kondisiCellWo}</td>
                    <td style="font-size:12px;width:150px;">
                        ${!isLocked
                            ? `<select class="form-select form-select-sm wo-sample-inline-status"
                                    data-id="${s.id_lab_sample}"
                                    style="font-size:11px;width:140px;">
                                    <option value="belum_diambil"${statusVal==='belum_diambil'?' selected':''}>Belum Diambil</option>
                                    <option value="diambil"${statusVal==='diambil'?' selected':''}>Diambil</option>
                                    <option value="dikirim"${statusVal==='dikirim'?' selected':''}>Dikirim ke Lab</option>
                               </select>`
                            : `<span style="font-size:11px;font-weight:600;color:${SAMPLE_STATUS_COLOR_WO[statusVal]||'#64748b'};">${SAMPLE_STATUS_LABEL_WO[statusVal]||statusVal}</span>`
                        }
                    </td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${keteranganCellWo}</td>
                    <td style="width:80px;text-align:center;">${attBadgeWo}</td>
                    <td class="text-center" style="width:72px;white-space:nowrap;">
                        ${!isLocked && can('lab-sample-regs', 'can_update') ? `
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-wo-sample-edit"
                            data-id="${s.id_lab_sample}" title="Edit" style="font-size:11px;">
                            <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                        </button>` : ''}
                        ${!isLocked && can('lab-sample-regs', 'can_delete') ? `
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-sample-delete"
                            data-id="${s.id_lab_sample}" data-no="${escHtml(s.no_sample || 'Sample #' + s.no_urut)}"
                            title="Hapus" style="font-size:11px;">
                            <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                        </button>` : ''}
                    </td>
                </tr>`;
            }).join('');

        const addBtn = (!isLocked && !hasFwo) ? `
            <div class="d-flex gap-2">
                ${total > 0 ? `
                <button type="button" class="btn btn-sm py-0 px-2 btn-wo-sample-bulk-fill"
                    data-id-boq="${boq.id_boq}"
                    style="font-size:11px;border:1px solid #7c3aed;color:#7c3aed;background:#f5f3ff;">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Bulk Insert
                </button>` : ''}
                <div class="dropdown">
                    <button type="button" class="btn btn-sm dropdown-toggle py-0 px-2"
                        data-bs-toggle="dropdown"
                        style="font-size:11px;border:1px solid #0369a1;color:#0369a1;background:#eff6ff;">
                        <i class="fa-solid fa-plus me-1"></i>Registrasi Sample
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px;min-width:200px;">
                        <li>
                            <button type="button" class="dropdown-item btn-wo-sample-generate"
                                data-id-boq="${boq.id_boq}" data-sisa="${sisa}">
                                <i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>
                                Buat Otomatis
                                ${sisa > 0 ? `<span class="badge bg-primary ms-1">${sisa}</span>` : ''}
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item btn-wo-sample-add-one"
                                data-id-boq="${boq.id_boq}">
                                <i class="fa-solid fa-plus me-2 text-success"></i>
                                Tambah 1 Sample
                            </button>
                        </li>
                    </ul>
                </div>
            </div>` : '';

        const collapseId = `woSampleCollapse_${boq.id_boq}`;
        // Default tertutup; panel yang sedang dibuka user tetap terbuka saat daftar dimuat ulang
        const isOpen = woSampleOpenPanels.has(collapseId);
        return `
        <div class="mb-3 border rounded" style="background:#fff;">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <span class="text-muted wo-sample-progress-text" style="font-size:11px;">Target: ${boq.qty || 0} &nbsp;·&nbsp; ${diambil}/${total} diambil &nbsp;·&nbsp; ${dikirim} dikirim</span>
                        ${sisa > 0 && !isLocked ? `<span style="font-size:11px;color:#b45309;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;padding:1px 6px;">${sisa} sisa qty BOQ</span>` : ''}
                    </div>
                    <div style="font-size:13px;font-weight:600;color:#1e293b;">${escHtml(boq.nama_boq)}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${addBtn}
                    <button type="button" class="btn btn-sm btn-plan-icon btn-wo-sample-collapse"
                        data-target="#${collapseId}" title="Sembunyikan / Tampilkan">
                        <i class="fa-solid ${isOpen ? 'fa-chevron-up' : 'fa-chevron-down'}"></i>
                    </button>
                </div>
            </div>
            <div id="${collapseId}"${isOpen ? '' : ' style="display:none;"'}>
                <div style="padding:4px 12px 0;">
                    <div class="progress" style="height:3px;border-radius:0;">
                        <div class="progress-bar" style="width:${pct}%;background:${barColor};"></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="pm-table">
                        <thead>
                            <tr>
                                <th style="width:32px;"></th>
                                <th style="width:36px;">#</th>
                                <th style="width:1%;white-space:nowrap;">Jenis / No. Sample</th>
                                <th style="width:220px;">Titik Lokasi</th>
                                <th style="width:100px;">Tgl Pengambilan</th>
                                <th style="width:100px;">Kondisi</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th style="width:80px;">Lampiran</th>
                                <th style="width:48px;"></th>
                            </tr>
                        </thead>
                        <tbody>${slotRows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }).join('');
}

function _woRegSampleRow(s, isLocked, groupId) {
    const statusVal = s.status || 'belum_diambil';
    const jenisTag  = s.jenis_sample
        ? `<span style="font-size:10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;">${JENIS_LABEL_WO_SAMPLE[s.jenis_sample] || s.jenis_sample}</span>`
        : `<span style="font-size:10px;color:#94a3b8;font-style:italic;">–</span>`;
    const noSample = s.no_sample
        ? `<span style="font-size:11px;font-weight:600;">${escHtml(s.no_sample)}</span>`
        : `<span class="text-muted" style="font-size:11px;font-style:italic;">–</span>`;
    const noRegLabCell = s.no_reg_lab
        ? `<span style="font-size:11px;font-weight:600;color:#0369a1;">${escHtml(s.no_reg_lab)}</span>`
        : `<span class="text-muted" style="font-size:11px;font-style:italic;">–</span>`;
    const statusLabCell = s.status_lab === 'diterima'
        ? `<span class="pm-badge pm-badge--completed" style="font-size:10px;">Diterima</span>`
        : `<span class="text-muted" style="font-size:11px;font-style:italic;">–</span>`;

    // Sample asal FWO: sumber datanya di FWO, di WO hanya boleh dilihat (preview)
    const fromFwo    = !!s.id_fwo;
    const readOnly   = isLocked || fromFwo;
    const attCountWo = (s.attachments || []).length;
    const attBadgeWo = attCountWo > 0
        ? `<span class="btn-wo-sample-edit" data-id="${s.id_lab_sample}" data-preview="${fromFwo ? 1 : 0}"
                title="${attCountWo} lampiran" style="font-size:11px;color:#0369a1;cursor:pointer;white-space:nowrap;">
                <i class="fa-solid fa-paperclip"></i> ${attCountWo}
           </span>`
        : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

    const titikVal  = s.titik_lokasi || '';
    const titikCell = !readOnly
        ? `<select class="form-select form-select-sm wo-sample-inline-titik"
                data-id="${s.id_lab_sample}"
                data-current="${escHtml(titikVal)}"
                style="font-size:11px;width:220px;"></select>`
        : (titikVal ? escHtml(titikVal) : '<span style="color:#94a3b8;font-style:italic;">–</span>');

    const tglCellWo = s.tanggal_pengambilan
        ? `<span style="font-size:11px;white-space:nowrap;">${fmtDate(s.tanggal_pengambilan)}</span>`
        : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

    const kondisiCellWo = s.kondisi_sample
        ? `<span style="font-size:11px;font-weight:600;color:${KONDISI_COLOR_WO[s.kondisi_sample]||'#64748b'};">${KONDISI_LABEL_WO[s.kondisi_sample]||s.kondisi_sample}</span>`
        : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

    const keteranganCellWo = s.keterangan
        ? `<span style="font-size:11px;" title="${escHtml(s.keterangan)}">${escHtml(s.keterangan)}</span>`
        : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

    return `
    <tr data-id-sample="${s.id_lab_sample}" data-boq-name="${escHtml(s.nama_boq || '')}" class="${groupId || ''}-row">
        <td style="font-size:12px;white-space:nowrap;">${jenisTag} ${noSample}</td>
        <td style="font-size:12px;white-space:nowrap;">${noRegLabCell}</td>
        <td style="font-size:12px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escHtml(s.nama_boq || '')}">${escHtml(s.nama_boq || '-')}</td>
        <td style="font-size:12px;">${titikCell}</td>
        <td style="width:100px;">${tglCellWo}</td>
        <td style="width:100px;">${kondisiCellWo}</td>
        <td style="font-size:12px;width:150px;">
            ${!readOnly
                ? `<select class="form-select form-select-sm wo-sample-inline-status"
                        data-id="${s.id_lab_sample}"
                        style="font-size:11px;width:140px;">
                        <option value="belum_diambil"${statusVal==='belum_diambil'?' selected':''}>Belum Diambil</option>
                        <option value="diambil"${statusVal==='diambil'?' selected':''}>Diambil</option>
                        <option value="dikirim"${statusVal==='dikirim'?' selected':''}>Dikirim ke Lab</option>
                   </select>`
                : `<span style="font-size:11px;font-weight:600;color:${SAMPLE_STATUS_COLOR_WO[statusVal]||'#64748b'};">${SAMPLE_STATUS_LABEL_WO[statusVal]||statusVal}</span>`
            }
        </td>
        <td style="width:100px;">${statusLabCell}</td>
        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${keteranganCellWo}</td>
        <td style="width:80px;text-align:center;">${attBadgeWo}</td>
        <td class="text-center" style="width:72px;white-space:nowrap;">
            ${fromFwo ? `
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-sample-edit"
                data-id="${s.id_lab_sample}" data-preview="1" title="Lihat (diubah dari FWO)" style="font-size:11px;">
                <i class="fa-solid fa-eye" style="color:#0369a1;"></i>
            </button>` : ''}
            ${!fromFwo && !isLocked && can('lab-sample-regs', 'can_update') ? `
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-wo-sample-edit"
                data-id="${s.id_lab_sample}" title="Edit" style="font-size:11px;">
                <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
            </button>` : ''}
            ${!fromFwo && !isLocked && can('lab-sample-regs', 'can_delete') ? `
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-sample-delete"
                data-id="${s.id_lab_sample}" data-no="${escHtml(s.no_sample || 'Sample #' + s.id_lab_sample)}"
                title="Hapus" style="font-size:11px;">
                <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
            </button>` : ''}
        </td>
    </tr>`;
}

function renderWoLabRegGroups(regs, isLocked) {
    const bodyRows = regs.map(function (reg) {
        const groupId = `woRegGroup_${reg.id_wo_lab_sample_reg}`;
        const count   = (reg.samples || []).length;

        const headerRow = `
        <tr class="wo-reg-group-header" data-group="${groupId}">
            <td colspan="11">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <i class="fa-solid fa-clipboard-check" style="color:#0369a1;font-size:12px;"></i>
                        ${reg.no_registrasi ? `<span style="font-size:12px;font-weight:700;color:#0369a1;">${escHtml(reg.no_registrasi)}</span>` : ''}
                        <span style="font-size:12px;font-weight:600;color:#1e293b;">Registrasi ${fmtDate(reg.tanggal_registrasi)}</span>
                        <span class="text-muted" style="font-size:11px;">Oleh: ${reg.nama_personnel ? escHtml(reg.nama_personnel) : '-'} &nbsp;·&nbsp; ${count} sample</span>
                        ${reg.keterangan ? `<span class="text-muted" style="font-size:11px;font-style:italic;" title="${escHtml(reg.keterangan)}">"${escHtml(reg.keterangan)}"</span>` : ''}
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-lab-reg-print"
                            data-id="${reg.id_wo_lab_sample_reg}" title="Cetak Form Registrasi" style="font-size:11px;">
                            <i class="fa-solid fa-print me-1" style="color:#0369a1;"></i>Cetak Form
                        </button>
                        <i class="fa-solid fa-chevron-up wo-reg-group-chevron" style="font-size:11px;color:#64748b;"></i>
                    </div>
                </div>
            </td>
        </tr>`;

        const rows = count
            ? reg.samples.map(s => _woRegSampleRow(s, isLocked, groupId)).join('')
            : `<tr class="${groupId}-row"><td colspan="11" class="text-center text-muted py-3" style="font-size:12px;font-style:italic;">Tidak ada sample dalam registrasi ini</td></tr>`;

        return headerRow + rows;
    }).join('');

    return `
    <div class="table-responsive border rounded" style="background:#fff;">
        <table class="pm-table mb-0">
            <thead>
                <tr>
                    <th style="width:1%;white-space:nowrap;">Jenis / No. Sample</th>
                    <th style="width:120px;">No. Reg Lab</th>
                    <th style="min-width:180px;">BOQ</th>
                    <th style="width:220px;">Titik Lokasi</th>
                    <th style="width:100px;">Tgl Pengambilan</th>
                    <th style="width:100px;">Kondisi</th>
                    <th>Status</th>
                    <th style="width:100px;">Status Lab</th>
                    <th>Keterangan</th>
                    <th style="width:80px;">Lampiran</th>
                    <th style="width:48px;"></th>
                </tr>
            </thead>
            <tbody>${bodyRows}</tbody>
        </table>
    </div>`;
}

$(document).on('click', '.wo-reg-group-header', function () {
    const groupId = $(this).data('group');
    $(`.${groupId}-row`).toggle();
    $(this).find('.wo-reg-group-chevron').toggleClass('fa-chevron-up fa-chevron-down');
});

function _initWoRegPersonnelSelect2(selector, dropdownParent) {
    const $sel = $(selector);
    if ($sel.data('select2')) $sel.select2('destroy');
    $sel.empty();
    $sel.select2({
        width: '100%',
        placeholder: 'Pilih personil (opsional)',
        allowClear: true,
        dropdownParent: dropdownParent,
        ajax: {
            url: '/personnel/select2',
            delay: 200,
            dataType: 'json',
            data: (p) => ({ q: p.term ?? '' }),
            processResults: (d) => ({ results: d }),
        },
    });
}

$(document).on('click', '.btn-wo-sample-generate', function () {
    const idBoq = $(this).data('id-boq');
    const sisa  = parseInt($(this).data('sisa') ?? 0);
    const idWo  = labRegWoId;

    if (sisa <= 0) {
        Swal.fire({ icon: 'warning', title: 'Sisa Qty Habis', text: 'Qty BOQ sudah terpenuhi.', confirmButtonText: 'OK' });
        return;
    }

    $.post(`/wo-samples/by-wo/${idWo}/boq/${idBoq}/generate`, { _token: window.route.csrf })
        .done(function (res) {
            if (res.success) {
                Notify.success('Sample berhasil dibuat otomatis.');
                loadWoSampleData(idWo);
            } else {
                Notify.error(res.message || 'Gagal membuat sample.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal membuat sample.');
        });
});

$(document).on('click', '.btn-wo-sample-add-one', function () {
    const idBoq = $(this).data('id-boq');
    const idWo  = labRegWoId;

    $.post(`/wo-samples/by-wo/${idWo}/boq/${idBoq}/add-one`, { _token: window.route.csrf })
        .done(function (res) {
            if (res.success) {
                loadWoSampleData(idWo);
            } else {
                Swal.fire({ icon: 'warning', title: 'Tidak Dapat Ditambahkan', text: res.message || 'Gagal menambah sample.', confirmButtonText: 'OK' });
            }
        })
        .fail(function (xhr) {
            Swal.fire({ icon: 'warning', title: 'Tidak Dapat Ditambahkan', text: xhr.responseJSON?.message || 'Gagal menambah sample.', confirmButtonText: 'OK' });
        });
});

// Tombol "Registrasi Sample Terpilih" cuma muncul kalau ada checkbox yang dicentang
$(document).on('change', '.wo-direct-sample-check', function () {
    const count = $('.wo-direct-sample-check:checked').length;
    $('#woDirectSelectedCount').text(count);
    $('#btn-wo-register-direct-selected').toggleClass('d-none', count === 0);
});

// Registrasi sample yang dicentang di tabel "Belum Diregistrasi" (jalur WO-langsung)
$(document).on('click', '#btn-wo-register-direct-selected', function () {
    const ids = $('.wo-direct-sample-check:checked').map(function () { return $(this).val(); }).get();
    if (!ids.length) return Swal.fire('Perhatian', 'Pilih minimal 1 sample untuk diregistrasi.', 'warning');
    _openWoRegHeaderModal(ids);
});

function _openWoRegHeaderModal(idLabSampleList) {
    $('#woRegHeaderModal').data('id-lab-sample', idLabSampleList);
    $('#woRegHeaderModal-tanggal').val('');
    $('#woRegHeaderModal-keterangan').val('');
    initFpDate('#woRegHeaderModal');
    _initWoRegPersonnelSelect2('#woRegHeaderModal-personnel', $('#woRegHeaderModal'));
    new bootstrap.Modal(document.getElementById('woRegHeaderModal')).show();
}

$(document).on('click', '#woRegHeaderModal-btn-save', function () {
    const idWo   = labRegWoId;
    const idLabSample = $('#woRegHeaderModal').data('id-lab-sample') || [];
    const tanggalDiterima = $('#woRegHeaderModal-tanggal').val();

    if (!tanggalDiterima) return Swal.fire('Perhatian', 'Tanggal Registrasi wajib diisi.', 'warning');

    const payload = {
        _token: window.route.csrf,
        tanggal_registrasi: tanggalDiterima,
        id_personnel_penerima: $('#woRegHeaderModal-personnel').val() || '',
        keterangan: $('#woRegHeaderModal-keterangan').val().trim(),
        id_lab_sample: idLabSample,
    };

    $('#woRegHeaderModal-btn-save').prop('disabled', true);
    $.post(`/wo-samples/by-wo/${idWo}/pull-from-fwo`, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('woRegHeaderModal'))?.hide();
                afterLabRegRegistered(res, idWo);
            } else {
                Swal.fire('Gagal', res.message || 'Gagal registrasi sample.', 'error');
            }
        })
        .fail(function (xhr) {
            const errs = xhr.responseJSON?.errors;
            const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Gagal registrasi sample.');
            Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
        })
        .always(function () {
            $('#woRegHeaderModal-btn-save').prop('disabled', false);
        });
});


// ─── Registrasi dari FWO ────────────────────────────────────────────────────

$(document).on('click', '#btn-wo-reg-from-fwo', function () {
    const idWo = $(this).data('wo-id');
    $('#woRegFromFwoModal-tanggal').val('');
    $('#woRegFromFwoModal-keterangan').val('');
    $('#woRegFromFwoModal-search').val('');
    $('#woRegFromFwoModal-check-all').prop('checked', false);
    $('#woRegFromFwoModal').data('id-wo', idWo);
    initFpDate('#woRegFromFwoModal');
    _initWoRegPersonnelSelect2('#woRegFromFwoModal-personnel', $('#woRegFromFwoModal'));

    const $list = $('#woRegFromFwoModal-list');
    $list.html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data sample dari FWO...</div>');

    new bootstrap.Modal(document.getElementById('woRegFromFwoModal')).show();

    $.get(`/wo-samples/by-wo/${idWo}/available-fwo-samples${labRegFwoQuery(labRegFwoId)}`)
        .done(function (res) {
            $list.html(renderWoRegFromFwoList(res.data || []));
            initRegFromFwoFilters();
            updateRegFromFwoCount();
        })
        .fail(function () {
            $list.html('<div class="text-center text-danger py-3">Gagal memuat data.</div>');
        });
});

// Daftar sample siap registrasi — satu tabel datar (bukan daftar per grup) supaya banyak sample tidak perlu digulir panjang.
// Area tabel punya scroll sendiri dengan header menempel; klik baris = centang.
// Filter: chip Kelompok Matriks (boleh pilih lebih dari satu), pilihan FWO & Jenis, dan pencarian — semuanya digabung (AND).
const REG_KELOMPOK_NONE = '__none';

function renderWoRegFromFwoList(groups) {
    const rows = [];
    groups.forEach((g) => (g.samples || []).forEach((s) => rows.push({ g, s })));

    if (!rows.length) {
        return '<div class="text-center text-muted py-3" style="font-size:13px;">Tidak ada sample berstatus <b>Dikirim</b> yang belum diregistrasi.<br>Hanya sample yang sudah dikirim ke lab yang bisa diregistrasi.</div>';
    }

    const dash = '<span style="color:#94a3b8;">–</span>';
    const th = 'position:sticky;top:0;z-index:1;background:#f8fafc;white-space:nowrap;';
    const kelKey = (g) => g.kelompok_matriks || REG_KELOMPOK_NONE;
    const fwoKey = (g) => g.no_fwo || 'BOQ WO';

    const body = rows.map(function ({ g, s }) {
        const jenisTxt = JENIS_LABEL_WO_SAMPLE[s.jenis_sample] || s.jenis_sample || '';
        const searchText = [s.no_sample, jenisTxt, s.titik_lokasi, g.no_fwo, g.kelompok_matriks, g.nama_boq, g.label].filter(Boolean).join(' ').toLowerCase();
        return `
        <tr class="wo-reg-from-fwo-row" data-search="${escHtml(searchText)}" data-kelompok="${escHtml(kelKey(g))}" data-fwo="${escHtml(fwoKey(g))}" data-jenis="${escHtml(jenisTxt)}" style="cursor:pointer;">
            <td class="text-center" style="width:36px;">
                <input class="form-check-input wo-reg-from-fwo-check" type="checkbox" value="${s.id_lab_sample}" id="fwoSample${s.id_lab_sample}">
            </td>
            <td style="font-size:12px;white-space:nowrap;font-weight:600;">${escHtml(s.no_sample || ('Sample #' + s.id_lab_sample))}</td>
            <td style="font-size:12px;white-space:nowrap;">${jenisTxt ? `<span style="font-size:10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;">${escHtml(jenisTxt)}</span>` : dash}</td>
            <td style="font-size:12px;white-space:nowrap;">${g.no_fwo ? escHtml(g.no_fwo) : '<span class="text-muted">BOQ WO</span>'}</td>
            <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escHtml(g.kelompok_matriks || '')}">${g.kelompok_matriks ? escHtml(g.kelompok_matriks) : dash}</td>
            <td style="font-size:12px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escHtml(g.nama_boq || '')}">${escHtml(g.nama_boq || '-')}</td>
            <td style="font-size:12px;">${s.titik_lokasi ? escHtml(s.titik_lokasi) : dash}</td>
            <td style="font-size:12px;white-space:nowrap;">${s.tanggal_pengambilan ? fmtDate(s.tanggal_pengambilan) : dash}</td>
            <td style="font-size:12px;white-space:nowrap;">${s.kondisi_sample ? (KONDISI_LABEL_WO[s.kondisi_sample] || escHtml(s.kondisi_sample)) : dash}</td>
        </tr>`;
    }).join('');

    // Pilihan filter dihitung dari data yang ada
    const countBy = (keyFn) => {
        const m = new Map();
        rows.forEach(({ g, s }) => { const k = keyFn(g, s); m.set(k, (m.get(k) || 0) + 1); });
        // Tanpa kelompok selalu di urutan terakhir
        return [...m.entries()].sort((a, b) => ((a[0] === REG_KELOMPOK_NONE) - (b[0] === REG_KELOMPOK_NONE)) || String(a[0]).localeCompare(String(b[0]), 'id'));
    };
    const kelompok = countBy((g) => kelKey(g));
    const fwoList = countBy((g) => fwoKey(g));
    const jenisList = countBy((g, s) => JENIS_LABEL_WO_SAMPLE[s.jenis_sample] || s.jenis_sample || '');

    // Tiap filter = Select2 multiple (kosong = semua); kotak filter hanya muncul bila ada lebih dari satu pilihan.
    const labelOf = (k) => (k === REG_KELOMPOK_NONE ? 'Tanpa kelompok' : (k || '–'));
    const filterBox = (id, label, list, placeholder) => list.length > 1 ? `
        <div style="flex:1 1 230px;min-width:200px;">
            <div class="text-muted fw-semibold mb-1" style="font-size:11px;">${label}</div>
            <select id="${id}" multiple data-no-disable data-placeholder="${placeholder}">
                ${list.map(([k, n]) => `<option value="${escHtml(k)}">${escHtml(labelOf(k))} (${n})</option>`).join('')}
            </select>
        </div>` : '';

    const filterBoxes = filterBox('woRegFilterKelompok', 'Kelompok Matriks', kelompok, 'Semua kelompok')
        + filterBox('woRegFilterFwo', 'FWO', fwoList, 'Semua FWO')
        + filterBox('woRegFilterJenis', 'Jenis', jenisList, 'Semua jenis');
    const filters = filterBoxes ? `
    <div id="woRegFromFwoModal-filters" class="d-flex flex-wrap align-items-end gap-3 mb-3">
        ${filterBoxes}
    </div>` : '';

    return `
    ${filters}
    <div class="table-responsive border rounded" style="max-height:40vh;overflow:auto;">
        <table class="pm-table mb-0">
            <thead>
                <tr>
                    <th style="${th}width:36px;"></th>
                    <th style="${th}">No. Sample</th>
                    <th style="${th}">Jenis</th>
                    <th style="${th}">FWO</th>
                    <th style="${th}">Kelompok Matriks</th>
                    <th style="${th}">BOQ</th>
                    <th style="${th}">Titik Lokasi</th>
                    <th style="${th}">Tgl Ambil</th>
                    <th style="${th}">Kondisi</th>
                </tr>
            </thead>
            <tbody>${body}</tbody>
        </table>
        <div id="woRegFromFwoModal-empty" class="text-center text-muted py-3 d-none" style="font-size:13px;">Tidak ada sample yang cocok dengan filter.</div>
    </div>`;
}

// Terapkan semua filter sekaligus: pencarian + Kelompok + FWO + Jenis (AND; filter kosong = semua). Pakai class d-none, bukan :visible.
function applyRegFromFwoFilter() {
    const term = ($('#woRegFromFwoModal-search').val() || '').trim().toLowerCase();
    const kel = $('#woRegFilterKelompok').val() || [];
    const fwo = $('#woRegFilterFwo').val() || [];
    const jenis = $('#woRegFilterJenis').val() || [];

    $('#woRegFromFwoModal-list .wo-reg-from-fwo-row').each(function () {
        const $r = $(this);
        let ok = !term || String($r.data('search') || '').includes(term);
        if (ok && kel.length) ok = kel.includes(String($r.attr('data-kelompok')));
        if (ok && fwo.length) ok = fwo.includes($r.attr('data-fwo'));
        if (ok && jenis.length) ok = jenis.includes($r.attr('data-jenis'));
        $r.toggleClass('d-none', !ok);
    });

    // "Pilih Semua (Tampil)" selalu mulai dari kosong setelah filter berubah
    $('#woRegFromFwoModal-check-all').prop('checked', false);
    updateRegFromFwoCount();
}

function updateRegFromFwoCount() {
    const $rows = $('#woRegFromFwoModal-list .wo-reg-from-fwo-row');
    const total = $rows.length;
    const shown = $rows.not('.d-none').length;
    const picked = $('#woRegFromFwoModal-list .wo-reg-from-fwo-check:checked').length;
    const hiddenPicked = $rows.filter('.d-none').find('.wo-reg-from-fwo-check:checked').length;

    $('#woRegFromFwoModal-count').html(total
        ? `${picked} dipilih · ${shown} dari ${total} sample tampil`
            + (hiddenPicked ? `<br><span class="text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>${hiddenPicked} terpilih sedang disembunyikan filter (tetap ikut diregistrasi)</span>` : '')
        : '');
    $('#woRegFromFwoModal-btn-count').text(picked ? ` (${picked})` : '');
    $('#woRegFromFwoModal-empty').toggleClass('d-none', !total || shown > 0);
}

function initRegFromFwoFilters() {
    $('#woRegFromFwoModal-filters select').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
        $(this).select2({
            width: '100%',
            dropdownParent: $('#woRegFromFwoModal'),
            placeholder: $(this).data('placeholder'),
            allowClear: true,
            closeOnSelect: false, // multi-centang: dropdown tetap terbuka sampai selesai memilih
        });
    });
}
$(document).on('change', '#woRegFilterKelompok, #woRegFilterFwo, #woRegFilterJenis', applyRegFromFwoFilter);
// Select2 dibuat saat modal masih animasi buka â†’ lebar kolom cari 0 (placeholder tak terlihat); ulangi setelah modal tampil
$(document).on('shown.bs.modal', '#woRegFromFwoModal', function () {
    if ($('#woRegFromFwoModal-filters select').length && !$('#woRegFromFwoModal-filters select').first().val()?.length) initRegFromFwoFilters();
});
$(document).on('input', '#woRegFromFwoModal-search', applyRegFromFwoFilter);

// Klik di mana saja pada baris = centang/uncheck (kecuali klik langsung pada checkbox)
$(document).on('click', '#woRegFromFwoModal-list .wo-reg-from-fwo-row', function (e) {
    if ($(e.target).is('input')) return;
    const $cb = $(this).find('.wo-reg-from-fwo-check');
    $cb.prop('checked', !$cb.is(':checked')).trigger('change');
});
$(document).on('change input', '#woRegFromFwoModal-list .wo-reg-from-fwo-check, #woRegFromFwoModal-check-all', function () {
    // Ditunda sampai handler "pilih semua" selesai mengubah centang
    setTimeout(updateRegFromFwoCount, 0);
});

// "Pilih Semua (Tampil)" — cuma centang/uncheck checkbox yang sedang terlihat (hasil filter), bukan seluruh sample.
$(document).on('change', '#woRegFromFwoModal-check-all', function () {
    const checked = $(this).is(':checked');
    $('#woRegFromFwoModal-list .wo-reg-from-fwo-row:not(.d-none) .wo-reg-from-fwo-check').prop('checked', checked);
});

$(document).on('click', '#woRegFromFwoModal-btn-save', function () {
    const idWo = $('#woRegFromFwoModal').data('id-wo');
    const tanggalDiterima = $('#woRegFromFwoModal-tanggal').val();
    const idLabSample = $('.wo-reg-from-fwo-check:checked').map(function () { return $(this).val(); }).get();

    if (!tanggalDiterima) return Swal.fire('Perhatian', 'Tanggal Registrasi wajib diisi.', 'warning');
    if (!idLabSample.length) return Swal.fire('Perhatian', 'Pilih minimal 1 sample untuk diregistrasi.', 'warning');

    const payload = {
        _token: window.route.csrf,
        tanggal_registrasi: tanggalDiterima,
        id_personnel_penerima: $('#woRegFromFwoModal-personnel').val() || '',
        keterangan: $('#woRegFromFwoModal-keterangan').val().trim(),
        id_lab_sample: idLabSample,
    };

    $('#woRegFromFwoModal-btn-save').prop('disabled', true);
    $.post(`/wo-samples/by-wo/${idWo}/pull-from-fwo`, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('woRegFromFwoModal'))?.hide();
                afterLabRegRegistered(res, idWo);
            } else {
                Swal.fire('Gagal', res.message || 'Gagal registrasi sample.', 'error');
            }
        })
        .fail(function (xhr) {
            const errs = xhr.responseJSON?.errors;
            const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Gagal registrasi sample.');
            Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
        })
        .always(function () {
            $('#woRegFromFwoModal-btn-save').prop('disabled', false);
        });
});

$(document).on('change', '.wo-sample-inline-titik', function () {
    const $sel = $(this);
    const id   = $sel.data('id');
    const val  = $sel.val() || null;

    // Update data-current lalu reinit semua titik dalam tabel yang sama
    $sel.data('current', val || '');
    const $table = $sel.closest('table');
    initWoSampleTitikSelect2($table.closest('div')[0]);

    $.post(`/wo-samples/${id}/field`, { _token: window.route.csrf, field: 'titik_lokasi', value: val })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan titik lokasi.');
        });
});

$(document).on('change', '.wo-sample-inline-status', function () {
    const id   = $(this).data('id');
    const val  = $(this).val();
    const idWo = labRegWoId;
    $.post(`/wo-samples/${id}/field`, { _token: window.route.csrf, field: 'status', value: val })
        .done(function () { loadWoSampleData(idWo); })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan status.');
        });
});

$(document).on('click', '.btn-wo-sample-bulk-fill', function (e) {
    e.stopPropagation();
    const idBoq  = $(this).data('id-boq');
    const $modal = $('#woSampleBulkFillModal');
    $modal.find('#woBulkFillModal-id-boq').val(idBoq);
    $modal.find('#woBulkFill-jenis, #woBulkFill-status, #woBulkFill-kondisi').val('');
    $modal.find('#woBulkFill-tanggal').val('');
    new bootstrap.Modal($modal[0]).show();
    initFpDate('#woSampleBulkFillModal');
});

$(document).on('click', '#woSampleBulkFillModal-btn-save', function () {
    const idBoq = $('#woBulkFillModal-id-boq').val();
    const idWo  = labRegWoId;
    const $btn  = $(this);

    const payload = {
        _token:              window.route.csrf,
        jenis_sample:        $('#woBulkFill-jenis').val() || null,
        tanggal_pengambilan: $('#woBulkFill-tanggal').val() || null,
        status:              $('#woBulkFill-status').val() || null,
        kondisi_sample:      $('#woBulkFill-kondisi').val() || null,
    };

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.post(`/wo-samples/by-wo/${idWo}/boq/${idBoq}/bulk-fill`, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('woSampleBulkFillModal'))?.hide();
                Notify.success('Semua sample berhasil diperbarui.');
                loadWoSampleData(idWo);
            } else {
                Notify.error(res.message || 'Gagal memperbarui sample.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal memperbarui sample.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Apply ke Semua Sample'); });
});

$(document).on('click', '.btn-wo-sample-edit', function (e) {
    e.stopPropagation();
    const idSample = $(this).data('id');
    const $tr      = $(this).closest('tr');
    const boqName  = $tr.data('boq-name') || '';
    const preview  = String($(this).data('preview')) === '1'; // sample asal FWO: hanya lihat

    const $modal = $('#woSampleDetailModal');
    $modal.find('#woSampleDetailModalLabel').html(`<i class="fa-solid fa-flask me-2" style="color:#0369a1;"></i>${preview ? 'Preview' : 'Detail'} Sample – ${escHtml(boqName)}`);
    $modal.find('input:not([type=hidden]), select, textarea').prop('disabled', false);
    $modal.find('#woSampleModal-btn-save').toggle(!preview);
    $modal.find('#woSampleModal-attachments').closest('.col-12').find('.filepond--root, input[type=file]').show();
    $modal.find('.wo-sample-preview-note').remove();
    $modal.find('#woSampleModal-id').val(idSample);
    $modal.find('#woSampleModal-jenis').val('');
    $modal.find('#woSampleModal-no').val('');
    $modal.find('#woSampleModal-no-reg-lab').val('');
    $modal.find('#woSampleModal-tanggal').val('');
    const $titikSelWo = $modal.find('#woSampleModal-titik');
    $titikSelWo.data('_pendingVal', '');
    if ($titikSelWo.data('select2')) $titikSelWo.val(null).trigger('change');
    $modal.find('#woSampleModal-kondisi').val('');
    $modal.find('#woSampleModal-status').val('belum_diambil');
    $modal.find('#woSampleModal-keterangan').val('');
    $modal.find('#woSampleModal-existing-files').empty();
    if (woSampleFilePond) { woSampleFilePond.destroy(); woSampleFilePond = null; }

    $.get(`/wo-samples/${idSample}`)
        .done(function (res) {
            const s = res.data || {};
            $modal.find('#woSampleModal-jenis').val(s.jenis_sample || '');
            $modal.find('#woSampleModal-no').val(s.no_sample || '');
            $modal.find('#woSampleModal-no-reg-lab').val(s.no_reg_lab || '');
            $modal.find('#woSampleModal-tanggal').val(s.tanggal_pengambilan || '');
            $modal.find('#woSampleModal-titik').data('_pendingVal', s.titik_lokasi || '');
            $modal.find('#woSampleModal-kondisi').val(s.kondisi_sample || '');
            $modal.find('#woSampleModal-status').val(s.status || 'belum_diambil');
            $modal.find('#woSampleModal-keterangan').val(s.keterangan || '');

            const files = s.attachments || [];
            const filesHtml = files.length
                ? files.map(f => `<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">
                        <i class="fa-solid fa-paperclip"></i>
                        <span class="text-truncate" style="max-width:260px;" title="${escHtml(filePreviewName(f))}">${escHtml(filePreviewName(f))}</span>
                        ${renderFilePreviewButton([f])}
                        <a href="#" class="btn-remove-existing-wo-sample-file" style="font-size:11px;color:#dc2626;cursor:pointer;">Hapus</a>
                        <input type="hidden" class="existing-wo-sample-file-path" value="${escHtml(f)}">
                    </div>`).join('')
                : '';
            $modal.find('#woSampleModal-existing-files').html(filesHtml);
            if (preview) $modal.find('.btn-remove-existing-wo-sample-file').remove();
        })
        .always(function () {
            if (woSampleFilePond) { woSampleFilePond.destroy(); woSampleFilePond = null; }
            if (!preview) woSampleFilePond = createFileUploader('#woSampleModal-attachments');

            // Init Select2 untuk field enum
            const s2OptsWo = { width: '100%', dropdownParent: $modal, allowClear: true };
            ['#woSampleModal-jenis', '#woSampleModal-kondisi', '#woSampleModal-status'].forEach(function (sel) {
                const $el = $modal.find(sel);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                $el.select2({ ...s2OptsWo, placeholder: $el.find('option:first').text() || '-- Pilih --' });
                $el.trigger('change');
            });

            // Re-init Select2 titik lokasi (supaya options ter-refresh & value bisa di-set)
            initWoSampleModalTitikSelect2();
            const curTitikWo = $modal.find('#woSampleModal-titik').data('_pendingVal');
            if (curTitikWo !== undefined) {
                $modal.find('#woSampleModal-titik').val(curTitikWo || null).trigger('change');
                $modal.find('#woSampleModal-titik').removeData('_pendingVal');
            }

            new bootstrap.Modal($modal[0]).show();
            initFpDate('#woSampleDetailModal');

            if (preview) {
                $modal.find('input:not([type=hidden]), select, textarea').prop('disabled', true);
                $modal.find('#woSampleModal-attachments').hide();
                $modal.find('#woSampleModal-existing-files').after('<div class="wo-sample-preview-note text-muted" style="font-size:11px;">Sample ini berasal dari FWO — ubah datanya lewat tab Sample di FWO.</div>');
            }
        });
});

$(document).on('click', '#woSampleModal-btn-save', function () {
    const idSample = $('#woSampleDetailModal #woSampleModal-id').val();
    if (!idSample) return;

    const $btn  = $(this);
    const idWo  = labRegWoId;

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('jenis_sample', $('#woSampleModal-jenis').val() || '');
    fd.append('tanggal_pengambilan', $('#woSampleModal-tanggal').val() || '');
    fd.append('titik_lokasi', $('#woSampleModal-titik').val() || '');
    fd.append('kondisi_sample', $('#woSampleModal-kondisi').val() || '');
    fd.append('status', $('#woSampleModal-status').val());
    fd.append('keterangan', $('#woSampleModal-keterangan').val().trim());

    $('#woSampleModal-existing-files .existing-wo-sample-file-path').each(function () {
        fd.append('existing_attachments[]', $(this).val());
    });
    if (woSampleFilePond) {
        woSampleFilePond.getFiles().forEach(function (f) {
            fd.append('attachments[]', f.file);
        });
    }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.ajax({
        url: `/wo-samples/${idSample}`,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
    })
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('woSampleDetailModal'))?.hide();
                Notify.success('Sample berhasil disimpan.');
                loadWoSampleData(idWo);
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan sample.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

$(document).on('click', '.btn-remove-existing-wo-sample-file', function (e) {
    e.preventDefault();
    $(this).closest('div').remove();
});

$('#woSampleDetailModal').on('hidden.bs.modal', function () {
    if (woSampleFilePond) { woSampleFilePond.destroy(); woSampleFilePond = null; }
});

$(document).on('click', '.btn-wo-sample-delete', function (e) {
    e.stopPropagation();
    const id   = $(this).data('id');
    const no   = $(this).data('no');
    const idWo = labRegWoId;

    Swal.fire({
        title: 'Hapus Sample?',
        html: `Sample <strong>${escHtml(no)}</strong> akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/wo-samples/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            success: function () {
                Notify.success('Sample berhasil dihapus.');
                loadWoSampleData(idWo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menghapus sample.');
            },
        });
    });
});

// ── Collapse / Expand panel BOQ (dipindah dari work-order/index.js) ──
$(document).off('click.wosample', '.btn-wo-sample-collapse').on('click.wosample', '.btn-wo-sample-collapse', function () {
    const $icon   = $(this).find('i');
    const $target = $($(this).data('target'));
    $target.slideToggle(180, function () {
        const visible = $target.is(':visible');
        $icon.toggleClass('fa-chevron-up', visible).toggleClass('fa-chevron-down', !visible);
        const id = $target.attr('id');
        if (visible) woSampleOpenPanels.add(id); else woSampleOpenPanels.delete(id);
    });
});

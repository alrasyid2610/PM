@extends('layouts.app')
@section('page-title', 'Fieldwork')
@section('page-descrip', 'Tambah data kegiatan pekerjaan lapangan')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('fieldworks.index') }}">Fieldwork</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="30" width="60" height="40" rx="4" stroke="white" stroke-width="3"/>
        <path d="M28 30V22a12 12 0 0 1 24 0v8" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="40" cy="50" r="5" fill="white"/>
        <path d="M40 55v8" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')

{{-- STICKY WO INFO BANNER --}}
<div id="woInfoBanner" style="display:none;position:sticky;top:0;z-index:100;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-8px -12px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
            <i class="fa-solid fa-location-dot" style="color:#0891b2;font-size:11px;flex-shrink:0;"></i>
            <span id="woBannerSite" style="color:#0e7490;font-weight:600;white-space:nowrap;"></span>
        </div>
        <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
            <i class="fa-solid fa-briefcase" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
            <span id="woBannerNoWo" style="font-weight:700;color:#1a56db;white-space:nowrap;"></span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
            <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
            <span id="woBannerJudul" style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
        </div>
    </div>
</div>

<section class="section">
    <form id="fieldworkForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-hard-hat" color="icon-amber" title="Informasi Fieldwork" subtitle="Data kegiatan pekerjaan lapangan">
                <div class="row g-3">

                    <div class="col-md-12" id="woFieldWrapper">
                        <label class="form-label required">Work Order</label>
                        <select name="id_wo" id="create_id_wo" class="form-select" required>
                            <option value="">-- Pilih Work Order --</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label required">Judul Pekerjaan</label>
                        <input type="text" name="judul_pekerjaan" class="form-control" required maxlength="500"
                            placeholder="Judul kegiatan fieldwork">
                    </div>

                    <div class="col-md-6" id="siteFieldWrapper">
                        <label class="form-label required">Site Pelanggan</label>
                        <select name="id_site_pelanggan_pekerjaan" id="create_id_site" class="form-select" required>
                            <option value="">-- Pilih Site --</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">PIC Pelanggan</label>
                        <select name="id_pic_pelanggan_pekerjaan" id="create_id_pic" class="form-select" required>
                            <option value="">-- Pilih PIC --</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Waktu Kedatangan</label>
                        <input type="datetime-local" name="waktu_kedatangan" class="form-control">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Opsional"></textarea>
                    </div>

                </div>
            </x-section-card>
        </div>

        <!-- SECTION: PERSONEL -->
        <div class="col-12">
            <x-section-card icon="fa-users" color="icon-purple" title="Personel" subtitle="Teknisi / personel yang bertugas pada fieldwork ini">
                <div id="personelContainer" class="d-flex flex-column gap-2 mb-3"></div>

                <div id="personelEmpty" class="text-center text-muted py-3" style="border:1px dashed #e2e8f0;border-radius:8px;">
                    <i class="fa-solid fa-users fa-lg d-block mb-2 opacity-25"></i>
                    <span style="font-size:13px;">Belum ada personel. Klik <strong>+ Tambah Personel</strong> di bawah.</span>
                </div>

                <button type="button" id="btnAddPersonel" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Personel
                </button>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('fieldworks.index') }}" submit-label="Simpan Fieldwork" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    var preselectWoId = new URLSearchParams(window.location.search).get('id_wo');

    function noResultsAdd(createUrl) {
        return {
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="${createUrl}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        };
    }

    $(document).ready(function () {
        // WO Select2 (hanya jika tidak preselect)
        if (!preselectWoId) {
            $('#create_id_wo').select2({
                width: '100%',
                placeholder: 'Ketik No WO...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('work-orders.select2') }}",
                    dataType: 'json',
                    delay: 250,
                    data: p => ({ q: p.term }),
                    processResults: d => ({ results: d }),
                    cache: true,
                },
            });

            $('#create_id_wo').on('select2:select', function (e) {
                $('input[name="judul_pekerjaan"]').val(e.params.data.judul || '');
            });
        }

        // Auto-fill dan lock dari ?id_wo=
        if (preselectWoId) {
            $.get("{{ url('work-orders') }}/" + preselectWoId + "/detail", function (res) {
                if (!res || !res.id_wo) return;

                // Lock WO field
                const woLabel = (res.no_wo ?? '') + (res.judul_pekerjaan ? ' — ' + res.judul_pekerjaan : '');
                $('#woFieldWrapper').html(`
                    <label class="form-label required">Work Order</label>
                    <input type="hidden" name="id_wo" value="${res.id_wo}">
                    <p class="form-control mb-0" style="background:#f8fafc;color:#374151;">${escHtml(woLabel)}</p>
                `);

                // Lock judul pekerjaan
                $('input[name="judul_pekerjaan"]')
                    .val(res.judul_pekerjaan ?? '')
                    .prop('readonly', true)
                    .css({ background: '#f8fafc', color: '#374151' });

                // Lock site pelanggan dari WO
                const siteNama = res['Site Pelanggan'] ?? '—';
                const siteId   = res.id_site_pelanggan_pekerjaan ?? '';
                $('#create_id_site').select2('destroy');
                $('#siteFieldWrapper').html(`
                    <label class="form-label required">Site Pelanggan</label>
                    <input type="hidden" name="id_site_pelanggan_pekerjaan" value="${siteId}">
                    <p class="form-control mb-0" style="background:#f8fafc;color:#374151;">${escHtml(siteNama)}</p>
                `);

                // Tampilkan sticky banner
                $('#woBannerNoWo').text(res.no_wo ?? '—');
                $('#woBannerJudul').text(res.judul_pekerjaan ?? '—');
                $('#woBannerSite').text(siteNama);
                $('#woInfoBanner').show();
            });
        }

        $('#create_id_site').select2({
            width: '100%',
            placeholder: 'Ketik nama site...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-sites.select2') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d }),
                cache: true,
            },
            ...noResultsAdd('/business-relations/create'),
        });

        $('#create_id_pic').select2({
            width: '100%',
            placeholder: 'Ketik nama PIC...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-contacts.select2') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d }),
                cache: true,
            },
            ...noResultsAdd('/business-relation-contacts/create'),
        });
    });

    // ── Personel dynamic rows ────────────────────────────────────────────────
    let personelIdx = 0;

    function syncPersonelEmpty() {
        const hasRows = $('#personelContainer .personel-row').length > 0;
        $('#personelEmpty').toggle(!hasRows);
    }

    function addPersonelRow(userData, roleVal) {
        const idx = personelIdx++;
        const row = $(`
            <div class="personel-row d-flex align-items-start gap-2" data-idx="${idx}">
                <div style="flex:1;min-width:0;">
                    <label class="form-label form-label-sm text-muted mb-1">Personel</label>
                    <select name="personels[${idx}][id_user]"
                        class="form-select personel-user-select" required></select>
                </div>
                <div style="flex:1;min-width:0;">
                    <label class="form-label form-label-sm text-muted mb-1">Role</label>
                    <select name="personels[${idx}][role]" class="form-select personel-role-select">
                        <option value="">-- Pilih Role --</option>
                        <option value="Leader"  ${roleVal === 'Leader'  ? 'selected' : ''}>Leader</option>
                        <option value="Driver"  ${roleVal === 'Driver'  ? 'selected' : ''}>Driver</option>
                        <option value="Anggota" ${roleVal === 'Anggota' ? 'selected' : ''}>Anggota</option>
                    </select>
                </div>
                <div style="padding-top:26px;flex-shrink:0;">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-personel"
                        title="Hapus personel">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        `);

        const $select = row.find('.personel-user-select');
        $('#personelContainer').append(row);

        $select.select2({
            width: '100%',
            placeholder: 'Ketik nama personel...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('users.select2') }}",
                dataType: 'json',
                delay: 200,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d }),
                cache: true,
            },
        });

        row.find('.personel-role-select').select2({
            width: '100%',
            placeholder: '-- Pilih Role --',
            allowClear: false,
            minimumResultsForSearch: Infinity,
        });

        if (userData) {
            const opt = new Option(userData.text, userData.id, true, true);
            $select.append(opt).trigger('change');
        }

        syncPersonelEmpty();
    }

    $('#btnAddPersonel').on('click', function () {
        addPersonelRow(null, '');
    });

    $(document).on('click', '.btn-remove-personel', function () {
        $(this).closest('.personel-row').remove();
        syncPersonelEmpty();
    });

    syncPersonelEmpty();

    // ── Submit ───────────────────────────────────────────────────────────────
    submitCreateForm({
        formId: '#fieldworkForm',
        url: "{{ route('fieldworks.store') }}",
        onSuccess: preselectWoId ? function (res) {
            localStorage.setItem('fwo_created', JSON.stringify({ id_wo: preselectWoId, ts: Date.now() }));
            if (window.opener && !window.opener.closed) window.opener.focus();
            setTimeout(function () { window.close(); }, 800);
        } : null,
        redirect: preselectWoId ? null : "{{ route('fieldworks.index') }}",
    });
</script>
@endsection

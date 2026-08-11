@extends('layouts.app')

@section('page-title', 'Business Relations')
@section('page-descrip', 'Kelola data Business Relations')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('business-relations.index') }}">Business Relations</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createBusinessRelationForm" class="row g-3">
        @csrf
        <input type="hidden" name="id_br" id="id_br">
        <input type="hidden" name="nama_br" id="nama_br_hidden">
        <input type="hidden" name="site_id" id="site_id_hidden">

        <!-- SECTION 1: BUSINESS RELATION -->
        <div class="col-12">
            <x-section-card icon="fa-building" color="icon-navy" title="Business Relation" subtitle="Data utama perusahaan klien">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label required">Nama Business Relation</label>
                        <select id="nama_br" name="nama" class="form-select" style="width:100%"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Entitas</label>
                        <select name="id_entitas" id="entitas" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kepemilikan</label>
                        <select name="id_kepemilikan" id="kepemilikan" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">NPWP</label>
                        <input type="text" name="npwp" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kategori Bisnis</label>
                        <select name="id_kategori_bisnis" id="kategori_bisnis" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Sub Kategori Bisnis</label>
                        <select name="id_sub_kategori_bisnis" id="sub_kategori_bisnis" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="nomor_telepon" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" id="br_is_aktif" class="form-select">
                            <option value="">Pilih Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Alamat NPWP</label>
                        <textarea name="npwp_alamat" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 2: BUSINESS RELATION SITE -->
        <div class="col-12">
            <x-section-card icon="fa-location-dot" color="icon-blue" title="Business Relation Site" subtitle="Data Site">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Site</label>
                        <select id="site_id" class="form-select" style="width:100%"></select>
                        <small class="text-muted">Pilih Site yang sudah ada atau ketik untuk menambahkan Site baru.</small>
                    </div>
                    <div class="col-md-7 col-12">
                        <label class="form-label required">Nama Site</label>
                        <input type="text" name="nama_lokasi" class="form-control" required>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">NPWP</label>
                        <input type="text" name="npwp_cabang" class="form-control">
                    </div>
    
                    <div class="col-md-2">
                        <label class="form-label" for="is_kantor_pusat">
                            Kantor Pusat
                        </label>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_kantor_pusat"
                                id="is_kantor_pusat"
                                class="form-check-input"
                                value="1">
                            {{-- <label class="form-check-label" for="is_kantor_pusat">
                                Kantor Pusat
                            </label> --}}
                        </div>
                    </div>
                    
                    <div class="col-md-4 col-12">
                        <label class="form-label">Provinsi</label>
                        <select name="provinsi" class="form-select wilayah-provinsi" data-value=""></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kota / Kabupaten</label>
                        <select name="kota_kabupaten" class="form-select wilayah-kota" data-value=""></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan" class="form-select wilayah-kecamatan" data-value=""></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kelurahan</label>
                        <select name="kelurahan" class="form-select wilayah-kelurahan" data-value=""></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="kode_pos" class="form-control">
                    </div>
                    <div class="col-md-5 col-12">
                        <label class="form-label">Kawasan Bisnis</label>
                        <select name="kawasan_bisnis" id="kawasan_bisnis" class="form-select"></select>
                    </div>
                    <div class="col-md-5 col-12">
                        <label class="form-label">Gedung</label>
                        <select name="gedung" id="gedung" class="form-select"></select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" id="site_is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
    
                    <div class="col-md-12">
                        <label class="form-label">Nama Jalan</label>
                        <input type="text" name="nama_jalan" class="form-control">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan Alamat</label>
                        <textarea name="keterangan_alamat" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('business-relations.index') }}" submit-label="Simpan Data" submit-id="btnSubmit" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    const EDIT_BR = @json($br ?? null);
    const EDIT_SITE = @json($site ?? null);
</script>

<script>
    $(document).ready(function () {
        initBrSelect2();

        if (!EDIT_BR) {
            resetCreateForm();
            destroySiteSelect2();
        } else {
            initEditBr(EDIT_BR);
        }

        if (EDIT_SITE) {
            initEditSite(EDIT_SITE);
        } else {
            WilayahEngine.init('body');
        }

        initLookupSelect2("#entitas", "{{ route('entitas.select2') }}", "Pilih Entitas", "{{ route('entitas.create') }}");
        initLookupSelect2("#kepemilikan", "{{ route('kepemilikan.select2') }}", "Pilih Kepemilikan", "{{ route('kepemilikan.create') }}");
        initLookupSelect2("#kategori_bisnis", "{{ route('kategori-bisnis.select2') }}", "Pilih Kategori Bisnis", "{{ route('kategori-bisnis.create') }}");
        initSubKategoriSelect2();

        // Ganti Kategori Bisnis → Sub Kategori Bisnis lama sudah tentu tidak valid lagi
        $("#kategori_bisnis").on('select2:select select2:clear', function () {
            $("#sub_kategori_bisnis").val(null).trigger('change');
        });

        $("#br_is_aktif").select2({ placeholder: 'Pilih Status', allowClear: true, width: '100%' });
        $("#site_is_aktif").select2({ placeholder: 'Pilih Status', allowClear: true, width: '100%' });

        $("#kawasan_bisnis").select2({
            placeholder: 'Pilih Kawasan Bisnis',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('business-estates.select2') }}",
                dataType: 'json',
                delay: 0,
                data: params => ({ q: params.term ?? '' }),
                processResults: data => ({ results: data }),
                cache: true,
            },
        });

        $("#gedung").select2({
            placeholder: 'Pilih Gedung',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('commercial-buildings.select2') }}",
                dataType: 'json',
                delay: 0,
                data: params => ({ q: params.term ?? '' }),
                processResults: data => ({ results: data }),
                cache: true,
            },
        });

    });

    function initBrSelect2() {
        console.log('Done init BR select2');
        $('#nama_br').select2({
            placeholder: 'Pilih atau ketik Business Relation',
            tags: true,
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relations.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                }
            }
        });
    }

    // Select2 ajax untuk master data lookup (Entitas/Kepemilikan/Kategori Bisnis)
    function initLookupSelect2(selector, url, placeholder, createUrl) {
        $(selector).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term ?? '' }),
                processResults: data => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: () => `<span>Tidak ditemukan. <a href="${createUrl}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
            },
            escapeMarkup: (m) => m,
        });
    }

    // Sub Kategori Bisnis: opsional di-filter oleh Kategori Bisnis yang sedang dipilih
    function initSubKategoriSelect2() {
        if ($('#sub_kategori_bisnis').hasClass('select2-hidden-accessible')) {
            $('#sub_kategori_bisnis').select2('destroy');
        }
        $('#sub_kategori_bisnis').select2({
            placeholder: 'Pilih Sub Kategori Bisnis',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('sub-kategori-bisnis.select2') }}",
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term ?? '', id_kategori_bisnis: $('#kategori_bisnis').val() }),
                processResults: data => ({ results: data }),
                cache: false,
            },
            language: {
                noResults: () => `<span>Tidak ditemukan. <a href="{{ route('sub-kategori-bisnis.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
            },
            escapeMarkup: (m) => m,
        });
    }

    // Isi ulang select2 lookup dengan 1 opsi terpilih (dari data id+nama hasil AJAX/autofill)
    function setLookupValue(selector, id, nama) {
        $(selector).empty();
        if (id && nama) {
            $(selector).append(new Option(nama, id, true, true));
        }
        $(selector).trigger('change');
    }

    function initEditBr(br) {
        $('#id_br').val(br.id_br);

        const brOption = new Option(br.nama, br.id_br, true, true);
        $('#nama_br').append(brOption).trigger('change');

        setLookupValue('#entitas', br.id_entitas, br.nama_entitas);
        setLookupValue('#kepemilikan', br.id_kepemilikan, br.nama_kepemilikan);
        $('input[name="npwp"]').val(br.npwp);
        $('textarea[name="npwp_alamat"]').val(br.npwp_alamat);
        setLookupValue('#kategori_bisnis', br.id_kategori_bisnis, br.nama_kategori_bisnis);
        setLookupValue('#sub_kategori_bisnis', br.id_sub_kategori_bisnis, br.nama_sub_kategori_bisnis);
        $('input[name="website"]').val(br.website);
        $('input[name="nomor_telepon"]').val(br.nomor_telepon);
        $('select[name="is_aktif"]').val(br.is_aktif ?? 1).trigger('change');

        destroySiteSelect2();
        initSiteSelect2(br.id_br);
    }

    function initEditSite(site) {
        $('#site_id_hidden').val(site.id_site);

        const siteOption = new Option(site.nama_lokasi, site.id_site, true, true);
        $('#site_id').append(siteOption).trigger('change');

        $('input[name="nama_lokasi"]').val(site.nama_lokasi);
        $('textarea[name="alamat_lengkap"]').val(site.alamat_lengkap);
        $('.wilayah-provinsi').data('value', site.provinsi ?? '');
        $('.wilayah-kota').data('value', site.kota_kabupaten ?? '');
        $('.wilayah-kecamatan').data('value', site.kecamatan ?? '');
        $('.wilayah-kelurahan').data('value', site.kelurahan ?? '');
        WilayahEngine.init('body');
        $('input[name="kode_pos"]').val(site.kode_pos);
        if (site.id_bestate && site.nama_kawasan_bisnis) {
            $('#kawasan_bisnis').append(new Option(site.nama_kawasan_bisnis, site.id_bestate, true, true)).trigger('change');
        }
        if (site.id_building && site.nama_gedung) {
            $('#gedung').append(new Option(site.nama_gedung, site.id_building, true, true)).trigger('change');
        }
        $('input[name="alamat"]').val(site.alamat);
        $('input[name="npwp_cabang"]').val(site.npwp_cabang);
    }

    $('#nama_br').on('select2:select', function (e) {
        console.log('BR selected:', e.params.data);
        const d = e.params.data;
        if (d.id && !isNaN(d.id)) {
            $('#id_br').val(d.id);
            $("#nama_br_hidden").val(d.text);

            $('input[name="npwp"]').val(d.npwp ?? '');
            $('textarea[name="npwp_alamat"]').val(d.npwp_alamat ?? '');
            setLookupValue('#kategori_bisnis', d.id_kategori_bisnis, d.nama_kategori_bisnis);
            setLookupValue('#sub_kategori_bisnis', d.id_sub_kategori_bisnis, d.nama_sub_kategori_bisnis);
            $('input[name="website"]').val(d.website ?? '');
            $('input[name="nomor_telepon"]').val(d.nomor_telepon ?? '');

            setLookupValue('#entitas', d.id_entitas, d.nama_entitas);
            setLookupValue('#kepemilikan', d.id_kepemilikan, d.nama_kepemilikan);
            $('select[name="is_aktif"]').val(d.is_aktif ?? 1).trigger('change');

            destroySiteSelect2();
            initSiteSelect2(d.id);
            clearSiteField();
        } else {
            $('#id_br').val('');
            console.log('New BR name entered:', d);
            $("#nama_br_hidden").val(d.text);
            clearBrField();
            destroySiteSelect2();
            clearSiteField();
        }
    });

    $('#nama_br').on('select2:clear', function () {
        console.log('BR cleared');
        $("#nama_br").html('');
        destroySiteSelect2();
        clearSiteField();
        clearBrField();
    });

    function initSiteSelect2(idBr) {
        console.log('Init Site select2 with BR id:', idBr);
        $('#site_id')
            .prop('disabled', false)
            .select2({
                placeholder: 'Pilih atau ketik lokasi / cabang',
                tags: true,
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') return null;
                    return { id: term, text: term, newTag: true };
                },
                allowClear: true,
                ajax: {
                    url: `/business-relations/${idBr}/sites`,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data })
                }
            });
        console.log('Site select2 initialized');

        $('#site_id').on('select2:select', function (e) {
            const d = e.params.data;
            console.log('Data diterima:', d);

            const isExistingData = d.id && !isNaN(d.id) && !d.newTag;

            if (isExistingData) {
                $('input[name="nama_lokasi"]').val(d.nama_lokasi ?? '');
                $('textarea[name="alamat_lengkap"]').val(d.alamat_lengkap ?? '');

                $('.wilayah-provinsi').data('value', d.provinsi ?? '');
                $('.wilayah-kota').data('value', d.kota_kabupaten ?? '');
                $('.wilayah-kecamatan').data('value', d.kecamatan ?? '');
                $('.wilayah-kelurahan').data('value', d.kelurahan ?? '');
                WilayahEngine.init('body');

                $('input[name="kode_pos"]').val(d.kode_pos ?? '');
                $('input[name="nama_jalan"]').val(d.nama_jalan ?? '');
                $('textarea[name="keterangan_alamat"]').val(d.keterangan_alamat ?? '');
                $('input[name="npwp_cabang"]').val(d.npwp_cabang ?? '');
                $('#site_is_aktif').val(d.is_aktif ?? 1).trigger('change');

                $('#kawasan_bisnis').empty();
                if (d.kawasan_bisnis && d.nama_kawasan_bisnis) {
                    $('#kawasan_bisnis').append(new Option(d.nama_kawasan_bisnis, d.kawasan_bisnis, true, true));
                }
                $('#kawasan_bisnis').trigger('change');

                $('#gedung').empty();
                if (d.gedung && d.nama_gedung) {
                    $('#gedung').append(new Option(d.nama_gedung, d.gedung, true, true));
                }
                $('#gedung').trigger('change');

                $('#site_id_hidden').val(d.id);
            } else {
                clearSiteField();
                console.log('User mengetik data baru:', d.text);
                $('input[name="nama_lokasi"]').val(d.text);
                $('#site_id_hidden').val('');
            }
        });
    }

    function destroySiteSelect2() {
        if ($('#site_id').hasClass('select2-hidden-accessible')) {
            $('#site_id').select2('destroy');
        }
        $('#site_id').prop('disabled', true).empty();
    }

    $('#site_id').on('select2:clear', function () {
        $("#site_id").html('');
        clearSiteField();
    });

    function resetCreateForm() {
        const form = document.getElementById('createBusinessRelationForm');
        if (form) form.reset();

        $('#id_br').val('');
        $('#site_id_hidden').val('');

        if ($('#nama_br').hasClass('select2-hidden-accessible')) {
            $('#nama_br').val(null).trigger('change');
        }

        destroySiteSelect2();

        $('#btnSubmit').prop('disabled', false).text('Simpan Data');
    }

    function clearSiteField() {
        $('#site_id_hidden').val('');
        const fields = ['nama_lokasi', 'alamat_lengkap', 'kode_pos', 'alamat', 'npwp_cabang'];
        fields.forEach(name => $(`[name="${name}"]`).val(''));
        WilayahEngine.reset('.wilayah-provinsi', '.wilayah-kota', '.wilayah-kecamatan', '.wilayah-kelurahan');
        $('#kawasan_bisnis').val(null).trigger('change');
        $('#gedung').val(null).trigger('change');
    }

    function clearBrField() {
        $('#id_br').val('');
        ['npwp', 'npwp_alamat', 'website', 'nomor_telepon'].forEach(name => {
            $(`[name="${name}"]`).val('');
        });
        setLookupValue('#entitas', null, null);
        setLookupValue('#kepemilikan', null, null);
        setLookupValue('#kategori_bisnis', null, null);
        setLookupValue('#sub_kategori_bisnis', null, null);
        $('#br_is_aktif').val('').trigger('change');
    }

    $('#createBusinessRelationForm').on('submit', function (e) {
        e.preventDefault();

        // Pastikan nama_br_hidden terisi dari nilai Select2 saat ini
        if (!$('#nama_br_hidden').val()) {
            const selected = $('#nama_br').select2('data');
            if (selected && selected.length && selected[0].text) {
                $('#nama_br_hidden').val(selected[0].text);
            }
        }

        const form = $(this);
        const btn = $('#btnSubmit');

        btn.prop('disabled', true).text('Menyimpan...');

        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ route('business-relations.store') }}",
                type: "POST",
                data: form.serialize(),
                success: function (res) {
                    Notify.success('Data berhasil disimpan!');
                    window.location.href = "{{ route('business-relations.index') }}?open=" + res.id;
                },
                error: function (xhr) {
                    btn.prop('disabled', false).text('Simpan Data');

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let msg = Object.values(errors).map(e => e[0]).join('<br>');
                        Notify.error(msg);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ?? 'Terjadi kesalahan'
                        });
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).text('Simpan Data');
                }
            });
        });

    });
</script>
@endsection

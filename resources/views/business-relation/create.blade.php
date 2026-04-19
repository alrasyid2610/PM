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
    <form id="createBusinessRelationForm">
        @csrf
        <input type="hidden" name="id_br" id="id_br">
        <input type="hidden" name="nama_br" id="nama_br_hidden">
        <input type="hidden" name="site_id" id="site_id_hidden">

        <!-- SECTION 1: BUSINESS RELATION -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="detail-section-title">Business Relation</div>
                <div class="detail-section-sub">Data utama perusahaan klien</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label required">Nama Business Relation</label>
                        <select id="nama_br" name="nama" class="form-select" style="width:100%"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Entitas</label>
                        <select name="entitas" class="form-select">
                            <option value="">Pilih Entitas</option>
                            <option value="Perseroan Terbatas">Perseroan Terbatas</option>
                            <option value="Commanditaire Vennootschap">Commanditaire Vennootschap</option>
                            <option value="Firma">Firma</option>
                            <option value="Koperasi">Koperasi</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kepemilikan</label>
                        <select name="kepemilikan" class="form-select">
                            <option value="">Pilih Kepemilikan</option>
                            <option value="Swasta">Swasta</option>
                            <option value="BUMN/BUMD">BUMN/BUMD</option>
                            <option value="Pemerintah">Pemerintah</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">NPWP</label>
                        <input type="text" name="npwp" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kategori Bisnis</label>
                        <input type="text" name="kategori_bisnis" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Sub Kategori Bisnis</label>
                        <input type="text" name="sub_kategori_bisnis" class="form-control">
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
                        <select name="is_aktif" class="form-select">
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
            </div>
        </div>

        <!-- SECTION 2: BUSINESS RELATION SITE -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="detail-section-title">Business Relation Site</div>
                <div class="detail-section-sub">Data lokasi & cabang</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Lokasi / Cabang</label>
                        <select id="site_id" class="form-select" style="width:100%"></select>
                        <small class="text-muted">Pilih cabang yang sudah ada atau ketik untuk menambahkan cabang baru.</small>
                    </div>
                    <div class="col-md-8 col-12">
                        <label class="form-label required">Nama Lokasi</label>
                        <input type="text" name="nama_lokasi" class="form-control" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">NPWP Cabang</label>
                        <input type="text" name="npwp_cabang" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label required">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Provinsi</label>
                        <input type="text" name="provinsi" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kota / Kabupaten</label>
                        <input type="text" name="kota_kabupaten" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kecamatan</label>
                        <input type="text" name="kecamatan" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kelurahan</label>
                        <input type="text" name="kelurahan" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="kode_pos" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kawasan Bisnis</label>
                        <select name="kawasan_bisnis" id="kawasan_bisnis" class="select2 form-control">
                            @foreach ($commercial_buildings as $value)
                                <option value="{{ $value->id_building }}">{{ $value->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Gedung</label>
                        <select name="gedung" id="gedung" class="select2 form-control">
                            @foreach ($bestate as $value)
                                <option value="{{ $value->id_bestate }}">{{ $value->nama }} - {{ $value->kode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Alamat Tambahan</label>
                        <input type="text" name="alamat" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('business-relations.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data
            </button>
        </div>

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
        }

        $("#gedung").select2({
            placeholder: 'Pilih Business Estate',
            allowClear: true
        });

        $("#kawasan_bisnis").select2({
            placeholder: 'Pilih Kawasan Bisnis',
            allowClear: true
        });

    });

    function initBrSelect2() {
        console.log('Done init BR select2');
        $('#nama_br').select2({
            placeholder: 'Pilih atau ketik Business Relation',
            tags: true,
            allowClear: true,
            minimumInputLength: 2,
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

    function initEditBr(br) {
        $('#id_br').val(br.id_br);

        const brOption = new Option(br.nama, br.id_br, true, true);
        $('#nama_br').append(brOption).trigger('change');

        $('select[name="entitas"]').val(br.entitas).trigger('change');
        $('select[name="kepemilikan"]').val(br.kepemilikan).trigger('change');
        $('input[name="npwp"]').val(br.npwp);
        $('textarea[name="npwp_alamat"]').val(br.npwp_alamat);
        $('input[name="kategori_bisnis"]').val(br.kategori_bisnis);
        $('input[name="sub_kategori_bisnis"]').val(br.sub_kategori_bisnis);
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
        $('input[name="provinsi"]').val(site.provinsi);
        $('input[name="kota_kabupaten"]').val(site.kota_kabupaten);
        $('input[name="kecamatan"]').val(site.kecamatan);
        $('input[name="kelurahan"]').val(site.kelurahan);
        $('input[name="kode_pos"]').val(site.kode_pos);
        $('input[name="kawasan_bisnis"]').val(site.kawasan_bisnis);
        $('input[name="gedung"]').val(site.gedung);
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
            $('input[name="kategori_bisnis"]').val(d.kategori_bisnis ?? '');
            $('input[name="sub_kategori_bisnis"]').val(d.sub_kategori_bisnis ?? '');
            $('input[name="website"]').val(d.website ?? '');
            $('input[name="nomor_telepon"]').val(d.nomor_telepon ?? '');

            $('select[name="entitas"]').val(d.entitas ?? '').trigger('change');
            $('select[name="kepemilikan"]').val(d.kepemilikan ?? '').trigger('change');
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
                $('input[name="provinsi"]').val(d.provinsi ?? '');
                $('input[name="kota_kabupaten"]').val(d.kota_kabupaten ?? '');
                $('input[name="kecamatan"]').val(d.kecamatan ?? '');
                $('input[name="kelurahan"]').val(d.kelurahan ?? '');
                $('input[name="kode_pos"]').val(d.kode_pos ?? '');
                $('input[name="kawasan_bisnis"]').val(d.kawasan_bisnis ?? '');
                $('input[name="gedung"]').val(d.gedung ?? '');
                $('input[name="alamat"]').val(d.alamat ?? '');
                $('input[name="npwp_cabang"]').val(d.npwp_cabang ?? '');
                $('select[name="is_aktif"]').val(d.is_aktif ?? 1).trigger('change');
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
        const fields = [
            'nama_lokasi','alamat_lengkap','provinsi','kota_kabupaten',
            'kecamatan','kelurahan','kode_pos','kawasan_bisnis',
            'gedung','alamat','npwp_cabang'
        ];
        fields.forEach(name => {
            $(`[name="${name}"]`).val('');
        });
    }

    function clearBrField() {
        $('#id_br').val('');
        const fields = [
            'npwp','npwp_alamat','kategori_bisnis','sub_kategori_bisnis',
            'website','nomor_telepon'
        ];
        fields.forEach(name => {
            $(`[name="${name}"]`).val('');
        });
        $('select[name="entitas"]').val('').trigger('change');
        $('select[name="kepemilikan"]').val('').trigger('change');
        $('select[name="is_aktif"]').val(1).trigger('change');
    }

    $('#createBusinessRelationForm').on('submit', function (e) {
        e.preventDefault();

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
                    console.log(res);
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

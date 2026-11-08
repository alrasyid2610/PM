@extends('layouts.app')

@section('page-title', 'Business Relations')
@section('page-descrip', 'Tambah Site untuk ' . $br->nama_display)

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('business-relations.index') }}">Business Relations</a>
    </li>
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('business-relations.index') }}?open={{ $br->id_br }}&tab=tabBrsSite">{{ $br->nama_display }}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah Site</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M40 8c-12 0-22 9-22 21 0 16 22 43 22 43s22-27 22-43C62 17 52 8 40 8z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <circle cx="40" cy="29" r="8" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createSiteForm" class="row g-3">
        @csrf

        {{-- BR terkunci: user membuka halaman ini dari tab Site BR tersebut, jadi tidak perlu memilih BR lagi --}}
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 p-3" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;">
                <i class="fa-solid fa-building" style="color:#0369a1;font-size:18px;"></i>
                <div>
                    <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Business Relation</div>
                    <div style="font-weight:600;color:#0f172a;">{{ $br->nama_display }}</div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-location-dot" color="icon-blue" title="Business Relation Site" subtitle="Data Site baru">
                <div class="row g-3">
                    <div class="col-md-7 col-12">
                        <label class="form-label required">Nama Site</label>
                        <input type="text" name="nama_lokasi" class="form-control" required maxlength="255" autofocus>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">NPWP</label>
                        <input type="text" name="npwp_cabang" class="form-control" maxlength="25">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="is_kantor_pusat">Kantor Pusat</label>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_kantor_pusat" id="is_kantor_pusat" class="form-check-input" value="1">
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
                        <input type="text" name="kode_pos" class="form-control" maxlength="10">
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

        <x-form-actions back-route="{{ route('business-relations.index') }}?open={{ $br->id_br }}&tab=tabBrsSite" submit-label="Simpan Site" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        WilayahEngine.init('body');

        $('#site_is_aktif').select2({ width: '100%', minimumResultsForSearch: Infinity });

        function lookupSelect2(selector, url, placeholder) {
            $(selector).select2({
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 0,
                    data: params => ({ q: params.term ?? '' }),
                    processResults: data => ({ results: data }),
                    cache: true,
                },
            });
        }
        lookupSelect2('#kawasan_bisnis', "{{ route('business-estates.select2') }}", 'Pilih Kawasan Bisnis');
        lookupSelect2('#gedung', "{{ route('commercial-buildings.select2') }}", 'Pilih Gedung');
    });

    submitCreateForm({
        formId: "#createSiteForm",
        url: "{{ route('business-relations.sites.store', $br->id_br) }}",
        confirmMessage: "Simpan Site baru?",
        onSuccess: function (res) {
            // Kembali ke detail BR → tab Site → langsung buka workspace Site yang baru dibuat
            window.location.href = "{{ route('business-relations.index') }}?open=" + res.id_br + "&tab=tabBrsSite&site=" + res.id_site;
        },
    });
</script>
@endsection

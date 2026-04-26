@extends('layouts.app')

@section('page-title', 'Business Relations')
@section('page-descrip', 'Kelola data Business Relations')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('business-relations.index') }}">Business Relations</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
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
        <input type="hidden" name="site_id" id="site_id_hidden">

        <x-section-card icon="fa-building" color="icon-navy" title="Informasi Business Relation" subtitle="Data utama perusahaan klien">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required">Nama Business Relation</label>
                    <input type="text" name="nama_br" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entitas</label>
                    <select name="entitas" class="form-select">
                        <option value="">Pilih Entitas</option>
                        <option value="Perseroan Terbatas">Perseroan Terbatas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kepemilikan</label>
                    <select name="kepemilikan" class="form-select">
                        <option value="">Pilih Kepemilikan</option>
                        <option value="Swasta">Swasta</option>
                        <option value="BUMN/BUMD">BUMN/BUMD</option>
                        <option value="Pemerintah">Pemerintah</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Alamat NPWP</label>
                    <textarea name="npwp_alamat" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori Bisnis</label>
                    <input type="text" name="kategori_bisnis" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Kategori Bisnis</label>
                    <input type="text" name="sub_kategori_bisnis" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="nomor_telepon" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_aktif_br" class="form-select">
                        <option value="">Pilih Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non Aktif</option>
                    </select>
                </div>
            </div>
        </x-section-card>

        <x-section-card icon="fa-location-dot" color="icon-blue" title="Informasi Lokasi / Site" subtitle="Data lokasi & cabang">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label required">Nama Lokasi</label>
                    <input type="text" name="nama_lokasi" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NPWP Cabang</label>
                    <input type="text" name="npwp_cabang" class="form-control">
                </div>
                <div class="col-md-12">
                    <label class="form-label required">Alamat Lengkap</label>
                    <textarea name="alamat_lengkap" class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi" class="form-select wilayah-provinsi" data-value=""></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kota / Kabupaten</label>
                    <select name="kota_kabupaten" class="form-select wilayah-kota" data-value=""></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kecamatan</label>
                    <select name="kecamatan" class="form-select wilayah-kecamatan" data-value=""></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kelurahan</label>
                    <select name="kelurahan" class="form-select wilayah-kelurahan" data-value=""></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="kode_pos" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kawasan Bisnis</label>
                    <input type="text" name="kawasan_bisnis" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gedung</label>
                    <input type="text" name="gedung" class="form-control">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Alamat Tambahan</label>
                    <input type="text" name="alamat" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_aktif_site" class="form-select">
                        <option value="1">Aktif</option>
                        <option value="0">Non Aktif</option>
                    </select>
                </div>
            </div>
        </x-section-card>

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
    $('select[name="entitas"]').select2({ placeholder: 'Pilih Entitas', allowClear: true, width: '100%' });
    $('select[name="kepemilikan"]').select2({ placeholder: 'Pilih Kepemilikan', allowClear: true, width: '100%' });
    $('select[name="is_aktif_br"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    $('select[name="is_aktif_site"]').select2({ placeholder: 'Pilih Status', width: '100%' });

    initEditBr(EDIT_BR);
    initEditSite(EDIT_SITE);
});

function initEditBr(br) {
    $('#id_br').val(br.id_br);
    $('input[name="nama_br"]').val(br.nama);
    $('select[name="entitas"]').val(br.entitas).trigger('change');
    $('select[name="kepemilikan"]').val(br.kepemilikan).trigger('change');
    $('input[name="npwp"]').val(br.npwp);
    $('textarea[name="npwp_alamat"]').val(br.npwp_alamat);
    $('input[name="kategori_bisnis"]').val(br.kategori_bisnis);
    $('input[name="sub_kategori_bisnis"]').val(br.sub_kategori_bisnis);
    $('input[name="website"]').val(br.website);
    $('input[name="nomor_telepon"]').val(br.nomor_telepon);
    $('select[name="is_aktif_br"]').val(br.is_aktif ?? 1).trigger('change');
}

function initEditSite(site) {
    $('#site_id_hidden').val(site.id_site);
    $('input[name="nama_lokasi"]').val(site.nama_lokasi);
    $('textarea[name="alamat_lengkap"]').val(site.alamat_lengkap);
    $('.wilayah-provinsi').data('value', site.provinsi ?? '');
    $('.wilayah-kota').data('value', site.kota_kabupaten ?? '');
    $('.wilayah-kecamatan').data('value', site.kecamatan ?? '');
    $('.wilayah-kelurahan').data('value', site.kelurahan ?? '');
    WilayahEngine.init('body');
    $('input[name="kode_pos"]').val(site.kode_pos);
    $('input[name="kawasan_bisnis"]').val(site.kawasan_bisnis);
    $('input[name="gedung"]').val(site.gedung);
    $('input[name="alamat"]').val(site.alamat);
    $('input[name="npwp_cabang"]').val(site.npwp_cabang);
    $('select[name="is_aktif_site"]').val(site.is_aktif ?? 1).trigger('change');
}

$('#createBusinessRelationForm').on('submit', function (e) {
    e.preventDefault();

    const form = $(this);
    const btn  = $('#btnSubmit');

    btn.prop('disabled', true).text('Menyimpan...');

    Notify.confirm('Simpan Data?', function() {
        $.ajax({
            url: "/business-relations/" + $("#site_id_hidden").val(),
            type: "PUT",
            data: form.serialize(),
            success: function (res) {
                Notify.success('Data berhasil disimpan!');
                window.location.href = "{{ route('business-relations.index') }}";
            },
            error: function (xhr) {
                btn.prop('disabled', false).text('Simpan Data');

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let msg = Object.values(errors).map(e => e[0]).join('<br>');
                    Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: msg });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan' });
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

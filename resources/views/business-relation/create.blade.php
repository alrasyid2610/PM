@extends('layouts.app')

@section('content')
<style>
    .required::after {
        content: " *";
        color: red;
    }
</style>

<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">{{ session('mode') === 'Edit' ? 'Edit' : 'Tambah' }} Business Relation</h4>
                <p class="text-muted mb-0">
                    Tambahkan business relation baru atau kantor cabang dari data yang sudah ada.
                </p>
            </div>

            <a href="{{ route('business-relations.index') }}" class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        <form id="createBusinessRelationForm">
            @csrf
            <input type="hidden" name="id_br" id="id_br">
            <input type="hidden" name="nama_br" id="nama_br_hidden">
            <input type="hidden" name="site_id" id="site_id_hidden">

            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informasi Business Relation</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        {{-- Nama Business Relation (Select2) --}}
                        <div class="col-md-6">
                            <label class="form-label required">Nama Business Relation</label>
                            <select id="nama_br" name="nama" class="form-select" style="width:100%"></select>
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
                            <select name="is_aktif" class="form-select">
                                <option value="">Pilih Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            

            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informasi Lokasi / Site</strong>
                </div>

                <div class="card-body">
                    
                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label">Lokasi / Cabang</label>
                            <select id="site_id"
                                    class="form-select"
                                    style="width:100%">
                            </select>

                            <small class="text-muted">
                                Pilih cabang yang sudah ada atau ketik untuk menambahkan cabang baru.
                            </small>
                        </div>
                        

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
                            <input type="text" name="provinsi" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kota / Kabupaten</label>
                            <input type="text" name="kota_kabupaten" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kecamatan</label>
                            <input type="text" name="kecamatan" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kelurahan</label>
                            <input type="text" name="kelurahan" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kode Pos</label>
                            <input type="text" name="kode_pos" class="form-control">
                        </div>

                        {{-- <div class="col-md-4">
                            <label class="form-label">Kawasan Bisnis</label>
                            <input type="text" name="kawasan_bisnis" class="form-control">
                        </div> --}}

                        <div class="col-md-4">
                            <label class="form-label">Kawasan Bisnis</label>
                            <select name="kawasan_bisnis" id="kawasan_bisnis" class="select2 form-control">
                                @foreach ($commercial_buildings as $value)
                                    <option value="{{ $value->id_building }}">{{ $value->nama }}</option>
                                @endforeach
                            </select>
                            {{-- <input type="text" name="gedung" class="form-control"> --}}
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gedung</label>
                            <select name="gedung" id="gedung" class="select2 form-control">
                                @foreach ($bestate as $value)
                                    <option value="{{ $value->id_bestate }}">{{ $value->nama }} - {{ $value->kode }}</option>
                                @endforeach
                            </select>
                            {{-- <input type="text" name="gedung" class="form-control"> --}}
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Alamat Tambahan</label>
                            <input type="text" name="alamat" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_aktif" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>


                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('business-relations.index') }}"
                class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit"
                        class="btn btn-primary">
                    Simpan Data
                </button>
            </div>

        </form>
    </div>
</section>
@endsection

@section('custom-script')
<script>
    const EDIT_BR = @json($br ?? null);
    const EDIT_SITE = @json($site ?? null);
</script>

<script>
    $(document).ready(function () {
            // reset form ketika halaman dimuat
            // INIT select2 selalu
        initBrSelect2();

        if (!EDIT_BR) {
            // MODE CREATE
            resetCreateForm();
            destroySiteSelect2();
        } else {
            // MODE EDIT BR
            initEditBr(EDIT_BR);
        }

        if (EDIT_SITE) {
            initEditSite(EDIT_SITE);
        }
        
        // @if(isset($br) && $br)
        //     // set hidden id_br
        //     $('#id_br').val('{{ $br->id_br }}');

        //     // inject option ke select2 BR
        //     const brOption = new Option(
        //         '{{ $br->nama }}',
        //         '{{ $br->id_br }}',
        //         true,
        //         true
        //     );
        //     $('#nama_br').append(brOption).trigger('change');

        //     // isi field BR
        //     $('select[name="entitas"]').val('{{ $br->entitas }}').trigger('change');
        //     $('select[name="kepemilikan"]').val('{{ $br->kepemilikan }}').trigger('change');
        //     $('input[name="npwp"]').val('{{ $br->npwp }}');
        //     $('textarea[name="npwp_alamat"]').val(`{!! addslashes($br->npwp_alamat) !!}`);
        //     $('input[name="kategori_bisnis"]').val('{{ $br->kategori_bisnis }}');
        //     $('input[name="sub_kategori_bisnis"]').val('{{ $br->sub_kategori_bisnis }}');
        //     $('input[name="website"]').val('{{ $br->website }}');
        //     $('input[name="nomor_telepon"]').val('{{ $br->nomor_telepon }}');
        //     $('select[name="is_aktif"]').val('{{ $br->is_aktif }}').trigger('change');

        //     // init site select2
        //     destroySiteSelect2();
        //     initSiteSelect2('{{ $br->id_br }}');
        // @endif


        // @if(isset($site) && $site)
        //     // set hidden site id
        //     $('#site_id_hidden').val('{{ $site->id_site }}');

        //     // inject option ke select2 Site
        //     const siteOption = new Option(
        //         '{{ $site->nama_lokasi }}',
        //         '{{ $site->id_site }}',
        //         true,
        //         true
        //     );
        //     $('#site_id').append(siteOption).trigger('change');

        //     // isi field site
        //     $('input[name="nama_lokasi"]').val('{{ $site->nama_lokasi }}');
        //     $('textarea[name="alamat_lengkap"]').val(`{!! addslashes($site->alamat_lengkap) !!}`);
        //     $('input[name="provinsi"]').val('{{ $site->provinsi }}');
        //     $('input[name="kota_kabupaten"]').val('{{ $site->kota_kabupaten }}');
        //     $('input[name="kecamatan"]').val('{{ $site->kecamatan }}');
        //     $('input[name="kelurahan"]').val('{{ $site->kelurahan }}');
        //     $('input[name="kode_pos"]').val('{{ $site->kode_pos }}');
        //     $('input[name="kawasan_bisnis"]').val('{{ $site->kawasan_bisnis }}');
        //     $('input[name="gedung"]').val('{{ $site->gedung }}');
        //     $('input[name="alamat"]').val('{{ $site->alamat }}');
        //     $('input[name="npwp_cabang"]').val('{{ $site->npwp_cabang }}');
        // @endif

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

        const brOption = new Option(
            br.nama,
            br.id_br,
            true,
            true
        );

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

        const siteOption = new Option(
            site.nama_lokasi,
            site.id_site,
            true,
            true
        );

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

            // text input / textarea
            $('input[name="npwp"]').val(d.npwp ?? '');
            $('textarea[name="npwp_alamat"]').val(d.npwp_alamat ?? '');
            $('input[name="kategori_bisnis"]').val(d.kategori_bisnis ?? '');
            $('input[name="sub_kategori_bisnis"]').val(d.sub_kategori_bisnis ?? '');
            $('input[name="website"]').val(d.website ?? '');
            $('input[name="nomor_telepon"]').val(d.nomor_telepon ?? '');

            // select
            $('select[name="entitas"]').val(d.entitas ?? '').trigger('change');
            $('select[name="kepemilikan"]').val(d.kepemilikan ?? '').trigger('change');
            $('select[name="is_aktif"]').val(d.is_aktif ?? 1).trigger('change');

            // init select2 site SETELAH BR dipilih
            destroySiteSelect2();
            initSiteSelect2(d.id);
            clearSiteField();
        } else {
            // nama baru → belum ada BR
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
        // $('#createBusinessRelationForm')
        //     .find('input:not([name="nama"]), textarea, select')
        //     .val('')
        //     .trigger('change');
        
        $("#nama_br").html('');
        destroySiteSelect2();
        clearSiteField();
        clearBrField();
    });



    function initSiteSelect2(idBr) {
        $('#site_id')
            .prop('disabled', false)
            .select2({
                placeholder: 'Pilih atau ketik lokasi / cabang',
                tags: true,
                allowClear: true,
                // minimumInputLength: 1,
                ajax: {
                    url: `/business-relations/${idBr}/sites`,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term }),
                    processResults: data => ({ results: data })
                }
            });
    }
    
    function destroySiteSelect2() {
        if ($('#site_id').hasClass('select2-hidden-accessible')) {
            $('#site_id').select2('destroy');
        }

        $('#site_id').prop('disabled', true).empty();
    }


    function resetCreateForm() {

        // reset form biasa
        const form = document.getElementById('createBusinessRelationForm');
        if (form) form.reset();

        // clear hidden ids
        $('#id_br').val('');
        $('#site_id_hidden').val('');

        // reset select2 Business Relation
        if ($('#nama_br').hasClass('select2-hidden-accessible')) {
            $('#nama_br').val(null).trigger('change');
        }

        // reset select2 Site
        destroySiteSelect2();

        // pastikan tombol submit aktif kembali
        $('#btnSubmit')
            .prop('disabled', false)
            .text('Simpan Data');
    }


    function initSiteSelect2(idBr) {
        console.log('Init Site select2 with BR id:', idBr);
        $('#site_id')
            .prop('disabled', false)
            .select2({
                placeholder: 'Pilih atau ketik lokasi / cabang',
                tags: true,
                // ... setting lainnya
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') return null;

                    return {
                        id: term,
                        text: term,
                        newTag: true // Penanda bahwa ini data baru
                    };
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

        // Tambahkan event listener DI SINI setelah select2 initialized
        $('#site_id').on('select2:select', function (e) {
            const d = e.params.data;
            console.log('Data diterima:', d);

            // Cek apakah d.id adalah angka (Data dari DB) atau String (Tag Baru)
            const isExistingData = d.id && !isNaN(d.id) && !d.newTag;

            if (isExistingData) {
                // Logika untuk data dari database
                $('input[name="nama_lokasi"]').val(d.nama_lokasi ?? '');
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
                // ... (sisanya sama)
            } else {
                // Logika untuk TAG BARU (ketikan user)
                clearSiteField(); 
                console.log('User mengetik data baru:', d.text);
                $('input[name="nama_lokasi"]').val(d.text);
                $('#site_id_hidden').val('');
            }
        });
        
        // $('#site_id').on('select2:select', function (e) {
        //     const d = e.params.data;
        //     console.log('Site selected:', d);
        //     if(d.id && !isNaN(d.id)) {
        //         $('input[name="nama_lokasi"]').val(d.nama_lokasi ?? '');
        //         $('textarea[name="alamat_lengkap"]').val(d.alamat_lengkap ?? '');
        //         $('input[name="provinsi"]').val(d.provinsi ?? '');
        //         $('input[name="kota_kabupaten"]').val(d.kota_kabupaten ?? '');
        //         $('input[name="kecamatan"]').val(d.kecamatan ?? '');
        //         $('input[name="kelurahan"]').val(d.kelurahan ?? '');
        //         $('input[name="kode_pos"]').val(d.kode_pos ?? '');
        //         $('input[name="kawasan_bisnis"]').val(d.kawasan_bisnis ?? '');
        //         $('input[name="gedung"]').val(d.gedung ?? '');
        //         $('input[name="alamat"]').val(d.alamat ?? '');
        //         $('input[name="npwp_cabang"]').val(d.npwp_cabang ?? '');
        //         $('select[name="is_aktif"]').val(d.is_aktif ?? 1).trigger('change');
        //         $('#site_id_hidden').val(d.id);
        //     } else {
        //         clearSiteField();
        //         console.log('New Site name entered:', d);
        //         $('input[name="nama_lokasi"]').val(d.text);
        //         $('#site_id_hidden').val('');
        //     }
        // });
    }

    $('#site_id').on('select2:clear', function () {
        $("#site_id").html('');
        clearSiteField();
    });

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

        // reset select
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
    
                    // resetCreateForm(); // fungsi reset yang sudah kita buat
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
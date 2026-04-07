@extends('layouts.app')

@section('page-title', 'Business Relation Contacts')
@section('page-descrip', 'Kelola data Business Relation Contacts')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('business-relation-contacts.index') }}">Business Relation Contacts</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Business Relation Contacts</li>
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
    <div class="container-fluid">
        <form id="createBusinesRelationContact">
            @csrf

            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informasi Business Relation Contacts</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="mb-3">
                            <label class="form-label required">
                                Bussines Relation Site
                            </label>

                            <select id="id_br"
                                    name="id_br"
                                    class="form-select"
                                    required>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">
                                Nama PIC
                            </label>
                            <input type="text" class="form-control" id="nama_pic" name="nama_pic" required>
                        </div>

                        <div class="mb-3 col-md-3">
                            <label class="form-label required">
                                No. Telp PIC
                            </label>
                            <input type="text" class="form-control" id="nomor_telepon_pic" name="nomor_telepon_pic" required>
                        </div>

                        <div class="mb-3 col-md-3">
                            <label class="form-label required">
                                Email PIC
                            </label>
                            <input type="text" class="form-control" id="email_pic" name="email_pic" required>
                        </div>

                        <div class="mb-3 col-md-3">
                            <label class="form-label required">
                                Lokasi PIC
                            </label>
                            <input type="text" class="form-control" id="lokasi_pic" name="lokasi_pic" required>
                        </div>

                        <div class="mb-3 col-md-3">
                            <label for="is_aktif" class="form-label required">Status</label>
                            <select
                                class="form-select"
                                id="is_aktif"
                                name="is_aktif"
                                required>
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>


                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('business-estates.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Data
                </button>
            </div>

        </form>
    </div>
</section>
@endsection


@section('custom-script')
<script>
$(document).ready(function () {

    $('#id_br').select2({
        placeholder: 'Pilih Kelompok...',
        ajax: {
            url: "{{ route('business-relation-sites.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
    
    $('#createBusinesRelationContact').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const btn  = form.find('button[type="submit"]');

        btn.prop('disabled', true).text('Menyimpan...');

        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ route('business-relation-contacts.store') }}",
                type: "POST",
                data: form.serialize(),
                success: function (res) {
    
                    Notify.success('Data berhasil disimpan!');
                    window.location.href = "{{ route('business-relation-contacts.index') }}";
                    // reset form (optional)
                    form[0].reset();
                },
                error: function (xhr) {
    
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let msg = Object.values(errors).map(e => e[0]).join('<br>');
    
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            html: msg
                        });
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

});
</script>
@endsection

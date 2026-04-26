@extends('layouts.app')

@section('page-title', 'Tambah Grup Menu')
@section('page-descrip', 'Buat grup menu baru untuk departemen')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('menu-groups.index') }}">Grup Menu</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah Grup Menu</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="20" width="60" height="10" rx="3" stroke="white" stroke-width="3"/>
        <rect x="10" y="38" width="60" height="10" rx="3" stroke="white" stroke-width="3"/>
        <rect x="10" y="56" width="40" height="10" rx="3" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<section class="section">

    <form id="createGroupForm">
        @csrf

        <!-- SECTION 1: INFO GRUP -->
        <x-section-card icon="fa-layer-group" color="icon-navy" title="Informasi Grup" subtitle="Nama dan deskripsi departemen">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label required">Nama Grup</label>
                    <input type="text" name="name" class="form-control" required placeholder="contoh: Sales, Lab, Admin">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Keterangan singkat grup ini">
                </div>
            </div>
        </x-section-card>

        <!-- SECTION 2: PERMISSION MATRIX -->
        <x-section-card icon="fa-shield-halved" color="icon-green" title="Hak Akses Menu" subtitle="Konfigurasi akses grup per menu dan per aksi">
            <div class="p-0" id="permissionMatrixContainer">
                <div class="p-3 text-muted">Memuat konfigurasi menu...</div>
            </div>
        </x-section-card>

        <x-form-actions back-route="{{ route('menu-groups.index') }}" submit-label="Simpan Grup" />

    </form>

</section>
@endsection

@section('custom-script')
<script src="{{ asset('assets/js/menu-groups/form.js') }}"></script>
<script>
$(document).ready(async function () {
    await loadMenuConfig();
    $('#permissionMatrixContainer').html(renderPermissionMatrix({}));
    initPermissionMatrix('#permissionMatrixContainer');

    $('#createGroupForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.set('permissions', JSON.stringify(collectPermissions('#permissionMatrixContainer')));

        $.ajax({
            url:         "{{ route('menu-groups.store') }}",
            method:      'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.href = "{{ route('menu-groups.index') }}");
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }
        });
    });
});
</script>
@endsection


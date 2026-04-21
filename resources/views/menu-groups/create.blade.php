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
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="detail-section-title">Informasi Grup</div>
                <div class="detail-section-sub">Nama dan deskripsi departemen</div>
            </div>
            <div class="detail-section-body">
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
            </div>
        </div>

        <!-- SECTION 2: PERMISSION MATRIX -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="detail-section-title">Hak Akses Menu</div>
                <div class="detail-section-sub">Konfigurasi akses grup per menu dan per aksi</div>
            </div>
            <div class="detail-section-body p-0" id="permissionMatrixContainer">
                <div class="p-3 text-muted">Memuat konfigurasi menu...</div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('menu-groups.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Grup
            </button>
        </div>

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

@section('style')
<style>
    .permission-matrix th,
    .permission-matrix td { vertical-align: middle; }
    .permission-matrix .form-check-input { cursor: pointer; width: 16px; height: 16px; }
</style>
@endsection

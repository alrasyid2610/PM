@extends('layouts.app')

@section('page-title', 'Tambah User')
@section('page-descrip', 'Buat akun pengguna baru')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('users.index') }}">User Management</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah User</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="28" r="14" stroke="white" stroke-width="3"/>
        <path d="M12 68c0-15.464 12.536-28 28-28s28 12.536 28 28" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">

    <form id="createUserForm">
        @csrf

        <!-- SECTION 1: INFORMASI USER -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="detail-section-title">Informasi User</div>
                <div class="detail-section-sub">Data akun pengguna sistem</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label required">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label required">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" selected>Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Grup / Departemen</label>
                        <select name="menu_group_id" class="form-select">
                            <option value="">-- Tanpa Grup --</option>
                            @foreach(\Illuminate\Support\Facades\DB::table('menu_groups')->orderBy('name')->get() as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan User
            </button>
        </div>

    </form>

</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId:    '#createUserForm',
        url:       "{{ route('users.store') }}",
        redirect:  "{{ route('users.index') }}",
        successMessage: 'User berhasil dibuat',
    });
</script>
@endsection

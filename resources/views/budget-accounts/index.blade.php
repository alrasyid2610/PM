@extends('layouts.app')

@section('page-title', 'Budget Account')
@section('page-descrip', 'Kelola kategori dan item akun anggaran')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Budget Account</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="15" width="60" height="50" rx="5" stroke="white" stroke-width="3"/>
        <path d="M25 35h30M25 45h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="58" cy="55" r="10" fill="white"/>
        <path d="M58 50v5l3 3" stroke="#0f766e" stroke-width="2" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold">
                    <i class="fa-solid fa-layer-group me-2" style="color:#0f766e;"></i>
                    Daftar Budget Account
                </h6>
                @if(userCan('budget-accounts', 'can_create'))
                <button type="button" class="pm-btn-pill" id="btn-add-kategori"
                    style="border-color:#0f766e;color:#0f766e;">
                    <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                    <i class="fa-solid fa-layer-group" style="font-size:11px;"></i>
                    Tambah Kategori
                </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div id="account-table-wrap">
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah/Edit --}}
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-no-disable></button>
            </div>
            <div class="modal-body py-3 px-3">
                <input type="hidden" id="accountModal-id">
                <input type="hidden" id="accountModal-id-parent">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label" id="accountModal-label-nama">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="accountModal-nama"
                            placeholder="Nama akun" data-no-disable>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="accountModal-kode"
                            placeholder="cth: TRANSPORT" data-no-disable style="text-transform:uppercase;"
                            oninput="this.value=this.value.toUpperCase()">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control form-control-sm" id="accountModal-keterangan"
                            rows="2" style="resize:none;" placeholder="Opsional" data-no-disable></textarea>
                    </div>
                    <div class="col-md-6" id="accountModal-aktif-wrap">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="accountModal-is_aktif" data-no-disable>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="accountModal-btn-save" data-no-disable
                    style="background:#0f766e;border-color:#0f766e;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<script>
    window.route = {
        csrf: '{{ csrf_token() }}',
    };
</script>
<script src="{{ asset('assets/js/budget-accounts/index.js') }}"></script>
@endsection

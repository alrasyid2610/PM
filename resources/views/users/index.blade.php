@extends('layouts.app')

@section('page-title', 'User Management')
@section('page-descrip', 'Kelola pengguna dan hak akses sistem')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">User Management</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="28" r="14" stroke="white" stroke-width="3"/>
        <path d="M12 68c0-15.464 12.536-28 28-28s28 12.536 28 28" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <div class="card">
        <div class="card-body">

            <x-datatable-header
                title="List of Users"
                create-route="users.create"
                add-label="Tambah User"
                :with-history="false"
            />

            <div class="tab-content">

                <!-- TAB DATA -->
                <div class="tab-pane fade show active" id="tab-data">
                    <div class="table-responsive">
                        <table id="{{ request()->segment(1) }}-table"
                               class="table table-striped table-hover table-sm table-bordered w-100"
                               data-datatable-auto-columns="true">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB DETAIL -->
                <div class="tab-pane fade" id="tab-detail">
                    <div id="detailContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat detail
                    </div>
                </div>

                <!-- TAB HISTORY -->
                <div class="tab-pane fade" id="tab-history">
                    <div id="historyContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat history
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('users.data') }}",
        update:  "{{ url('users') }}/",
        history: "{{ url('users') }}/",
        csrf:    "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/users/form.js') }}"></script>
<script src="{{ asset('assets/js/users/index.js') }}"></script>
@endsection

@section('style')
<style>
    .permission-matrix th,
    .permission-matrix td {
        vertical-align: middle;
    }
    .permission-matrix .table-secondary td {
        font-size: 13px;
        letter-spacing: 0.3px;
    }
    .permission-matrix .form-check-input {
        cursor: pointer;
        width: 16px;
        height: 16px;
    }
    .permission-matrix .form-check-input:disabled,
    .permission-matrix .form-check-input.disabled {
        opacity: 0.5;
        cursor: default;
    }
</style>
@endsection

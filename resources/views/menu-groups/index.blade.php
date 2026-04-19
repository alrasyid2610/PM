@extends('layouts.app')

@section('page-title', 'Grup Menu')
@section('page-descrip', 'Kelola grup menu dan hak akses per departemen')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Grup Menu</li>
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
    <div class="card">
        <div class="card-body">

            <x-datatable-header
                title="List of Grup Menu"
                create-route="menu-groups.create"
                add-label="Tambah Grup"
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
        data:    "{{ route('menu-groups.data') }}",
        update:  "{{ url('menu-groups') }}/",
        history: "{{ url('menu-groups') }}/",
        csrf:    "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/menu-groups/form.js') }}"></script>
<script src="{{ asset('assets/js/menu-groups/index.js') }}"></script>
@endsection

@section('style')
<style>
    .permission-matrix th,
    .permission-matrix td { vertical-align: middle; }
    .permission-matrix .form-check-input { cursor: pointer; width: 16px; height: 16px; }
    .permission-matrix .form-check-input:disabled,
    .permission-matrix .form-check-input.disabled { opacity: 0.5; cursor: default; }
</style>
@endsection

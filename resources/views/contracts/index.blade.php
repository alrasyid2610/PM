@extends('layouts.app')

@section('content')

<style>
    thead * { text-align: center }
</style>

<section class="section">
    <div class="card">
        <div class="card-body">

            {{-- Header --}}
            <x-datatable-header
                title="Contracts"
                create-route="contracts.create"
                add-label="Tambah Contract"
                :with-history="true"
            />

            <div class="tab-content">

                {{-- TAB DATA --}}
                <div class="tab-pane fade show active" id="tab-data">
                    <div class="table-responsive">
                        <table id="contracts-table"
                               class="table table-striped table-hover table-sm table-bordered w-100">
                            <thead>
                                <th>No</th>
                                <th>No Kontrak</th>
                                <th>Pelanggan</th>
                                <th>Tgl Kontrak</th>
                                <th>Tgl Mulai</th>
                                <th>Tgl Selesai</th>
                                <th>Durasi</th>
                                <th>Nilai Kontrak</th>
                                <th>Status</th>
                                <th>PIC Pramatek</th>
                                <th>Created</th>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB DETAIL --}}
                <div class="tab-pane fade" id="tab-detail">
                    <div id="detailContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat detail
                    </div>
                </div>

                {{-- TAB HISTORY --}}
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
        data:    "{{ route('contracts.data') }}",
        update:  "{{ url('contracts') }}/",
        detail:  "{{ url('contracts') }}/",
        history: "{{ url('contracts') }}/",
        select2: "{{ route('contracts.select2') }}",
        csrf:    "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/contracts/form.js') }}"></script>
<script src="{{ asset('assets/js/contracts/index.js') }}"></script>
@endsection


@section('style')
<style>
    .summary-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 22px 26px;
        border-radius: 14px;
        color: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        transition: all .25s ease;
    }
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.18);
    }
    .summary-icon {
        width: 60px; height: 60px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px;
        background: rgba(255,255,255,0.25);
    }
    .summary-content { display: flex; flex-direction: column; }
    .summary-title   { font-size: 14px; opacity: 0.9; }
    .summary-content h2 { margin: 0; font-size: 34px; font-weight: 700; color: white !important; }

    .summary-blue   { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
    .summary-orange { background: linear-gradient(135deg, #fd7e14, #e55a00); }
    .summary-green  { background: linear-gradient(135deg, #198754, #146c43); }
    .summary-gray   { background: linear-gradient(135deg, #6c757d, #495057); }
</style>
@endsection

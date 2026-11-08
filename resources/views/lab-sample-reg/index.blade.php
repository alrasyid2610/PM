@extends('layouts.app')

@section('page-title', 'Lab Sample Reg')
@section('page-descrip', 'Daftarkan sample ke lab per Work Order')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Lab Sample Reg</li>
@endsection

@section('content')
<section class="section">
    <div class="row g-3">

        {{-- PILIH FWO --}}
        <div class="col-12">
            <x-section-card icon="fa-magnifying-glass" color="icon-blue" title="Cari Fieldwork Order (FWO)" subtitle="Ketik no. FWO, atau no. WO / SO, judul, pelanggan untuk menemukan FWO-nya">
                <select id="labRegWoSelect" class="form-select"></select>
                <div class="form-text" id="labRegWoHint">Registrasi sample ke lab dilakukan <b>per Fieldwork Order</b> — pilih FWO untuk mendaftarkan sample yang sudah <b>Dikirim</b>.</div>
            </x-section-card>
        </div>

        {{-- SAMPLE WO TERPILIH --}}
        <div class="col-12 d-none" id="labRegWoWrap">
            <x-section-card icon="fa-vial" color="icon-green" title="Sample Work Order" subtitle="Registrasi sample yang sudah dikirim ke lab">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 pb-3 mb-3" style="border-bottom:1px solid #e2e8f0;">
                    <div id="labRegWoBanner" style="flex:1 1 460px;min-width:0;"></div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        @if (userCan('lab-sample-regs', 'can_create'))
                        <button type="button" class="pm-btn-pill" id="btn-wo-reg-from-fwo" data-no-disable
                            style="border-color:#0369a1;color:#0369a1;display:none;">
                            <i class="fa-solid fa-file-import" style="font-size:11px;"></i> Registrasi dari FWO
                        </button>
                        @endif
                        <button type="button" id="btnRefreshWoSample" class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </div>
                <div id="labRegContent"></div>
            </x-section-card>
        </div>

        {{-- RIWAYAT --}}
        <div class="col-12">
            <x-section-card icon="fa-clock-rotate-left" color="icon-navy" title="Riwayat Pendaftaran" subtitle="Semua pendaftaran sample ke lab">
                <div class="table-responsive">
                    <table id="labRegRiwayatTable" class="pm-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>No. Registrasi</th>
                                <th>Tgl Registrasi</th>
                                <th>No. WO</th>
                                <th>No. SO</th>
                                <th>Pelanggan</th>
                                <th>Diterima Oleh</th>
                                <th class="text-center">Sample</th>
                                <th class="text-center" style="width:60px;">Form</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </x-section-card>
        </div>

    </div>
</section>

@include('lab-sample-reg._modals')
@endsection

@section('custom-script')
<script>
    window.route = { csrf: "{{ csrf_token() }}" };
    // Tanggal Registrasi selalu hari ini (tidak bisa backdate); diambil dari server supaya tidak bergantung jam perangkat user
    window.labRegServerDate = "{{ now()->toDateString() }}";
    // Default "Diterima Oleh" = user yang sedang login
    window.labRegMe = @json(['id' => auth()->id(), 'name' => auth()->user()?->name]);
    window.labRegRoute = {
        woOptions: "{{ route('lab-sample-regs.wo-options') }}",
        riwayat:   "{{ route('lab-sample-regs.riwayat') }}",
    };
</script>
<script src="{{ asset('assets/js/lab-sample-reg/index.js') }}?v={{ filemtime(public_path('assets/js/lab-sample-reg/index.js')) }}"></script>
@endsection

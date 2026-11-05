@extends('layouts.app')

@section('page-title', 'Kendaraan')
@section('page-descrip', 'Kelola data kendaraan operasional (milik sendiri / sewa)')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Kendaraan</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 50V36l8-14h36l10 14h10v14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8 50h64" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="24" cy="54" r="7" fill="#1a3a6e" stroke="white" stroke-width="3"/>
        <circle cx="58" cy="54" r="7" fill="#1a3a6e" stroke="white" stroke-width="3"/>
        <path d="M18 36h32" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Kendaraan"
    create-route="kendaraan.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:             "{{ route('kendaraan.data') }}",
        update:           "{{ url('kendaraan') }}/",
        deleteAttachment: "{{ route('kendaraan.delete-attachment') }}",
        history:          "{{ url('kendaraan') }}/",
        csrf:             "{{ csrf_token() }}"
    }

    window.datatableHeaderLabels = {
        no_polisi: 'No. Polisi',
        nama_vendor: 'Vendor',
        masa_berlaku_stnk: 'STNK Berlaku s/d',
        is_aktif: 'Status',
    };

    window.datatableColumnRenderers = {
        kepemilikan: function (data) {
            return data === 'Sewa'
                ? '<span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;">Sewa</span>'
                : '<span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:600;">Milik Sendiri</span>';
        },
        is_aktif: function (data) {
            return data === 'Aktif'
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/kendaraan/form.js') }}"></script>
<script src="{{ asset('assets/js/kendaraan/index.js') }}"></script>
@endsection

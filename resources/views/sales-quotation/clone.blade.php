@extends('layouts.app')

{{-- Mode "Clone SQ WO" ($woOnly = true, $woId terisi): halaman yang sama dipakai untuk menyalin 1 WO ke SQ-nya sendiri
     (tanpa membuat SQ baru). Form SQ disembunyikan. $id = id SQ pemilik WO. Struktur mengikuti sales-order/clone.blade.php. --}}
@php $woOnly = !empty($woOnly); @endphp
@section('page-title', $woOnly ? 'Clone SQ Work Order' : 'Clone Sales Quotation')
@section('page-descrip', $woOnly ? 'Salin SQ WO ini beserta BOQ, FWO, dan Budget-nya menjadi SQ WO baru' : 'Salin SQ ini beserta seluruh WO-nya menjadi SQ baru')

@section('breadcrumb')
    @if ($woOnly)
        <li class="breadcrumb-item"><a href="{{ url('sales-quotations') }}?open={{ $id }}">Sales Quotations</a></li>
        <li class="breadcrumb-item active" aria-current="page">Clone SQ Work Order</li>
    @else
        <li class="breadcrumb-item"><a href="{{ url('sales-quotations') }}">Sales Quotations</a></li>
        <li class="breadcrumb-item active" aria-current="page">Clone</li>
    @endif
@endsection

@section('style')
    <style>
        .col-resize-handle { position: absolute; top: 0; right: 0; width: 6px; height: 100%; cursor: col-resize; user-select: none; z-index: 2; }
        .col-resize-handle:hover, .col-resize-handle.resizing { background: rgba(29, 78, 216, 0.35); }
        table.boq-table thead th, table.boq-other-table thead th, table.boq-sampling-table thead th, table.fieldwork-boq-table thead th {
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        /* Header WO sticky begitu card dibuka (alasan & pola sama dengan Clone SO) */
        #woAccordion .wo-card > .accordion-header { position: sticky; top: 0; z-index: 3; background: #fff; }
        #woSectionWrap .detail-section-card { overflow: visible; }
        #woSectionWrap .detail-section-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }

        #woAccordion .wo-card.wo-active { background: #f5f8ff; border-left: 4px solid #5a8dee; }
        #woAccordion .wo-card.wo-active > .accordion-header { background: #eef3ff; }
        .fwo-card.fwo-active { background: #f5fbf8; border-left: 4px solid #39da8a; }
        .fwo-card.fwo-active > .accordion-header { background: #e9f9f1; }
        .fwo-card > .accordion-header { position: sticky; top: var(--wo-header-h, 0px); z-index: 2; background: #fff; }
    </style>
@endsection

@section('content')
<section class="section">

    <div id="cloneLoading" class="text-center py-5">
        <i class="fa-solid fa-spinner fa-spin fa-2x mb-3 d-block text-primary"></i>
        Memuat data Sales Quotation...
    </div>

    <div id="cloneLoadError" class="alert alert-danger d-none"></div>

    <form id="cloneSqForm" class="row g-3 d-none">
        @csrf

        <div class="col-12">
            <div class="alert alert-info d-flex align-items-start gap-2 mb-0">
                <i class="fa-solid fa-circle-info mt-1"></i>
                @if ($woOnly)
                <div>
                    Menyalin <strong id="cloneSourceLabel">-</strong> menjadi SQ WO baru di SQ yang sama (hanya bisa jika SQ berstatus Draft).
                    Semua field di bawah pre-filled dari WO sumber dan bisa diedit bebas sebelum disimpan. Ikut disalin: BOQ, BOQ Other/Sampling,
                    FWO (Fieldwork BOQ), dan Budget Plan WO &amp; FWO beserta item.
                </div>
                @else
                <div>
                    Menyalin dari <strong id="cloneSourceLabel">-</strong> menjadi <strong>SQ baru</strong> (Draft, nomor baru, Rev.0 — bukan revisi).
                    Semua field di bawah pre-filled dari data sumber dan bisa diedit bebas sebelum disimpan. WO yang tidak dicentang "Sertakan"
                    tidak akan ikut disalin (beserta BOQ, FWO, dan Budget di dalamnya).
                </div>
                @endif
            </div>
        </div>

        @if (!$woOnly)
        <!-- SECTION INFORMASI -->
        <div class="col-12">
            <x-section-card icon="fa-file-lines" color="icon-navy" title="Informasi Quotation" subtitle="Data SQ hasil salinan">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Tanggal SQ</label>
                        <input type="text" name="tanggal_sq" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off" required>
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Judul Order</label>
                        <input type="text" name="judul_order" class="form-control" required>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Berlaku Sampai</label>
                        <input type="text" name="berlaku_sampai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Rencana Mulai <span class="text-muted">(acuan hari ke-1)</span></label>
                        <input type="text" name="rencana_mulai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Office</label>
                        <select name="id_office" id="sq_id_office" class="form-select"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION DATA PELANGGAN -->
        <div class="col-12">
            <x-section-card icon="fa-building-user" color="icon-blue" title="Data Pelanggan" subtitle="Pemesan, Pengiriman & Pembayaran">
                <div class="row g-3 mb-1">
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan (Pemesan)</label>
                        <select id="sq_id_pelanggan" class="form-select" required></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan (Pengiriman)</label>
                        <select id="sq_id_pelanggan_delivery" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan (Pembayaran)</label>
                        <select id="sq_id_pelanggan_payment" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Site</label>
                        <select id="sq_id_site_pelanggan" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Site</label>
                        <select id="sq_id_site_pelanggan_delivery" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Site</label>
                        <select id="sq_id_site_pelanggan_payment" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC</label>
                        <select id="sq_id_pic_pelanggan" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC</label>
                        <select id="sq_id_pic_pelanggan_delivery" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC</label>
                        <select id="sq_id_pic_pelanggan_payment" class="form-select"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION PIC INTERNAL -->
        <div class="col-12">
            <x-section-card icon="fa-users" color="icon-green" title="PIC Internal" subtitle="Penanggung jawab dari Pramatek">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label required">PIC Input</label>
                        <select id="sq_pic_input" class="form-select"></select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label required">PIC Order</label>
                        <select id="sq_pic_order" class="form-select"></select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Marketing Internal</label>
                        <select id="sq_pic_marketing_internal" class="form-select"></select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Marketing Eksternal</label>
                        <select id="sq_pic_marketing_eksternal" class="form-select"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION KETERANGAN -->
        <div class="col-12">
            <x-section-card icon="fa-circle-info" color="icon-purple" title="Keterangan">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Cara Pembayaran</label>
                        <textarea name="cara_pembayaran" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Catatan Internal</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        <div class="form-text">Tidak tercetak di penawaran.</div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" id="sq_salin_keterangan" class="form-check-input" checked>
                            <label class="form-check-label" for="sq_salin_keterangan">Salin poin Keterangan Penawaran dari SQ sumber</label>
                        </div>
                        <div class="form-text">Jika dimatikan, SQ baru memakai paket keterangan standar. Isian rekening/kontak tetap bisa diubah di tab Keterangan.</div>
                    </div>
                </div>
            </x-section-card>
        </div>
        @endif

        <!-- SECTION WORK ORDER (hierarki: BOQ/FWO/Budget menempel di dalam tiap card WO) -->
        <div class="col-12" id="woSectionWrap">
            <x-section-card icon="fa-briefcase" color="icon-blue" title="Work Order" subtitle="Pilih & sesuaikan WO yang ikut disalin">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="pm-search">
                        <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="woSearch" placeholder="Cari No. WO / Judul...">
                        <button type="button" id="woSearchClear" class="pm-search-clear d-none" title="Hapus"><i class="fa-solid fa-times"></i></button>
                    </div>
                </div>
                <div id="woSummary" class="mb-3 text-muted" style="font-size:13px;"></div>
                <div id="woAccordion" class="accordion"></div>
            </x-section-card>
        </div>

        <div class="col-12 d-flex justify-content-between align-items-center"
            style="position:sticky;bottom:0;z-index:100;background:#fff;
                   border-top:1px solid #e2e8f0;padding-block:12px;margin-top:8px;
                   box-shadow:0 -2px 10px rgba(0,0,0,.06);">
            <a href="{{ url('sales-quotations') }}?open={{ $id }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Batal
            </a>
            <button type="submit" id="btnSubmitClone" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-copy me-1"></i> Buat Salinan
            </button>
        </div>
    </form>
</section>
@endsection

@section('custom-script')
<script>
    window.cloneRoute = {
        woOnly: {{ $woOnly ? 'true' : 'false' }},
        @if ($woOnly)
        data:   "{{ url('sq-work-orders') }}/{{ $woId }}/clone-data",
        submit: "{{ url('sq-work-orders') }}/{{ $woId }}/clone",
        @else
        data:   "{{ url('sales-quotations') }}/{{ $id }}/clone-data",
        submit: "{{ url('sales-quotations') }}/{{ $id }}/clone",
        @endif
        csrf:   "{{ csrf_token() }}",
        select2Br:       "{{ route('business-relations.select2') }}",
        select2Site:     "{{ url('business-relations/sites/select2') }}",
        select2Contact:  "{{ route('business-relation-contacts.select2') }}",
        select2User:     "{{ route('users.select2') }}",
        authUser:        @json(['id' => Auth::id(), 'name' => Auth::user()->name]),
        select2Satuan:   "{{ route('satuan.select2') }}",
        select2TestingPoint: "{{ route('testing-points.select2') }}",
        itemsByPoint:    "{{ url('testing-items/by-point') }}/",
        select2Office: "{{ route('office.select2') }}",
        createOffice: "{{ route('office.create') }}",
    };
</script>
<script src="{{ asset('assets/js/sales-quotation/clone.js') }}?v={{ filemtime(public_path('assets/js/sales-quotation/clone.js')) }}"></script>
@endsection

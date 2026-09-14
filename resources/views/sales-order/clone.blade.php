@extends('layouts.app')

@section('page-title', 'Clone Sales Order')
@section('page-descrip', 'Salin SO ini beserta seluruh WO & BOQ-nya menjadi SO baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('sales-orders') }}">Sales Orders</a></li>
    <li class="breadcrumb-item active" aria-current="page">Clone</li>
@endsection

@section('style')
    <style>
        .col-resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 6px;
            height: 100%;
            cursor: col-resize;
            user-select: none;
            z-index: 2;
        }
        .col-resize-handle:hover,
        .col-resize-handle.resizing {
            background: rgba(29, 78, 216, 0.35);
        }
        table.boq-table thead th,
        table.boq-other-table thead th,
        table.boq-sampling-table thead th {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Header WO tetap kelihatan (sticky) begitu card-nya dibuka —
           supaya waktu scroll ke bawah lihat BOQ/FWO, user tidak kehilangan
           konteks "WO mana yang lagi dibuka" karena headernya ikut ter-scroll
           ke atas dan hilang dari layar. */
        #woAccordion .wo-card > .accordion-header {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #fff;
        }

        /* .detail-section-card (wrapper komponen section-card) pakai overflow:hidden
           bawaan (buat clip sudut rounded) — tapi overflow selain "visible" di
           ANCESTOR manapun membatalkan position:sticky pada keturunannya,
           makanya sticky di atas tidak pernah nyala. Dilepas KHUSUS section
           WO ini saja (bukan global) supaya section lain tidak kena efek
           sampingnya, sudut atas dibulatkan manual sebagai gantinya. */
        #woSectionWrap .detail-section-card {
            overflow: visible;
        }
        #woSectionWrap .detail-section-header {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
    </style>
@endsection

@section('content')
<section class="section">

    <div id="cloneLoading" class="text-center py-5">
        <i class="fa-solid fa-spinner fa-spin fa-2x mb-3 d-block text-primary"></i>
        Memuat data Sales Order...
    </div>

    <div id="cloneLoadError" class="alert alert-danger d-none"></div>

    <form id="cloneSoForm" class="row g-3 d-none">
        @csrf

        <div class="col-12">
            <div class="alert alert-info d-flex align-items-start gap-2 mb-0">
                <i class="fa-solid fa-circle-info mt-1"></i>
                <div>
                    Menyalin dari <strong id="cloneSourceLabel">-</strong>. Semua field di bawah pre-filled dari data sumber
                    dan bisa diedit bebas sebelum disimpan. WO yang tidak dicentang "Sertakan" tidak akan ikut disalin
                    (beserta seluruh BOQ, FWO, Fieldwork BOQ, dan Personel di dalamnya). <strong>Belum termasuk Budget Plan & Realisasi</strong> — menyusul di tahap berikutnya.
                </div>
            </div>
        </div>

        <!-- SECTION SO -->
        <div class="col-12">
            <x-section-card icon="fa-file-lines" color="icon-navy" title="Informasi Sales Order" subtitle="Data SO hasil salinan">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Tanggal SO</label>
                        <input type="text" name="tanggal_so" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off" required>
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label">Judul Order</label>
                        <input type="text" name="judul_order" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="text" name="tanggal_mulai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="text" name="tanggal_selesai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Office</label>
                        <select name="id_office" id="so_id_office" class="form-select">
                            <option value="">Pilih Office</option>
                            <option value="1">Pramatek Jakarta</option>
                            <option value="2">Pramatek Bandung</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION PO -->
        <div class="col-12">
            <x-section-card icon="fa-receipt" color="icon-amber" title="Purchase Order (PO)">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="tidak_ada_po" id="so_tidak_ada_po" class="form-check-input" value="1">
                            <label class="form-check-label" for="so_tidak_ada_po">Ada PO</label>
                        </div>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Tanggal PO</label>
                        <input type="text" name="tanggal_po" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label">No PO</label>
                        <input type="text" name="no_po" class="form-control">
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
                        <select id="so_id_pelanggan" class="form-select" required></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Perusahaan (Pengiriman)</label>
                        <select id="so_id_pelanggan_delivery" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Perusahaan (Pembayaran)</label>
                        <select id="so_id_pelanggan_payment" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Site</label>
                        <select id="so_id_site_pelanggan" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Site</label>
                        <select id="so_id_site_pelanggan_delivery" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Site</label>
                        <select id="so_id_site_pelanggan_payment" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">PIC</label>
                        <select id="so_id_pic_pelanggan" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">PIC</label>
                        <select id="so_id_pic_pelanggan_delivery" class="form-select"></select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">PIC</label>
                        <select id="so_id_pic_pelanggan_payment" class="form-select"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION PIC INTERNAL -->
        <div class="col-12">
            <x-section-card icon="fa-users" color="icon-green" title="PIC Internal">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label">PIC Input</label>
                        <select id="so_pic_input" class="form-select"></select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">PIC Order</label>
                        <select id="so_pic_order" class="form-select"></select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Marketing Internal</label>
                        <select id="so_pic_marketing_internal" class="form-select"></select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Marketing Eksternal</label>
                        <select id="so_pic_marketing_eksternal" class="form-select"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION STATUS & KETERANGAN -->
        <div class="col-12">
            <x-section-card icon="fa-circle-info" color="icon-purple" title="Status & Keterangan">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Keterangan Status</label>
                        <textarea name="keterangan_status" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Cara Pembayaran</label>
                        <textarea name="cara_pembayaran" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION WORK ORDER (hierarki: BOQ menempel di dalam tiap card WO) -->
        <div class="col-12" id="woSectionWrap">
            <x-section-card icon="fa-briefcase" color="icon-blue" title="Work Order" subtitle="Pilih & sesuaikan WO yang ikut disalin">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="pm-search">
                        <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="woSearch" placeholder="Cari No. WO / Judul...">
                        <button type="button" id="woSearchClear" class="pm-search-clear d-none" title="Hapus"><i class="fa-solid fa-times"></i></button>
                    </div>
                    <div class="input-group input-group-sm" style="max-width:360px;">
                        <span class="input-group-text">Geser tanggal WO (hari)</span>
                        <input type="number" id="dateShiftDays" class="form-control" placeholder="mis. 30, boleh minus">
                        <button type="button" class="btn btn-outline-primary" id="btnApplyDateShift">Terapkan</button>
                    </div>
                </div>
                <div id="woSummary" class="mb-3 text-muted" style="font-size:13px;"></div>
                <div id="woAccordion" class="accordion"></div>
            </x-section-card>
        </div>

        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
            <a href="{{ url('sales-orders') }}?open={{ $id }}" class="btn btn-secondary btn-sm">
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
        data:   "{{ url('sales-orders') }}/{{ $id }}/clone-data",
        submit: "{{ url('sales-orders') }}/{{ $id }}/clone",
        csrf:   "{{ csrf_token() }}",
        select2Br:       "{{ route('business-relations.select2') }}",
        select2Site:     "{{ url('business-relations/sites/select2') }}",
        select2Contact:  "{{ route('business-relation-contacts.select2') }}",
        select2User:     "{{ route('users.select2') }}",
        select2Satuan:   "{{ route('satuan.select2') }}",
        select2TestingPoint: "{{ route('testing-points.select2') }}",
        itemsByPoint:    "{{ url('testing-items/by-point') }}/",
        select2Personnel: "{{ route('personnel.select2') }}",
    };
</script>
<script src="{{ asset('assets/js/sales-order/clone.js') }}"></script>
@endsection

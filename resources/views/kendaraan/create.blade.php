@extends('layouts.app')

@section('page-title', 'Kendaraan')
@section('page-descrip', 'Tambah kendaraan operasional (milik sendiri / sewa)')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('kendaraan.index') }}">Kendaraan</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
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
<section class="section">
    <form id="createKendaraanForm" enctype="multipart/form-data" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-truck-pickup" color="icon-navy" title="Data Kendaraan" subtitle="Identitas kendaraan operasional">
                <div class="row g-3">
                    <div class="col-md-5 col-12">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="nama" class="form-control" required maxlength="150" placeholder="mis. Avanza Putih">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label required">No. Polisi</label>
                        <input type="text" name="no_polisi" class="form-control text-uppercase" required maxlength="20" placeholder="B 1234 XYZ">
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label required">Jenis</label>
                        <select name="jenis" class="form-select" required>
                            @foreach(\App\Support\KendaraanOptions::JENIS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Merek</label>
                        <input type="text" name="merek" class="form-control" maxlength="100" placeholder="mis. Toyota">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" maxlength="100" placeholder="mis. Avanza">
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">Tahun</label>
                        <input type="number" name="tahun" class="form-control" min="1980" max="{{ now()->year + 1 }}" placeholder="mis. 2020">
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">Warna</label>
                        <input type="text" name="warna" class="form-control" maxlength="50">
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">STNK Berlaku s/d</label>
                        <input type="text" name="masa_berlaku_stnk" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-handshake" color="icon-teal" title="Kepemilikan" subtitle="Milik sendiri atau sewa (vendor wajib diisi untuk sewa)">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Kepemilikan</label>
                        <select name="kepemilikan" class="form-select" required>
                            @foreach(\App\Support\KendaraanOptions::KEPEMILIKAN as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 col-12 kendaraan-vendor d-none">
                        <label class="form-label required">Nama Vendor / Pemilik</label>
                        <input type="text" name="nama_vendor" class="form-control" maxlength="150" placeholder="mis. Rental Maju Jaya">
                    </div>
                    <div class="col-md-4 col-12 kendaraan-vendor d-none">
                        <label class="form-label">Kontak Vendor</label>
                        <input type="text" name="kontak_vendor" class="form-control" maxlength="100" placeholder="No. HP / email">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-paperclip" color="icon-blue" title="Attachment" subtitle="Foto kendaraan, STNK, kontrak sewa">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('kendaraan.index') }}" submit-label="Simpan Kendaraan" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        createFileUploader(".filepond");
        $('select[name="jenis"]').select2({ width: '100%' });
        $('select[name="is_aktif"]').select2({ width: '100%' });
        $('select[name="kepemilikan"]').select2({ width: '100%' });

        // Vendor hanya tampil & wajib untuk kendaraan sewa
        function syncVendor() {
            const sewa = $('select[name="kepemilikan"]').val() === 'sewa';
            $('.kendaraan-vendor').toggleClass('d-none', !sewa);
            $('input[name="nama_vendor"]').prop('required', sewa);
            if (!sewa) $('input[name="nama_vendor"], input[name="kontak_vendor"]').val('');
        }
        $('select[name="kepemilikan"]').on('change', syncVendor);
        syncVendor();
    });

    submitCreateForm({
        formId: "#createKendaraanForm",
        url: "{{ route('kendaraan.store') }}",
        filepond: ".filepond",
        onSuccess: function (res) {
            window.location.href = "{{ route('kendaraan.index') }}?open=" + res.id;
        },
    });
</script>
@endsection

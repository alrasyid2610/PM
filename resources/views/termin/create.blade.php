@extends('layouts.app')

@section('page-title', 'Termin')
@section('page-descrip', 'Kelola data termin pembayaran proyek')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('termin.index') }}">Termin</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="12" y="10" width="56" height="60" rx="4" stroke="white" stroke-width="3"/>
        <path d="M24 28h32M24 40h32M24 52h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M52 48l6 6-6 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="terminForm" enctype="multipart/form-data" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI TERMIN -->
        <div class="col-12">
            <x-section-card icon="fa-file-invoice-dollar" color="icon-navy" title="Termin" subtitle="Data termin pembayaran proyek">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label for="nomor" class="form-label required">Nomor</label>
                        <input type="text" class="form-control" id="nomor" name="nomor" required>
                    </div>
                    <div class="col-md-5 col-12">
                        <label for="nama" class="form-label required">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="persentase" class="form-label required">Persentase (%)</label>
                        <input type="number" class="form-control" id="persentase" name="persentase"
                               step="0.01" min="0" max="100" required>
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="status" class="form-label required">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="nilai" class="form-label required">Nilai (Rp)</label>
                        <input type="number" class="form-control" id="nilai" name="nilai"
                               step="1" min="0" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="tanggal" class="form-label required">Tanggal</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="1"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 2: ATTACHMENT -->
        <div class="col-12">
            <x-section-card icon="fa-paperclip" color="icon-blue" title="Attachment" subtitle="File pendukung termin">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('termin.index') }}" submit-label="Simpan Termin" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        createFileUploader(".filepond");
        $('#status').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId:   "#terminForm",
        url:      "{{ route('termin.store') }}",
        redirect: "{{ route('termin.index') }}",
        filepond: ".filepond",
    });
</script>
@endsection

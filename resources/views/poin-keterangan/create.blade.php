@extends('layouts.app')

@section('page-title', 'Poin Keterangan')
@section('page-descrip', 'Kelola master varian poin keterangan penawaran')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('poin-keterangan.index') }}">Poin Keterangan</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20" cy="22" r="3" fill="white"/>
        <circle cx="20" cy="40" r="3" fill="white"/>
        <circle cx="20" cy="58" r="3" fill="white"/>
        <line x1="30" y1="22" x2="62" y2="22" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="30" y1="40" x2="62" y2="40" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="30" y1="58" x2="52" y2="58" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createPoinForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-list-ol" color="icon-navy" title="Poin Keterangan" subtitle="Varian poin keterangan penawaran">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($kategori as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Paket</label>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_default" id="create_is_default" class="form-check-input" value="1">
                            <label class="form-check-label" for="create_is_default">Masuk paket standar (otomatis terisi di SQ baru)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label required">Teks Poin</label>
                        <textarea name="template" class="form-control" rows="4" maxlength="2000" required></textarea>
                        <div class="form-text">
                            Bagian yang boleh diubah per SQ ditulis sebagai isian
                            <code>{kunci:tipe}</code> atau <code>{kunci:tipe=default}</code>.
                            Tipe: <code>{{ implode('</code>, <code>', $tipe) }}</code>.<br>
                            Contoh: <code>Penerbitan laporan adalah {hari:angka=12} hari kerja setelah sampel diterima</code>
                        </div>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('poin-keterangan.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId: "#createPoinForm",
        url: "{{ route('poin-keterangan.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('poin-keterangan.index') }}?open=" + res.id;
        },
    });
</script>
@endsection

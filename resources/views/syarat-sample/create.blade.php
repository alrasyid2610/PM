@extends('layouts.app')

@section('page-title', 'Syarat Sample')
@section('page-descrip', 'Tambah master syarat sample')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('syarat-sample.index') }}">Syarat Sample</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="18" y="10" width="44" height="60" rx="4" stroke="white" stroke-width="3"/>
        <path d="M28 30l5 5 9-10M28 50l5 5 9-10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M48 32h8M48 52h8" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createSyaratForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-clipboard-check" color="icon-navy" title="Syarat Sample" subtitle="Syarat yang bisa dipilih di tiap Testing Point">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label required">Teks Syarat</label>
                        <textarea name="template" class="form-control" rows="3" maxlength="1000" required
                            placeholder="mis. Volume air harus lebih dari &#123;volume:angka=100&#125; ml"></textarea>
                        <div class="form-text">
                            Bagian yang berbeda antar Testing Point (jumlah botol, volume, ukuran) dibuat sebagai <b>isian</b>:
                            klik <b>Sisipkan isian</b>, atau ketik <code>&#123;</code>. Angkanya diisi di tab Syarat tiap Testing Point.
                        </div>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('syarat-sample.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script src="{{ asset('assets/js/poin-keterangan/editor.js') }}?v={{ filemtime(public_path('assets/js/poin-keterangan/editor.js')) }}"></script>
<script>
    PoinEditor.init($('textarea[name="template"]'), { tipe: ['angka'], untuk: 'Testing Point' });

    submitCreateForm({
        formId: "#createSyaratForm",
        url: "{{ route('syarat-sample.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('syarat-sample.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
@extends('layouts.app')

@section('content')
<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Contract</h4>
                <p class="text-muted mb-0">Tambahkan data kontrak baru.</p>
            </div>
            <a href="{{ route('contracts.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <form id="contractForm">
            @csrf

            {{-- SECTION: Informasi Kontrak --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    <i class="fa-solid fa-file-contract me-2 text-primary"></i>
                    Informasi Kontrak
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label required">No Kontrak</label>
                            <input type="text" class="form-control" name="no_kontrak" required
                                   placeholder="KTR/2025/001">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Kontrak</label>
                            <input type="date" class="form-control" name="tanggal_kontrak">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="draft">Draft</option>
                                <option value="aktif">Aktif</option>
                                <option value="selesai">Selesai</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tanggal_mulai">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="tanggal_selesai">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Durasi (Bulan)</label>
                            <input type="number" class="form-control" name="durasi_bulan"
                                   min="1" placeholder="12">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nilai Kontrak (Rp)</label>
                            <input type="number" class="form-control" name="nilai_kontrak"
                                   min="0" placeholder="150000000">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Attachment</label>
                            <input type="file" class="form-control" name="attachment"
                                   accept=".pdf,.doc,.docx,.jpg,.png">
                            <small class="text-muted">PDF, DOC, DOCX, JPG, PNG — Maks 5MB</small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="catatan" rows="3"
                                      placeholder="Catatan tambahan..."></textarea>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SECTION: Data Pelanggan --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    <i class="fa-solid fa-building me-2 text-info"></i>
                    Data Pelanggan
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Pelanggan (Business Relation)</label>
                            <select class="form-select select2-br" name="id_business_relation"
                                    id="id_business_relation">
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PIC Pelanggan</label>
                            <select class="form-select select2-pic-pelanggan" name="id_pic_pelanggan"
                                    id="id_pic_pelanggan">
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SECTION: PIC Pramatek --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    <i class="fa-solid fa-user-tie me-2 text-success"></i>
                    PIC Internal Pramatek
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">PIC Pramatek</label>
                            <select class="form-select select2-user" name="id_pic_pramatek"
                                    id="id_pic_pramatek">
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('contracts.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Contract
                </button>
            </div>

        </form>
    </div>
</section>
@endsection


@section('custom-script')
<script>
window.route = {
    select2BR:      "{{ route('business-relations.select2') }}",
    select2Contact: "{{ url('business-relation-contacts/select2') }}",
    select2User:    "{{ url('users/select2') }}",
    store:          "{{ route('contracts.store') }}",
    index:          "{{ route('contracts.index') }}",
    csrf:           "{{ csrf_token() }}",
}
</script>
<script src="{{ asset('assets/js/contracts/create.js') }}"></script>
@endsection

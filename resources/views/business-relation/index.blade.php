@extends('layouts.app')
@section('content')
    <style>
        thead * {
            text-align: center
        }
    </style>

    <section class="section">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Advance Search</span>
                <button id="toggleAdvanceSearch" class="btn btn-sm btn-link" type="button">
                    Show
                </button>
            </div>

            <div id="advanceSearchForm" class="collapse">
                <div class="card-body">
                    <form id="form-advance-search">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Perusahaan</label>
                                <select id="company_id" class="form-select select2" style="width:100%">
                                    <option value="">-- Semua --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="btn-search">
                                Search
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
        
    </section>
    <hr>

    <section class="section">
        
        <div class="card">

            <div class="card-body">
                
                 {{-- Header + Add Button --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Business Relations</h5>

                    <a href="{{ route('business-relations.create') }}"
                    class="btn btn-primary">
                        Add Business Relation
                    </a>
                </div>
                {{-- End Header + Add Button --}}
                
                <div class="row g-3 mb-4">
                    <!-- Kantor Pusat -->
                    <div class="col-md-6">
                        <div class="summary-card summary-blue">
                            <div class="summary-icon">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-title">Total Kantor Pusat</span>
                                <h2 id="totalKantorPusat">0</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Kantor Cabang -->
                    <div class="col-md-6">
                        <div class="summary-card summary-light-blue">
                            <div class="summary-icon">
                                <i class="fa-solid fa-sitemap"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-title">Total Kantor Cabang / Sites</span>
                                <h2 id="totalKantorCabang">0</h2>
                            </div>
                        </div>
                    </div>

                </div>

                <ul class="nav nav-tabs mb-3" id="brTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active"
                                id="data-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-data"
                                type="button">
                            Data
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link"
                                id="detail-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-detail"
                                type="button">
                            Detail
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- TAB DATA -->
                    <div class="tab-pane fade show active" id="tab-data">
                        <div class="table-responsive">
                            {{-- Table Business Relations --}}
                            <table id="businessRelationTable"
                                class="table table-striped table-hover table-sm table-bordered w-100">
                                <thead>
                                    <th>No</th>
                                    <th>Nama Perusahaan</th>
                                    <th>Entitas</th>
                                    <th>Nama Lokasi</th>
                                    <th>Tipe Lokasi</th>
                                    <th>Alamat Lengkap</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    {{-- <th>Aksi</th> --}}
                                </thead>

                                <tbody></tbody>
                            </table>
                            {{-- End Table Business Relations --}}
                        </div>
                    </div>

                    <!-- TAB DETAIL -->
                    <div class="tab-pane fade" id="tab-detail">
                        <div id="detailContent" class="p-3 text-muted">
                            Pilih data pada tab Data untuk melihat detail
                        </div>
                    </div>

                </div>
                
                
            </div>
        </div>
    </section>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                {{-- Form --}}
                <form id="editForm">
                    @csrf
                    @method('PUT')

                    <!-- hidden id -->
                    <input type="hidden" id="edit_id_br" name="id_br">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Business Relation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body" style="overflow:auto; max-height:70vh;">

                        <div class="row g-3">

                            <!-- Nama -->
                            <div class="col-md-6">
                                <label class="form-label">Nama Perusahaan</label>
                                <input type="text" id="edit_nama" name="nama" class="form-control" required>
                            </div>

                            <!-- Entitas -->
                            <div class="col-md-3">
                                <label class="form-label">Entitas</label>
                                <input type="text" id="edit_entitas" name="entitas" class="form-control">
                            </div>

                            <!-- Kepemilikan -->
                            <div class="col-md-3">
                                <label class="form-label">Kepemilikan</label>
                                <input type="text" id="edit_kepemilikan" name="kepemilikan" class="form-control">
                            </div>

                            <!-- NPWP -->
                            <div class="col-md-4">
                                <label class="form-label">NPWP</label>
                                <input type="text" id="edit_npwp" name="npwp" class="form-control">
                            </div>

                            <!-- Website -->
                            <div class="col-md-4">
                                <label class="form-label">Website</label>
                                <input type="text" id="edit_website" name="website" class="form-control">
                            </div>

                            <!-- Nomor Telepon -->
                            <div class="col-md-4">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" id="edit_nomor_telepon" name="nomor_telepon" class="form-control">
                            </div>

                            <!-- Alamat NPWP -->
                            <div class="col-md-12">
                                <label class="form-label">Alamat NPWP</label>
                                <textarea id="edit_npwp_alamat"
                                        name="npwp_alamat"
                                        class="form-control"
                                        rows="2"></textarea>
                            </div>

                            <!-- Kategori Bisnis -->
                            <div class="col-md-6">
                                <label class="form-label">Kategori Bisnis</label>
                                <input type="text"
                                    id="edit_kategori_bisnis"
                                    name="kategori_bisnis"
                                    class="form-control">
                            </div>

                            <!-- Sub Kategori -->
                            <div class="col-md-6">
                                <label class="form-label">Sub Kategori Bisnis</label>
                                <input type="text"
                                    id="edit_sub_kategori_bisnis"
                                    name="sub_kategori_bisnis"
                                    class="form-control">
                            </div>

                            <!-- Status Aktif -->
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select id="edit_is_aktif" name="is_aktif" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Non Aktif</option>
                                </select>
                            </div>

                            <!-- Created At (readonly) -->
                            <div class="col-md-4">
                                <label class="form-label">Created At</label>
                                <input type="text"
                                    id="edit_created_at"
                                    class="form-control"
                                    readonly>
                            </div>

                            <!-- Updated At (readonly) -->
                            <div class="col-md-4">
                                <label class="form-label">Updated At</label>
                                <input type="text"
                                    id="edit_updated_at"
                                    class="form-control"
                                    readonly>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>

                </form>
                {{-- End Form --}}

            </div>
        </div>
    </div>
    {{-- End Edit Modal --}}

    {{--  Edit Business Relation Site Modal --}}
    <div class="modal fade" id="editBrsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <form id="editBrsForm">
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="edit_id_site" name="id_site">
                    <input type="hidden" id="edit_id_br" name="id_br">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Business Relation Site</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- Nama Lokasi --}}
                            <div class="col-md-6">
                                <label class="form-label">Nama Lokasi</label>
                                <input type="text" class="form-control"
                                    id="edit_nama_lokasi"
                                    name="nama_lokasi" required>
                            </div>

                            {{-- Kantor Pusat --}}
                            <div class="col-md-3">
                                <label class="form-label">Tipe Lokasi</label>
                                <select class="form-select"
                                        id="edit_is_kantor_pusat"
                                        name="is_kantor_pusat">
                                    <option value="1">Kantor Pusat</option>
                                    <option value="0">Cabang</option>
                                </select>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select"
                                        id="edit_is_aktif"
                                        name="is_aktif">
                                    <option value="1">Aktif</option>
                                    <option value="0">Non Aktif</option>
                                </select>
                            </div>

                            {{-- Alamat Lengkap --}}
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control"
                                        id="edit_alamat_lengkap"
                                        name="alamat_lengkap"
                                        rows="2"></textarea>
                            </div>

                            {{-- Wilayah --}}
                            <div class="col-md-4">
                                <label class="form-label">Provinsi</label>
                                <input type="text" class="form-control"
                                    id="edit_provinsi"
                                    name="provinsi">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kota / Kabupaten</label>
                                <input type="text" class="form-control"
                                    id="edit_kota_kabupaten"
                                    name="kota_kabupaten">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" class="form-control"
                                    id="edit_kecamatan"
                                    name="kecamatan">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kelurahan</label>
                                <input type="text" class="form-control"
                                    id="edit_kelurahan"
                                    name="kelurahan">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control"
                                    id="edit_kode_pos"
                                    name="kode_pos">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kawasan Bisnis</label>
                                <input type="text" class="form-control"
                                    id="edit_kawasan_bisnis"
                                    name="kawasan_bisnis">
                            </div>

                            {{-- Gedung --}}
                            <div class="col-md-6">
                                <label class="form-label">Gedung</label>
                                <input type="text" class="form-control"
                                    id="edit_gedung"
                                    name="gedung">
                            </div>

                            {{-- Alamat --}}
                            <div class="col-md-6">
                                <label class="form-label">Alamat (Ringkas)</label>
                                <input type="text" class="form-control"
                                    id="edit_alamat"
                                    name="alamat">
                            </div>

                            {{-- NPWP Cabang --}}
                            <div class="col-md-6">
                                <label class="form-label">NPWP Cabang</label>
                                <input type="text" class="form-control"
                                    id="edit_npwp_cabang"
                                    name="npwp_cabang">
                            </div>

                            {{-- Timestamp (readonly) --}}
                            <div class="col-md-3">
                                <label class="form-label">Created At</label>
                                <input type="text" class="form-control"
                                    id="edit_created_at" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Updated At</label>
                                <input type="text" class="form-control"
                                    id="edit_updated_at" readonly>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    {{--  End Edit Business Relation Site Modal --}}


    
@endsection


@section('custom-script')
<script>
    window.route = {
        summary: "{{ route('business-relations.summary') }}",
        data: "{{ route('business-relations.data') }}",
        select2: "{{ route('business-relations.select2') }}",
        site: "{{ url('business-relations/sites') }}/",
        csrf: "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/business-relations/index.js') }}"></script>
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
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
            transition: all .25s ease;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.25);
        }

        .summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            background: rgba(255, 255, 255, 0.25);
        }

        .summary-content {
            display: flex;
            flex-direction: column;
        }

        .summary-title {
            font-size: 14px;
            opacity: 0.9;
        }

        .summary-content h2 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            color: white !important;
        }

        /* Variants */
        .summary-blue {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .summary-light-blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

            
        td.dt-control {
            cursor: pointer;
        }

        tr.shown td.dt-control i {
            transform: rotate(90deg);
            transition: transform .2s ease;
        }

    </style>

@endsection
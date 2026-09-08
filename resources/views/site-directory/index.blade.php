@extends('layouts.app')

@section('page-title', 'Site')
@section('page-descrip', 'Daftar semua Site (BRS) lintas Perusahaan — klik Buka untuk mengelola di workspace Business Relation')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Site</li>
@endsection

@section('content')
<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <div class="pm-search" style="max-width:340px;">
                    <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="siteDirectorySearch" placeholder="Cari nama site, perusahaan, atau kota...">
                </div>
            </div>

            <div class="table-responsive">
                <table id="siteDirectoryTable" class="table table-striped table-sm table-bordered w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Site</th>
                            <th>Perusahaan</th>
                            <th>Kota/Kabupaten</th>
                            <th>Lokasi</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        const table = $('#siteDirectoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('site-directory.data') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nama_lokasi' },
                { data: 'nama_br' },
                { data: 'kota_kabupaten' },
                { data: 'lokasi_gedung', orderable: false },
                { data: 'tipe_label', orderable: false, searchable: false },
                { data: 'status_label', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false },
            ],
            dom: 'rtip',
            language: { emptyTable: 'Belum ada data Site.' },
        });

        $('#siteDirectorySearch').on('input', function () {
            table.search($(this).val()).draw();
        });
    });
</script>
@endsection

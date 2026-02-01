@extends('layouts.app')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Business Relation Sites</h5>
                <a href="{{ route('business-relations.index') }}" class="btn btn-secondary">
                    Back to Business Relations
                </a>
            </div>

            <div class="table-responsive">
                <table id="brsTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Business Relation</th>
                            <th>Nama Lokasi</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</section>

{{-- Edit BRS Modal --}}
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
{{-- End Edit BRS Modal --}}

@endsection

@section('custom-script')
<script>
$(document).ready(function () {
    const table = $('#brsTable').DataTable({
        // processing: true,
        // serverSide: true,
        ajax: "{{ route('business-relation-sites.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama_br' },
            { data: 'nama_lokasi' },
            { data: 'is_kantor_pusat_label', orderable: false, searchable: false },
            { data: 'status_label', orderable: false, searchable: false },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false },
        ]
    });

    // delete
    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Yakin?',
            text: 'Data site akan dihapus',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/business-relation-sites/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        Swal.fire('Berhasil', res.message, 'success');
                        table.ajax.reload(null, false);
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');

        $.get('/business-relation-sites/' + id, function (res) {

            $('#edit_id_site').val(res.id_site);
            $('#edit_id_br').val(res.id_br);

            $('#edit_nama_lokasi').val(res.nama_lokasi);
            $('#edit_is_kantor_pusat').val(res.is_kantor_pusat);
            $('#edit_is_aktif').val(res.is_aktif);

            $('#edit_alamat_lengkap').val(res.alamat_lengkap);
            $('#edit_provinsi').val(res.provinsi);
            $('#edit_kota_kabupaten').val(res.kota_kabupaten);
            $('#edit_kecamatan').val(res.kecamatan);
            $('#edit_kelurahan').val(res.kelurahan);
            $('#edit_kode_pos').val(res.kode_pos);
            $('#edit_kawasan_bisnis').val(res.kawasan_bisnis);
            $('#edit_gedung').val(res.gedung);
            $('#edit_alamat').val(res.alamat);
            $('#edit_npwp_cabang').val(res.npwp_cabang);

            $('#edit_created_at').val(res.created_at);
            $('#edit_updated_at').val(res.updated_at);

            $('#editBrsModal').modal('show');
        });
    });

    $('#editBrsForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#edit_id_site').val();

        $.ajax({
            url: '/business-relation-sites/' + id,
            type: 'PUT',
            data: $(this).serialize(),
            success: function (res) {
                Swal.fire('Berhasil', res.message, 'success');
                $('#editBrsModal').modal('hide');

                // reload datatable tanpa reset halaman
                $('#brsTable').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let msg = Object.values(errors).map(e => e[0]).join('<br>');
                    Swal.fire('Validasi gagal', msg, 'error');
                } else {
                    Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                }
            }
        });
    });


});
</script>
@endsection

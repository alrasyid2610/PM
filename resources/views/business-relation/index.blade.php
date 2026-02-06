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
                
                <div class="table-responsive">
                    {{-- Table Business Relations --}}
                    <table id="businessRelationTable"
                        class="table table-striped table-hover table-sm table-bordered w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Perusahaan</th>
                                <th>Entitas</th>
                                <th>Kepemilikan</th>
                                <th>NPWP</th>
                                <th>Alamat NPWP</th>
                                <th>Kategori Bisnis</th>
                                <th>Sub Kategori Bisnis</th>
                                <th>Website</th>
                                <th>No Telepon</th>
                                <th>Aktif</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    {{-- End Table Business Relations --}}
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


    
@endsection


@section('custom-script')
<script>
    let table;
    let brFilter = {
        id: null,
        text: null
    };

    // Advance Search Toggle
    document.addEventListener('DOMContentLoaded', function () {
        const advanceSearch = document.getElementById('advanceSearchForm');
        const toggleBtn = document.getElementById('toggleAdvanceSearch');

        if (!advanceSearch || !toggleBtn) return;

        const bsCollapse = new bootstrap.Collapse(advanceSearch, { toggle: false });
        bsCollapse.hide();
        toggleBtn.textContent = 'Show';

        toggleBtn.addEventListener('click', function () {
            const isShown = advanceSearch.classList.contains('show');
            isShown ? bsCollapse.hide() : bsCollapse.show();
            toggleBtn.textContent = isShown ? 'Show' : 'Hide';
        });
    });


    $('#company_id').on('change', function () {
        const selected = $(this).select2('data')[0];

        if (!selected) {
            brFilter.value = null;
            brFilter.type = null;
            return;
        }

        if (!isNaN(selected.id)) {
            // pilih BR existing
            brFilter.value = selected.id;
            brFilter.type = 'id';
        } else {
            // ketik BR baru / bebas
            brFilter.value = selected.text;
            brFilter.type = 'text';
        }
    });

    $('#btn-search').on('click', function () {
        table.ajax.reload();
    });




    $(document).ready(function () {
        // table = $('#businessRelationTable').DataTable({
        //     ajax: "{{ route('business-relations.data') }}",
        //     columns: [
        //         { data: 'DT_RowIndex', orderable: false, searchable: false },
        //         { data: 'nama', name: 'nama' },
        //         { data: 'entitas' },
        //         { data: 'kepemilikan' },
        //         { data: 'npwp' },
        //         { data: 'npwp_alamat' },
        //         { data: 'kategori_bisnis' },
        //         { data: 'sub_kategori_bisnis' },
        //         { data: 'website' },
        //         { data: 'nomor_telepon' },
        //         {
        //             data: 'is_aktif',
        //             render: function (data) {
        //                 return data == 1
        //                     ? '<span class="badge bg-success">Aktif</span>'
        //                     : '<span class="badge bg-secondary">Non Aktif</span>';
        //             }
        //         },
        //         { data: 'created_at' },
        //         { data: 'updated_at' },
        //         { data: 'action', orderable: false, searchable: false }
        //     ]
        // });

        table = $('#businessRelationTable').DataTable({
            processing: true,
            serverSide: false, // sesuai kondisi terakhir kamu
            ajax: {
                url: "{{ route('business-relations.data') }}",
                data: function (d) {
                    d.filter_value = brFilter.value;
                    d.filter_type  = brFilter.type;
                }
            },
            columns: [
                { 
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                { data: 'nama' },
                { data: 'entitas' },
                { data: 'kepemilikan' },
                { data: 'npwp' },
                { data: 'npwp_alamat' },
                { data: 'kategori_bisnis' },
                { data: 'sub_kategori_bisnis' },
                { data: 'website' },
                { data: 'nomor_telepon' },
                { 
                    data: 'is_aktif',
                    render: function (data) {
                        return data == 1
                            ? '<span class="badge bg-success">Aktif</span>'
                            : '<span class="badge bg-secondary">Non Aktif</span>';
                    }
                },
                { data: 'created_at' },
                { data: 'updated_at' },
                { 
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });


        $('#company_id').select2({
            placeholder: '-- Semua --',
            allowClear: true,
            tags: true,
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('business-relations.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                }
            }
        });
        // simpan nilai SETIAP kali user memilih / mengetik
        $('#company_id').on('change', function () {
            const selected = $(this).select2('data')[0];

            if (selected) {
                brFilter.id   = selected.id;
                brFilter.text = selected.text;
            } else {
                brFilter.id   = null;
                brFilter.text = null;
            }
        });
        
    });

     $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');

        $.get('/business-relations/' + id, function (res) {
            $('#edit_id_br').val(res.id_br);
            $('#edit_nama').val(res.nama);
            $('#edit_entitas').val(res.entitas);
            $('#edit_kepemilikan').val(res.kepemilikan);
            $('#edit_npwp').val(res.npwp);
            $('#edit_npwp_alamat').val(res.npwp_alamat);
            $('#edit_kategori_bisnis').val(res.kategori_bisnis);
            $('#edit_sub_kategori_bisnis').val(res.sub_kategori_bisnis);
            $('#edit_website').val(res.website);
            $('#edit_nomor_telepon').val(res.nomor_telepon);
            $('#edit_is_aktif').val(res.is_aktif);
            $('#edit_created_at').val(res.created_at);
            $('#edit_updated_at').val(res.updated_at);

            $('#editModal').modal('show');
        });
    });

    $(document).on('click', '.btn-delete', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Yakin?',
            text: 'Data ini akan dihapus permanen',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/business-relations/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function () {
                        Swal.fire('Deleted!', 'Data berhasil dihapus', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        Swal.fire(
                            'Gagal',
                            xhr.responseJSON?.message ?? 'Terjadi kesalahan',
                            'error'
                        );
                    }
                });
            }
        });
    });

    $('#editForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#edit_id_br').val();

    $.ajax({
        url: '/business-relations/' + id,
        type: 'PUT',
        data: $(this).serialize(),
        success: function (res) {
            Swal.fire('Berhasil', res.message, 'success');
            $('#editModal').modal('hide');

            // refresh table tanpa reset pagination
            table.ajax.reload(null, false);
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



</script>
@endsection

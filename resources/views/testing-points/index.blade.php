@extends('layouts.app')

@section('content')
<style>
    thead * {
        text-align: center
    }
</style>

<hr>

<section class="section">
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Testing Points</h5>
                <a href="{{ route('testing-points.create') }}"
                    class="btn btn-primary">
                    Add Testing Point
                </a>
            </div>

            <ul class="nav nav-tabs mb-3">
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

                <div class="tab-pane fade show active" id="tab-data">
                    <div class="table-responsive">
                        <table id="{{ request()->segment(1) }}-table"
                            class="table table-striped table-hover table-sm table-bordered w-100"
                            data-datatable-auto-columns="true">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-detail">
                    <div id="detailContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat detail
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
@endsection

@section('custom-script')
<script type="text/template" id="row-template">

    <tr>

        <input type="hidden" name="id_testing_item[]" value="">
        
        <td class="row-number"></td>

        <td>
            <input type="text" name="judul_indonesia[]" class="form-control">
        </td>

        <td>
            <input type="text" name="judul_inggris[]" class="form-control">
        </td>

        <td>
            <select name="parameter[]" class="form-control parameter-select"></select>
        </td>

        <td>
            <select name="unit[]" class="form-control unit-select"></select>
        </td>

        <td>
            <input type="text" name="nilai[]" class="form-control">
        </td>

        <td>
            <input type="text" name="keterangan[]" class="form-control">
        </td>

        <td class="text-center">
            <input type="checkbox" name="status[]" value="1">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-remove">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>

    </tr>

</script>




<script>
    window.route = {
        data: "{{ route('testing-points.data') }}",
        update: "{{ url('testing-points') }}/",
        deleteAttachment: "{{ route('testing-points.delete-attachment') }}",
        detail: "{{ url('testing-points') }}/",
        csrf: "{{ csrf_token() }}"
    }
</script>

<script src="{{ asset('assets/js/testing-points/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-points/index.js') }}"></script>
@endsection
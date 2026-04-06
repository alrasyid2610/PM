@extends('layouts.app')

@section('content')
<style>
    thead * { text-align: center }
</style>

<section class="section">
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Testing Items</h5>
                <a href="{{ route('testing-items.create') }}"
                   class="btn btn-primary">
                    Add Testing Item
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
<script>
window.route = {
    data: "{{ route('testing-items.data') }}",
    update: "{{ url('testing-items') }}/",
    detail: "{{ url('testing-items') }}/",
    csrf: "{{ csrf_token() }}"
}
</script>

<script src="{{ asset('assets/js/testing-items/index.js') }}"></script>
@endsection
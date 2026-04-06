@extends('layouts.app')
@section('content')
<style>
    thead * {
        text-align: center
    }
</style>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-2">List of Testing Units</h5>
            <hr>
            <a href="{{ route('testing-units.create') }}" class="btn btn-primary mb-4">Add Testing Unit</a> 
            <ul class="nav nav-tabs mb-3" id="brTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="data-tab" data-bs-toggle="tab" data-bs-target="#tab-data" type="button">Data</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#tab-detail" type="button">Detail</button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-data">
                    <div class="table-responsive">
                        <table id="{{ request()->segment(1) }}-table" class="table table-striped table-hover table-sm table-bordered w-100" data-datatable-auto-columns="true">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-detail">
                    <div id="detailContent" class="p-3 text-muted">Pilih data pada tab Data untuk melihat detail</div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('testing-units.data') }}",
        update: "{{ url('testing-units') }}/",
        csrf: "{{ csrf_token() }}",
        detail: "{{ url('testing-units') }}/",

    }
</script>

<script src="{{ asset('assets/js/testing-units/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-units/index.js') }}"></script>
@endsection
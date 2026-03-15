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
            <button id="toggleAdvanceSearch" class="btn btn-sm btn-link" type="button">Show</button>
        </div>
        <div id="advanceSearchForm" class="collapse">
            <div class="card-body">
                <form id="form-advance-search">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode</label>
                            <input type="text" id="kode_search" class="form-control" placeholder="Cari kode">
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="btn-search">Search</button>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">business-estates</h5>
                <a href="{{ route('business-estates.create') }}" class="btn btn-primary">Add Business Estate</a>
            </div>
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
                        <table id="business-estates-table" class="table table-striped table-hover table-sm table-bordered w-100" data-datatable-auto-columns="true">
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
        data: "{{ route('business-estates.data') }}",
        update: "{{ url('business-estates') }}/",
        csrf: "{{ csrf_token() }}"
    }
</script>
<script src="{{ asset('assets/js/business-estates/index.js') }}"></script>
@endsection
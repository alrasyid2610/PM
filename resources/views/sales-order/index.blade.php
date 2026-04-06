@extends('layouts.app')
@section('content')
    <style>
        thead * {
            text-align: center
        }

        #detailContent input.disabled,
        #detailContent textarea.disabled,
        #detailContent select.disabled {
            /* border: none; */
            background: #86868612;
            /* padding: 0; */
            /* margin: 0; */
        }
    </style>

    <section class="section">
        
        <div class="card">

            <div class="card-body">
                
                 {{-- Header + Add Button --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ Str::title(str_replace('-', ' ', request()->segment(1))); }}</h5>

                    <a href="{{ route('sales-orders.create') }}"
                    class="btn btn-primary">
                        Add {{ Str::title(str_replace('-', ' ', request()->segment(1))); }}
                    </a>
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
                            <table id="{{ request()->segment(1) }}-table"
                                class="table table-striped table-hover table-sm table-bordered w-100" data-datatable-auto-columns="true">
                                <thead>
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
    
@endsection


@section('custom-script')
<script>
    window.route = {
        summary: "{{ route('business-relations.summary') }}",
        data: "{{ route('sales-orders.data') }}",
        select2: "{{ route('business-relations.select2') }}",
        detail: "{{ url('sales-orders') }}/",
        site: "{{ url('business-relations/sites') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('sales-orders') }}/"
    }
</script>
<script src="{{ asset('assets/js/sales-order/index.js') }}"></script>
@endsection
@extends('layouts.app')

@section('page-title', 'Sales Orders')
@section('page-descrip', 'Kelola data Sales Orders')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sales Orders</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

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

                <x-datatable-header
                    title="List of Sales Orders"
                    create-route="sales-orders.create"
                    add-label="Add Data"
                    :with-history="true"
                />

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

                    <!-- TAB HISTORY -->
                    <div class="tab-pane fade" id="tab-history">
                        <div id="historyContent" class="p-3 text-muted">
                            Pilih data pada tab Data untuk melihat history
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
        history: "{{ url('sales-orders') }}/",
        site: "{{ url('business-relations/sites') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('sales-orders') }}/"
    }
</script>
<script src="{{ asset('assets/js/sales-order/index.js') }}"></script>
<script src="{{ asset('assets/js/sales-order/form.js') }}"></script>
@endsection
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
                
                <x-datatable-header
                    title="List of Work Orders"
                    create-route="work-orders.create"
                    add-label="Add Data"
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

                </div>
                
                
            </div>
        </div>
    </section>
    
@endsection


@section('custom-script')
<script>
    window.route = {
        summary: "{{ route('business-relations.summary') }}",
        data: "{{ route('work-orders.data') }}",
        select2: "{{ route('sales-orders.select2') }}",
        detail: "{{ url('work-orders') }}/",
        site: "{{ url('business-relations/sites') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('work-orders') }}/"
    }
</script>
<script src="{{ asset('assets/js/work-order/index.js') }}"></script>
@endsection
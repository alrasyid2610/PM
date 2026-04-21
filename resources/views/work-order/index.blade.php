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
                    :with-history="true"
                />

                <div class="tab-content">

                    <!-- TAB DATA -->
                    <div class="tab-pane fade show active" id="tab-data">
                        <div class="table-responsive">
                            <table id="{{ request()->segment(1) }}-table"
                                class="table table-hover table-striped table-sm w-100"
                                data-datatable-auto-columns="true">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
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
        data: "{{ route('work-orders.data') }}",
        history: "{{ url('work-orders') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('work-orders') }}/"
    }
</script>
<script src="{{ asset('assets/js/work-order/index.js') }}"></script>
<script src="{{ asset('assets/js/work-order/form.js') }}"></script>
@endsection
@extends('layouts.app')

@section('page-title', 'Business Relations')
@section('page-descrip', 'Kelola data Business Relations')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Business Relations</li>
    {{-- <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-units.index') }}">Testing Units</a>
    </li> --}}
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
    </style>

    <section class="section">
        <div class="row g-3 mb-4">
            <!-- Kantor Pusat -->
            <div class="col-md-6">
                <div class="summary-card summary-blue">
                    <div class="summary-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div class="summary-content">
                        <span class="summary-title">Total Kantor Pusat</span>
                        <h2 id="totalKantorPusat">0</h2>
                    </div>
                </div>
            </div>

            <!-- Kantor Cabang -->
            <div class="col-md-6">
                <div class="summary-card summary-light-blue">
                    <div class="summary-icon">
                        <i class="fa-solid fa-sitemap"></i>
                    </div>
                    <div class="summary-content">
                        <span class="summary-title">Total Kantor Cabang / Sites</span>
                        <h2 id="totalKantorCabang">0</h2>
                    </div>
                </div>
            </div>

        </div>
        
        <div class="card">

            <div class="card-body">
                
                <x-datatable-header
                    title="List of Business Relations"
                    create-route="business-relations.create"
                    add-label="Add Data"
                    :with-history="true"
                />
                
                <div class="tab-content">

                    <!-- TAB DATA -->
                    <div class="tab-pane fade show active" id="tab-data">
                        <div class="table-responsive">
                            {{-- Table Business Relations --}}
                            <div class="table-responsive">
                                <table id="{{ request()->segment(1) }}-table" class="table table-striped table-hover table-sm table-bordered w-100" data-datatable-auto-columns="true">
                                    <thead></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
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
        data: "{{ route('business-relations.data') }}",
        detail: "{{ url('business-relations') }}/",
        update: "{{ url('business-relations') }}/",
        history: "{{ url('business-relations') }}/",
        select2: "{{ route('business-relations.select2') }}",
        site: "{{ url('business-relations/sites') }}/",
        csrf: "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/business-relations/index.js') }}"></script>
<script src="{{ asset('assets/js/business-relations/form.js') }}"></script>
@endsection



@section('style')

    <style>
        .summary-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 22px 26px;
            border-radius: 14px;
            color: #fff;
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
            transition: all .25s ease;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.25);
        }

        .summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            background: rgba(255, 255, 255, 0.25);
        }

        .summary-content {
            display: flex;
            flex-direction: column;
        }

        .summary-title {
            font-size: 14px;
            opacity: 0.9;
        }

        .summary-content h2 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            color: white !important;
        }

        /* Variants */
        .summary-blue {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .summary-light-blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

            
        td.dt-control {
            cursor: pointer;
        }

        tr.shown td.dt-control i {
            transform: rotate(90deg);
            transition: transform .2s ease;
        }

    </style>

@endsection
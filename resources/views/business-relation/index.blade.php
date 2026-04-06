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
                
                 {{-- Header + Add Button --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Business Relations</h5>

                    <a href="{{ route('business-relations.create') }}"
                    class="btn btn-primary">
                        Add Business Relation
                    </a>
                </div>
                {{-- End Header + Add Button --}}
                
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
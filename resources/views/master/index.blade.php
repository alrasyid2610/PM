@extends('layouts.app')

@section('content')
<section class="section">
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ Str::title(str_replace('-', ' ', request()->segment(1))); }}</h5>

                <a href="/{{ request()->segment(1) }}/create" class="btn btn-primary">
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
                <div class="tab-pane fade show active"
                     id="tab-data"
                     role="tabpanel"
                     aria-labelledby="data-tab">
                    {{-- Data Table will be here --}}
                    <div class="table-responsive">
                        <table id="{{ request()->segment(1) }}-datatable" data-datatable="ya" class="table table-striped table-sm table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    @foreach($columns as $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                    </div>
                    
                </div>
                <div class="tab-pane fade"
                     id="tab-detail"
                     role="tabpanel"
                     aria-labelledby="detail-tab">
                        <div id="detailContent" class="mt-3">
                        </div>
                    {{-- Detail content will be here --}}
                </div>
            </div>
            
            

        </div>
    </div>
</section>

@endsection

@section('custom-script')
<script>
    window.route = {
        prefix: "{{ url(request()->segment(1)) }}/",
        csrf: "{{ csrf_token() }}"
    };

    
    var columnsConfig = [];
    const tableSelector = '#{{ request()->segment(1) }}-datatable';
    const tableEl = $(tableSelector);
    const pageTitle = "{{ Str::title(str_replace('-', ' ', request()->segment(1))) }}";

    $.ajax({
        url: "{{ route($routePrefix . '.data') }}",
        type: 'GET',
        dataType: 'json',
        success: function(response) {

            columnsConfig.push({ data: null, title: 'No' });

            response.header.forEach(function(col) {
                columnsConfig.push({ data: col, title: col });
            });

            const dt = tableEl.DataTable({
                data: response.data,
                columns: columnsConfig,
                columnDefs: [
                    {
                        targets: 0,
                        render: (d, t, r, m) => m.row + 1
                    }
                ]
            });
        }
    });


    function getPrimaryId(rowData) {
        if (!rowData || typeof rowData !== 'object') return null;

        const blacklist = [
            'id_br',
            'id_user',
            'id_created_by',
            'id_updated_by'
        ];

        const key = Object.keys(rowData).find(k =>
            k.startsWith('id_') && !blacklist.includes(k)
        );

        return key ? rowData[key] : null;
    }


    tableEl.find('tbody').on('click', 'tr', function () {

        const dt = tableEl.DataTable();
        const rowData = dt.row(this).data();

        console.log('row clicked:', rowData);

        const primaryId = getPrimaryId(rowData);

        if (!primaryId) {
            console.warn('Primary ID tidak ditemukan', rowData);
            return;
        }

        // highlight row
        tableEl.find('tr').removeClass('table-active');
        $(this).addClass('table-active');

        // pindah ke tab Detail
        const detailTabEl = document.querySelector('#detail-tab');
        if (detailTabEl) {
            const detailTab = new bootstrap.Tab(detailTabEl);
            detailTab.show();
        }

        // load detail (GENERIC)
        loadDetail(primaryId);
    });


        function renderTableFromObject(obj) {
            console.log("renderTableFromObject", obj);

            if (!obj || typeof obj !== 'object') {
                return `<div class="text-muted">Data tidak tersedia</div>`;
            }

            let html = `<table class="table table-sm">`;

            Object.entries(obj).forEach(([key, value]) => {

                if (key.startsWith('id_')) return;

                const label = key
                    .replaceAll('_', ' ')
                    .replace(/\b\w/g, l => l.toUpperCase());

                let displayValue = value ?? '-';

                if (key.includes('is_aktif')) {
                    displayValue = value == 1
                        ? "<span class='badge bg-primary'>Aktif</span>"
                        : "<span class='badge bg-secondary'>Non Aktif</span>";
                }

                html += `
                    <tr>
                        <th width="300">${label}</th>
                        <td>${displayValue}</td>
                    </tr>
                `;
            });

            html += `</table>`;
            return html;
        }

    function submit() {
        console.log("edit context clicked");
        var id = $("#btn-edit-context").data("id2");
        $.post(
            `${window.route.prefix}edit-context`,
            {
                _token: window.route.csrf,
                id: id,
            },
            function (res) {
                window.location.href = res.redirect;
            },
        );
    }


        

    function loadDetail(id) {

        $("#detailContent").html("Loading...");

        $.get(window.route.prefix + id + "/detail", function (res) {

            console.log("Detail data:", res);


            let PrimaryId = getPrimaryId(res);
            console.log("PrimaryId:", PrimaryId);
            
            // fallback jika controller belum dipisah
            const data = res;

            $("#detailContent").html(`
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>${pageTitle} kocak</h3>
                            <button
                                class="btn btn-warning btn-sm btn-edit-context"
                                id="btn-edit-context"
                                data-id2="${PrimaryId}"
                                title="Edit Business Relation" onclick="submit()">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                        ${renderTableFromObject(data)}
                    </div>
                </div>
            `);
        });
    }


    
</script>

@endsection

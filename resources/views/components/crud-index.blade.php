@props([
    'title',
    'createRoute'       => null,
    'addLabel'          => 'Add Data',
    'withHistory'       => true,
    'withSelesaiFilter' => false,
    'searchFields'      => [],
])

<x-datatable-header
    :title="$title"
    :create-route="$createRoute"
    :add-label="$addLabel"
    :with-history="$withHistory"
    :with-selesai-filter="$withSelesaiFilter"
>
    @if(!empty($searchFields))
    <x-slot name="extraTabs">
        <li class="nav-item">
            <button id="btnToggleDtSearch" class="nav-link" type="button" title="Cari data">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </li>
    </x-slot>
    @endif
</x-datatable-header>

<section class="section">
    <div class="page-index-wrap">
        @if(!empty($searchFields))
        <div id="dtSearchBar" style="display:none;overflow:hidden;transition:all .2s ease;">
            <div class="p-2 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                {{-- Baris-baris filter (diisi JS) --}}
                <div id="dtFilterRows" class="d-flex flex-column gap-2 mb-2"></div>
                {{-- Action bar --}}
                <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                    <button id="btnAddFilterRow" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Filter
                    </button>
                    <button id="btnDtSearch" class="btn btn-sm btn-primary" disabled>
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                    </button>
                    <button id="btnDtClearSearch" class="btn btn-sm btn-outline-secondary" style="display:none;">
                        <i class="fa-solid fa-xmark me-1"></i> Reset
                    </button>
                    <span id="dtSearchBadge" style="display:none;font-size:12px;color:#0891b2;background:#e0f2fe;padding:2px 10px;border-radius:20px;"></span>
                </div>
            </div>
        </div>
        <script>
            window._dtSearchFields = @json($searchFields);
        </script>
        @endif
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-data">
                <div class="table-responsive">
                    <table
                        id="{{ request()->segment(1) }}-table"
                        class="table table-hover table-striped table-sm w-100"
                        data-datatable-auto-columns="true">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-detail">
                <div id="detailContent" class="text-muted">
                    Pilih data pada tab Data untuk melihat detail
                </div>
            </div>

            @if($withHistory)
            <div class="tab-pane fade" id="tab-history">
                <div id="historyContent" class="text-muted">
                    Pilih data pada tab Data untuk melihat history
                </div>
            </div>
            @endif

        </div>
    </div>
</section>

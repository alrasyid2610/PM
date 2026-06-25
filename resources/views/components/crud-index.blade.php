@props([
    'title',
    'createRoute' => null,
    'addLabel'    => 'Add Data',
    'withHistory' => true,
])

<x-datatable-header
    :title="$title"
    :create-route="$createRoute"
    :add-label="$addLabel"
    :with-history="$withHistory"
/>

<section class="section">
    <div class="page-index-wrap">
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

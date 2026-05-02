@props([
    'title',
    'createRoute' => null,
    'addLabel'    => 'Add Data',
    'withHistory' => true,
])

<section class="section">
    <div class="card">
        <div class="card-body">

            <x-datatable-header
                :title="$title"
                :create-route="$createRoute"
                :add-label="$addLabel"
                :with-history="$withHistory"
            />

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
                    <div id="detailContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat detail
                    </div>
                </div>

                @if($withHistory)
                <div class="tab-pane fade" id="tab-history">
                    <div id="historyContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat history
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</section>

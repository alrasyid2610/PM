@props([
    'title',
    'createRoute'       => null,
    'addLabel'          => 'Add Data',
    'withHistory'       => true,
    'withSelesaiFilter' => false,
    'extraTabs'         => null,
])

<div class="page-header-bar">
    <div class="page-header-left">
        <div class="page-header-title">{{ $title }}</div>
    </div>
    <div class="page-header-right">
        @if($withSelesaiFilter)
        <div class="d-flex align-items-center gap-1" style="font-size:13px;color:#64748b;">
            <input type="checkbox" id="toggleShowSelesai" class="form-check-input mt-0"
                style="cursor:pointer;width:16px;height:16px;">
            <label for="toggleShowSelesai" style="cursor:pointer;user-select:none;">
                Tampilkan yang Selesai
            </label>
        </div>
        @endif
        @if($createRoute)
        @php
            $routePrefix = explode('.', $createRoute)[0];
            $slugMap = [
                'business-relation-sites'    => 'business-relations',
                'business-relation-contacts' => 'business-relations',
                'brs-sampling-points'        => 'business-relations',
                'brs-mp'                     => 'business-relations',
                'br-products'                => 'business-relations',
                'testing-items'              => 'testing-points',
                'fwo-budgets'                => 'fieldworks',
                'fwo-budget-actuals'         => 'fieldworks',
                'lab-samples'                => 'fieldworks',
                'fieldwork-boq'              => 'fieldworks',
                'output-pekerjaan'           => 'work-orders',
                'boq'                        => 'work-orders',
            ];
            $permSlug = $slugMap[$routePrefix] ?? $routePrefix;
        @endphp
        @if(userCan($permSlug, 'can_create'))
        <a href="{{ route($createRoute) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> {{ $addLabel ?? 'Add Data' }}
        </a>
        @endif
        @endif
        <ul class="nav nav-tabs" id="brTabs" role="tablist">
            {{ $extraTabs ?? '' }}
            <li class="nav-item">
                <button class="nav-link active" id="data-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-data"
                    type="button">Data</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="detail-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-detail"
                    type="button">Detail</button>
            </li>
            @if($withHistory)
            <li class="nav-item">
                <button class="nav-link" id="history-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-history"
                    type="button">History</button>
            </li>
            @endif
        </ul>
    </div>
</div>

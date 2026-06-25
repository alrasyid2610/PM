<div class="page-header-bar">
    <div class="page-header-left">
        <div class="page-header-title">{{ $title }}</div>
    </div>
    <div class="page-header-right">
        @if($createRoute)
        <a href="{{ route($createRoute) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> {{ $addLabel ?? 'Add Data' }}
        </a>
        @endif
        <ul class="nav nav-tabs" id="brTabs" role="tablist">
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

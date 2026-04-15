<div class="card-datatable-header">
    <div class="card-datatable-title">
        {{ $title }}
    </div>
    @if($createRoute)
    <a href="{{ route($createRoute) }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> {{ $addLabel ?? 'Add Data' }}
    </a>
    @endif
</div>
<hr>
<ul class="nav nav-tabs mb-3" id="brTabs" role="tablist">
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
</ul>
@props([
    'icon'      => 'fa-solid fa-chart-line',
    'iconColor' => '#6366f1',
    'iconBg'    => '#eef2ff',
    'value'     => '—',
    'label'     => 'Widget',
    'sub'       => null,
    'trend'     => null,
    'col'       => 3,
    'id'        => null,
])

<div class="{{ $col ? 'col-xl-'.$col.' col-md-'.min((int)$col * 2, 12).' col-12' : 'col' }}">
    <div class="dw-card {{ $attributes->has('data-tab') ? 'dw-card-clickable' : '' }}" {{ $attributes->filter(fn($v,$k) => str_starts_with($k,'data-')) }}>
        <div class="dw-top">
            <div class="dw-icon" style="background:{{ $iconBg }};color:{{ $iconColor }};">
                <i class="{{ $icon }}"></i>
            </div>
            @if($trend !== null)
            <div class="dw-trend {{ $trend >= 0 ? 'dw-trend-up' : 'dw-trend-down' }}">
                <i class="fa-solid {{ $trend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                <span>{{ abs($trend) }}</span>
            </div>
            @endif
        </div>

        <div class="dw-value" @if($id) id="{{ $id }}-value" @endif>{{ $value }}</div>
        <div class="dw-label">{{ $label }}</div>
        <div class="dw-sub" @if($id) id="{{ $id }}-sub" @endif>{{ $sub }}</div>
    </div>
</div>

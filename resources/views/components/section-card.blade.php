@props([
    'icon'     => 'fa-circle',
    'color'    => 'icon-navy',
    'title'    => '',
    'subtitle' => null,
])

<div class="detail-section-card" data-sc-open="true">
    <div class="detail-section-header">
        <div class="detail-section-icon {{ $color }}">
            <i class="fa-solid {{ $icon }}"></i>
        </div>
        <div class="detail-section-title">{{ $title }}</div>
        @if($subtitle)
            <div class="detail-section-sub">{{ $subtitle }}</div>
        @endif
        {{ $actions ?? '' }}
        <div class="detail-section-icon" style="background-color:#e5e5e5; flex-shrink:0; cursor:pointer;" onclick="scToggle(this, event)">
            <i class="fa-solid fa-chevron-up sc-chevron" style="transition:transform 0.25s;"></i>
        </div>
    </div>
    <div class="sc-body">
        <div class="detail-section-body">
            {{ $slot }}
        </div>
    </div>
</div>

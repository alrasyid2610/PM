<div id="sidebar" class='active'>
    <div class="sidebar-wrapper active">
        <div class="sidebar-header">
            <a href="/">
                <img style="width: 100%; height: 60px;" src="/assets/images/logo.png" alt="" srcset="">
            </a>
        </div>
        <div class="sidebar-search-wrap">
            <i class="fa-solid fa-magnifying-glass sidebar-search-icon"></i>
            <input type="text" id="sidebarSearch" class="sidebar-search-input" placeholder="Cari menu..." autocomplete="off">
            <button type="button" id="sidebarSearchClear" class="sidebar-search-clear">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">

                @foreach(config('menus') as $group)
                    @if(($group['show_in_sidebar'] ?? true) === false)
                        @continue
                    @endif

                    @php
                        $hasAccess = collect($group['items'])->contains(fn($item) => userCan($item['slug']));
                    @endphp

                    @if(!$hasAccess)
                        @continue
                    @endif

                    @if(isset($group['divider']))
                        <li class='sidebar-title'>{{ $group['divider'] }}</li>
                    @endif

                    @if($group['type'] === 'submenu')
                        @php
                            $patterns = collect($group['items'])->pluck('slug')->map(fn($s) => $s . '.*')->toArray();
                            $isActive = request()->routeIs($patterns);
                        @endphp
                        <li class="sidebar-item has-sub {{ $isActive ? 'active' : '' }}">
                            <a href="#" class='sidebar-link'>
                                <span>{{ $group['group'] }}</span>
                            </a>
                            <ul class="submenu">
                                @php $lastSection = null; @endphp
                                @foreach($group['items'] as $item)
                                    @if(!userCan($item['slug']))
                                        @continue
                                    @endif
                                    @if(isset($item['section']) && $item['section'] !== $lastSection)
                                        @php $lastSection = $item['section']; @endphp
                                        <li class="sidebar-title mt-2">{{ $item['section'] }}</li>
                                    @endif
                                    <li class="{{ request()->routeIs($item['slug'] . '.*') ? 'active' : '' }}">
                                        <a href="{{ route($item['slug'] . '.index') }}">{{ $item['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        @foreach($group['items'] as $item)
                            @if(!userCan($item['slug']))
                                @continue
                            @endif
                            <li class="sidebar-item {{ request()->routeIs($item['slug'] . '.*') ? 'active' : '' }}">
                                <a href="{{ route($item['slug'] . '.index') }}" class='sidebar-link'>
                                    <i class="fa-solid {{ $group['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endif

                @endforeach

            </ul>
        </div>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $search = $('#sidebarSearch');
    var $clear  = $('#sidebarSearchClear');

    $clear.hide();

    $search.on('input', function () {
        var q = $(this).val().trim().toLowerCase();
        $clear.toggle(q.length > 0);
        filterMenu(q);
    });

    $clear.on('click', function () {
        $search.val('').trigger('input').focus();
    });

    function filterMenu(q) {
        var $menu = $('.menu');

        if (!q) {
            $menu.find('.sf-hidden').removeClass('sf-hidden');
            $menu.find('.sf-open').removeClass('sf-open');
            return;
        }

        $menu.children('.sidebar-title').addClass('sf-hidden');

        $menu.children('.sidebar-item').each(function () {
            var $item = $(this);

            if ($item.hasClass('has-sub')) {
                var groupText  = $item.find('> .sidebar-link > span').text().toLowerCase();
                var groupMatch = groupText.includes(q);
                var anyMatch   = false;

                $item.find('.submenu').children('li').each(function () {
                    if ($(this).hasClass('sidebar-title')) {
                        $(this).addClass('sf-hidden');
                        return;
                    }
                    var itemText = $(this).text().trim().toLowerCase();
                    if (itemText.includes(q) || groupMatch) {
                        $(this).removeClass('sf-hidden');
                        anyMatch = true;
                    } else {
                        $(this).addClass('sf-hidden');
                    }
                });

                if (anyMatch) {
                    $item.removeClass('sf-hidden').addClass('sf-open');
                } else {
                    $item.addClass('sf-hidden').removeClass('sf-open');
                }
            } else {
                var linkText = $item.find('.sidebar-link span').text().toLowerCase();
                if (linkText.includes(q)) {
                    $item.removeClass('sf-hidden');
                } else {
                    $item.addClass('sf-hidden');
                }
            }
        });
    }
});
</script>

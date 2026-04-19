<div id="sidebar" class='active'>
    <div class="sidebar-wrapper active">
        <div class="sidebar-header">
            <a href="/">
                <img style="width: 100%; height: 60px;" src="/assets/images/logo.png" alt="" srcset="">
            </a>
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

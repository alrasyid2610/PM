<div id="sidebar" class='active'>
    <div class="sidebar-wrapper active">
        <div class="sidebar-header">
            <!-- <h1></h1> -->
            <!-- <img src="assets/images/logo.svg" alt="" srcset=""> -->
        </div>
        <div class="sidebar-menu">
            <ul class="menu">


                <li class='sidebar-title'>Main Menu</li>

                <li class="sidebar-item has-sub {{ request()->is('testing-*') ? 'active' : '' }}">
                    <a href="#" class='sidebar-link'>
                        <span>Master Data</span>
                    </a>

                    <ul class="submenu">

                        <li class="{{ request()->routeIs('business-relations.*') ? 'active' : '' }}">
                            <a href="{{ route('business-relations.index') }}">
                                Business Relation
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('business-estates.*') ? 'active' : '' }}">
                            <a href="{{ route('business-estates.index') }}">
                                Business Estate
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('commercial-buildings.*') ? 'active' : '' }}">
                            <a href="{{ route('commercial-buildings.index') }}">
                                Commercial Buildings
                            </a>
                        </li>

                        {{-- ===== Testing Section ===== --}}

                        <li class="sidebar-title mt-2">Testing</li>

                        <li class="{{ request()->routeIs('testing-units.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-units.index') }}">
                                Testing Units
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('testing-parameters.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-parameters.index') }}">
                                Testing Parameters
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('testing-kelompok-matriks-samples.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-kelompok-matriks-samples.index') }}">
                                Kelompok Matriks Sample
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('testing-matriks-samples.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-matriks-samples.index') }}">
                                Matriks Sample
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('testing-standards.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-standards.index') }}">
                                Testing Standards
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('testing-items.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-items.index') }}">
                                Testing Items
                            </a>
                        </li>

                        <li class="{{ request()->routeIs('testing-points.*') ? 'active' : '' }}">
                            <a href="{{ route('testing-points.index') }}">
                                Testing Points
                            </a>
                        </li>

                    </ul>
                </li>
                
                <li class="sidebar-item  has-sub">

                    <a href="#" class='sidebar-link'>
                        <!-- <i data-feather="triangle" width="20"></i> -->
                        <span>Menu</span>
                    </a>


                    <ul class="submenu ">

                        <li>
                            <a href="{{ route('sales-orders.index') }}">Sales Order</a>
                        </li>

                        <li>
                            <a href="{{ route('work-orders.index') }}">Work Order</a>
                        </li>

                        <li>
                            <a href="{{ route('boq.index') }}">BOQ</a>
                        </li>
                    </ul>

                </li>
                

            </ul>
        </div>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>
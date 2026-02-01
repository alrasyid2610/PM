<nav class="navbar navbar-header navbar-expand navbar-light">
    <a class="sidebar-toggler" href="#">
        <span class="navbar-toggler-icon"></span>
    </a>

    <button class="btn navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ms-auto align-items-center">

            {{-- NOTIFICATION (optional, static dulu) --}}
            <li class="nav-item dropdown me-2">
                <a href="#" data-bs-toggle="dropdown"
                   class="nav-link dropdown-toggle nav-link-lg">
                    <i data-feather="bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-large">
                    <h6 class="py-2 px-4">Notifications</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <small class="text-muted">Belum ada notifikasi</small>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- USER DROPDOWN --}}
            @auth
            <li class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown"
                   class="nav-link dropdown-toggle nav-link-lg nav-link-user d-flex align-items-center">

                    <div class="avatar me-2">
                        <img src="{{ asset('assets/images/avatar/avatar-s-1.png') }}"
                             alt="avatar">
                    </div>

                    <div class="d-none d-md-block">
                        Hi, {{ Auth::user()->name }}
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#">
                        <i data-feather="user" class="me-2"></i> Profile
                    </a>

                    <a class="dropdown-item" href="#">
                        <i data-feather="settings" class="me-2"></i> Settings
                    </a>

                    <div class="dropdown-divider"></div>

                    {{-- LOGOUT (POST) --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i data-feather="log-out" class="me-2"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
            @endauth

        </ul>
    </div>
</nav>

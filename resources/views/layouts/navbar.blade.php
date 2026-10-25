<nav class="navbar navbar-header navbar-expand navbar-light" style="padding-top: 0px">
    <a class="sidebar-toggler" href="#">
        <span class="navbar-toggler-icon"></span>
    </a>

    <button class="btn navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ms-auto align-items-center">

            {{-- HELP / DOCUMENTATION --}}
            <li class="nav-item me-2">
                <a href="{{ route('documentations.index') }}" class="nav-link nav-link-lg" title="Dokumentasi">
                    <i data-feather="help-circle"></i>
                </a>
            </li>

            {{-- NOTIFICATION — Fase 1: data dimuat saat dropdown dibuka / halaman pindah,
                 belum polling otomatis. Lihat public/assets/js/core/notifications.js --}}
            @auth
            <li class="nav-item dropdown me-2" id="notifBell">
                <a href="#" data-bs-toggle="dropdown" id="notifBellToggle"
                   class="nav-link dropdown-toggle nav-link-lg position-relative">
                    <i data-feather="bell"></i>
                    <span id="notifBadge" class="badge rounded-pill bg-danger position-absolute"
                          style="top:2px;right:2px;font-size:10px;display:none;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-large" style="width:340px;max-width:90vw;">
                    <div class="d-flex justify-content-between align-items-center py-2 px-3">
                        <h6 class="mb-0">Notifikasi</h6>
                        <button type="button" id="notifMarkAllRead" class="btn btn-link btn-sm p-0" style="font-size:12px;">
                            Tandai semua dibaca
                        </button>
                    </div>
                    <ul class="list-group list-group-flush" id="notifList" style="max-height:360px;overflow-y:auto;">
                        <li class="list-group-item">
                            <small class="text-muted">Memuat...</small>
                        </li>
                    </ul>
                </div>
            </li>
            @endauth

            {{-- USER DROPDOWN --}}
            @auth
            <li class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown"
                   class="nav-link dropdown-toggle nav-link-lg nav-link-user d-flex align-items-center">

                    <div class="avatar me-2">
                        <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : asset('assets/images/avatar/avatar-s-1.png') }}"
                             alt="avatar">
                    </div>

                    <div class="d-none d-md-block">
                        Hi, {{ Auth::user()->name }}
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i data-feather="user" class="me-2"></i> Profile
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

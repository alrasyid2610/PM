<!doctype html>
<html lang="id">
<head>
    <title>Pramatek — Sign In</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/all.min.css') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body, html {
            height: 100%;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f0f4f8;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

        /* ===== LEFT PANEL ===== */
        .login-left {
            width: 420px;
            flex-shrink: 0;
            background: linear-gradient(160deg, #0a1628 0%, #0d2146 40%, #1a3a6e 75%, #0f2d5a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: "";
            position: absolute;
            top: -80px; right: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(74, 158, 255, 0.08);
        }

        .login-left::after {
            content: "";
            position: absolute;
            bottom: -60px; left: -60px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(26, 95, 190, 0.12);
        }

        .left-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .left-logo {
            width: 180px;
            margin-bottom: 32px;
            filter: brightness(0) invert(1);
        }

        .left-divider {
            width: 48px;
            height: 3px;
            background: linear-gradient(90deg, #4a9eff, #1a5fbe);
            border-radius: 2px;
            margin: 0 auto 24px;
        }

        .left-tagline {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            line-height: 1.7;
            max-width: 280px;
        }

        .left-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 32px;
            background: rgba(74,158,255,0.15);
            border: 1px solid rgba(74,158,255,0.3);
            border-radius: 24px;
            padding: 6px 16px;
            color: #7ec8ff;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        /* ===== RIGHT PANEL ===== */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: #f0f4f8;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
        }

        .login-heading {
            margin-bottom: 32px;
        }

        .login-heading h1 {
            font-size: 26px;
            font-weight: 700;
            color: #0d2146;
            letter-spacing: -0.5px;
        }

        .login-heading p {
            color: #6b7280;
            font-size: 14px;
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: #111827;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .input-wrap input:focus {
            border-color: #1a5fbe;
            box-shadow: 0 0 0 3px rgba(26, 95, 190, 0.1);
        }

        .input-wrap input::placeholder {
            color: #d1d5db;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
            cursor: pointer;
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #1a5fbe;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: #1a5fbe;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1a5fbe, #0d2146);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            opacity: 0.92;
        }

        .btn-login:active {
            transform: scale(0.99);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            padding: 13px 16px;
            margin-bottom: 24px;
            color: #b91c1c;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        .alert-error i {
            margin-top: 1px;
            flex-shrink: 0;
            font-size: 15px;
            color: #dc2626;
        }

        .input-wrap.is-error input {
            border-color: #fca5a5;
            background: #fff9f9;
        }

        .input-wrap.is-error input:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .input-wrap.is-error .input-icon {
            color: #fca5a5;
        }

        @keyframes shake {
            0%   { transform: translateX(0); }
            20%  { transform: translateX(-6px); }
            40%  { transform: translateX(6px); }
            60%  { transform: translateX(-4px); }
            80%  { transform: translateX(4px); }
            100% { transform: translateX(0); }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-right { background: #fff; padding: 24px 20px; align-items: flex-start; padding-top: 60px; }
            .login-card { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">

        <!-- LEFT -->
        <div class="login-left">
            <div class="left-content">
                <img class="left-logo" src="{{ asset('assets/images/logo.png') }}" alt="Pramatek Logo">
                <div class="left-badge">
                    <i class="fa-solid fa-flask-vial"></i>
                    Laboratory Management System
                </div>
                <div class="left-divider"></div>
                <p class="left-tagline">
                    Kelola data pengujian laboratorium secara efisien, akurat, dan terstruktur.
                </p>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="login-right">
            <div class="login-card">

                <div class="login-heading">
                    <h1>Selamat datang</h1>
                    <p>Masuk ke akun Pramatek Anda</p>
                </div>

                @if ($errors->any())
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrap {{ $errors->has('email') ? 'is-error' : '' }}">
                            <i class="fa-regular fa-envelope input-icon"></i>
                            <input
                                type="text"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nama@pramatek.id"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap {{ $errors->has('email') ? 'is-error' : '' }}">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="remember-label">
                            <input type="checkbox" name="remember" id="remember">
                            Ingat saya
                        </label>
                        <a href="#" class="forgot-link">Lupa password?</a>
                    </div>

                                    <button type="submit" class="btn-login">
                        Masuk
                    </button>

                </form>
                {{-- <script>
                    document.getElementById('email').addEventListener('input', function () {
                        const val = this.value;
                        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
                        const wrap = this.closest('.input-wrap');
                        let msg = document.getElementById('email-format-msg');
                        if (val && !valid) {
                            wrap.classList.add('is-error');
                            if (!msg) {
                                msg = document.createElement('small');
                                msg.id = 'email-format-msg';
                                msg.style.cssText = 'color:#dc2626;font-size:12px;margin-top:4px;display:block;';
                                msg.textContent = 'Format email tidak valid';
                                wrap.insertAdjacentElement('afterend', msg);
                            }
                        } else {
                            wrap.classList.remove('is-error');
                            if (msg) msg.remove();
                        }
                    });

                    document.querySelector('form').addEventListener('submit', function (e) {
                        const emailEl = document.getElementById('email');
                        const val = emailEl.value;
                        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
                        if (!valid) {
                            e.preventDefault();
                            emailEl.focus();
                        }
                    });
                </script> --}}
            </div>
        </div>

    </div>
</body>
</html>

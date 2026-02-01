<!doctype html>
<html lang="en">
  <head>
    <title>Login V15</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="images/icons/favicon.ico" />
    <!--===============================================================================================-->

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/util.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/login-main.css') }}" />

</head>
  <body>
    <div class="limiter">
      <div class="container-login100">
        <div class="wrap-login100">
          <div
            class="login100-form-title"
            style="background-image: url({{ asset('assets/images/background/bg-01.jpg') }})"
          >
            <span class="login100-form-title-1"> Sign In </span>
          </div>

            @if ($errors->any())
                <div class="alert alert-danger text-center">
                    {{ $errors->first() }}
                </div>
            @endif


          <form class="login100-form validate-form" method="POST"
      action="{{ route('login.submit') }}">
            @csrf
            <div
              class="wrap-input100 validate-input m-b-26"
              data-validate="Username is required"
            >
              <span class="label-input100">Username</span>
              <input
                    class="input100"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Enter email"
                    required
                />
              <span class="focus-input100"></span>
            </div>

            <div
              class="wrap-input100 validate-input m-b-18"
              data-validate="Password is required"
            >
              <span class="label-input100">Password</span>
              <input
                    class="input100"
                    type="password"
                    name="password"
                    placeholder="Enter password"
                    required
                />
              <span class="focus-input100"></span>
            </div>

            <div class="flex-sb-m w-full p-b-30">
                <div class="form-check">
                    <input class="form-check-input"
                        type="checkbox"
                        name="remember"
                        id="remember">
                    <label class="form-check-label" for="flexCheckDefault">
                        Remember me
                    </label>
                </div>

                

              <div>
                <a href="#" class="txt1"> Forgot Password? </a>
              </div>
            </div>

            <div class="container-login100-form-btn">
              <button class="login100-form-btn">Login</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>

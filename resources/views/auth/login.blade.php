<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Login' }} | Chickventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <div class="login-shell">
        <div class="login-card">
            <div class="login-logo">
                <img src="{{ asset('images/ChickyLogo.jpg') }}" alt="Chicky Fryday Logo" class="brand-logo">
            </div>
            <p class="eyebrow" style="text-align:center;">Chicky Fryday</p>
            <h1 class="login-title">Inventory Management System</h1>
            <p class="login-subtitle">Sign in to continue</p>

            @if ($errors->any())
                <div class="alert" style="border-color:#f3b5b5;background:#fff5f5;">
                    <div class="alert-icon">!</div>
                    <div>
                        <strong>Login failed</strong>
                        <p>{{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf
                <label>
                    Username
                    <input type="text" name="username" value="{{ old('username') }}" autofocus autocomplete="username" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <label class="login-remember">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
                <button type="submit" class="orange-btn login-submit">Log In</button>
            </form>
        </div>
    </div>
</body>
</html>

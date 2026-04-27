<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Login</title>

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background-color: #f7fafc;
                color: #4a5568;
            }

            .login {
                position: fixed;
                top: 1.5rem;
                right: 1.5rem;
                font-size: 0.875rem;
                font-weight: 600;
            }

            .login a {
                color: inherit;
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        @if (Route::has('filament.admin.auth.login'))
            <div class="login">
                <a href="{{ route('filament.admin.auth.login') }}">Log in</a>
            </div>
        @endif
    </body>
</html>

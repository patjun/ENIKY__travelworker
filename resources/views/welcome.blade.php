<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Login</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full m-0 font-sans bg-slate-50 text-slate-600">
        @if (Route::has('filament.admin.auth.login'))
            <div class="fixed top-6 right-6 text-sm font-semibold">
                <a href="{{ route('filament.admin.auth.login') }}" class="text-accent underline hover:text-primary">Log in</a>
            </div>
        @endif
    </body>
</html>

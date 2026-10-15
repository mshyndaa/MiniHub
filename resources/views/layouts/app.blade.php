<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <script>
        // Apply saved theme (or the device preference) before first paint.
        (function () {
            var t = null;
            try { t = localStorage.getItem('theme'); } catch (e) {}
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-4">
        <a href="{{ auth()->check() && ! auth()->user()->must_change_password ? route('dashboard') : '#' }}"
           class="flex items-center gap-2 text-xl font-extrabold">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-sky-400 via-pink-400 to-yellow-300 text-white shadow">🎨</span>
            <span class="bg-gradient-to-r from-sky-500 via-pink-500 to-amber-400 bg-clip-text text-transparent">{{ config('app.name') }}</span>
        </a>

        <div class="flex items-center gap-2">
            <button type="button" data-theme-toggle aria-label="Toggle dark mode"
                    class="btn btn-soft h-10 w-10 !p-0 text-lg">
                <span class="dark:hidden">🌙</span><span class="hidden dark:inline">☀️</span>
            </button>
            @auth
                <span class="hidden text-sm font-bold sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-soft">Log out</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 pb-16">
        @if (session('status'))
            <div class="alert-ok mb-4">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>

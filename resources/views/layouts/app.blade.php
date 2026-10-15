<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f1f5f9" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0d0f15" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
    <script>
        // Apply saved theme / sidebar state before first paint.
        (function () {
            var t = null, s = null;
            try { t = localStorage.getItem('theme'); s = localStorage.getItem('sidebar'); } catch (e) {}
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
            if (s === 'collapsed') document.documentElement.classList.add('sidebar-collapsed');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    // The sidebar shell is for signed-in users who are free to use the hub.
    $shell = auth()->check() && ! auth()->user()->must_change_password;
@endphp
<body>
@if ($shell)
    @php
        $user = auth()->user();
        // Admin is a management tool, not a mini program: it gets its own spot at the bottom.
        $allApps = collect($user->apps());
        $adminApp = $allApps->first(fn ($a) => str_starts_with($a['route'], 'admin.'));
        $miniApps = $allApps->reject(fn ($a) => str_starts_with($a['route'], 'admin.'))->values();
    @endphp
    @include('partials.cloud-defs')

    {{-- Mobile top bar --}}
    <div class="topbar lg:hidden">
        <button type="button" data-sidebar-open aria-label="Open menu" class="btn btn-soft h-10 w-10 !p-0"><x-icon name="menu" /></button>
        <a href="{{ route('dashboard') }}" class="brand text-lg uppercase tracking-widest">
            <span class="brand-text">{{ config('app.name') }}</span>
        </a>
        <button type="button" data-theme-toggle aria-label="Toggle dark mode" class="btn btn-soft h-10 w-10 !p-0">
            <x-icon name="moon" class="dark:hidden" /><x-icon name="sun" class="hidden dark:block" />
        </button>
    </div>

    <div class="sidebar-backdrop" data-sidebar-close></div>

    <aside class="sidebar" id="sidebar">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('dashboard') }}" class="brand flex-1 justify-center px-1 text-xl uppercase tracking-widest">
                <span class="brand-text">{{ config('app.name') }}</span><span class="brand-short brand-text hidden">CJ</span>
            </a>
            <button type="button" data-sidebar-collapse class="collapse-btn" aria-label="Collapse sidebar" title="Collapse sidebar">
                <x-icon name="chevrons-left" class="h-5 w-5" />
            </button>
        </div>

        <nav class="mt-8 flex-1 space-y-1">
            <p class="nav-heading">Menu</p>
            <a href="{{ route('dashboard') }}" title="Dashboard" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                <span class="nav-icon"><x-icon name="home" /></span> <span class="nav-label">Dashboard</span>
            </a>

            @if ($miniApps->isNotEmpty())
                <p class="nav-heading mt-5">Mini programs</p>
                @foreach ($miniApps as $app)
                    <a href="{{ route($app['route']) }}" title="{{ $app['name'] }}"
                       class="nav-link {{ request()->routeIs(\Illuminate\Support\Str::before($app['route'], '.').'.*') ? 'nav-link-active' : '' }}">
                        <span class="nav-icon"><x-icon :name="$app['symbol']" /></span> <span class="nav-label">{{ $app['name'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="space-y-3 border-t border-slate-200/70 pt-4 dark:border-white/10">
            @if ($adminApp)
                <a href="{{ route($adminApp['route']) }}" title="{{ $adminApp['name'] }}" class="nav-link {{ request()->routeIs('admin.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-icon"><x-icon :name="$adminApp['symbol']" /></span> <span class="nav-label">{{ $adminApp['name'] }}</span>
                </a>
            @endif

            <a href="{{ route('profile') }}" title="Profile"
               class="profile-chip {{ request()->routeIs('profile') ? 'profile-chip-active' : '' }}">
                @include('partials.avatar', ['user' => $user, 'class' => 'h-11 w-11 rounded-xl text-lg'])
                <span class="pc-text min-w-0 flex-1">
                    <span class="block truncate text-sm font-extrabold">{{ $user->name }}</span>
                    <span class="block truncate text-xs opacity-60">View profile</span>
                </span>
                <x-icon name="chevron-right" class="pc-text h-4 w-4 opacity-40" />
            </a>

            <div class="side-grid grid grid-cols-2 gap-2">
                <button type="button" data-theme-toggle class="side-btn" title="Toggle theme">
                    <x-icon name="moon" class="h-4 w-4 dark:hidden" /><x-icon name="sun" class="hidden h-4 w-4 dark:block" />
                    <span class="side-lbl dark:hidden">Dark</span><span class="side-lbl hidden dark:inline">Light</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="side-btn side-btn-danger w-full" title="Log out">
                        <x-icon name="log-out" class="h-4 w-4" /><span class="side-lbl">Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="app-main">
        <div class="mx-auto {{ View::hasSection('wide') ? 'max-w-none' : 'max-w-5xl' }} px-4 py-6 sm:px-8 sm:py-10">
            @if (session('status'))
                <div class="alert-ok mb-6">{{ session('status') }}</div>
            @endif
            <div class="stagger">
                @yield('content')
            </div>
        </div>
    </main>
@else
    @unless (request()->routeIs('login'))
    <header class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-4 {{ request()->routeIs('login') ? 'justify-end' : 'justify-between' }}">
        @unless (request()->routeIs('login'))
            <a href="#" class="brand text-xl">
                <span class="brand-mark h-10 w-10 shrink-0">@include('partials.dino', ['class' => 'h-7 w-7'])</span>
                <span class="brand-text">{{ config('app.name') }}</span>
            </a>
        @endunless

        <div class="flex items-center gap-2">
            <button type="button" data-theme-toggle aria-label="Toggle dark mode" class="btn btn-soft h-10 w-10 !p-0">
                <x-icon name="moon" class="dark:hidden" /><x-icon name="sun" class="hidden dark:block" />
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
    @endunless

    <main class="mx-auto max-w-5xl px-4 pb-16">
        @if (session('status'))
            <div class="alert-ok mb-4">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
@endif
<script>
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    }
</script>
</body>
</html>

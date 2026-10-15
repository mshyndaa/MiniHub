@extends('layouts.app')
@section('title', 'Log in · '.config('app.name'))

@section('content')
{{-- Floating colour blobs, dots and icon bubbles behind the card --}}
<div class="login-blobs" aria-hidden="true">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
    <span class="blob blob-3"></span>
    <span class="login-dots"></span>
    <span class="float-icon" style="left:6%;top:18%;--d:0s;--s:3.5rem"><x-icon name="file-text" class="h-7 w-7" /></span>
    <span class="float-icon" style="left:42%;top:8%;--d:-2s;--s:2.5rem"><x-icon name="sparkles" class="h-5 w-5" /></span>
    <span class="float-icon" style="right:8%;top:14%;--d:-4s;--s:4rem"><x-icon name="square-check" class="h-8 w-8" /></span>
    <span class="float-icon" style="left:12%;bottom:14%;--d:-6s;--s:3rem"><x-icon name="palette" class="h-6 w-6" /></span>
    <span class="float-icon" style="left:48%;bottom:8%;--d:-3s;--s:3.5rem"><x-icon name="rocket" class="h-7 w-7" /></span>
    <span class="float-icon" style="right:10%;bottom:16%;--d:-5s;--s:3rem"><x-icon name="shield-check" class="h-6 w-6" /></span>
</div>

<div class="login-stage">
<div class="login-scale grid w-full max-w-4xl items-center gap-10 lg:grid-cols-2">
    {{-- Hero --}}
    <div class="hidden lg:block">
        <p class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-pink-500 shadow-sm dark:bg-white/10">
            <x-icon name="sparkles" class="h-3.5 w-3.5" /> All your mini programs · one place
        </p>
        <h2 class="text-5xl font-extrabold leading-tight">
            Small apps.<br>
            <span class="bg-gradient-to-r from-sky-500 via-pink-500 to-amber-400 bg-clip-text text-transparent">Big productivity.</span>
        </h2>
        <p class="mt-4 max-w-md text-lg opacity-70">{{ config('app.name') }} brings your everyday tools together behind a single login.</p>

        <div class="mt-8 grid max-w-md gap-3">
            @foreach ([['file-text','Notes','Jot down your thoughts.','from-yellow-300 to-amber-500'],['square-check','To-do','Keep track of what to do.','from-pink-400 to-rose-500'],['shield-check','Admin Panel','Manage users and roles.','from-sky-400 to-blue-500']] as $i => [$symbol,$name,$desc,$grad])
                <div class="feature-tile" style="animation-delay: {{ 0.2 + $i * 0.12 }}s">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br {{ $grad }} text-white shadow-md"><x-icon :name="$symbol" class="h-6 w-6" /></span>
                    <div>
                        <p class="font-extrabold">{{ $name }}</p>
                        <p class="text-sm opacity-70">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-sm">
        <div class="login-glow" aria-hidden="true"></div>

        <div class="card login-card relative !p-8">
            <button type="button" data-theme-toggle aria-label="Toggle dark mode" title="Toggle theme"
                    class="btn btn-soft absolute right-4 top-4 z-10 h-10 w-10 !p-0">
                <x-icon name="moon" class="dark:hidden" /><x-icon name="sun" class="hidden dark:block" />
            </button>
            <div class="mb-6 text-center">
                <div class="login-logo brand-mark mx-auto mb-4 h-16 w-16 overflow-hidden !rounded-3xl"><img src="{{ asset('favicon.svg') }}" alt="" class="h-full w-full"></div>
                <h1 class="text-3xl font-extrabold">
                    Welcome to
                    <span class="brand-text">{{ config('app.name') }}</span>
                </h1>
                <p class="mt-1 text-sm opacity-70">Log in to open your mini programs</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label" for="email">Email</label>
                    <div class="relative">
                        <x-icon name="mail" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input class="input !pl-12" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="username">
                    </div>
                    @error('email')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="password">Password</label>
                    <div class="relative">
                        <x-icon name="lock" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input class="input !pl-12 !pr-12" id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" id="toggle-password" aria-label="Show password" class="reveal-btn">
                            <span data-eye><x-icon name="eye" class="h-5 w-5" /></span>
                            <span data-eye-off class="hidden"><x-icon name="eye-off" class="h-5 w-5" /></span>
                        </button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="remember" class="h-4 w-4 accent-pink-500"> Remember me
                </label>
                <button class="btn btn-blue login-btn w-full !py-3 text-base">Log in <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </form>

            <p class="mt-5 text-center text-xs opacity-60">No account? Ask your admin to register you.</p>
        </div>
    </div>
</div>
</div>

<script>
    (function () {
        var btn = document.getElementById('toggle-password');
        var input = document.getElementById('password');
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('[data-eye]').classList.toggle('hidden', show);
            btn.querySelector('[data-eye-off]').classList.toggle('hidden', !show);
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    })();
</script>
@endsection

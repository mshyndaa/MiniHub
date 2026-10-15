@extends('layouts.app')
@section('title', 'Log in · '.config('app.name'))

@section('content')
{{-- Floating colour blobs, dots and icon bubbles behind the card --}}
<div class="login-sky" aria-hidden="true">
    <span class="sky-sun"></span>
    <span class="sky-rays"></span>
    <span class="sky-stars"></span>
    {{-- Bubbles rise from the bottom: [left %, size px, duration s, delay s, sway px] --}}
    @foreach ([
        [6, 38, 16, -2, 18], [14, 18, 11, -7, -14], [22, 64, 22, -12, 24], [31, 26, 13, -4, -20],
        [40, 46, 19, -15, 16], [49, 16, 10, -9, -12], [57, 72, 24, -6, -26], [66, 30, 14, -1, 20],
        [74, 52, 20, -17, -18], [82, 20, 12, -10, 14], [90, 42, 17, -5, -22], [96, 24, 15, -13, 12],
        [10, 30, 18, -16, 16], [36, 14, 9, -3, -10], [62, 34, 16, -8, 18], [86, 58, 23, -19, -24],
    ] as [$left, $size, $dur, $delay, $sway])
        <span class="bubble" style="left:{{ $left }}%;width:{{ $size }}px;height:{{ $size }}px;--t:{{ $dur }}s;--d:{{ $delay }}s;--s:{{ $sway }}px"></span>
    @endforeach
    <svg width="0" height="0" class="absolute">
        <defs>
            <filter id="blur-bank" x="-5%" y="-30%" width="110%" height="160%"><feGaussianBlur stdDeviation="4"/></filter>
            <filter id="cloud-edge" filterUnits="userSpaceOnUse" x="-10" y="-10" width="280" height="140"><feGaussianBlur stdDeviation="0.9"/></filter>
            <linearGradient id="cloud-fill" gradientUnits="userSpaceOnUse" x1="0" y1="20" x2="0" y2="116">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="0.55" stop-color="#ffffff"/>
            <stop offset="1" stop-color="#cfe4fa"/>
        </linearGradient>
            <symbol id="cloud" viewBox="-10 -10 280 140">
            <g filter="url(#cloud-edge)" fill="url(#cloud-fill)">
                <circle cx="46" cy="84" r="26"/><circle cx="86" cy="62" r="34"/><circle cx="132" cy="48" r="40"/><circle cx="178" cy="62" r="34"/><circle cx="216" cy="84" r="26"/>
                <rect x="20" y="82" width="222" height="32" rx="16"/>
            </g>
            <g fill="#ffffff" opacity=".85"><circle cx="118" cy="36" r="16"/><circle cx="150" cy="40" r="11"/><circle cx="80" cy="54" r="10"/></g>
        </symbol>
            <symbol id="bird" viewBox="0 0 28 12"><path d="M1 8 Q7 0 14 7 Q21 0 27 8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
        </defs>
    </svg>


    {{-- Birds --}}
    @foreach ([['top:16%', 28, 60, -8], ['top:18%', 22, 75, -34], ['top:14%', 20, 68, -48]] as [$top, $w, $dur, $delay])
        <svg class="bird" viewBox="0 0 28 12" style="{{ $top }};width:{{ $w }}px;--t:{{ $dur }}s;--d:{{ $delay }}s"><use href="#bird"/></svg>
    @endforeach

</div>

<div class="login-stage">
<div class="login-scale grid w-full max-w-4xl items-center gap-10 lg:grid-cols-2">
    {{-- Hero --}}
    <div class="hidden lg:block">
        <p class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-pink-500 shadow-sm dark:bg-white/10">
            <x-icon name="sparkles" class="h-3.5 w-3.5" /> All your mini programs · one place
        </p>
        <h2 class="hero-text text-5xl font-extrabold leading-tight text-white">
            Small apps.<br>
            <span class="text-amber-300">Big productivity.</span>
        </h2>
        <p class="hero-text mt-4 max-w-md text-lg font-semibold text-white/90">Hakuna matata, no worries today.</p>

        <div class="mt-8 grid max-w-md gap-3">
            @foreach ([['file-text','Notes made easy','Jot down ideas anytime, anywhere.','from-yellow-300 to-amber-500'],['square-check','Tasks, sorted','Plan your day and check things off.','from-pink-400 to-rose-500'],['shield-check','Everything in one place','One login for all your tools.','from-sky-400 to-blue-500']] as $i => [$symbol,$name,$desc,$grad])
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
                <div class="login-logo brand-mark mx-auto mb-4 h-20 w-20 !rounded-3xl">@include('partials.dino', ['class' => 'h-[3.75rem] w-[3.75rem]'])</div>
                <h1 class="text-3xl font-extrabold">
                    Welcome to
                    <span class="brand-text">{{ config('app.name') }}</span>
                </h1>
                <p class="mt-1 text-sm opacity-70">Log in to open your mini programs</p>
            </div>

            @if (session('status'))
                <p class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ session('status') }}</p>
            @endif
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

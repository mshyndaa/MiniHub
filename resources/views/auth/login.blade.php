@extends('layouts.app')
@section('title', 'Log in · '.config('app.name'))

@section('content')
{{-- Floating colour blobs, dots and icon bubbles behind the card --}}
<div class="login-sky" aria-hidden="true">
    <span class="sky-sun"></span>
    <span class="sky-rays"></span>
    <span class="sky-stars"></span>
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

    {{-- Far / mid / near clouds drift across the top at different speeds --}}
    @foreach ([
        ['top:1%', 260, 120, -30, 1], ['top:7%', 180, 170, -95, 0.95],
        ['top:13%', 120, 230, -150, 0.75], ['top:4%', 150, 200, -10, 0.85],
    ] as [$top, $w, $dur, $delay, $op])
        <svg class="cloud" viewBox="-10 -10 280 140" style="{{ $top }};width:{{ $w }}px;opacity:{{ $op }};--t:{{ $dur }}s;--d:{{ $delay }}s"><use href="#cloud"/></svg>
    @endforeach

    {{-- Birds --}}
    @foreach ([['top:16%', 28, 60, -8], ['top:18%', 22, 75, -34], ['top:14%', 20, 68, -48]] as [$top, $w, $dur, $delay])
        <svg class="bird" viewBox="0 0 28 12" style="{{ $top }};width:{{ $w }}px;--t:{{ $dur }}s;--d:{{ $delay }}s"><use href="#bird"/></svg>
    @endforeach

    {{-- Cloud bank along the bottom, three layers --}}
    <svg class="cloud-bank cloud-bank-back" viewBox="0 0 1600 300" preserveAspectRatio="xMidYMax slice"><g filter="url(#blur-bank)" fill="#bcdcf7"><circle cx="-20" cy="150" r="100"/><circle cx="120" cy="187" r="123"/><circle cx="260" cy="184" r="116"/><circle cx="400" cy="181" r="109"/><circle cx="540" cy="178" r="102"/><circle cx="680" cy="175" r="125"/><circle cx="820" cy="172" r="118"/><circle cx="960" cy="169" r="111"/><circle cx="1100" cy="166" r="104"/><circle cx="1240" cy="163" r="127"/><circle cx="1380" cy="160" r="120"/><circle cx="1520" cy="157" r="113"/><circle cx="1660" cy="154" r="106"/><circle cx="1800" cy="151" r="129"/><circle cx="1940" cy="188" r="122"/><rect x="0" y="200" width="1600" height="100"/></g></svg>
    <svg class="cloud-bank cloud-bank-mid" viewBox="0 0 1600 300" preserveAspectRatio="xMidYMax slice"><g filter="url(#blur-bank)" fill="#e3f1fd"><circle cx="-60" cy="200" r="95"/><circle cx="90" cy="231" r="114"/><circle cx="240" cy="226" r="105"/><circle cx="390" cy="221" r="96"/><circle cx="540" cy="216" r="115"/><circle cx="690" cy="211" r="106"/><circle cx="840" cy="206" r="97"/><circle cx="990" cy="201" r="116"/><circle cx="1140" cy="232" r="107"/><circle cx="1290" cy="227" r="98"/><circle cx="1440" cy="222" r="117"/><circle cx="1590" cy="217" r="108"/><circle cx="1740" cy="212" r="99"/><circle cx="1890" cy="207" r="118"/><rect x="0" y="240" width="1600" height="60"/></g></svg>
    <svg class="cloud-bank cloud-bank-front" viewBox="0 0 1600 300" preserveAspectRatio="xMidYMax slice"><g filter="url(#blur-bank)" fill="#ffffff"><circle cx="-10" cy="240" r="90"/><circle cx="150" cy="269" r="105"/><circle cx="310" cy="268" r="94"/><circle cx="470" cy="267" r="109"/><circle cx="630" cy="266" r="98"/><circle cx="790" cy="265" r="113"/><circle cx="950" cy="264" r="102"/><circle cx="1110" cy="263" r="91"/><circle cx="1270" cy="262" r="106"/><circle cx="1430" cy="261" r="95"/><circle cx="1590" cy="260" r="110"/><circle cx="1750" cy="259" r="99"/><circle cx="1910" cy="258" r="114"/><rect x="0" y="275" width="1600" height="25"/></g></svg>
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
        <p class="hero-text mt-4 max-w-md text-lg font-semibold text-white/90">{{ config('app.name') }} brings your everyday tools together behind a single login.</p>

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
                <div class="login-logo brand-mark mx-auto mb-4 h-20 w-20 !rounded-3xl">@include('partials.dino', ['class' => 'h-[3.75rem] w-[3.75rem]'])</div>
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

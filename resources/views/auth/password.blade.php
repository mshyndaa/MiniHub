@extends('layouts.app')
@section('title', 'Change password · '.config('app.name'))
@section('no-header', '1')

@section('content')
@php
    $eye = '<svg class="pw-eye-on h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
          .'<svg class="pw-eye-off hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 5.1-5.9M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2M1 1l22 22"/></svg>';
@endphp
<div class="pw-page" data-pw>
  <div class="pw-scale mx-auto grid items-center gap-10 lg:grid-cols-[1fr_32rem] lg:gap-24">
    <aside class="pw-aside hidden lg:block">
        <p class="pw-kicker">Account security</p>
        <h2 class="mt-3 text-4xl font-black leading-tight tracking-tight">Make it <span class="brand-text">yours.</span></h2>
        <p class="mt-3 max-w-sm text-base opacity-70">A strong password keeps your hub, your notes and your stock data safe. Here's what makes a good one:</p>

        <ul class="mt-7 space-y-3" data-pw-rules>
            @foreach ([
                ['len', 'At least 8 characters', 'The longer the better'],
                ['case', 'Upper & lower case', 'Mix Aa for extra strength'],
                ['num', 'Includes a number', 'Like 4, 7 or 2026'],
                ['sym', 'Includes a symbol', 'Such as ! ? # or @'],
            ] as [$key, $title, $hint])
                <li class="pw-rule" data-rule="{{ $key }}">
                    <span class="pw-rule-dot"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg></span>
                    <span><span class="block text-sm font-extrabold">{{ $title }}</span><span class="block text-xs opacity-60">{{ $hint }}</span></span>
                </li>
            @endforeach
        </ul>
    </aside>

  <div class="pw-wrap mx-auto w-full max-w-md lg:max-w-none">
    <div class="pw-glow"></div>
    <div class="card pw-card">
        <button type="button" data-theme-toggle aria-label="Toggle dark mode" class="pw-theme">
            <x-icon name="moon" class="dark:hidden" /><x-icon name="sun" class="hidden dark:block" />
        </button>
        <div class="pw-badge">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="3"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/><circle cx="12" cy="16" r="1.2" fill="currentColor"/></svg>
        </div>

        <h1 class="text-center text-2xl font-black tracking-tight">Change password</h1>
        @if (auth()->user()->must_change_password)
            <p class="mx-auto mb-6 mt-1 max-w-xs text-center text-sm opacity-70">Your password was set by an admin. Please choose your own before continuing.</p>
        @else
            <p class="mb-6 mt-1 text-center text-sm opacity-70">Pick a new password.</p>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="label" for="current_password">Current password</label>
                <div class="pw-field">
                    <input class="input pr-12" id="current_password" name="current_password" type="password" required autocomplete="current-password" placeholder="••••••••">
                    <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password">{!! $eye !!}</button>
                </div>
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="pw-sep"><span>new password</span></div>

            <div>
                <label class="label" for="password">New password</label>
                <div class="pw-field">
                    <input class="input pr-12" id="password" name="password" type="password" required autocomplete="new-password" placeholder="New password" data-pw-new>
                    <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password">{!! $eye !!}</button>
                </div>
                <div class="mt-2 flex items-center gap-1.5" aria-hidden="true">
                    <span class="pw-seg" data-pw-seg></span><span class="pw-seg" data-pw-seg></span><span class="pw-seg" data-pw-seg></span><span class="pw-seg" data-pw-seg></span>
                    <span class="ml-2 w-14 text-right text-xs font-extrabold opacity-70" data-pw-label></span>
                </div>
                <ul class="pw-chips lg:hidden">
                    <li data-rule="len">8+ characters</li>
                    <li data-rule="case">Aa mix</li>
                    <li data-rule="num">Number</li>
                    <li data-rule="sym">Symbol</li>
                </ul>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="password_confirmation">Confirm new password</label>
                <div class="pw-field">
                    <input class="input pr-12" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Type it once more" data-pw-confirm>
                    <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password">{!! $eye !!}</button>
                </div>
                <p class="mt-1 hidden text-sm font-bold" data-pw-match></p>
            </div>

            <button class="btn btn-pink pw-submit w-full py-3! text-base">Save password</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button class="text-sm font-bold opacity-60 transition hover:opacity-100">Log out</button>
        </form>
    </div>
  </div>
  </div>
</div>

<script>
(function () {
    var root = document.querySelector('[data-pw]');
    if (!root) return;

    root.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('.pw-eye-on').classList.toggle('hidden', show);
            btn.querySelector('.pw-eye-off').classList.toggle('hidden', !show);
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    var pw = root.querySelector('[data-pw-new]'),
        cf = root.querySelector('[data-pw-confirm]'),
        segs = root.querySelectorAll('[data-pw-seg]'),
        label = root.querySelector('[data-pw-label]'),
        match = root.querySelector('[data-pw-match]'),
        names = ['', 'Weak', 'Fair', 'Good', 'Strong'],
        colors = ['', '#f43f5e', '#f59e0b', '#38bdf8', '#10b981'];

    function strength(v) {
        if (!v) return 0;
        var s = 0;
        if (v.length >= 8) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) s++;
        return Math.max(1, s);
    }
    var tests = {
        len: function (v) { return v.length >= 8; },
        case: function (v) { return /[a-z]/.test(v) && /[A-Z]/.test(v); },
        num: function (v) { return /\d/.test(v); },
        sym: function (v) { return /[^A-Za-z0-9]/.test(v); }
    };
    function update() {
        root.querySelectorAll('[data-rule]').forEach(function (li) {
            li.classList.toggle('pw-rule-ok', tests[li.dataset.rule](pw.value));
        });
        var n = strength(pw.value);
        segs.forEach(function (el, i) { el.style.background = i < n ? colors[n] : ''; });
        label.textContent = names[n];
        label.style.color = colors[n];
        if (!cf.value) { match.classList.add('hidden'); return; }
        var ok = cf.value === pw.value;
        match.classList.remove('hidden');
        match.textContent = ok ? '✓ Passwords match' : '✗ Passwords don’t match yet';
        match.style.color = ok ? '#10b981' : '#f43f5e';
    }
    pw.addEventListener('input', update);
    cf.addEventListener('input', update);
})();
</script>
@endsection

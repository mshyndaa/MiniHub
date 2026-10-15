@extends('layouts.app')
@section('title', 'Profile · '.config('app.name'))

@php
    $details = [
        ['user', 'Full name', $user->name],
        ['mail', 'Email', $user->email],
        ['calendar', 'Member since', $user->created_at?->format('j F Y')],
    ];
@endphp

@section('content')
@error('image')
    <div class="mb-4 rounded-2xl bg-rose-100 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
@enderror

{{-- Cover --}}
<section class="profile-cover @unless ($user->bannerUrl()) profile-cover-flat @endunless" @if ($user->bannerUrl()) style="background-image: url('{{ $user->bannerUrl() }}'); background-size: cover; background-position: center;" @endif>
    @unless ($user->bannerUrl())
        <span class="sky-sunglow"></span>
        <svg class="hero-cloud" style="left:6%;top:18%;width:190px;--t:10s" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
        <svg class="hero-cloud" style="left:38%;top:44%;width:150px;--t:13s;opacity:.9" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
        <svg class="hero-cloud" style="left:66%;top:8%;width:210px;--t:11s" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
        <svg class="hero-cloud" style="left:84%;top:52%;width:130px;--t:9s;opacity:.85" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    @endunless

    <div class="absolute bottom-3 right-3 flex gap-2">
        <form method="POST" action="{{ route('profile.media.update', 'banner') }}" enctype="multipart/form-data" data-upload>
            @csrf
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" data-file>
            <button type="button" data-pick class="cover-btn" title="JPG, PNG, WebP or GIF, max 5 MB"><x-icon name="camera" class="h-4 w-4" /> {{ $user->bannerUrl() ? 'Change' : 'Add' }} banner</button>
        </form>
        @if ($user->bannerUrl())
            <form method="POST" action="{{ route('profile.media.destroy', 'banner') }}" onsubmit="return confirm('Remove your banner?')">
                @csrf @method('DELETE')
                <button class="cover-btn" aria-label="Remove banner"><x-icon name="trash" class="h-4 w-4" /></button>
            </form>
        @endif
    </div>
</section>

{{-- Identity --}}
<div class="relative z-10 -mt-14 flex flex-col items-center gap-4 px-4 text-center sm:flex-row sm:items-start sm:px-8 sm:text-left">
    <div class="relative shrink-0">
        <div class="profile-avatar">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
            @else
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            @endif
        </div>
        <form method="POST" action="{{ route('profile.media.update', 'avatar') }}" enctype="multipart/form-data" data-upload>
            @csrf
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" data-file>
            <button type="button" data-pick class="avatar-btn" aria-label="Change profile photo" title="Change photo (JPG, PNG, WebP or GIF, max 5 MB)"><x-icon name="camera" class="h-4 w-4" /></button>
        </form>
    </div>
    <div class="pb-1 sm:pt-[4.5rem]">
        <h1 class="text-3xl font-extrabold leading-tight tracking-tight">{{ $user->name }}</h1>
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"><x-icon name="mail" class="h-3.5 w-3.5" /> {{ $user->email }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-200/70 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300"><x-icon name="calendar" class="h-3.5 w-3.5" /> Joined {{ $user->created_at?->format('M Y') }}</span>
            @if ($user->avatarUrl())
                <form method="POST" action="{{ route('profile.media.destroy', 'avatar') }}" onsubmit="return confirm('Remove your profile photo?')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-600 transition hover:bg-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:hover:bg-rose-500/25"><x-icon name="trash" class="h-3.5 w-3.5" /> Remove photo</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="stagger mt-6 grid gap-6 lg:grid-cols-5">
    {{-- Account details --}}
    <section class="card lg:col-span-2">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold"><x-icon name="id-card" class="h-5 w-5 text-sky-500" /> Account</h2>

        <div class="space-y-3">
            @foreach ($details as [$symbol, $label, $value])
                <div class="info-row">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300"><x-icon :name="$symbol" /></span>
                    <div class="min-w-0">
                        <p class="text-xs font-extrabold uppercase tracking-wider opacity-50">{{ $label }}</p>
                        <p class="truncate font-bold">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mb-2 mt-6 text-xs font-extrabold uppercase tracking-wider opacity-50">Can open</p>
        <div class="grid gap-2">
            @forelse ($apps as $app)
                <a href="{{ route($app['route']) }}" class="info-row group transition hover:border-sky-400">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-500 text-white transition group-hover:scale-110"><x-icon :name="$app['symbol']" /></span>
                    <span class="flex-1 font-bold">{{ $app['name'] }}</span>
                    <x-icon name="chevron-right" class="h-4 w-4 opacity-30 transition group-hover:translate-x-1 group-hover:opacity-70" />
                </a>
            @empty
                <p class="text-sm opacity-60">Nothing yet — ask your admin.</p>
            @endforelse
        </div>
    </section>

    {{-- Preferences: appearance + app lock --}}
    <section class="card order-last lg:col-span-5">
        <div class="grid gap-8 md:grid-cols-2">
            <div>
                <h2 class="mb-1 flex items-center gap-2 text-lg font-extrabold"><x-icon name="palette" class="h-5 w-5 text-sky-500" /> Appearance</h2>
                <p class="mb-4 text-sm opacity-70">System follows your device and switches automatically.</p>
                <div class="seg" role="group" aria-label="Theme">
                    <button type="button" class="seg-btn" data-theme-set="light"><x-icon name="sun" class="h-4 w-4" /> Light</button>
                    <button type="button" class="seg-btn" data-theme-set="dark"><x-icon name="moon" class="h-4 w-4" /> Dark</button>
                    <button type="button" class="seg-btn" data-theme-set="system"><x-icon name="monitor" class="h-4 w-4" /> System</button>
                </div>
            </div>

            <div data-applock-panel data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                <h2 class="mb-1 flex items-center gap-2 text-lg font-extrabold"><x-icon name="fingerprint" class="h-5 w-5 text-sky-500" /> Biometric lock</h2>
                <p class="mb-1 text-sm opacity-70">Ask for your fingerprint, face or device PIN to open the app. It locks the moment you leave it. This applies to this device only.</p>
                <p class="mb-4 text-sm font-bold" data-applock-status>Checking…</p>
                <button type="button" class="btn btn-blue hidden" data-applock-enable><x-icon name="fingerprint" class="h-4 w-4" /> Turn on biometric lock</button>
                <button type="button" class="btn btn-soft hidden" data-applock-disable>Turn off</button>
                <p class="hidden text-sm opacity-70" data-applock-note>This device or browser doesn't support biometric unlock (it needs HTTPS and a fingerprint, face or PIN set up on the device).</p>
            </div>
        </div>
    </section>

    {{-- Security --}}
    <section class="card flex flex-col lg:col-span-3">
        <div class="flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300"><x-icon name="lock" class="h-5 w-5" /></span>
            <div>
                <h2 class="text-lg font-extrabold leading-tight">Change password</h2>
                <p class="text-sm opacity-70">Pick something new — at least 8 characters.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 flex flex-1 flex-col space-y-5" id="pw-form">
            @csrf @method('PUT')
            <div>
                <label class="label" for="current_password">Current password</label>
                <div class="relative">
                    <input class="input !pr-12" id="current_password" name="current_password" type="password" required autocomplete="current-password" placeholder="Enter your current password">
                    <button type="button" data-reveal class="reveal-btn" aria-label="Show password"><x-icon name="eye" class="h-5 w-5" /></button>
                </div>
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>

            <hr class="border-slate-200 dark:border-white/10">

            <div>
                <label class="label" for="password">New password</label>
                <div class="relative">
                    <input class="input !pr-12" id="password" name="password" type="password" required autocomplete="new-password" placeholder="At least 8 characters">
                    <button type="button" data-reveal class="reveal-btn" aria-label="Show password"><x-icon name="eye" class="h-5 w-5" /></button>
                </div>
                @error('password')<p class="error">{{ $message }}</p>@enderror

                {{-- Strength meter: only appears once you start typing --}}
                <div class="mt-3 hidden" id="strength-wrap">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-1 gap-1.5">
                            <span class="strength-bar"></span><span class="strength-bar"></span><span class="strength-bar"></span><span class="strength-bar"></span>
                        </div>
                        <span class="w-16 text-right text-xs font-extrabold" id="strength-label"></span>
                    </div>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ([["len", "8+ characters"], ["case", "Aa upper & lower"], ["num", "Number"], ["sym", "Symbol"]] as [$rule, $text])
                            <li class="rule inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 transition dark:bg-white/5 dark:text-slate-400" data-rule="{{ $rule }}">
                                <x-icon name="circle-check" class="h-3.5 w-3.5 opacity-40" /> {{ $text }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div>
                <label class="label" for="password_confirmation">Confirm new password</label>
                <div class="relative">
                    <input class="input !pr-12" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Type it again">
                    <button type="button" data-reveal class="reveal-btn" aria-label="Show password"><x-icon name="eye" class="h-5 w-5" /></button>
                </div>
                <p class="mt-1 hidden text-sm font-semibold" id="match-msg"></p>
            </div>

            <div class="mt-auto flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5 dark:border-white/10">
                <button class="btn btn-blue disabled:pointer-events-none disabled:opacity-40" id="pw-submit" disabled><x-icon name="save" class="h-4 w-4" /> Update password</button>
                <p class="text-xs opacity-60">Tip: a passphrase of a few random words beats a short, complicated password.</p>
            </div>
        </form>
    </section>
</div>

{{-- Swap target for the show/hide button (see script) --}}
<template id="icon-eye"><x-icon name="eye" class="h-5 w-5" /></template>
<template id="icon-eye-off"><x-icon name="eye-off" class="h-5 w-5" /></template>

<script>
    (function () {
        // Photo / banner pickers: validate size on the client, then upload straight away.
        var MAX = 5 * 1024 * 1024;
        document.querySelectorAll('form[data-upload]').forEach(function (form) {
            var file = form.querySelector('[data-file]');
            form.querySelector('[data-pick]').addEventListener('click', function () { file.click(); });
            file.addEventListener('change', function () {
                if (!file.files.length) return;
                if (file.files[0].size > MAX) {
                    alert('That image is larger than 5 MB. Please choose a smaller one.');
                    file.value = '';
                    return;
                }
                form.submit();
            });
        });

        var eye = document.getElementById('icon-eye').innerHTML, eyeOff = document.getElementById('icon-eye-off').innerHTML;
        document.querySelectorAll('[data-reveal]').forEach(function (b) {
            b.addEventListener('click', function () {
                var i = b.previousElementSibling, show = i.type === 'password';
                i.type = show ? 'text' : 'password';
                b.innerHTML = show ? eyeOff : eye;
            });
        });


        var pw = document.getElementById('password'), cf = document.getElementById('password_confirmation'), cur = document.getElementById('current_password');
        var bars = document.querySelectorAll('.strength-bar'), label = document.getElementById('strength-label');
        var wrap = document.getElementById('strength-wrap'), msg = document.getElementById('match-msg'), submit = document.getElementById('pw-submit');
        var names = ['', 'Weak', 'Okay', 'Good', 'Strong'];
        var colors = ['', 'bg-rose-400', 'bg-amber-400', 'bg-sky-400', 'bg-emerald-400'];
        var textColors = ['', 'text-rose-500', 'text-amber-500', 'text-sky-500', 'text-emerald-500'];

        function score(v) {
            var s = 0;
            if (v.length >= 8) s++;
            if (v.length >= 12) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
            return v ? Math.max(s, 1) : 0;
        }
        function update() {
            var v = pw.value, s = score(v);
            wrap.classList.toggle('hidden', !v);
            bars.forEach(function (b, i) {
                colors.forEach(function (c) { if (c) b.classList.remove(c); });
                if (i < s) b.classList.add(colors[s]);
            });
            textColors.forEach(function (c) { if (c) label.classList.remove(c); });
            label.textContent = names[s];
            if (s) label.classList.add(textColors[s]);

            var ok = { len: v.length >= 8, case: /[a-z]/.test(v) && /[A-Z]/.test(v), num: /\d/.test(v), sym: /[^A-Za-z0-9]/.test(v) };
            document.querySelectorAll('[data-rule]').forEach(function (li) {
                var met = ok[li.dataset.rule];
                li.classList.toggle('bg-emerald-100', met);
                li.classList.toggle('text-emerald-700', met);
                li.classList.toggle('dark:bg-emerald-500/20', met);
                li.classList.toggle('dark:text-emerald-300', met);
                li.classList.toggle('bg-slate-100', !met);
                li.classList.toggle('text-slate-500', !met);
                li.querySelector('svg').classList.toggle('opacity-40', !met);
            });

            var match = cf.value && cf.value === v;
            msg.classList.toggle('hidden', !cf.value);
            msg.textContent = match ? 'Passwords match.' : "Passwords don't match yet.";
            msg.classList.toggle('text-emerald-600', !!match);
            msg.classList.toggle('text-rose-500', !match);

            submit.disabled = !(cur.value && ok.len && match);
        }
        [pw, cf, cur].forEach(function (el) { el.addEventListener('input', update); });
        update();
    })();
</script>
@endsection

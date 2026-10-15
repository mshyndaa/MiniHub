@extends('layouts.app')
@section('title', 'Profile · '.config('app.name'))

@php
    $tiles = array_filter([
        ['file-text', 'Notes', $stats['notes'], 'from-yellow-200 to-amber-300'],
        ['hourglass', 'To do', $stats['open'], 'from-pink-200 to-rose-300'],
        ['circle-check', 'Completed', $stats['done'], 'from-emerald-200 to-teal-300'],
        ['puzzle', 'Mini programs', count($apps), 'from-sky-200 to-blue-300'],
    ], fn ($t) => $t[2] !== null);

    $details = [
        ['user', 'Full name', $user->name, 'from-sky-400 to-blue-500'],
        ['mail', 'Email', $user->email, 'from-pink-400 to-rose-500'],
        ['calendar', 'Member since', $user->created_at?->format('j F Y'), 'from-yellow-300 to-amber-500'],
    ];
    $appTint = ['blue' => 'from-sky-400 to-blue-500', 'pink' => 'from-pink-400 to-rose-500', 'yellow' => 'from-yellow-300 to-amber-500'];
@endphp

@section('content')
@error('image')
    <div class="mb-4 rounded-2xl bg-rose-100 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
@enderror

{{-- Cover --}}
<section class="profile-cover" @if ($user->bannerUrl()) style="background-image: url('{{ $user->bannerUrl() }}'); background-size: cover; background-position: center;" @endif>
    @unless ($user->bannerUrl())
        <span class="hero-orb hero-orb-1"></span>
        <span class="hero-orb hero-orb-2"></span>
        <span class="float-icon" style="right:8%;top:20%;--d:0s;--s:3rem"><x-icon name="sparkles" class="h-6 w-6" /></span>
        <span class="float-icon" style="right:26%;bottom:16%;--d:-3s;--s:2.5rem"><x-icon name="palette" class="h-5 w-5" /></span>
    @endunless

    <div class="absolute bottom-3 right-3 flex gap-2">
        <form method="POST" action="{{ route('profile.media.update', 'banner') }}" enctype="multipart/form-data" data-upload>
            @csrf
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" data-file>
            <button type="button" data-pick class="cover-btn"><x-icon name="camera" class="h-4 w-4" /> {{ $user->bannerUrl() ? 'Change' : 'Add' }} banner</button>
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
<div class="relative z-10 -mt-14 flex flex-col items-center gap-4 px-4 text-center sm:-mt-12 sm:flex-row sm:items-end sm:px-8 sm:text-left">
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
            <button type="button" data-pick class="avatar-btn" aria-label="Change profile photo" title="Change photo"><x-icon name="camera" class="h-4 w-4" /></button>
        </form>
    </div>
    <div class="pb-1 sm:pt-14">
        <h1 class="text-3xl font-extrabold leading-tight">{{ $user->name }}</h1>
        <p class="text-sm opacity-70">{{ $user->email }}</p>
        <p class="mt-1 text-xs opacity-60">
            JPG, PNG, WebP or GIF · max 5 MB
            @if ($user->avatarUrl())
                ·
                <form method="POST" action="{{ route('profile.media.destroy', 'avatar') }}" class="inline" onsubmit="return confirm('Remove your profile photo?')">
                    @csrf @method('DELETE')
                    <button class="font-bold text-rose-500 hover:underline">Remove photo</button>
                </form>
            @endif
        </p>
    </div>
</div>

{{-- Stats --}}
@if (count($tiles))
    <div class="stagger mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($tiles as [$symbol, $label, $value, $grad])
            <div class="stat-tile bg-gradient-to-br {{ $grad }} dark:opacity-90">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/60 text-slate-700 shadow-sm"><x-icon :name="$symbol" /></span>
                <p class="mt-3 text-3xl font-extrabold text-slate-800" data-count="{{ $value }}">{{ $value }}</p>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-700/70">{{ $label }}</p>
            </div>
        @endforeach
    </div>
@endif

<div class="stagger mt-6 grid gap-6 lg:grid-cols-5">
    {{-- Account details --}}
    <section class="card lg:col-span-2">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold"><x-icon name="id-card" class="h-5 w-5 text-sky-500" /> Account</h2>

        <div class="space-y-3">
            @foreach ($details as [$symbol, $label, $value, $grad])
                <div class="info-row">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-sm"><x-icon :name="$symbol" /></span>
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
                <a href="{{ route($app['route']) }}" class="info-row group transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br {{ $appTint[$app['color']] ?? $appTint['blue'] }} text-white shadow-sm transition group-hover:rotate-6"><x-icon :name="$app['symbol']" /></span>
                    <span class="flex-1 font-bold">{{ $app['name'] }}</span>
                    <x-icon name="chevron-right" class="h-4 w-4 opacity-30 transition group-hover:translate-x-1 group-hover:opacity-70" />
                </a>
            @empty
                <p class="text-sm opacity-60">Nothing yet — ask your admin.</p>
            @endforelse
        </div>
    </section>

    {{-- Security --}}
    <section class="card flex flex-col lg:col-span-3">
        <h2 class="flex items-center gap-2 text-lg font-extrabold"><x-icon name="lock" class="h-5 w-5 text-pink-500" /> Security</h2>
        <p class="mb-5 text-sm opacity-70">Change your password. Use at least 8 characters.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="label" for="current_password">Current password</label>
                <div class="relative">
                    <input class="input !pr-12" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                    <button type="button" data-reveal class="reveal-btn" aria-label="Show password"><x-icon name="eye" class="h-5 w-5" /></button>
                </div>
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="password">New password</label>
                    <div class="relative">
                        <input class="input !pr-12" id="password" name="password" type="password" required autocomplete="new-password">
                        <button type="button" data-reveal class="reveal-btn" aria-label="Show password"><x-icon name="eye" class="h-5 w-5" /></button>
                    </div>
                    @error('password')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="password_confirmation">Confirm new password</label>
                    <div class="relative">
                        <input class="input !pr-12" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        <button type="button" data-reveal class="reveal-btn" aria-label="Show password"><x-icon name="eye" class="h-5 w-5" /></button>
                    </div>
                    <p class="mt-1 hidden text-sm font-semibold text-rose-500" id="mismatch">Passwords don't match yet.</p>
                </div>
            </div>

            {{-- Strength meter --}}
            <div>
                <div class="flex gap-1.5">
                    <span class="strength-bar"></span><span class="strength-bar"></span><span class="strength-bar"></span><span class="strength-bar"></span>
                </div>
                <p class="mt-1 text-xs font-bold opacity-70" id="strength-label">Strength: —</p>
            </div>

            {{-- Requirements checklist --}}
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ([["len", "At least 8 characters"], ["case", "Upper & lowercase letters"], ["num", "At least one number"], ["sym", "At least one symbol"]] as [$rule, $text])
                    <li class="rule flex items-center gap-2 text-sm font-semibold opacity-60 transition" data-rule="{{ $rule }}">
                        <span class="rule-dot grid h-5 w-5 shrink-0 place-items-center rounded-full bg-slate-200 text-transparent transition dark:bg-slate-700"><x-icon name="circle-check" class="h-3.5 w-3.5" /></span>
                        {{ $text }}
                    </li>
                @endforeach
            </ul>

            <button class="btn btn-pink"><x-icon name="save" class="h-4 w-4" /> Save password</button>
        </form>

        <div class="mt-6 flex gap-3 rounded-2xl bg-gradient-to-br from-sky-100 to-pink-100 p-4 text-sm dark:from-sky-500/10 dark:to-pink-500/10 lg:mt-auto">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/70 text-sky-500 shadow-sm dark:bg-white/10"><x-icon name="shield-check" /></span>
            <p class="opacity-80"><span class="font-extrabold">Tip:</span> a long passphrase of a few random words is stronger and easier to remember than a short, complicated password. Never reuse a password from another site.</p>
        </div>
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

        var pw = document.getElementById('password'), cf = document.getElementById('password_confirmation');
        var bars = document.querySelectorAll('.strength-bar'), label = document.getElementById('strength-label');
        var names = ['Too weak', 'Weak', 'Okay', 'Good', 'Strong'];
        var colors = ['bg-rose-400', 'bg-orange-400', 'bg-amber-400', 'bg-lime-400', 'bg-emerald-400'];

        function score(v) {
            var s = 0;
            if (v.length >= 8) s++;
            if (v.length >= 12) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
            return v ? Math.max(s, 1) : 0;
        }
        function update() {
            var s = score(pw.value);
            bars.forEach(function (b, i) {
                colors.forEach(function (c) { b.classList.remove(c); });
                if (i < s) b.classList.add(colors[s]);
            });
            label.textContent = 'Strength: ' + (pw.value ? names[s] : '—');
            var v = pw.value, ok = {
                len: v.length >= 8,
                case: /[a-z]/.test(v) && /[A-Z]/.test(v),
                num: /d/.test(v),
                sym: /[^A-Za-z0-9]/.test(v)
            };
            document.querySelectorAll('[data-rule]').forEach(function (li) {
                var met = ok[li.dataset.rule], dot = li.querySelector('.rule-dot');
                li.classList.toggle('opacity-60', !met);
                li.classList.toggle('text-emerald-600', met);
                dot.classList.toggle('bg-emerald-400', met);
                dot.classList.toggle('text-white', met);
                dot.classList.toggle('text-transparent', !met);
            });
            document.getElementById('mismatch').classList.toggle('hidden', !cf.value || cf.value === pw.value);
        }
        pw.addEventListener('input', update);
        cf.addEventListener('input', update);
    })();
</script>
@endsection

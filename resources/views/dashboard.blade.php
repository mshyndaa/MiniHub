@extends('layouts.app')
@section('title', 'Dashboard · '.config('app.name'))

@php
    $tint = [
        'blue' => 'from-sky-200 to-blue-300 dark:from-sky-500/40 dark:to-blue-600/40',
        'pink' => 'from-pink-200 to-rose-300 dark:from-pink-500/40 dark:to-rose-600/40',
        'yellow' => 'from-yellow-200 to-amber-300 dark:from-yellow-500/40 dark:to-amber-600/40',
    ];
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $first = explode(' ', trim(auth()->user()->name))[0];
@endphp

@section('content')
<section class="hero-banner">
    <div class="relative z-10">
        <p class="text-sm font-bold uppercase tracking-widest opacity-80">{{ now()->format('l, j F Y') }}</p>
        <h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">{{ $greeting }}, {{ $first }}</h1>
        <p class="mt-2 max-w-md opacity-90">Pick a mini program below and get things done.</p>
    </div>
    <span class="hero-orb hero-orb-1"></span>
    <span class="hero-orb hero-orb-2"></span>
    <span class="hero-icon"><x-icon name="sparkles" class="h-12 w-12" /></span>
</section>

<div>
    <h2 class="mb-4 mt-8 text-lg font-extrabold">Your mini programs</h2>

    @if (count($apps))
        <div class="stagger grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($apps as $key => $app)
                <a href="{{ route($app['route']) }}"
                   class="app-card group bg-gradient-to-br {{ $tint[$app['color']] ?? $tint['blue'] }}">
                    <div class="flex items-start justify-between">
                        <span class="app-icon"><x-icon :name="$app['symbol']" class="h-7 w-7" /></span>
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-white/60 opacity-0 transition group-hover:translate-x-1 group-hover:opacity-100 dark:bg-white/10"><x-icon name="arrow-right" class="h-4 w-4" /></span>
                    </div>
                    <h3 class="mt-5 text-xl font-extrabold text-slate-800 dark:text-white">{{ $app['name'] }}</h3>
                    <p class="text-sm text-slate-700/80 dark:text-slate-200/80">{{ $app['description'] }}</p>
                    @isset($stats[$key])
                        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/60 px-3 py-1 text-xs font-extrabold text-slate-700 dark:bg-white/10 dark:text-slate-100">
                            <span data-count="{{ $stats[$key]['n'] }}">{{ $stats[$key]['n'] }}</span> {{ $stats[$key]['label'] }}
                        </span>
                    @endisset
                </a>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <span class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-white/70 text-slate-500 shadow dark:bg-white/10"><x-icon name="lock" class="h-7 w-7" /></span>
            <h2 class="text-xl font-extrabold">No access yet</h2>
            <p class="opacity-70">You haven't been given any roles. Please contact your admin.</p>
        </div>
    @endif
</div>
@endsection

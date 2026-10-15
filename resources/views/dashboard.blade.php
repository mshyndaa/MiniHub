@extends('layouts.app')

@php
    $tint = [
        'blue' => 'from-sky-200 to-blue-300 dark:from-sky-500/40 dark:to-blue-600/40',
        'pink' => 'from-pink-200 to-rose-300 dark:from-pink-500/40 dark:to-rose-600/40',
        'yellow' => 'from-yellow-200 to-amber-300 dark:from-yellow-500/40 dark:to-amber-600/40',
    ];
@endphp

@section('content')
<h1 class="mb-1 text-3xl font-extrabold">Hi, {{ auth()->user()->name }} ✨</h1>
<p class="mb-6 opacity-70">Pick a mini program to get started.</p>

@if (count($apps))
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($apps as $app)
            <a href="{{ route($app['route']) }}"
               class="group rounded-3xl bg-gradient-to-br {{ $tint[$app['color']] ?? $tint['blue'] }} p-5 shadow-lg transition hover:-translate-y-1 hover:shadow-xl">
                <div class="mb-3 text-4xl transition group-hover:scale-110">{{ $app['icon'] }}</div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-white">{{ $app['name'] }}</h2>
                <p class="text-sm text-slate-700/80 dark:text-slate-200/80">{{ $app['description'] }}</p>
            </a>
        @endforeach
    </div>
@else
    <div class="card text-center">
        <div class="mb-2 text-5xl">🔒</div>
        <h2 class="text-xl font-extrabold">No access yet</h2>
        <p class="opacity-70">You haven't been given any roles. Please contact your admin.</p>
    </div>
@endif
@endsection

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
    <span class="sky-sunglow"></span>
    <svg class="hero-cloud" style="left:60%;top:8%;width:170px;--t:9s" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    <svg class="hero-cloud" style="left:48%;top:58%;width:120px;--t:12s;opacity:.85" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    <svg class="hero-cloud" style="left:-3%;top:62%;width:170px;--t:11s;opacity:.9" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    <div class="relative z-10 max-w-md">
        <p class="inline-flex items-center gap-1.5 rounded-full bg-white/25 px-3 py-1 text-xs font-extrabold uppercase tracking-widest text-white"><x-icon name="calendar" class="h-3.5 w-3.5" /> {{ now()->format('l, j F') }}</p>
        <h1 class="hero-text mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $greeting }}, {{ $first }}!</h1>
        <p class="hero-text mt-2 font-semibold text-white/90">Pick a mini program below and get things done.</p>
    </div>
    <div class="hero-dino" aria-hidden="true">
        <span class="hero-bubble">Hi!</span>
        @include('partials.dino', ['class' => 'h-28 w-28 text-white drop-shadow-lg sm:h-36 sm:w-36'])
    </div>
</section>

@unless (count($apps))
    <div class="empty-state mt-8">
        @include('partials.dino', ['class' => 'mx-auto mb-3 h-16 w-16 text-sky-300'])
        <h2 class="text-xl font-extrabold">No access yet</h2>
        <p class="opacity-70">You haven't been given any roles. Please contact your admin.</p>
    </div>
@endunless
@endsection

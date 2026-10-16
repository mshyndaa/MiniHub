@extends('layouts.app')
@section('title', 'Dashboard · '.config('app.name'))

@php
    // Admin is a management tool, not a mini program: it lives in the sidebar only.
    $miniApps = collect($apps)->reject(fn ($a) => str_starts_with($a['route'], 'admin.'))->values();
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $first = explode(' ', trim(auth()->user()->name))[0];
    $unread = $announcements->filter(fn ($a) => $a->created_at > $announcementsSince)->count();
    $chip = [
        'blue' => 'text-sky-700 dark:text-sky-200',
        'pink' => 'text-rose-700 dark:text-rose-200',
        'yellow' => 'text-amber-700 dark:text-amber-200',
    ];
@endphp

@section('content')
<section class="hero-banner">
    <span class="sky-sunglow"></span>
    <svg class="hero-cloud" style="left:60%;top:8%;width:170px;--t:9s" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    <svg class="hero-cloud" style="left:48%;top:58%;width:120px;--t:12s;opacity:.85" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    <svg class="hero-cloud" style="left:-3%;top:62%;width:170px;--t:11s;opacity:.9" viewBox="-10 -10 280 140" aria-hidden="true"><use href="#cloud"/></svg>
    <div class="relative z-10 max-w-md">
        <p class="inline-flex items-center gap-1.5 rounded-full bg-white/25 px-3 py-1 text-xs font-extrabold uppercase tracking-widest text-white"><x-icon name="calendar" class="h-3.5 w-3.5" /> <span data-local-date>{{ now()->format('l, j F') }}</span></p>
        <h1 class="hero-text mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl"><span data-local-greeting>{{ $greeting }}</span>, {{ $first }}!</h1>
        <p class="hero-text mt-2 font-semibold text-white/90">
            @if ($unread > 0) You have {{ $unread }} new {{ \Illuminate\Support\Str::plural('notification', $unread) }}. @else Pick a mini program below and get things done. @endif
        </p>
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

<div class="mt-8 grid gap-8 lg:grid-cols-5">
    {{-- Mini programs --}}
    @if ($miniApps->isNotEmpty())
        <section class="lg:col-span-3">
            <h2 class="mb-3 px-1 text-sm font-bold uppercase tracking-widest opacity-50">Your mini programs</h2>
            @php $single = $miniApps->count() === 1; @endphp
            <div class="stagger grid gap-4 {{ $single ? '' : 'sm:grid-cols-2' }}">
                @foreach ($miniApps as $app)
                    @php $stat = $stats[\Illuminate\Support\Str::before($app['route'], '.')] ?? null; @endphp
                    <a href="{{ route($app['route']) }}" class="app-card app-card-{{ $app['color'] }} group flex flex-col gap-4 {{ $single ? 'sm:flex-row sm:items-center sm:gap-6' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <span class="app-icon transition-transform group-hover:scale-110 group-hover:-rotate-6"><x-icon :name="$app['symbol']" class="h-7 w-7" /></span>
                            @if ($stat)
                                <span class="rounded-full bg-white/70 px-3 py-1 text-xs font-extrabold dark:bg-white/15 {{ $chip[$app['color']] ?? '' }}">{{ $stat['n'] }} {{ $stat['label'] }}</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-black leading-tight">{{ $app['name'] }}</h3>
                            @if ($app['description'])<p class="mt-1 text-sm font-semibold opacity-70">{{ $app['description'] }}</p>@endif
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-sm font-extrabold {{ $chip[$app['color']] ?? '' }}">
                            Open <x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Notifications --}}
    <section class="{{ $miniApps->isNotEmpty() ? 'lg:col-span-2' : 'lg:col-span-5' }}">
        <div class="mb-3 flex items-baseline justify-between px-1">
            <h2 class="text-sm font-bold uppercase tracking-widest opacity-50">Notifications</h2>
            <a href="{{ route('notifications.index') }}" class="text-sm font-semibold text-sky-600 hover:underline dark:text-sky-300">View all</a>
        </div>
        <div class="card p-2!">
            @forelse ($announcements as $item)
                <a href="{{ route('notifications.index') }}" class="flex items-start gap-3 rounded-2xl p-3 transition hover:bg-sky-50 dark:hover:bg-white/5">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-400/15 dark:text-amber-300"><x-icon name="bell" class="h-5 w-5" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-extrabold leading-tight">
                            {{ $item->title }}
                            @if ($item->created_at > $announcementsSince)<span class="ml-1 rounded-full bg-rose-500 px-2 py-0.5 text-[11px] font-bold text-white">New</span>@endif
                        </span>
                        <span class="mt-0.5 line-clamp-2 block whitespace-pre-line break-words text-sm opacity-70">{{ $item->body }}</span>
                        <span class="mt-1 block text-xs font-semibold opacity-50">{{ $item->author?->name ?? 'Admin' }} · {{ $item->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-400 dark:bg-white/10"><x-icon name="bell" class="h-6 w-6" /></span>
                    <p class="font-extrabold">All quiet</p>
                    <p class="text-sm opacity-60">New announcements will show up here.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection

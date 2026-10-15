@extends('layouts.app')
@section('title', 'To-do · '.config('app.name'))

@php
    $total = count($todos);
    $done = collect($todos)->where('done', true)->count();
    $pct = $total ? round($done / $total * 100) : 0;
@endphp

@section('content')
@include('partials.page-header', ['symbol' => 'square-check', 'title' => 'To-do', 'subtitle' => $total ? "$done of $total done" : 'Keep track of what to do', 'tint' => 'pink'])

@if ($total)
    <div class="mb-6">
        <div class="mb-1 flex justify-between text-xs font-extrabold uppercase tracking-wider opacity-60">
            <span>Progress</span><span><span data-count="{{ $pct }}">{{ $pct }}</span>%</span>
        </div>
        <div class="h-3 overflow-hidden rounded-full bg-white/70 shadow-inner dark:bg-white/10">
            <div class="h-full rounded-full bg-gradient-to-r from-pink-400 to-amber-300 transition-all duration-700" style="width: {{ $pct }}%"></div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('todo.store') }}" class="card mb-6 flex gap-2">
    @csrf
    <input class="input" name="text" placeholder="What needs doing?" required>
    <button class="btn btn-pink shrink-0"><x-icon name="plus" class="h-4 w-4" /> Add</button>
</form>
@error('text')<p class="error mb-4">{{ $message }}</p>@enderror

<div class="stagger space-y-2">
    @forelse ($todos as $todo)
        <div class="card flex items-center gap-3 !py-3 transition hover:-translate-y-0.5">
            <form method="POST" action="{{ route('todo.toggle', $todo) }}">
                @csrf @method('PUT')
                <button aria-label="Toggle done"
                        class="grid h-8 w-8 place-items-center rounded-full border-2 transition hover:scale-110
                               {{ $todo->done ? 'border-pink-400 bg-gradient-to-br from-pink-400 to-rose-400 text-white' : 'border-sky-300' }}">
                    @if ($todo->done)<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@endif
                </button>
            </form>
            <span class="flex-1 {{ $todo->done ? 'line-through opacity-50' : 'font-semibold' }}">{{ $todo->text }}</span>
            <form method="POST" action="{{ route('todo.destroy', $todo) }}">
                @csrf @method('DELETE')
                <button class="grid h-9 w-9 place-items-center rounded-xl text-rose-500 transition hover:bg-rose-100 dark:hover:bg-rose-500/20" aria-label="Delete"><x-icon name="trash" class="h-4 w-4" /></button>
            </form>
        </div>
    @empty
        <div class="empty-state">
            <span class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-white/70 text-pink-500 shadow dark:bg-white/10"><x-icon name="sparkles" class="h-7 w-7" /></span>
            <p class="font-extrabold">Nothing to do</p>
            <p class="text-sm opacity-70">Enjoy the free time!</p>
        </div>
    @endforelse
</div>
@endsection

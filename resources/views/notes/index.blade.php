@extends('layouts.app')
@section('title', 'Notes · '.config('app.name'))

@php
    $count = count($notes);
    $accents = ['border-t-amber-300', 'border-t-pink-300', 'border-t-sky-300', 'border-t-emerald-300', 'border-t-violet-300'];
@endphp

@section('content')
@include('partials.page-header', ['symbol' => 'file-text', 'title' => 'Notes', 'subtitle' => $count.' '.\Illuminate\Support\Str::plural('note', $count).' · jot down your thoughts', 'tint' => 'yellow'])

<form method="POST" action="{{ route('notes.store') }}" class="card mb-8 space-y-3">
    @csrf
    <input class="input font-bold" name="title" placeholder="Title" value="{{ old('title') }}" required>
    @error('title')<p class="error">{{ $message }}</p>@enderror
    <textarea class="input" name="body" rows="3" placeholder="Write something…">{{ old('body') }}</textarea>
    <div class="text-right"><button class="btn btn-yellow"><x-icon name="plus" class="h-4 w-4" /> Add note</button></div>
</form>

<div class="stagger grid gap-5 sm:grid-cols-2">
    @forelse ($notes as $note)
        <div class="card border-t-4 {{ $accents[$loop->index % count($accents)] }}">
            <form method="POST" action="{{ route('notes.update', $note) }}" class="space-y-2">
                @csrf @method('PUT')
                <input class="input font-bold" name="title" value="{{ $note->title }}" required>
                <textarea class="input" name="body" rows="4">{{ $note->body }}</textarea>
                <div class="flex items-center justify-between">
                    <span class="text-xs opacity-60">{{ $note->updated_at->diffForHumans() }}</span>
                    <button class="btn btn-blue"><x-icon name="save" class="h-4 w-4" /> Save</button>
                </div>
            </form>
            <form method="POST" action="{{ route('notes.destroy', $note) }}" class="mt-2 text-right"
                  onsubmit="return confirm('Delete this note?')">
                @csrf @method('DELETE')
                <button class="inline-flex items-center gap-1 text-sm font-bold text-rose-500 hover:underline"><x-icon name="trash" class="h-4 w-4" /> Delete</button>
            </form>
        </div>
    @empty
        <div class="empty-state sm:col-span-2">
            <span class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-white/70 text-amber-500 shadow dark:bg-white/10"><x-icon name="file-text" class="h-7 w-7" /></span>
            <p class="font-extrabold">No notes yet</p>
            <p class="text-sm opacity-70">Add your first one above.</p>
        </div>
    @endforelse
</div>
@endsection

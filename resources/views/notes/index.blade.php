@extends('layouts.app')
@section('title', 'Notes · '.config('app.name'))

@section('content')
<a href="{{ route('dashboard') }}" class="text-sm font-bold opacity-70 hover:opacity-100">← Back</a>
<h1 class="mb-4 mt-1 text-3xl font-extrabold">📝 Notes</h1>

<form method="POST" action="{{ route('notes.store') }}" class="card mb-6 space-y-3">
    @csrf
    <input class="input" name="title" placeholder="Title" value="{{ old('title') }}" required>
    @error('title')<p class="error">{{ $message }}</p>@enderror
    <textarea class="input" name="body" rows="3" placeholder="Write something…">{{ old('body') }}</textarea>
    <button class="btn btn-yellow">Add note</button>
</form>

<div class="grid gap-4 sm:grid-cols-2">
    @forelse ($notes as $note)
        <div class="card">
            <form method="POST" action="{{ route('notes.update', $note) }}" class="space-y-2">
                @csrf @method('PUT')
                <input class="input font-bold" name="title" value="{{ $note->title }}" required>
                <textarea class="input" name="body" rows="4">{{ $note->body }}</textarea>
                <div class="flex items-center justify-between">
                    <span class="text-xs opacity-60">{{ $note->updated_at->diffForHumans() }}</span>
                    <button class="btn btn-blue">Save</button>
                </div>
            </form>
            <form method="POST" action="{{ route('notes.destroy', $note) }}" class="mt-2 text-right"
                  onsubmit="return confirm('Delete this note?')">
                @csrf @method('DELETE')
                <button class="text-sm font-bold text-rose-500 hover:underline">Delete</button>
            </form>
        </div>
    @empty
        <p class="card text-center opacity-70 sm:col-span-2">No notes yet — add your first one above ✨</p>
    @endforelse
</div>
@endsection

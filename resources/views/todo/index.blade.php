@extends('layouts.app')
@section('title', 'To-do · '.config('app.name'))

@section('content')
<a href="{{ route('dashboard') }}" class="text-sm font-bold opacity-70 hover:opacity-100">← Back</a>
<h1 class="mb-4 mt-1 text-3xl font-extrabold">✅ To-do</h1>

<form method="POST" action="{{ route('todo.store') }}" class="card mb-6 flex gap-2">
    @csrf
    <input class="input" name="text" placeholder="What needs doing?" required>
    <button class="btn btn-pink shrink-0">Add</button>
</form>
@error('text')<p class="error mb-4">{{ $message }}</p>@enderror

<div class="space-y-2">
    @forelse ($todos as $todo)
        <div class="card flex items-center gap-3 !py-3">
            <form method="POST" action="{{ route('todo.toggle', $todo) }}">
                @csrf @method('PUT')
                <button aria-label="Toggle done"
                        class="grid h-8 w-8 place-items-center rounded-full border-2 text-sm font-extrabold
                               {{ $todo->done ? 'border-pink-400 bg-pink-400 text-white' : 'border-sky-300' }}">
                    {{ $todo->done ? '✓' : '' }}
                </button>
            </form>
            <span class="flex-1 {{ $todo->done ? 'line-through opacity-50' : 'font-semibold' }}">{{ $todo->text }}</span>
            <form method="POST" action="{{ route('todo.destroy', $todo) }}">
                @csrf @method('DELETE')
                <button class="text-sm font-bold text-rose-500 hover:underline">Delete</button>
            </form>
        </div>
    @empty
        <p class="card text-center opacity-70">Nothing to do — enjoy the free time 🎉</p>
    @endforelse
</div>
@endsection

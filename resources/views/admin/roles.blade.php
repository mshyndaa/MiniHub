@extends('layouts.app')
@section('title', 'Roles · '.config('app.name'))

@section('content')
@include('partials.page-header', ['symbol' => 'key', 'title' => 'Roles', 'subtitle' => 'Create the access levels you can give to users', 'tint' => 'blue'])
@include('admin.partials.tabs')

@if ($errors->any() && ! $errors->hasAny(['name', 'description']))
    <div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif

{{-- Create --}}
<details class="card group mb-8" @if ($errors->hasAny(['name', 'description'])) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
        <span class="flex items-center gap-3.5">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-400/15 dark:text-sky-300"><x-icon name="key" class="h-5 w-5" /></span>
            <span>
                <span class="block font-extrabold leading-tight">New role</span>
                <span class="block text-sm opacity-50">Then give it to users or menus</span>
            </span>
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 transition group-hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300">
            <x-icon name="plus" class="h-4 w-4 transition-transform group-open:rotate-45" />
            <span class="group-open:hidden">Add</span><span class="hidden group-open:inline">Close</span>
        </span>
    </summary>
    <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-6 space-y-5 border-t border-slate-200/60 pt-6 dark:border-white/10">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="name">Name</label>
                <input class="input font-mono text-sm" id="name" name="name" value="{{ old('name') }}" placeholder="finance" required>
                <p class="mt-1.5 text-xs opacity-50">Lowercase letters, numbers, dashes or underscores.</p>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="description">Description</label>
                <input class="input" id="description" name="description" value="{{ old('description') }}" placeholder="What this role allows">
                @error('description')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end"><button class="btn btn-blue"><x-icon name="plus" class="h-4 w-4" /> Create role</button></div>
    </form>
</details>

<div class="mb-3 flex items-baseline justify-between px-1">
    <h2 class="text-sm font-bold uppercase tracking-widest opacity-50">Roles</h2>
    <span class="text-sm font-semibold opacity-50">{{ $roles->count() }}</span>
</div>

<section class="stagger space-y-3">
    @foreach ($roles as $role)
        @php $n = $counts[$role->name] ?? 0; @endphp
        <details class="card group p-5!">
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                <span class="flex min-w-0 items-center gap-3.5">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-400/15 dark:text-sky-300">
                        <x-icon :name="$role->is_system ? 'shield-check' : 'key'" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1 font-extrabold capitalize leading-tight">
                            {{ $role->name }}
                            @if ($role->is_system)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold normal-case text-slate-500 dark:bg-white/10 dark:text-slate-300">built-in</span>@endif
                        </span>
                        <span class="block truncate text-sm opacity-60">{{ $role->description ?: 'No description' }}</span>
                    </span>
                </span>
                <span class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">
                        <x-icon name="users" class="h-4 w-4" /> {{ $n }} {{ Str::plural('user', $n) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold opacity-60 transition group-hover:bg-slate-100 group-hover:opacity-100 dark:group-hover:bg-white/10">
                        <x-icon name="pencil" class="h-4 w-4" /> Edit
                    </span>
                </span>
            </summary>

            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-5 space-y-5 border-t border-slate-200/60 pt-5 dark:border-white/10">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label flex items-center gap-1.5">Name @if ($role->is_system)<x-icon name="lock" class="h-3.5 w-3.5 opacity-50" />@endif</label>
                        <input class="input font-mono text-sm {{ $role->is_system ? 'cursor-not-allowed opacity-60' : '' }}" name="name" value="{{ $role->name }}" @disabled($role->is_system) required>
                        @if ($role->is_system)<p class="mt-1.5 text-xs opacity-50">Built-in roles keep their name.</p>@endif
                    </div>
                    <div>
                        <label class="label">Description</label>
                        <input class="input" name="description" value="{{ $role->description }}">
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    @unless ($role->is_system)
                        <button type="submit" form="del-role-{{ $role->id }}" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold text-rose-600 opacity-70 transition hover:bg-rose-50 hover:opacity-100">
                            <x-icon name="trash" class="h-4 w-4" /> Delete
                        </button>
                    @else
                        <span></span>
                    @endunless
                    <button class="btn btn-blue"><x-icon name="save" class="h-4 w-4" /> Save</button>
                </div>
            </form>
            @unless ($role->is_system)
                <form id="del-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                      onsubmit="return confirm('Delete role {{ e($role->name) }}? It will be removed from all users and menus.')">
                    @csrf @method('DELETE')
                </form>
            @endunless
        </details>
    @endforeach
</section>
@endsection

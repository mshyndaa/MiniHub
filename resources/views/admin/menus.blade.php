@extends('layouts.app')
@section('title', 'Menus · '.config('app.name'))

@section('content')
@include('partials.page-header', ['symbol' => 'layout-grid', 'title' => 'Menus', 'subtitle' => 'Choose which sidebar entries exist and who sees them', 'tint' => 'blue'])
@include('admin.partials.tabs')

@if ($errors->any())
    <div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif

{{-- Create --}}
<details class="card group mb-8">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
        <span class="block font-extrabold leading-tight">New menu</span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300">
            <x-icon name="plus" class="h-4 w-4 transition-transform group-open:rotate-45" /> <span class="group-open:hidden">Add</span><span class="hidden group-open:inline">Close</span>
        </span>
    </summary>
    <form method="POST" action="{{ route('admin.menus.store') }}" class="mt-6 space-y-5 border-t border-slate-200/60 pt-6 dark:border-white/10">
        @csrf
        @include('admin.partials.menu-fields', ['menu' => null])
        <div class="flex justify-end"><button class="btn btn-blue"><x-icon name="plus" class="h-4 w-4" /> Create menu</button></div>
    </form>
</details>

<div class="mb-3 flex items-baseline justify-between px-1">
    <h2 class="text-sm font-bold uppercase tracking-widest opacity-50">Menus</h2>
    <span class="text-sm font-semibold opacity-50">{{ $menus->count() }}</span>
</div>

<section class="stagger space-y-3">
    @foreach ($menus as $menu)
        <details class="card group p-5!">
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                <span class="flex items-center gap-3.5">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-400/15 dark:text-sky-300"><x-icon :name="$menu->symbol" class="h-5 w-5" /></span>
                    <span>
                        <span class="block font-extrabold leading-tight">{{ $menu->name }}
                            @if ($menu->is_system)<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500 dark:bg-white/10">built-in</span>@endif
                        </span>
                        <span class="block text-sm opacity-50">{{ $menu->route }} · {{ implode(', ', $menu->roles) ?: 'no roles' }}</span>
                    </span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold opacity-60"><x-icon name="pencil" class="h-4 w-4" /> Edit</span>
            </summary>

            <form method="POST" action="{{ route('admin.menus.update', $menu) }}" class="mt-5 space-y-5 border-t border-slate-200/60 pt-5 dark:border-white/10">
                @csrf @method('PUT')
                @include('admin.partials.menu-fields', ['menu' => $menu])
                <div class="flex justify-end"><button class="btn btn-blue"><x-icon name="save" class="h-4 w-4" /> Save</button></div>
            </form>
            @unless ($menu->is_system)
                <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" class="mt-2 text-right"
                      onsubmit="return confirm('Delete menu {{ e($menu->name) }}?')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold text-rose-600 opacity-70 transition hover:bg-rose-50 hover:opacity-100">
                        <x-icon name="trash" class="h-4 w-4" /> Delete
                    </button>
                </form>
            @endunless
        </details>
    @endforeach
</section>
@endsection

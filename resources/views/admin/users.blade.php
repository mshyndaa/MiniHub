@extends('layouts.app')
@section('title', 'Admin · '.config('app.name'))

@section('content')
@include('partials.page-header', ['symbol' => 'shield-check', 'title' => 'Admin Panel', 'subtitle' => 'Manage people and what they can access', 'tint' => 'blue'])

@include('admin.partials.tabs')

@php
    $roleHints = \App\Models\Role::pluck('description', 'name')->map(fn ($d) => (string) $d)->all();
@endphp

@if (session('temp'))
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/80 p-5 text-amber-950 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">
        <p class="text-sm font-semibold opacity-80">Temporary password for {{ session('temp.user') }} · {{ session('temp.email') }}</p>
        <div class="my-3 flex flex-wrap items-center gap-3">
            <code class="rounded-xl bg-white/80 px-4 py-2 text-lg font-bold tracking-widest dark:bg-black/30">{{ session('temp.password') }}</code>
            <button type="button" data-copy="{{ session('temp.password') }}" class="btn btn-yellow"><x-icon name="copy" class="h-4 w-4" /><span data-label>Copy</span></button>
        </div>
        <p class="text-xs opacity-70">Shown only once. They'll be asked to choose a new password at first login.</p>
    </div>
@endif

@error('roles')<div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

{{-- Register --}}
<details class="card group mb-8" @if ($errors->hasAny(['name', 'email'])) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
        <span class="flex items-center gap-3.5">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-400/15 dark:text-sky-300"><x-icon name="users" class="h-5 w-5" /></span>
            <span>
                <span class="block font-extrabold leading-tight">New user</span>
                <span class="block text-sm opacity-50">Creates an account with a one-time password</span>
            </span>
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 transition group-hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300">
            <x-icon name="plus" class="h-4 w-4 transition-transform group-open:rotate-45" />
            <span class="group-open:hidden">Add</span><span class="hidden group-open:inline">Close</span>
        </span>
    </summary>

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-6 border-t border-slate-200/60 pt-6 dark:border-white/10">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="name">Name</label>
                <input class="input" id="name" name="name" value="{{ old('name') }}" placeholder="Jane Doe" required>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="jane@example.com" required>
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <span class="label">Access</span>
            @include('partials.role-select', ['roles' => $roles, 'selected' => old('roles', []), 'hints' => $roleHints])
        </div>
        <div class="flex justify-end">
            <button class="btn btn-blue"><x-icon name="plus" class="h-4 w-4" /> Create user</button>
        </div>
    </form>
</details>

{{-- Users --}}
<div class="mb-3 flex items-baseline justify-between px-1">
    <h2 class="text-sm font-bold uppercase tracking-widest opacity-50">Users</h2>
    <span class="text-sm font-semibold opacity-50">{{ $users->count() }}</span>
</div>

<section class="stagger space-y-3">
    @foreach ($users as $u)
        <div class="card p-5! {{ $u->is_active ? '' : 'opacity-60' }}">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3.5">
                    @include('partials.avatar', ['user' => $u, 'class' => 'h-11 w-11 shrink-0 rounded-full'])
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 font-extrabold leading-tight">
                            {{ $u->name }}
                            @if ($u->is(auth()->user()))<span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-600 dark:bg-sky-400/15 dark:text-sky-300">you</span>@endif
                            @if (! $u->is_active)<span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-600">disabled</span>@endif
                            @if ($u->must_change_password)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">pending password</span>@endif
                        </p>
                        <p class="truncate text-sm opacity-60">{{ $u->email }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <form method="POST" action="{{ route('admin.users.reset', $u) }}"
                          onsubmit="return confirm('Reset password for {{ e($u->name) }}? They will be logged out.')">
                        @csrf
                        <button class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold opacity-60 transition hover:bg-amber-100 hover:text-amber-700 hover:opacity-100 dark:hover:bg-amber-400/15 dark:hover:text-amber-300">
                            <x-icon name="rotate" class="h-4 w-4" /> Reset password
                        </button>
                    </form>
                    @unless ($u->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.active', $u) }}">
                            @csrf @method('PUT')
                            <button class="rounded-full px-3 py-1.5 text-sm font-semibold opacity-60 transition hover:bg-slate-100 hover:opacity-100 dark:hover:bg-white/10">
                                {{ $u->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    @endunless
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.roles', $u) }}" data-roles-form
                  class="mt-4 border-t border-slate-200/60 pt-4 dark:border-white/10">
                @csrf @method('PUT')

                {{-- Read-only view --}}
                <div data-view class="flex flex-wrap items-center gap-2">
                    @forelse ($u->roleNames() as $r)
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-sm font-bold capitalize text-sky-700 dark:bg-sky-400/15 dark:text-sky-200">{{ $r }}</span>
                    @empty
                        <span class="text-sm opacity-50">No access</span>
                    @endforelse
                    <button type="button" data-edit class="ml-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold opacity-60 transition hover:bg-slate-100 hover:opacity-100 dark:hover:bg-white/10">Edit roles</button>
                </div>

                {{-- Edit mode --}}
                <div data-editor class="hidden flex-wrap items-center gap-3">
                    <div class="min-w-56 flex-1">
                        @include('partials.role-select', ['roles' => $roles, 'selected' => $u->roleNames(), 'hints' => $roleHints])
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" data-cancel class="rounded-full px-3 py-1.5 text-sm font-semibold opacity-60 transition hover:bg-slate-100 hover:opacity-100 dark:hover:bg-white/10">Cancel</button>
                        <button class="btn btn-blue"><x-icon name="save" class="h-4 w-4" /> Save</button>
                    </div>
                </div>
            </form>
        </div>
    @endforeach
</section>

<script>
    // Roles are read-only until "Edit roles" is clicked; Cancel restores the stored state.
    document.querySelectorAll('[data-roles-form]').forEach(form => {
        const view = form.querySelector('[data-view]');
        const editor = form.querySelector('[data-editor]');
        const ms = form.querySelector('[data-ms]');
        const setEditing = on => {
            view.classList.toggle('hidden', on);
            editor.classList.toggle('hidden', !on);
            editor.classList.toggle('flex', on);
        };
        form.querySelector('[data-edit]').addEventListener('click', () => setEditing(true));
        form.querySelector('[data-cancel]').addEventListener('click', () => {
            ms.msReset();
            setEditing(false);
        });
    });

    // Multi-select dropdown (tags + searchable-style menu) on top of a hidden <select multiple>.
    const closeAllMenus = except => document.querySelectorAll('[data-ms]').forEach(el => el !== except && el.msClose());

    document.querySelectorAll('[data-ms]').forEach(root => {
        const select = root.querySelector('select');
        const trigger = root.querySelector('[data-ms-trigger]');
        const tags = root.querySelector('[data-ms-tags]');
        const empty = root.querySelector('[data-ms-empty]');
        const menu = root.querySelector('[data-ms-menu]');
        const chevron = root.querySelector('[data-ms-chevron]');
        const options = [...select.options];
        const check = '<svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.5l2.5 2.5 4.5-5.5"/></svg>';

        const toggle = opt => { opt.selected = !opt.selected; render(); };

        function render() {
            tags.innerHTML = '';
            options.filter(o => o.selected).forEach(o => {
                const tag = document.createElement('span');
                tag.className = 'inline-flex items-center gap-1 rounded-full bg-sky-100 py-1 pl-3 pr-1.5 text-sm font-bold capitalize text-sky-700 dark:bg-sky-400/15 dark:text-sky-200';
                tag.textContent = o.value;
                const x = document.createElement('button');
                x.type = 'button';
                x.setAttribute('aria-label', 'Remove ' + o.value);
                x.className = 'grid h-5 w-5 place-items-center rounded-full text-sky-500 hover:bg-sky-200 dark:hover:bg-sky-400/30';
                x.innerHTML = '<svg class="h-3 w-3" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l6 6M9 3l-6 6"/></svg>';
                x.addEventListener('click', e => { e.stopPropagation(); toggle(o); });
                tag.append(x);
                tags.append(tag);
            });
            empty.hidden = options.some(o => o.selected);

            menu.innerHTML = '';
            options.forEach(o => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', o.selected);
                li.className = 'flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-100 dark:hover:bg-white/10';
                li.innerHTML = `<span class="grid h-5 w-5 shrink-0 place-items-center rounded-md border-2 ${o.selected ? 'border-sky-500 bg-sky-500 text-white' : 'border-slate-300 text-transparent dark:border-white/20'}">${check}</span>
                    <span class="min-w-0"><span class="block text-sm font-bold capitalize"></span><span class="block text-xs opacity-60"></span></span>`;
                li.querySelector('.font-bold').textContent = o.value;
                li.querySelector('.opacity-60').textContent = o.dataset.hint || '';
                li.addEventListener('click', e => { e.stopPropagation(); toggle(o); });
                menu.append(li);
            });
        }

        // .card clips its overflow, which would cut the dropdown off; let it spill out while open.
        const card = root.closest('.card');
        const open = () => { closeAllMenus(root); menu.hidden = false; trigger.setAttribute('aria-expanded', 'true'); chevron.style.transform = 'rotate(180deg)'; if (card) { card.style.overflow = 'visible'; card.style.zIndex = '20'; } };
        root.msClose = () => { menu.hidden = true; trigger.setAttribute('aria-expanded', 'false'); chevron.style.transform = ''; if (card) { card.style.overflow = ''; card.style.zIndex = ''; } };
        root.msReset = () => { options.forEach(o => o.selected = o.defaultSelected); render(); root.msClose(); };

        trigger.addEventListener('click', () => menu.hidden ? open() : root.msClose());
        trigger.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') { e.preventDefault(); open(); }
            if (e.key === 'Escape') root.msClose();
            if (e.key === 'Backspace') { const last = options.filter(o => o.selected).pop(); if (last) toggle(last); }
        });
        render();
    });

    document.addEventListener('click', e => closeAllMenus(e.target.closest('[data-ms]')));
    document.addEventListener('keydown', e => e.key === 'Escape' && closeAllMenus());
</script>
@endsection

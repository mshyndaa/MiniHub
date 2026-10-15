@extends('layouts.app')
@section('title', 'Admin · '.config('app.name'))

@section('content')
@include('partials.page-header', ['symbol' => 'shield-check', 'title' => 'Admin Panel', 'subtitle' => 'Register users, reset passwords, assign roles', 'tint' => 'blue'])

@if (session('temp'))
    <div class="mb-4 rounded-3xl bg-gradient-to-r from-yellow-200 to-amber-200 p-5 text-amber-950 shadow dark:from-yellow-500/30 dark:to-amber-500/30 dark:text-yellow-100">
        <p class="font-extrabold">Temporary password for {{ session('temp.user') }} ({{ session('temp.email') }})</p>
        <p class="my-2 flex flex-wrap items-center gap-3">
            <code class="rounded-xl bg-white/70 px-3 py-1.5 text-lg font-bold tracking-wider dark:bg-black/30">{{ session('temp.password') }}</code>
            <button type="button" data-copy="{{ session('temp.password') }}" class="btn btn-yellow"><x-icon name="copy" class="h-4 w-4" /><span data-label>Copy</span></button>
        </p>
        <p class="text-sm">Shown only once. Give it to the user — they must choose a new password at first login.</p>
    </div>
@endif

@error('roles')<div class="mb-4 rounded-2xl bg-rose-100 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

<section class="card mb-6">
    <h2 class="mb-3 flex items-center gap-2 text-lg font-extrabold"><x-icon name="users" class="h-5 w-5 text-sky-500" /> Register a user</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="name">Name</label>
                <input class="input" id="name" name="name" value="{{ old('name') }}" required>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required>
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <span class="label">Roles</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($roles as $role)
                    <label class="pill">
                        <input type="checkbox" name="roles[]" value="{{ $role }}" class="accent-pink-500" @checked(in_array($role, old('roles', [])))> {{ $role }}
                    </label>
                @endforeach
            </div>
        </div>
        <button class="btn btn-pink"><x-icon name="plus" class="h-4 w-4" /> Register &amp; generate password</button>
    </form>
</section>

<section class="stagger space-y-3">
    <h2 class="text-lg font-extrabold">Users ({{ $users->count() }})</h2>
    @foreach ($users as $u)
        <div class="card {{ $u->is_active ? '' : 'opacity-60' }}">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                  @include('partials.avatar', ['user' => $u, 'class' => 'h-11 w-11 rounded-xl'])
                  <div>
                    <p class="font-extrabold">{{ $u->name }}
                        @if (! $u->is_active)<span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-600">disabled</span>@endif
                        @if ($u->must_change_password)<span class="ml-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-amber-700">must change password</span>@endif
                    </p>
                    <p class="text-sm opacity-70">{{ $u->email }}</p>
                  </div>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.users.reset', $u) }}"
                          onsubmit="return confirm('Reset password for {{ e($u->name) }}? They will be logged out.')">
                        @csrf
                        <button class="btn btn-yellow"><x-icon name="rotate" class="h-4 w-4" /> Reset password</button>
                    </form>
                    @unless ($u->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.active', $u) }}">
                            @csrf @method('PUT')
                            <button class="btn btn-soft">{{ $u->is_active ? 'Disable' : 'Enable' }}</button>
                        </form>
                    @endunless
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.roles', $u) }}" class="flex flex-wrap items-center gap-2">
                @csrf @method('PUT')
                @foreach ($roles as $role)
                    <label class="pill">
                        <input type="checkbox" name="roles[]" value="{{ $role }}" class="accent-pink-500" @checked($u->hasRole($role))> {{ $role }}
                    </label>
                @endforeach
                <button class="btn btn-blue"><x-icon name="save" class="h-4 w-4" /> Save roles</button>
            </form>
        </div>
    @endforeach
</section>
@endsection

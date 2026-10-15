@extends('layouts.app')
@section('title', 'Log in · '.config('app.name'))

@section('content')
<div class="mx-auto mt-8 max-w-sm">
    <div class="card">
        <h1 class="mb-1 text-2xl font-extrabold">Welcome back 👋</h1>
        <p class="mb-5 text-sm opacity-70">Log in to open your mini programs.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm font-semibold">
                <input type="checkbox" name="remember" class="h-4 w-4 accent-pink-500"> Remember me
            </label>
            <button class="btn btn-blue w-full">Log in</button>
        </form>

        <p class="mt-4 text-center text-xs opacity-60">No account? Ask your admin to register you.</p>
    </div>
</div>
@endsection

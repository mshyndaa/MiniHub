@extends('layouts.app')
@section('title', 'Change password · '.config('app.name'))

@section('content')
<div class="mx-auto mt-8 max-w-sm">
    <div class="card">
        <h1 class="mb-1 text-2xl font-extrabold">Change password 🔑</h1>
        @if (auth()->user()->must_change_password)
            <p class="mb-5 text-sm opacity-70">Your password was set by an admin. Please choose your own before continuing.</p>
        @else
            <p class="mb-5 text-sm opacity-70">Pick a new password.</p>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="label" for="current_password">Current password</label>
                <input class="input" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">New password</label>
                <input class="input" id="password" name="password" type="password" required autocomplete="new-password">
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm new password</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>
            <button class="btn btn-pink w-full">Save password</button>
        </form>
    </div>
</div>
@endsection

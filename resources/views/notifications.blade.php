@extends('layouts.app')
@section('title', 'Notifications · '.config('app.name'))

@section('content')
@include('partials.page-header', ['symbol' => 'bell', 'title' => 'Notifications', 'subtitle' => 'Announcements from the admin', 'tint' => 'yellow'])

@if ($errors->any())
    <div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif

@if (auth()->user()->hasRole('admin'))
    <details class="card group mb-8" @if ($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
            <span class="block font-extrabold leading-tight">Post a notification</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300">
                <x-icon name="plus" class="h-4 w-4 transition-transform group-open:rotate-45" /> <span class="group-open:hidden">New</span><span class="hidden group-open:inline">Close</span>
            </span>
        </summary>
        <form method="POST" action="{{ route('notifications.store') }}" class="mt-6 space-y-4 border-t border-slate-200/60 pt-6 dark:border-white/10">
            @csrf
            <div>
                <label class="label">Title</label>
                <input class="input" name="title" value="{{ old('title') }}" maxlength="120" required>
            </div>
            <div>
                <label class="label">Message</label>
                <textarea class="input" name="body" rows="4" maxlength="2000" required>{{ old('body') }}</textarea>
            </div>
            <div class="flex justify-end"><button class="btn btn-blue"><x-icon name="bell" class="h-4 w-4" /> Send to all users</button></div>
        </form>
    </details>
@endif

<section class="stagger space-y-3">
    @forelse ($items as $item)
        <article class="card p-5!">
            <div class="flex items-start gap-3.5">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-400/15 dark:text-amber-300"><x-icon name="bell" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <h2 class="font-extrabold leading-tight">
                        {{ $item->title }}
                        @if (in_array($item->id, $newIds))<span class="ml-1 rounded-full bg-rose-500 px-2 py-0.5 text-[11px] font-bold text-white">New</span>@endif
                    </h2>
                    <p class="mt-1 whitespace-pre-line break-words text-sm opacity-80">{{ $item->body }}</p>
                    <p class="mt-2 text-xs font-semibold opacity-50">{{ $item->author?->name ?? 'Admin' }} · {{ $item->created_at->diffForHumans() }}</p>
                </div>
                @if (auth()->user()->hasRole('admin'))
                    <form method="POST" action="{{ route('notifications.destroy', $item->id) }}" onsubmit="return confirm('Delete this notification for everyone?')">
                        @csrf @method('DELETE')
                        <button class="rounded-full p-2 text-rose-600 opacity-60 transition hover:bg-rose-50 hover:opacity-100" aria-label="Delete"><x-icon name="trash" class="h-4 w-4" /></button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <div class="card py-10 text-center"><p class="font-extrabold">No notifications yet</p></div>
    @endforelse

    {{ $items->links() }}
</section>
@endsection

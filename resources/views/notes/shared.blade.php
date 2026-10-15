@extends('layouts.app')
@section('title', $note->title.' · Shared note')

@section('content')
<article class="card nc-{{ $note->color }} mx-auto mt-6 max-w-3xl space-y-4">
    <header>
        <p class="text-xs font-extrabold uppercase tracking-widest opacity-60">Shared note · read only</p>
        <h1 class="mt-1 break-words text-3xl font-black text-slate-900 dark:text-white">{{ $note->title }}</h1>
        <p class="mt-1 text-sm font-semibold opacity-60">By {{ $note->user->name }} · updated {{ $note->updated_at->format('j F Y') }}</p>
        @if ($note->tags->isNotEmpty())
            <div class="mt-2 flex flex-wrap gap-1.5">@foreach ($note->tags as $tag)<span class="chip">#{{ $tag->name }}</span>@endforeach</div>
        @endif
    </header>
    <div class="note-body">{!! $note->body !!}</div>
</article>
@endsection

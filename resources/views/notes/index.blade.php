@extends('layouts.app')
@section('title', 'Notes · '.config('app.name'))
@section('wide', '1')

@php
    use App\Models\Note;

    $f = $filters;
    $isTrash = $f['view'] === 'trash';
    $isArchive = $f['view'] === 'archive';
    $total = $notes->total();
    $keep = array_filter(['sort' => $f['sort'] !== 'updated' ? $f['sort'] : null]);
    $link = fn (array $over = []) => route('notes.index', array_filter(array_merge($keep, $over), fn ($v) => $v !== null && $v !== ''));
    $filtered = $f['term'] !== '' || $f['folder'] || $f['tag'] || $f['type'];
    $heading = match ($f['view']) {
        'favorites' => 'Favorites',
        'archive' => 'Archive',
        'trash' => 'Trash',
        default => 'All notes',
    };
    $typeIcon = ['general' => 'file-text', 'study' => 'pencil', 'work' => 'list-checks', 'meeting' => 'users'];
    $pinned = $notes->getCollection()->where('pinned', true);
    $rest = $notes->getCollection()->where('pinned', false);
    $groups = ($pinned->isNotEmpty() && ! $isTrash) ? ['Pinned' => $pinned, 'Notes' => $rest] : ['' => $notes->getCollection()];
@endphp

@section('content')
@include('partials.page-header', ['symbol' => 'file-text', 'title' => 'Notes', 'subtitle' => $counts['all'].' '.\Illuminate\Support\Str::plural('note', $counts['all']).' · study, work and meeting notes', 'tint' => 'yellow'])

<div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
    {{-- Filters --}}
    <aside class="space-y-5 lg:sticky lg:top-6 lg:self-start">
        <div class="card space-y-1 !p-3">
            <a href="{{ $link() }}" class="nf-link {{ $f['view'] === 'all' && ! $f['folder'] && ! $f['tag'] && ! $f['type'] ? 'nf-link-active' : '' }}"><x-icon name="file-text" class="h-4 w-4" /> All notes <span class="nf-count">{{ $counts['all'] }}</span></a>
            <a href="{{ $link(['view' => 'favorites']) }}" class="nf-link {{ $f['view'] === 'favorites' ? 'nf-link-active' : '' }}"><x-icon name="star" class="h-4 w-4" /> Favorites <span class="nf-count">{{ $counts['favorites'] }}</span></a>
            <a href="{{ $link(['view' => 'archive']) }}" class="nf-link {{ $isArchive ? 'nf-link-active' : '' }}"><x-icon name="archive" class="h-4 w-4" /> Archive <span class="nf-count">{{ $counts['archive'] }}</span></a>
            <a href="{{ $link(['view' => 'trash']) }}" class="nf-link {{ $isTrash ? 'nf-link-active' : '' }}"><x-icon name="trash" class="h-4 w-4" /> Trash <span class="nf-count">{{ $counts['trash'] }}</span></a>
        </div>

        <div class="card !p-3">
            <p class="nav-heading mb-1 px-3">Types</p>
            @foreach (Note::TYPES as $key => $label)
                <a href="{{ $link(['type' => $key]) }}" class="nf-link {{ $f['type'] === $key ? 'nf-link-active' : '' }}"><x-icon :name="$typeIcon[$key]" class="h-4 w-4" /> {{ $label === 'Blank' ? 'General' : $label }}</a>
            @endforeach
        </div>

        <div class="card !p-3">
            <p class="nav-heading mb-1 px-3">Folders</p>
            <a href="{{ $link(['folder' => 'none']) }}" class="nf-link {{ $f['folder'] === 'none' ? 'nf-link-active' : '' }}"><x-icon name="inbox" class="h-4 w-4" /> Unfiled <span class="nf-count">{{ $counts['unfiled'] }}</span></a>
            @foreach ($folders as $folder)
                <div class="group flex items-center">
                    <a href="{{ $link(['folder' => $folder->id]) }}" class="nf-link min-w-0 flex-1 {{ (string) $f['folder'] === (string) $folder->id ? 'nf-link-active' : '' }}">
                        <x-icon name="folder" class="h-4 w-4 shrink-0" /> <span class="truncate">{{ $folder->name }}</span> <span class="nf-count">{{ $folder->notes_count }}</span>
                    </a>
                    <button type="button" class="nf-mini" title="Rename folder" data-folder-rename="{{ route('notes.folders.update', $folder->id) }}" data-name="{{ $folder->name }}"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                    <form method="POST" action="{{ route('notes.folders.destroy', $folder->id) }}" onsubmit="return confirm('Delete the folder “{{ e($folder->name) }}”? Its notes will be kept.')">
                        @csrf @method('DELETE')
                        <button class="nf-mini hover:!text-rose-500" title="Delete folder"><x-icon name="x" class="h-3.5 w-3.5" /></button>
                    </form>
                </div>
            @endforeach
            <form method="POST" action="{{ route('notes.folders.store') }}" class="mt-2 flex gap-1.5 px-1">
                @csrf
                <input class="input !rounded-xl !px-3 !py-1.5 text-sm" name="name" maxlength="60" placeholder="New folder" required>
                <button class="btn btn-soft !px-2.5 !py-1.5" title="Add folder"><x-icon name="plus" class="h-4 w-4" /></button>
            </form>
            @error('name')<p class="error px-1">{{ $message }}</p>@enderror
        </div>

        @if ($tags->isNotEmpty())
            <div class="card !p-3">
                <p class="nav-heading mb-2 px-3">Tags</p>
                <div class="flex flex-wrap gap-1.5 px-1">
                    @foreach ($tags as $tag)
                        <a href="{{ $link(['tag' => $tag->id]) }}" class="chip {{ (string) $f['tag'] === (string) $tag->id ? 'chip-active' : '' }}">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </aside>

    {{-- Notes --}}
    <section class="min-w-0">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('notes.index') }}" class="relative min-w-[12rem] flex-1">
                @foreach (['view' => $f['view'] !== 'all' ? $f['view'] : null, 'folder' => $f['folder'], 'tag' => $f['tag'], 'type' => $f['type'], 'sort' => $keep['sort'] ?? null] as $k => $v)
                    @if ($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 opacity-50" />
                <input class="input w-full !pl-10" type="search" name="q" value="{{ $f['term'] }}" placeholder="Search titles and content…">
            </form>

            <form method="GET" action="{{ route('notes.index') }}">
                @foreach (['view' => $f['view'] !== 'all' ? $f['view'] : null, 'folder' => $f['folder'], 'tag' => $f['tag'], 'type' => $f['type'], 'q' => $f['term']] as $k => $v)
                    @if ($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <select name="sort" class="input !py-2 font-bold" onchange="this.form.submit()" aria-label="Sort notes">
                    <option value="updated" @selected($f['sort'] === 'updated')>Last edited</option>
                    <option value="created" @selected($f['sort'] === 'created')>Date created</option>
                    <option value="title" @selected($f['sort'] === 'title')>Title (A–Z)</option>
                </select>
            </form>

            <div class="flex rounded-2xl bg-white/70 p-1 ring-1 ring-sky-100 dark:bg-white/5 dark:ring-white/10">
                <button type="button" class="layout-btn" data-layout="grid" title="Grid view" aria-label="Grid view"><x-icon name="layout-grid" class="h-4 w-4" /></button>
                <button type="button" class="layout-btn" data-layout="list" title="List view" aria-label="List view"><x-icon name="list" class="h-4 w-4" /></button>
            </div>
        </div>

        @unless ($isTrash)
            <div class="card mb-6 flex flex-wrap items-center gap-2 !p-3">
                <span class="mr-1 text-sm font-extrabold opacity-70">New note</span>
                @foreach (['general' => 'Blank', 'study' => 'Study', 'work' => 'Work', 'meeting' => 'Meeting'] as $key => $label)
                    <form method="POST" action="{{ route('notes.store') }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $key }}">
                        @if (ctype_digit((string) $f['folder']))<input type="hidden" name="folder_id" value="{{ $f['folder'] }}">@endif
                        <button class="btn {{ $key === 'general' ? 'btn-yellow' : 'btn-soft' }} !py-2 text-sm"><x-icon :name="$key === 'general' ? 'plus' : $typeIcon[$key]" class="h-4 w-4" /> {{ $label }}</button>
                    </form>
                @endforeach
            </div>
        @endunless

        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-black">{{ $heading }}
                <span class="text-sm font-bold opacity-50">· {{ $total }} {{ \Illuminate\Support\Str::plural('note', $total) }}{{ $f['term'] !== '' ? ' matching “'.$f['term'].'”' : '' }}</span>
            </h2>
            @if ($filtered)
                <a href="{{ $link(['view' => $f['view'] !== 'all' ? $f['view'] : null]) }}" class="text-sm font-bold text-sky-600 hover:underline dark:text-sky-300">Clear filters</a>
            @endif
            @if ($isTrash && $counts['trash'])
                <form method="POST" action="{{ route('notes.trash.empty') }}" onsubmit="return confirm('Permanently delete everything in the trash?')">
                    @csrf @method('DELETE')
                    <button class="text-sm font-bold text-rose-500 hover:underline">Empty trash</button>
                </form>
            @endif
        </div>
        @if ($isTrash)
            <p class="mb-4 text-sm opacity-60">Notes in the trash are deleted automatically after {{ $trashDays }} days.</p>
        @endif

        @forelse ($groups as $label => $group)
            @if ($group->isNotEmpty())
                @if ($label !== '')<p class="nav-heading mb-2 mt-4 flex items-center gap-1.5">@if ($label === 'Pinned')<x-icon name="pin" class="h-3.5 w-3.5" />@endif {{ $label }}</p>@endif
                <div class="note-grid stagger mb-4">
                    @foreach ($group as $note)
                        @php $due = $note->reminder_at; @endphp
                        <article class="card note-card nc-{{ $note->color }} flex flex-col">
                            @if ($isTrash)
                                <div class="block flex-1">
                            @else
                                <a href="{{ route('notes.edit', $note->id) }}" class="block flex-1">
                            @endif
                                <div class="flex items-start gap-2">
                                    <h3 class="min-w-0 flex-1 break-words text-lg font-extrabold leading-snug text-slate-900 dark:text-white">{{ $note->title }}</h3>
                                    @if ($note->pinned)<x-icon name="pin" class="mt-1 h-4 w-4 shrink-0 fill-current text-amber-500" />@endif
                                    @if ($note->favorite)<x-icon name="star" class="mt-1 h-4 w-4 shrink-0 fill-current text-amber-400" />@endif
                                </div>
                                <p class="note-preview mt-1 text-sm opacity-75">{{ $note->preview() ?: 'Empty note' }}</p>
                            @if ($isTrash)
                                </div>
                            @else
                                </a>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs font-bold">
                                <span class="chip"><x-icon :name="$typeIcon[$note->type] ?? 'file-text'" class="h-3 w-3" /> {{ $note->type === 'general' ? 'General' : Note::TYPES[$note->type] }}</span>
                                @if ($note->folder)<span class="chip"><x-icon name="folder" class="h-3 w-3" /> {{ $note->folder->name }}</span>@endif
                                @foreach ($note->tags as $tag)<span class="chip">#{{ $tag->name }}</span>@endforeach
                                @if ($due && ! $isTrash)
                                    <span class="chip {{ $due->isPast() ? '!bg-rose-100 !text-rose-600 dark:!bg-rose-500/20 dark:!text-rose-300' : '!bg-sky-100 !text-sky-700 dark:!bg-sky-500/20 dark:!text-sky-200' }}"><x-icon name="bell" class="h-3 w-3" /> {{ $due->isPast() ? 'Overdue' : 'Due' }} · <time data-local="short" datetime="{{ $due->toIso8601String() }}">{{ $due->format('j M, H:i') }}</time></span>
                                @endif
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2 border-t border-black/5 pt-2 dark:border-white/10">
                                <span class="text-xs opacity-60">{{ $isTrash ? 'Deleted '.$note->deleted_at->diffForHumans() : $note->updated_at->diffForHumans() }}</span>
                                <div class="flex items-center gap-0.5">
                                    @if ($isTrash)
                                        <form method="POST" action="{{ route('notes.restore', $note->id) }}">@csrf <button class="note-act" title="Restore"><x-icon name="undo-2" class="h-4 w-4" /> <span class="text-xs font-bold">Restore</span></button></form>
                                        <form method="POST" action="{{ route('notes.force', $note->id) }}" onsubmit="return confirm('Delete this note for good? This cannot be undone.')">@csrf @method('DELETE') <button class="note-act hover:!text-rose-500" title="Delete forever"><x-icon name="trash" class="h-4 w-4" /></button></form>
                                    @else
                                        <form method="POST" action="{{ route('notes.toggle', [$note->id, 'pin']) }}">@csrf <button class="note-act {{ $note->pinned ? '!text-amber-500' : '' }}" title="{{ $note->pinned ? 'Unpin' : 'Pin' }}"><x-icon name="pin" class="h-4 w-4 {{ $note->pinned ? 'fill-current' : '' }}" /></button></form>
                                        <form method="POST" action="{{ route('notes.toggle', [$note->id, 'favorite']) }}">@csrf <button class="note-act {{ $note->favorite ? '!text-amber-400' : '' }}" title="{{ $note->favorite ? 'Remove favorite' : 'Favorite' }}"><x-icon name="star" class="h-4 w-4 {{ $note->favorite ? 'fill-current' : '' }}" /></button></form>
                                        <form method="POST" action="{{ route('notes.toggle', [$note->id, 'archive']) }}">@csrf <button class="note-act" title="{{ $note->archived_at ? 'Unarchive' : 'Archive' }}"><x-icon :name="$note->archived_at ? 'undo-2' : 'archive'" class="h-4 w-4" /></button></form>
                                        <form method="POST" action="{{ route('notes.duplicate', $note->id) }}">@csrf <button class="note-act" title="Duplicate"><x-icon name="copy" class="h-4 w-4" /></button></form>
                                        <form method="POST" action="{{ route('notes.destroy', $note->id) }}" onsubmit="return confirm('Move this note to the trash?')">@csrf @method('DELETE') <button class="note-act hover:!text-rose-500" title="Move to trash"><x-icon name="trash" class="h-4 w-4" /></button></form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @empty
        @endforelse

        @if ($total === 0)
            <div class="empty-state">
                @include('partials.dino', ['class' => 'mx-auto mb-3 h-16 w-16 text-sky-300'])
                <p class="font-extrabold">
                    @if ($f['term'] !== '' || $filtered) Nothing matches those filters
                    @elseif ($isTrash) The trash is empty
                    @elseif ($isArchive) No archived notes
                    @elseif ($f['view'] === 'favorites') No favorites yet
                    @else No notes yet @endif
                </p>
                <p class="text-sm opacity-70">
                    @if (! $isTrash && ! $filtered && $f['view'] === 'all') Pick Blank, Study, Work or Meeting above to write your first note. @endif
                </p>
            </div>
        @endif

        {{ $notes->links() }}
    </section>
</div>

{{-- Rename folder (filled in by JS) --}}
<form id="folder-rename-form" method="POST" class="hidden">
    @csrf @method('PUT')
    <input type="hidden" name="name">
</form>
@endsection

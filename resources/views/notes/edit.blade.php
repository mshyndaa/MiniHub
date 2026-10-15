@extends('layouts.app')
@section('title', $note->title.' · '.config('app.name'))
@section('wide', '1')

@php
    use App\Models\Note;

    $reminder = $note->reminder_at;
    $tools = [
        ['bold', 'bold', 'Bold (Ctrl+B)'],
        ['italic', 'italic', 'Italic (Ctrl+I)'],
        ['underline', 'underline', 'Underline (Ctrl+U)'],
        ['strikeThrough', 'strikethrough', 'Strikethrough'],
        '|',
        ['h2', 'heading-2', 'Heading'],
        ['h3', 'heading-3', 'Sub-heading'],
        '|',
        ['ul', 'list', 'Bulleted list'],
        ['ol', 'list-ordered', 'Numbered list'],
        ['checklist', 'list-checks', 'Checklist'],
        '|',
        ['quote', 'quote', 'Quote'],
        ['code', 'code', 'Code block'],
        ['link', 'link', 'Link'],
        ['hr', 'minus', 'Divider line'],
        ['clear', 'eraser', 'Clear formatting'],
        '|',
        ['undo', 'undo', 'Undo (Ctrl+Z)'],
        ['redo', 'redo', 'Redo (Ctrl+Y)'],
    ];
@endphp

@section('content')
<div id="note-editor"
     data-update-url="{{ route('notes.update', $note->id) }}"
     data-toggle-url="{{ route('notes.toggle', [$note->id, 'FIELD']) }}"
     data-share-url="{{ route('notes.share', $note->id) }}"
     data-versions-url="{{ route('notes.versions', $note->id) }}"
     data-attach-url="{{ route('notes.attachments.store', $note->id) }}"
     data-shared="{{ $note->share_token ? route('notes.shared', $note->share_token) : '' }}">

    {{-- Top bar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('notes.index') }}" class="btn btn-soft !py-2 text-sm"><x-icon name="arrow-left" class="h-4 w-4" /> All notes</a>

        <div class="flex flex-wrap items-center gap-1.5">
            <span id="save-status" class="mr-1 text-sm font-bold opacity-60" aria-live="polite">Saved</span>

            <button type="button" class="tb-act {{ $note->pinned ? 'tb-on' : '' }}" data-toggle="pin" title="Pin to top"><x-icon name="pin" class="h-4 w-4 {{ $note->pinned ? 'fill-current' : '' }}" /></button>
            <button type="button" class="tb-act {{ $note->favorite ? 'tb-on' : '' }}" data-toggle="favorite" title="Favorite"><x-icon name="star" class="h-4 w-4 {{ $note->favorite ? 'fill-current' : '' }}" /></button>
            <button type="button" class="tb-act {{ $note->archived_at ? 'tb-on' : '' }}" data-toggle="archive" title="{{ $note->archived_at ? 'Archived' : 'Archive' }}"><x-icon name="archive" class="h-4 w-4" /></button>
            <button type="button" class="tb-act" id="btn-history" title="Version history"><x-icon name="history" class="h-4 w-4" /></button>
            <button type="button" class="tb-act {{ $note->share_token ? 'tb-on' : '' }}" id="btn-share" title="Share a read-only link"><x-icon name="link" class="h-4 w-4" /></button>

            <details class="relative" id="export-menu">
                <summary class="tb-act cursor-pointer list-none" title="Export"><x-icon name="download" class="h-4 w-4" /></summary>
                <div class="menu-pop">
                    <a href="{{ route('notes.export', [$note->id, 'pdf']) }}" target="_blank" rel="noopener" class="menu-item"><x-icon name="printer" class="h-4 w-4" /> PDF / Print</a>
                    <a href="{{ route('notes.export', [$note->id, 'md']) }}" class="menu-item"><x-icon name="download" class="h-4 w-4" /> Markdown (.md)</a>
                    <a href="{{ route('notes.export', [$note->id, 'txt']) }}" class="menu-item"><x-icon name="download" class="h-4 w-4" /> Plain text (.txt)</a>
                    <button type="button" class="menu-item" id="btn-copy"><x-icon name="copy" class="h-4 w-4" /> <span data-label>Copy to clipboard</span></button>
                </div>
            </details>

            <form method="POST" action="{{ route('notes.duplicate', $note->id) }}">@csrf <button class="tb-act" title="Duplicate"><x-icon name="copy" class="h-4 w-4" /></button></form>
            <form method="POST" action="{{ route('notes.destroy', $note->id) }}" onsubmit="return confirm('Move this note to the trash?')">@csrf @method('DELETE') <button class="tb-act hover:!text-rose-500" title="Move to trash"><x-icon name="trash" class="h-4 w-4" /></button></form>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start">
        {{-- Writing surface --}}
        <div class="card nc-{{ $note->color }} note-paper" id="note-card">
            <div class="note-paper-head">
        <input id="note-title" class="note-title" value="{{ $note->title }}" maxlength="150" placeholder="Title" aria-label="Title" autocomplete="off">
            </div>

        <div class="editor-bar" id="editor-bar" role="toolbar" aria-label="Formatting">
            @foreach ($tools as $tool)
                @if ($tool === '|')
                    <span class="editor-sep"></span>
                @else
                    <button type="button" class="editor-btn" data-cmd="{{ $tool[0] }}" title="{{ $tool[2] }}" aria-label="{{ $tool[2] }}"><x-icon :name="$tool[1]" class="h-4 w-4" /></button>
                @endif
            @endforeach
        </div>

            <div class="note-paper-body">
        <div id="note-body" class="note-body note-editable" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Note body" spellcheck="true" data-placeholder="Start writing…">{!! $note->body !!}</div>
            </div>

            <div class="note-paper-foot">
            <span><span id="word-count">{{ $note->wordCount() }}</span> words · <span id="read-time">1</span> min read</span>
            <span>Created {{ $note->created_at->format('j M Y') }}</span>
            </div>
        </div>

        {{-- Side panel --}}
        <aside class="space-y-5 lg:sticky lg:top-6">
            <div class="card note-side space-y-4">
                <h2 class="text-sm font-black uppercase tracking-wider opacity-60">Details</h2>
            <label class="meta-field">
                <span class="meta-label"><x-icon name="file-text" class="h-3.5 w-3.5" /> Type</span>
                <select class="input !py-2 text-sm font-bold" id="note-type">
                    @foreach (Note::TYPES as $key => $label)
                        <option value="{{ $key }}" @selected($note->type === $key)>{{ $key === 'general' ? 'General' : $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="meta-field">
                <span class="meta-label"><x-icon name="folder" class="h-3.5 w-3.5" /> Folder</span>
                <select class="input !py-2 text-sm font-bold" id="note-folder">
                    <option value="">No folder</option>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder->id }}" @selected($note->folder_id === $folder->id)>{{ $folder->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="meta-field">
                <span class="meta-label"><x-icon name="bell" class="h-3.5 w-3.5" /> Reminder</span>
                <input class="input !py-2 text-sm font-bold" type="datetime-local" id="note-reminder" data-utc="{{ $reminder?->toIso8601String() }}">
            </label>
            <div class="meta-field">
                <span class="meta-label"><x-icon name="palette" class="h-3.5 w-3.5" /> Color</span>
                <div class="flex items-center gap-2 py-1.5" id="note-colors" role="radiogroup" aria-label="Note color">
                    @foreach (Note::COLORS as $color)
                        <label class="swatch sw-{{ $color }}" title="{{ ucfirst($color === 'none' ? 'plain' : $color) }}">
                            <input type="radio" name="color" value="{{ $color }}" class="sr-only" @checked($note->color === $color)>
                            <x-icon name="check" class="h-3.5 w-3.5" />
                        </label>
                    @endforeach
                </div>
            </div>
        <label class="meta-field">
            <span class="meta-label"><x-icon name="tag" class="h-3.5 w-3.5" /> Tags <span class="font-semibold opacity-60">(separate with commas)</span></span>
            <input class="input !py-2 text-sm" id="note-tags" list="tag-suggestions" value="{{ $note->tags->pluck('name')->implode(', ') }}" placeholder="e.g. exam, project-x, q4" autocomplete="off">
            <datalist id="tag-suggestions">@foreach ($allTags as $t)<option value="{{ $t }}">@endforeach</datalist>
        </label>
            </div>

    <div class="card note-side">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="flex items-center gap-2 text-lg font-black"><x-icon name="paperclip" class="h-5 w-5 text-sky-500" /> Attachments</h2>
            <label class="btn btn-soft shrink-0 cursor-pointer !py-2 text-sm"><x-icon name="plus" class="h-4 w-4" /> Add file
                <input type="file" id="attach-input" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
            </label>
        </div>
        <ul id="attach-list" class="space-y-2">
            @foreach ($note->attachments as $a)
                @include('notes.partials.attachment', ['a' => $a->toArray()])
            @endforeach
        </ul>
        <p id="attach-empty" class="text-sm opacity-60 {{ $note->attachments->isEmpty() ? '' : 'hidden' }}">No files yet. Images, PDF, Office files, text, CSV and ZIP up to 10 MB each.</p>
        <p id="attach-error" class="error hidden"></p>
    </div>
        </aside>
    </div>
</div>

{{-- Version history --}}
<dialog id="history-dialog" class="note-dialog">
    <div class="flex items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-xl font-black"><x-icon name="history" class="h-5 w-5 text-sky-500" /> Version history</h2>
        <button type="button" class="tb-act" data-close aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
    </div>
    <p class="mt-1 text-sm opacity-60">Earlier checkpoints of this note. Restoring keeps your current text as a version, so nothing is lost.</p>
    <ul id="history-list" class="mt-4 max-h-[55vh] space-y-2 overflow-y-auto pr-1"></ul>
</dialog>

{{-- Share --}}
<dialog id="share-dialog" class="note-dialog">
    <div class="flex items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-xl font-black"><x-icon name="link" class="h-5 w-5 text-sky-500" /> Share this note</h2>
        <button type="button" class="tb-act" data-close aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
    </div>
    <div id="share-off" class="mt-3 space-y-3">
        <p class="text-sm opacity-70">Create a link that lets anyone who has it read this note. They can't edit it or see your attachments. You can turn the link off at any time.</p>
        <button type="button" class="btn btn-blue" id="share-on"><x-icon name="link" class="h-4 w-4" /> Create share link</button>
    </div>
    <div id="share-live" class="mt-3 hidden space-y-3">
        <p class="text-sm opacity-70">Anyone with this link can read the note.</p>
        <input class="input w-full text-sm" id="share-link" readonly>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-blue" id="share-copy"><x-icon name="copy" class="h-4 w-4" /> <span data-label>Copy link</span></button>
            <button type="button" class="btn btn-soft text-rose-500" id="share-off-btn">Stop sharing</button>
        </div>
    </div>
</dialog>
@endsection

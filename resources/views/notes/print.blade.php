<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $note->title }}</title>
    <style>
        body { font: 16px/1.6 'Nunito', 'Segoe UI', system-ui, sans-serif; color: #1e293b; max-width: 46rem; margin: 2rem auto; padding: 0 1.25rem; }
        h1 { font-size: 2rem; line-height: 1.2; margin: 0 0 .25rem; }
        .meta { color: #64748b; font-size: .85rem; margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: .75rem; }
        .body > * + * { margin-top: .75em; }
        h2 { font-size: 1.4rem; margin: 1.2em 0 0; } h3 { font-size: 1.15rem; margin: 1em 0 0; }
        ul { list-style: disc; padding-left: 1.5rem; } ol { list-style: decimal; padding-left: 1.5rem; }
        ul[data-checklist] { list-style: none; padding-left: 0; }
        ul[data-checklist] > li::before { content: '\2610'; margin-right: .5rem; }
        ul[data-checklist] > li[data-checked="true"]::before { content: '\2611'; }
        ul[data-checklist] > li[data-checked="true"] { color: #64748b; text-decoration: line-through; }
        blockquote { border-left: 4px solid #7cc6ff; margin-left: 0; padding: .1rem 1rem; color: #475569; font-style: italic; }
        pre { background: #f1f5f9; padding: .85rem 1rem; border-radius: .6rem; white-space: pre-wrap; font: .9em ui-monospace, Consolas, monospace; }
        code { background: #f1f5f9; padding: .1em .35em; border-radius: .3em; font-family: ui-monospace, Consolas, monospace; }
        a { color: #1d6fd1; } hr { border: 0; border-top: 2px dashed #cbd5e1; }
        .bar { margin-bottom: 1rem; } .bar button { font: inherit; padding: .4rem .9rem; border-radius: .6rem; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; }
        @media print { .bar { display: none; } body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="bar"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <h1>{{ $note->title }}</h1>
    <div class="meta">
        {{ $note->user->name }} · {{ $note->type === 'general' ? 'General' : \App\Models\Note::TYPES[$note->type] }} note · updated {{ $note->updated_at->format('j F Y, H:i') }}
        @if ($note->tags->isNotEmpty()) · {{ $note->tags->map(fn ($t) => '#'.$t->name)->implode(' ') }} @endif
    </div>
    <div class="body">{!! $note->body !!}</div>
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>

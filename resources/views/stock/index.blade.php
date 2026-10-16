@extends('layouts.app')
@section('title', 'Stock · '.config('app.name'))
@section('wide', '1')

@section('content')
@include('partials.page-header', ['symbol' => 'inbox', 'title' => 'Stock', 'subtitle' => 'Tap an item each time a customer scans the barcode', 'tint' => 'pink'])

<style>
    .stock-grid { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); }
    .stock-emoji { font-size: 5rem; line-height: 1; }
    .stock-count { font-size: clamp(5rem, 12vw, 10rem); font-weight: 900; line-height: 1; margin: .75rem 0 1rem; font-variant-numeric: tabular-nums; }
    .stock-row { display: flex; gap: .5rem; }
    .stock-row .input { min-width: 0; }
</style>

@if ($errors->any())
    <div class="mb-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif

{{-- Add an item --}}
<details class="card group mb-8" @if ($errors->any() || count($items) === 0) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
        <span class="block font-extrabold leading-tight">New item</span>
        <span class="flex items-center gap-2">
            <a href="{{ route('stock.tv') }}" target="_blank" class="btn btn-soft tv-link" onclick="event.stopPropagation()"><x-icon name="eye" class="h-4 w-4" /> Open TV display</a>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300">
                <x-icon name="plus" class="h-4 w-4 transition-transform group-open:rotate-45" /> <span class="group-open:hidden">Add</span><span class="hidden group-open:inline">Close</span>
            </span>
        </span>
    </summary>
    <form method="POST" action="{{ route('stock.store') }}" class="mt-6 border-t border-slate-200/60 pt-6 dark:border-white/10">
        @csrf
        <div class="stock-row" style="flex-wrap:wrap">
            <input name="icon" value="{{ old('icon') }}" maxlength="16" placeholder="😀" class="input" style="width:5rem;text-align:center" aria-label="Emoji">
            <input name="name" value="{{ old('name') }}" maxlength="50" placeholder="Item name (e.g. Tea)" class="input" style="flex:1 1 12rem" aria-label="Name">
            <input type="number" name="stock" min="0" required value="{{ old('stock', 100) }}" class="input" style="width:8rem" aria-label="Starting stock">
            <button class="btn btn-blue"><x-icon name="plus" class="h-4 w-4" /> Add item</button>
        </div>
    </form>
</details>

<section class="stock-grid" id="stock-grid">
    @foreach ($items as $item)
        <div class="card text-center" data-item="{{ $item['id'] }}">
            <div class="stock-emoji">{{ $item['icon'] ?: '📦' }}</div>
            <h2 class="mt-2 text-xl font-extrabold">{{ $item['name'] !== '' ? $item['name'] : 'Item #'.$item['id'] }}</h2>
            <p class="stock-count" data-count>{{ $item['stock'] }}</p>
            <button type="button" data-delta="-1" class="btn btn-blue w-full text-xl" style="padding-block:1.1rem">Scanned · −1</button>
            <button type="button" data-delta="1" class="mt-2 text-sm font-semibold opacity-60 hover:opacity-100">Undo (+1)</button>

            <details class="mt-4 border-t border-slate-200/60 pt-3 text-left dark:border-white/10">
                <summary class="cursor-pointer text-center text-sm font-bold opacity-60 hover:opacity-100">Edit item</summary>
                <form method="POST" action="{{ route('stock.update', $item['id']) }}" class="mt-3 space-y-2">
                    @csrf @method('PUT')
                    <div class="stock-row">
                        <input name="icon" value="{{ $item['icon'] }}" maxlength="16" class="input" style="width:4.5rem;text-align:center" aria-label="Emoji">
                        <input name="name" value="{{ $item['name'] }}" maxlength="50" class="input" style="flex:1" aria-label="Name">
                    </div>
                    <div class="stock-row">
                        <input type="number" name="stock" min="0" required value="{{ $item['stock'] }}" class="input" style="flex:1" aria-label="Stock">
                        <button class="btn btn-blue">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('stock.destroy', $item['id']) }}" class="mt-2 text-center" onsubmit="return confirm('Remove this item?')">
                    @csrf @method('DELETE')
                    <button class="text-sm font-semibold text-rose-500 hover:underline">Remove item</button>
                </form>
            </details>
        </div>
    @endforeach
    @if (count($items) === 0)
        <div class="empty-state" style="grid-column: 1 / -1">
            <div class="stock-emoji">📦</div>
            <h2 class="mt-3 text-xl font-extrabold">No items yet</h2>
            <p class="mt-1 text-sm opacity-70">This is your own stock list. Add your first item above to start counting.</p>
        </div>
    @endif
</section>

<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var grid = document.getElementById('stock-grid');

    function render(items) {
        items.forEach(function (it) {
            var el = grid.querySelector('[data-item="' + it.id + '"] [data-count]');
            if (el) el.textContent = it.stock;
        });
    }

    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-delta]');
        if (!btn) return;
        var id = btn.closest('[data-item]').dataset.item;
        fetch('{{ url('stock') }}/' + id + '/adjust', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({delta: parseInt(btn.dataset.delta, 10)})
        }).then(function (r) { return r.json(); }).then(render).catch(function () {});
    });

    // Pick up changes made from another screen.
    setInterval(function () {
        fetch('{{ route('stock.data') }}', {headers: {'Accept': 'application/json'}})
            .then(function (r) { return r.json(); }).then(render).catch(function () {});
    }, 3000);
})();
</script>
@endsection

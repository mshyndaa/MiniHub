@extends('layouts.app')
@section('title', 'Spin Wheel · '.config('app.name'))
@section('wide', '1')

@section('content')
@include('partials.page-header', ['symbol' => 'sparkles', 'title' => 'Spin Wheel', 'subtitle' => 'Upload names from Excel, choose how many winners, then spin', 'tint' => 'yellow'])

<style>
    .spin-layout { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 64rem) { .spin-layout { grid-template-columns: 22rem minmax(0, 1fr); align-items: start; } }
    .spin-stage { position: relative; width: min(100%, 36rem); margin: 0 auto; aspect-ratio: 1; }
    .spin-stage canvas { width: 100%; height: 100%; display: block; }
    .spin-pointer { position: absolute; top: -.35rem; left: 50%; transform: translateX(-50%); width: 0; height: 0; z-index: 2;
        border-left: 1rem solid transparent; border-right: 1rem solid transparent; border-top: 2rem solid #f2587f;
        filter: drop-shadow(0 3px 2px rgba(0,0,0,.3)); }
    .spin-fs:fullscreen { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2vh; padding: 2vh; background: radial-gradient(60rem 40rem at 10% -10%, #bfe3ff, transparent 60%), #eaf5ff; }
    .spin-fs:fullscreen #banner { display: block; }
    .spin-fs:fullscreen .spin-stage { width: min(92vh, 96vw); }
    .spin-names { max-height: 12rem; overflow: auto; }
    .spin-winner { animation: spin-pop .45s cubic-bezier(.2,1.6,.4,1) both; }
    @keyframes spin-pop { from { transform: scale(.4); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

<div class="spin-layout">
    {{-- Setup --}}
    <div class="space-y-4">
        <div class="card space-y-3">
            <p class="font-extrabold">1. Load names</p>
            <label class="btn btn-soft w-full cursor-pointer justify-center">
                <x-icon name="file-plus" class="h-4 w-4" /> Upload Excel / CSV
                <input id="file" type="file" accept=".xlsx,.xls,.csv,.txt" class="hidden">
            </label>
            <button id="template" type="button" class="btn btn-soft w-full justify-center"><x-icon name="download" class="h-4 w-4" /> Download template</button>
            <p class="text-xs opacity-60">Names are read from the first column of the first sheet (a header row such as "Name" is skipped).</p>
            <textarea id="paste" rows="3" class="input w-full" placeholder="…or type / paste names, one per line"></textarea>
            <button id="usePaste" type="button" class="btn btn-soft w-full justify-center">Use pasted names</button>
            <p id="msg" class="text-sm font-semibold text-rose-600" hidden></p>
        </div>

        <div class="card space-y-3">
            <p class="font-extrabold">2. Settings</p>
            <label class="block text-sm font-semibold">Number of names to show
                <input id="count" type="number" min="1" value="1" class="input mt-1 w-full">
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold">
                <input id="noRepeat" type="checkbox" checked class="h-4 w-4"> Don't pick the same name twice (across spins)
            </label>
            <div class="flex items-center justify-between text-sm">
                <span><b id="total">0</b> names loaded · <b id="left">0</b> available</span>
                <button id="clear" type="button" class="font-bold text-rose-600 hover:underline">Clear</button>
            </div>
            <div id="names" class="spin-names rounded-2xl bg-slate-100/70 p-2 text-sm dark:bg-white/5" hidden></div>
        </div>
    </div>

    {{-- Wheel + results --}}
    <div class="space-y-6">
        <div class="flex flex-wrap justify-center gap-2">
            <a href="{{ route('spin.tv') }}" target="_blank" class="btn btn-soft"><x-icon name="eye" class="h-4 w-4" /> Open TV display</a>
            <button id="fs" type="button" class="btn btn-soft"><x-icon name="layout-grid" class="h-4 w-4" /> Full screen</button>
        </div>
        <div id="stageWrap" class="spin-fs">
        <div class="spin-stage">
            <div class="spin-pointer"></div>
            <canvas id="wheel" width="800" height="800"></canvas>
        </div>
        <div class="text-center">
            <button id="spin" type="button" class="btn btn-pink px-10 text-lg" disabled><x-icon name="sparkles" class="h-5 w-5" /> SPIN</button>
        </div>
        <p id="banner" class="hidden text-center text-3xl font-black"></p>
        </div>

        <div id="resultCard" class="card" hidden>
            <div class="mb-3 flex items-center justify-between gap-2">
                <p class="font-extrabold">Winners</p>
                <div class="flex gap-2">
                    <button id="copy" type="button" class="btn btn-soft"><x-icon name="copy" class="h-4 w-4" /> Copy</button>
                    <button id="reset" type="button" class="btn btn-soft"><x-icon name="rotate" class="h-4 w-4" /> Reset history</button>
                </div>
            </div>
            <div id="winners" class="flex flex-wrap gap-2"></div>
        </div>
    </div>
</div>

@include('spin.wheel-js')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
(() => {
    const $ = id => document.getElementById(id);
    const wheel = SpinWheel($('wheel'));
    let spinning = false;
    let history = [];

    const error = t => { $('msg').textContent = t; $('msg').hidden = !t; };

    // Publish the wheel (and an optional spin to replay) for the TV display.
    function publish(plan = null, added = 0) {
        fetch(@json(route('spin.push')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
            body: JSON.stringify({ names: wheel.names, picked: [...wheel.picked], history, added, plan }),
        }).catch(() => {});
    }

    function setNames(list) {
        wheel.names = list.map(s => String(s ?? '').trim().slice(0, 100)).filter(Boolean).slice(0, 5000);
        wheel.picked = new Set(); history = []; wheel.angle = 0;
        renderResults(); refresh(); publish();
    }

    function refresh() {
        wheel.noRepeat = $('noRepeat').checked;
        const avail = wheel.pool().length;
        $('total').textContent = wheel.names.length;
        $('left').textContent = avail;
        $('count').max = Math.max(avail, 1);
        $('spin').disabled = spinning || avail === 0;
        const box = $('names');
        box.hidden = !wheel.names.length;
        box.innerHTML = '';
        wheel.names.slice(0, 500).forEach((n, i) => {
            const d = document.createElement('div');
            d.className = 'truncate px-2 py-0.5' + (wheel.picked.has(i) && wheel.noRepeat ? ' line-through opacity-40' : '');
            d.textContent = n;
            box.appendChild(d);
        });
        wheel.draw();
    }

    async function spin() {
        if (spinning) return;
        wheel.noRepeat = $('noRepeat').checked;
        const plan = wheel.makePlan(parseInt($('count').value) || 1);
        $('count').value = plan.steps.length;
        const winners = plan.steps.map(st => st.pool[st.k]);
        spinning = true; $('spin').disabled = true; error('');

        // Tell the TV what is about to happen (final state + the spin to replay).
        const finalHistory = history.concat(winners.map(i => wheel.names[i]));
        const finalPicked = new Set([...wheel.picked, ...winners]);
        const keep = history, keepPicked = wheel.picked;
        history = finalHistory; wheel.picked = finalPicked;
        publish(plan, winners.length);
        history = keep; wheel.picked = keepPicked;

        await wheel.play(plan, i => { history.push(wheel.names[i]); addWinner(wheel.names[i]); });
        winners.forEach(i => wheel.picked.add(i));
        spinning = false;
        refresh();
    }

    function addWinner(name) {
        $('resultCard').hidden = false;
        const el = document.createElement('span');
        el.className = 'spin-winner rounded-full bg-gradient-to-br from-amber-200 to-amber-400 px-4 py-2 text-lg font-extrabold text-amber-950 shadow';
        el.textContent = `${history.length}. ${name}`;
        $('winners').appendChild(el);
        $('banner').textContent = '🎉 ' + name;
    }

    function renderResults() {
        $('winners').innerHTML = '';
        $('resultCard').hidden = true;
        $('banner').textContent = '';
    }

    // Parsing ---------------------------------------------------------------
    $('file').addEventListener('change', async e => {
        const f = e.target.files[0]; e.target.value = '';
        if (!f) return;
        error('');
        try {
            const wb = XLSX.read(await f.arrayBuffer(), { type: 'array' });
            const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1, blankrows: false });
            const list = rows.map(r => r[0]).filter(v => v !== undefined && String(v).trim() !== '');
            if (list.length > 1 && /^(names?|nama|full ?name|participants?)$/i.test(String(list[0]).trim())) list.shift();
            if (!list.length) return error('No names found in the first column.');
            setNames(list);
        } catch (err) {
            error(window.XLSX ? 'Could not read that file.' : 'Excel reader could not load (check your internet connection).');
        }
    });

    $('template').addEventListener('click', () => {
        if (!window.XLSX) return error('Excel writer could not load (check your internet connection).');
        const ws = XLSX.utils.aoa_to_sheet([['Name'], ['Alice Johnson'], ['Budi Santoso'], ['Citra Dewi'], ['David Lee'], ['Eka Putra']]);
        ws['!cols'] = [{ wch: 30 }];
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Names');
        XLSX.writeFile(wb, 'spin-wheel-template.xlsx');
    });

    $('usePaste').addEventListener('click', () => {
        const list = $('paste').value.split(/?
/);
        if (!list.some(s => s.trim())) return error('Type or paste at least one name.');
        error(''); setNames(list);
    });

    $('spin').addEventListener('click', spin);
    $('noRepeat').addEventListener('change', () => { refresh(); publish(); });
    $('clear').addEventListener('click', () => { if (!spinning) { setNames([]); $('paste').value = ''; error(''); } });
    $('reset').addEventListener('click', () => { if (!spinning) { wheel.picked = new Set(); history = []; renderResults(); refresh(); publish(); } });
    $('copy').addEventListener('click', () => {
        navigator.clipboard?.writeText(history.map((n, i) => `${i + 1}. ${n}`).join('
'));
    });
    $('fs').addEventListener('click', () => {
        const el = $('stageWrap');
        document.fullscreenElement ? document.exitFullscreen() : el.requestFullscreen?.();
    });

    refresh();
    publish();
})();
</script>
@endsection

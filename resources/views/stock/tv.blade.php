<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock TV · {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.bunny.net/css?family=nunito:700,800,900" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Nunito', system-ui, sans-serif; color: #fff; overflow: hidden;
            background:
                radial-gradient(60rem 40rem at 10% -10%, rgba(56, 189, 248, .22), transparent 60%),
                radial-gradient(50rem 40rem at 100% 110%, rgba(236, 72, 153, .20), transparent 60%),
                #070a1a;
        }
        .tv { height: 100%; display: flex; flex-direction: column; padding: 3vh 4vw; gap: 3vh; }
        .title { text-align: center; font-weight: 800; letter-spacing: .6em; text-transform: uppercase; font-size: 2.2vh; opacity: .55; }
        .grid { flex: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(28vw, 1fr)); gap: 3vw; min-height: 0; }
        .tile {
            position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-radius: 4vh; padding: 3vh 2vw; text-align: center; overflow: hidden;
            background: linear-gradient(160deg, rgba(255,255,255,.10), rgba(255,255,255,.03));
            border: 1px solid rgba(255,255,255,.14);
            box-shadow: 0 3vh 8vh -2vh rgba(0,0,0,.6), inset 0 1px 0 rgba(255,255,255,.12);
        }
        .tile::before { content: ""; position: absolute; inset: -40% -20% auto; height: 80%; background: radial-gradient(closest-side, var(--glow), transparent); opacity: .35; }
        .tile > * { position: relative; }
        .icon { font-size: 14vh; line-height: 1; filter: drop-shadow(0 1.5vh 2vh rgba(0,0,0,.4)); }
        .name { margin-top: 2vh; font-size: 5vh; font-weight: 900; letter-spacing: .3em; text-transform: uppercase; opacity: .85; }
        .count {
            margin-top: 1vh; font-size: min(30vh, 17vw); font-weight: 900; line-height: 1; font-variant-numeric: tabular-nums;
            background: linear-gradient(180deg, #fff 30%, var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent;
            transition: transform .25s;
        }
        .count.tick { transform: scale(1.08); }
        .unit { margin-top: 1vh; font-size: 2.6vh; font-weight: 700; letter-spacing: .35em; text-transform: uppercase; opacity: .5; }
        .tile.low .count { background: linear-gradient(180deg, #fff 30%, #fb7185); -webkit-background-clip: text; background-clip: text; }
        .tile:nth-child(1) { --glow: #f59e0b; --accent: #fbbf24; }
        .tile:nth-child(2) { --glow: #ec4899; --accent: #f9a8d4; }
        .tile:nth-child(n+3) { --glow: #38bdf8; --accent: #7dd3fc; }
        @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } .count { font-size: 18vh; } }
    </style>
</head>
<body>
<main class="tv">
    <div class="title">Available now</div>
    <div class="grid" id="tv">
        @foreach ($items as $item)
            <div class="tile {{ $item['stock'] <= 50 ? 'low' : '' }}" data-item="{{ $item['id'] }}">
                <div class="icon">{{ $item['icon'] }}</div>
                <div class="name">{{ $item['name'] }}</div>
                <div class="count" data-count>{{ $item['stock'] }}</div>
                <div class="unit">left</div>
            </div>
        @endforeach
    </div>
</main>
<script>
(function () {
    var tv = document.getElementById('tv');
    function poll() {
        fetch('{{ route('stock.data') }}', {headers: {'Accept': 'application/json'}})
            .then(function (r) { return r.json(); })
            .then(function (items) {
                // An item was added, removed or renamed: reload to rebuild the tiles.
                var shown = Array.prototype.map.call(tv.children, function (t) { return t.dataset.item + '|' + t.querySelector('.name').textContent + '|' + t.querySelector('.icon').textContent; }).join();
                var fresh = items.map(function (it) { return it.id + '|' + it.name + '|' + (it.icon || ''); }).join();
                if (shown !== fresh) { location.reload(); return; }
                items.forEach(function (it) {
                    var tile = tv.querySelector('[data-item="' + it.id + '"]');
                    if (!tile) return;
                    var el = tile.querySelector('[data-count]');
                    tile.classList.toggle('low', it.stock <= 50);
                    if (el.textContent != it.stock) {
                        el.textContent = it.stock;
                        el.classList.add('tick');
                        setTimeout(function () { el.classList.remove('tick'); }, 250);
                    }
                });
            }).catch(function () {});
    }
    setInterval(poll, 2000);
})();
</script>
</body>
</html>

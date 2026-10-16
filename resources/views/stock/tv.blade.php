<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Stock TV · {{ config('app.name') }}</title>
    <script src="{{ asset('session-guard.js') }}"></script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.bunny.net/css?family=nunito:700,800,900" rel="stylesheet">
    <script>
        (function() {
            var t = null;
            try {
                t = localStorage.getItem('theme');
            } catch (e) {}
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) document
                .documentElement.classList.add('dark');
        })();
    </script>
    <style>
        :root {
            --fg: #0f172a;
            --bg-base: #eaf3fd;
            --glow1: rgba(56, 189, 248, .35);
            --glow2: rgba(236, 72, 153, .16);
            --tile-a: rgba(255, 255, 255, .95);
            --tile-b: rgba(255, 255, 255, .65);
            --tile-border: rgba(15, 23, 42, .08);
            --tile-shadow: rgba(30, 64, 120, .25);
            --tile-hi: rgba(255, 255, 255, .9);
            --top: #1e293b;
            --low: #e11d48;
            --btn-bg: rgba(255, 255, 255, .8);
            --btn-border: rgba(15, 23, 42, .12);
            --glow-op: .22;
        }

        .dark {
            --fg: #fff;
            --bg-base: #070a1a;
            --glow1: rgba(56, 189, 248, .22);
            --glow2: rgba(236, 72, 153, .20);
            --tile-a: rgba(255, 255, 255, .10);
            --tile-b: rgba(255, 255, 255, .03);
            --tile-border: rgba(255, 255, 255, .14);
            --tile-shadow: rgba(0, 0, 0, .6);
            --tile-hi: rgba(255, 255, 255, .12);
            --top: #fff;
            --low: #fb7185;
            --btn-bg: rgba(255, 255, 255, .10);
            --btn-border: rgba(255, 255, 255, .2);
            --glow-op: .35;
        }

        * {
            box-sizing: border-box;
            margin: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Nunito', system-ui, sans-serif;
            color: var(--fg);
            overflow: hidden;
            background:
                radial-gradient(60rem 40rem at 10% -10%, var(--glow1), transparent 60%),
                radial-gradient(50rem 40rem at 100% 110%, var(--glow2), transparent 60%),
                var(--bg-base);
        }

        .tv {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 3vh 4vw;
            gap: 3vh;
        }

        .title {
            text-align: center;
            font-weight: 800;
            letter-spacing: .6em;
            text-transform: uppercase;
            font-size: 2.2vh;
            opacity: .55;
        }

        .grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(28vw, 1fr));
            gap: 3vw;
            min-height: 0;
            position: relative;
        }

        /* Custom layout: tiles are placed by percentage inside the grid area. */
        .grid.custom {
            display: block;
        }

        .grid.custom .tile {
            position: absolute;
        }

        body.editing .tile {
            cursor: move;
            outline: 2px dashed var(--accent);
            outline-offset: -6px;
            touch-action: none;
            user-select: none;
        }

        .resize-handle {
            display: none;
            position: absolute !important;
            right: 0;
            bottom: 0;
            width: 5vh;
            height: 5vh;
            cursor: nwse-resize;
            background: linear-gradient(135deg, transparent 50%, var(--accent) 50%);
            border-bottom-right-radius: 4vh;
            z-index: 2;
        }

        body.editing .resize-handle {
            display: block;
        }

        body.txt .tile {
            color: var(--txt);
        }

        body.txt .count {
            background: none;
            color: var(--txt);
        }

        body.tilebg .tile {
            background: var(--tile-custom);
        }

        .style-panel {
            position: fixed;
            top: calc(2vh + 3.2em);
            right: 2vw;
            z-index: 6;
            display: none;
            flex-direction: column;
            gap: .7em;
            min-width: 15em;
            padding: 1em;
            color: var(--fg);
            font: 700 14px 'Nunito', system-ui, sans-serif;
            background: var(--bg-base);
            border: 1px solid var(--btn-border);
            border-radius: 1em;
            box-shadow: 0 1em 3em rgba(0, 0, 0, .35);
        }

        .style-panel.open {
            display: flex;
        }

        .style-panel label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1em;
        }

        .style-panel input[type=color] {
            width: 3em;
            height: 2em;
            padding: 0;
            border: 0;
            background: none;
            cursor: pointer;
        }

        .style-panel button {
            font: inherit;
            cursor: pointer;
            color: var(--fg);
            background: var(--btn-bg);
            border: 1px solid var(--btn-border);
            border-radius: 999px;
            padding: .4em 1em;
        }

        :fullscreen .style-panel {
            display: none;
        }

        #layout-reset {
            display: none;
        }

        body.custom-layout #layout-reset {
            display: inline-block;
        }

        .tile {
            container-type: size;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 4vh;
            padding: 3vh 2vw;
            text-align: center;
            overflow: hidden;
            background: linear-gradient(160deg, var(--tile-a), var(--tile-b));
            border: 1px solid var(--tile-border);
            box-shadow: 0 3vh 8vh -2vh var(--tile-shadow), inset 0 1px 0 var(--tile-hi);
        }

        .tile>* {
            position: relative;
        }

        .icon {
            font-size: calc(24cqmin * var(--fs, 1));
            line-height: 1;
            filter: drop-shadow(0 1.5vh 2vh rgba(0, 0, 0, .4));
        }

        .name {
            margin-top: 2vh;
            font-size: calc(8cqmin * var(--fs, 1));
            max-width: 100%;
            overflow-wrap: anywhere;
            font-weight: 900;
            letter-spacing: .3em;
            text-transform: uppercase;
            opacity: .85;
        }

        .count {
            margin-top: 1vh;
            font-size: calc(48cqmin * var(--fs, 1));
            font-weight: 900;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            background: linear-gradient(180deg, var(--top) 30%, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            transition: transform .25s;
        }

        .count.tick {
            transform: scale(1.08);
        }

        .unit {
            margin-top: 1vh;
            font-size: 2.6vh;
            font-weight: 700;
            letter-spacing: .35em;
            text-transform: uppercase;
            opacity: .5;
        }

        .tile.low .count {
            background: linear-gradient(180deg, var(--top) 30%, var(--low));
            -webkit-background-clip: text;
            background-clip: text;
        }

        .tile:nth-child(1) {
            --glow: #f59e0b;
            --accent: #fbbf24;
        }

        .tile:nth-child(2) {
            --glow: #ec4899;
            --accent: #f9a8d4;
        }

        .tile:nth-child(n+3) {
            --glow: #38bdf8;
            --accent: #7dd3fc;
        }

        :root:not(.dark) .tile:nth-child(1) {
            --accent: #d97706;
        }

        :root:not(.dark) .tile:nth-child(2) {
            --accent: #db2777;
        }

        :root:not(.dark) .tile:nth-child(n+3) {
            --accent: #0284c7;
        }

        .fs-btn {
            position: fixed;
            top: 2vh;
            right: 2vw;
            z-index: 5;
            cursor: pointer;
            color: var(--fg);
            font: 700 14px 'Nunito', system-ui, sans-serif;
            background: var(--btn-bg);
            border: 1px solid var(--btn-border);
            border-radius: 999px;
            padding: .5em 1em;
            opacity: .6;
            transition: opacity .2s;
        }

        .fs-btn:hover {
            opacity: 1;
        }

        .back-btn {
            right: auto;
            left: 2vw;
            text-decoration: none;
        }

        .toolbar {
            position: fixed;
            top: 2vh;
            right: 2vw;
            z-index: 5;
            display: flex;
            gap: .5em;
        }

        .toolbar .fs-btn {
            position: static;
        }

        .bg-layer {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: center / cover no-repeat;
            display: none;
        }

        .bg-layer::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .2);
        }

        .dark .bg-layer::after {
            background: rgba(0, 0, 0, .35);
        }

        body.has-bg .bg-layer {
            display: block;
        }

        body.no-overlay .bg-layer::after {
            display: none;
        }

        body.has-bg {
            background: var(--bg-base);
        }

        #bg-reset {
            display: none;
        }

        body.has-bg #bg-reset {
            display: inline-block;
        }

        :fullscreen .fs-btn,
        :fullscreen .toolbar {
            display: none;
        }

        :fullscreen {
            cursor: none;
        }

        @media (max-width: 800px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <a href="{{ route('stock.index') }}" class="fs-btn back-btn" title="Back to stock">← Back</a>
    <div class="bg-layer" id="bg-layer"></div>
    <div class="toolbar">
        <button type="button" class="fs-btn" id="layout-reset" title="Back to the automatic layout">⊞ Reset layout</button>
        <button type="button" class="fs-btn" id="style-btn" title="Text colour, tile colour and transparency">🎨 Style</button>
        <button type="button" class="fs-btn" id="layout-edit" title="Move and resize the tiles">✥ Layout</button>
        <button type="button" class="fs-btn" id="bg-reset" title="Back to the default background">↺ Reset</button>
        <button type="button" class="fs-btn" id="bg-pick" title="Choose a background image (this screen only)">🖼
            Background</button>
        <input type="file" id="bg-file" accept="image/*" hidden>
        <button type="button" class="fs-btn" id="theme" title="Toggle light/dark">◐ Theme</button>
        <button type="button" class="fs-btn" id="fs" title="Full screen (F or double-click)">⛶ Full
            screen</button>
    </div>
    <div class="style-panel" id="style-panel">
        <label>Image overlay <input type="checkbox" id="st-overlay" checked></label>
        <label>Text size <span id="st-fs-val">100%</span></label>
        <input type="range" id="st-fs" min="40" max="200" value="100">
        <label>Text colour <input type="color" id="st-text" value="#ffffff"></label>
        <label>Tile colour <input type="color" id="st-tile" value="#ffffff"></label>
        <label>Tile opacity <span id="st-op-val">10%</span></label>
        <input type="range" id="st-opacity" min="0" max="100" value="10">
        <button type="button" id="st-reset">Reset style</button>
    </div>
    <main class="tv">
        <div class="grid" id="tv">
            @foreach ($items as $item)
                <div class="tile {{ $item['stock'] <= 50 ? 'low' : '' }}" data-item="{{ $item['id'] }}">
                    @if ($item['icon'])<div class="icon">{{ $item['icon'] }}</div>@endif
                    @if ($item['name'] !== '')<div class="name">{{ $item['name'] }}</div>@endif
                    <div class="count" data-count>{{ $item['stock'] }}</div>
                </div>
            @endforeach
        </div>
    </main>
    <script>
        (function() {
            var tv = document.getElementById('tv');

            function toggleFullscreen() {
                if (document.fullscreenElement) document.exitFullscreen();
                else if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen().catch(
                    function() {});
            }
            document.getElementById('theme').addEventListener('click', function() {
                var dark = document.documentElement.classList.toggle('dark');
                try {
                    localStorage.setItem('theme', dark ? 'dark' : 'light');
                } catch (e) {}
            });
            document.getElementById('fs').addEventListener('click', toggleFullscreen);

            // Custom background: saved on the signed-in user's account, so it follows them to any device.
            var bgLayer = document.getElementById('bg-layer');
            var csrf = document.querySelector('meta[name="csrf-token"]').content;

            function applyBg(url) {
                bgLayer.style.backgroundImage = url ? 'url("' + url + '")' : '';
                document.body.classList.toggle('has-bg', !!url);
            }
            applyBg(@json($background));

            function saveBg(method, body) {
                return fetch('{{ route('stock.background.update') }}', {
                    method: method,
                    body: body,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                }).then(function(r) {
                    if (!r.ok) throw new Error();
                    return r.json();
                });
            }
            document.getElementById('bg-pick').addEventListener('click', function() {
                document.getElementById('bg-file').click();
            });
            document.getElementById('bg-reset').addEventListener('click', function() {
                saveBg('DELETE').then(function() {
                    applyBg(null);
                }).catch(function() {
                    alert('Could not reset the background.');
                });
            });
            document.getElementById('bg-file').addEventListener('change', function() {
                var file = this.files[0];
                this.value = '';
                if (!file) return;
                var img = new Image(),
                    src = URL.createObjectURL(file);
                img.onload = function() {
                    // Downscale so the picture fits comfortably in localStorage.
                    var k = Math.min(1, 1920 / Math.max(img.width, img.height));
                    var c = document.createElement('canvas');
                    c.width = Math.round(img.width * k);
                    c.height = Math.round(img.height * k);
                    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                    URL.revokeObjectURL(src);
                    c.toBlob(function(blob) {
                        var form = new FormData();
                        form.append('image', blob, 'background.jpg');
                        saveBg('POST', form).then(function(res) {
                                applyBg(res.url);
                            })
                            .catch(function() {
                                alert('Could not save this image. Try a smaller one.');
                            });
                    }, 'image/jpeg', 0.85);
                };
                img.onerror = function() {
                    URL.revokeObjectURL(src);
                    alert('That file is not a supported image.');
                };
                img.src = src;
            });

            // Custom layout: drag a tile to move it, drag its corner to resize. Saved per user (percent of the grid).
            var layout = @json($layout);
            var layoutEditBtn = document.getElementById('layout-edit');
            var editing = false;
            var saveTimer = null;

            function api(url, method, json) {
                return fetch(url, {
                    method: method,
                    body: json ? JSON.stringify(json) : undefined,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                }).then(function(r) {
                    if (!r.ok) throw new Error();
                });
            }

            function tiles() {
                return Array.prototype.slice.call(tv.children);
            }

            function place(tile, b) {
                tile.style.left = b.x + '%';
                tile.style.top = b.y + '%';
                tile.style.width = b.w + '%';
                tile.style.height = b.h + '%';
            }

            function applyLayout() {
                var ok = layout && tiles().length && tiles().every(function(t) {
                    return layout[t.dataset.item];
                });
                tv.classList.toggle('custom', !!ok);
                document.body.classList.toggle('custom-layout', !!ok);
                tiles().forEach(function(t) {
                    if (ok) place(t, layout[t.dataset.item]);
                    else t.style.left = t.style.top = t.style.width = t.style.height = '';
                });
            }

            // Freeze the current automatic arrangement as explicit boxes so it can be edited.
            function freezeLayout() {
                var g = tv.getBoundingClientRect();
                layout = {};
                tiles().forEach(function(t) {
                    var r = t.getBoundingClientRect();
                    layout[t.dataset.item] = {
                        x: (r.left - g.left) / g.width * 100,
                        y: (r.top - g.top) / g.height * 100,
                        w: r.width / g.width * 100,
                        h: r.height / g.height * 100
                    };
                });
                applyLayout();
            }

            function queueSave() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(function() {
                    api('{{ route('stock.layout.save') }}', 'PUT', {
                        layout: layout
                    }).catch(function() {
                        alert('Could not save the layout.');
                    });
                }, 400);
            }

            tiles().forEach(function(tile) {
                var handle = document.createElement('div');
                handle.className = 'resize-handle';
                tile.appendChild(handle);

                tile.addEventListener('pointerdown', function(e) {
                    if (!editing) return;
                    e.preventDefault();
                    var resizing = e.target === handle;
                    var g = tv.getBoundingClientRect();
                    var b = layout[tile.dataset.item];
                    var start = {
                        x: e.clientX,
                        y: e.clientY,
                        b: {
                            x: b.x,
                            y: b.y,
                            w: b.w,
                            h: b.h
                        }
                    };
                    tile.setPointerCapture(e.pointerId);
                    tile.style.zIndex = 3;

                    function move(ev) {
                        var dx = (ev.clientX - start.x) / g.width * 100;
                        var dy = (ev.clientY - start.y) / g.height * 100;
                        if (resizing) {
                            b.w = Math.min(100 - b.x, Math.max(10, start.b.w + dx));
                            b.h = Math.min(100 - b.y, Math.max(10, start.b.h + dy));
                        } else {
                            b.x = Math.min(100 - b.w, Math.max(0, start.b.x + dx));
                            b.y = Math.min(100 - b.h, Math.max(0, start.b.y + dy));
                        }
                        place(tile, b);
                    }

                    function up() {
                        tile.removeEventListener('pointermove', move);
                        tile.removeEventListener('pointerup', up);
                        tile.removeEventListener('pointercancel', up);
                        tile.style.zIndex = '';
                        queueSave();
                    }
                    tile.addEventListener('pointermove', move);
                    tile.addEventListener('pointerup', up);
                    tile.addEventListener('pointercancel', up);
                });
            });

            layoutEditBtn.addEventListener('click', function() {
                editing = !editing;
                if (editing && !tv.classList.contains('custom')) freezeLayout();
                document.body.classList.toggle('editing', editing);
                layoutEditBtn.textContent = editing ? '✓ Done' : '✥ Layout';
            });
            document.getElementById('layout-reset').addEventListener('click', function() {
                api('{{ route('stock.layout.reset') }}', 'DELETE').then(function() {
                    layout = null;
                    editing = false;
                    document.body.classList.remove('editing');
                    layoutEditBtn.textContent = '✥ Layout';
                    applyLayout();
                }).catch(function() {
                    alert('Could not reset the layout.');
                });
            });
            applyLayout();

            // Look of the tiles: text colour, tile colour, tile opacity. Saved per user.
            var style = @json($style) || {};
            var stText = document.getElementById('st-text'),
                stTile = document.getElementById('st-tile'),
                stOverlay = document.getElementById('st-overlay'),
                stFs = document.getElementById('st-fs'),
                stFsVal = document.getElementById('st-fs-val'),
                stOp = document.getElementById('st-opacity'),
                stOpVal = document.getElementById('st-op-val'),
                stPanel = document.getElementById('style-panel'),
                styleTimer = null;

            function applyStyle() {
                var root = document.documentElement.style,
                    cls = document.body.classList;
                cls.toggle('txt', !!style.text);
                var fs = style.scale || 100;
                root.setProperty('--fs', fs / 100);
                stFs.value = fs;
                stFsVal.textContent = fs + '%';
                cls.toggle('no-overlay', style.overlay === false);
                stOverlay.checked = style.overlay !== false;
                if (style.text) root.setProperty('--txt', style.text);
                var custom = style.tile || style.opacity !== null && style.opacity !== undefined;
                cls.toggle('tilebg', !!custom);
                if (custom) {
                    var c = style.tile || '#ffffff',
                        a = (style.opacity === null || style.opacity === undefined ? 10 : style.opacity) / 100;
                    root.setProperty('--tile-custom', 'rgba(' + parseInt(c.slice(1, 3), 16) + ',' + parseInt(c.slice(3, 5),
                        16) + ',' + parseInt(c.slice(5, 7), 16) + ',' + a + ')');
                }
                if (style.text) stText.value = style.text;
                if (style.tile) stTile.value = style.tile;
                var op = style.opacity === null || style.opacity === undefined ? 10 : style.opacity;
                stOp.value = op;
                stOpVal.textContent = op + '%';
            }

            function changeStyle(key, value) {
                style[key] = value;
                applyStyle();
                clearTimeout(styleTimer);
                styleTimer = setTimeout(function() {
                    api('{{ route('stock.style.save') }}', 'PUT', {
                        text: style.text || null,
                        tile: style.tile || null,
                        opacity: style.opacity === undefined ? null : style.opacity,
                        overlay: style.overlay !== false,
                        scale: style.scale || null
                    }).catch(function() {
                        alert('Could not save the style.');
                    });
                }, 400);
            }
            stText.addEventListener('input', function() {
                changeStyle('text', this.value);
            });
            stTile.addEventListener('input', function() {
                changeStyle('tile', this.value);
            });
            stOverlay.addEventListener('change', function() {
                changeStyle('overlay', this.checked);
            });
            stFs.addEventListener('input', function() {
                changeStyle('scale', parseInt(this.value, 10));
            });
            stOp.addEventListener('input', function() {
                changeStyle('opacity', parseInt(this.value, 10));
            });
            document.getElementById('style-btn').addEventListener('click', function() {
                stPanel.classList.toggle('open');
            });
            document.getElementById('st-reset').addEventListener('click', function() {
                api('{{ route('stock.style.reset') }}', 'DELETE').then(function() {
                    style = {};
                    document.documentElement.style.removeProperty('--txt');
                    document.documentElement.style.removeProperty('--fs');
                    document.documentElement.style.removeProperty('--tile-custom');
                    stText.value = stTile.value = '#ffffff';
                    applyStyle();
                }).catch(function() {
                    alert('Could not reset the style.');
                });
            });
            applyStyle();

            document.addEventListener('dblclick', function() {
                if (!editing) toggleFullscreen();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'f' || e.key === 'F') toggleFullscreen();
            });

            function poll() {
                fetch('{{ route('stock.data') }}', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(items) {
                        // An item was added, removed or renamed: reload to rebuild the tiles.
                        var shown = Array.prototype.map.call(tv.children, function(t) {
                            return t.dataset.item + '|' + (t.querySelector('.name') || {textContent: ''}).textContent + '|' +
                                (t.querySelector('.icon') || {textContent: ''}).textContent;
                        }).join();
                        var fresh = items.map(function(it) {
                            return it.id + '|' + it.name + '|' + (it.icon || '');
                        }).join();
                        if (shown !== fresh) {
                            location.reload();
                            return;
                        }
                        items.forEach(function(it) {
                            var tile = tv.querySelector('[data-item="' + it.id + '"]');
                            if (!tile) return;
                            var el = tile.querySelector('[data-count]');
                            tile.classList.toggle('low', it.stock <= 50);
                            if (el.textContent != it.stock) {
                                el.textContent = it.stock;
                                el.classList.add('tick');
                                setTimeout(function() {
                                    el.classList.remove('tick');
                                }, 250);
                            }
                        });
                    }).catch(function() {});
            }
            setInterval(poll, 2000);
        })();
    </script>
</body>

</html>

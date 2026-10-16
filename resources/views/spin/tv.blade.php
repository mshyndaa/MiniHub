<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spin Wheel TV · {{ config('app.name') }}</title>
    <script src="{{ asset('session-guard.js') }}"></script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.bunny.net/css?family=nunito:700,800,900" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Nunito', system-ui, sans-serif; color: #fff; overflow: hidden; cursor: pointer;
            background:
                radial-gradient(60rem 40rem at 10% -10%, rgba(56, 189, 248, .22), transparent 60%),
                radial-gradient(50rem 40rem at 100% 110%, rgba(236, 72, 153, .20), transparent 60%),
                #070a1a;
        }
        .tv { height: 100%; display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 3vw; align-items: center; padding: 3vh 4vw; }
        .stage { position: relative; height: min(92vh, 48vw); aspect-ratio: 1; justify-self: center; }
        .stage canvas { width: 100%; height: 100%; display: block; }
        .pointer { position: absolute; top: -.5vh; left: 50%; transform: translateX(-50%); width: 0; height: 0; z-index: 2;
            border-left: 2.2vh solid transparent; border-right: 2.2vh solid transparent; border-top: 4.4vh solid #f2587f;
            filter: drop-shadow(0 3px 3px rgba(0,0,0,.5)); }
        .side { display: flex; flex-direction: column; gap: 3vh; min-width: 0; }
        .title { font-weight: 800; letter-spacing: .5em; text-transform: uppercase; font-size: 2.2vh; opacity: .55; }
        .banner { font-size: 8vh; font-weight: 900; line-height: 1.1; min-height: 9vh; word-break: break-word;
            background: linear-gradient(180deg, #fff 30%, #ffd166); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .pop { animation: pop .5s cubic-bezier(.2,1.6,.4,1) both; }
        @keyframes pop { from { transform: scale(.4); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .list { display: flex; flex-wrap: wrap; gap: 1.2vh; align-content: flex-start; max-height: 55vh; overflow: hidden; }
        .chip { font-size: 3vh; font-weight: 800; padding: 1vh 2vh; border-radius: 99px; color: #422006; background: linear-gradient(135deg, #fde68a, #fbbf24); }
        .hint { position: fixed; bottom: 1.5vh; right: 2vw; font-size: 1.8vh; opacity: .35; }
        @media (max-aspect-ratio: 1/1) { .tv { grid-template-columns: 1fr; grid-template-rows: auto auto; } .stage { height: auto; width: 90vw; } }
    </style>
</head>
<body>
    <div class="tv">
        <div class="stage">
            <div class="pointer"></div>
            <canvas id="wheel" width="1000" height="1000"></canvas>
        </div>
        <div class="side">
            <div class="title">Winners</div>
            <div id="banner" class="banner"></div>
            <div id="list" class="list"></div>
        </div>
    </div>
    <div class="hint">Click anywhere for full screen</div>

    @include('spin.wheel-js')
    <script>
    (() => {
        const $ = id => document.getElementById(id);
        const wheel = SpinWheel($('wheel'));
        let version = null, lastPlan = null, playing = false, first = true;

        function showList(history, banner) {
            $('list').innerHTML = '';
            history.forEach((n, i) => {
                const c = document.createElement('span');
                c.className = 'chip'; c.textContent = `${i + 1}. ${n}`;
                $('list').appendChild(c);
            });
            $('banner').textContent = banner ?? (history.length ? '🎉 ' + history[history.length - 1] : '');
        }

        async function apply(s) {
            wheel.names = s.names; wheel.picked = new Set(s.picked);
            const plan = s.plan;
            // Replay a fresh spin the first time we see it; on first load just show the current state.
            if (plan && plan.id !== lastPlan && !first && !playing) {
                lastPlan = plan.id; playing = true;
                const before = s.history.slice(0, s.history.length - s.added);
                const shownNow = [...before];
                wheel.picked = new Set(s.picked.filter(i => !plan.steps.some(st => st.pool[st.k] === i)));
                showList(shownNow, '');
                await wheel.play(plan, i => {
                    shownNow.push(wheel.names[i]);
                    $('banner').classList.remove('pop'); void $('banner').offsetWidth; $('banner').classList.add('pop');
                    showList(shownNow);
                });
                wheel.picked = new Set(s.picked);
                playing = false;
            } else if (!playing) {
                if (plan) lastPlan = plan.id;
                showList(s.history);
            }
            if (!playing) wheel.draw();
        }

        async function poll() {
            try {
                const res = await fetch(@json(route('spin.state')), { headers: { Accept: 'application/json' }, cache: 'no-store' });
                const s = await res.json();
                if (s.v !== version && !playing) {
                    version = s.v;
                    await apply(s);
                    first = false;
                }
            } catch (e) {}
            setTimeout(poll, 1500);
        }

        document.addEventListener('click', () => {
            document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen?.();
        });
        wheel.draw();
        poll();
    })();
    </script>
</body>
</html>

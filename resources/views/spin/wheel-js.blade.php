{{-- Shared wheel engine: window.SpinWheel(canvas) --}}
<script>
window.SpinWheel = function (canvas) {
    const ctx = canvas.getContext('2d');
    const TAU = Math.PI * 2;
    const COLORS = ['#52b6ff', '#ff8fb1', '#ffe27a', '#7ee0b0', '#b79cff', '#ffb27a'];
    const w = {
        names: [], picked: new Set(), noRepeat: true, angle: 0, shown: null,
        pool() { return this.names.map((_, i) => i).filter(i => !(this.noRepeat && this.picked.has(i))); },
    };

    w.draw = function () {
        const W = canvas.width, c = W / 2, r = c - 14;
        ctx.clearRect(0, 0, W, W);
        const idx = w.shown || w.pool(), n = idx.length;
        ctx.save();
        ctx.translate(c, c);
        ctx.shadowColor = 'rgba(0,0,0,.25)'; ctx.shadowBlur = 18;
        ctx.beginPath(); ctx.arc(0, 0, r + 6, 0, TAU); ctx.fillStyle = '#fff'; ctx.fill();
        ctx.shadowBlur = 0;
        if (!n) {
            ctx.beginPath(); ctx.arc(0, 0, r, 0, TAU); ctx.fillStyle = '#e2e8f0'; ctx.fill();
            ctx.fillStyle = '#64748b'; ctx.font = '800 36px Nunito, sans-serif'; ctx.textAlign = 'center';
            ctx.fillText(w.names.length ? 'All names drawn' : 'Load some names', 0, 12);
            ctx.restore(); return;
        }
        ctx.rotate(w.angle - Math.PI / 2);
        const a = TAU / n, labels = a * r > 22;
        for (let k = 0; k < n; k++) {
            ctx.beginPath(); ctx.moveTo(0, 0); ctx.arc(0, 0, r, k * a, (k + 1) * a); ctx.closePath();
            ctx.fillStyle = COLORS[k % COLORS.length];
            if (n > 1 && k === n - 1 && k % COLORS.length === 0) ctx.fillStyle = '#7ee0b0';
            ctx.fill();
            if (n < 200) { ctx.strokeStyle = 'rgba(255,255,255,.8)'; ctx.lineWidth = 2; ctx.stroke(); }
            if (labels) {
                ctx.save();
                ctx.rotate(k * a + a / 2);
                ctx.textAlign = 'right'; ctx.textBaseline = 'middle'; ctx.fillStyle = '#1e293b';
                ctx.font = `800 ${Math.max(11, Math.min(34, a * r * 0.55))}px Nunito, sans-serif`;
                let t = w.names[idx[k]] ?? '';
                if (t.length > 18) t = t.slice(0, 17) + '…';
                ctx.fillText(t, r - 18, 0);
                ctx.restore();
            }
        }
        ctx.restore();
        ctx.beginPath(); ctx.arc(c, c, 34, 0, TAU); ctx.fillStyle = '#fff'; ctx.fill();
        ctx.lineWidth = 6; ctx.strokeStyle = '#f2587f'; ctx.stroke();
    };

    const ease = t => 1 - Math.pow(1 - t, 4);

    // Rotate so slice k (of the step's pool) ends under the pointer.
    function spinTo(step) {
        return new Promise(res => {
            const n = step.pool.length, a = TAU / n;
            let target = -((step.k + 0.5 + step.j) * a);
            target = ((target % TAU) + TAU) % TAU;
            const start = w.angle = ((w.angle % TAU) + TAU) % TAU;
            const delta = ((target - start + TAU) % TAU) + TAU * step.turns;
            const t0 = performance.now();
            const tick = now => {
                const p = Math.min(1, (now - t0) / step.ms);
                w.angle = start + delta * ease(p);
                w.draw();
                p < 1 ? requestAnimationFrame(tick) : res();
            };
            requestAnimationFrame(tick);
        });
    }

    /** Decide the winners up front (unbiased shuffle) and describe each wheel stop. */
    w.makePlan = function (count) {
        const idx = w.pool();
        const want = Math.max(1, Math.min(count, idx.length));
        const order = [...idx];
        for (let i = order.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [order[i], order[j]] = [order[j], order[i]];
        }
        let remaining = [...idx];
        const steps = order.slice(0, want).map((win, s) => {
            const step = { pool: remaining, k: remaining.indexOf(win), j: (Math.random() - .5) * .7, ms: s ? 1800 : 4200, turns: s ? 3 : 6 };
            remaining = remaining.filter(x => x !== win);
            return step;
        });
        return { id: Date.now() + '-' + Math.random().toString(36).slice(2, 8), steps };
    };

    /** Animate a plan; onWin(index) fires as each name lands. The wheel drops each winner before the next stop. */
    w.play = async function (plan, onWin) {
        for (let s = 0; s < plan.steps.length; s++) {
            const step = plan.steps[s];
            w.shown = step.pool; w.angle = 0; w.draw();
            await spinTo(step);
            onWin(step.pool[step.k]);
            if (s < plan.steps.length - 1) await new Promise(r => setTimeout(r, 900));
        }
        w.shown = null; w.angle = 0;
    };

    return w;
};
</script>

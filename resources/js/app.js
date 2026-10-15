import './bootstrap';
import './notes';

// Light / dark toggle. The initial class is set by an inline script in the
// layout <head> (to avoid a flash); this just handles clicks.
document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        // Keep the phone's status bar in step with the in-app theme, not the OS setting.
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#0d0f15' : '#f1f5f9');
        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            // storage may be unavailable (private mode); the toggle still works for this page
        }
    });
});

// The server runs in UTC; show the greeting and date for the viewer's own clock.
const localGreeting = document.querySelector('[data-local-greeting]');
if (localGreeting) {
    const now = new Date();
    const h = now.getHours();
    localGreeting.textContent = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
    const localDate = document.querySelector('[data-local-date]');
    if (localDate) {
        localDate.textContent = now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }).replace(/^(\w+)\s/, '$1, ');
    }
}

// Copy button for the one-time temporary password.
document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const label = btn.querySelector('[data-label]') ?? btn;
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
            label.textContent = 'Copied!';
        } catch (e) {
            label.textContent = 'Press Ctrl+C';
        }
    });
});

// Mobile sidebar drawer.
const openSidebar = (open) => document.body.classList.toggle('sidebar-open', open);
document.querySelectorAll('[data-sidebar-open]').forEach((b) => b.addEventListener('click', () => openSidebar(true)));
document.querySelectorAll('[data-sidebar-close]').forEach((b) => b.addEventListener('click', () => openSidebar(false)));
document.addEventListener('keydown', (e) => e.key === 'Escape' && openSidebar(false));

// Desktop sidebar collapse (remembered; the initial class is set in the layout <head>).
document.querySelectorAll('[data-sidebar-collapse]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'open');
        } catch (e) {
            // ignore
        }
    });
});

// Cursor-following shine on glass cards.
document.addEventListener('pointermove', (e) => {
    const el = e.target.closest?.('.card, .app-card, .stat-tile, .feature-tile');
    if (!el) return;
    const r = el.getBoundingClientRect();
    el.style.setProperty('--mx', `${e.clientX - r.left}px`);
    el.style.setProperty('--my', `${e.clientY - r.top}px`);
});

// Count-up numbers: <span data-count="12">12</span>
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
document.querySelectorAll('[data-count]').forEach((el) => {
    const target = parseInt(el.dataset.count, 10);
    if (reduceMotion || !Number.isFinite(target) || target === 0) return;
    const start = performance.now();
    const duration = 900;
    const tick = (now) => {
        const t = Math.min((now - start) / duration, 1);
        el.textContent = Math.round(target * (1 - Math.pow(1 - t, 3)));
        if (t < 1) requestAnimationFrame(tick);
    };
    el.textContent = '0';
    requestAnimationFrame(tick);
});

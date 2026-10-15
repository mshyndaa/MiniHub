import './bootstrap';

// Light / dark toggle. The initial class is set by an inline script in the
// layout <head> (to avoid a flash); this just handles clicks.
document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            // storage may be unavailable (private mode); the toggle still works for this page
        }
    });
});

// Copy button for the one-time temporary password.
document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
            btn.textContent = 'Copied!';
        } catch (e) {
            btn.textContent = 'Press Ctrl+C';
        }
    });
});

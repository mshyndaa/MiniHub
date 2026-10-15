// Biometric app lock (WebAuthn platform authenticator: fingerprint / face / device PIN).
//
// This is a device-local privacy lock on top of the normal login session: the credential never
// leaves this device and the server does not verify it. It stops someone who picks up an
// already-signed-in phone or laptop; it does not replace the password.
//
// - The credential id is kept in localStorage under window.cjAppLockKey (set in the layout <head>).
// - sessionStorage 'applock-ok' = this tab/app session has been unlocked.
// - The app locks the moment it is hidden (app switch, screen off, tab change), but not when
//   navigating between pages of the app itself.

const key = window.cjAppLockKey;
const root = document.documentElement;
const overlay = document.getElementById('applock');

const b64 = {
    enc: (buf) => btoa(String.fromCharCode(...new Uint8Array(buf))),
    dec: (str) => Uint8Array.from(atob(str), (c) => c.charCodeAt(0)),
};
const store = {
    get: () => { try { return localStorage.getItem(key); } catch (e) { return null; } },
    set: (v) => { try { localStorage.setItem(key, v); } catch (e) { /* ignore */ } },
    del: () => { try { localStorage.removeItem(key); } catch (e) { /* ignore */ } },
};
const session = {
    ok: () => { try { return sessionStorage.getItem('applock-ok') === '1'; } catch (e) { return false; } },
    set: (v) => { try { v ? sessionStorage.setItem('applock-ok', '1') : sessionStorage.removeItem('applock-ok'); } catch (e) { /* ignore */ } },
};

export const supported = async () =>
    !!(window.isSecureContext && window.PublicKeyCredential &&
        await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(() => false));

// Ask the device to verify the user with the stored credential. Resolves true on success.
async function verify() {
    const id = store.get();
    if (!id) return true;
    try {
        await navigator.credentials.get({
            publicKey: {
                challenge: crypto.getRandomValues(new Uint8Array(32)),
                allowCredentials: [{ type: 'public-key', id: b64.dec(id), transports: ['internal'] }],
                userVerification: 'required',
                timeout: 60000,
            },
        });
        return true;
    } catch (e) {
        return false;
    }
}

async function register(user) {
    const cred = await navigator.credentials.create({
        publicKey: {
            challenge: crypto.getRandomValues(new Uint8Array(32)),
            rp: { name: document.title.split('·').pop().trim() || 'App', id: location.hostname },
            user: { id: new TextEncoder().encode(String(user.id)), name: user.name, displayName: user.name },
            pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
            authenticatorSelection: { authenticatorAttachment: 'platform', userVerification: 'required', residentKey: 'discouraged' },
            timeout: 60000,
        },
    });
    store.set(b64.enc(cred.rawId));
}

if (key && overlay) {
    const msg = overlay.querySelector('[data-applock-msg]');
    const unlockBtn = overlay.querySelector('[data-applock-unlock]');
    let busy = false;

    const lock = () => {
        if (!store.get()) return;
        session.set(false);
        root.classList.add('app-locked');
    };

    const tryUnlock = async () => {
        if (busy || !root.classList.contains('app-locked')) return;
        busy = true;
        msg.textContent = 'Waiting for your fingerprint, face or device PIN…';
        const ok = await verify();
        busy = false;
        if (ok) {
            session.set(true);
            root.classList.remove('app-locked');
        } else {
            msg.textContent = 'Not verified. Tap Unlock to try again.';
        }
    };

    unlockBtn.addEventListener('click', tryUnlock);

    // Lock as soon as the app is hidden, except when we are just moving to another page of the app.
    let navigating = false;
    window.addEventListener('beforeunload', () => {
        navigating = true;
        setTimeout(() => { navigating = false; }, 1500); // unload was cancelled
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            if (!navigating) lock();
        } else if (root.classList.contains('app-locked')) {
            tryUnlock();
        }
    });
    // Restored from the back/forward cache after the app was left.
    window.addEventListener('pageshow', (e) => { if (e.persisted && !session.ok()) { lock(); tryUnlock(); } });

    if (root.classList.contains('app-locked')) tryUnlock();
}

// Profile page: turn the lock on or off.
const panel = document.querySelector('[data-applock-panel]');
if (panel && key) {
    const status = panel.querySelector('[data-applock-status]');
    const enableBtn = panel.querySelector('[data-applock-enable]');
    const disableBtn = panel.querySelector('[data-applock-disable]');
    const note = panel.querySelector('[data-applock-note]');
    const user = { id: panel.dataset.userId, name: panel.dataset.userName };

    const render = async () => {
        const on = !!store.get();
        const can = await supported();
        status.textContent = on ? 'On — this device locks as soon as you leave the app.' : 'Off';
        enableBtn.classList.toggle('hidden', on || !can);
        disableBtn.classList.toggle('hidden', !on);
        note.classList.toggle('hidden', can || on);
    };

    enableBtn.addEventListener('click', async () => {
        try {
            await register(user);
            session.set(true); // just verified; don't lock the page they are on
        } catch (e) {
            status.textContent = 'Could not set up biometrics: ' + (e.name === 'NotAllowedError' ? 'cancelled.' : e.message);
            return;
        }
        render();
    });

    disableBtn.addEventListener('click', async () => {
        // Turning the lock off needs the same proof as unlocking.
        if (!(await verify())) {
            status.textContent = 'Not verified — the lock stays on.';
            return;
        }
        store.del();
        render();
    });

    render();
}

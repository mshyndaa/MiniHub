// Notes mini program: list-page helpers and the rich-text editor.

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': csrf(),
    'X-Requested-With': 'XMLHttpRequest',
});

// Local-time display for reminders: <time data-local="short" datetime="…">
document.querySelectorAll('time[data-local]').forEach((el) => {
    const d = new Date(el.getAttribute('datetime'));
    if (!isNaN(d)) el.textContent = d.toLocaleString([], { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
});

/* ---------------------------------------------------------------- list page */

const layoutButtons = document.querySelectorAll('[data-layout]');
if (layoutButtons.length) {
    let layout = 'grid';
    try {
        layout = localStorage.getItem('notes-layout') === 'list' ? 'list' : 'grid';
    } catch (e) {
        // storage unavailable: default to grid
    }
    const apply = (value) => {
        document.documentElement.classList.toggle('notes-list', value === 'list');
        layoutButtons.forEach((b) => b.classList.toggle('layout-btn-on', b.dataset.layout === value));
    };
    apply(layout);
    layoutButtons.forEach((b) => b.addEventListener('click', () => {
        apply(b.dataset.layout);
        try {
            localStorage.setItem('notes-layout', b.dataset.layout);
        } catch (e) {
            // ignore
        }
    }));
}

document.querySelectorAll('[data-folder-rename]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const name = window.prompt('Rename folder', btn.dataset.name);
        if (name === null || name.trim() === '' || name.trim() === btn.dataset.name) return;
        const form = document.getElementById('folder-rename-form');
        form.action = btn.dataset.folderRename;
        form.elements.name.value = name.trim();
        form.submit();
    });
});

/* ------------------------------------------------------------------- editor */

const root = document.getElementById('note-editor');
if (root) initEditor(root);

function initEditor(root) {
    const $ = (sel) => root.querySelector(sel);
    const editor = $('#note-body');
    const title = $('#note-title');
    const type = $('#note-type');
    const folder = $('#note-folder');
    const tags = $('#note-tags');
    const reminder = $('#note-reminder');
    const status = $('#save-status');
    const card = $('#note-card');
    const updateUrl = root.dataset.updateUrl;

    try {
        document.execCommand('defaultParagraphSeparator', false, 'p');
    } catch (e) {
        // older browsers: Enter creates <div>, which the server turns into paragraphs
    }

    // Reminder: stored in UTC, shown in the viewer's own timezone.
    if (reminder.dataset.utc) {
        const d = new Date(reminder.dataset.utc);
        const pad = (n) => String(n).padStart(2, '0');
        reminder.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    /* ---- autosave ---- */
    let timer = null;
    let saving = false;
    let dirty = false;
    let failed = false;

    const setStatus = (text, bad = false) => {
        status.textContent = text;
        status.classList.toggle('text-rose-500', bad);
    };

    const payload = () => ({
        title: title.value,
        body: editor.innerHTML,
        type: type.value,
        folder_id: folder.value || null,
        color: root.querySelector('input[name="color"]:checked')?.value ?? 'none',
        tags: tags.value.split(',').map((t) => t.trim()).filter(Boolean),
        reminder_at: reminder.value ? new Date(reminder.value).toISOString() : null,
    });

    async function save() {
        clearTimeout(timer);
        if (saving || !dirty) return;
        saving = true;
        dirty = false;
        failed = false;
        setStatus('Saving…');
        try {
            const res = await fetch(updateUrl, { method: 'PUT', headers: jsonHeaders(), body: JSON.stringify(payload()), keepalive: true });
            if (!res.ok) throw new Error(String(res.status));
            await res.json();
            setStatus('Saved');
        } catch (e) {
            dirty = true;
            failed = true;
            setStatus('Not saved. Retrying…', true);
            timer = setTimeout(save, 4000);
        } finally {
            saving = false;
            if (dirty && !failed) save();
        }
    }

    const changed = () => {
        dirty = true;
        setStatus('Unsaved changes…');
        clearTimeout(timer);
        timer = setTimeout(save, 900);
    };

    window.addEventListener('beforeunload', (e) => {
        if (dirty || saving) {
            save();
            e.preventDefault();
            e.returnValue = '';
        }
    });
    document.addEventListener('visibilitychange', () => document.visibilityState === 'hidden' && save());
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            save();
        }
    });

    /* ---- word count / placeholder ---- */
    const words = $('#word-count');
    const readTime = $('#read-time');
    const refresh = () => {
        const text = editor.innerText.trim();
        const n = text ? text.split(/\s+/).length : 0;
        words.textContent = n;
        readTime.textContent = Math.max(1, Math.round(n / 200));
        editor.toggleAttribute('data-empty', text === '' && !editor.querySelector('li, hr'));
    };
    refresh();

    editor.addEventListener('input', (e) => {
        // A new checklist line must start unchecked, not copy the state of the line above.
        if (e.inputType === 'insertParagraph') {
            const li = closestEl(window.getSelection()?.anchorNode, 'li');
            if (li?.parentElement?.hasAttribute('data-checklist')) li.setAttribute('data-checked', 'false');
        }
        refresh();
        changed();
    });
    title.addEventListener('input', () => {
        document.title = `${title.value || 'Untitled note'} · ${document.title.split(' · ').pop()}`;
        changed();
    });
    [type, folder, reminder, tags].forEach((el) => el.addEventListener('change', changed));
    tags.addEventListener('input', changed);

    root.querySelectorAll('input[name="color"]').forEach((r) => r.addEventListener('change', () => {
        card.className = card.className.replace(/\bnc-\w+/, `nc-${r.value}`);
        changed();
    }));

    // Paste as plain text (keeps pasted junk formatting out of the note).
    editor.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
        const html = text.split(/\r?\n/).map((l) => `<p>${escapeHtml(l) || '<br>'}</p>`).join('');
        document.execCommand('insertHTML', false, html);
    });

    // Tick / untick a checklist item by clicking its box.
    editor.addEventListener('click', (e) => {
        const li = e.target.closest('li');
        if (!li || !li.parentElement.hasAttribute('data-checklist')) return;
        if (e.clientX - li.getBoundingClientRect().left > 28) return;
        li.setAttribute('data-checked', li.getAttribute('data-checked') === 'true' ? 'false' : 'true');
        changed();
    });

    /* ---- toolbar ---- */
    const block = (tag) => {
        const current = (document.queryCommandValue('formatBlock') || '').toLowerCase().replace(/[<>]/g, '');
        document.execCommand('formatBlock', false, current === tag ? 'p' : tag);
    };
    const list = (cmd, checklist = false) => {
        const inList = closestEl(window.getSelection()?.anchorNode, 'ul, ol');
        const wasChecklist = inList?.hasAttribute('data-checklist');
        if (checklist && inList && inList.tagName === 'UL' && !wasChecklist) {
            // Turn an existing bullet list into a checklist in place.
            inList.setAttribute('data-checklist', '');
            inList.querySelectorAll(':scope > li').forEach((li) => li.setAttribute('data-checked', 'false'));
            return;
        }
        document.execCommand(cmd);
        const now = closestEl(window.getSelection()?.anchorNode, 'ul, ol');
        if (now && now.tagName === 'UL') {
            if (checklist && !wasChecklist) {
                now.setAttribute('data-checklist', '');
                now.querySelectorAll(':scope > li').forEach((li) => li.setAttribute('data-checked', li.getAttribute('data-checked') === 'true' ? 'true' : 'false'));
            } else if (!checklist) {
                now.removeAttribute('data-checklist');
                now.querySelectorAll(':scope > li').forEach((li) => li.removeAttribute('data-checked'));
            }
        }
    };

    const commands = {
        bold: () => document.execCommand('bold'),
        italic: () => document.execCommand('italic'),
        underline: () => document.execCommand('underline'),
        strikeThrough: () => document.execCommand('strikeThrough'),
        h2: () => block('h2'),
        h3: () => block('h3'),
        ul: () => list('insertUnorderedList'),
        ol: () => list('insertOrderedList'),
        checklist: () => list('insertUnorderedList', true),
        quote: () => block('blockquote'),
        code: () => block('pre'),
        hr: () => document.execCommand('insertHorizontalRule'),
        clear: () => document.execCommand('removeFormat'),
        undo: () => document.execCommand('undo'),
        redo: () => document.execCommand('redo'),
        link: () => {
            const url = window.prompt('Link address (https://…)');
            if (!url) return;
            const href = /^(https?:\/\/|mailto:|tel:)/i.test(url.trim()) ? url.trim() : `https://${url.trim()}`;
            if (window.getSelection().isCollapsed) {
                document.execCommand('insertHTML', false, `<a href="${escapeHtml(href)}">${escapeHtml(url.trim())}</a>`);
            } else {
                document.execCommand('createLink', false, href);
            }
        },
    };

    // Keep the text selection when a toolbar button is pressed.
    $('#editor-bar').addEventListener('mousedown', (e) => e.target.closest('[data-cmd]') && e.preventDefault());
    $('#editor-bar').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cmd]');
        if (!btn || !commands[btn.dataset.cmd]) return;
        editor.focus();
        commands[btn.dataset.cmd]();
        refresh();
        changed();
    });

    /* ---- pin / favorite / archive ---- */
    root.querySelectorAll('[data-toggle]').forEach((btn) => btn.addEventListener('click', async () => {
        try {
            const res = await fetch(root.dataset.toggleUrl.replace('FIELD', btn.dataset.toggle), { method: 'POST', headers: jsonHeaders() });
            const { on } = await res.json();
            btn.classList.toggle('tb-on', on);
            btn.querySelector('svg')?.classList.toggle('fill-current', on && btn.dataset.toggle !== 'archive');
            setStatus({ pin: on ? 'Pinned' : 'Unpinned', favorite: on ? 'Favorited' : 'Unfavorited', archive: on ? 'Archived' : 'Unarchived' }[btn.dataset.toggle]);
        } catch (e) {
            setStatus('Could not update', true);
        }
    }));

    /* ---- export menu ---- */
    const copyBtn = $('#btn-copy');
    copyBtn.addEventListener('click', async () => {
        const label = copyBtn.querySelector('[data-label]');
        try {
            await navigator.clipboard.writeText(`${title.value}\n\n${editor.innerText}`);
            label.textContent = 'Copied!';
        } catch (e) {
            label.textContent = 'Press Ctrl+C';
        }
        setTimeout(() => (label.textContent = 'Copy to clipboard'), 1800);
    });
    document.addEventListener('click', (e) => {
        const menu = $('#export-menu');
        if (menu.open && !menu.contains(e.target)) menu.open = false;
    });

    /* ---- dialogs ---- */
    document.querySelectorAll('dialog.note-dialog').forEach((d) => {
        d.addEventListener('click', (e) => (e.target === d || e.target.closest('[data-close]')) && d.close());
    });

    // History
    const historyDialog = document.getElementById('history-dialog');
    const historyList = document.getElementById('history-list');
    $('#btn-history').addEventListener('click', async () => {
        await save();
        historyList.innerHTML = '<li class="text-sm opacity-60">Loading…</li>';
        historyDialog.showModal();
        try {
            const res = await fetch(root.dataset.versionsUrl, { headers: jsonHeaders() });
            const versions = await res.json();
            historyList.innerHTML = '';
            if (!versions.length) {
                historyList.innerHTML = '<li class="text-sm opacity-60">No earlier versions yet. They appear as you keep editing.</li>';
                return;
            }
            versions.forEach((v) => {
                const li = document.createElement('li');
                li.className = 'attach-row !items-start';
                const when = new Date(v.at).toLocaleString([], { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                li.innerHTML = `<div class="min-w-0 flex-1"><p class="truncate font-extrabold"></p><p class="text-xs font-bold opacity-60"></p><p class="mt-1 text-sm opacity-75 break-words"></p></div>
                    <form method="POST" class="shrink-0"><input type="hidden" name="_token"><button class="btn btn-soft !py-1.5 text-sm">Restore</button></form>`;
                li.querySelector('p.truncate').textContent = v.title;
                li.querySelectorAll('p')[1].textContent = `${when} · ${v.when}`;
                li.querySelectorAll('p')[2].textContent = v.preview || '(empty)';
                const form = li.querySelector('form');
                form.action = v.restore_url;
                form.elements._token.value = csrf();
                form.addEventListener('submit', (e) => !window.confirm('Restore this version? Your current text is kept as a version too.') && e.preventDefault());
                historyList.appendChild(li);
            });
        } catch (e) {
            historyList.innerHTML = '<li class="error">Could not load the history.</li>';
        }
    });

    // Share
    const shareDialog = document.getElementById('share-dialog');
    const shareOff = document.getElementById('share-off');
    const shareLive = document.getElementById('share-live');
    const shareLink = document.getElementById('share-link');
    const shareBtn = $('#btn-share');
    const showShare = (url) => {
        root.dataset.shared = url || '';
        shareOff.classList.toggle('hidden', !!url);
        shareLive.classList.toggle('hidden', !url);
        shareLink.value = url || '';
        shareBtn.classList.toggle('tb-on', !!url);
    };
    showShare(root.dataset.shared);
    shareBtn.addEventListener('click', () => shareDialog.showModal());
    const toggleShare = async () => {
        const res = await fetch(root.dataset.shareUrl, { method: 'POST', headers: jsonHeaders() });
        showShare((await res.json()).url);
    };
    document.getElementById('share-on').addEventListener('click', toggleShare);
    document.getElementById('share-off-btn').addEventListener('click', () => window.confirm('Stop sharing? The old link will stop working.') && toggleShare());
    document.getElementById('share-copy').addEventListener('click', async (e) => {
        const label = e.currentTarget.querySelector('[data-label]');
        try {
            await navigator.clipboard.writeText(shareLink.value);
            label.textContent = 'Copied!';
        } catch (err) {
            shareLink.select();
            label.textContent = 'Press Ctrl+C';
        }
        setTimeout(() => (label.textContent = 'Copy link'), 1800);
    });

    /* ---- send checklist items to To-do ---- */
    $('#btn-todo')?.addEventListener('click', async () => {
        const items = [...editor.querySelectorAll('ul[data-checklist] > li:not([data-checked="true"])')]
            .map((li) => li.textContent.trim().slice(0, 200))
            .filter(Boolean);
        if (!items.length) {
            window.alert('There are no unchecked checklist items in this note.\n\nAdd a checklist with the checklist button first.');
            return;
        }
        if (!window.confirm(`Add ${items.length} item${items.length === 1 ? '' : 's'} to your To-do list?\n\n• ${items.slice(0, 8).join('\n• ')}${items.length > 8 ? '\n…' : ''}`)) return;
        try {
            const res = await fetch(root.dataset.todosUrl, { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ items }) });
            if (!res.ok) throw new Error();
            setStatus(`Sent ${items.length} to To-do`);
        } catch (e) {
            setStatus('Could not send to To-do', true);
        }
    });

    /* ---- attachments ---- */
    const attachList = $('#attach-list');
    const attachEmpty = $('#attach-empty');
    const attachError = $('#attach-error');
    const fmtSize = (b) => (b >= 1048576 ? `${(b / 1048576).toFixed(1)} MB` : b >= 1024 ? `${Math.round(b / 1024)} KB` : `${b} B`);
    const syncEmpty = () => attachEmpty.classList.toggle('hidden', attachList.children.length > 0);

    const addRow = (a) => {
        const li = document.createElement('li');
        li.className = 'attach-row';
        li.dataset.attachment = '';
        li.dataset.delete = a.delete_url;
        li.innerHTML = `<svg class="h-5 w-5 shrink-0 text-sky-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            <a target="_blank" rel="noopener" class="min-w-0 flex-1 truncate font-bold hover:underline"></a>
            <span class="shrink-0 text-xs opacity-60"></span>
            <button type="button" class="note-act hover:!text-rose-500" data-attachment-delete title="Remove file">&times;</button>`;
        const link = li.querySelector('a');
        link.href = a.url;
        link.textContent = a.name;
        li.querySelector('span').textContent = fmtSize(a.size);
        attachList.appendChild(li);
        syncEmpty();
    };

    $('#attach-input').addEventListener('change', async (e) => {
        attachError.classList.add('hidden');
        for (const file of e.target.files) {
            const body = new FormData();
            body.append('file', file);
            try {
                const res = await fetch(root.dataset.attachUrl, { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.errors?.file?.[0] ?? data.message ?? 'Upload failed.');
                addRow(data);
            } catch (err) {
                attachError.textContent = `${file.name}: ${err.message}`;
                attachError.classList.remove('hidden');
            }
        }
        e.target.value = '';
    });

    attachList.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-attachment-delete]');
        if (!btn || !window.confirm('Remove this file from the note?')) return;
        const row = btn.closest('[data-attachment]');
        const res = await fetch(row.dataset.delete, { method: 'DELETE', headers: jsonHeaders() });
        if (res.ok) {
            row.remove();
            syncEmpty();
        }
    });
}

function closestEl(node, selector) {
    const el = node && node.nodeType === 3 ? node.parentElement : node;
    return el?.closest?.(selector) ?? null;
}

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

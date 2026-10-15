<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Note;
use App\Models\NoteVersion;
use App\Models\Tag;
use App\Support\NoteHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    private const TRASH_DAYS = 30;

    private const VERSION_GAP_MINUTES = 5;

    private const VERSION_LIMIT = 30;

    private function find(Request $request, $id, bool $withTrashed = false): Note
    {
        $query = Note::where('user_id', $request->user()->id);
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $uid = $user->id;

        // Notes sit in the trash for a while, then go for good.
        Note::onlyTrashed()->where('user_id', $uid)
            ->where('deleted_at', '<', now()->subDays(self::TRASH_DAYS))
            ->each(fn (Note $note) => $note->forceDelete());

        $view = in_array($request->query('view'), ['favorites', 'archive', 'trash'], true) ? $request->query('view') : 'all';
        $sort = in_array($request->query('sort'), ['created', 'title'], true) ? $request->query('sort') : 'updated';
        $term = trim((string) $request->query('q', ''));
        $folder = $request->query('folder');
        $tag = $request->query('tag');
        $type = array_key_exists($request->query('type'), Note::TYPES) ? $request->query('type') : null;

        $query = Note::where('user_id', $uid)->with(['tags', 'folder']);
        match ($view) {
            'trash' => $query->onlyTrashed(),
            'archive' => $query->whereNotNull('archived_at'),
            'favorites' => $query->where('favorite', true)->whereNull('archived_at'),
            default => $query->whereNull('archived_at'),
        };

        if ($folder === 'none') {
            $query->whereNull('folder_id');
        } elseif (ctype_digit((string) $folder)) {
            $query->where('folder_id', $folder);
        }
        if (ctype_digit((string) $tag)) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tag));
        }
        if ($type) {
            $query->where('type', $type);
        }
        if ($term !== '') {
            $like = '%'.addcslashes($term, '\\%_').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('plain', 'like', $like));
        }

        if ($view !== 'trash') {
            $query->orderByDesc('pinned');
        }
        match ($sort) {
            'created' => $query->orderByDesc('created_at'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('updated_at'),
        };

        $active = Note::where('user_id', $uid);

        return view('notes.index', [
            'notes' => $query->paginate(24)->withQueryString(),
            'folders' => Folder::where('user_id', $uid)->withCount(['notes' => fn ($q) => $q->whereNull('archived_at')])->orderBy('name')->get(),
            'tags' => Tag::where('user_id', $uid)->withCount(['notes' => fn ($q) => $q->whereNull('archived_at')])->orderBy('name')->get()->filter(fn ($t) => $t->notes_count > 0),
            'counts' => [
                'all' => (clone $active)->whereNull('archived_at')->count(),
                'favorites' => (clone $active)->where('favorite', true)->whereNull('archived_at')->count(),
                'archive' => (clone $active)->whereNotNull('archived_at')->count(),
                'trash' => Note::onlyTrashed()->where('user_id', $uid)->count(),
                'unfiled' => (clone $active)->whereNull('archived_at')->whereNull('folder_id')->count(),
            ],
            'filters' => compact('view', 'sort', 'term', 'folder', 'tag', 'type'),
            'trashDays' => self::TRASH_DAYS,
        ]);
    }

    public function store(Request $request)
    {
        $uid = $request->user()->id;
        $data = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(Note::TYPES))],
            'folder_id' => ['nullable', Rule::exists('folders', 'id')->where('user_id', $uid)],
            'title' => ['nullable', 'max:150'],
        ]);

        $template = Note::template($data['type'] ?? 'general');
        $note = new Note(['user_id' => $uid, 'type' => $data['type'] ?? 'general', 'folder_id' => $data['folder_id'] ?? null]);
        $note->setContent($data['title'] ?? $template['title'], $template['body']);
        $note->save();

        return redirect()->route('notes.edit', $note->id);
    }

    public function edit(Request $request, $id)
    {
        $note = $this->find($request, $id, true);
        if ($note->trashed()) {
            return redirect()->route('notes.index', ['view' => 'trash'])->with('status', 'Restore this note from the trash to edit it.');
        }

        $uid = $request->user()->id;

        return view('notes.edit', [
            'note' => $note->load(['tags', 'attachments']),
            'folders' => Folder::where('user_id', $uid)->orderBy('name')->get(),
            'allTags' => Tag::where('user_id', $uid)->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $note = $this->find($request, $id);
        $uid = $request->user()->id;

        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'max:150'],
            'body' => ['sometimes', 'nullable', 'string', 'max:2000000'],
            'type' => ['sometimes', Rule::in(array_keys(Note::TYPES))],
            'folder_id' => ['sometimes', 'nullable', Rule::exists('folders', 'id')->where('user_id', $uid)],
            'color' => ['sometimes', Rule::in(Note::COLORS)],
            'tags' => ['sometimes', 'nullable', 'array', 'max:10'],
            'tags.*' => ['nullable', 'string', 'max:30'],
            'reminder_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $contentChanged = (array_key_exists('title', $data) && (trim($data['title'] ?? '') ?: 'Untitled note') !== $note->title)
            || (array_key_exists('body', $data) && NoteHtml::clean($data['body']) !== $note->body);
        if ($contentChanged) {
            $this->snapshot($note);
        }

        // Empty strings arrive as null (ConvertEmptyStringsToNull), so check the key, not the value.
        $note->setContent(
            array_key_exists('title', $data) ? ($data['title'] ?? '') : null,
            array_key_exists('body', $data) ? ($data['body'] ?? '') : null,
        );
        foreach (['type', 'folder_id', 'color'] as $field) {
            if (array_key_exists($field, $data)) {
                $note->$field = $data[$field];
            }
        }
        if (array_key_exists('reminder_at', $data)) {
            $note->reminder_at = $data['reminder_at']
                ? Carbon::parse($data['reminder_at'])->setTimezone(config('app.timezone'))
                : null;
        }
        $note->save();

        if (array_key_exists('tags', $data)) {
            $this->syncTags($note, $data['tags'] ?? []);
        }

        if ($request->expectsJson()) {
            return response()->json(['saved_at' => now()->toIso8601String(), 'words' => $note->wordCount()]);
        }

        return back();
    }

    public function toggle(Request $request, $id, string $field)
    {
        abort_unless(in_array($field, ['pin', 'favorite', 'archive'], true), 404);
        $note = $this->find($request, $id);

        $on = match ($field) {
            'pin' => ($note->pinned = ! $note->pinned),
            'favorite' => ($note->favorite = ! $note->favorite),
            'archive' => (bool) ($note->archived_at = $note->archived_at ? null : now()),
        };
        $note->timestamps = false; // organizing a note is not editing it
        $note->save();

        if ($request->expectsJson()) {
            return response()->json(['on' => $on]);
        }

        return back()->with('status', match ($field) {
            'pin' => $on ? 'Note pinned.' : 'Note unpinned.',
            'favorite' => $on ? 'Added to favorites.' : 'Removed from favorites.',
            'archive' => $on ? 'Note archived.' : 'Note moved out of the archive.',
        });
    }

    public function duplicate(Request $request, $id)
    {
        $note = $this->find($request, $id);

        $copy = $note->replicate(['pinned', 'share_token', 'archived_at', 'reminder_at']);
        $copy->title = Str::limit($note->title, 138, '').' (copy)';
        $copy->save();
        $copy->tags()->sync($note->tags()->pluck('tags.id'));

        return redirect()->route('notes.edit', $copy->id)->with('status', 'Note duplicated.');
    }

    public function destroy(Request $request, $id)
    {
        $this->find($request, $id)->delete();

        return redirect()->route('notes.index')->with('status', 'Moved to trash. You can restore it for '.self::TRASH_DAYS.' days.');
    }

    public function restore(Request $request, $id)
    {
        $this->find($request, $id, true)->restore();

        return back()->with('status', 'Note restored.');
    }

    public function forceDelete(Request $request, $id)
    {
        $this->find($request, $id, true)->forceDelete();

        return back()->with('status', 'Note deleted for good.');
    }

    public function emptyTrash(Request $request)
    {
        Note::onlyTrashed()->where('user_id', $request->user()->id)->each(fn (Note $note) => $note->forceDelete());

        return back()->with('status', 'Trash emptied.');
    }

    public function export(Request $request, $id, string $format)
    {
        abort_unless(in_array($format, ['md', 'txt', 'pdf'], true), 404);
        $note = $this->find($request, $id);

        if ($format === 'pdf') {
            return view('notes.print', ['note' => $note->load('tags')]);
        }

        $text = $format === 'md'
            ? '# '.$note->title."\n\n".NoteHtml::toMarkdown($note->body)
            : $note->title."\n".str_repeat('=', min(mb_strlen($note->title), 60))."\n\n".NoteHtml::toText($note->body);

        return response(trim($text)."\n", 200, [
            'Content-Type' => $format === 'md' ? 'text/markdown; charset=UTF-8' : 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.(Str::slug($note->title) ?: 'note').'.'.$format.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function share(Request $request, $id)
    {
        $note = $this->find($request, $id);
        $note->share_token = $note->share_token ? null : Str::random(40);
        $note->timestamps = false;
        $note->save();

        return response()->json(['url' => $note->share_token ? route('notes.shared', $note->share_token) : null]);
    }

    public function versions(Request $request, $id)
    {
        $note = $this->find($request, $id);

        return response()->json($note->versions()->orderByDesc('id')->get()->map(fn (NoteVersion $v) => [
            'id' => $v->id,
            'title' => $v->title,
            'when' => $v->created_at->diffForHumans(),
            'at' => $v->created_at->toIso8601String(),
            'preview' => Str::limit(trim(preg_replace('/\s+/', ' ', NoteHtml::toText($v->body))), 140),
            'restore_url' => route('notes.versions.restore', [$note->id, $v->id]),
        ]));
    }

    public function restoreVersion(Request $request, $id, $versionId)
    {
        $note = $this->find($request, $id);
        $version = $note->versions()->findOrFail($versionId);

        $this->snapshot($note, force: true);
        $note->setContent($version->title, $version->body);
        $note->save();

        return redirect()->route('notes.edit', $note->id)->with('status', 'Earlier version restored.');
    }

    /** Keep a checkpoint of the note before it is overwritten (at most one every few minutes). */
    private function snapshot(Note $note, bool $force = false): void
    {
        if (trim((string) $note->plain) === '' && ! $force) {
            return;
        }
        $last = $note->versions()->latest('id')->first();
        if (! $force && $last && $last->created_at->gt(now()->subMinutes(self::VERSION_GAP_MINUTES))) {
            return;
        }

        $note->versions()->create(['title' => $note->title, 'body' => $note->body]);

        $stale = $note->versions()->orderByDesc('id')->skip(self::VERSION_LIMIT)->take(1000)->pluck('id');
        if ($stale->isNotEmpty()) {
            NoteVersion::whereIn('id', $stale)->delete();
        }
    }

    private function syncTags(Note $note, array $names): void
    {
        $ids = collect($names)
            ->map(fn ($n) => Str::lower(trim(ltrim((string) $n, '#'))))
            ->filter()
            ->unique()
            ->take(10)
            ->map(fn ($name) => Tag::firstOrCreate(['user_id' => $note->user_id, 'name' => $name])->id);

        $note->tags()->sync($ids->all());
    }
}

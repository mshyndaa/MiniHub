<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Note;
use App\Models\Todo;
use App\Models\User;
use App\Support\NoteHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $roles = ['notes']): User
    {
        $user = User::factory()->create();
        foreach ($roles as $role) {
            $user->roles()->create(['role' => $role]);
        }

        return $user;
    }

    private function note(User $user, array $attrs = []): Note
    {
        $note = new Note(['user_id' => $user->id, 'type' => 'general'] + $attrs);
        $note->setContent($attrs['title'] ?? 'A note', $attrs['body'] ?? '<p>Hello world</p>');
        $note->save();

        return $note;
    }

    /* ---------- Phase 1 ---------- */

    public function test_new_note_uses_the_template_for_its_type(): void
    {
        $u = $this->user();

        foreach (['study', 'work', 'meeting', 'general'] as $type) {
            $res = $this->actingAs($u)->post('/notes', ['type' => $type]);
            $note = Note::where('user_id', $u->id)->latest('id')->first();
            $res->assertRedirect("/notes/{$note->id}");
            $this->assertSame($type, $note->type);
        }

        $meeting = Note::where('type', 'meeting')->first();
        $this->assertStringContainsString('Action items', $meeting->body);
        $this->assertStringContainsString('data-checklist', $meeting->body);
        $this->assertStringContainsString('Action items', $meeting->plain);
        $this->assertSame('Untitled note', Note::where('type', 'general')->first()->title);
    }

    public function test_editor_page_renders_and_autosave_updates_the_note(): void
    {
        $u = $this->user();
        $note = $this->note($u);

        $this->actingAs($u)->get("/notes/{$note->id}")->assertOk()->assertSee('Hello world', false)->assertSee('Start writing', false);

        $this->actingAs($u)->putJson("/notes/{$note->id}", [
            'title' => 'Chemistry', 'body' => '<h2>Bonds</h2><p>Ionic &amp; covalent</p>',
            'type' => 'study', 'color' => 'blue',
        ])->assertOk()->assertJsonStructure(['saved_at', 'words']);

        $note->refresh();
        $this->assertSame('Chemistry', $note->title);
        $this->assertSame('study', $note->type);
        $this->assertSame('blue', $note->color);
        $this->assertStringContainsString('Ionic', $note->plain);
    }

    public function test_empty_title_becomes_untitled_and_bad_values_are_rejected(): void
    {
        $u = $this->user();
        $note = $this->note($u);

        $this->actingAs($u)->putJson("/notes/{$note->id}", ['title' => '   ', 'body' => ''])->assertOk();
        $this->assertSame('Untitled note', $note->fresh()->title);

        $this->actingAs($u)->putJson("/notes/{$note->id}", ['color' => 'neon'])->assertStatus(422);
        $this->actingAs($u)->putJson("/notes/{$note->id}", ['type' => 'secret'])->assertStatus(422);
    }

    public function test_html_is_sanitised(): void
    {
        $dirty = '<p onclick="x()">Hi <script>alert(1)</script><b>bold</b></p>'
            .'<img src=x onerror=alert(1)><iframe src="//evil"></iframe>'
            .'<a href="javascript:alert(1)">bad</a> <a href="https://ok.test" onclick="y()">ok</a>'
            .'<ul data-checklist onclick="z()"><li data-checked="true" style="x:y">done</li></ul><style>p{}</style>'
            .'<custom-tag>kept text</custom-tag>';

        $clean = NoteHtml::clean($dirty);

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('style', $clean);
        $this->assertStringContainsString('<b>bold</b>', $clean);
        $this->assertStringContainsString('href="https://ok.test"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('<ul data-checklist=""><li data-checked="true">done</li></ul>', $clean);
        $this->assertStringContainsString('kept text', $clean);
        $this->assertStringNotContainsString('custom-tag', $clean);
    }

    public function test_html_keeps_unicode(): void
    {
        $this->assertSame('<p>Café – 日本語 ✓</p>', NoteHtml::clean('<p>Café – 日本語 ✓</p>'));
    }

    public function test_saved_body_is_sanitised(): void
    {
        $u = $this->user();
        $note = $this->note($u);
        $this->actingAs($u)->putJson("/notes/{$note->id}", ['body' => '<p>ok</p><script>alert(1)</script>'])->assertOk();
        $this->assertSame('<p>ok</p>', $note->fresh()->body);
    }

    public function test_search_and_sort(): void
    {
        $u = $this->user();
        $this->note($u, ['title' => 'Zebra facts', 'body' => '<p>stripes</p>']);
        $this->note($u, ['title' => 'Apple pie', 'body' => '<p>needs cinnamon</p>']);

        $this->actingAs($u)->get('/notes?q=cinnamon')->assertOk()->assertSee('Apple pie')->assertDontSee('Zebra facts');
        $this->actingAs($u)->get('/notes?q=zebra')->assertOk()->assertSee('Zebra facts')->assertDontSee('Apple pie');
        $this->actingAs($u)->get('/notes?q=100%25')->assertOk()->assertSee('Nothing matches');
        $this->actingAs($u)->get('/notes?sort=title')->assertOk()->assertSeeInOrder(['Apple pie', 'Zebra facts']);
    }

    public function test_pinned_notes_come_first(): void
    {
        $u = $this->user();
        $this->note($u, ['title' => 'Newest note']);
        $this->note($u, ['title' => 'Old pinned note', 'pinned' => true, 'updated_at' => now()->subDays(5)]);

        $this->actingAs($u)->get('/notes')->assertSeeInOrder(['Old pinned note', 'Newest note']);
    }

    public function test_notes_are_private_to_their_owner(): void
    {
        $a = $this->user();
        $b = $this->user();
        $note = $this->note($a, ['title' => 'Secret plans']);

        $this->actingAs($b)->get('/notes')->assertDontSee('Secret plans');
        $this->actingAs($b)->get("/notes/{$note->id}")->assertNotFound();
        $this->actingAs($b)->putJson("/notes/{$note->id}", ['title' => 'x'])->assertNotFound();
        $this->actingAs($b)->post("/notes/{$note->id}/toggle/pin")->assertNotFound();
        $this->actingAs($b)->post("/notes/{$note->id}/duplicate")->assertNotFound();
        $this->actingAs($b)->get("/notes/{$note->id}/export/md")->assertNotFound();
        $this->actingAs($b)->post("/notes/{$note->id}/share")->assertNotFound();
        $this->actingAs($b)->get("/notes/{$note->id}/versions")->assertNotFound();
        $this->actingAs($b)->delete("/notes/{$note->id}")->assertNotFound();
        $this->assertSame('Secret plans', $note->fresh()->title);
    }

    public function test_notes_role_is_required(): void
    {
        $u = $this->user([]);
        $note = $this->note($u);
        $this->actingAs($u)->get("/notes/{$note->id}")->assertForbidden();
        $this->actingAs($u)->putJson("/notes/{$note->id}", ['title' => 'x'])->assertForbidden();
    }

    /* ---------- Phase 2 ---------- */

    public function test_folders_filter_and_deleting_keeps_notes(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/notes/folders', ['name' => 'Biology'])->assertRedirect();
        $folder = Folder::first();
        $in = $this->note($u, ['title' => 'Cells', 'folder_id' => $folder->id]);
        $this->note($u, ['title' => 'Loose note']);

        $this->actingAs($u)->get("/notes?folder={$folder->id}")->assertSee('Cells')->assertDontSee('Loose note');
        $this->actingAs($u)->get('/notes?folder=none')->assertSee('Loose note')->assertDontSee('Cells');

        $this->actingAs($u)->put("/notes/folders/{$folder->id}", ['name' => 'Bio 101'])->assertRedirect();
        $this->assertSame('Bio 101', $folder->fresh()->name);

        $this->actingAs($u)->delete("/notes/folders/{$folder->id}")->assertRedirect('/notes');
        $this->assertNull($in->fresh()->folder_id);
        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }

    public function test_cannot_use_or_change_another_users_folder(): void
    {
        $a = $this->user();
        $b = $this->user();
        $theirs = Folder::create(['user_id' => $b->id, 'name' => 'Theirs']);
        $note = $this->note($a);

        $this->actingAs($a)->putJson("/notes/{$note->id}", ['folder_id' => $theirs->id])->assertStatus(422);
        $this->actingAs($a)->put("/notes/folders/{$theirs->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->actingAs($a)->delete("/notes/folders/{$theirs->id}")->assertNotFound();
        $this->assertSame('Theirs', $theirs->fresh()->name);
    }

    public function test_tags_are_normalised_and_filterable(): void
    {
        $u = $this->user();
        $note = $this->note($u, ['title' => 'Tagged']);
        $this->note($u, ['title' => 'Untagged']);

        $this->actingAs($u)->putJson("/notes/{$note->id}", ['tags' => ['Exam', '#exam', '  Project-X ', '']])->assertOk();
        $this->assertSame(['exam', 'project-x'], $note->tags()->pluck('name')->all());

        $tagId = $note->tags()->where('name', 'exam')->value('tags.id');
        $this->actingAs($u)->get("/notes?tag={$tagId}")->assertSee('Tagged')->assertDontSee('Untagged');

        $this->actingAs($u)->putJson("/notes/{$note->id}", ['tags' => []])->assertOk();
        $this->assertCount(0, $note->tags);
    }

    public function test_colour_pin_favorite_archive(): void
    {
        $u = $this->user();
        $note = $this->note($u, ['title' => 'Organise me']);

        $this->actingAs($u)->postJson("/notes/{$note->id}/toggle/pin")->assertJson(['on' => true]);
        $this->actingAs($u)->postJson("/notes/{$note->id}/toggle/favorite")->assertJson(['on' => true]);
        $this->actingAs($u)->get('/notes?view=favorites')->assertSee('Organise me');

        $this->actingAs($u)->postJson("/notes/{$note->id}/toggle/archive")->assertJson(['on' => true]);
        $this->actingAs($u)->get('/notes')->assertDontSee('Organise me');
        $this->actingAs($u)->get('/notes?view=archive')->assertSee('Organise me');

        $this->actingAs($u)->postJson("/notes/{$note->id}/toggle/archive")->assertJson(['on' => false]);
        $this->actingAs($u)->postJson("/notes/{$note->id}/toggle/pin")->assertJson(['on' => false]);
        $this->actingAs($u)->post("/notes/{$note->id}/toggle/bogus")->assertNotFound();
    }

    public function test_trash_restore_and_delete_forever(): void
    {
        $u = $this->user();
        $note = $this->note($u, ['title' => 'Doomed']);

        $this->actingAs($u)->delete("/notes/{$note->id}")->assertRedirect('/notes');
        $this->actingAs($u)->get('/notes')->assertDontSee('Doomed');
        $this->actingAs($u)->get('/notes?view=trash')->assertSee('Doomed');
        $this->actingAs($u)->get("/notes/{$note->id}")->assertRedirect('/notes?view=trash');

        $this->actingAs($u)->post("/notes/{$note->id}/restore")->assertRedirect();
        $this->actingAs($u)->get('/notes')->assertSee('Doomed');

        $this->actingAs($u)->delete("/notes/{$note->id}");
        $this->actingAs($u)->delete("/notes/{$note->id}/force")->assertRedirect();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_empty_trash_and_auto_purge(): void
    {
        $u = $this->user();
        $old = $this->note($u, ['title' => 'Ancient']);
        $old->delete();
        Note::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(31)]);
        $recent = $this->note($u, ['title' => 'Recent']);
        $recent->delete();

        $this->actingAs($u)->get('/notes?view=trash')->assertSee('Recent')->assertDontSee('Ancient');
        $this->assertDatabaseMissing('notes', ['id' => $old->id]);

        $this->actingAs($u)->delete('/notes/trash')->assertRedirect();
        $this->assertDatabaseMissing('notes', ['id' => $recent->id]);
    }

    public function test_duplicate_copies_content_and_tags(): void
    {
        $u = $this->user();
        $note = $this->note($u, ['title' => 'Original', 'body' => '<p>Body</p>', 'pinned' => true]);
        $this->actingAs($u)->putJson("/notes/{$note->id}", ['tags' => ['one']]);

        $this->actingAs($u)->post("/notes/{$note->id}/duplicate")->assertRedirect();
        $copy = Note::where('title', 'Original (copy)')->first();
        $this->assertNotNull($copy);
        $this->assertSame('<p>Body</p>', $copy->body);
        $this->assertFalse($copy->pinned);
        $this->assertSame(['one'], $copy->tags()->pluck('name')->all());
    }

    /* ---------- Phase 3 ---------- */

    public function test_reminder_is_stored_in_utc(): void
    {
        $u = $this->user();
        $note = $this->note($u);

        $this->actingAs($u)->putJson("/notes/{$note->id}", ['reminder_at' => '2030-05-01T13:30:00.000Z'])->assertOk();
        $this->assertSame('2030-05-01 13:30:00', $note->fresh()->reminder_at->utc()->format('Y-m-d H:i:s'));

        $this->actingAs($u)->get('/notes')->assertSee('Due');
        $this->actingAs($u)->putJson("/notes/{$note->id}", ['reminder_at' => null])->assertOk();
        $this->assertNull($note->fresh()->reminder_at);
    }

    public function test_versions_are_checkpointed_and_restorable(): void
    {
        $u = $this->user();
        $note = $this->note($u, ['title' => 'Draft', 'body' => '<p>first draft</p>']);

        $this->actingAs($u)->putJson("/notes/{$note->id}", ['body' => '<p>second draft</p>'])->assertOk();
        $this->assertSame(1, $note->versions()->count());

        // Rapid follow-up edits do not create a version every keystroke.
        $this->actingAs($u)->putJson("/notes/{$note->id}", ['body' => '<p>third draft</p>'])->assertOk();
        $this->assertSame(1, $note->versions()->count());

        $res = $this->actingAs($u)->getJson("/notes/{$note->id}/versions")->assertOk();
        $res->assertJsonCount(1)->assertJsonPath('0.preview', 'first draft');

        $version = $note->versions()->first();
        $this->actingAs($u)->post("/notes/{$note->id}/versions/{$version->id}/restore")->assertRedirect("/notes/{$note->id}");
        $this->assertSame('<p>first draft</p>', $note->fresh()->body);
        // The text we replaced is itself kept.
        $this->assertTrue($note->versions()->where('body', '<p>third draft</p>')->exists());
    }

    public function test_version_history_is_capped(): void
    {
        $u = $this->user();
        $note = $this->note($u);
        for ($i = 0; $i < 40; $i++) {
            $this->actingAs($u)->post("/notes/{$note->id}/versions/".$note->versions()->create(['title' => "v$i", 'body' => "<p>$i</p>"])->id.'/restore');
        }
        $this->assertLessThanOrEqual(31, $note->versions()->count());
    }

    public function test_export_markdown_and_text(): void
    {
        $u = $this->user();
        $note = $this->note($u, [
            'title' => 'Plan',
            'body' => '<h2>Goals</h2><p>Be <strong>bold</strong> and <em>kind</em>.</p><ul><li>one</li><li>two</li></ul>'
                .'<ol><li>first</li></ol><ul data-checklist><li data-checked="true">done</li><li data-checked="false">todo</li></ul>'
                .'<blockquote>quoted</blockquote><p><a href="https://x.test">site</a></p>',
        ]);

        $md = $this->actingAs($u)->get("/notes/{$note->id}/export/md")->assertOk();
        $this->assertStringContainsString('attachment; filename="plan.md"', $md->headers->get('Content-Disposition'));
        $body = $md->getContent();
        foreach (['# Plan', '## Goals', '**bold**', '_kind_', '- one', '1. first', '- [x] done', '- [ ] todo', '> quoted', '[site](https://x.test)'] as $needle) {
            $this->assertStringContainsString($needle, $body);
        }

        $txt = $this->actingAs($u)->get("/notes/{$note->id}/export/txt")->assertOk()->getContent();
        foreach (['Plan', 'Goals', '• one', '[x] done', '[ ] todo'] as $needle) {
            $this->assertStringContainsString($needle, $txt);
        }
        $this->assertStringNotContainsString('**', $txt);

        $this->actingAs($u)->get("/notes/{$note->id}/export/pdf")->assertOk()->assertSee('window.print', false)->assertSee('Be <strong>bold</strong>', false);
        $this->actingAs($u)->get("/notes/{$note->id}/export/exe")->assertNotFound();
    }

    public function test_share_link_is_public_read_only_and_revocable(): void
    {
        $u = $this->user();
        $note = $this->note($u, ['title' => 'Public thoughts', 'body' => '<p>visible to all</p>']);

        $this->get('/s/'.str_repeat('a', 40))->assertNotFound();

        $url = $this->actingAs($u)->postJson("/notes/{$note->id}/share")->assertOk()->json('url');
        $this->assertNotNull($url);

        auth()->logout();
        $this->get($url)->assertOk()->assertSee('Public thoughts')->assertSee('visible to all')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->actingAs($u)->postJson("/notes/{$note->id}/share")->assertJson(['url' => null]);
        auth()->logout();
        $this->get($url)->assertNotFound();
    }

    public function test_trashed_note_is_no_longer_shared(): void
    {
        $u = $this->user();
        $note = $this->note($u);
        $url = $this->actingAs($u)->postJson("/notes/{$note->id}/share")->json('url');
        $note->delete();
        auth()->logout();
        $this->get($url)->assertNotFound();
    }

    public function test_attachments_upload_serve_and_delete(): void
    {
        Storage::fake('local');
        $u = $this->user();
        $other = $this->user();
        $note = $this->note($u);

        $res = $this->actingAs($u)->postJson("/notes/{$note->id}/attachments", ['file' => UploadedFile::fake()->image('diagram.png')])->assertCreated();
        $id = $res->json('id');
        $this->assertTrue($res->json('is_image'));
        Storage::disk('local')->assertExists($note->attachments()->first()->path);

        $this->actingAs($u)->postJson("/notes/{$note->id}/attachments", ['file' => UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf')])->assertCreated();

        $this->actingAs($u)->get("/notes/{$note->id}/attachments/{$id}")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($other)->get("/notes/{$note->id}/attachments/{$id}")->assertNotFound();
        $this->actingAs($other)->deleteJson("/notes/{$note->id}/attachments/{$id}")->assertNotFound();

        $path = $note->attachments()->find($id)->path;
        $this->actingAs($u)->deleteJson("/notes/{$note->id}/attachments/{$id}")->assertOk();
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('note_attachments', ['id' => $id]);
    }

    public function test_attachments_reject_dangerous_or_huge_files(): void
    {
        Storage::fake('local');
        $u = $this->user();
        $note = $this->note($u);

        $this->actingAs($u)->postJson("/notes/{$note->id}/attachments", ['file' => UploadedFile::fake()->create('evil.php', 1)])->assertStatus(422);
        $this->actingAs($u)->postJson("/notes/{$note->id}/attachments", ['file' => UploadedFile::fake()->create('page.html', 1, 'text/html')])->assertStatus(422);
        $this->actingAs($u)->postJson("/notes/{$note->id}/attachments", ['file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf')])->assertStatus(422);
        $this->assertSame(0, $note->attachments()->count());
    }

    public function test_deleting_a_note_for_good_removes_its_files(): void
    {
        Storage::fake('local');
        $u = $this->user();
        $note = $this->note($u);
        $this->actingAs($u)->postJson("/notes/{$note->id}/attachments", ['file' => UploadedFile::fake()->image('a.png')]);
        $path = $note->attachments()->first()->path;

        $this->actingAs($u)->delete("/notes/{$note->id}");
        Storage::disk('local')->assertExists($path);
        $this->actingAs($u)->delete("/notes/{$note->id}/force");
        Storage::disk('local')->assertMissing($path);
    }

    public function test_checklist_items_can_be_sent_to_todo(): void
    {
        $u = $this->user(['notes', 'todo']);
        $note = $this->note($u);

        $this->actingAs($u)->postJson("/notes/{$note->id}/todos", ['items' => ['Email Sam', 'Book room']])->assertOk()->assertJson(['count' => 2]);
        $this->assertSame(['Email Sam', 'Book room'], Todo::where('user_id', $u->id)->orderBy('id')->pluck('text')->all());

        $this->actingAs($u)->postJson("/notes/{$note->id}/todos", ['items' => []])->assertStatus(422);
        $this->actingAs($u)->get("/notes/{$note->id}")->assertSee('id="btn-todo"', false);
    }

    public function test_todo_integration_needs_the_todo_role(): void
    {
        $u = $this->user(['notes']);
        $note = $this->note($u);

        $this->actingAs($u)->postJson("/notes/{$note->id}/todos", ['items' => ['x']])->assertForbidden();
        $this->actingAs($u)->get("/notes/{$note->id}")->assertDontSee('id="btn-todo"', false);
        $this->assertSame(0, Todo::count());
    }

    public function test_dashboard_counts_ignore_trashed_notes(): void
    {
        $u = $this->user();
        $keep = $this->note($u);
        $gone = $this->note($u);
        $gone->delete();

        $this->assertSame(1, Note::where('user_id', $u->id)->count());
    }
}

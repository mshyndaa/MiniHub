<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->string('type', 20)->default('general');
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('color', 10)->default('none');
            $table->boolean('pinned')->default(false);
            $table->boolean('favorite')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('reminder_at')->nullable();
            $table->string('share_token', 64)->nullable()->unique();
            $table->longText('plain')->nullable();
            $table->softDeletes();
        });

        // Bodies become HTML (rich text) and can be long.
        Schema::table('notes', function (Blueprint $table) {
            $table->longText('body')->nullable()->change();
        });

        // Existing notes were plain text: wrap them as HTML and keep the text for search.
        DB::table('notes')->orderBy('id')->each(function ($note) {
            $text = (string) $note->body;
            $html = $text === '' ? '' : '<p>'.nl2br(e($text), false).'</p>';
            DB::table('notes')->where('id', $note->id)->update(['body' => $html, 'plain' => $text]);
        });

        Schema::create('note_tag', function (Blueprint $table) {
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['note_id', 'tag_id']);
        });

        Schema::create('note_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_attachments');
        Schema::dropIfExists('note_versions');
        Schema::dropIfExists('note_tag');

        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropConstrainedForeignId('folder_id');
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['type', 'color', 'pinned', 'favorite', 'archived_at', 'reminder_at', 'share_token', 'plain']);
        });

        Schema::dropIfExists('tags');
        Schema::dropIfExists('folders');
    }
};

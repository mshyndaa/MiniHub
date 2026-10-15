{{-- $a: attachment array (see NoteAttachment::toArray). Mirrored by the row builder in resources/js/notes.js. --}}
<li class="attach-row" data-attachment data-delete="{{ $a['delete_url'] }}">
    <x-icon :name="$a['is_image'] ? 'camera' : 'file-text'" class="h-5 w-5 shrink-0 text-sky-500" />
    <a href="{{ $a['url'] }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate font-bold hover:underline">{{ $a['name'] }}</a>
    <span class="shrink-0 text-xs opacity-60">{{ \Illuminate\Support\Number::fileSize($a['size']) }}</span>
    <button type="button" class="note-act hover:!text-rose-500" data-attachment-delete title="Remove file" aria-label="Remove {{ $a['name'] }}"><x-icon name="trash" class="h-4 w-4" /></button>
</li>

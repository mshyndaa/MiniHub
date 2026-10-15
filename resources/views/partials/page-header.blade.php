{{-- Usage: @include('partials.page-header', ['symbol' => 'file-text', 'title' => 'Notes', 'subtitle' => '…', 'tint' => 'yellow']) --}}
@php
    $tints = [
        'blue' => 'from-sky-300 to-sky-500 border-sky-600/30 shadow-sky-300/60',
        'pink' => 'from-pink-300 to-rose-400 border-rose-600/30 shadow-pink-300/60',
        'yellow' => 'from-yellow-200 to-amber-400 border-amber-600/30 shadow-amber-300/60',
    ];
@endphp
<div class="mb-8 flex items-center gap-4">
    <span class="page-icon border-b-4 bg-gradient-to-br {{ $tints[$tint ?? 'blue'] }}"><x-icon :name="$symbol" class="h-7 w-7" /></span>
    <div>
        <h1 class="text-3xl font-black leading-tight tracking-tight">{{ $title }}</h1>
        @isset($subtitle)<p class="text-sm font-semibold opacity-60">{{ $subtitle }}</p>@endisset
    </div>
</div>

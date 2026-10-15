{{-- Usage: @include('partials.page-header', ['symbol' => 'file-text', 'title' => 'Notes', 'subtitle' => '…', 'tint' => 'yellow']) --}}
@php
    $tints = [
        'blue' => 'from-sky-400 to-blue-500 shadow-sky-300/50',
        'pink' => 'from-pink-400 to-rose-500 shadow-pink-300/50',
        'yellow' => 'from-yellow-300 to-amber-500 shadow-amber-300/50',
    ];
@endphp
<div class="mb-8 flex items-center gap-4">
    <span class="page-icon bg-gradient-to-br {{ $tints[$tint ?? 'blue'] }}"><x-icon :name="$symbol" class="h-7 w-7" /></span>
    <div>
        <h1 class="text-3xl font-extrabold leading-tight">{{ $title }}</h1>
        @isset($subtitle)<p class="text-sm opacity-70">{{ $subtitle }}</p>@endisset
    </div>
</div>

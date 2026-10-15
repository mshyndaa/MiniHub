{{-- Shared by create and edit. Params: $menu (Menu|null), $roles, $colors --}}
@php
    $checked = $menu?->roles ?? [];
    $swatch = ['blue' => 'bg-sky-400', 'pink' => 'bg-pink-400', 'yellow' => 'bg-amber-300'];
    $current = $menu?->color ?? 'blue';
@endphp

{{-- Basics --}}
<div class="space-y-4">
    <p class="text-xs font-bold uppercase tracking-widest opacity-50">Basics</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Name</label>
            <input class="input" name="name" value="{{ $menu?->name }}" placeholder="Finance" required>
        </div>
        <div>
            <label class="label">Description</label>
            <input class="input" name="description" value="{{ $menu?->description }}" placeholder="One line shown to users">
        </div>
        <div>
            <label class="label flex items-center gap-1.5">
                Route name
                @if ($menu?->is_system)<x-icon name="lock" class="h-3.5 w-3.5 opacity-50" />@endif
            </label>
            <input class="input font-mono text-sm {{ $menu?->is_system ? 'cursor-not-allowed opacity-60' : '' }}" name="route" value="{{ $menu?->route }}" placeholder="notes.index" @if ($menu?->is_system) disabled @else required @endif>
            @if ($menu?->is_system)<p class="mt-1.5 text-xs opacity-50">Built-in menus keep their route.</p>@endif
        </div>
        <div>
            <label class="label">Order</label>
            <input class="input" type="number" min="0" name="sort_order" value="{{ $menu?->sort_order }}" placeholder="Last">
        </div>
    </div>
</div>

{{-- Appearance --}}
<div class="space-y-4">
    <p class="text-xs font-bold uppercase tracking-widest opacity-50">Appearance</p>
    <div class="grid gap-4 sm:grid-cols-[1fr_8rem_auto] sm:items-end">
        <div>
            <label class="label">Icon name</label>
            <input class="input font-mono text-sm" name="symbol" value="{{ $menu?->symbol ?? 'puzzle' }}" required>
        </div>
        <div>
            <label class="label">Emoji</label>
            <input class="input text-center text-lg" name="icon" value="{{ $menu?->icon }}" maxlength="16" placeholder="🙂">
        </div>
        <div>
            <span class="label">Color</span>
            <div class="flex h-12 items-center gap-2.5">
                @foreach ($colors as $c)
                    <label class="cursor-pointer" title="{{ ucfirst($c) }}">
                        <input type="radio" name="color" value="{{ $c }}" class="peer sr-only" @checked($current === $c)>
                        <span class="block h-8 w-8 rounded-full {{ $swatch[$c] ?? 'bg-slate-300' }} ring-2 ring-transparent ring-offset-2 ring-offset-white transition hover:scale-110 peer-checked:ring-slate-700 peer-focus-visible:ring-sky-500 dark:ring-offset-slate-800 dark:peer-checked:ring-white"></span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Access --}}
<div class="space-y-3">
    <p class="text-xs font-bold uppercase tracking-widest opacity-50">Visible to</p>
    <div class="flex flex-wrap gap-2">
        @foreach ($roles as $r)
            <label class="cursor-pointer">
                <input type="checkbox" name="roles[]" value="{{ $r }}" class="peer sr-only" @checked(in_array($r, $checked))>
                <span class="inline-flex items-center gap-1.5 rounded-full border-2 border-slate-200 bg-white/60 px-4 py-1.5 text-sm font-bold capitalize text-slate-600 transition hover:border-sky-300 peer-checked:border-sky-500 peer-checked:bg-sky-500 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-sky-400 dark:border-white/15 dark:bg-white/5 dark:text-slate-300">
                    <x-icon name="check" class="h-3.5 w-3.5" /> {{ $r }}
                </span>
            </label>
        @endforeach
    </div>
</div>

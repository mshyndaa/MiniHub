{{-- Multi-select dropdown. Backed by a real <select multiple name="roles[]"> so it submits like any form field.
     Params: $roles (array), $selected (array), $hints (array, optional) --}}
@php $hints = $hints ?? []; @endphp
<div data-ms class="relative">
    <select name="roles[]" multiple hidden>
        @foreach ($roles as $role)
            <option value="{{ $role }}" data-hint="{{ $hints[$role] ?? '' }}" @selected(in_array($role, $selected))>{{ $role }}</option>
        @endforeach
    </select>

    <div data-ms-trigger role="combobox" tabindex="0" aria-haspopup="listbox" aria-expanded="false"
         class="input flex min-h-[3rem] cursor-pointer flex-wrap items-center gap-1.5 py-2!">
        <span data-ms-tags class="contents"></span>
        <span data-ms-empty class="px-1 opacity-50">Select access…</span>
        <svg class="ml-auto h-4 w-4 shrink-0 opacity-50 transition-transform" data-ms-chevron viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l5 5 5-5"/></svg>
    </div>

    <ul data-ms-menu role="listbox" aria-multiselectable="true" hidden
        class="absolute left-0 right-0 z-30 mt-2 max-h-64 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10 dark:border-white/10 dark:bg-slate-800"></ul>
</div>

@php
    $tabs = ['admin.users.*' => ['Users', 'admin.users.index', 'users'], 'admin.roles.*' => ['Roles', 'admin.roles.index', 'key'], 'admin.menus.*' => ['Menus', 'admin.menus.index', 'layout-grid']];
@endphp
<div class="mb-6 flex flex-wrap gap-2">
    @foreach ($tabs as $pattern => [$label, $route, $symbol])
        <a href="{{ route($route) }}" class="btn {{ request()->routeIs($pattern) ? 'btn-blue' : 'btn-soft' }} !py-2 text-sm">
            <x-icon :name="$symbol" class="h-4 w-4" /> {{ $label }}
        </a>
    @endforeach
</div>

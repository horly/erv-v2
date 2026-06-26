@php
    $activeGmaoPage ??= 'dashboard';
    $visibleGmaoMenuKeys = request()->attributes->get('gmao_visible_menu_keys', \App\Support\GmaoModuleNavigation::keys());
    $canManageGmaoSettings = request()->attributes->get('can_manage_gmao_settings', $user->isAdmin() || $user->isSuperadmin());
    $canOpenGmaoMenu = fn (string $key) => $canManageGmaoSettings || in_array($key, $visibleGmaoMenuKeys, true);
    $moduleRoute = route('main.gmao.dashboard', [$company, $site]);
    $navigationGroups = [
        [
            'label' => __('main.gmao_assets'),
            'icon' => 'bi-cpu',
            'items' => [
                ['key' => 'gmao-equipment', 'label' => __('main.gmao_equipment'), 'icon' => 'bi-cpu', 'href' => route('main.gmao.equipment', [$company, $site]), 'active' => $activeGmaoPage === 'equipment'],
                ['key' => 'gmao-equipment-categories', 'label' => __('main.gmao_equipment_categories'), 'icon' => 'bi-tags', 'href' => route('main.gmao.equipment-categories', [$company, $site]), 'active' => $activeGmaoPage === 'equipment-categories'],
                ['key' => 'gmao-locations', 'label' => __('main.gmao_locations'), 'icon' => 'bi-geo-alt', 'href' => route('main.gmao.locations', [$company, $site]), 'active' => $activeGmaoPage === 'locations'],
            ],
        ],
        [
            'label' => __('main.gmao_operations'),
            'icon' => 'bi-tools',
            'items' => [
                ['key' => 'gmao-requests', 'label' => __('main.gmao_requests'), 'icon' => 'bi-exclamation-diamond', 'href' => route('main.gmao.requests', [$company, $site]), 'active' => $activeGmaoPage === 'requests'],
                ['key' => 'gmao-work-orders', 'label' => __('main.gmao_work_orders'), 'icon' => 'bi-clipboard2-check', 'href' => route('main.gmao.work-orders', [$company, $site]), 'active' => $activeGmaoPage === 'work-orders'],
                ['key' => 'gmao-preventive', 'label' => __('main.gmao_preventive'), 'icon' => 'bi-calendar2-week', 'href' => route('main.gmao.preventive', [$company, $site]), 'active' => $activeGmaoPage === 'preventive'],
                ['key' => 'gmao-maintenance-routes', 'label' => __('main.gmao_maintenance_routes'), 'icon' => 'bi-list-check', 'href' => route('main.gmao.maintenance-routes', [$company, $site]), 'active' => $activeGmaoPage === 'maintenance-routes'],
            ],
        ],
        [
            'label' => __('main.gmao_resources_store'),
            'icon' => 'bi-box-seam',
            'items' => [
                ['key' => 'gmao-technicians', 'label' => __('main.gmao_technicians'), 'icon' => 'bi-person-gear', 'href' => route('main.gmao.technicians', [$company, $site]), 'active' => $activeGmaoPage === 'technicians'],
                ['key' => 'gmao-spare-parts', 'label' => __('main.gmao_spare_parts'), 'icon' => 'bi-nut', 'href' => route('main.gmao.spare-parts', [$company, $site]), 'active' => $activeGmaoPage === 'spare-parts'],
            ],
        ],
        [
            'label' => __('main.gmao_pilotage'),
            'icon' => 'bi-bar-chart',
            'items' => [
                ['key' => 'gmao-traceability', 'label' => __('main.gmao_traceability'), 'icon' => 'bi-clock-history', 'href' => route('main.gmao.traceability', [$company, $site]), 'active' => $activeGmaoPage === 'traceability'],
                ['key' => 'gmao-reports', 'label' => __('main.gmao_reports'), 'icon' => 'bi-bar-chart', 'href' => route('main.gmao.reports', [$company, $site]), 'active' => $activeGmaoPage === 'reports'],
                ['key' => 'gmao-settings', 'label' => __('main.gmao_settings'), 'icon' => 'bi-sliders', 'href' => route('main.gmao.settings', [$company, $site]), 'active' => $activeGmaoPage === 'settings', 'visible' => $canManageGmaoSettings],
            ],
        ],
    ];
    $navigationGroups = collect($navigationGroups)
        ->map(fn (array $group) => array_merge($group, ['items' => array_values(array_filter($group['items'], fn (array $item): bool => ($item['visible'] ?? true) && $canOpenGmaoMenu($item['key'])))]))
        ->filter(fn (array $group): bool => $group['items'] !== [])
        ->values()
        ->all();
@endphp

<aside class="dashboard-sidebar accounting-sidebar gmao-sidebar">
    <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="{{ app_brand_name() }}">
        <span class="sidebar-logo"><img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}"></span>
        <span>
            <strong>{{ app_brand_short_name() }}</strong>
            <small>{{ __('main.module_gmao_short') }}</small>
        </span>
    </a>

    <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="{{ __('admin.collapse_sidebar') }}" title="{{ __('admin.collapse_sidebar') }}" data-label-collapse="{{ __('admin.collapse_sidebar') }}" data-label-expand="{{ __('admin.expand_sidebar') }}">
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
    </button>

    <nav class="sidebar-nav accounting-nav gmao-nav" aria-label="{{ __('main.gmao_navigation') }}">
        @if ($canOpenGmaoMenu('gmao-dashboard'))
            <a class="nav-link {{ $activeGmaoPage === 'dashboard' ? 'active' : '' }}" href="{{ $moduleRoute }}">
                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                {{ __('main.dashboard') }}
            </a>
        @endif

        @foreach ($navigationGroups as $group)
            <div class="sidebar-group {{ collect($group['items'])->contains(fn ($item) => $item['active'] ?? false) ? 'open' : '' }}">
                <button class="sidebar-group-toggle" type="button" title="{{ $group['label'] }}" aria-expanded="{{ collect($group['items'])->contains(fn ($item) => $item['active'] ?? false) ? 'true' : 'false' }}" data-accounting-submenu>
                    <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $group['label'] }}</span>
                    <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                </button>
                <div class="sidebar-subnav">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['href'] }}" title="{{ $item['label'] }}" class="{{ ($item['active'] ?? false) ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-tools" aria-hidden="true"></i>
        <span>{{ $site->name }}</span>
    </div>
</aside>

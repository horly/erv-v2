@php
    $activeArchivingPage ??= 'dashboard';
    $visibleArchivingMenuKeys = request()->attributes->get('archiving_visible_menu_keys', \App\Support\ArchivingModuleNavigation::keys());
    $canManageArchivingSettings = request()->attributes->get('can_manage_archiving_settings', $user->isAdmin() || $user->isSuperadmin());
    $canOpenArchivingMenu = fn (string $key) => $canManageArchivingSettings || in_array($key, $visibleArchivingMenuKeys, true);
    $moduleRoute = route('main.archiving.dashboard', [$company, $site]);
    $navigationGroups = [
        [
            'label' => __('main.archive_physical_structure'),
            'icon' => 'bi-building',
            'items' => [
                ['key' => 'archive-locations', 'label' => __('main.archive_locations'), 'icon' => 'bi-geo-alt', 'href' => route('main.archiving.locations', [$company, $site]), 'active' => $activeArchivingPage === 'locations'],
                ['key' => 'archive-containers', 'label' => __('main.archive_containers'), 'icon' => 'bi-folder2-open', 'href' => route('main.archiving.containers', [$company, $site]), 'active' => $activeArchivingPage === 'containers'],
            ],
        ],
        [
            'label' => __('main.archive_records_management'),
            'icon' => 'bi-archive',
            'items' => [
                ['key' => 'archive-records', 'label' => __('main.archive_records'), 'icon' => 'bi-file-earmark-text', 'href' => route('main.archiving.records', [$company, $site]), 'active' => $activeArchivingPage === 'records'],
                ['key' => 'archive-movements', 'label' => __('main.archive_movements'), 'icon' => 'bi-arrow-left-right', 'href' => route('main.archiving.movements', [$company, $site]), 'active' => $activeArchivingPage === 'movements'],
                ['key' => 'archive-retention', 'label' => __('main.archive_retention'), 'icon' => 'bi-hourglass-split', 'href' => route('main.archiving.retention', [$company, $site]), 'active' => $activeArchivingPage === 'retention'],
            ],
        ],
        [
            'label' => __('main.archive_governance'),
            'icon' => 'bi-shield-check',
            'items' => [
                ['key' => 'archive-traceability', 'label' => __('main.archive_traceability'), 'icon' => 'bi-clock-history', 'href' => route('main.archiving.traceability', [$company, $site]), 'active' => $activeArchivingPage === 'traceability'],
            ],
        ],
    ];
    $navigationGroups = collect($navigationGroups)
        ->map(fn (array $group) => array_merge($group, ['items' => array_values(array_filter($group['items'], fn (array $item): bool => $canOpenArchivingMenu($item['key'])))]))
        ->filter(fn (array $group): bool => $group['items'] !== [])
        ->values()
        ->all();
@endphp

<aside class="dashboard-sidebar accounting-sidebar archiving-sidebar">
    <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="{{ app_brand_name() }}">
        <span class="sidebar-logo">
            <img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}">
        </span>
        <span>
            <strong>{{ app_brand_short_name() }}</strong>
            <small>{{ __('main.module_archiving') }}</small>
        </span>
    </a>

    <button
        class="sidebar-toggle"
        type="button"
        id="sidebarToggle"
        aria-label="{{ __('admin.collapse_sidebar') }}"
        title="{{ __('admin.collapse_sidebar') }}"
        data-label-collapse="{{ __('admin.collapse_sidebar') }}"
        data-label-expand="{{ __('admin.expand_sidebar') }}"
    >
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
    </button>

    <nav class="sidebar-nav accounting-nav archiving-nav" aria-label="{{ __('main.archive_navigation') }}">
        @if ($canOpenArchivingMenu('archive-dashboard'))
            <a class="nav-link {{ $activeArchivingPage === 'dashboard' ? 'active' : '' }}" href="{{ $moduleRoute }}">
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

        @if ($canOpenArchivingMenu('archive-reports'))
            <span class="sidebar-section-title">{{ __('main.reports') }}</span>
            <a class="nav-link {{ $activeArchivingPage === 'reports' ? 'active' : '' }}" href="{{ route('main.archiving.reports', [$company, $site]) }}">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                {{ __('main.archive_reports') }}
            </a>
        @endif

        @if ($canManageArchivingSettings)
            <a class="nav-link {{ $activeArchivingPage === 'settings' ? 'active' : '' }}" href="{{ route('main.archiving.settings', [$company, $site]) }}">
                <i class="bi bi-sliders" aria-hidden="true"></i>
                {{ __('main.archive_settings') }}
            </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-archive" aria-hidden="true"></i>
        <span>{{ $site->name }}</span>
    </div>
</aside>

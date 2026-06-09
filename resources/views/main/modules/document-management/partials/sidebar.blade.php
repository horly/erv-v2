@php
    $activeDocumentManagementPage ??= 'dashboard';
    $visibleDocumentManagementMenuKeys = request()->attributes->get('document_management_visible_menu_keys', \App\Support\DocumentManagementModuleNavigation::keys());
    $canManageDocumentManagementSettings = request()->attributes->get('can_manage_document_management_settings', $user->isAdmin() || $user->isSuperadmin());
    $canOpenDocumentManagementMenu = fn (string $key) => $canManageDocumentManagementSettings || in_array($key, $visibleDocumentManagementMenuKeys, true);
    $moduleRoute = route('main.document-management.dashboard', [$company, $site]);
    $incomingRoute = route('main.document-management.incoming', [$company, $site]);
    $outgoingRoute = route('main.document-management.outgoing', [$company, $site]);
    $internalRoute = route('main.document-management.internal', [$company, $site]);
    $foldersRoute = route('main.document-management.folders', [$company, $site]);
    $assignmentsRoute = route('main.document-management.assignments', [$company, $site]);
    $traceabilityRoute = route('main.document-management.traceability', [$company, $site]);
    $validationRoute = route('main.document-management.validation-circuits', [$company, $site]);
    $reportsRoute = route('main.document-management.reports', [$company, $site]);
    $navigationGroups = [
        [
            'label' => __('main.ged_registry_office'),
            'icon' => 'bi-building-check',
            'items' => [
                ['key' => 'ged-incoming', 'label' => __('main.ged_incoming_mail'), 'icon' => 'bi-inbox', 'href' => $incomingRoute, 'active' => $activeDocumentManagementPage === 'incoming'],
                ['key' => 'ged-outgoing', 'label' => __('main.ged_outgoing_mail'), 'icon' => 'bi-send', 'href' => $outgoingRoute, 'active' => $activeDocumentManagementPage === 'outgoing'],
                ['key' => 'ged-internal', 'label' => __('main.ged_internal_documents'), 'icon' => 'bi-file-earmark-text', 'href' => $internalRoute, 'active' => $activeDocumentManagementPage === 'internal'],
            ],
        ],
        [
            'label' => __('main.ged_processing'),
            'icon' => 'bi-diagram-3',
            'items' => [
                ['key' => 'ged-assignments', 'label' => __('main.ged_assignments'), 'icon' => 'bi-person-check', 'href' => $assignmentsRoute, 'active' => $activeDocumentManagementPage === 'assignments'],
                ['key' => 'ged-validation', 'label' => __('main.ged_validation_circuits'), 'icon' => 'bi-check2-square', 'href' => $validationRoute, 'active' => $activeDocumentManagementPage === 'validation'],
                ['key' => 'ged-history', 'label' => __('main.ged_traceability'), 'icon' => 'bi-clock-history', 'href' => $traceabilityRoute, 'active' => $activeDocumentManagementPage === 'history'],
            ],
        ],
        [
            'label' => __('main.ged_classification'),
            'icon' => 'bi-folder2-open',
            'items' => [
                ['key' => 'ged-folders', 'label' => __('main.ged_folders'), 'icon' => 'bi-folder', 'href' => $foldersRoute, 'active' => $activeDocumentManagementPage === 'folders'],
            ],
        ],
    ];
    $navigationGroups = collect($navigationGroups)
        ->map(fn (array $group) => array_merge($group, ['items' => array_values(array_filter($group['items'], fn (array $item): bool => $canOpenDocumentManagementMenu($item['key'])))]))
        ->filter(fn (array $group): bool => $group['items'] !== [])
        ->values()
        ->all();
@endphp

<aside class="dashboard-sidebar accounting-sidebar document-management-sidebar">
    <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="{{ app_brand_name() }}">
        <span class="sidebar-logo">
            <img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}">
        </span>
        <span>
            <strong>{{ app_brand_short_name() }}</strong>
            <small>{{ __('main.module_document_management') }}</small>
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

    <nav class="sidebar-nav accounting-nav document-management-nav" aria-label="{{ __('main.ged_navigation') }}">
        @if ($canOpenDocumentManagementMenu('ged-dashboard'))
            <a class="nav-link {{ $activeDocumentManagementPage === 'dashboard' ? 'active' : '' }}" href="{{ $moduleRoute }}">
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

        @if ($canOpenDocumentManagementMenu('ged-reports'))
            <span class="sidebar-section-title">{{ __('main.reports') }}</span>
            <a class="nav-link {{ $activeDocumentManagementPage === 'reports' ? 'active' : '' }}" href="{{ $reportsRoute }}">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                {{ __('main.ged_reports') }}
            </a>
        @endif

        @if ($canManageDocumentManagementSettings)
            <a class="nav-link {{ $activeDocumentManagementPage === 'settings' ? 'active' : '' }}" href="{{ route('main.document-management.settings', [$company, $site]) }}">
                <i class="bi bi-sliders" aria-hidden="true"></i>
                {{ __('main.ged_settings') }}
            </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
        <span>{{ $site->name }}</span>
    </div>
</aside>

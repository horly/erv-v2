@php
    $activeHumanResourcesPage ??= 'dashboard';
    $visibleHumanResourcesMenuKeys = request()->attributes->get('human_resources_visible_menu_keys', \App\Support\HumanResourcesModuleNavigation::keys());
    $canManageHumanResourcesSettings = request()->attributes->get('can_manage_human_resources_settings', $user->isAdmin() || $user->isSuperadmin());
    $canOpenHumanResourcesMenu = fn (string $key) => $canManageHumanResourcesSettings || in_array($key, $visibleHumanResourcesMenuKeys, true);
    $moduleRoute = route('main.human-resources.dashboard', [$company, $site]);
    $navigationGroups = [
        [
            'label' => __('main.hr_people'),
            'icon' => 'bi-people',
            'items' => [
                ['key' => 'hr-employees', 'label' => __('main.hr_employees'), 'icon' => 'bi-person-lines-fill', 'href' => route('main.human-resources.employees', [$company, $site]), 'active' => $activeHumanResourcesPage === 'employees'],
                ['key' => 'hr-departments', 'label' => __('main.hr_departments'), 'icon' => 'bi-diagram-3', 'href' => route('main.human-resources.departments', [$company, $site]), 'active' => $activeHumanResourcesPage === 'departments'],
                ['key' => 'hr-documents', 'label' => __('main.hr_documents'), 'icon' => 'bi-folder2-open', 'href' => route('main.human-resources.resources', [$company, $site, 'documents']), 'active' => $activeHumanResourcesPage === 'documents'],
                ['key' => 'hr-attendance', 'label' => __('main.hr_attendance'), 'icon' => 'bi-calendar-week', 'href' => route('main.human-resources.attendance', [$company, $site]), 'active' => $activeHumanResourcesPage === 'attendance'],
            ],
        ],
        [
            'label' => __('main.hr_administration'),
            'icon' => 'bi-folder-check',
            'items' => [
                ['key' => 'hr-contracts', 'label' => __('main.hr_contracts'), 'icon' => 'bi-file-earmark-text', 'href' => route('main.human-resources.contracts', [$company, $site]), 'active' => $activeHumanResourcesPage === 'contracts'],
                ['key' => 'hr-leave', 'label' => __('main.hr_leave'), 'icon' => 'bi-calendar-check', 'href' => route('main.human-resources.leave', [$company, $site]), 'active' => $activeHumanResourcesPage === 'leave'],
                ['key' => 'hr-payroll', 'label' => __('main.hr_payroll'), 'icon' => 'bi-cash-stack', 'href' => route('main.human-resources.payroll', [$company, $site]), 'active' => $activeHumanResourcesPage === 'payroll'],
                ['key' => 'hr-salary-advances', 'label' => __('main.hr_salary_advances'), 'icon' => 'bi-wallet2', 'href' => route('main.human-resources.resources', [$company, $site, 'salary-advances']), 'active' => $activeHumanResourcesPage === 'salary-advances'],
                ['key' => 'hr-payroll-adjustments', 'label' => __('main.hr_payroll_adjustments'), 'icon' => 'bi-plus-slash-minus', 'href' => route('main.human-resources.resources', [$company, $site, 'payroll-adjustments']), 'active' => $activeHumanResourcesPage === 'payroll-adjustments'],
            ],
        ],
        [
            'label' => __('main.hr_development'),
            'icon' => 'bi-graph-up-arrow',
            'items' => [
                ['key' => 'hr-schedules', 'label' => __('main.hr_schedules'), 'icon' => 'bi-clock-history', 'href' => route('main.human-resources.resources', [$company, $site, 'schedules']), 'active' => $activeHumanResourcesPage === 'schedules'],
                ['key' => 'hr-evaluations', 'label' => __('main.hr_evaluations'), 'icon' => 'bi-clipboard-data', 'href' => route('main.human-resources.resources', [$company, $site, 'evaluations']), 'active' => $activeHumanResourcesPage === 'evaluations'],
                ['key' => 'hr-trainings', 'label' => __('main.hr_trainings'), 'icon' => 'bi-mortarboard', 'href' => route('main.human-resources.resources', [$company, $site, 'trainings']), 'active' => $activeHumanResourcesPage === 'trainings'],
            ],
        ],
        [
            'label' => __('main.hr_governance'),
            'icon' => 'bi-shield-check',
            'items' => [
                ['key' => 'hr-sanctions', 'label' => __('main.hr_sanctions'), 'icon' => 'bi-exclamation-triangle', 'href' => route('main.human-resources.resources', [$company, $site, 'sanctions']), 'active' => $activeHumanResourcesPage === 'sanctions'],
                ['key' => 'hr-recruitment', 'label' => __('main.hr_recruitment'), 'icon' => 'bi-person-plus', 'href' => route('main.human-resources.resources', [$company, $site, 'recruitment']), 'active' => $activeHumanResourcesPage === 'recruitment'],
            ],
        ],
    ];
    $navigationGroups = collect($navigationGroups)
        ->map(fn (array $group) => array_merge($group, ['items' => array_values(array_filter($group['items'], fn (array $item): bool => $canOpenHumanResourcesMenu($item['key'])))])
        )
        ->filter(fn (array $group): bool => $group['items'] !== [])
        ->values()
        ->all();
@endphp

<aside class="dashboard-sidebar accounting-sidebar human-resources-sidebar">
    <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="{{ app_brand_name() }}">
        <span class="sidebar-logo">
            <img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}">
        </span>
        <span>
            <strong>{{ app_brand_short_name() }}</strong>
            <small>{{ __('main.module_human_resources') }}</small>
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

    <nav class="sidebar-nav accounting-nav human-resources-nav" aria-label="{{ __('main.hr_navigation') }}">
        @if ($canOpenHumanResourcesMenu('hr-dashboard'))
            <a class="nav-link {{ $activeHumanResourcesPage === 'dashboard' ? 'active' : '' }}" href="{{ $moduleRoute }}">
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

        @if ($canOpenHumanResourcesMenu('hr-reports'))
            <span class="sidebar-section-title">{{ __('main.reports') }}</span>
            <a class="nav-link {{ $activeHumanResourcesPage === 'reports' ? 'active' : '' }}" href="{{ route('main.human-resources.reports', [$company, $site]) }}">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                {{ __('main.hr_reports') }}
            </a>
        @endif

        @if ($canManageHumanResourcesSettings)
            <a class="nav-link {{ $activeHumanResourcesPage === 'settings' ? 'active' : '' }}" href="{{ route('main.human-resources.settings', [$company, $site]) }}">
                <i class="bi bi-sliders" aria-hidden="true"></i>
                {{ __('main.hr_settings') }}
            </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-people" aria-hidden="true"></i>
        <span>{{ __('main.hr_foundation') }}</span>
    </div>
</aside>

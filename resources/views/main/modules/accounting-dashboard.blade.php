<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.accounting_dashboard') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $siteCurrency = $site->currency ?: 'CDF';
        $weeklyLabels = collect(range(7, 0))
            ->map(fn (int $weeksAgo) => \Carbon\CarbonImmutable::now()->subWeeks($weeksAgo)->startOfWeek());
        $monthlyLabels = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => \Carbon\CarbonImmutable::now()->subMonths($monthsAgo)->startOfMonth());
        $yearlyLabels = collect(range(4, 0))
            ->map(fn (int $yearsAgo) => \Carbon\CarbonImmutable::now()->subYears($yearsAgo)->startOfYear());
        $kpis = [
            ['label' => __('main.revenue'), 'value' => '24,8M '.$siteCurrency, 'icon' => 'bi-graph-up-arrow', 'tone' => 'blue', 'trend' => '+18%'],
            ['label' => __('main.sales_invoices'), 'value' => 128, 'icon' => 'bi-receipt', 'tone' => 'violet', 'trend' => '+12%'],
            ['label' => __('main.payments'), 'value' => 86, 'icon' => 'bi-credit-card', 'tone' => 'green', 'trend' => '+9%'],
            ['label' => __('main.receivables'), 'value' => '12,4M '.$siteCurrency, 'icon' => 'bi-arrow-down-left-circle', 'tone' => 'amber', 'trend' => null],
            ['label' => __('main.expenses'), 'value' => '8,7M '.$siteCurrency, 'icon' => 'bi-wallet2', 'tone' => 'rose', 'trend' => null],
        ];
        $accountingChartData = [
            'emptyLabel' => __('main.no_accounting_documents'),
            'labels' => [
                'revenue' => __('main.revenue'),
                'sales' => __('main.sales'),
                'expenses' => __('main.expenses'),
                'customers' => __('main.customers'),
                'suppliers' => __('main.suppliers'),
                'prospects' => __('main.prospects'),
                'creditors' => __('main.creditors'),
                'debtors' => __('main.debtors'),
                'stock' => __('main.stock'),
                'services' => __('main.services'),
                'documents' => __('main.documents'),
                'cashflow' => __('main.cashflow'),
                'receivables' => __('main.receivables'),
                'debts' => __('main.debts'),
                'schedule' => __('main.schedule'),
            ],
            'periods' => [
                'week' => [
                    'labels' => $weeklyLabels->map(fn (\Carbon\CarbonImmutable $date) => $date->translatedFormat('d M'))->all(),
                    'revenue' => [0, 0, 0, 0, 8, 18, 26, 14],
                    'sales' => [0, 0, 0, 0, 5, 13, 20, 9],
                    'expenses' => [0, 0, 0, 0, 3, 8, 12, 7],
                    'receivables' => [0, 0, 0, 0, 4, 9, 16, 11],
                    'debts' => [0, 0, 0, 0, 2, 5, 8, 6],
                ],
                'month' => [
                    'labels' => $monthlyLabels->map(fn (\Carbon\CarbonImmutable $date) => $date->translatedFormat('M Y'))->all(),
                    'revenue' => [0, 0, 0, 9, 24, 38],
                    'sales' => [0, 0, 0, 6, 17, 27],
                    'expenses' => [0, 0, 0, 4, 12, 18],
                    'receivables' => [0, 0, 0, 5, 13, 21],
                    'debts' => [0, 0, 0, 2, 8, 12],
                ],
                'year' => [
                    'labels' => $yearlyLabels->map(fn (\Carbon\CarbonImmutable $date) => $date->format('Y'))->all(),
                    'revenue' => [0, 0, 6, 28, 68],
                    'sales' => [0, 0, 4, 19, 51],
                    'expenses' => [0, 0, 3, 12, 34],
                    'receivables' => [0, 0, 2, 14, 32],
                    'debts' => [0, 0, 1, 9, 18],
                ],
            ],
            'contacts' => [
                'labels' => [__('main.customers'), __('main.suppliers'), __('main.prospects'), __('main.creditors'), __('main.debtors')],
                'series' => [42, 18, 27, 9, 14],
            ],
            'stockServices' => [
                'labels' => [__('main.items'), __('main.categories'), __('main.services'), __('main.price_list')],
                'series' => [126, 24, 58, 31],
            ],
            'documents' => [
                'labels' => [__('main.sales_invoices'), __('main.proforma_invoices'), __('main.delivery_notes'), __('main.cash_register'), __('main.purchase_orders')],
                'series' => [128, 54, 71, 39, 46],
            ],
        ];
        $scheduleSummary = [
            ['label' => __('main.clients_owe_me'), 'amount' => '12,4M '.$siteCurrency, 'tone' => 'green', 'icon' => 'bi-arrow-down-left-circle'],
            ['label' => __('main.i_owe_suppliers'), 'amount' => '7,8M '.$siteCurrency, 'tone' => 'rose', 'icon' => 'bi-arrow-up-right-circle'],
        ];
        $scheduleItems = [
            ['label' => __('main.customer_receivable'), 'subject' => 'EXAD SARL', 'amount' => '4,2M '.$siteCurrency, 'date' => now()->addDays(2)->translatedFormat('d M'), 'tone' => 'green'],
            ['label' => __('main.customer_receivable'), 'subject' => 'Prestervice', 'amount' => '2,9M '.$siteCurrency, 'date' => now()->addDays(9)->translatedFormat('d M'), 'tone' => 'blue'],
            ['label' => __('main.supplier_debt'), 'subject' => 'Fournisseur IT', 'amount' => '1,6M '.$siteCurrency, 'date' => now()->addDays(5)->translatedFormat('d M'), 'tone' => 'amber'],
            ['label' => __('main.supplier_debt'), 'subject' => 'Logistique Kin', 'amount' => '980K '.$siteCurrency, 'date' => now()->addDays(13)->translatedFormat('d M'), 'tone' => 'rose'],
        ];
        $navigationGroups = [
            [
                'label' => __('main.contacts'),
                'icon' => 'bi-person-lines-fill',
                'items' => [
                    ['label' => __('main.customers'), 'icon' => 'bi-person-check'],
                    ['label' => __('main.suppliers'), 'icon' => 'bi-truck'],
                    ['label' => __('main.prospects'), 'icon' => 'bi-person-plus'],
                    ['label' => __('main.creditors'), 'icon' => 'bi-arrow-up-right-circle'],
                    ['label' => __('main.debtors'), 'icon' => 'bi-arrow-down-left-circle'],
                    ['label' => __('main.partners'), 'icon' => 'bi-diagram-3'],
                    ['label' => __('main.sales_representatives'), 'icon' => 'bi-briefcase'],
                ],
            ],
            [
                'label' => __('main.stock'),
                'icon' => 'bi-box-seam',
                'items' => [
                    ['label' => __('main.items'), 'icon' => 'bi-box'],
                    ['label' => __('main.subcategories'), 'icon' => 'bi-tags'],
                    ['label' => __('main.categories'), 'icon' => 'bi-folder'],
                ],
            ],
            [
                'label' => __('main.services'),
                'icon' => 'bi-grid-1x2',
                'items' => [
                    ['label' => __('main.price_list'), 'icon' => 'bi-card-list'],
                    ['label' => __('main.subcategories'), 'icon' => 'bi-tags'],
                    ['label' => __('main.categories'), 'icon' => 'bi-folder'],
                ],
            ],
        ];
        $salesItems = [
            ['label' => __('main.sales_invoices'), 'icon' => 'bi-receipt'],
            ['label' => __('main.proforma_invoices'), 'icon' => 'bi-file-earmark-richtext'],
            ['label' => __('main.delivery_notes'), 'icon' => 'bi-box-arrow-up'],
            ['label' => __('main.cash_register'), 'icon' => 'bi-calculator'],
            ['label' => __('main.other_income'), 'icon' => 'bi-plus-circle'],
        ];
        $expenseItems = [
            ['label' => __('main.purchases'), 'icon' => 'bi-bag-check'],
            ['label' => __('main.purchase_orders'), 'icon' => 'bi-clipboard-check'],
            ['label' => __('main.expenses'), 'icon' => 'bi-wallet2'],
        ];
        $otherItems = [
            ['label' => __('main.debts'), 'icon' => 'bi-arrow-up-right'],
            ['label' => __('main.receivables'), 'icon' => 'bi-arrow-down-left'],
            ['label' => __('main.taxes'), 'icon' => 'bi-percent'],
            ['label' => __('main.cashflow'), 'icon' => 'bi-activity'],
            ['label' => __('main.bank_reconciliation'), 'icon' => 'bi-bank'],
            ['label' => __('main.payment_reminders'), 'icon' => 'bi-bell'],
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        <aside class="dashboard-sidebar accounting-sidebar">
            <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="EXAD ERP">
                <span class="sidebar-logo">
                    <img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services">
                </span>
                <span>
                    <strong>EXAD ERP</strong>
                    <small>{{ __('main.accounting_dashboard') }}</small>
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

            <nav class="sidebar-nav accounting-nav" aria-label="{{ __('main.accounting_navigation') }}">
                <a class="nav-link active" href="{{ $moduleRoute }}">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    {{ __('main.dashboard') }}
                </a>

                @foreach ($navigationGroups as $group)
                    <div class="sidebar-group">
                        <button class="sidebar-group-toggle" type="button" title="{{ $group['label'] }}" aria-expanded="false" data-accounting-submenu>
                            <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $group['label'] }}</span>
                            <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-subnav">
                            @foreach ($group['items'] as $item)
                                <a href="#" title="{{ $item['label'] }}">
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <a class="nav-link" href="#">
                    <i class="bi bi-currency-exchange" aria-hidden="true"></i>
                    {{ __('main.currencies') }}
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                    {{ __('main.payment_methods') }}
                </a>

                <span class="sidebar-section-title">{{ __('main.billing') }}</span>

                <div class="sidebar-group">
                    <button class="sidebar-group-toggle" type="button" title="{{ __('main.sales') }}" aria-expanded="false" data-accounting-submenu>
                        <i class="bi bi-cart-check" aria-hidden="true"></i>
                        <span>{{ __('main.sales') }}</span>
                        <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="sidebar-subnav">
                        @foreach ($salesItems as $item)
                            <a href="#" title="{{ $item['label'] }}">
                                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="sidebar-group">
                    <button class="sidebar-group-toggle" type="button" title="{{ __('main.expenses_group') }}" aria-expanded="false" data-accounting-submenu>
                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                        <span>{{ __('main.expenses_group') }}</span>
                        <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="sidebar-subnav">
                        @foreach ($expenseItems as $item)
                            <a href="#" title="{{ $item['label'] }}">
                                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <span class="sidebar-section-title">{{ __('main.other') }}</span>

                @foreach ($otherItems as $item)
                    <a class="nav-link" href="#">
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <a class="nav-link" href="#">
                    <i class="bi bi-check2-square" aria-hidden="true"></i>
                    {{ __('main.tasks') }}
                </a>

                <a class="nav-link" href="#">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                    {{ __('main.reports') }}
                </a>

                <a class="nav-link" href="#">
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                    {{ __('main.module_settings') }}
                </a>
            </nav>

            <div class="sidebar-footer">
                <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                <span>{{ $site->name }}</span>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.accounting_dashboard') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                <div class="header-actions">
                    <button class="icon-button" type="button" id="themeButton" aria-label="{{ __('auth.theme_dark') }}" title="{{ __('auth.theme_dark') }}">
                        <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
                    </button>
                    <div class="language-menu">
                        <button class="language-button" type="button" id="languageButton" aria-label="{{ __('auth.language_switch') }}" aria-expanded="false" aria-controls="languageDropdown" title="{{ __('auth.language_switch') }}">
                            <i class="bi bi-globe2" aria-hidden="true"></i>
                            <span>{{ strtoupper($currentLocale) }}</span>
                            <i class="bi bi-chevron-down language-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="language-dropdown" id="languageDropdown" aria-labelledby="languageButton">
                            <a class="language-option {{ $currentLocale === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}">
                                <span class="language-code">FR</span>
                                <span class="language-name">{{ __('auth.language_fr') }}</span>
                                @if ($currentLocale === 'fr')
                                    <i class="bi bi-check-lg language-check" aria-hidden="true"></i>
                                @endif
                            </a>
                            <a class="language-option {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">
                                <span class="language-code">EN</span>
                                <span class="language-name">{{ __('auth.language_en') }}</span>
                                @if ($currentLocale === 'en')
                                    <i class="bi bi-check-lg language-check" aria-hidden="true"></i>
                                @endif
                            </a>
                        </div>
                    </div>
                    <div class="profile-menu">
                        <button class="profile-button" type="button" id="profileButton" aria-expanded="false" aria-controls="profileDropdown">
                            @include('partials.user-avatar', ['avatarUser' => $user])
                            <span class="profile-name">{{ $user->name }}</span>
                            <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                            <div class="profile-summary">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                                <em>{{ $user->role === 'admin' ? __('main.admin_badge') : strtoupper($user->role) }}</em>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="profile-link">
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                                {{ __('main.profile') }}
                            </a>
                            @if ($user->isAdmin())
                                <a href="{{ route('main.users') }}" class="profile-link">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    {{ __('main.users') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="profile-link logout-link" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    {{ __('main.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <section class="dashboard-content module-dashboard-page">
                <a class="back-link" href="{{ route('main.companies.sites.show', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ $site->name }}
                </a>

                <section class="module-heading">
                    <span class="module-heading-icon {{ $moduleMeta['class'] }}">
                        <i class="bi {{ $moduleMeta['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('main.accounting_dashboard') }}</h2>
                        <p>{{ $moduleMeta['description'] }}</p>
                    </div>
                </section>

                <section class="dashboard-content module-dashboard-content accounting-dashboard-content">
                    <div class="period-tabs" aria-label="{{ __('admin.period') }}">
                        <button type="button" data-accounting-period="week">{{ __('admin.week') }}</button>
                        <button type="button" class="active" data-accounting-period="month">{{ __('admin.month') }}</button>
                        <button type="button" data-accounting-period="year">{{ __('admin.year') }}</button>
                    </div>

                    <section class="kpi-grid module-kpi-grid" aria-label="{{ __('admin.indicators') }}">
                        @foreach ($kpis as $kpi)
                            <article class="kpi-card kpi-{{ $kpi['tone'] }}">
                                <div class="kpi-icon">
                                    <i class="bi {{ $kpi['icon'] }}" aria-hidden="true"></i>
                                </div>
                                @if ($kpi['trend'])
                                    <span class="kpi-trend">
                                        <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                        {{ $kpi['trend'] }}
                                    </span>
                                @endif
                                <strong>{{ $kpi['value'] }}</strong>
                                <span>{{ $kpi['label'] }}</span>
                            </article>
                        @endforeach
                    </section>

                    <section class="dashboard-grid accounting-dashboard-grid">
                        <article class="dashboard-panel panel-wide">
                            <h2>{{ __('main.revenue_expenses_evolution') }}</h2>
                            <div class="apex-chart accounting-chart-large" id="accountingRevenueChart" aria-label="{{ __('main.revenue_expenses_evolution') }}"></div>
                        </article>

                        <article class="dashboard-panel">
                            <h2>{{ __('main.contacts_distribution') }}</h2>
                            <div class="apex-chart donut-chart accounting-chart" id="accountingContactsChart" aria-label="{{ __('main.contacts_distribution') }}"></div>
                        </article>

                        <article class="dashboard-panel panel-wide accounting-documents-panel">
                            <h2>{{ __('main.documents_flow') }}</h2>
                            <div class="apex-chart accounting-chart" id="accountingDocumentsChart" aria-label="{{ __('main.documents_flow') }}"></div>
                        </article>

                        <article class="dashboard-panel accounting-schedule-panel">
                            <h2>{{ __('main.schedule') }}</h2>
                            <div class="accounting-schedule-summary">
                                @foreach ($scheduleSummary as $summary)
                                    <div class="schedule-summary-card schedule-{{ $summary['tone'] }}">
                                        <i class="bi {{ $summary['icon'] }}" aria-hidden="true"></i>
                                        <span>{{ $summary['label'] }}</span>
                                        <strong>{{ $summary['amount'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                            <div class="accounting-schedule-list">
                                @foreach ($scheduleItems as $item)
                                    <div class="accounting-schedule-item schedule-{{ $item['tone'] }}">
                                        <span class="schedule-dot" aria-hidden="true"></span>
                                        <div>
                                            <strong>{{ $item['label'] }}</strong>
                                            <small>{{ $item['subject'] }} · {{ $item['date'] }}</small>
                                        </div>
                                        <em>{{ $item['amount'] }}</em>
                                    </div>
                                @endforeach
                            </div>
                        </article>

                        <article class="dashboard-panel">
                            <h2>{{ __('main.stock_services_activity') }}</h2>
                            <div class="apex-chart accounting-chart" id="accountingStockServicesChart" aria-label="{{ __('main.stock_services_activity') }}"></div>
                        </article>

                        <article class="dashboard-panel panel-wide">
                            <h2>{{ __('main.cashflow_overview') }}</h2>
                            <div class="apex-chart accounting-chart-large" id="accountingCashflowChart" aria-label="{{ __('main.cashflow_overview') }}"></div>
                        </article>
                    </section>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script type="application/json" id="accountingDashboardData">@json($accountingChartData)</script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-dashboard.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-dashboard.js')) !!}</script>
</body>
</html>

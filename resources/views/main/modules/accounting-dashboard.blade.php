<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.accounting_dashboard') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $siteCurrency = $site->currency ?: 'CDF';
        $clientStats = array_merge([
            'total' => 0,
            'individuals' => 0,
            'companies' => 0,
            'contacts' => 0,
            'recent' => collect(),
        ], $clientStats ?? []);
        $supplierStats = array_merge([
            'total' => 0,
            'contacts' => 0,
        ], $supplierStats ?? []);
        $prospectStats = array_merge([
            'total' => 0,
            'contacts' => 0,
        ], $prospectStats ?? []);
        $creditorStats = array_merge([
            'total' => 0,
            'balance_due' => 0,
            'urgent' => 0,
        ], $creditorStats ?? []);
        $creditorBalance = number_format((float) $creditorStats['balance_due'], 0, ',', ' ');
        $debtorStats = array_merge([
            'total' => 0,
            'balance_receivable' => 0,
        ], $debtorStats ?? []);
        $debtorBalance = number_format((float) $debtorStats['balance_receivable'], 0, ',', ' ');
        $partnerStats = array_merge([
            'total' => 0,
            'active' => 0,
        ], $partnerStats ?? []);
        $salesRepresentativeStats = array_merge([
            'total' => 0,
            'active' => 0,
        ], $salesRepresentativeStats ?? []);
        $accountingDashboard = array_merge([
            'kpis' => [],
            'chartData' => [],
            'operations' => [],
            'scheduleSummary' => [],
            'scheduleItems' => [],
        ], $accountingDashboard ?? []);
        $fallbackChartData = [
            'emptyLabel' => __('main.no_accounting_documents'),
            'emptyClientsLabel' => __('main.no_clients'),
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
                'week' => ['labels' => [], 'revenue' => [], 'sales' => [], 'expenses' => [], 'receivables' => [], 'debts' => []],
                'month' => ['labels' => [], 'revenue' => [], 'sales' => [], 'expenses' => [], 'receivables' => [], 'debts' => []],
                'year' => ['labels' => [], 'revenue' => [], 'sales' => [], 'expenses' => [], 'receivables' => [], 'debts' => []],
            ],
            'contacts' => [
                'labels' => [__('main.prospects'), __('main.customers'), __('main.suppliers'), __('main.partners'), __('main.sales_representatives'), __('main.client_contacts'), __('main.supplier_contacts')],
                'series' => [$prospectStats['total'], $clientStats['total'], $supplierStats['total'], $partnerStats['total'], $salesRepresentativeStats['total'], $clientStats['contacts'], $supplierStats['contacts']],
            ],
            'stockServices' => [
                'labels' => [__('main.items'), __('main.categories'), __('main.services'), __('main.price_list')],
                'series' => [0, 0, 0, 0],
            ],
            'documents' => [
                'labels' => [__('main.sales_invoices'), __('main.proforma_invoices'), __('main.customer_orders'), __('main.delivery_notes'), __('main.purchase_orders'), __('main.credit_notes')],
                'series' => [0, 0, 0, 0, 0, 0],
            ],
        ];
        $kpis = $accountingDashboard['kpis'] ?: [
            ['label' => __('main.revenue'), 'value' => '0,00 '.$siteCurrency, 'icon' => 'bi-graph-up-arrow', 'tone' => 'blue', 'trend' => null],
            ['label' => __('main.sales_invoices'), 'value' => 0, 'icon' => 'bi-receipt', 'tone' => 'violet', 'trend' => null],
            ['label' => __('main.customers'), 'value' => $clientStats['total'], 'icon' => 'bi-person-check', 'tone' => 'green', 'trend' => null],
            ['label' => __('main.receivables'), 'value' => $debtorBalance.' '.$siteCurrency, 'icon' => 'bi-arrow-down-left-circle', 'tone' => 'amber', 'trend' => null],
            ['label' => __('main.expenses'), 'value' => '0,00 '.$siteCurrency, 'icon' => 'bi-wallet2', 'tone' => 'rose', 'trend' => null],
        ];
        $accountingChartData = array_replace_recursive($fallbackChartData, $accountingDashboard['chartData'] ?: []);
        $scheduleSummary = $accountingDashboard['scheduleSummary'] ?: [
            ['label' => __('main.clients_owe_me'), 'amount' => $debtorBalance.' '.$siteCurrency, 'tone' => 'green', 'icon' => 'bi-arrow-down-left-circle'],
            ['label' => __('main.i_owe_suppliers'), 'amount' => $creditorBalance.' '.$siteCurrency, 'tone' => 'rose', 'icon' => 'bi-arrow-up-right-circle'],
        ];
        $scheduleItems = $accountingDashboard['scheduleItems'] ?: [];
        $operations = $accountingDashboard['operations'] ?: [];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'dashboard'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.accounting_dashboard') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
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

                    @if ($operations !== [])
                        <section class="accounting-operations-grid" aria-label="{{ __('main.accounting_operations_overview') }}">
                            @foreach ($operations as $operation)
                                @php($operationUrl = isset($operation['route']) ? route($operation['route'], [$company, $site]) : null)
                                <article class="accounting-operation-card operation-{{ $operation['tone'] ?? 'blue' }}">
                                    <div class="operation-icon">
                                        <i class="bi {{ $operation['icon'] }}" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <span>{{ $operation['label'] }}</span>
                                        <strong>{{ $operation['value'] }}</strong>
                                        <small>{{ $operation['meta'] }}</small>
                                    </div>
                                    @if ($operationUrl)
                                        <a href="{{ $operationUrl }}" aria-label="{{ $operation['label'] }}">
                                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </article>
                            @endforeach
                        </section>
                    @endif

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

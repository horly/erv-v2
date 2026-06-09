<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.cashflow') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $treasuryRoute = route('main.accounting.treasury', [$company, $site]);
        $totalRecords = $movements->total();
        $currency = $filters['currency'];
        $amount = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$currency;
        $netFlow = round((float) $metrics['inflows'] - (float) $metrics['outflows'], 2);
        $flowWidgets = [
            ['label' => __('main.treasury_inflows'), 'value' => $metrics['inflows'], 'icon' => 'bi-arrow-down-left-circle', 'tone' => 'inflow'],
            ['label' => __('main.treasury_outflows'), 'value' => $metrics['outflows'], 'icon' => 'bi-arrow-up-right-circle', 'tone' => 'outflow'],
            ['label' => __('main.receivables'), 'value' => $metrics['receivables'], 'icon' => 'bi-cash-coin', 'tone' => 'receivable'],
            ['label' => __('main.debts'), 'value' => $metrics['debts'], 'icon' => 'bi-receipt-cutoff', 'tone' => 'debt'],
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'treasury'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.cashflow')])

            <section class="dashboard-content module-dashboard-page accounting-dashboard-content treasury-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading treasury-heading">
                    <div>
                        <h1>{{ __('main.cashflow') }}</h1>
                        <p>{{ __('main.treasury_subtitle') }}</p>
                    </div>
                    <form method="GET" action="{{ $treasuryRoute }}" class="treasury-currency-filter">
                        <label class="form-label" for="treasuryCurrency">{{ __('main.treasury_currency_view') }}</label>
                        <select id="treasuryCurrency" name="currency" class="form-select" onchange="this.form.submit()">
                            @foreach ($currencyOptions as $code => $label)
                                <option value="{{ $code }}" @selected($currency === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </section>

                <nav class="period-tabs treasury-period-tabs" aria-label="{{ __('admin.period') }}">
                    <button type="button" data-treasury-period="week">{{ __('admin.week') }}</button>
                    <button type="button" class="active" data-treasury-period="month">{{ __('admin.month') }}</button>
                    <button type="button" data-treasury-period="year">{{ __('admin.year') }}</button>
                </nav>

                <section class="treasury-summary-grid" aria-label="{{ __('main.treasury_overview') }}">
                    <article class="treasury-hero-card">
                        <div class="treasury-card-head">
                            <span class="treasury-widget-icon treasury-widget-icon-primary"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                            <span class="treasury-currency-chip">{{ $currency }}</span>
                        </div>
                        <p class="treasury-widget-label">{{ __('main.treasury_available_balance') }}</p>
                        <strong class="treasury-hero-amount">{{ $amount($metrics['balance']) }}</strong>
                        <p class="treasury-widget-description">{{ __('main.treasury_available_caption') }}</p>
                        <div class="treasury-net-position">
                            <span>{{ __('main.treasury_net_flow') }}</span>
                            <strong class="{{ $netFlow < 0 ? 'negative' : 'positive' }}">{{ $amount($netFlow) }}</strong>
                        </div>
                    </article>

                    <div class="treasury-flow-widgets">
                        @foreach ($flowWidgets as $widget)
                            <article class="treasury-stat-card treasury-stat-{{ $widget['tone'] }}">
                                <div class="treasury-stat-heading">
                                    <span class="treasury-widget-icon"><i class="bi {{ $widget['icon'] }}" aria-hidden="true"></i></span>
                                    <p class="treasury-widget-label">{{ $widget['label'] }}</p>
                                </div>
                                <strong>{{ $amount($widget['value']) }}</strong>
                            </article>
                        @endforeach
                    </div>

                    <article class="treasury-projection-card">
                        <div class="treasury-card-head">
                            <span class="treasury-widget-icon treasury-widget-icon-projection"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                            <span class="treasury-projection-pill">{{ __('main.treasury_forecast') }}</span>
                        </div>
                        <p class="treasury-widget-label">{{ __('main.treasury_projected_balance') }}</p>
                        <strong class="treasury-projection-amount">{{ $amount($metrics['projected_balance']) }}</strong>
                        <p class="treasury-widget-description">{{ __('main.treasury_projected_caption') }}</p>
                        <div class="treasury-projection-lines">
                            <span><i class="bi bi-plus-circle" aria-hidden="true"></i>{{ __('main.receivables') }} <strong>{{ $amount($metrics['receivables']) }}</strong></span>
                            <span><i class="bi bi-dash-circle" aria-hidden="true"></i>{{ __('main.debts') }} <strong>{{ $amount($metrics['debts']) }}</strong></span>
                        </div>
                    </article>
                </section>

                <section class="dashboard-grid accounting-dashboard-grid treasury-dashboard-grid">
                    <article class="dashboard-panel panel-wide">
                        <h2>{{ __('main.treasury_flow_evolution') }}</h2>
                        <div class="accounting-chart-large" id="treasuryFlowChart"></div>
                    </article>
                    <article class="dashboard-panel">
                        <h2>{{ __('main.treasury_account_balances') }}</h2>
                        <div class="accounting-chart" id="treasuryBalanceChart"></div>
                    </article>
                    <article class="dashboard-panel">
                        <h2>{{ __('main.treasury_forecast') }}</h2>
                        <div class="accounting-chart" id="treasuryForecastChart"></div>
                    </article>
                </section>

                <div class="modal-total-strip treasury-notice">
                    <span><i class="bi bi-info-circle" aria-hidden="true"></i> {{ __('main.treasury_forecast_notice') }}</span>
                </div>

                <section class="page-heading treasury-movement-heading">
                    <div>
                        <h2>{{ __('main.treasury_movements') }}</h2>
                        <p>{{ __('main.treasury_movements_subtitle') }}</p>
                    </div>
                </section>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $treasuryRoute }}" class="receipt-filter-form">
                        <input type="hidden" name="currency" value="{{ $currency }}">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-xl-2">
                                <label for="treasuryDirectionFilter" class="form-label">{{ __('main.direction') }}</label>
                                <select id="treasuryDirectionFilter" name="direction" class="form-select">
                                    <option value="">{{ __('main.treasury_all_directions') }}</option>
                                    @foreach ($movementDirectionLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['direction'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label for="treasuryTypeFilter" class="form-label">{{ __('main.type') }}</label>
                                <select id="treasuryTypeFilter" name="movement_type" class="form-select">
                                    <option value="">{{ __('main.treasury_all_types') }}</option>
                                    @foreach ($movementTypeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['movementType'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label for="treasuryMethodFilter" class="form-label">{{ __('main.payment_method') }}</label>
                                <select id="treasuryMethodFilter" name="payment_method_id" class="form-select">
                                    <option value="0">{{ __('main.all_payment_methods') }}</option>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected($filters['paymentMethodId'] === (int) $paymentMethod->id)>{{ $paymentMethod->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-2">
                                <label for="treasuryDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="treasuryDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['dateFrom'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-xl-2">
                                <label for="treasuryDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="treasuryDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['dateTo'] }}">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 receipt-filter-actions">
                                <a class="modal-cancel" href="{{ $treasuryRoute }}?currency={{ $currency }}">{{ __('main.reset_filters') }}</a>
                                <button class="modal-submit" type="submit">{{ __('main.apply_filters') }}</button>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $movements->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table treasury-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.treasury_source') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.treasury_inflows') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.treasury_outflows') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td>{{ ($movements->firstItem() ?? 1) + $loop->index }}</td>
                                        <td data-sort-value="{{ optional($movement->movement_date)->format('Y-m-d') }}">{{ optional($movement->movement_date)->format('d/m/Y') }}</td>
                                        <td>
                                            <strong>{{ $movement->source_reference ?: $movement->reference }}</strong>
                                            <small class="d-block text-muted">{{ $movement->label }}</small>
                                        </td>
                                        <td>{{ $movementTypeLabels[$movement->movement_type] ?? $movement->movement_type }}</td>
                                        <td>{{ $movement->paymentMethod?->name ?? '-' }}</td>
                                        <td class="amount-cell text-end treasury-inflow" data-sort-value="{{ $movement->direction === \App\Models\AccountingTreasuryMovement::DIRECTION_INFLOW ? $movement->amount : 0 }}">
                                            {{ $movement->direction === \App\Models\AccountingTreasuryMovement::DIRECTION_INFLOW ? $amount($movement->amount) : '-' }}
                                        </td>
                                        <td class="amount-cell text-end treasury-outflow" data-sort-value="{{ $movement->direction === \App\Models\AccountingTreasuryMovement::DIRECTION_OUTFLOW ? $movement->amount : 0 }}">
                                            {{ $movement->direction === \App\Models\AccountingTreasuryMovement::DIRECTION_OUTFLOW ? $amount($movement->amount) : '-' }}
                                        </td>
                                        <td><span class="status-pill treasury-status-{{ $movement->status }}">{{ $movementStatusLabels[$movement->status] ?? $movement->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.treasury_no_movements') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($movements->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $movements->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $movements->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($movements->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $movements->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($movements->getUrlRange(1, $movements->lastPage()) as $page => $url)
                                @if ($page === $movements->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($movements->hasMorePages())<a href="{{ $movements->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <script type="application/json" id="accountingTreasuryData">@json($chartData)</script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-treasury.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-treasury.js')) !!}</script>
</body>
</html>

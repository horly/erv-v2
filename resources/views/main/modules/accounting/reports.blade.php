<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.reports') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.reports', [$company, $site]);
        $exportParams = collect(request()->query())->except('page')->all();
        $amount = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$currency;
        $number = fn ($value) => number_format((float) $value, 2, ',', ' ');
        $totalRecords = $records->total();
        $salesStatuses = $statusLabels;
        $sectionIcons = [
            'sales' => 'bi-receipt',
            'receipts' => 'bi-cash-coin',
            'purchases' => 'bi-bag-check',
            'treasury' => 'bi-activity',
            'stock' => 'bi-box-seam',
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'reports'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.reports')])

            <section class="dashboard-content module-dashboard-page accounting-dashboard-content accounting-reports-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading reports-heading">
                    <div>
                        <h1>{{ __('main.reports') }}</h1>
                        <p>{{ __('main.reports_subtitle') }}</p>
                    </div>
                    <div class="report-export-actions">
                        <a class="secondary-action" href="{{ route('main.accounting.reports.export', [$company, $site] + $exportParams) }}">
                            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                            {{ __('main.export_csv') }}
                        </a>
                        <a class="primary-action" href="{{ route('main.accounting.reports.pdf', [$company, $site] + $exportParams) }}" target="_blank" rel="noopener">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            {{ __('main.print_pdf') }}
                        </a>
                    </div>
                </section>

                <nav class="report-section-tabs" aria-label="{{ __('main.reports') }}">
                    @foreach ($sections as $key => $label)
                        @php $params = array_merge($exportParams, ['section' => $key]); @endphp
                        <a class="{{ $section === $key ? 'active' : '' }}" href="{{ route('main.accounting.reports', [$company, $site] + $params) }}">
                            <i class="bi {{ $sectionIcons[$key] }}" aria-hidden="true"></i>
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                <section class="company-card receipt-filter-card report-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <input type="hidden" name="section" value="{{ $section }}">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label" for="reportPeriod">{{ __('main.period') }}</label>
                                <select id="reportPeriod" name="period" class="form-select">
                                    <option value="week" @selected($filters['period'] === 'week')>{{ __('admin.week') }}</option>
                                    <option value="month" @selected($filters['period'] === 'month')>{{ __('admin.month') }}</option>
                                    <option value="quarter" @selected($filters['period'] === 'quarter')>{{ __('main.quarter') }}</option>
                                    <option value="year" @selected($filters['period'] === 'year')>{{ __('admin.year') }}</option>
                                    <option value="custom" @selected($filters['period'] === 'custom')>{{ __('main.custom_period') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label" for="reportDateFrom">{{ __('main.date_from') }}</label>
                                <input id="reportDateFrom" name="date_from" class="form-control" type="date" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label" for="reportDateTo">{{ __('main.date_to') }}</label>
                                <input id="reportDateTo" name="date_to" class="form-control" type="date" value="{{ $filters['date_to'] }}">
                            </div>
                            @if (in_array($section, ['sales', 'receipts'], true))
                                <div class="col-12 col-md-6 col-xl-3">
                                    <label class="form-label" for="reportClient">{{ __('main.customer') }}</label>
                                    <select id="reportClient" name="client_id" class="form-select">
                                        <option value="0">{{ __('main.all_customers') }}</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}" @selected($filters['client_id'] === $client->id)>{{ $client->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif ($section === 'purchases')
                                <div class="col-12 col-md-6 col-xl-3">
                                    <label class="form-label" for="reportSupplier">{{ __('main.supplier') }}</label>
                                    <select id="reportSupplier" name="supplier_id" class="form-select">
                                        <option value="0">{{ __('main.all_suppliers') }}</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected($filters['supplier_id'] === $supplier->id)>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if (in_array($section, ['receipts', 'treasury'], true))
                                <div class="col-12 col-md-6 col-xl-3">
                                    <label class="form-label" for="reportPaymentMethod">{{ __('main.payment_method') }}</label>
                                    <select id="reportPaymentMethod" name="payment_method_id" class="form-select">
                                        <option value="0">{{ __('main.all_payment_methods') }}</option>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}" @selected($filters['payment_method_id'] === $method->id)>{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if (in_array($section, ['sales', 'purchases'], true))
                                <div class="col-12 col-md-6 col-xl-3">
                                    <label class="form-label" for="reportStatus">{{ __('main.status') }}</label>
                                    <select id="reportStatus" name="status" class="form-select">
                                        <option value="">{{ __('main.all_statuses') }}</option>
                                        @foreach ($salesStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-12 d-flex justify-content-end gap-2 receipt-filter-actions">
                                <a class="modal-cancel" href="{{ $indexRoute }}?section={{ $section }}">{{ __('main.reset_filters') }}</a>
                                <button class="modal-submit" type="submit">{{ __('main.apply_filters') }}</button>
                            </div>
                        </div>
                    </form>
                </section>

                <div class="report-period-note">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    {{ __('main.report_period_label') }} <strong>{{ $periodLabel }}</strong>
                    <span>{{ $currency }}</span>
                </div>

                <section class="report-metric-grid" aria-label="{{ __('main.reports') }}">
                    @foreach ($metrics as $metric)
                        <article class="report-metric report-metric-{{ $metric['tone'] }}">
                            <span class="report-metric-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                            <p>{{ $metric['label'] }}</p>
                            <strong>{{ $metric['isMoney'] ? $amount($metric['value']) : number_format($metric['value'], 0, ',', ' ') }}</strong>
                        </article>
                    @endforeach
                </section>

                <article class="dashboard-panel report-chart-panel">
                    <h2>{{ $chartData['title'] }}</h2>
                    <div id="accountingReportChart" class="accounting-chart-large"></div>
                </article>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" value="{{ $filters['search'] }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $records->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table report-table" id="companyTable">
                            @if ($section === 'sales')
                                <thead><tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.paid_total') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.balance_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr></thead>
                                <tbody>
                                    @forelse ($records as $invoice)
                                        <tr>
                                            <td>{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                                            <td><span class="reference-pill">{{ $invoice->reference }}</span></td>
                                            <td>{{ $invoice->client?->display_name ?? '-' }}</td>
                                            <td>{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                                            <td class="amount-cell text-end">{{ $amount($invoice->total_ttc) }}</td>
                                            <td class="amount-cell text-end">{{ $amount($invoice->paid_total) }}</td>
                                            <td class="amount-cell text-end">{{ $amount($invoice->balance_due) }}</td>
                                            <td><span class="status-pill sales-invoice-status-{{ $invoice->status }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="8">{{ __('main.report_no_results') }}</td></tr>
                                    @endforelse
                                    <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                                </tbody>
                            @elseif ($section === 'receipts')
                                <thead><tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="date">{{ __('main.payment_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.sales_invoices') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr></thead>
                                <tbody>
                                    @forelse ($records as $payment)
                                        <tr>
                                            <td>{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                                            <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                                            <td><span class="reference-pill">{{ $payment->reference }}</span></td>
                                            <td>{{ $payment->salesInvoice?->reference ?? '-' }}</td>
                                            <td>{{ $payment->salesInvoice?->client?->display_name ?? '-' }}</td>
                                            <td>{{ $payment->paymentMethod?->name ?? '-' }}</td>
                                            <td class="amount-cell text-end">{{ $amount($payment->amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="7">{{ __('main.report_no_results') }}</td></tr>
                                    @endforelse
                                    <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                                </tbody>
                            @elseif ($section === 'purchases')
                                <thead><tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.supplier') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="date">{{ __('main.purchase_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.paid_total') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.balance_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr></thead>
                                <tbody>
                                    @forelse ($records as $purchase)
                                        <tr>
                                            <td>{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                                            <td><span class="reference-pill">{{ $purchase->reference }}</span></td>
                                            <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                                            <td>{{ optional($purchase->purchase_date)->format('d/m/Y') }}</td>
                                            <td class="amount-cell text-end">{{ $amount($purchase->total_ttc) }}</td>
                                            <td class="amount-cell text-end">{{ $amount($purchase->paid_total) }}</td>
                                            <td class="amount-cell text-end">{{ $amount($purchase->balance_due) }}</td>
                                            <td><span class="status-pill purchase-status-{{ $purchase->status }}">{{ $statusLabels[$purchase->status] ?? $purchase->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="8">{{ __('main.report_no_results') }}</td></tr>
                                    @endforelse
                                    <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                                </tbody>
                            @elseif ($section === 'treasury')
                                <thead><tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.treasury_source') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.direction') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr></thead>
                                <tbody>
                                    @forelse ($records as $movement)
                                        <tr>
                                            <td>{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                                            <td>{{ optional($movement->movement_date)->format('d/m/Y') }}</td>
                                            <td><strong>{{ $movement->source_reference ?: $movement->reference }}</strong><small class="d-block text-muted">{{ $movement->label }}</small></td>
                                            <td>{{ $movement->paymentMethod?->name ?? '-' }}</td>
                                            <td>{{ $movement->direction === \App\Models\AccountingTreasuryMovement::DIRECTION_INFLOW ? __('main.treasury_direction_inflow') : __('main.treasury_direction_outflow') }}</td>
                                            <td class="amount-cell text-end {{ $movement->direction === \App\Models\AccountingTreasuryMovement::DIRECTION_INFLOW ? 'treasury-inflow' : 'treasury-outflow' }}">{{ $amount($movement->amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="6">{{ __('main.report_no_results') }}</td></tr>
                                    @endforelse
                                    <tr class="empty-row search-empty-row" hidden><td colspan="6">{{ __('admin.no_results') }}</td></tr>
                                </tbody>
                            @else
                                <thead><tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.items') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.categories') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.current_stock') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.min_stock') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.report_inventory_value') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr></thead>
                                <tbody>
                                    @forelse ($records as $item)
                                        <tr>
                                            <td>{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                                            <td><span class="reference-pill">{{ $item->reference }}</span></td>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->category?->name ?? '-' }}</td>
                                            <td class="amount-cell text-end">{{ $number($item->current_stock) }}</td>
                                            <td class="amount-cell text-end">{{ $number($item->min_stock) }}</td>
                                            <td class="amount-cell text-end">{{ $amount($item->current_stock * $item->purchase_price) }}</td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="7">{{ __('main.report_no_results') }}</td></tr>
                                    @endforelse
                                    <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </section>

                @if ($records->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $records->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $records->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($records->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $records->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($records->getUrlRange(1, $records->lastPage()) as $page => $url)
                                @if ($page === $records->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <script type="application/json" id="accountingReportData">@json($chartData)</script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-reports.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-reports.js')) !!}</script>
</body>
</html>

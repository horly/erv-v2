<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.payments_received') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRecords = $receipts->total();
        $currencySuffix = $site->currency ?: 'CDF';
        $formatAmount = fn (float $value) => number_format($value, 2, ',', ' ');
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'receipts'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.payments_received')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.payments_received') }}</h1>
                        <p>{{ __('main.receipts_subtitle') }}</p>
                    </div>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <div class="modal-total-strip">
                    <span>{{ __('main.total_received') }}</span>
                    <strong>{{ $formatAmount((float) $receiptMetrics['total_received']) }} {{ $currencySuffix }}</strong>
                </div>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ route('main.accounting.receipts', [$company, $site]) }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label for="receiptClientFilter" class="form-label">{{ __('main.customer') }}</label>
                                <select id="receiptClientFilter" name="client_id" class="form-select">
                                    <option value="0">{{ __('main.all_customers') }}</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" @selected((int) $filters['client_id'] === (int) $client->id)>{{ $client->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="receiptMethodFilter" class="form-label">{{ __('main.payment_method') }}</label>
                                <select id="receiptMethodFilter" name="payment_method_id" class="form-select">
                                    <option value="0">{{ __('main.all_payment_methods') }}</option>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected((int) $filters['payment_method_id'] === (int) $paymentMethod->id)>
                                            {{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="receiptDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="receiptDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="receiptDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="receiptDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="receiptCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="receiptCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="receiptStatusFilter" class="form-label">{{ __('main.invoice_status') }}</label>
                                <select id="receiptStatusFilter" name="invoice_status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($invoiceStatusOptions as $status)
                                        <option value="{{ $status }}" @selected($filters['invoice_status'] === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-9 d-flex align-items-end justify-content-end gap-2 receipt-filter-actions">
                                <a class="modal-cancel" href="{{ route('main.accounting.receipts', [$company, $site]) }}">{{ __('main.reset_filters') }}</a>
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
                        <strong id="visibleCount">{{ $receipts->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table accounting-receipts-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="date">{{ __('main.payment_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.sales_invoice') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.invoice_status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="8">{{ __('main.received_by') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($receipts as $receipt)
                                    @php
                                        $invoice = $receipt->salesInvoice;
                                        $invoiceStatus = $invoice?->status;
                                    @endphp
                                    <tr>
                                        <td>{{ ($receipts->firstItem() ?? 1) + $loop->index }}</td>
                                        <td data-sort-value="{{ optional($receipt->payment_date)->format('Y-m-d') }}">{{ optional($receipt->payment_date)->format('d/m/Y') }}</td>
                                        <td>{{ $receipt->reference ?: '-' }}</td>
                                        <td>{{ $invoice?->reference ?? '-' }}</td>
                                        <td>{{ $invoice?->client?->display_name ?? '-' }}</td>
                                        <td>{{ $receipt->paymentMethod?->name ?? '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $receipt->amount }}">{{ number_format((float) $receipt->amount, 2, ',', ' ') }} {{ $receipt->currency }}</td>
                                        <td>
                                            @if ($invoiceStatus)
                                                <span class="status-pill sales-invoice-status-{{ $invoiceStatus }}">{{ $statusLabels[$invoiceStatus] ?? $invoiceStatus }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $receipt->receiver?->name ?? '-' }}</td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($invoice)
                                                    <a class="table-button table-button-print" href="{{ route('main.accounting.sales-invoices.print', [$company, $site, $invoice]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.print_pdf') }}" title="{{ __('main.print_pdf') }}">
                                                        <i class="bi bi-printer" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#receiptDetailsModal{{ $receipt->id }}" aria-label="{{ __('main.view_details') }}" title="{{ __('main.view_details') }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="10">{{ __('main.no_receipts') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="10">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($receipts->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $receipts->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $receipts->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($receipts->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $receipts->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($receipts->getUrlRange(1, $receipts->lastPage()) as $page => $url)
                                @if ($page === $receipts->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($receipts->hasMorePages())<a href="{{ $receipts->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($receipts as $receipt)
        <div class="modal fade subscription-modal" id="receiptDetailsModal{{ $receipt->id }}" tabindex="-1" aria-labelledby="receiptDetailsModal{{ $receipt->id }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content app-modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="receiptDetailsModal{{ $receipt->id }}Label">
                            <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                            {{ __('main.receipt_details') }}
                        </h2>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 receipt-details-grid">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.sales_invoice') }}</label>
                                <input class="form-control" value="{{ $receipt->salesInvoice?->reference ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.payment_date') }}</label>
                                <input class="form-control" value="{{ optional($receipt->payment_date)->format('d/m/Y') }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.payment_method') }}</label>
                                <input class="form-control" value="{{ $receipt->paymentMethod?->name ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.amount') }}</label>
                                <input class="form-control" value="{{ number_format((float) $receipt->amount, 2, ',', ' ') }} {{ $receipt->currency }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.reference') }}</label>
                                <input class="form-control" value="{{ $receipt->reference ?: '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.received_by') }}</label>
                                <input class="form-control" value="{{ $receipt->receiver?->name ?? '-' }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('main.notes') }}</label>
                                <textarea class="form-control" rows="3" readonly>{{ $receipt->notes ?: '-' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

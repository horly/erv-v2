<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.sales_invoices') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalInvoices = $invoices->total();
        $payableStatuses = [
            \App\Models\AccountingSalesInvoice::STATUS_DRAFT,
            \App\Models\AccountingSalesInvoice::STATUS_ISSUED,
            \App\Models\AccountingSalesInvoice::STATUS_PARTIALLY_PAID,
            \App\Models\AccountingSalesInvoice::STATUS_OVERDUE,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'sales-invoices'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.sales_invoices')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.sales_invoices') }}</h1>
                        <p>{{ __('main.sales_invoices_subtitle') }}</p>
                    </div>
                    @if ($invoicePermissions['can_create'])
                        <a class="primary-action" href="{{ route('main.accounting.sales-invoices.create', [$company, $site]) }}">
                            <i class="bi bi-receipt" aria-hidden="true"></i>
                            {{ __('main.new_sales_invoice') }}
                        </a>
                    @endif
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $invoices->count() }}</strong>
                        /
                        <strong>{{ $totalInvoices }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table sales-invoice-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.paid_total') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="7" data-sort-type="number">{{ __('main.balance_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="8">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ ($invoices->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $invoice->reference }}</span></td>
                                        <td>{{ $invoice->client?->display_name ?? '-' }}</td>
                                        <td>{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                                        <td>{{ optional($invoice->due_date)->format('d/m/Y') }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $invoice->total_ttc }}">{{ number_format((float) $invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $invoice->paid_total }}">{{ number_format((float) $invoice->paid_total, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $invoice->balance_due }}">{{ number_format((float) $invoice->balance_due, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                                        <td><span class="status-pill sales-invoice-status-{{ $invoice->status }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ route('main.accounting.sales-invoices.print', [$company, $site, $invoice]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.print_pdf') }}" title="{{ __('main.print_pdf') }}">
                                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                                </a>
                                                @if ($invoice->payments->isNotEmpty())
                                                    <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#salesInvoicePaymentsHistoryModal{{ $invoice->id }}" aria-label="{{ __('main.view_payments') }}" title="{{ __('main.view_payments') }}">
                                                        <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($invoicePermissions['can_update'] && in_array($invoice->status, $payableStatuses, true))
                                                    <button class="table-button table-button-edit" type="button" data-bs-toggle="modal" data-bs-target="#salesInvoicePaymentModal{{ $invoice->id }}" aria-label="{{ __('main.add_payment') }}" title="{{ __('main.add_payment') }}">
                                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($invoicePermissions['can_update'] && $invoice->isEditable())
                                                    <a class="table-button table-button-edit" href="{{ route('main.accounting.sales-invoices.edit', [$company, $site, $invoice]) }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if ($invoicePermissions['can_delete'] && $invoice->isEditable())
                                                    <form method="POST" action="{{ route('main.accounting.sales-invoices.destroy', [$company, $site, $invoice]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_sales_invoice_title') }}" data-delete-text="{{ __('main.delete_sales_invoice_text', ['reference' => $invoice->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="10">{{ __('main.no_sales_invoices') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="10">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($invoices->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $invoices->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $invoices->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalInvoices }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($invoices->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $invoices->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($invoices->getUrlRange(1, $invoices->lastPage()) as $page => $url)
                                @if ($page === $invoices->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($invoices->hasMorePages())<a href="{{ $invoices->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($invoices as $invoice)
        @if ($invoice->payments->isNotEmpty())
            <div class="modal fade subscription-modal related-table-modal sales-invoice-payments-history-modal" id="salesInvoicePaymentsHistoryModal{{ $invoice->id }}" tabindex="-1" aria-labelledby="salesInvoicePaymentsHistoryModal{{ $invoice->id }}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content modal-table-dialog">
                        <div class="modal-body" data-sales-payments-table>
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                            <h2 id="salesInvoicePaymentsHistoryModal{{ $invoice->id }}Label">
                                <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                                {{ __('main.sales_invoice_payments_title', ['reference' => $invoice->reference]) }}
                            </h2>

                            <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                                <label class="search-box">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <input type="search" placeholder="{{ __('admin.search') }}" autocomplete="off" data-sales-payments-search>
                                </label>
                                <span class="row-count">
                                    <strong data-sales-payments-visible-count>{{ $invoice->payments->count() }}</strong>
                                    /
                                    <strong data-sales-payments-total-count>{{ $invoice->payments->count() }}</strong>
                                    {{ __('admin.rows') }}
                                </span>
                            </section>

                            <div class="modal-table-frame">
                                <table class="company-table modal-data-table">
                                    <thead>
                                        <tr>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="1" data-sort-type="date">{{ __('main.payment_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="2" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="3">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="4">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="5">{{ __('main.received_by') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        </tr>
                                    </thead>
                                    <tbody data-sales-payments-body>
                                        @foreach ($invoice->payments->sortByDesc('payment_date')->values() as $payment)
                                            <tr data-payment-row>
                                                <td data-sort-value="{{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                                <td data-sort-value="{{ optional($payment->payment_date)->format('Y-m-d') }}">{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                                                <td class="amount-cell text-end" data-sort-value="{{ $payment->amount }}">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                                <td>{{ $payment->paymentMethod?->name ?? '-' }}</td>
                                                <td>{{ $payment->reference ?: '-' }}</td>
                                                <td>{{ $payment->receiver?->name ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <p class="modal-table-empty" data-sales-payments-empty hidden>{{ __('main.no_sales_invoice_payments') }}</p>
                            </div>

                            <section class="subscriptions-pagination modal-table-pagination" data-sales-payments-pagination data-previous-label="{{ __('admin.previous') }}" data-next-label="{{ __('admin.next') }}" data-showing-label="{{ __('admin.showing') }}" data-to-label="{{ __('admin.to') }}" data-on-label="{{ __('admin.on') }}" hidden aria-label="{{ __('admin.pagination') }}">
                                <span data-sales-payments-pagination-count></span>
                                <nav class="pagination-shell" data-sales-payments-pagination-nav aria-label="{{ __('admin.pagination') }}"></nav>
                            </section>

                            <div class="modal-actions">
                                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($invoicePermissions['can_update'] && in_array($invoice->status, $payableStatuses, true))
            <div class="modal fade subscription-modal sales-invoice-payment-modal" id="salesInvoicePaymentModal{{ $invoice->id }}" tabindex="-1" aria-labelledby="salesInvoicePaymentModal{{ $invoice->id }}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content app-modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="salesInvoicePaymentModal{{ $invoice->id }}Label">
                                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                {{ __('main.add_payment') }}
                            </h2>
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                        <form class="admin-form sales-invoice-payment-form" method="POST" action="{{ route('main.accounting.sales-invoices.payments.store', [$company, $site, $invoice]) }}" novalidate>
                            @csrf
                            <input type="hidden" name="payment_invoice_id" value="{{ $invoice->id }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.amount') }} *</label>
                                    <input name="amount" type="number" step="0.01" min="0.01" max="{{ number_format((float) $invoice->balance_due, 2, '.', '') }}" class="form-control @if ((int) old('payment_invoice_id') === $invoice->id && $errors->has('amount')) is-invalid @endif" value="{{ (int) old('payment_invoice_id') === $invoice->id ? old('amount') : number_format((float) $invoice->balance_due, 2, '.', '') }}" placeholder="0" data-payment-amount data-required-message="{{ __('validation.required', ['attribute' => strtolower(__('main.amount'))]) }}" data-min-message="{{ __('validation.min.numeric', ['attribute' => strtolower(__('main.amount')), 'min' => '0,01']) }}" data-max-message="{{ __('main.sales_invoice_payment_exceeds_balance', ['amount' => number_format((float) $invoice->balance_due, 2, ',', ' '), 'currency' => $invoice->currency]) }}">
                                    <div class="invalid-feedback d-block" data-payment-amount-feedback>
                                        @if ((int) old('payment_invoice_id') === $invoice->id)
                                            @error('amount'){{ $message }}@enderror
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.payment_date') }} *</label>
                                    <input name="payment_date" type="date" class="form-control @if ((int) old('payment_invoice_id') === $invoice->id && $errors->has('payment_date')) is-invalid @endif" value="{{ (int) old('payment_invoice_id') === $invoice->id ? old('payment_date') : now()->format('Y-m-d') }}">
                                    @if ((int) old('payment_invoice_id') === $invoice->id)
                                        @error('payment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.payment_method') }} *</label>
                                    <select name="payment_method_id" class="form-select @if ((int) old('payment_invoice_id') === $invoice->id && $errors->has('payment_method_id')) is-invalid @endif" required>
                                        <option value="">{{ __('main.choose_payment_method') }}</option>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}" @selected((int) old('payment_invoice_id') === $invoice->id && (string) old('payment_method_id') === (string) $method->id)>{{ $method->name }} ({{ $method->currency_code }})</option>
                                        @endforeach
                                    </select>
                                    @if ((int) old('payment_invoice_id') === $invoice->id)
                                        @error('payment_method_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.reference') }}</label>
                                    <input name="reference" type="text" class="form-control" placeholder="{{ __('main.reference') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('main.notes') }}</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="{{ __('main.notes') }}"></textarea>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                                <button type="submit" class="modal-submit">{{ __('main.save_payment') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const invalidPaymentInvoiceId = @json(old('payment_invoice_id'));

            if (invalidPaymentInvoiceId) {
                const invalidPaymentModal = document.getElementById(`salesInvoicePaymentModal${invalidPaymentInvoiceId}`);
                if (invalidPaymentModal && window.bootstrap?.Modal) {
                    bootstrap.Modal.getOrCreateInstance(invalidPaymentModal).show();
                }
            }

            const parseAmount = (value = '') => Number(String(value).replace(/\s/g, '').replace(',', '.'));

            const validatePaymentAmount = (input, showRequired = true) => {
                if (!input) {
                    return true;
                }

                const feedback = input.closest('.col-md-6, .col-12')?.querySelector('[data-payment-amount-feedback]');
                const value = parseAmount(input.value);
                const max = parseAmount(input.max);
                let message = '';

                if (input.value.trim() === '') {
                    message = showRequired ? input.dataset.requiredMessage : '';
                } else if (!Number.isFinite(value) || value < 0.01) {
                    message = input.dataset.minMessage;
                } else if (Number.isFinite(max) && value > max) {
                    message = input.dataset.maxMessage;
                }

                input.classList.toggle('is-invalid', message !== '');
                input.classList.toggle('is-valid', message === '' && input.value.trim() !== '');

                if (feedback) {
                    feedback.textContent = message;
                    feedback.hidden = message === '';
                }

                return message === '';
            };

            document.querySelectorAll('.sales-invoice-payment-form').forEach((form) => {
                const amountInput = form.querySelector('[data-payment-amount]');
                const amountFeedback = form.querySelector('[data-payment-amount-feedback]');

                if (amountFeedback && amountFeedback.textContent.trim() === '') {
                    amountFeedback.hidden = true;
                }

                amountInput?.addEventListener('input', () => {
                    validatePaymentAmount(amountInput, false);
                });

                amountInput?.addEventListener('blur', () => {
                    validatePaymentAmount(amountInput);
                });

                form.addEventListener('submit', (event) => {
                    if (!validatePaymentAmount(amountInput)) {
                        event.preventDefault();
                        amountInput?.focus();
                    }
                });
            });

            const normalize = (value = '') => String(value)
                .trim()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const sortValue = (cell, type = 'text') => {
                const rawValue = cell?.dataset.sortValue || cell?.textContent || '';
                const value = String(rawValue).trim();

                if (type === 'number') {
                    return Number(value.replace(/[^0-9.-]/g, '')) || 0;
                }

                if (type === 'date') {
                    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                        return new Date(value).getTime();
                    }

                    const match = value.match(/(\d{2})\/(\d{2})\/(\d{4})/);
                    return match ? new Date(`${match[3]}-${match[2]}-${match[1]}`).getTime() : 0;
                }

                return normalize(value);
            };

            const initPaymentsTable = (wrapper) => {
                if (!wrapper || wrapper.dataset.salesPaymentsTableBound === 'true') {
                    return;
                }

                wrapper.dataset.salesPaymentsTableBound = 'true';

                const rows = Array.from(wrapper.querySelectorAll('[data-payment-row]'));
                const search = wrapper.querySelector('[data-sales-payments-search]');
                const body = wrapper.querySelector('[data-sales-payments-body]');
                const empty = wrapper.querySelector('[data-sales-payments-empty]');
                const visibleCount = wrapper.querySelector('[data-sales-payments-visible-count]');
                const totalCount = wrapper.querySelector('[data-sales-payments-total-count]');
                const pagination = wrapper.querySelector('[data-sales-payments-pagination]');
                const paginationCount = wrapper.querySelector('[data-sales-payments-pagination-count]');
                const paginationNav = wrapper.querySelector('[data-sales-payments-pagination-nav]');
                const perPage = 5;
                let page = 1;
                let sortIndex = null;
                let sortType = 'text';
                let sortDirection = 'asc';

                if (totalCount) {
                    totalCount.textContent = String(rows.length);
                }

                const render = () => {
                    const query = normalize(search?.value || '');
                    let filteredRows = rows.filter((row) => normalize(row.textContent || '').includes(query));

                    if (sortIndex !== null) {
                        filteredRows = filteredRows.sort((left, right) => {
                            const leftValue = sortValue(left.cells[sortIndex], sortType);
                            const rightValue = sortValue(right.cells[sortIndex], sortType);

                            if (leftValue < rightValue) {
                                return sortDirection === 'asc' ? -1 : 1;
                            }

                            if (leftValue > rightValue) {
                                return sortDirection === 'asc' ? 1 : -1;
                            }

                            return 0;
                        });
                    }

                    const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
                    page = Math.min(page, totalPages);
                    const pageRows = filteredRows.slice((page - 1) * perPage, page * perPage);

                    rows.forEach((row) => {
                        row.hidden = true;
                    });

                    pageRows.forEach((row) => {
                        row.hidden = false;
                        body.appendChild(row);
                    });

                    if (empty) {
                        empty.hidden = filteredRows.length > 0;
                    }

                    if (visibleCount) {
                        visibleCount.textContent = String(filteredRows.length);
                    }

                    if (!pagination || !paginationNav) {
                        return;
                    }

                    paginationNav.innerHTML = '';
                    if (paginationCount) {
                        paginationCount.textContent = '';
                    }

                    if (filteredRows.length > perPage) {
                        const previousLabel = pagination.dataset.previousLabel || 'Previous';
                        const nextLabel = pagination.dataset.nextLabel || 'Next';
                        const showingLabel = pagination.dataset.showingLabel || 'Showing';
                        const toLabel = pagination.dataset.toLabel || 'to';
                        const onLabel = pagination.dataset.onLabel || 'of';
                        const start = ((page - 1) * perPage) + 1;
                        const end = Math.min(page * perPage, filteredRows.length);

                        pagination.hidden = false;
                        if (paginationCount) {
                            paginationCount.textContent = `${showingLabel} ${start} ${toLabel} ${end} ${onLabel} ${filteredRows.length}`;
                        }

                        paginationNav.innerHTML = `
                            <button type="button" ${page === 1 ? 'disabled' : ''} data-sales-payments-page="${page - 1}">${previousLabel}</button>
                            ${Array.from({ length: totalPages }, (_, index) => {
                                const currentPage = index + 1;
                                return `<button type="button" class="${currentPage === page ? 'active' : ''}" data-sales-payments-page="${currentPage}">${currentPage}</button>`;
                            }).join('')}
                            <button type="button" ${page === totalPages ? 'disabled' : ''} data-sales-payments-page="${page + 1}">${nextLabel}</button>
                        `;
                    } else {
                        pagination.hidden = true;
                    }
                };

                search?.addEventListener('input', () => {
                    page = 1;
                    render();
                });

                wrapper.querySelectorAll('[data-sales-payments-sort]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const nextIndex = Number(button.dataset.salesPaymentsSort);
                        sortDirection = sortIndex === nextIndex && sortDirection === 'asc' ? 'desc' : 'asc';
                        sortIndex = nextIndex;
                        sortType = button.dataset.sortType || 'text';
                        page = 1;

                        wrapper.querySelectorAll('[data-sales-payments-sort]').forEach((sortButton) => {
                            sortButton.classList.remove('is-sorted-asc', 'is-sorted-desc');
                        });

                        button.classList.add(sortDirection === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
                        render();
                    });
                });

                pagination?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-sales-payments-page]');

                    if (!button || button.disabled) {
                        return;
                    }

                    page = Number(button.dataset.salesPaymentsPage || '1');
                    render();
                });

                render();
            };

            const initAllPaymentTables = () => {
                document.querySelectorAll('[data-sales-payments-table]').forEach(initPaymentsTable);
            };

            initAllPaymentTables();
            document.addEventListener('exad:table-updated', initAllPaymentTables);
            document.addEventListener('shown.bs.modal', (event) => {
                initPaymentsTable(event.target.querySelector('[data-sales-payments-table]'));
            });
        })();
    </script>
</body>
</html>

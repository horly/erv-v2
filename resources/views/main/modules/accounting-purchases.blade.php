<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.purchases') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.purchases', [$company, $site]);
        $totalRecords = $purchases->total();
        $currencySuffix = $site->currency ?: array_key_first($currencies) ?: 'CDF';
        $payableStatuses = [
            \App\Models\AccountingPurchase::STATUS_VALIDATED,
            \App\Models\AccountingPurchase::STATUS_PARTIALLY_PAID,
            \App\Models\AccountingPurchase::STATUS_OVERDUE,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'purchases'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.purchases')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.purchases') }}</h1>
                        <p>{{ __('main.purchases_subtitle') }}</p>
                    </div>
                    @if ($purchasePermissions['can_create'])
                        <a class="primary-action" href="{{ route('main.accounting.purchases.create', [$company, $site]) }}">
                            <i class="bi bi-bag-check" aria-hidden="true"></i>
                            {{ __('main.new_purchase') }}
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

                <div class="modal-total-strip">
                    <span>{{ __('main.total_supplier_debt') }}</span>
                    <strong>{{ number_format((float) $totalBalanceDue, 2, ',', ' ') }} {{ $currencySuffix }}</strong>
                </div>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label for="purchaseSupplierFilter" class="form-label">{{ __('main.supplier') }}</label>
                                <select id="purchaseSupplierFilter" name="supplier_id" class="form-select">
                                    <option value="0">{{ __('main.all_suppliers') }}</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected((int) $filters['supplier_id'] === (int) $supplier->id)>{{ $supplier->name }} ({{ $supplier->reference }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="purchaseMethodFilter" class="form-label">{{ __('main.payment_method') }}</label>
                                <select id="purchaseMethodFilter" name="payment_method_id" class="form-select">
                                    <option value="0">{{ __('main.all_payment_methods') }}</option>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected((int) $filters['payment_method_id'] === (int) $paymentMethod->id)>{{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="purchaseDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="purchaseDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="purchaseDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="purchaseDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="purchaseCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="purchaseCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="purchaseStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="purchaseStatusFilter" name="status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-9 d-flex align-items-end justify-content-end gap-2 receipt-filter-actions">
                                <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('main.reset_filters') }}</a>
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
                        <strong id="visibleCount">{{ $purchases->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.supplier') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="date">{{ __('main.purchase_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="date">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.paid_total') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="7" data-sort-type="number">{{ __('main.balance_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="8">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $purchase)
                                    <tr>
                                        <td>{{ ($purchases->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>
                                            <span class="reference-pill">{{ $purchase->reference }}</span>
                                            @if ($purchase->supplier_invoice_reference)
                                                <small class="d-block text-muted">{{ $purchase->supplier_invoice_reference }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                                        <td data-sort-value="{{ optional($purchase->purchase_date)->format('Y-m-d') }}">{{ optional($purchase->purchase_date)->format('d/m/Y') }}</td>
                                        <td data-sort-value="{{ optional($purchase->due_date)->format('Y-m-d') }}">{{ optional($purchase->due_date)->format('d/m/Y') ?: '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $purchase->total_ttc }}">{{ number_format((float) $purchase->total_ttc, 2, ',', ' ') }} {{ $purchase->currency }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $purchase->paid_total }}">{{ number_format((float) $purchase->paid_total, 2, ',', ' ') }} {{ $purchase->currency }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $purchase->balance_due }}">{{ number_format((float) $purchase->balance_due, 2, ',', ' ') }} {{ $purchase->currency }}</td>
                                        <td><span class="status-pill purchase-status-{{ $purchase->status }}">{{ $statusLabels[$purchase->status] ?? $purchase->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($purchase->payments->isNotEmpty())
                                                    <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#purchasePaymentsHistoryModal{{ $purchase->id }}" aria-label="{{ __('main.view_payments') }}" title="{{ __('main.view_payments') }}">
                                                        <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($purchasePermissions['can_update'] && in_array($purchase->status, $payableStatuses, true))
                                                    <button class="table-button table-button-edit" type="button" data-bs-toggle="modal" data-bs-target="#purchasePaymentModal{{ $purchase->id }}" aria-label="{{ __('main.add_payment') }}" title="{{ __('main.add_payment') }}">
                                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($purchasePermissions['can_update'] && $purchase->isEditable())
                                                    <a class="table-button table-button-edit" href="{{ route('main.accounting.purchases.edit', [$company, $site, $purchase]) }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if ($purchasePermissions['can_delete'] && $purchase->isEditable())
                                                    <form method="POST" action="{{ route('main.accounting.purchases.destroy', [$company, $site, $purchase]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_purchase_title') }}" data-delete-text="{{ __('main.delete_purchase_text', ['reference' => $purchase->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="10">{{ __('main.no_purchases') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="10">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($purchases->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $purchases->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $purchases->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($purchases->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $purchases->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($purchases->getUrlRange(1, $purchases->lastPage()) as $page => $url)
                                @if ($page === $purchases->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($purchases->hasMorePages())<a href="{{ $purchases->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($purchases as $purchase)
        @if ($purchase->payments->isNotEmpty())
            <div class="modal fade subscription-modal related-table-modal" id="purchasePaymentsHistoryModal{{ $purchase->id }}" tabindex="-1" aria-labelledby="purchasePaymentsHistoryModal{{ $purchase->id }}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content modal-table-dialog">
                        <div class="modal-body" data-sales-payments-table>
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                            <h2 id="purchasePaymentsHistoryModal{{ $purchase->id }}Label">
                                <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                                {{ __('main.purchase_payments_title', ['reference' => $purchase->reference]) }}
                            </h2>

                            <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                                <label class="search-box">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <input type="search" placeholder="{{ __('admin.search') }}" autocomplete="off" data-sales-payments-search>
                                </label>
                                <span class="row-count">
                                    <strong data-sales-payments-visible-count>{{ $purchase->payments->count() }}</strong>
                                    /
                                    <strong data-sales-payments-total-count>{{ $purchase->payments->count() }}</strong>
                                    {{ __('admin.rows') }}
                                </span>
                            </section>

                            <div class="modal-table-frame">
                                <table class="company-table modal-data-table">
                                    <thead>
                                        <tr>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="1" data-sort-type="date">{{ __('main.payment_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th class="text-end"><button class="table-sort" type="button" data-sales-payments-sort="2" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="3">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="4">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sales-payments-sort="5">{{ __('main.paid_by') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        </tr>
                                    </thead>
                                    <tbody data-sales-payments-body>
                                        @foreach ($purchase->payments->sortByDesc('payment_date')->values() as $payment)
                                            <tr data-payment-row>
                                                <td data-sort-value="{{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                                <td data-sort-value="{{ optional($payment->payment_date)->format('Y-m-d') }}">{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                                                <td class="amount-cell text-end" data-sort-value="{{ $payment->amount }}">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                                <td>{{ $payment->paymentMethod?->name ?? '-' }}</td>
                                                <td>{{ $payment->reference ?: '-' }}</td>
                                                <td>{{ $payment->payer?->name ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <p class="modal-table-empty" data-sales-payments-empty hidden>{{ __('main.no_purchase_payments') }}</p>
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

        @if ($purchasePermissions['can_update'] && in_array($purchase->status, $payableStatuses, true))
            <div class="modal fade subscription-modal sales-invoice-payment-modal" id="purchasePaymentModal{{ $purchase->id }}" tabindex="-1" aria-labelledby="purchasePaymentModal{{ $purchase->id }}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content app-modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="purchasePaymentModal{{ $purchase->id }}Label">
                                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                {{ __('main.add_supplier_payment') }}
                            </h2>
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                        <form class="admin-form sales-invoice-payment-form" method="POST" action="{{ route('main.accounting.purchases.payments.store', [$company, $site, $purchase]) }}" novalidate>
                            @csrf
                            <input type="hidden" name="payment_purchase_id" value="{{ $purchase->id }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.amount') }} *</label>
                                    <input name="amount" type="number" step="0.01" min="0.01" max="{{ number_format((float) $purchase->balance_due, 2, '.', '') }}" class="form-control @if ((int) old('payment_purchase_id') === $purchase->id && $errors->has('amount')) is-invalid @endif" value="{{ (int) old('payment_purchase_id') === $purchase->id ? old('amount') : number_format((float) $purchase->balance_due, 2, '.', '') }}" placeholder="0">
                                    @if ((int) old('payment_purchase_id') === $purchase->id)
                                        @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.payment_date') }} *</label>
                                    <input name="payment_date" type="date" class="form-control @if ((int) old('payment_purchase_id') === $purchase->id && $errors->has('payment_date')) is-invalid @endif" value="{{ (int) old('payment_purchase_id') === $purchase->id ? old('payment_date') : now()->format('Y-m-d') }}">
                                    @if ((int) old('payment_purchase_id') === $purchase->id)
                                        @error('payment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.payment_method') }} *</label>
                                    <select name="payment_method_id" class="form-select @if ((int) old('payment_purchase_id') === $purchase->id && $errors->has('payment_method_id')) is-invalid @endif">
                                        <option value="">{{ __('main.choose_payment_method') }}</option>
                                        @foreach ($paymentMethods as $paymentMethod)
                                            <option value="{{ $paymentMethod->id }}" @selected((int) old('payment_method_id') === (int) $paymentMethod->id)>{{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})</option>
                                        @endforeach
                                    </select>
                                    @if ((int) old('payment_purchase_id') === $purchase->id)
                                        @error('payment_method_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.reference') }}</label>
                                    <input name="reference" type="text" class="form-control" value="{{ (int) old('payment_purchase_id') === $purchase->id ? old('reference') : '' }}" placeholder="{{ __('main.reference') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('main.notes') }}</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('main.notes') }}">{{ (int) old('payment_purchase_id') === $purchase->id ? old('notes') : '' }}</textarea>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                                <button class="modal-submit" type="submit">{{ __('main.save_supplier_payment') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>{!! file_get_contents(resource_path('js/main/modal-tables.js')) !!}</script>
    @if ($errors->any() && old('payment_purchase_id'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('purchasePaymentModal{{ (int) old('payment_purchase_id') }}');
                if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        </script>
    @endif
</body>
</html>

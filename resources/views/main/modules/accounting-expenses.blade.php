<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.expenses') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.expenses', [$company, $site]);
        $totalRecords = $expenses->total();
        $currencySuffix = $site->currency ?: array_key_first($currencies) ?: 'CDF';
        $defaultCurrencyCode = old('currency', $site->currency ?: array_key_first($currencies));
        $defaultCategoryId = old('expense_category_id', optional($categories->first())->id);
        $defaultPaymentMethodId = old('payment_method_id', optional($paymentMethods->firstWhere('currency_code', $defaultCurrencyCode))->id ?? optional($paymentMethods->first())->id);
        $hasFormErrors = $errors->any();
        $isEditingExpense = old('form_mode') === 'edit' && old('expense_id');
        $formAction = $isEditingExpense
            ? route('main.accounting.expenses.update', [$company, $site, old('expense_id')])
            : route('main.accounting.expenses.store', [$company, $site]);
        $expensePayload = fn ($expense) => [
            'expense_date' => optional($expense->expense_date)->format('Y-m-d'),
            'expense_category_id' => $expense->expense_category_id,
            'label' => $expense->label,
            'beneficiary' => $expense->beneficiary,
            'description' => $expense->description,
            'amount' => number_format((float) $expense->amount, 2, '.', ''),
            'currency' => $expense->currency,
            'payment_method_id' => $expense->payment_method_id,
            'payment_reference' => $expense->payment_reference,
            'status' => $expense->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'expenses'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.expenses')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.expenses') }}</h1>
                        <p>{{ __('main.expenses_subtitle') }}</p>
                    </div>
                    @if ($expensePermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#expenseModal" data-expense-mode="create">
                            <i class="bi bi-wallet2" aria-hidden="true"></i>
                            {{ __('main.new_expense') }}
                        </button>
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
                    <span>{{ __('main.expenses_total_validated') }}</span>
                    <strong>{{ number_format((float) $totalValidated, 2, ',', ' ') }} {{ $currencySuffix }}</strong>
                </div>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label for="expenseCategoryFilter" class="form-label">{{ __('main.expense_category') }}</label>
                                <select id="expenseCategoryFilter" name="expense_category_id" class="form-select">
                                    <option value="0">{{ __('main.all_expense_categories') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) $filters['expense_category_id'] === (int) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="expenseMethodFilter" class="form-label">{{ __('main.payment_method') }}</label>
                                <select id="expenseMethodFilter" name="payment_method_id" class="form-select">
                                    <option value="0">{{ __('main.all_payment_methods') }}</option>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected((int) $filters['payment_method_id'] === (int) $paymentMethod->id)>{{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="expenseDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="expenseDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="expenseDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="expenseDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="expenseCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="expenseCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="expenseStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="expenseStatusFilter" name="status" class="form-select">
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
                        <strong id="visibleCount">{{ $expenses->count() }}</strong>
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
                                    <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.expense_category') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.wording') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.beneficiary') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="7" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="8">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenses as $expense)
                                    <tr>
                                        <td>{{ ($expenses->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="status-pill reference-pill">{{ $expense->reference }}</span></td>
                                        <td data-sort-value="{{ optional($expense->expense_date)->format('Y-m-d') }}">{{ optional($expense->expense_date)->format('d/m/Y') }}</td>
                                        <td>{{ $expense->category?->name ?? '-' }}</td>
                                        <td>{{ $expense->label }}</td>
                                        <td>{{ $expense->beneficiary ?: '-' }}</td>
                                        <td>{{ $expense->paymentMethod?->name ?? '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $expense->amount }}">{{ number_format((float) $expense->amount, 2, ',', ' ') }} {{ $expense->currency }}</td>
                                        <td><span class="status-pill other-income-status-{{ $expense->status }}">{{ $statusLabels[$expense->status] ?? $expense->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#expenseDetailsModal{{ $expense->id }}" aria-label="{{ __('main.view_details') }}" title="{{ __('main.view_details') }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </button>
                                                @if ($expense->isDraft() && $expensePermissions['can_update'])
                                                    <button class="table-button table-button-edit" type="button" data-bs-toggle="modal" data-bs-target="#expenseModal" data-expense-mode="edit" data-expense-id="{{ $expense->id }}" data-expense-action="{{ route('main.accounting.expenses.update', [$company, $site, $expense]) }}" data-expense-values="{{ base64_encode(json_encode($expensePayload($expense))) }}" aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($expense->isDraft() && $expensePermissions['can_update'])
                                                    <form method="POST" action="{{ route('main.accounting.expenses.validate', [$company, $site, $expense]) }}">
                                                        @csrf
                                                        <button class="table-button table-button-confirm" type="submit" aria-label="{{ __('main.validate_expense') }}" title="{{ __('main.validate_expense') }}">
                                                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($expense->isValidated() && $expensePermissions['can_update'])
                                                    <form method="POST" action="{{ route('main.accounting.expenses.cancel', [$company, $site, $expense]) }}">
                                                        @csrf
                                                        <button class="table-button table-button-delete" type="submit" aria-label="{{ __('main.cancel_expense') }}" title="{{ __('main.cancel_expense') }}">
                                                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($expense->isDraft() && $expensePermissions['can_delete'])
                                                    <form method="POST" action="{{ route('main.accounting.expenses.destroy', [$company, $site, $expense]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_expense_title') }}" data-delete-text="{{ __('main.delete_expense_text', ['reference' => $expense->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="10">{{ __('main.no_expenses') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="10">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($expenses->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $expenses->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $expenses->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($expenses->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $expenses->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($expenses->getUrlRange(1, $expenses->lastPage()) as $page => $url)
                                @if ($page === $expenses->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($expenses->hasMorePages())<a href="{{ $expenses->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($expenses as $expense)
        <div class="modal fade subscription-modal" id="expenseDetailsModal{{ $expense->id }}" tabindex="-1" aria-labelledby="expenseDetailsModal{{ $expense->id }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content app-modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="expenseDetailsModal{{ $expense->id }}Label">
                            <i class="bi bi-wallet2" aria-hidden="true"></i>
                            {{ __('main.expense_details') }}
                        </h2>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </div>
                    <div class="modal-body">
                        <dl class="site-detail-list">
                            <div><dt>{{ __('main.reference') }}</dt><dd>{{ $expense->reference }}</dd></div>
                            <div><dt>{{ __('main.date') }}</dt><dd>{{ optional($expense->expense_date)->format('d/m/Y') }}</dd></div>
                            <div><dt>{{ __('main.expense_category') }}</dt><dd>{{ $expense->category?->name ?? '-' }}</dd></div>
                            <div><dt>{{ __('main.wording') }}</dt><dd>{{ $expense->label }}</dd></div>
                            <div><dt>{{ __('main.beneficiary') }}</dt><dd>{{ $expense->beneficiary ?: '-' }}</dd></div>
                            <div><dt>{{ __('main.payment_method') }}</dt><dd>{{ $expense->paymentMethod?->name ?? '-' }}</dd></div>
                            <div><dt>{{ __('main.payment_reference') }}</dt><dd>{{ $expense->payment_reference ?: '-' }}</dd></div>
                            <div><dt>{{ __('main.amount') }}</dt><dd>{{ number_format((float) $expense->amount, 2, ',', ' ') }} {{ $expense->currency }}</dd></div>
                            <div><dt>{{ __('main.status') }}</dt><dd>{{ $statusLabels[$expense->status] ?? $expense->status }}</dd></div>
                            <div><dt>{{ __('main.created_by') }}</dt><dd>{{ $expense->creator?->name ?? '-' }}</dd></div>
                            <div><dt>{{ __('main.description') }}</dt><dd>{{ $expense->description ?: '-' }}</dd></div>
                        </dl>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade subscription-modal" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content admin-form" method="POST" action="{{ $formAction }}" data-expense-form data-create-action="{{ route('main.accounting.expenses.store', [$company, $site]) }}" data-title-create="{{ __('main.new_expense') }}" data-title-edit="{{ __('main.edit_expense') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="expenseHttpMethod" value="PUT" @disabled(! $isEditingExpense)>
                <input type="hidden" name="form_mode" id="expenseFormMode" value="{{ $isEditingExpense ? 'edit' : 'create' }}">
                <input type="hidden" name="expense_id" id="expenseId" value="{{ old('expense_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="expenseModalLabel">
                        <i class="bi bi-wallet2" aria-hidden="true"></i>
                        {{ $isEditingExpense ? __('main.edit_expense') : __('main.new_expense') }}
                    </h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="expense_date" class="form-label">{{ __('main.expense_date') }} *</label>
                            <input id="expense_date" name="expense_date" type="date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', now()->toDateString()) }}" data-expense-field data-default-value="{{ now()->toDateString() }}">
                            @error('expense_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_category_id" class="form-label">{{ __('main.expense_category') }} *</label>
                            <select id="expense_category_id" name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" data-expense-field data-default-value="{{ $defaultCategoryId }}">
                                <option value="">{{ __('main.choose_expense_category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('expense_category_id', $defaultCategoryId) === (int) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_label" class="form-label">{{ __('main.wording') }} *</label>
                            <input id="expense_label" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}" placeholder="{{ __('main.expense_label_placeholder') }}" data-expense-field data-default-value="">
                            @error('label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_beneficiary" class="form-label">{{ __('main.beneficiary') }}</label>
                            <input id="expense_beneficiary" name="beneficiary" class="form-control @error('beneficiary') is-invalid @enderror" value="{{ old('beneficiary') }}" placeholder="{{ __('main.beneficiary_placeholder') }}" data-expense-field data-default-value="">
                            @error('beneficiary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_amount" class="form-label">{{ __('main.amount') }} *</label>
                            <input id="expense_amount" name="amount" type="number" min="0.01" step="0.01" class="form-control text-end @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0,00" data-expense-field data-default-value="">
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_currency" class="form-label">{{ __('main.currency') }} *</label>
                            <select id="expense_currency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-expense-field data-default-value="{{ $defaultCurrencyCode }}">
                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $defaultCurrencyCode) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_payment_method_id" class="form-label">{{ __('main.payment_method') }} *</label>
                            <select id="expense_payment_method_id" name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" data-expense-field data-default-value="{{ $defaultPaymentMethodId }}">
                                <option value="">{{ __('main.choose_payment_method') }}</option>
                                @foreach ($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->id }}" @selected((int) old('payment_method_id', $defaultPaymentMethodId) === (int) $paymentMethod->id)>{{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('payment_method_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_payment_reference" class="form-label">{{ __('main.payment_reference') }}</label>
                            <input id="expense_payment_reference" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror" value="{{ old('payment_reference') }}" placeholder="{{ __('main.payment_reference_placeholder') }}" data-expense-field data-default-value="">
                            @error('payment_reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="expense_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="expense_status" name="status" class="form-select @error('status') is-invalid @enderror" data-expense-field data-default-value="{{ \App\Models\AccountingExpense::STATUS_DRAFT }}">
                                @foreach ($statusLabels as $value => $label)
                                    @if ($value !== \App\Models\AccountingExpense::STATUS_CANCELLED)
                                        <option value="{{ $value }}" @selected(old('status', \App\Models\AccountingExpense::STATUS_DRAFT) === $value)>{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="expense_description" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="expense_description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.description') }}" data-expense-field data-default-value="">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit" data-expense-submit>{{ $isEditingExpense ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modalElement = document.getElementById('expenseModal');
            const form = document.querySelector('[data-expense-form]');

            if (!modalElement || !form) {
                return;
            }

            const methodInput = document.getElementById('expenseHttpMethod');
            const modeInput = document.getElementById('expenseFormMode');
            const idInput = document.getElementById('expenseId');
            const title = document.getElementById('expenseModalLabel');
            const submit = form.querySelector('[data-expense-submit]');
            const createAction = form.dataset.createAction;

            const setFieldValue = (field, value) => {
                field.value = value ?? '';
            };

            const resetForm = () => {
                form.action = createAction;
                methodInput.disabled = true;
                modeInput.value = 'create';
                idInput.value = '';
                title.innerHTML = `<i class="bi bi-wallet2" aria-hidden="true"></i>${form.dataset.titleCreate}`;
                submit.textContent = form.dataset.submitCreate;
                form.querySelectorAll('[data-expense-field]').forEach((field) => setFieldValue(field, field.dataset.defaultValue || ''));
            };

            modalElement.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;

                if (!trigger || trigger.dataset.expenseMode !== 'edit') {
                    resetForm();
                    return;
                }

                const values = JSON.parse(atob(trigger.dataset.expenseValues || 'e30='));
                form.action = trigger.dataset.expenseAction;
                methodInput.disabled = false;
                modeInput.value = 'edit';
                idInput.value = trigger.dataset.expenseId || '';
                title.innerHTML = `<i class="bi bi-wallet2" aria-hidden="true"></i>${form.dataset.titleEdit}`;
                submit.textContent = form.dataset.submitEdit;

                form.querySelectorAll('[data-expense-field]').forEach((field) => {
                    setFieldValue(field, values[field.name] ?? field.dataset.defaultValue ?? '');
                });
            });

            @if ($hasFormErrors)
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            @endif
        })();
    </script>
</body>
</html>

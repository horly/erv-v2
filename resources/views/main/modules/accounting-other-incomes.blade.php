<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.other_income') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.other-incomes', [$company, $site]);
        $totalRecords = $otherIncomes->total();
        $currencySuffix = $site->currency ?: array_key_first($currencies) ?: 'CDF';
        $defaultCurrencyCode = old('currency', $site->currency ?: array_key_first($currencies));
        $defaultPaymentMethodId = old('payment_method_id', optional($paymentMethods->firstWhere('currency_code', $defaultCurrencyCode))->id ?? optional($paymentMethods->first())->id);
        $hasFormErrors = $errors->any();
        $isEditingIncome = old('form_mode') === 'edit' && old('other_income_id');
        $formAction = $isEditingIncome
            ? route('main.accounting.other-incomes.update', [$company, $site, old('other_income_id')])
            : route('main.accounting.other-incomes.store', [$company, $site]);
        $incomePayload = fn ($income) => [
            'income_date' => optional($income->income_date)->format('Y-m-d'),
            'type' => $income->type,
            'label' => $income->label,
            'description' => $income->description,
            'amount' => number_format((float) $income->amount, 2, '.', ''),
            'currency' => $income->currency,
            'payment_method_id' => $income->payment_method_id,
            'payment_reference' => $income->payment_reference,
            'status' => $income->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'other-incomes'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.other_income')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.other_income') }}</h1>
                        <p>{{ __('main.other_income_subtitle') }}</p>
                    </div>
                    @if ($otherIncomePermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#otherIncomeModal" data-other-income-mode="create">
                            <i class="bi bi-plus-circle" aria-hidden="true"></i>
                            {{ __('main.new_other_income') }}
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
                    <span>{{ __('main.other_income_total_validated') }}</span>
                    <strong>{{ number_format((float) $totalValidated, 2, ',', ' ') }} {{ $currencySuffix }}</strong>
                </div>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label for="otherIncomeTypeFilter" class="form-label">{{ __('main.other_income_type') }}</label>
                                <select id="otherIncomeTypeFilter" name="type" class="form-select">
                                    <option value="">{{ __('main.all_types') }}</option>
                                    @foreach ($typeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="otherIncomeMethodFilter" class="form-label">{{ __('main.payment_method') }}</label>
                                <select id="otherIncomeMethodFilter" name="payment_method_id" class="form-select">
                                    <option value="0">{{ __('main.all_payment_methods') }}</option>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected((int) $filters['payment_method_id'] === (int) $paymentMethod->id)>{{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="otherIncomeDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="otherIncomeDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="otherIncomeDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="otherIncomeDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="otherIncomeCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="otherIncomeCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="otherIncomeStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="otherIncomeStatusFilter" name="status" class="form-select">
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
                        <strong id="visibleCount">{{ $otherIncomes->count() }}</strong>
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
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.other_income_type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.wording') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($otherIncomes as $income)
                                    <tr>
                                        <td>{{ ($otherIncomes->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="status-pill reference-pill">{{ $income->reference }}</span></td>
                                        <td data-sort-value="{{ optional($income->income_date)->format('Y-m-d') }}">{{ optional($income->income_date)->format('d/m/Y') }}</td>
                                        <td>{{ $typeLabels[$income->type] ?? $income->type }}</td>
                                        <td>{{ $income->label }}</td>
                                        <td>{{ $income->paymentMethod?->name ?? '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $income->amount }}">{{ number_format((float) $income->amount, 2, ',', ' ') }} {{ $income->currency }}</td>
                                        <td><span class="status-pill other-income-status-{{ $income->status }}">{{ $statusLabels[$income->status] ?? $income->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#otherIncomeDetailsModal{{ $income->id }}" aria-label="{{ __('main.view_details') }}" title="{{ __('main.view_details') }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </button>
                                                @if ($income->isDraft() && $otherIncomePermissions['can_update'])
                                                    <button class="table-button table-button-edit" type="button" data-bs-toggle="modal" data-bs-target="#otherIncomeModal" data-other-income-mode="edit" data-other-income-id="{{ $income->id }}" data-other-income-action="{{ route('main.accounting.other-incomes.update', [$company, $site, $income]) }}" data-other-income-values="{{ base64_encode(json_encode($incomePayload($income))) }}" aria-label="{{ __('admin.edit') }}" title="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($income->isDraft() && $otherIncomePermissions['can_update'])
                                                    <form method="POST" action="{{ route('main.accounting.other-incomes.validate', [$company, $site, $income]) }}">
                                                        @csrf
                                                        <button class="table-button table-button-confirm" type="submit" aria-label="{{ __('main.validate_other_income') }}" title="{{ __('main.validate_other_income') }}">
                                                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($income->isValidated() && $otherIncomePermissions['can_update'])
                                                    <form method="POST" action="{{ route('main.accounting.other-incomes.cancel', [$company, $site, $income]) }}">
                                                        @csrf
                                                        <button class="table-button table-button-delete" type="submit" aria-label="{{ __('main.cancel_other_income') }}" title="{{ __('main.cancel_other_income') }}">
                                                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($income->isDraft() && $otherIncomePermissions['can_delete'])
                                                    <form method="POST" action="{{ route('main.accounting.other-incomes.destroy', [$company, $site, $income]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_other_income_title') }}" data-delete-text="{{ __('main.delete_other_income_text', ['reference' => $income->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.no_other_incomes') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($otherIncomes->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $otherIncomes->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $otherIncomes->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($otherIncomes->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $otherIncomes->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($otherIncomes->getUrlRange(1, $otherIncomes->lastPage()) as $page => $url)
                                @if ($page === $otherIncomes->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($otherIncomes->hasMorePages())<a href="{{ $otherIncomes->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($otherIncomes as $income)
        <div class="modal fade subscription-modal" id="otherIncomeDetailsModal{{ $income->id }}" tabindex="-1" aria-labelledby="otherIncomeDetailsModal{{ $income->id }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content app-modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="otherIncomeDetailsModal{{ $income->id }}Label">
                            <i class="bi bi-plus-circle" aria-hidden="true"></i>
                            {{ __('main.other_income_details') }}
                        </h2>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 receipt-details-grid">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.reference') }}</label>
                                <input class="form-control" value="{{ $income->reference }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.date') }}</label>
                                <input class="form-control" value="{{ optional($income->income_date)->format('d/m/Y') }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.other_income_type') }}</label>
                                <input class="form-control" value="{{ $typeLabels[$income->type] ?? $income->type }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.status') }}</label>
                                <input class="form-control" value="{{ $statusLabels[$income->status] ?? $income->status }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.payment_method') }}</label>
                                <input class="form-control" value="{{ $income->paymentMethod?->name ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.amount') }}</label>
                                <input class="form-control" value="{{ number_format((float) $income->amount, 2, ',', ' ') }} {{ $income->currency }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.payment_reference') }}</label>
                                <input class="form-control" value="{{ $income->payment_reference ?: '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.created_by') }}</label>
                                <input class="form-control" value="{{ $income->creator?->name ?? '-' }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('main.wording') }}</label>
                                <input class="form-control" value="{{ $income->label }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('main.description') }}</label>
                                <textarea class="form-control" rows="3" readonly>{{ $income->description ?: '-' }}</textarea>
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

    <div class="modal fade subscription-modal" id="otherIncomeModal" tabindex="-1" aria-labelledby="otherIncomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form" method="POST" action="{{ $formAction }}" data-other-income-form data-create-action="{{ route('main.accounting.other-incomes.store', [$company, $site]) }}" data-title-create="{{ __('main.new_other_income') }}" data-title-edit="{{ __('main.edit_other_income') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="otherIncomeHttpMethod" value="PUT" @disabled(! $isEditingIncome)>
                <input type="hidden" name="form_mode" id="otherIncomeFormMode" value="{{ $isEditingIncome ? 'edit' : 'create' }}">
                <input type="hidden" name="other_income_id" id="otherIncomeId" value="{{ old('other_income_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="otherIncomeModalLabel"><i class="bi bi-plus-circle" aria-hidden="true"></i>{{ $isEditingIncome ? __('main.edit_other_income') : __('main.new_other_income') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="other_income_date" class="form-label">{{ __('main.date') }} *</label>
                            <input id="other_income_date" name="income_date" type="date" class="form-control @error('income_date') is-invalid @enderror" value="{{ old('income_date', now()->format('Y-m-d')) }}" data-other-income-field data-default-value="{{ now()->format('Y-m-d') }}">
                            @error('income_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="other_income_type" class="form-label">{{ __('main.other_income_type') }} *</label>
                            <select id="other_income_type" name="type" class="form-select @error('type') is-invalid @enderror" data-other-income-field data-default-value="{{ \App\Models\AccountingOtherIncome::TYPE_MISCELLANEOUS }}">
                                @foreach ($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', \App\Models\AccountingOtherIncome::TYPE_MISCELLANEOUS) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="other_income_label" class="form-label">{{ __('main.wording') }} *</label>
                            <input id="other_income_label" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}" placeholder="{{ __('main.other_income_label_placeholder') }}" data-other-income-field data-default-value="">
                            @error('label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="other_income_amount" class="form-label">{{ __('main.amount') }} *</label>
                            <input id="other_income_amount" name="amount" type="number" min="0.01" step="0.01" class="form-control text-end @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0,00" data-other-income-field data-default-value="">
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="other_income_currency" class="form-label">{{ __('main.currency') }} *</label>
                            <select id="other_income_currency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-other-income-field data-default-value="{{ $defaultCurrencyCode }}">
                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $defaultCurrencyCode) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="other_income_payment_method_id" class="form-label">{{ __('main.payment_method') }} *</label>
                            <select id="other_income_payment_method_id" name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" data-other-income-field data-default-value="{{ $defaultPaymentMethodId }}">
                                <option value="">{{ __('main.choose_payment_method') }}</option>
                                @foreach ($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->id }}" @selected((int) old('payment_method_id', $defaultPaymentMethodId) === (int) $paymentMethod->id)>{{ $paymentMethod->name }} ({{ $paymentMethod->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('payment_method_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="other_income_payment_reference" class="form-label">{{ __('main.payment_reference') }}</label>
                            <input id="other_income_payment_reference" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror" value="{{ old('payment_reference') }}" placeholder="{{ __('main.payment_reference_placeholder') }}" data-other-income-field data-default-value="">
                            @error('payment_reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="other_income_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="other_income_status" name="status" class="form-select @error('status') is-invalid @enderror" data-other-income-field data-default-value="{{ \App\Models\AccountingOtherIncome::STATUS_DRAFT }}">
                                @foreach ($statusLabels as $value => $label)
                                    @if ($value !== \App\Models\AccountingOtherIncome::STATUS_CANCELLED)
                                        <option value="{{ $value }}" @selected(old('status', \App\Models\AccountingOtherIncome::STATUS_DRAFT) === $value)>{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="other_income_description" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="other_income_description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.description') }}" data-other-income-field data-default-value="">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit" data-other-income-submit>{{ $isEditingIncome ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modalElement = document.getElementById('otherIncomeModal');
            const form = document.querySelector('[data-other-income-form]');

            if (!modalElement || !form) {
                return;
            }

            const methodInput = document.getElementById('otherIncomeHttpMethod');
            const modeInput = document.getElementById('otherIncomeFormMode');
            const idInput = document.getElementById('otherIncomeId');
            const title = document.getElementById('otherIncomeModalLabel');
            const submit = form.querySelector('[data-other-income-submit]');
            const createAction = form.dataset.createAction;

            const setFieldValue = (field, value) => {
                if (field.tagName === 'TEXTAREA') {
                    field.value = value ?? '';
                    return;
                }

                field.value = value ?? '';
            };

            const resetForm = () => {
                form.action = createAction;
                methodInput.disabled = true;
                modeInput.value = 'create';
                idInput.value = '';
                title.innerHTML = `<i class="bi bi-plus-circle" aria-hidden="true"></i>${form.dataset.titleCreate}`;
                submit.textContent = form.dataset.submitCreate;
                form.querySelectorAll('[data-other-income-field]').forEach((field) => setFieldValue(field, field.dataset.defaultValue || ''));
            };

            modalElement.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;

                if (!trigger || trigger.dataset.otherIncomeMode !== 'edit') {
                    resetForm();
                    return;
                }

                const values = JSON.parse(atob(trigger.dataset.otherIncomeValues || 'e30='));
                form.action = trigger.dataset.otherIncomeAction;
                methodInput.disabled = false;
                modeInput.value = 'edit';
                idInput.value = trigger.dataset.otherIncomeId || '';
                title.innerHTML = `<i class="bi bi-plus-circle" aria-hidden="true"></i>${form.dataset.titleEdit}`;
                submit.textContent = form.dataset.submitEdit;

                form.querySelectorAll('[data-other-income-field]').forEach((field) => {
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

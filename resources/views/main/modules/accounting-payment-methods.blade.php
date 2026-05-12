<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.payment_methods') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRecords = $paymentMethods->total();
        $hasPaymentMethodErrors = $errors->any();
        $isEditingPaymentMethod = old('form_mode') === 'edit' && old('payment_method_id');
        $formAction = $isEditingPaymentMethod
            ? route('main.accounting.payment-methods.update', [$company, $site, old('payment_method_id')])
            : route('main.accounting.payment-methods.store', [$company, $site]);
        $defaultCurrencyCode = old('currency_code', $site->currency ?: array_key_first($currencyOptions));
        $paymentMethodPayload = fn ($method) => [
            'name' => $method->name,
            'type' => $method->type,
            'currency_code' => $method->currency_code,
            'code' => $method->code,
            'bank_name' => $method->bank_name,
            'account_holder' => $method->account_holder,
            'account_number' => $method->account_number,
            'iban' => $method->iban,
            'bic_swift' => $method->bic_swift,
            'bank_address' => $method->bank_address,
            'description' => $method->description,
            'is_default' => $method->is_default ? '1' : '0',
            'status' => $method->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'payment-methods'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.payment_methods') }}</h1>
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

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.payment_methods') }}</h1>
                        <p>{{ __('main.payment_methods_subtitle') }}</p>
                    </div>
                    @if ($paymentMethodPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#paymentMethodModal" data-payment-method-mode="create">
                            <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                            {{ __('main.new_payment_method') }}
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

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $paymentMethods->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table payment-method-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.payment_method_type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.currency') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.bank_information') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.default_payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($paymentMethods as $method)
                                    @php
                                        $bankSummary = $method->type === \App\Models\AccountingPaymentMethod::TYPE_BANK
                                            ? trim(collect([$method->bank_name, $method->account_number])->filter()->implode(' - '))
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>{{ ($paymentMethods->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>
                                            <strong>{{ $method->name }}</strong>
                                            @if ($method->code)
                                                <small class="d-block text-muted">{{ $method->code }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $typeLabels[$method->type] ?? $method->type }}</td>
                                        <td>{{ $method->currency_code }}</td>
                                        <td>{{ $bankSummary !== '' ? ($bankSummary ?: '-') : '-' }}</td>
                                        <td>
                                            <span class="status-pill {{ $method->is_default ? 'payment-method-status-default' : 'payment-method-status-secondary' }}">
                                                {{ $method->is_default ? __('main.yes') : __('main.no') }}
                                            </span>
                                        </td>
                                        <td><span class="status-pill payment-method-status-{{ $method->status }}">{{ $statusLabels[$method->status] ?? $method->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($method->is_system_default)
                                                    <button type="button" class="table-button table-button-history" data-bs-toggle="modal" data-bs-target="#paymentMethodReceiptsModal{{ $method->id }}" aria-label="{{ __('main.view_receipts') }}" title="{{ __('main.view_receipts') }}">
                                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="table-button table-button-print" disabled aria-disabled="true" aria-label="{{ __('main.disbursements_coming_soon') }}" title="{{ __('main.disbursements_coming_soon') }}">
                                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#paymentMethodModal" data-payment-method-mode="view" data-payment-method-id="{{ $method->id }}" data-payment-method-values="{{ base64_encode(json_encode($paymentMethodPayload($method))) }}" aria-label="{{ __('main.view_details') }}">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="table-button table-button-history" data-bs-toggle="modal" data-bs-target="#paymentMethodReceiptsModal{{ $method->id }}" aria-label="{{ __('main.view_receipts') }}" title="{{ __('main.view_receipts') }}">
                                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="table-button table-button-print" disabled aria-disabled="true" aria-label="{{ __('main.disbursements_coming_soon') }}" title="{{ __('main.disbursements_coming_soon') }}">
                                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                                    </button>
                                                    @if ($paymentMethodPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#paymentMethodModal" data-payment-method-mode="edit" data-payment-method-action="{{ route('main.accounting.payment-methods.update', [$company, $site, $method]) }}" data-payment-method-id="{{ $method->id }}" data-payment-method-values="{{ base64_encode(json_encode($paymentMethodPayload($method))) }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($paymentMethodPermissions['can_delete'] && ! $method->is_default && (int) $method->sales_invoice_payments_count === 0 && (int) $method->other_incomes_count === 0)
                                                        <form method="POST" action="{{ route('main.accounting.payment-methods.destroy', [$company, $site, $method]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_payment_method_title') }}" data-delete-text="{{ __('main.delete_payment_method_text', ['name' => $method->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_payment_methods') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($paymentMethods->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $paymentMethods->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $paymentMethods->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($paymentMethods->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $paymentMethods->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($paymentMethods->getUrlRange(1, $paymentMethods->lastPage()) as $page => $url)
                                @if ($page === $paymentMethods->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($paymentMethods->hasMorePages())<a href="{{ $paymentMethods->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($paymentMethods as $method)
        @php
            $methodReceiptsCount = (int) $method->salesInvoicePayments->count() + (int) $method->otherIncomes->count();
            $methodReceiptsTotal = (float) ($method->receipts_total ?? $method->salesInvoicePayments->sum('amount')) + (float) ($method->other_incomes_total ?? $method->otherIncomes->sum('amount'));
        @endphp
        <div class="modal fade subscription-modal related-table-modal payment-method-receipts-modal" id="paymentMethodReceiptsModal{{ $method->id }}" tabindex="-1" aria-labelledby="paymentMethodReceiptsModal{{ $method->id }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content admin-form modal-table-dialog">
                    <div class="modal-body" data-payment-method-receipts-table>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <h2 id="paymentMethodReceiptsModal{{ $method->id }}Label">
                            <i class="bi bi-cash-coin" aria-hidden="true"></i>
                            {{ __('main.payment_method_receipts_title', ['name' => $method->name]) }}
                        </h2>

                        <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                            <label class="search-box">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" data-payment-method-receipts-search placeholder="{{ __('admin.search') }}" autocomplete="off">
                            </label>
                            <span class="row-count">
                                <strong data-payment-method-receipts-visible-count>{{ $methodReceiptsCount }}</strong>
                                /
                                <strong data-payment-method-receipts-total-count>{{ $methodReceiptsCount }}</strong>
                                {{ __('admin.rows') }}
                            </span>
                        </section>

                        <div class="modal-total-strip">
                            <span>{{ __('main.payment_method_receipts_total') }}</span>
                            <strong>{{ number_format($methodReceiptsTotal, 2, ',', ' ') }} {{ $method->currency_code }}</strong>
                        </div>

                        <div class="modal-table-frame">
                            <table class="company-table modal-data-table">
                                <thead>
                                    <tr>
                                        <th><button class="table-sort" type="button" data-payment-method-receipts-sort="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-payment-method-receipts-sort="1">{{ __('main.sales_invoice') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-payment-method-receipts-sort="2">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-payment-method-receipts-sort="3" data-sort-type="date">{{ __('main.payment_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th class="text-end"><button class="table-sort" type="button" data-payment-method-receipts-sort="4" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-payment-method-receipts-sort="5">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-payment-method-receipts-sort="6">{{ __('main.received_by') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    </tr>
                                </thead>
                                <tbody data-payment-method-receipts-body>
                                    @foreach ($method->salesInvoicePayments->sortByDesc('payment_date')->values() as $payment)
                                        <tr data-payment-method-receipt-row>
                                            <td data-sort-value="{{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                            <td>{{ $payment->salesInvoice?->reference ?? '-' }}</td>
                                            <td>{{ $payment->salesInvoice?->client?->display_name ?? '-' }}</td>
                                            <td data-sort-value="{{ optional($payment->payment_date)->format('Y-m-d') }}">{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                                            <td class="amount-cell text-end" data-sort-value="{{ $payment->amount }}">{{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                            <td>{{ $payment->reference ?: '-' }}</td>
                                            <td>{{ $payment->receiver?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($method->otherIncomes->sortByDesc('income_date')->values() as $income)
                                        <tr data-payment-method-receipt-row>
                                            <td data-sort-value="{{ $method->salesInvoicePayments->count() + $loop->iteration }}">{{ $method->salesInvoicePayments->count() + $loop->iteration }}</td>
                                            <td>{{ __('main.other_income') }}</td>
                                            <td>{{ $income->label }}</td>
                                            <td data-sort-value="{{ optional($income->income_date)->format('Y-m-d') }}">{{ optional($income->income_date)->format('d/m/Y') }}</td>
                                            <td class="amount-cell text-end" data-sort-value="{{ $income->amount }}">{{ number_format((float) $income->amount, 2, ',', ' ') }} {{ $income->currency }}</td>
                                            <td>{{ $income->payment_reference ?: $income->reference }}</td>
                                            <td>{{ $income->creator?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="modal-table-empty" data-payment-method-receipts-empty hidden>{{ __('main.no_payment_method_receipts') }}</p>
                        </div>

                        <section class="subscriptions-pagination modal-table-pagination" data-payment-method-receipts-pagination data-previous-label="{{ __('admin.previous') }}" data-next-label="{{ __('admin.next') }}" data-showing-label="{{ __('admin.showing') }}" data-to-label="{{ __('admin.to') }}" data-on-label="{{ __('admin.on') }}" hidden aria-label="{{ __('admin.pagination') }}">
                            <span data-payment-method-receipts-pagination-count></span>
                            <nav class="pagination-shell" data-payment-method-receipts-pagination-nav aria-label="{{ __('admin.pagination') }}"></nav>
                        </section>

                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade subscription-modal accounting-payment-method-modal" id="paymentMethodModal" tabindex="-1" aria-labelledby="paymentMethodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form payment-method-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.accounting.payment-methods.store', [$company, $site]) }}" data-title-create="{{ __('main.new_payment_method') }}" data-title-edit="{{ __('main.edit_payment_method') }}" data-title-view="{{ __('main.view_payment_method') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-cancel-label="{{ __('admin.cancel') }}" data-close-label="{{ __('admin.close') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="paymentMethodHttpMethod" value="PUT" @disabled(! $isEditingPaymentMethod)>
                <input type="hidden" name="form_mode" id="paymentMethodFormMode" value="{{ $isEditingPaymentMethod ? 'edit' : 'create' }}">
                <input type="hidden" name="payment_method_id" id="paymentMethodId" value="{{ old('payment_method_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="paymentMethodModalLabel"><i class="bi bi-credit-card-2-front" aria-hidden="true"></i>{{ $isEditingPaymentMethod ? __('main.edit_payment_method') : __('main.new_payment_method') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="payment_method_name" class="form-label">{{ __('main.payment_method') }} *</label>
                            <input id="payment_method_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.payment_method_name_placeholder') }}" data-payment-method-field data-default-value="">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method_type" class="form-label">{{ __('main.payment_method_type') }} *</label>
                            <select id="payment_method_type" name="type" class="form-select @error('type') is-invalid @enderror" data-payment-method-field data-default-value="{{ \App\Models\AccountingPaymentMethod::TYPE_CASH }}">
                                @foreach ($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', \App\Models\AccountingPaymentMethod::TYPE_CASH) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method_currency" class="form-label">{{ __('main.currency') }} *</label>
                            <select id="payment_method_currency" name="currency_code" class="form-select @error('currency_code') is-invalid @enderror" data-payment-method-field data-default-value="{{ $defaultCurrencyCode }}">
                                @foreach ($currencyOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency_code', $defaultCurrencyCode) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('currency_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method_code" class="form-label">{{ __('main.internal_code') }}</label>
                            <input id="payment_method_code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="{{ __('main.internal_code_placeholder') }}" data-payment-method-field data-default-value="">
                            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="payment_method_status" name="status" class="form-select @error('status') is-invalid @enderror" data-payment-method-field data-default-value="active">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="form-check form-switch form-toggle">
                                <input type="hidden" name="is_default" value="0">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" @checked(old('is_default') === '1') data-payment-method-field data-default-value="0">
                                <span>{{ __('main.set_as_default') }}</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label for="payment_method_description" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="payment_method_description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.payment_method_description_placeholder') }}" data-payment-method-field data-default-value="">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="payment-method-bank-section" data-bank-fields @hidden(old('type', \App\Models\AccountingPaymentMethod::TYPE_CASH) !== \App\Models\AccountingPaymentMethod::TYPE_BANK)>
                        <div class="form-section-title">
                            <span><i class="bi bi-bank" aria-hidden="true"></i> {{ __('main.bank_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="payment_bank_name" class="form-label">{{ __('main.bank_name') }}</label>
                                <input id="payment_bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="{{ __('main.bank_name_placeholder') }}" data-payment-method-field data-default-value="">
                                @error('bank_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_account_holder" class="form-label">{{ __('main.account_holder') }}</label>
                                <input id="payment_account_holder" name="account_holder" class="form-control @error('account_holder') is-invalid @enderror" value="{{ old('account_holder') }}" placeholder="{{ __('main.account_holder_placeholder') }}" data-payment-method-field data-default-value="">
                                @error('account_holder')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_account_number" class="form-label">{{ __('main.account_number') }}</label>
                                <input id="payment_account_number" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="{{ __('main.account_number_placeholder') }}" data-payment-method-field data-default-value="">
                                @error('account_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_iban" class="form-label">{{ __('main.iban') }}</label>
                                <input id="payment_iban" name="iban" class="form-control @error('iban') is-invalid @enderror" value="{{ old('iban') }}" placeholder="{{ __('main.iban_placeholder') }}" data-payment-method-field data-default-value="">
                                @error('iban')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_bic_swift" class="form-label">{{ __('main.bic_swift') }}</label>
                                <input id="payment_bic_swift" name="bic_swift" class="form-control @error('bic_swift') is-invalid @enderror" value="{{ old('bic_swift') }}" placeholder="{{ __('main.bic_swift_placeholder') }}" data-payment-method-field data-default-value="">
                                @error('bic_swift')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_bank_address" class="form-label">{{ __('main.bank_address') }}</label>
                                <input id="payment_bank_address" name="bank_address" class="form-control @error('bank_address') is-invalid @enderror" value="{{ old('bank_address') }}" placeholder="{{ __('main.bank_address_placeholder') }}" data-payment-method-field data-default-value="">
                                @error('bank_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" id="paymentMethodCancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="paymentMethodSubmit" type="submit">{{ $isEditingPaymentMethod ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasPaymentMethodErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentMethodModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-payment-methods.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-payment-methods.js')) !!}</script>
    <script>
        (() => {
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

            const initReceiptsTable = (wrapper) => {
                if (!wrapper || wrapper.dataset.paymentMethodReceiptsTableBound === 'true') {
                    return;
                }

                wrapper.dataset.paymentMethodReceiptsTableBound = 'true';

                const rows = Array.from(wrapper.querySelectorAll('[data-payment-method-receipt-row]'));
                const search = wrapper.querySelector('[data-payment-method-receipts-search]');
                const body = wrapper.querySelector('[data-payment-method-receipts-body]');
                const empty = wrapper.querySelector('[data-payment-method-receipts-empty]');
                const visibleCount = wrapper.querySelector('[data-payment-method-receipts-visible-count]');
                const totalCount = wrapper.querySelector('[data-payment-method-receipts-total-count]');
                const pagination = wrapper.querySelector('[data-payment-method-receipts-pagination]');
                const paginationCount = wrapper.querySelector('[data-payment-method-receipts-pagination-count]');
                const paginationNav = wrapper.querySelector('[data-payment-method-receipts-pagination-nav]');
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

                            if (leftValue < rightValue) return sortDirection === 'asc' ? -1 : 1;
                            if (leftValue > rightValue) return sortDirection === 'asc' ? 1 : -1;
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
                            <button type="button" ${page === 1 ? 'disabled' : ''} data-payment-method-receipts-page="${page - 1}">${previousLabel}</button>
                            ${Array.from({ length: totalPages }, (_, index) => {
                                const currentPage = index + 1;
                                return `<button type="button" class="${currentPage === page ? 'active' : ''}" data-payment-method-receipts-page="${currentPage}">${currentPage}</button>`;
                            }).join('')}
                            <button type="button" ${page === totalPages ? 'disabled' : ''} data-payment-method-receipts-page="${page + 1}">${nextLabel}</button>
                        `;
                    } else {
                        pagination.hidden = true;
                    }
                };

                search?.addEventListener('input', () => {
                    page = 1;
                    render();
                });

                wrapper.querySelectorAll('[data-payment-method-receipts-sort]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const nextIndex = Number(button.dataset.paymentMethodReceiptsSort);
                        sortDirection = sortIndex === nextIndex && sortDirection === 'asc' ? 'desc' : 'asc';
                        sortIndex = nextIndex;
                        sortType = button.dataset.sortType || 'text';
                        page = 1;

                        wrapper.querySelectorAll('[data-payment-method-receipts-sort]').forEach((sortButton) => {
                            sortButton.classList.remove('is-sorted-asc', 'is-sorted-desc');
                        });

                        button.classList.add(sortDirection === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
                        render();
                    });
                });

                pagination?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-payment-method-receipts-page]');

                    if (!button || button.disabled) {
                        return;
                    }

                    page = Number(button.dataset.paymentMethodReceiptsPage || '1');
                    render();
                });

                render();
            };

            const initAllReceiptsTables = () => {
                document.querySelectorAll('[data-payment-method-receipts-table]').forEach(initReceiptsTable);
            };

            initAllReceiptsTables();
            document.addEventListener('exad:table-updated', initAllReceiptsTables);
            document.addEventListener('shown.bs.modal', (event) => {
                initReceiptsTable(event.target.querySelector('[data-payment-method-receipts-table]'));
            });
        })();
    </script>
</body>
</html>

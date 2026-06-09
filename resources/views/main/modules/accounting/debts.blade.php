<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.debts') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.debts', [$company, $site]);
        $totalRecords = $debts->total();
        $defaultCurrencyCode = old('currency', $site->currency ?: array_key_first($currencies) ?: 'CDF');
        $isEditingDebt = old('form_mode') === 'edit' && old('creditor_id');
        $debtFormAction = $isEditingDebt
            ? route('main.accounting.debts.update', [$company, $site, old('creditor_id')])
            : route('main.accounting.debts.store', [$company, $site]);
        $formatMoney = fn ($amount, $currency) => number_format((float) $amount, 2, ',', ' ').' '.$currency;
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'debts'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.debts')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.debts') }}</h1>
                        <p>{{ __('main.debts_subtitle') }}</p>
                    </div>
                    @if ($debtPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#creditorModal" data-creditor-mode="create">
                            <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            {{ __('main.new_debt') }}
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
                    <span>{{ __('main.total_debts_due') }}</span>
                    <strong>{{ $formatMoney($totalBalance, $site->currency ?: $defaultCurrencyCode) }}</strong>
                </div>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="debtSourceFilter" class="form-label">{{ __('main.debt_source') }}</label>
                                <select id="debtSourceFilter" name="source" class="form-select">
                                    <option value="">{{ __('main.all_sources') }}</option>
                                    <option value="manual" @selected($filters['source'] === 'manual')>{{ __('main.manual_debt') }}</option>
                                    <option value="purchase" @selected($filters['source'] === 'purchase')>{{ __('main.supplier_purchase') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="debtStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="debtStatusFilter" name="status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                    @foreach ($purchaseStatusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="debtCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="debtCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="debtDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="debtDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['dateFrom'] }}">
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="debtDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="debtDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['dateTo'] }}">
                            </div>
                            <div class="col-12 d-flex align-items-end justify-content-end gap-2 receipt-filter-actions">
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
                        <strong id="visibleCount">{{ $debts->count() }}</strong>
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
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.source') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.creditor') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.paid_amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.balance_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($debts as $debt)
                                    @php
                                        $record = $debt['model'];
                                        $isManual = $debt['source'] === 'manual';
                                        $canPay = $debtPermissions['can_update'] && (float) $debt['balance'] > 0;
                                    @endphp
                                    <tr>
                                        <td>{{ ($debts->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>{{ $isManual ? __('main.manual_debt') : __('main.supplier_purchase') }}</td>
                                        <td><span class="status-pill reference-pill">{{ $debt['reference'] }}</span></td>
                                        <td>
                                            <strong>{{ $debt['third_party'] }}</strong>
                                            <small class="d-block text-muted">{{ $debt['type'] }}</small>
                                        </td>
                                        <td>{{ $debt['due_date'] ? \Illuminate\Support\Carbon::parse($debt['due_date'])->format('d/m/Y') : '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $debt['paid'] }}">{{ $formatMoney($debt['paid'], $debt['currency']) }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $debt['balance'] }}">{{ $formatMoney($debt['balance'], $debt['currency']) }}</td>
                                        <td><span class="status-pill {{ $isManual ? 'creditor-status-'.$debt['raw_status'] : 'purchase-status-'.$debt['raw_status'] }}">{{ $debt['status'] }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-history" data-bs-toggle="modal" data-bs-target="#debtPaymentsModal{{ $debt['source'] }}{{ $debt['id'] }}" aria-label="{{ __('main.view_payments') }}" title="{{ __('main.view_payments') }}">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </button>
                                                @if ($canPay)
                                                    <button type="button" class="table-button table-button-confirm" data-bs-toggle="modal" data-bs-target="#debtPaymentModal{{ $debt['source'] }}{{ $debt['id'] }}" aria-label="{{ __('main.add_payment') }}" title="{{ __('main.add_payment') }}">
                                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($isManual && $debtPermissions['can_update'])
                                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#creditorModal" data-creditor-mode="edit" data-creditor-action="{{ route('main.accounting.debts.update', [$company, $site, $record]) }}" data-creditor-id="{{ $record->id }}" data-creditor-type="{{ $record->type }}" data-creditor-name="{{ $record->name }}" data-creditor-phone="{{ $record->phone }}" data-creditor-email="{{ $record->email }}" data-creditor-address="{{ $record->address }}" data-creditor-currency="{{ $record->currency }}" data-creditor-initial-amount="{{ $record->initial_amount }}" data-creditor-paid-amount="{{ $record->paid_amount }}" data-creditor-due-date="{{ $record->due_date?->format('Y-m-d') }}" data-creditor-description="{{ $record->description }}" data-creditor-priority="{{ $record->priority }}" data-creditor-status="{{ $record->status }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($isManual && $debtPermissions['can_delete'] && $record->payments->isEmpty())
                                                    <form method="POST" action="{{ route('main.accounting.debts.destroy', [$company, $site, $record]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_debt_title') }}" data-delete-text="{{ __('main.delete_debt_text', ['name' => $record->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.no_debts') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($debts->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $debts->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $debts->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($debts->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $debts->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($debts->getUrlRange(1, $debts->lastPage()) as $page => $url)
                                @if ($page === $debts->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($debts->hasMorePages())<a href="{{ $debts->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($debts as $debt)
        @php
            $record = $debt['model'];
            $isManual = $debt['source'] === 'manual';
            $payments = $isManual ? $record->payments : $record->payments;
            $paymentAction = $isManual
                ? route('main.accounting.debts.payments.store', [$company, $site, $record])
                : route('main.accounting.purchases.payments.store', [$company, $site, $record]);
        @endphp
        <div class="modal fade subscription-modal related-table-modal" id="debtPaymentsModal{{ $debt['source'] }}{{ $debt['id'] }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content admin-form modal-table-dialog">
                    <div class="modal-body">
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <h2><i class="bi bi-clock-history" aria-hidden="true"></i>{{ __('main.debt_payments_title', ['reference' => $debt['reference']]) }}</h2>
                        <div class="modal-table-frame">
                            <table class="company-table modal-data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('main.payment_date') }}</th>
                                        <th>{{ __('main.payment_method') }}</th>
                                        <th class="text-end">{{ __('main.amount') }}</th>
                                        <th>{{ __('main.reference') }}</th>
                                        <th>{{ __('main.paid_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments->sortByDesc('payment_date')->values() as $payment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                                            <td>{{ $payment->paymentMethod?->name ?? '-' }}</td>
                                            <td class="amount-cell text-end">{{ $formatMoney($payment->amount, $payment->currency) }}</td>
                                            <td>{{ $payment->reference ?: '-' }}</td>
                                            <td>{{ $payment->payer?->name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="6">{{ __('main.no_debt_payments') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ((float) $debt['balance'] > 0)
            <div class="modal fade subscription-modal" id="debtPaymentModal{{ $debt['source'] }}{{ $debt['id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content admin-form" method="POST" action="{{ $paymentAction }}">
                        @csrf
                        @unless($isManual)
                            <input type="hidden" name="return_to" value="debts">
                        @endunless
                        <div class="modal-body">
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                            <h2><i class="bi bi-cash-coin" aria-hidden="true"></i>{{ __('main.add_debt_payment') }}</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.amount') }} *</label>
                                    <input name="amount" class="form-control" type="number" step="0.01" min="0.01" max="{{ number_format((float) $debt['balance'], 2, '.', '') }}" value="{{ number_format((float) $debt['balance'], 2, '.', '') }}" placeholder="0,00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.payment_date') }} *</label>
                                    <input name="payment_date" class="form-control" type="date" value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.payment_method') }} *</label>
                                    <select name="payment_method_id" class="form-select">
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->name }} ({{ $method->currency_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.reference') }}</label>
                                    <input name="reference" class="form-control" placeholder="{{ __('main.reference') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('main.notes') }}</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="{{ __('main.notes') }}"></textarea>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                                <button class="modal-submit" type="submit">{{ __('main.save_payment') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    <div class="modal fade subscription-modal accounting-debt-modal" id="creditorModal" tabindex="-1" aria-labelledby="creditorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form creditor-form" method="POST" action="{{ $debtFormAction }}" data-create-action="{{ route('main.accounting.debts.store', [$company, $site]) }}" data-title-create="{{ __('main.new_debt') }}" data-title-edit="{{ __('main.edit_debt') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="creditorMethod" value="PUT" @disabled(! $isEditingDebt)>
                <input type="hidden" name="form_mode" id="creditorFormMode" value="{{ $isEditingDebt ? 'edit' : 'create' }}">
                <input type="hidden" name="creditor_id" id="creditorId" value="{{ old('creditor_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="creditorModalLabel"><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i>{{ $isEditingDebt ? __('main.edit_debt') : __('main.new_debt') }}</h2>
                    <div class="row g-3">
                        <div class="col-12" data-existing-creditor-wrapper>
                            <label for="existingCreditor" class="form-label">{{ __('main.existing_creditor') }}</label>
                            <select id="existingCreditor" name="existing_creditor_id" class="form-select @error('existing_creditor_id') is-invalid @enderror" data-existing-creditor-select>
                                <option value="">{{ __('main.choose_existing_creditor') }}</option>
                                @foreach ($existingCreditors as $existingCreditor)
                                    <option value="{{ $existingCreditor->id }}" @selected((int) old('existing_creditor_id') === (int) $existingCreditor->id) data-creditor-type="{{ $existingCreditor->type }}" data-creditor-name="{{ $existingCreditor->name }}" data-creditor-phone="{{ $existingCreditor->phone }}" data-creditor-email="{{ $existingCreditor->email }}" data-creditor-address="{{ $existingCreditor->address }}" data-creditor-currency="{{ $existingCreditor->currency }}">
                                        {{ $existingCreditor->name }}{{ $existingCreditor->reference ? ' - '.$existingCreditor->reference : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text">{{ __('main.existing_creditor_help') }}</small>
                            @error('existing_creditor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorType" class="form-label">{{ __('main.creditor_type') }} *</label>
                            <select id="creditorType" name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', \App\Models\AccountingCreditor::TYPE_SUPPLIER) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorName" class="form-label">{{ __('main.creditor_name') }} *</label>
                            <input id="creditorName" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.creditor_name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorPhone" class="form-label">{{ __('main.phone') }}</label>
                            <input id="creditorPhone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorEmail" class="form-label">{{ __('main.email') }}</label>
                            <input id="creditorEmail" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="creditorAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="creditorAddress" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="creditorCurrency" class="form-label">{{ __('main.currency') }} *</label>
                            <select id="creditorCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $defaultCurrencyCode) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="creditorInitialAmount" class="form-label">{{ __('main.initial_amount') }} *</label>
                            <input id="creditorInitialAmount" name="initial_amount" type="number" step="0.01" min="0" class="form-control @error('initial_amount') is-invalid @enderror" value="{{ old('initial_amount', '0') }}" placeholder="0,00">
                            @error('initial_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="creditorPaidAmount" class="form-label">{{ __('main.paid_amount') }} *</label>
                            <input id="creditorPaidAmount" name="paid_amount" type="number" step="0.01" min="0" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', '0') }}" placeholder="0,00">
                            @error('paid_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="creditorDueDate" class="form-label">{{ __('main.due_date') }}</label>
                            <input id="creditorDueDate" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                            @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="creditorPriority" class="form-label">{{ __('main.priority') }} *</label>
                            <select id="creditorPriority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                                @foreach ($priorityLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', \App\Models\AccountingCreditor::PRIORITY_NORMAL) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="creditorStatus" class="form-label">{{ __('main.status') }} *</label>
                            <select id="creditorStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', \App\Models\AccountingCreditor::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="creditorDescription" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="creditorDescription" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.description') }}">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="creditorSubmit" type="submit">{{ $isEditingDebt ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>{!! file_get_contents(resource_path('js/main/accounting-creditors.js')) !!}</script>
    @if ($errors->any())
        <script>
            (() => {
                const modal = document.getElementById('creditorModal');
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            })();
        </script>
    @endif
</body>
</html>

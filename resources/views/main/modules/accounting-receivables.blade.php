<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.receivables') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.receivables', [$company, $site]);
        $totalRecords = $receivables->total();
        $defaultCurrencyCode = old('currency', $site->currency ?: array_key_first($currencies) ?: 'CDF');
        $isEditingReceivable = old('form_mode') === 'edit' && old('debtor_id');
        $receivableFormAction = $isEditingReceivable
            ? route('main.accounting.receivables.update', [$company, $site, old('debtor_id')])
            : route('main.accounting.receivables.store', [$company, $site]);
        $formatMoney = fn ($amount, $currency) => number_format((float) $amount, 2, ',', ' ').' '.$currency;
        $hasReceivableErrors = $errors->any() && old('form_mode');
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'receivables'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.receivables')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.receivables') }}</h1>
                        <p>{{ __('main.receivables_subtitle') }}</p>
                    </div>
                    @if ($receivablePermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#debtorModal" data-debtor-mode="create">
                            <i class="bi bi-arrow-down-left" aria-hidden="true"></i>
                            {{ __('main.new_receivable') }}
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
                    <span>{{ __('main.total_receivables_due') }}</span>
                    <strong>{{ $formatMoney($totalBalance, $site->currency ?: $defaultCurrencyCode) }}</strong>
                </div>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="receivableSourceFilter" class="form-label">{{ __('main.receivable_source') }}</label>
                                <select id="receivableSourceFilter" name="source" class="form-select">
                                    <option value="">{{ __('main.all_sources') }}</option>
                                    <option value="manual" @selected($filters['source'] === 'manual')>{{ __('main.manual_receivable') }}</option>
                                    <option value="invoice" @selected($filters['source'] === 'invoice')>{{ __('main.sales_invoice') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="receivableStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="receivableStatusFilter" name="status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                    @foreach ($invoiceStatusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="receivableCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="receivableCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="receivableDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="receivableDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['dateFrom'] }}">
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="receivableDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="receivableDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['dateTo'] }}">
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
                        <strong id="visibleCount">{{ $receivables->count() }}</strong>
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
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.debtor') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.received_amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.amount_receivable') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($receivables as $receivable)
                                    @php
                                        $record = $receivable['model'];
                                        $isManual = $receivable['source'] === 'manual';
                                        $canPay = $receivablePermissions['can_update'] && (float) $receivable['balance'] > 0;
                                    @endphp
                                    <tr>
                                        <td>{{ ($receivables->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>{{ $isManual ? __('main.manual_receivable') : __('main.sales_invoice') }}</td>
                                        <td><span class="status-pill reference-pill">{{ $receivable['reference'] }}</span></td>
                                        <td>
                                            <strong>{{ $receivable['third_party'] }}</strong>
                                            <small class="d-block text-muted">{{ $receivable['type'] }}</small>
                                        </td>
                                        <td>{{ $receivable['due_date'] ? \Illuminate\Support\Carbon::parse($receivable['due_date'])->format('d/m/Y') : '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $receivable['received'] }}">{{ $formatMoney($receivable['received'], $receivable['currency']) }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $receivable['balance'] }}">{{ $formatMoney($receivable['balance'], $receivable['currency']) }}</td>
                                        <td><span class="status-pill {{ $isManual ? 'debtor-status-'.$receivable['raw_status'] : 'sales-invoice-status-'.$receivable['raw_status'] }}">{{ $receivable['status'] }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-history" data-bs-toggle="modal" data-bs-target="#receivablePaymentsModal{{ $receivable['source'] }}{{ $receivable['id'] }}" aria-label="{{ __('main.view_payments') }}" title="{{ __('main.view_payments') }}">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </button>
                                                @if ($canPay)
                                                    <button type="button" class="table-button table-button-confirm" data-bs-toggle="modal" data-bs-target="#receivablePaymentModal{{ $receivable['source'] }}{{ $receivable['id'] }}" aria-label="{{ __('main.add_payment') }}" title="{{ __('main.add_payment') }}">
                                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($isManual && $receivablePermissions['can_update'])
                                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#debtorModal" data-debtor-mode="edit" data-debtor-action="{{ route('main.accounting.receivables.update', [$company, $site, $record]) }}" data-debtor-id="{{ $record->id }}" data-debtor-type="{{ $record->type }}" data-debtor-name="{{ $record->name }}" data-debtor-phone="{{ $record->phone }}" data-debtor-email="{{ $record->email }}" data-debtor-address="{{ $record->address }}" data-debtor-currency="{{ $record->currency }}" data-debtor-initial-amount="{{ $record->initial_amount }}" data-debtor-received-amount="{{ $record->received_amount }}" data-debtor-due-date="{{ $record->due_date?->format('Y-m-d') }}" data-debtor-description="{{ $record->description }}" data-debtor-status="{{ $record->status }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($isManual && $receivablePermissions['can_delete'] && $record->payments->isEmpty())
                                                    <form method="POST" action="{{ route('main.accounting.receivables.destroy', [$company, $site, $record]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_receivable_title') }}" data-delete-text="{{ __('main.delete_receivable_text', ['name' => $record->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.no_receivables') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($receivables->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $receivables->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $receivables->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($receivables->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $receivables->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($receivables->getUrlRange(1, $receivables->lastPage()) as $page => $url)
                                @if ($page === $receivables->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($receivables->hasMorePages())<a href="{{ $receivables->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($receivables as $receivable)
        @php
            $record = $receivable['model'];
            $isManual = $receivable['source'] === 'manual';
            $payments = $record->payments;
            $paymentAction = $isManual
                ? route('main.accounting.receivables.payments.store', [$company, $site, $record])
                : route('main.accounting.sales-invoices.payments.store', [$company, $site, $record]);
        @endphp
        <div class="modal fade subscription-modal related-table-modal" id="receivablePaymentsModal{{ $receivable['source'] }}{{ $receivable['id'] }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content admin-form modal-table-dialog">
                    <div class="modal-body">
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <h2><i class="bi bi-clock-history" aria-hidden="true"></i>{{ __('main.receivable_payments_title', ['reference' => $receivable['reference']]) }}</h2>
                        <div class="modal-table-frame">
                            <table class="company-table modal-data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('main.payment_date') }}</th>
                                        <th>{{ __('main.payment_method') }}</th>
                                        <th class="text-end">{{ __('main.amount') }}</th>
                                        <th>{{ __('main.reference') }}</th>
                                        <th>{{ __('main.received_by') }}</th>
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
                                            <td>{{ $payment->receiver?->name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="6">{{ __('main.no_receivable_payments') }}</td></tr>
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

        @if ((float) $receivable['balance'] > 0)
            <div class="modal fade subscription-modal" id="receivablePaymentModal{{ $receivable['source'] }}{{ $receivable['id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content admin-form" method="POST" action="{{ $paymentAction }}">
                        @csrf
                        @unless($isManual)
                            <input type="hidden" name="return_to" value="receivables">
                        @endunless
                        <div class="modal-body">
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                            <h2><i class="bi bi-cash-coin" aria-hidden="true"></i>{{ __('main.add_receivable_payment') }}</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.amount') }} *</label>
                                    <input name="amount" class="form-control" type="number" step="0.01" min="0.01" max="{{ number_format((float) $receivable['balance'], 2, '.', '') }}" value="{{ number_format((float) $receivable['balance'], 2, '.', '') }}" placeholder="0,00">
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

    <div class="modal fade subscription-modal accounting-debt-modal" id="debtorModal" tabindex="-1" aria-labelledby="debtorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form debtor-form" method="POST" action="{{ $receivableFormAction }}" data-create-action="{{ route('main.accounting.receivables.store', [$company, $site]) }}" data-title-create="{{ __('main.new_receivable') }}" data-title-edit="{{ __('main.edit_receivable') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="debtorMethod" value="PUT" @disabled(! $isEditingReceivable)>
                <input type="hidden" name="form_mode" id="debtorFormMode" value="{{ $isEditingReceivable ? 'edit' : 'create' }}">
                <input type="hidden" name="debtor_id" id="debtorId" value="{{ old('debtor_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="debtorModalLabel"><i class="bi bi-arrow-down-left-circle" aria-hidden="true"></i>{{ $isEditingReceivable ? __('main.edit_receivable') : __('main.new_receivable') }}</h2>
                    <div class="row g-3">
                        <div class="col-12" data-existing-debtor-wrapper>
                            <label for="existingDebtor" class="form-label">{{ __('main.existing_debtor') }}</label>
                            <select id="existingDebtor" name="existing_debtor_id" class="form-select @error('existing_debtor_id') is-invalid @enderror" data-existing-debtor-select>
                                <option value="">{{ __('main.choose_existing_debtor') }}</option>
                                @foreach ($existingDebtors as $existingDebtor)
                                    <option value="{{ $existingDebtor->id }}" @selected((int) old('existing_debtor_id') === (int) $existingDebtor->id) data-debtor-type="{{ $existingDebtor->type }}" data-debtor-name="{{ $existingDebtor->name }}" data-debtor-phone="{{ $existingDebtor->phone }}" data-debtor-email="{{ $existingDebtor->email }}" data-debtor-address="{{ $existingDebtor->address }}" data-debtor-currency="{{ $existingDebtor->currency }}">
                                        {{ $existingDebtor->name }}{{ $existingDebtor->reference ? ' - '.$existingDebtor->reference : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text">{{ __('main.existing_debtor_help') }}</small>
                            @error('existing_debtor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorType" class="form-label">{{ __('main.debtor_type') }} *</label>
                            <select id="debtorType" name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', \App\Models\AccountingDebtor::TYPE_CLIENT) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorName" class="form-label">{{ __('main.debtor_name') }} *</label>
                            <input id="debtorName" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.debtor_name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorPhone" class="form-label">{{ __('main.phone') }}</label>
                            <input id="debtorPhone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorEmail" class="form-label">{{ __('main.email') }}</label>
                            <input id="debtorEmail" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="debtorAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="debtorAddress" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="debtorCurrency" class="form-label">{{ __('main.currency') }} *</label>
                            <select id="debtorCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $defaultCurrencyCode) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="debtorInitialAmount" class="form-label">{{ __('main.initial_amount') }} *</label>
                            <input id="debtorInitialAmount" name="initial_amount" type="number" step="0.01" min="0" class="form-control @error('initial_amount') is-invalid @enderror" value="{{ old('initial_amount', '0') }}" placeholder="0,00">
                            @error('initial_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="debtorReceivedAmount" class="form-label">{{ __('main.received_amount') }} *</label>
                            <input id="debtorReceivedAmount" name="received_amount" type="number" step="0.01" min="0" class="form-control @error('received_amount') is-invalid @enderror" value="{{ old('received_amount', '0') }}" placeholder="0,00">
                            @error('received_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorDueDate" class="form-label">{{ __('main.due_date') }}</label>
                            <input id="debtorDueDate" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                            @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorStatus" class="form-label">{{ __('main.status') }} *</label>
                            <select id="debtorStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', \App\Models\AccountingDebtor::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="debtorDescription" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="debtorDescription" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.description') }}">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="debtorSubmit" type="submit">{{ $isEditingReceivable ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>{!! file_get_contents(resource_path('js/main/accounting-debtors.js')) !!}</script>
    @if ($hasReceivableErrors)
        <script>
            (() => {
                const modal = document.getElementById('debtorModal');
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            })();
        </script>
    @endif
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.cash_register') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>
        .pos-page {
            display: grid;
            gap: 1rem;
        }

        .pos-register {
            display: grid;
            grid-template-columns: minmax(280px, 1.1fr) minmax(340px, .95fr) minmax(270px, .72fr);
            gap: 1.25rem;
            margin-top: 1.25rem;
            min-height: 680px;
        }

        .pos-ticket-history-card {
            margin-top: 1.25rem;
            margin-bottom: 1.25rem;
            padding: 1rem;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
        }

        .pos-ticket-history-title {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            margin: 0 0 1rem;
            color: var(--ink);
            font-size: 1rem;
            font-weight: 900;
        }

        .pos-ticket-history-card .table-tools {
            margin-bottom: 1rem;
        }

        .pos-ticket-table-shell {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 12px;
        }

        .pos-ticket-table-shell .table-responsive {
            margin: 0;
        }

        .pos-ticket-history-card .subscriptions-pagination {
            margin-top: 1rem;
            margin-bottom: 0;
        }

        .cash-session-grid {
            display: grid;
            grid-template-columns: minmax(280px, .9fr) minmax(320px, 1.1fr);
            gap: 1rem;
        }

        .cash-session-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            padding: 1rem;
        }

        .cash-session-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .cash-session-header h2 {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            margin: 0;
            color: var(--ink);
            font-size: 1rem;
            font-weight: 900;
        }

        .cash-session-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .35rem .65rem;
            color: #047857;
            background: #d1fae5;
            font-size: .72rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .cash-session-status.closed {
            color: #475569;
            background: #eef2f7;
        }

        .cash-session-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .cash-session-metric {
            min-width: 0;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #f8fbff;
            padding: .85rem;
        }

        .cash-session-metric span {
            display: block;
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
        }

        .cash-session-metric strong {
            display: block;
            margin-top: .45rem;
            color: var(--ink);
            font-size: 1rem;
            font-weight: 950;
        }

        .cash-session-form {
            display: grid;
            gap: .85rem;
        }

        .cash-session-form .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .cash-session-note {
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e3a8a;
            padding: .85rem;
            font-weight: 800;
        }

        .cash-session-alert {
            border: 1px solid #fde68a;
            border-radius: 12px;
            background: #fffbeb;
            color: #92400e;
            padding: .85rem 1rem;
            font-weight: 800;
        }

        .pos-panel {
            display: flex;
            min-width: 0;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
        }

        .pos-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--line);
        }

        .pos-panel-title {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            margin: 0;
            font-size: .95rem;
            font-weight: 850;
        }

        .pos-panel-body {
            flex: 1;
            min-height: 0;
            overflow: auto;
            padding: 1rem;
        }

        .pos-product-search {
            margin-bottom: 1.25rem;
        }

        .pos-product-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .85rem;
        }

        .pos-product-crumbs {
            display: flex;
            min-width: 0;
            flex-wrap: wrap;
            gap: .4rem;
            color: var(--muted);
            font-size: .76rem;
            font-weight: 800;
        }

        .pos-product-crumbs span {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .pos-nav-button {
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            gap: .4rem;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #f8fbff;
            color: var(--blue-600);
            font-size: .78rem;
            font-weight: 900;
            padding: .45rem .65rem;
        }

        .pos-nav-button[hidden] {
            display: none;
        }

        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: .8rem;
        }

        .pos-category-card,
        .pos-subcategory-card,
        .pos-product-card {
            display: grid;
            gap: .55rem;
            min-height: 148px;
            padding: .9rem;
            border: 1px solid #d8e4f4;
            border-radius: 12px;
            background: #f8fbff;
            color: var(--ink);
            text-align: left;
        }

        .pos-category-card,
        .pos-subcategory-card {
            align-content: space-between;
        }

        .pos-category-card {
            background: linear-gradient(135deg, #f8fbff, #eef6ff);
        }

        .pos-subcategory-card {
            background: linear-gradient(135deg, #fbfdff, #f3f7fc);
        }

        .pos-product-card:hover {
            border-color: rgba(37, 99, 235, .5);
            box-shadow: 0 14px 30px rgba(37, 99, 235, .12);
            transform: translateY(-1px);
        }

        .pos-category-card:hover,
        .pos-subcategory-card:hover {
            border-color: rgba(37, 99, 235, .5);
            box-shadow: 0 14px 30px rgba(37, 99, 235, .12);
            transform: translateY(-1px);
        }

        .pos-category-icon {
            display: inline-grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 12px;
            background: #eaf2ff;
            color: var(--blue-600);
            font-size: 1.15rem;
        }

        .pos-product-card[disabled] {
            opacity: .55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .pos-category-name {
            font-weight: 950;
            line-height: 1.35;
        }

        .pos-category-count {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pos-product-reference,
        .pos-stock {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pos-product-name {
            min-height: 38px;
            font-weight: 850;
            line-height: 1.35;
        }

        .pos-product-price {
            color: var(--blue-600);
            font-weight: 900;
        }

        .pos-sale-form {
            display: contents;
        }

        .pos-cart-panel .pos-panel-body {
            padding: 0;
        }

        .pos-sale-meta {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 150px;
            gap: .75rem;
            padding: 1rem;
            border-bottom: 1px solid var(--line);
        }

        .pos-cart-list {
            display: grid;
            max-height: 390px;
            overflow: auto;
        }

        .pos-cart-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 118px 42px;
            gap: .8rem;
            align-items: center;
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--line);
            background: #fbfdff;
        }

        .pos-cart-row:nth-child(even) {
            background: #f3f7fc;
        }

        .pos-cart-row.is-active {
            outline: 2px solid rgba(37, 99, 235, .45);
            outline-offset: -2px;
            background: #eaf2ff;
        }

        .pos-cart-row strong,
        .pos-cart-row small {
            display: block;
        }

        .pos-cart-row small {
            margin-top: .2rem;
            color: var(--muted);
            font-size: .72rem;
        }

        .pos-quantity {
            display: grid;
            grid-template-columns: 30px 1fr 30px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--surface);
        }

        .pos-quantity button {
            border: 0;
            background: #eef4ff;
            color: var(--blue-600);
            font-weight: 900;
        }

        .pos-quantity input {
            min-width: 0;
            border: 0;
            text-align: center;
            font-weight: 850;
        }

        .pos-cart-empty {
            display: grid;
            min-height: 220px;
            place-items: center;
            padding: 2rem;
            color: var(--muted);
            text-align: center;
        }

        .pos-summary {
            display: grid;
            gap: .75rem;
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid var(--line);
            background: #f8fbff;
        }

        .pos-summary-line {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            color: var(--muted);
            font-weight: 750;
        }

        .pos-summary-line.total {
            align-items: center;
            color: var(--ink);
            font-size: 1.15rem;
            font-weight: 950;
        }

        .pos-summary-line.total strong {
            color: var(--blue-600);
            font-size: 1.35rem;
        }

        .pos-payment-grid {
            display: grid;
            align-content: start;
            grid-auto-rows: max-content;
            gap: .65rem;
        }

        .pos-payment-option {
            position: relative;
        }

        .pos-payment-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .pos-payment-option span {
            display: flex;
            min-height: 58px;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .8rem .95rem;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #f8fbff;
            font-weight: 850;
        }

        .pos-payment-option input:checked + span {
            border-color: var(--blue-600);
            background: #eaf2ff;
            color: var(--blue-600);
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .12);
        }

        .pos-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .55rem;
            margin-top: .35rem;
        }

        .pos-keypad button {
            min-height: 50px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #eef4ff;
            color: var(--ink);
            font-weight: 900;
        }

        .pos-cash-payment {
            display: none;
            gap: .75rem;
            padding: .85rem;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #f8fbff;
        }

        .pos-cash-payment.is-visible {
            display: grid;
        }

        .pos-cash-payment.is-keypad-active {
            border-color: var(--blue-600);
            background: #eaf2ff;
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .12);
        }

        .pos-cash-payment .form-control[readonly] {
            background: #fff;
            font-weight: 900;
        }

        .pos-actions {
            display: grid;
            gap: .7rem;
            margin-top: .35rem;
        }

        .pos-submit {
            min-height: 62px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-weight: 950;
        }

        .pos-submit:disabled {
            cursor: not-allowed;
            filter: blur(.35px) grayscale(.25);
            opacity: .48;
            transform: none;
        }

        .pos-clear {
            min-height: 50px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fff1f2;
            color: #dc2626;
            font-weight: 850;
        }

        @media (max-width: 1199px) {
            .pos-register {
                grid-template-columns: 1fr;
            }

            .cash-session-grid,
            .cash-session-form .form-row,
            .cash-session-metrics {
                grid-template-columns: 1fr;
            }

            .pos-panel {
                min-height: 420px;
            }
        }
    </style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRecords = $tickets->total();
        $siteCurrency = $site->currency ?: 'CDF';
        $defaultCurrency = old('currency', $siteCurrency);
        $defaultTaxRate = number_format((float) $defaultTaxRate, 2, '.', '');
        $selectedPaymentMethod = old('payment_method_id', optional($paymentMethods->firstWhere('is_default', true) ?? $paymentMethods->first())->id);
        $selectedPaymentType = optional($paymentMethods->firstWhere('id', (int) $selectedPaymentMethod))->type;
        $productsPayload = $posItems->map(fn ($item) => [
            'id' => $item->id,
            'reference' => $item->reference,
            'name' => $item->name,
            'price' => (float) $item->sale_price,
            'stock' => (float) $item->current_stock,
            'currency' => $item->currency ?: $siteCurrency,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name ?: __('main.pos_uncategorized'),
            'subcategory_id' => $item->subcategory_id,
            'subcategory_name' => $item->subcategory?->name ?: __('main.pos_without_subcategory'),
        ])->values();
        $oldCartLines = collect(old('lines', []))
            ->filter(fn ($line) => ! empty($line['item_id']))
            ->map(fn ($line) => [
                'item_id' => (int) $line['item_id'],
                'quantity' => (float) ($line['quantity'] ?? 1),
            ])
            ->values();
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'cash-register'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.cash_register')])

            <section class="dashboard-content module-dashboard-page accounting-list-page pos-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="cash-session-grid" aria-label="{{ __('main.cash_register_session_cycle') }}">
                    <div class="cash-session-card">
                        <div class="cash-session-header">
                            <h2><i class="bi bi-cash-coin" aria-hidden="true"></i>{{ __('main.cash_register_session') }}</h2>
                            @if ($openCashSession)
                                <span class="cash-session-status">{{ __('main.cash_register_status_open') }}</span>
                            @else
                                <span class="cash-session-status closed">{{ __('main.cash_register_status_closed') }}</span>
                            @endif
                        </div>

                        @if ($openCashSession)
                            <div class="cash-session-metrics">
                                <div class="cash-session-metric">
                                    <span>{{ __('main.reference') }}</span>
                                    <strong>{{ $openCashSession->reference }}</strong>
                                </div>
                                <div class="cash-session-metric">
                                    <span>{{ __('main.opening_float') }}</span>
                                    <strong>{{ number_format((float) $openCashSession->opening_float, 2, ',', ' ') }} {{ $siteCurrency }}</strong>
                                </div>
                                <div class="cash-session-metric">
                                    <span>{{ __('main.expected_total_amount') }}</span>
                                    <strong>{{ number_format((float) $openSessionAmounts['total'], 2, ',', ' ') }} {{ $siteCurrency }}</strong>
                                </div>
                            </div>
                            <p class="cash-session-note">
                                {{ __('main.cash_register_opened_by_at', [
                                    'user' => $openCashSession->opener?->name ?? '-',
                                    'date' => optional($openCashSession->opened_at)->format('d/m/Y H:i'),
                                ]) }}
                            </p>
                        @else
                            <form class="cash-session-form" method="POST" action="{{ route('main.accounting.cash-register.open', [$company, $site]) }}" novalidate>
                                @csrf
                                <div>
                                    <label for="opening_float" class="form-label">{{ __('main.opening_float') }}</label>
                                    <input id="opening_float" name="opening_float" type="number" min="0" step="0.01" class="form-control @error('opening_float') is-invalid @enderror" value="{{ old('opening_float', '0') }}" placeholder="0,00">
                                    @error('opening_float')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="opening_notes" class="form-label">{{ __('main.notes') }}</label>
                                    <textarea id="opening_notes" name="opening_notes" rows="3" class="form-control @error('opening_notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('opening_notes') }}</textarea>
                                    @error('opening_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <button class="pos-submit" type="submit">{{ __('main.open_cash_register') }}</button>
                            </form>
                        @endif
                    </div>

                    <div class="cash-session-card">
                        <div class="cash-session-header">
                            <h2><i class="bi bi-clipboard-check" aria-hidden="true"></i>{{ __('main.cash_register_closing') }}</h2>
                        </div>

                        @if (! $openCashSession)
                            <div class="cash-session-alert">{{ __('main.cash_register_open_required') }}</div>
                        @elseif (! $canCloseCashSession)
                            <div class="cash-session-alert">{{ __('main.cash_register_close_admin_required') }}</div>
                        @else
                            <form class="cash-session-form" method="POST" action="{{ route('main.accounting.cash-register.close', [$company, $site, $openCashSession]) }}" novalidate>
                                @csrf
                                <div class="cash-session-metrics">
                                    <div class="cash-session-metric">
                                        <span>{{ __('main.expected_cash_amount') }}</span>
                                        <strong>{{ number_format((float) $openSessionAmounts['cash'], 2, ',', ' ') }} {{ $siteCurrency }}</strong>
                                    </div>
                                    <div class="cash-session-metric">
                                        <span>{{ __('main.expected_other_amount') }}</span>
                                        <strong>{{ number_format((float) $openSessionAmounts['other'], 2, ',', ' ') }} {{ $siteCurrency }}</strong>
                                    </div>
                                    <div class="cash-session-metric">
                                        <span>{{ __('main.cash_register_sales_count') }}</span>
                                        <strong>{{ $openSessionAmounts['sales_count'] }}</strong>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div>
                                        <label for="counted_cash_amount" class="form-label">{{ __('main.counted_cash_amount') }} *</label>
                                        <input id="counted_cash_amount" name="counted_cash_amount" type="number" min="0" step="0.01" class="form-control @error('counted_cash_amount') is-invalid @enderror" value="{{ old('counted_cash_amount') }}" placeholder="0,00">
                                        @error('counted_cash_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div>
                                        <label for="counted_other_amount" class="form-label">{{ __('main.counted_other_amount') }}</label>
                                        <input id="counted_other_amount" name="counted_other_amount" type="number" min="0" step="0.01" class="form-control @error('counted_other_amount') is-invalid @enderror" value="{{ old('counted_other_amount', '0') }}" placeholder="0,00">
                                        @error('counted_other_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="closing_notes" class="form-label">{{ __('main.closing_notes') }}</label>
                                    <textarea id="closing_notes" name="closing_notes" rows="3" class="form-control @error('closing_notes') is-invalid @enderror" placeholder="{{ __('main.closing_notes') }}">{{ old('closing_notes') }}</textarea>
                                    @error('closing_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <button class="pos-submit" type="submit">{{ __('main.close_cash_register') }}</button>
                            </form>
                        @endif
                    </div>
                </section>

                @if ($cashPermissions['can_create'] && $openCashSession)
                    <form class="admin-form pos-sale-form" method="POST" action="{{ route('main.accounting.cash-register.store', [$company, $site]) }}" data-pos-form data-products='@json($productsPayload)' data-old-cart='@json($oldCartLines)' novalidate>
                        @csrf
                        <input type="hidden" name="currency" value="{{ $defaultCurrency }}">
                        <input type="hidden" id="proformaTaxRate" name="tax_rate" value="{{ old('tax_rate', $defaultTaxRate) }}">
                        <input type="hidden" name="payment_reference" value="{{ old('payment_reference') }}">

                        <section class="pos-register" aria-label="{{ __('main.cash_register_new_sale') }}">
                            <div class="pos-panel">
                                <header class="pos-panel-header">
                                    <h2 class="pos-panel-title"><i class="bi bi-box-seam" aria-hidden="true"></i>{{ __('main.pos_products') }}</h2>
                                    <span class="row-count">{{ $posItems->count() }} {{ __('admin.rows') }}</span>
                                </header>
                                <div class="pos-panel-body">
                                    <label class="search-box pos-product-search">
                                        <i class="bi bi-search" aria-hidden="true"></i>
                                        <input type="search" data-pos-product-search placeholder="{{ __('main.search_product') }}" autocomplete="off">
                                    </label>
                                    <div class="pos-product-nav">
                                        <div class="pos-product-crumbs" data-pos-crumbs></div>
                                        <button type="button" class="pos-nav-button" data-pos-back hidden>
                                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                            {{ __('main.back') }}
                                        </button>
                                    </div>
                                    <div class="pos-product-grid" data-pos-products>
                                        <div class="pos-cart-empty">{{ __('main.no_pos_products') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="pos-panel pos-cart-panel">
                                <header class="pos-panel-header">
                                    <h2 class="pos-panel-title"><i class="bi bi-cart3" aria-hidden="true"></i>{{ __('main.pos_cart') }}</h2>
                                    <span>{{ now()->format('d/m/Y') }}</span>
                                </header>
                                <div class="pos-panel-body">
                                    <div class="pos-sale-meta">
                                        <div>
                                            <label for="cashRegisterClient" class="form-label">{{ __('main.customer') }}</label>
                                            <select id="cashRegisterClient" name="client_id" class="form-select @error('client_id') is-invalid @enderror">
                                                <option value="">{{ __('main.walk_in_customer') }}</option>
                                                @foreach ($clients as $id => $label)
                                                    <option value="{{ $id }}" @selected(old('client_id') == $id)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div>
                                            <label for="cashRegisterSaleDate" class="form-label">{{ __('main.sale_date') }} *</label>
                                            <input id="cashRegisterSaleDate" name="sale_date" type="date" class="form-control @error('sale_date') is-invalid @enderror" value="{{ old('sale_date', now()->format('Y-m-d')) }}">
                                            @error('sale_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    @error('lines')<div class="invalid-feedback d-block p-3">{{ $message }}</div>@enderror
                                    <div class="pos-cart-list" data-pos-cart>
                                        <div class="pos-cart-empty" data-pos-empty>{{ __('main.pos_cart_empty') }}</div>
                                    </div>

                                    <div class="pos-summary">
                                        <div class="pos-summary-line"><span>{{ __('main.subtotal') }}</span><strong><span data-total-subtotal>0,00</span> {{ $defaultCurrency }}</strong></div>
                                        <div class="pos-summary-line"><span>{{ __('main.vat_amount') }}</span><strong><span data-total-tax>0,00</span> {{ $defaultCurrency }}</strong></div>
                                        <div class="pos-summary-line total"><span>{{ __('main.total_to_pay') }}</span><strong><span data-total-ttc>0,00</span> {{ $defaultCurrency }}</strong></div>
                                    </div>
                                </div>
                            </div>

                            <div class="pos-panel">
                                <header class="pos-panel-header">
                                    <h2 class="pos-panel-title"><i class="bi bi-credit-card-2-front" aria-hidden="true"></i>{{ __('main.payment_method') }}</h2>
                                </header>
                                <div class="pos-panel-body pos-payment-grid">
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <label class="pos-payment-option">
                                            <input type="radio" name="payment_method_id" value="{{ $paymentMethod->id }}" data-payment-type="{{ $paymentMethod->type }}" @checked((int) $selectedPaymentMethod === (int) $paymentMethod->id)>
                                            <span>
                                                <i class="bi bi-wallet2" aria-hidden="true"></i>
                                                {{ $paymentMethod->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                    @error('payment_method_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                                    <div class="pos-cash-payment {{ $selectedPaymentType === \App\Models\AccountingPaymentMethod::TYPE_CASH ? 'is-visible' : '' }}" data-cash-payment>
                                        <div>
                                            <label for="cashPaymentTotal" class="form-label">{{ __('main.total_to_pay') }}</label>
                                            <input id="cashPaymentTotal" type="text" class="form-control" value="0,00 {{ $defaultCurrency }}" data-cash-total readonly>
                                        </div>
                                        <div>
                                            <label for="cashPaymentReceived" class="form-label">{{ __('main.amount_received') }} *</label>
                                            <input id="cashPaymentReceived" name="payment_received" type="number" min="0" step="0.01" class="form-control" value="{{ old('payment_received') }}" placeholder="0,00" data-cash-received>
                                            @error('payment_received')<div class="alert alert-info mt-2 mb-0 py-2">{{ $message }}</div>@enderror
                                        </div>
                                        <div>
                                            <label for="cashPaymentChange" class="form-label">{{ __('main.change_due') }}</label>
                                            <input id="cashPaymentChange" type="text" class="form-control" value="0,00 {{ $defaultCurrency }}" data-cash-change readonly>
                                        </div>
                                        <div class="alert alert-info mb-0 py-2" data-cash-error hidden>{{ __('main.cash_received_too_low') }}</div>
                                    </div>

                                    <div class="pos-keypad" aria-label="{{ __('main.pos_keypad') }}">
                                        @foreach ([7, 8, 9, 4, 5, 6, 1, 2, 3, 0] as $digit)
                                            <button type="button" data-pos-key="{{ $digit }}">{{ $digit }}</button>
                                        @endforeach
                                        <button type="button" data-pos-key=".">.</button>
                                        <button type="button" data-pos-clear-key>C</button>
                                    </div>

                                    <label for="cashRegisterNotes" class="form-label mt-2">{{ __('main.notes') }}</label>
                                    <textarea id="cashRegisterNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes') }}</textarea>
                                    @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                                    <div class="pos-actions">
                                        <button class="pos-submit" type="submit" data-pos-submit disabled>{{ __('main.save_cash_sale') }}</button>
                                        <button class="pos-clear" type="button" data-pos-clear>{{ __('main.cancel_sale') }}</button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>
                @endif

                <section class="pos-ticket-history-card" aria-label="{{ __('main.cash_register_tickets') }}">
                    <h2 class="pos-ticket-history-title">
                        <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                        {{ __('main.cash_register_tickets') }}
                    </h2>
                    <div class="table-tools">
                        <label class="search-box">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                        </label>
                        <span class="row-count">
                            <strong id="visibleCount">{{ $tickets->count() }}</strong>
                            /
                            <strong>{{ $totalRecords }}</strong>
                            {{ __('admin.rows') }}
                        </span>
                    </div>

                    <div class="pos-ticket-table-shell">
                        <div class="table-responsive">
                            <table class="company-table accounting-receipts-table" id="companyTable">
                                <thead>
                                    <tr>
                                        <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="date">{{ __('main.sale_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.payment_method') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.invoice_status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th class="text-end">{{ __('admin.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($tickets as $ticket)
                                        @php($payment = $ticket->payments->first())
                                        <tr>
                                            <td>{{ ($tickets->firstItem() ?? 1) + $loop->index }}</td>
                                            <td>{{ $ticket->reference }}</td>
                                            <td data-sort-value="{{ optional($ticket->invoice_date)->format('Y-m-d') }}">{{ optional($ticket->invoice_date)->format('d/m/Y') }}</td>
                                            <td>{{ $ticket->client?->display_name ?? __('main.walk_in_customer') }}</td>
                                            <td>{{ $payment?->paymentMethod?->name ?? '-' }}</td>
                                            <td class="amount-cell text-end" data-sort-value="{{ $ticket->total_ttc }}">{{ number_format((float) $ticket->total_ttc, 2, ',', ' ') }} {{ $ticket->currency }}</td>
                                            <td><span class="status-pill sales-invoice-status-{{ $ticket->status }}">{{ $invoiceStatusLabels[$ticket->status] ?? $ticket->status }}</span></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a class="table-button table-button-print" href="{{ route('main.accounting.sales-invoices.print', [$company, $site, $ticket]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.print_pdf') }}" title="{{ __('main.print_pdf') }}">
                                                        <i class="bi bi-printer" aria-hidden="true"></i>
                                                    </a>
                                                    <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#cashTicketModal{{ $ticket->id }}" aria-label="{{ __('main.view_details') }}" title="{{ __('main.view_details') }}">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="8">{{ __('main.no_cash_register_tickets') }}</td></tr>
                                    @endforelse
                                    <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($tickets->hasPages())
                        <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                            <span>{{ __('admin.showing') }} <strong>{{ $tickets->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $tickets->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                            <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                                @if ($tickets->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $tickets->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                                @foreach ($tickets->getUrlRange(1, $tickets->lastPage()) as $page => $url)
                                    @if ($page === $tickets->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                                @endforeach
                                @if ($tickets->hasMorePages())<a href="{{ $tickets->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                            </nav>
                        </section>
                    @endif
                </section>

                <section class="pos-ticket-history-card" aria-label="{{ __('main.cash_register_sessions') }}">
                    <h2 class="pos-ticket-history-title">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        {{ __('main.cash_register_sessions') }}
                    </h2>
                    <div class="pos-ticket-table-shell">
                        <div class="table-responsive">
                            <table class="company-table accounting-receipts-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('main.reference') }}</th>
                                        <th>{{ __('main.opened_at') }}</th>
                                        <th>{{ __('main.closed_at') }}</th>
                                        <th>{{ __('main.opened_by') }}</th>
                                        <th class="text-end">{{ __('main.expected_total_amount') }}</th>
                                        <th class="text-end">{{ __('main.counted_total_amount') }}</th>
                                        <th class="text-end">{{ __('main.difference_amount') }}</th>
                                        <th>{{ __('admin.status') }}</th>
                                        <th class="text-end">{{ __('admin.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cashSessionHistory as $session)
                                        <tr>
                                            <td>{{ $session->reference }}</td>
                                            <td>{{ optional($session->opened_at)->format('d/m/Y H:i') }}</td>
                                            <td>{{ optional($session->closed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                            <td>{{ $session->opener?->name ?? '-' }}</td>
                                            <td class="amount-cell text-end">{{ number_format((float) $session->expected_total_amount, 2, ',', ' ') }} {{ $siteCurrency }}</td>
                                            <td class="amount-cell text-end">{{ $session->counted_total_amount === null ? '-' : number_format((float) $session->counted_total_amount, 2, ',', ' ').' '.$siteCurrency }}</td>
                                            <td class="amount-cell text-end">{{ $session->difference_amount === null ? '-' : number_format((float) $session->difference_amount, 2, ',', ' ').' '.$siteCurrency }}</td>
                                            <td>
                                                <span class="cash-session-status {{ $session->isClosed() ? 'closed' : '' }}">
                                                    {{ $session->isOpen() ? __('main.cash_register_status_open') : __('main.cash_register_status_closed') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    @if ($session->isClosed())
                                                        <a class="table-button table-button-print" href="{{ route('main.accounting.cash-register.report', [$company, $site, $session]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.cash_register_closing_report') }}" title="{{ __('main.cash_register_closing_report') }}">
                                                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="9">{{ __('main.no_cash_register_sessions') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </section>
        </main>
    </div>

    @foreach ($tickets as $ticket)
        @php($ticketPayment = $ticket->payments->first())
        <div class="modal fade subscription-modal" id="cashTicketModal{{ $ticket->id }}" tabindex="-1" aria-labelledby="cashTicketModal{{ $ticket->id }}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content app-modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="cashTicketModal{{ $ticket->id }}Label">
                            <i class="bi bi-receipt" aria-hidden="true"></i>
                            {{ __('main.cash_register_ticket_details', ['reference' => $ticket->reference]) }}
                        </h2>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-table-shell">
                            <table class="company-table modal-data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('main.designation') }}</th>
                                        <th class="text-end">{{ __('main.quantity') }}</th>
                                        <th class="text-end">{{ __('main.unit_price') }}</th>
                                        <th class="text-end">{{ __('main.line_total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ticket->lines as $line)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $line->description }}</td>
                                            <td class="text-end amount-cell">{{ number_format((float) $line->quantity, 2, ',', ' ') }}</td>
                                            <td class="text-end amount-cell">{{ number_format((float) $line->unit_price, 2, ',', ' ') }} {{ $ticket->currency }}</td>
                                            <td class="text-end amount-cell">{{ number_format((float) $line->line_total, 2, ',', ' ') }} {{ $ticket->currency }}</td>
                                        </tr>
                                    @empty
                                        <tr class="empty-row"><td colspan="5">{{ __('admin.no_results') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($ticketPayment?->amount_received !== null)
                            <dl class="details-list mt-3">
                                <div>
                                    <dt>{{ __('main.amount_received') }}</dt>
                                    <dd class="text-end">{{ number_format((float) $ticketPayment->amount_received, 2, ',', ' ') }} {{ $ticketPayment->currency }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('main.change_due') }}</dt>
                                    <dd class="text-end">{{ number_format((float) $ticketPayment->change_due, 2, ',', ' ') }} {{ $ticketPayment->currency }}</dd>
                                </div>
                            </dl>
                        @endif
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
    <script>
        (() => {
            const form = document.querySelector('[data-pos-form]');
            if (!form) return;

            const products = JSON.parse(form.dataset.products || '[]');
            const oldCart = JSON.parse(form.dataset.oldCart || '[]');
            const cart = new Map();
            const browser = {
                level: 'categories',
                categoryId: null,
                subcategoryId: null,
                search: '',
            };
            let selectedCartId = null;
            const productGrid = form.querySelector('[data-pos-products]');
            const backButton = form.querySelector('[data-pos-back]');
            const crumbsElement = form.querySelector('[data-pos-crumbs]');
            const cartElement = form.querySelector('[data-pos-cart]');
            const emptyElement = form.querySelector('[data-pos-empty]');
            const taxRateField = form.querySelector('#proformaTaxRate');
            const totalFields = {
                subtotal: form.querySelector('[data-total-subtotal]'),
                tax: form.querySelector('[data-total-tax]'),
                ttc: form.querySelector('[data-total-ttc]'),
            };
            const cashPayment = {
                wrapper: form.querySelector('[data-cash-payment]'),
                total: form.querySelector('[data-cash-total]'),
                received: form.querySelector('[data-cash-received]'),
                change: form.querySelector('[data-cash-change]'),
                error: form.querySelector('[data-cash-error]'),
            };
            const submitButton = form.querySelector('[data-pos-submit]');
            let currentTotal = 0;
            let keypadTarget = 'cart';
            const labels = {
                categories: @json(__('main.categories')),
                subcategories: @json(__('main.subcategories')),
                items: @json(__('main.items')),
                stock: @json(__('main.stock')),
                rows: @json(__('admin.rows')),
                noProducts: @json(__('main.no_pos_products')),
                noSubcategories: @json(__('main.pos_no_subcategories')),
                noArticles: @json(__('main.pos_no_articles_in_subcategory')),
                searchResults: @json(__('main.search_results')),
            };

            const numberValue = (value) => Number(String(value || '0').replace(',', '.')) || 0;
            const formatAmount = (value) => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const normalize = (value) => String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const productById = (id) => products.find((product) => Number(product.id) === Number(id));
            const selectedPaymentType = () => form.querySelector('input[name="payment_method_id"]:checked')?.dataset.paymentType || '';
            const isCashPayment = () => selectedPaymentType() === @json(\App\Models\AccountingPaymentMethod::TYPE_CASH);
            const setKeypadTarget = (target) => {
                keypadTarget = target;
                cashPayment.wrapper?.classList.toggle('is-keypad-active', target === 'cash' && isCashPayment());
                cartElement?.querySelectorAll('[data-pos-cart-row]').forEach((row) => {
                    row.classList.toggle('is-active', target === 'cart' && row.dataset.posCartRow === selectedCartId);
                });
            };
            const updateSubmitState = () => {
                if (!submitButton) return;

                const hasValidCart = cart.size > 0 && currentTotal > 0;
                const hasValidCashAmount = !isCashPayment() || numberValue(cashPayment.received?.value) >= currentTotal;
                submitButton.disabled = !hasValidCart || !hasValidCashAmount;
            };

            const updateCashPayment = () => {
                if (!cashPayment.wrapper) return;

                const shouldShow = isCashPayment();
                cashPayment.wrapper.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) {
                    if (cashPayment.error) cashPayment.error.hidden = true;
                    if (keypadTarget === 'cash') setKeypadTarget('cart');
                    updateSubmitState();
                    return;
                }

                const received = numberValue(cashPayment.received?.value);
                const change = Math.max(0, received - currentTotal);

                if (cashPayment.total) cashPayment.total.value = `${formatAmount(currentTotal)} {{ $defaultCurrency }}`;
                if (cashPayment.change) cashPayment.change.value = `${formatAmount(change)} {{ $defaultCurrency }}`;

                const hasShortage = cart.size > 0 && currentTotal > 0 && received < currentTotal;
                if (cashPayment.error) cashPayment.error.hidden = !hasShortage;
                updateSubmitState();
            };

            const appendCashKey = (key) => {
                if (!cashPayment.received) return;

                const current = String(cashPayment.received.value || '');
                if (key === '.' && current.includes('.')) return;

                const nextValue = key === '.'
                    ? (current === '' ? '0.' : `${current}.`)
                    : `${current}${key}`;

                cashPayment.received.value = nextValue.replace(/^0+(?=\d)/, '');
                cashPayment.received.focus({ preventScroll: true });
                updateCashPayment();
            };

            const categoryKey = (product) => product.category_id ? `category-${product.category_id}` : 'category-null';
            const subcategoryKey = (product) => product.subcategory_id ? `subcategory-${product.subcategory_id}` : 'subcategory-null';
            const itemMatchesSearch = (product, query) => normalize([
                product.reference,
                product.name,
                product.category_name,
                product.subcategory_name,
            ].join(' ')).includes(query);

            const renderEmptyProducts = (message) => {
                productGrid.innerHTML = `<div class="pos-cart-empty">${escapeHtml(message)}</div>`;
            };

            const renderProductCard = (product) => `
                <button type="button" class="pos-product-card" data-pos-product="${Number(product.id)}" ${Number(product.stock) <= 0 ? 'disabled' : ''}>
                    <span class="pos-product-reference">${escapeHtml(product.reference || '')}</span>
                    <span class="pos-product-name">${escapeHtml(product.name)}</span>
                    <span class="pos-product-price">${formatAmount(product.price)} ${escapeHtml(product.currency || '{{ $defaultCurrency }}')}</span>
                    <span class="pos-stock">${escapeHtml(labels.stock)} : ${formatAmount(product.stock)}</span>
                </button>
            `;

            const renderProductBrowser = () => {
                if (!productGrid) return;

                const query = normalize(browser.search);
                backButton.hidden = browser.level === 'categories' && query === '';

                const currentCategory = products.find((product) => categoryKey(product) === browser.categoryId);
                const currentSubcategory = products.find((product) => subcategoryKey(product) === browser.subcategoryId && categoryKey(product) === browser.categoryId);
                const crumbs = [labels.categories];
                if (browser.level !== 'categories' && currentCategory) crumbs.push(currentCategory.category_name);
                if (browser.level === 'items' && currentSubcategory) crumbs.push(currentSubcategory.subcategory_name);
                if (query !== '') crumbs.push(labels.searchResults);
                crumbsElement.innerHTML = crumbs.map((crumb, index) => `<span>${index > 0 ? '<i class="bi bi-chevron-right" aria-hidden="true"></i>' : ''}${escapeHtml(crumb)}</span>`).join('');

                if (query !== '') {
                    const matchedProducts = products.filter((product) => itemMatchesSearch(product, query));
                    productGrid.innerHTML = matchedProducts.map(renderProductCard).join('');
                    if (!matchedProducts.length) renderEmptyProducts(labels.noProducts);
                    return;
                }

                if (browser.level === 'categories') {
                    const categories = new Map();
                    products.forEach((product) => {
                        const key = categoryKey(product);
                        const current = categories.get(key) || {
                            id: key,
                            name: product.category_name,
                            count: 0,
                        };
                        current.count += 1;
                        categories.set(key, current);
                    });

                    productGrid.innerHTML = Array.from(categories.values())
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map((category) => `
                            <button type="button" class="pos-category-card" data-pos-category="${escapeHtml(category.id)}">
                                <span class="pos-category-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                                <span class="pos-category-name">${escapeHtml(category.name)}</span>
                                <span class="pos-category-count">${category.count} ${escapeHtml(labels.items)}</span>
                            </button>
                        `).join('');
                    if (!categories.size) renderEmptyProducts(labels.noProducts);
                    return;
                }

                if (browser.level === 'subcategories') {
                    const subcategories = new Map();
                    products
                        .filter((product) => categoryKey(product) === browser.categoryId)
                        .forEach((product) => {
                            const key = subcategoryKey(product);
                            const current = subcategories.get(key) || {
                                id: key,
                                name: product.subcategory_name,
                                count: 0,
                            };
                            current.count += 1;
                            subcategories.set(key, current);
                        });

                    productGrid.innerHTML = Array.from(subcategories.values())
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .map((subcategory) => `
                            <button type="button" class="pos-subcategory-card" data-pos-subcategory="${escapeHtml(subcategory.id)}">
                                <span class="pos-category-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
                                <span class="pos-category-name">${escapeHtml(subcategory.name)}</span>
                                <span class="pos-category-count">${subcategory.count} ${escapeHtml(labels.items)}</span>
                            </button>
                        `).join('');
                    if (!subcategories.size) renderEmptyProducts(labels.noSubcategories);
                    return;
                }

                const categoryProducts = products.filter((product) => categoryKey(product) === browser.categoryId && subcategoryKey(product) === browser.subcategoryId);
                productGrid.innerHTML = categoryProducts.map(renderProductCard).join('');
                if (!categoryProducts.length) renderEmptyProducts(labels.noArticles);
            };

            const rebuildCart = () => {
                cartElement.querySelectorAll('[data-pos-cart-row], input[type="hidden"]').forEach((element) => element.remove());
                emptyElement.hidden = cart.size > 0;

                let index = 0;
                let subtotal = 0;

                cart.forEach((line, id) => {
                    const lineTotal = line.quantity * line.price;
                    subtotal += lineTotal;

                    const row = document.createElement('div');
                    row.className = 'pos-cart-row';
                    row.dataset.posCartRow = id;
                    row.classList.toggle('is-active', keypadTarget === 'cart' && selectedCartId === String(id));
                    row.innerHTML = `
                        <div>
                            <strong>${line.name}</strong>
                            <small>${line.reference || ''} - ${formatAmount(line.price)} ${line.currency}</small>
                        </div>
                        <div class="pos-quantity">
                            <button type="button" data-pos-decrement="${id}">-</button>
                            <input type="number" min="1" step="1" value="${line.quantity}" data-pos-quantity="${id}">
                            <button type="button" data-pos-increment="${id}">+</button>
                        </div>
                        <button type="button" class="icon-light-button" data-pos-remove="${id}" aria-label="{{ __('admin.delete') }}">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    `;
                    cartElement.appendChild(row);

                    [
                        ['line_type', 'item'],
                        ['item_id', id],
                        ['description', line.name],
                        ['quantity', line.quantity],
                        ['unit_price', line.price],
                        ['discount_type', 'fixed'],
                        ['discount_amount', 0],
                        ['details', line.reference || ''],
                    ].forEach(([name, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `lines[${index}][${name}]`;
                        input.value = value;
                        cartElement.appendChild(input);
                    });

                    index += 1;
                });

                const tax = subtotal * (numberValue(taxRateField?.value) / 100);
                currentTotal = subtotal + tax;
                if (totalFields.subtotal) totalFields.subtotal.textContent = formatAmount(subtotal);
                if (totalFields.tax) totalFields.tax.textContent = formatAmount(tax);
                if (totalFields.ttc) totalFields.ttc.textContent = formatAmount(currentTotal);
                updateCashPayment();
            };

            const addProduct = (id) => {
                const product = productById(id);
                if (!product || Number(product.stock) <= 0) return;

                const current = cart.get(String(id));
                const nextQuantity = (current?.quantity || 0) + 1;
                if (nextQuantity > Number(product.stock)) return;

                cart.set(String(id), {
                    id,
                    reference: product.reference,
                    name: product.name,
                    price: Number(product.price || 0),
                    stock: Number(product.stock || 0),
                    currency: product.currency || '{{ $defaultCurrency }}',
                    quantity: nextQuantity,
                });
                selectedCartId = String(id);
                setKeypadTarget('cart');
                rebuildCart();
            };

            form.addEventListener('click', (event) => {
                const row = event.target.closest('[data-pos-cart-row]');
                if (row) {
                    selectedCartId = row.dataset.posCartRow;
                    setKeypadTarget('cart');
                    rebuildCart();
                }

                const productButton = event.target.closest('[data-pos-product]');
                if (productButton) {
                    addProduct(productButton.dataset.posProduct);
                    return;
                }

                const categoryButton = event.target.closest('[data-pos-category]');
                if (categoryButton) {
                    browser.level = 'subcategories';
                    browser.categoryId = categoryButton.dataset.posCategory;
                    browser.subcategoryId = null;
                    renderProductBrowser();
                    return;
                }

                const subcategoryButton = event.target.closest('[data-pos-subcategory]');
                if (subcategoryButton) {
                    browser.level = 'items';
                    browser.subcategoryId = subcategoryButton.dataset.posSubcategory;
                    renderProductBrowser();
                    return;
                }

                if (event.target.closest('[data-pos-back]')) {
                    if (browser.search !== '') {
                        browser.search = '';
                        const field = form.querySelector('[data-pos-product-search]');
                        if (field) field.value = '';
                    } else if (browser.level === 'items') {
                        browser.level = 'subcategories';
                        browser.subcategoryId = null;
                    } else {
                        browser.level = 'categories';
                        browser.categoryId = null;
                        browser.subcategoryId = null;
                    }
                    renderProductBrowser();
                    return;
                }

                const increment = event.target.closest('[data-pos-increment]');
                if (increment) {
                    addProduct(increment.dataset.posIncrement);
                    return;
                }

                const decrement = event.target.closest('[data-pos-decrement]');
                if (decrement) {
                    const id = decrement.dataset.posDecrement;
                    const current = cart.get(String(id));
                    if (!current) return;
                    current.quantity -= 1;
                    if (current.quantity <= 0) cart.delete(String(id));
                    if (!cart.has(String(id))) selectedCartId = null;
                    rebuildCart();
                    return;
                }

                const remove = event.target.closest('[data-pos-remove]');
                if (remove) {
                    cart.delete(String(remove.dataset.posRemove));
                    if (selectedCartId === String(remove.dataset.posRemove)) selectedCartId = null;
                    rebuildCart();
                    return;
                }

                if (event.target.closest('[data-pos-clear]')) {
                    cart.clear();
                    selectedCartId = null;
                    rebuildCart();
                }

                const key = event.target.closest('[data-pos-key]');
                if (key && (keypadTarget === 'cash' || (!selectedCartId && isCashPayment()))) {
                    setKeypadTarget('cash');
                    appendCashKey(key.dataset.posKey);
                    return;
                }

                if (key && selectedCartId && cart.has(selectedCartId)) {
                    const current = cart.get(selectedCartId);
                    const pressedKey = String(key.dataset.posKey);
                    if (pressedKey === '.') return;

                    const typed = String(current.keyBuffer || '') + pressedKey;
                    const quantity = Math.max(1, Math.min(Number(current.stock), Number(typed || 1)));
                    current.keyBuffer = typed;
                    current.quantity = quantity;
                    rebuildCart();
                }

                if (event.target.closest('[data-pos-clear-key]') && (keypadTarget === 'cash' || (!selectedCartId && isCashPayment()))) {
                    setKeypadTarget('cash');
                    if (cashPayment.received) {
                        cashPayment.received.value = '';
                        cashPayment.received.focus({ preventScroll: true });
                    }
                    updateCashPayment();
                    return;
                }

                if (event.target.closest('[data-pos-clear-key]') && selectedCartId && cart.has(selectedCartId)) {
                    const current = cart.get(selectedCartId);
                    current.keyBuffer = '';
                    current.quantity = 1;
                    rebuildCart();
                }
            });

            form.querySelectorAll('input[name="payment_method_id"]').forEach((field) => {
                field.addEventListener('change', updateCashPayment);
            });

            cashPayment.received?.addEventListener('focus', () => setKeypadTarget('cash'));
            cashPayment.received?.addEventListener('click', () => setKeypadTarget('cash'));
            cashPayment.received?.addEventListener('input', () => {
                setKeypadTarget('cash');
                updateCashPayment();
            });

            cartElement.addEventListener('input', (event) => {
                const field = event.target.closest('[data-pos-quantity]');
                if (!field) return;

                const id = field.dataset.posQuantity;
                const current = cart.get(String(id));
                if (!current) return;

                selectedCartId = String(id);
                setKeypadTarget('cart');
                current.keyBuffer = '';
                current.quantity = Math.max(1, Math.min(Number(current.stock), Number(field.value || 1)));
                rebuildCart();
            });

            form.querySelector('[data-pos-product-search]')?.addEventListener('input', (event) => {
                browser.search = event.target.value;
                renderProductBrowser();
            });

            form.addEventListener('submit', (event) => {
                if (cart.size === 0) {
                    event.preventDefault();
                    emptyElement.hidden = false;
                    emptyElement.textContent = @json(__('main.pos_cart_required'));
                    return;
                }

                if (isCashPayment() && numberValue(cashPayment.received?.value) < currentTotal) {
                    event.preventDefault();
                    cashPayment.wrapper?.classList.add('is-visible');
                    if (cashPayment.error) cashPayment.error.hidden = false;
                }
            });

            oldCart.forEach((line) => {
                const product = productById(line.item_id);
                if (!product) return;
                cart.set(String(product.id), {
                    id: product.id,
                    reference: product.reference,
                    name: product.name,
                    price: Number(product.price || 0),
                    stock: Number(product.stock || 0),
                    currency: product.currency || '{{ $defaultCurrency }}',
                    quantity: Math.max(1, Math.min(Number(product.stock || 1), Number(line.quantity || 1))),
                });
            });

            rebuildCart();
            renderProductBrowser();
            updateCashPayment();
        })();
    </script>
</body>
</html>

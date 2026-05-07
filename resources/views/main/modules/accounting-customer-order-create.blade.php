<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isEditingOrder = isset($order) && $order?->exists;
        $pageTitle = $isEditingOrder ? __('main.edit_customer_order') : __('main.new_customer_order');
    @endphp
    <title>{{ $pageTitle }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $indexRoute = route('main.accounting.customer-orders', [$company, $site]);
        $formAction = $isEditingOrder
            ? route('main.accounting.customer-orders.update', [$company, $site, $order])
            : route('main.accounting.customer-orders.store', [$company, $site]);
        $defaultOrderDate = $isEditingOrder ? optional($order->order_date)->format('Y-m-d') : now()->format('Y-m-d');
        $defaultDeliveryDate = $isEditingOrder ? optional($order->expected_delivery_date)->format('Y-m-d') : '';
        $defaultCurrency = $isEditingOrder ? $order->currency : ($site->currency ?: 'CDF');
        $defaultStatus = $isEditingOrder ? $order->status : \App\Models\AccountingCustomerOrder::STATUS_DRAFT;
        $defaultPaymentTerms = $isEditingOrder ? ($order->payment_terms ?: \App\Models\AccountingProformaInvoice::PAYMENT_TO_DISCUSS) : \App\Models\AccountingProformaInvoice::PAYMENT_TO_DISCUSS;
        $defaultTaxRate = $isEditingOrder ? number_format((float) $order->tax_rate, 2, '.', '') : number_format((float) $defaultTaxRate, 2, '.', '');
        $linePayload = fn ($line) => [
            'line_type' => $line->line_type,
            'item_id' => $line->item_id,
            'service_id' => $line->service_id,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'cost_price' => number_format((float) $line->cost_price, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'margin_type' => $line->margin_type ?: \App\Models\AccountingCustomerOrderLine::MARGIN_FIXED,
            'margin_value' => number_format((float) $line->margin_value, 2, '.', ''),
            'discount_type' => $line->discount_type ?: \App\Models\AccountingCustomerOrderLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
        ];
        $defaultLines = $isEditingOrder
            ? $order->lines->map($linePayload)->values()->all()
            : [[
                'line_type' => \App\Models\AccountingCustomerOrderLine::TYPE_FREE,
                'item_id' => '',
                'service_id' => '',
                'description' => '',
                'details' => '',
                'quantity' => '1',
                'cost_price' => '0',
                'unit_price' => '0',
                'margin_type' => \App\Models\AccountingCustomerOrderLine::MARGIN_FIXED,
                'margin_value' => '0',
                'discount_type' => \App\Models\AccountingCustomerOrderLine::DISCOUNT_FIXED,
                'discount_amount' => '0',
            ]];
        $oldLines = old('lines', $defaultLines ?: [[
            'line_type' => \App\Models\AccountingCustomerOrderLine::TYPE_FREE,
            'item_id' => '',
            'service_id' => '',
            'description' => '',
            'details' => '',
            'quantity' => '1',
            'cost_price' => '0',
            'unit_price' => '0',
            'margin_type' => \App\Models\AccountingCustomerOrderLine::MARGIN_FIXED,
            'margin_value' => '0',
            'discount_type' => \App\Models\AccountingCustomerOrderLine::DISCOUNT_FIXED,
            'discount_amount' => '0',
        ]]);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'customer-orders'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => $pageTitle])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.customer_orders') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.customer_orders_subtitle') }}</p>

                <section class="company-card proforma-page-card">
                    <form class="admin-form customer-order-form proforma-page-form" method="POST" action="{{ $formAction }}" novalidate>
                        @csrf
                        @if ($isEditingOrder)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="orderClient" class="form-label">{{ __('main.customer') }} *</label>
                                <select id="orderClient" name="client_id" class="form-select @error('client_id') is-invalid @enderror">
                                    <option value="">{{ __('main.choose_customer') }}</option>
                                    @foreach ($clients as $id => $label)
                                        <option value="{{ $id }}" @selected(old('client_id', $isEditingOrder ? $order->client_id : null) == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="orderTitle" class="form-label">{{ __('main.order_title') }}</label>
                                <input id="orderTitle" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $isEditingOrder ? $order->title : '') }}" placeholder="{{ __('main.order_title_placeholder') }}">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="orderDate" class="form-label">{{ __('main.date') }} *</label>
                                <input id="orderDate" name="order_date" type="date" class="form-control @error('order_date') is-invalid @enderror" value="{{ old('order_date', $defaultOrderDate) }}">
                                @error('order_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="expectedDeliveryDate" class="form-label">{{ __('main.expected_delivery_date') }}</label>
                                <input id="expectedDeliveryDate" name="expected_delivery_date" type="date" class="form-control @error('expected_delivery_date') is-invalid @enderror" value="{{ old('expected_delivery_date', $defaultDeliveryDate) }}">
                                @error('expected_delivery_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="orderCurrency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="orderCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency', $defaultCurrency) === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="orderStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="orderStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $defaultStatus) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-list-check" aria-hidden="true"></i> {{ __('main.customer_order_lines') }}</span>
                                <button type="button" class="light-action" data-add-customer-order-line>
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    {{ __('main.add_line') }}
                                </button>
                            </div>

                            <div class="proforma-line-list" data-customer-order-line-list>
                                @foreach ($oldLines as $index => $line)
                                    @include('main.modules.partials.customer-order-line-row', ['index' => $index, 'line' => $line, 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
                                @endforeach
                            </div>
                        </section>

                        <section class="proforma-summary-grid">
                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="orderPaymentTerms" class="form-label">{{ __('main.payment_terms') }}</label>
                                            <select id="orderPaymentTerms" name="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
                                                @foreach ($paymentTermLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('payment_terms', $defaultPaymentTerms) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('payment_terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="orderNotes" class="form-label">{{ __('main.notes') }}</label>
                                            <textarea id="orderNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes', $isEditingOrder ? $order->notes : '') }}</textarea>
                                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="orderTerms" class="form-label">{{ __('main.terms') }}</label>
                                            <textarea id="orderTerms" name="terms" rows="3" class="form-control @error('terms') is-invalid @enderror" placeholder="{{ __('main.terms') }}">{{ old('terms', $isEditingOrder ? $order->terms : '') }}</textarea>
                                            @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="proforma-total-card">
                                        <label for="orderTaxRate" class="form-label">{{ __('main.global_vat_rate') }} *</label>
                                        <input id="orderTaxRate" name="tax_rate" type="number" min="0" max="100" step="0.01" class="form-control @error('tax_rate') is-invalid @enderror" value="{{ old('tax_rate', $defaultTaxRate) }}" placeholder="0">
                                        @error('tax_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        <dl>
                                            <div><dt>{{ __('main.subtotal') }}</dt><dd data-order-total-subtotal>0,00</dd></div>
                                            <div><dt>{{ __('main.cost_total') }}</dt><dd data-order-total-cost>0,00</dd></div>
                                            <div><dt>{{ __('main.margin') }}</dt><dd data-order-total-margin>0,00</dd></div>
                                            <div><dt>{{ __('main.margin_rate') }}</dt><dd data-order-total-margin-rate>0,00 %</dd></div>
                                            <div><dt>{{ __('main.discount_total') }}</dt><dd data-order-total-discount>0,00</dd></div>
                                            <div><dt>{{ __('main.total_ht') }}</dt><dd data-order-total-ht>0,00</dd></div>
                                            <div><dt>{{ __('main.vat_amount') }}</dt><dd data-order-total-tax>0,00</dd></div>
                                            <div class="total"><dt>{{ __('main.total_ttc') }}</dt><dd data-order-total-ttc>0,00</dd></div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="modal-actions">
                            <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('admin.cancel') }}</a>
                            <button class="modal-submit" id="customerOrderSubmit" type="submit">{{ $isEditingOrder ? __('admin.update') : __('admin.create') }}</button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <template id="customerOrderLineTemplate">
        @include('main.modules.partials.customer-order-line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-customer-orders.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-customer-orders.js')) !!}</script>
</body>
</html>

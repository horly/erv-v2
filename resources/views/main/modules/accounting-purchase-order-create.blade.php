<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isEditingPurchaseOrder = isset($purchaseOrder) && $purchaseOrder?->exists;
        $pageTitle = $isEditingPurchaseOrder ? __('main.edit_purchase_order') : __('main.new_purchase_order');
    @endphp
    <title>{{ $pageTitle }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $indexRoute = route('main.accounting.purchase-orders', [$company, $site]);
        $formAction = $isEditingPurchaseOrder
            ? route('main.accounting.purchase-orders.update', [$company, $site, $purchaseOrder])
            : route('main.accounting.purchase-orders.store', [$company, $site]);
        $defaultOrderDate = $isEditingPurchaseOrder ? optional($purchaseOrder->order_date)->format('Y-m-d') : now()->format('Y-m-d');
        $defaultExpectedDate = $isEditingPurchaseOrder ? optional($purchaseOrder->expected_delivery_date)->format('Y-m-d') : now()->addDays(7)->format('Y-m-d');
        $defaultCurrency = $isEditingPurchaseOrder ? $purchaseOrder->currency : ($site->currency ?: array_key_first($currencies) ?: 'CDF');
        $defaultStatus = $isEditingPurchaseOrder ? $purchaseOrder->status : \App\Models\AccountingPurchaseOrder::STATUS_DRAFT;
        $defaultTaxRate = $isEditingPurchaseOrder
            ? number_format((float) $purchaseOrder->tax_rate, 2, '.', '')
            : number_format((float) $defaultTaxRate, 2, '.', '');
        $linePayload = fn ($line) => [
            'line_type' => $line->line_type,
            'item_id' => $line->item_id,
            'service_id' => $line->service_id,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => $line->discount_type ?: \App\Models\AccountingPurchaseOrderLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
        ];
        $defaultLines = $isEditingPurchaseOrder
            ? $purchaseOrder->lines->map($linePayload)->values()->all()
            : [[
                'line_type' => \App\Models\AccountingPurchaseOrderLine::TYPE_FREE,
                'item_id' => '',
                'service_id' => '',
                'description' => '',
                'details' => '',
                'quantity' => '1',
                'unit_price' => '0',
                'discount_type' => \App\Models\AccountingPurchaseOrderLine::DISCOUNT_FIXED,
                'discount_amount' => '0',
                'create_stock_item' => '0',
            ]];
        $oldLines = old('lines', $defaultLines);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'purchase-orders'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => $pageTitle])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.purchase_orders') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.purchase_orders_subtitle') }}</p>

                <section class="company-card proforma-page-card">
                    <form class="admin-form proforma-form proforma-page-form" method="POST" action="{{ $formAction }}" novalidate>
                        @csrf
                        @if ($isEditingPurchaseOrder)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="purchaseOrderSupplier" class="form-label">{{ __('main.supplier') }} *</label>
                                <select id="purchaseOrderSupplier" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" data-proforma-client data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                                    <option value="">{{ __('main.choose_supplier') }}</option>
                                    @foreach ($suppliers as $id => $label)
                                        <option value="{{ $id }}" @selected(old('supplier_id', $isEditingPurchaseOrder ? $purchaseOrder->supplier_id : '') == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseOrderSupplierReference" class="form-label">{{ __('main.supplier_reference') }}</label>
                                <input id="purchaseOrderSupplierReference" name="supplier_reference" type="text" class="form-control @error('supplier_reference') is-invalid @enderror" value="{{ old('supplier_reference', $isEditingPurchaseOrder ? $purchaseOrder->supplier_reference : '') }}" placeholder="{{ __('main.supplier_reference_placeholder') }}">
                                @error('supplier_reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseOrderTitle" class="form-label">{{ __('main.purchase_order_subject') }}</label>
                                <input id="purchaseOrderTitle" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $isEditingPurchaseOrder ? $purchaseOrder->title : '') }}" placeholder="{{ __('main.purchase_order_subject_placeholder') }}">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="purchaseOrderDate" class="form-label">{{ __('main.order_date') }} *</label>
                                <input id="purchaseOrderDate" name="order_date" type="date" class="form-control @error('order_date') is-invalid @enderror" value="{{ old('order_date', $defaultOrderDate) }}">
                                @error('order_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="purchaseOrderExpectedDate" class="form-label">{{ __('main.expected_delivery_date') }}</label>
                                <input id="purchaseOrderExpectedDate" name="expected_delivery_date" type="date" class="form-control @error('expected_delivery_date') is-invalid @enderror" value="{{ old('expected_delivery_date', $defaultExpectedDate) }}">
                                @error('expected_delivery_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseOrderCurrency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="purchaseOrderCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency', $defaultCurrency) === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseOrderStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="purchaseOrderStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $defaultStatus) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-list-check" aria-hidden="true"></i> {{ __('main.purchase_order_lines') }}</span>
                            </div>

                            <div class="proforma-line-list" data-proforma-line-list>
                                @foreach ($oldLines as $index => $line)
                                    @include('main.modules.partials.purchase-line-row', ['index' => $index, 'line' => $line, 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
                                @endforeach
                            </div>

                            <div class="line-section-actions">
                                <button type="button" class="light-action" data-add-proforma-line>
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    {{ __('main.add_line') }}
                                </button>
                            </div>
                        </section>

                        <section class="proforma-summary-grid">
                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="purchaseOrderNotes" class="form-label">{{ __('main.notes') }}</label>
                                            <textarea id="purchaseOrderNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes', $isEditingPurchaseOrder ? $purchaseOrder->notes : '') }}</textarea>
                                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="purchaseOrderTerms" class="form-label">{{ __('main.terms') }}</label>
                                            <textarea id="purchaseOrderTerms" name="terms" rows="3" class="form-control @error('terms') is-invalid @enderror" placeholder="{{ __('main.terms') }}">{{ old('terms', $isEditingPurchaseOrder ? $purchaseOrder->terms : '') }}</textarea>
                                            @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="proforma-total-card">
                                        <label for="proformaTaxRate" class="form-label">{{ __('main.global_vat_rate') }} *</label>
                                        <input id="proformaTaxRate" name="tax_rate" type="number" min="0" max="100" step="0.01" class="form-control @error('tax_rate') is-invalid @enderror" value="{{ old('tax_rate', $defaultTaxRate) }}" placeholder="0">
                                        @error('tax_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        <dl>
                                            <div><dt>{{ __('main.subtotal') }}</dt><dd data-total-subtotal>0,00</dd></div>
                                            <div><dt>{{ __('main.discount_total') }}</dt><dd data-total-discount>0,00</dd></div>
                                            <div><dt>{{ __('main.total_ht') }}</dt><dd data-total-ht>0,00</dd></div>
                                            <div><dt>{{ __('main.vat_amount') }}</dt><dd data-total-tax>0,00</dd></div>
                                            <div class="total"><dt>{{ __('main.total_ttc') }}</dt><dd data-total-ttc>0,00</dd></div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="modal-actions">
                            <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('admin.cancel') }}</a>
                            <button class="modal-submit" type="submit">{{ $isEditingPurchaseOrder ? __('admin.update') : __('admin.create') }}</button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <template id="proformaLineTemplate">
        @include('main.modules.partials.purchase-line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>{!! file_get_contents(resource_path('js/main/accounting-proforma-invoices.js')) !!}</script>
</body>
</html>

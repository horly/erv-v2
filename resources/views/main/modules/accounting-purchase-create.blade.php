<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isEditingPurchase = isset($purchase) && $purchase?->exists;
        $pageTitle = $isEditingPurchase ? __('main.edit_purchase') : __('main.new_purchase');
    @endphp
    <title>{{ $pageTitle }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $indexRoute = route('main.accounting.purchases', [$company, $site]);
        $formAction = $isEditingPurchase
            ? route('main.accounting.purchases.update', [$company, $site, $purchase])
            : route('main.accounting.purchases.store', [$company, $site]);
        $defaultPurchaseDate = $isEditingPurchase ? optional($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d');
        $defaultDueDate = $isEditingPurchase ? optional($purchase->due_date)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d');
        $defaultCurrency = $isEditingPurchase ? $purchase->currency : ($site->currency ?: array_key_first($currencies) ?: 'CDF');
        $defaultStatus = $isEditingPurchase ? $purchase->status : \App\Models\AccountingPurchase::STATUS_DRAFT;
        $defaultTaxRate = $isEditingPurchase
            ? number_format((float) $purchase->tax_rate, 2, '.', '')
            : number_format((float) $defaultTaxRate, 2, '.', '');
        $linePayload = fn ($line) => [
            'line_type' => $line->line_type,
            'item_id' => $line->item_id,
            'service_id' => $line->service_id,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => $line->discount_type ?: \App\Models\AccountingPurchaseLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
        ];
        $defaultLines = $isEditingPurchase
            ? $purchase->lines->map($linePayload)->values()->all()
            : [[
                'line_type' => \App\Models\AccountingPurchaseLine::TYPE_FREE,
                'item_id' => '',
                'service_id' => '',
                'description' => '',
                'details' => '',
                'quantity' => '1',
                'unit_price' => '0',
                'discount_type' => \App\Models\AccountingPurchaseLine::DISCOUNT_FIXED,
                'discount_amount' => '0',
                'create_stock_item' => '0',
            ]];
        $oldLines = old('lines', $defaultLines);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'purchases'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => $pageTitle])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.purchases') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.purchases_subtitle') }}</p>

                <section class="company-card proforma-page-card">
                    <form class="admin-form proforma-form proforma-page-form" method="POST" action="{{ $formAction }}" novalidate>
                        @csrf
                        @if ($isEditingPurchase)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="purchaseSupplier" class="form-label">{{ __('main.supplier') }} *</label>
                                <select id="purchaseSupplier" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" data-proforma-client data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                                    <option value="">{{ __('main.choose_supplier') }}</option>
                                    @foreach ($suppliers as $id => $label)
                                        <option value="{{ $id }}" @selected(old('supplier_id', $isEditingPurchase ? $purchase->supplier_id : '') == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseSupplierReference" class="form-label">{{ __('main.supplier_invoice_reference') }}</label>
                                <input id="purchaseSupplierReference" name="supplier_invoice_reference" type="text" class="form-control @error('supplier_invoice_reference') is-invalid @enderror" value="{{ old('supplier_invoice_reference', $isEditingPurchase ? $purchase->supplier_invoice_reference : '') }}" placeholder="{{ __('main.supplier_invoice_reference_placeholder') }}">
                                @error('supplier_invoice_reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseTitle" class="form-label">{{ __('main.purchase_subject') }}</label>
                                <input id="purchaseTitle" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $isEditingPurchase ? $purchase->title : '') }}" placeholder="{{ __('main.purchase_subject_placeholder') }}">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="purchaseDate" class="form-label">{{ __('main.purchase_date') }} *</label>
                                <input id="purchaseDate" name="purchase_date" type="date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', $defaultPurchaseDate) }}">
                                @error('purchase_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="purchaseDueDate" class="form-label">{{ __('main.due_date') }}</label>
                                <input id="purchaseDueDate" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $defaultDueDate) }}">
                                @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseCurrency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="purchaseCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency', $defaultCurrency) === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="purchaseStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="purchaseStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $defaultStatus) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-list-check" aria-hidden="true"></i> {{ __('main.purchase_lines') }}</span>
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
                                            <label for="purchaseNotes" class="form-label">{{ __('main.notes') }}</label>
                                            <textarea id="purchaseNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes', $isEditingPurchase ? $purchase->notes : '') }}</textarea>
                                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="purchaseTerms" class="form-label">{{ __('main.terms') }}</label>
                                            <textarea id="purchaseTerms" name="terms" rows="3" class="form-control @error('terms') is-invalid @enderror" placeholder="{{ __('main.terms') }}">{{ old('terms', $isEditingPurchase ? $purchase->terms : '') }}</textarea>
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
                            <button class="modal-submit" type="submit">{{ $isEditingPurchase ? __('admin.update') : __('admin.create') }}</button>
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
    <!-- resources/js/main/accounting-proforma-invoices.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-proforma-invoices.js')) !!}</script>
</body>
</html>

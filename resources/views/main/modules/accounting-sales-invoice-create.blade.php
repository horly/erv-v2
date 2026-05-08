<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isEditingInvoice = isset($invoice) && $invoice?->exists;
        $pageTitle = $isEditingInvoice ? __('main.edit_sales_invoice') : __('main.new_sales_invoice');
    @endphp
    <title>{{ $pageTitle }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $indexRoute = route('main.accounting.sales-invoices', [$company, $site]);
        $formAction = $isEditingInvoice
            ? route('main.accounting.sales-invoices.update', [$company, $site, $invoice])
            : route('main.accounting.sales-invoices.store', [$company, $site]);
        $source ??= null;
        $defaultInvoiceDate = $isEditingInvoice ? optional($invoice->invoice_date)->format('Y-m-d') : now()->format('Y-m-d');
        $defaultDueDate = $isEditingInvoice ? optional($invoice->due_date)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d');
        $defaultCurrency = $isEditingInvoice ? $invoice->currency : ($source['currency'] ?? ($site->currency ?: 'CDF'));
        $defaultStatus = $isEditingInvoice ? $invoice->status : \App\Models\AccountingSalesInvoice::STATUS_DRAFT;
        $defaultPaymentTerms = $isEditingInvoice
            ? ($invoice->payment_terms ?: \App\Models\AccountingProformaInvoice::PAYMENT_TO_DISCUSS)
            : ($source['payment_terms'] ?? \App\Models\AccountingProformaInvoice::PAYMENT_TO_DISCUSS);
        $defaultTaxRate = $isEditingInvoice
            ? number_format((float) $invoice->tax_rate, 2, '.', '')
            : number_format((float) ($source['tax_rate'] ?? $defaultTaxRate), 2, '.', '');
        $defaultClientId = $isEditingInvoice ? $invoice->client_id : ($source['client_id'] ?? '');
        $linePayload = fn ($line) => [
            'line_type' => $line->line_type,
            'item_id' => $line->item_id,
            'service_id' => $line->service_id,
            'customer_order_line_id' => $line->customer_order_line_id,
            'delivery_note_line_id' => $line->delivery_note_line_id,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => $line->discount_type ?: \App\Models\AccountingSalesInvoiceLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
        ];
        $defaultLines = $isEditingInvoice
            ? $invoice->lines->map($linePayload)->values()->all()
            : ($source['lines'] ?? [[
                'line_type' => \App\Models\AccountingSalesInvoiceLine::TYPE_FREE,
                'item_id' => '',
                'service_id' => '',
                'customer_order_line_id' => '',
                'delivery_note_line_id' => '',
                'description' => '',
                'details' => '',
                'quantity' => '1',
                'unit_price' => '0',
                'discount_type' => \App\Models\AccountingSalesInvoiceLine::DISCOUNT_FIXED,
                'discount_amount' => '0',
                'create_stock_item' => '0',
            ]]);
        $oldLines = old('lines', $defaultLines);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'sales-invoices'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => $pageTitle])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.sales_invoices') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.sales_invoices_subtitle') }}</p>

                <section class="company-card proforma-page-card">
                    <form class="admin-form proforma-form proforma-page-form" method="POST" action="{{ $formAction }}" novalidate>
                        @csrf
                        @if ($isEditingInvoice)
                            @method('PUT')
                        @endif

                        <input type="hidden" name="customer_order_id" value="{{ old('customer_order_id', $isEditingInvoice ? $invoice->customer_order_id : ($source['customer_order_id'] ?? '')) }}">
                        <input type="hidden" name="delivery_note_id" value="{{ old('delivery_note_id', $isEditingInvoice ? $invoice->delivery_note_id : ($source['delivery_note_id'] ?? '')) }}">
                        <input type="hidden" name="proforma_invoice_id" value="{{ old('proforma_invoice_id', $isEditingInvoice ? $invoice->proforma_invoice_id : ($source['proforma_invoice_id'] ?? '')) }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="salesInvoiceClient" class="form-label">{{ __('main.customer') }} *</label>
                                <select id="salesInvoiceClient" name="client_id" class="form-select @error('client_id') is-invalid @enderror" data-proforma-client data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                                    <option value="">{{ __('main.choose_customer') }}</option>
                                    @foreach ($clients as $id => $label)
                                        <option value="{{ $id }}" @selected(old('client_id', $defaultClientId) == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="salesInvoiceTitle" class="form-label">{{ __('main.invoice_subject') }}</label>
                                <input id="salesInvoiceTitle" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $isEditingInvoice ? $invoice->title : ($source['title'] ?? '')) }}" placeholder="{{ __('main.invoice_subject_placeholder') }}">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="salesInvoiceDate" class="form-label">{{ __('main.date') }} *</label>
                                <input id="salesInvoiceDate" name="invoice_date" type="date" class="form-control @error('invoice_date') is-invalid @enderror" value="{{ old('invoice_date', $defaultInvoiceDate) }}">
                                @error('invoice_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="salesInvoiceDueDate" class="form-label">{{ __('main.due_date') }} *</label>
                                <input id="salesInvoiceDueDate" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $defaultDueDate) }}">
                                @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="salesInvoiceCurrency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="salesInvoiceCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency', $defaultCurrency) === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="salesInvoiceStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="salesInvoiceStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $defaultStatus) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-list-check" aria-hidden="true"></i> {{ __('main.sales_invoice_lines') }}</span>
                            </div>

                            <div class="proforma-line-list" data-proforma-line-list>
                                @foreach ($oldLines as $index => $line)
                                    @include('main.modules.partials.sales-invoice-line-row', ['index' => $index, 'line' => $line, 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
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
                                        <div class="col-12">
                                            <label for="salesInvoicePaymentTerms" class="form-label">{{ __('main.payment_terms') }}</label>
                                            <select id="salesInvoicePaymentTerms" name="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
                                                @foreach ($paymentTermLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('payment_terms', $defaultPaymentTerms) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('payment_terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="salesInvoiceNotes" class="form-label">{{ __('main.notes') }}</label>
                                            <textarea id="salesInvoiceNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes', $isEditingInvoice ? $invoice->notes : ($source['notes'] ?? '')) }}</textarea>
                                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="salesInvoiceTerms" class="form-label">{{ __('main.terms') }}</label>
                                            <textarea id="salesInvoiceTerms" name="terms" rows="3" class="form-control @error('terms') is-invalid @enderror" placeholder="{{ __('main.terms') }}">{{ old('terms', $isEditingInvoice ? $invoice->terms : ($source['terms'] ?? '')) }}</textarea>
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
                            <button class="modal-submit" type="submit">{{ $isEditingInvoice ? __('admin.update') : __('admin.create') }}</button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <template id="proformaLineTemplate">
        @include('main.modules.partials.sales-invoice-line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-sales-invoices.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-proforma-invoices.js')) !!}</script>
</body>
</html>

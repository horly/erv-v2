<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.new_delivery_note') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $indexRoute = route('main.accounting.delivery-notes', [$company, $site]);
        $submittedLines = old('lines');
        $defaultLinesByOrderLine = collect($defaultLines)->keyBy('customer_order_line_id');
        $oldLines = $submittedLines
            ? collect($submittedLines)->map(function ($line) use ($defaultLinesByOrderLine) {
                $defaultLine = $defaultLinesByOrderLine->get((int) ($line['customer_order_line_id'] ?? 0), []);

                return array_merge($defaultLine, $line);
            })->values()->all()
            : $defaultLines;
        $defaultStatus = old('status', \App\Models\AccountingDeliveryNote::STATUS_DELIVERED);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'delivery-notes'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.new_delivery_note')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.delivery_notes') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.delivery_notes_subtitle') }}</p>

                <section class="company-card proforma-page-card">
                    <form class="admin-form proforma-page-form" method="POST" action="{{ route('main.accounting.delivery-notes.store', [$company, $site]) }}" novalidate>
                        @csrf
                        <input type="hidden" name="customer_order_id" value="{{ old('customer_order_id', $order->id) }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.customer_order') }}</label>
                                <input type="text" class="form-control" value="{{ $order->reference }} - {{ $order->client?->display_name ?? '-' }}" readonly>
                                @error('customer_order_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="deliveryTitle" class="form-label">{{ __('main.order_title') }}</label>
                                <input id="deliveryTitle" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $order->title) }}" placeholder="{{ __('main.order_title_placeholder') }}">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="deliveryDate" class="form-label">{{ __('main.delivery_date') }} *</label>
                                <input id="deliveryDate" name="delivery_date" type="date" class="form-control @error('delivery_date') is-invalid @enderror" value="{{ old('delivery_date', now()->format('Y-m-d')) }}">
                                @error('delivery_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="deliveryStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="deliveryStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($defaultStatus === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="deliveredBy" class="form-label">{{ __('main.delivered_by') }}</label>
                                <input id="deliveredBy" name="delivered_by" type="text" class="form-control @error('delivered_by') is-invalid @enderror" value="{{ old('delivered_by') }}" placeholder="{{ __('main.delivered_by') }}">
                                @error('delivered_by')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="carrier" class="form-label">{{ __('main.carrier') }}</label>
                                <input id="carrier" name="carrier" type="text" class="form-control @error('carrier') is-invalid @enderror" value="{{ old('carrier') }}" placeholder="{{ __('main.carrier') }}">
                                @error('carrier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-box-arrow-up" aria-hidden="true"></i> {{ __('main.delivery_notes') }}</span>
                            </div>

                            @error('lines')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                            <div class="delivery-line-list">
                                @foreach ($oldLines as $index => $line)
                                    @php
                                        $lineType = $line['line_type'] ?? null;
                                        $lineQuantity = old('lines.'.$index.'.quantity', $line['quantity'] ?? 0);
                                        $serialNumbers = $line['serial_numbers'] ?? [];
                                        $serialFieldCount = max(count($serialNumbers), (int) floor((float) str_replace(',', '.', (string) $lineQuantity)));
                                    @endphp
                                    <div class="proforma-line-card delivery-line-card" data-delivery-line-card>
                                        <input type="hidden" name="lines[{{ $index }}][customer_order_line_id]" value="{{ $line['customer_order_line_id'] ?? '' }}">
                                        <input type="hidden" name="lines[{{ $index }}][line_type]" value="{{ $lineType }}">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-lg-4">
                                                <label class="form-label">{{ __('main.description') }}</label>
                                                <input type="text" class="form-control" value="{{ $line['description'] ?? '' }}" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">{{ __('main.ordered_quantity') }}</label>
                                                <input type="text" class="form-control text-end" value="{{ number_format((float) ($line['ordered_quantity'] ?? 0), 2, ',', ' ') }}" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">{{ __('main.already_delivered') }}</label>
                                                <input type="text" class="form-control text-end" value="{{ number_format((float) ($line['already_delivered_quantity'] ?? 0), 2, ',', ' ') }}" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">{{ __('main.remaining_quantity') }}</label>
                                                <input type="text" class="form-control text-end" value="{{ number_format((float) ($line['remaining_quantity'] ?? 0), 2, ',', ' ') }}" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">{{ __('main.quantity_to_deliver') }} *</label>
                                                <input name="lines[{{ $index }}][quantity]" type="number" min="0" step="0.01" class="form-control text-end @error('lines.'.$index.'.quantity') is-invalid @enderror" value="{{ $lineQuantity }}" placeholder="0" data-delivery-quantity>
                                                @error('lines.'.$index.'.quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            @if ($lineType === \App\Models\AccountingCustomerOrderLine::TYPE_ITEM)
                                                <div class="col-12 delivery-serial-section" data-delivery-serial-section data-line-index="{{ $index }}" data-serial-label="{{ __('main.serial_number') }}" data-serial-placeholder="{{ __('main.serial_number_placeholder') }}">
                                                    <div class="delivery-serial-heading">
                                                        <span><i class="bi bi-upc-scan" aria-hidden="true"></i> {{ __('main.serial_numbers') }}</span>
                                                        <small>{{ __('main.serial_numbers_hint') }}</small>
                                                    </div>
                                                    @error('lines.'.$index.'.serial_numbers')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                    <div class="delivery-serial-grid" data-delivery-serial-list>
                                                        @for ($serialIndex = 0; $serialIndex < $serialFieldCount; $serialIndex++)
                                                            <label class="delivery-serial-field">
                                                                <span>{{ __('main.serial_number') }} {{ $serialIndex + 1 }}</span>
                                                                <input name="lines[{{ $index }}][serial_numbers][{{ $serialIndex }}]" type="text" class="form-control @error('lines.'.$index.'.serial_numbers.'.$serialIndex) is-invalid @enderror" value="{{ $serialNumbers[$serialIndex] ?? '' }}" placeholder="{{ __('main.serial_number_placeholder') }}">
                                                                @error('lines.'.$index.'.serial_numbers.'.$serialIndex)<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                                            </label>
                                                        @endfor
                                                    </div>
                                                </div>
                                            @endif
                                            @if (! empty($line['details']))
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('main.description') }}</label>
                                                    <textarea class="form-control" rows="2" readonly>{{ $line['details'] }}</textarea>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="deliveryNotes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="deliveryNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="modal-actions">
                            <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('admin.cancel') }}</a>
                            <button class="modal-submit" type="submit">{{ __('admin.create') }}</button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-delivery-notes.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-delivery-notes.js')) !!}</script>
</body>
</html>

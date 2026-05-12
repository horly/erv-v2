<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.new_credit_note') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $indexRoute = route('main.accounting.credit-notes', [$company, $site]);
        $submittedLines = old('lines');
        $oldLines = $submittedLines
            ? collect($lineDefaults)->map(fn (array $line, int $index): array => array_merge($line, $submittedLines[$index] ?? []))->values()->all()
            : $lineDefaults;
        $defaultStatus = old('status', \App\Models\AccountingCreditNote::STATUS_VALIDATED);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'credit-notes'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.new_credit_note')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.credit_notes') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.credit_note_create_subtitle', ['reference' => $invoice->reference]) }}</p>

                @if ($errors->has('lines') || $errors->has('credit_note'))
                    <div class="alert alert-danger">
                        {{ $errors->first('lines') ?: $errors->first('credit_note') }}
                    </div>
                @endif

                <section class="company-card proforma-page-card">
                    <form class="admin-form proforma-form proforma-page-form" method="POST" action="{{ route('main.accounting.credit-notes.store', [$company, $site]) }}" novalidate>
                        @csrf
                        <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('main.sales_invoice') }}</label>
                                <input type="text" class="form-control" value="{{ $invoice->reference }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('main.customer') }}</label>
                                <input type="text" class="form-control" value="{{ $invoice->client?->display_name ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('main.creditable_amount') }}</label>
                                <input type="text" class="form-control" value="{{ number_format((float) $creditableAmount, 2, ',', ' ') }} {{ $invoice->currency }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="creditNoteDate" class="form-label">{{ __('main.date') }} *</label>
                                <input id="creditNoteDate" name="credit_date" type="date" class="form-control @error('credit_date') is-invalid @enderror" value="{{ old('credit_date', now()->format('Y-m-d')) }}">
                                @error('credit_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="creditNoteStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="creditNoteStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $value => $label)
                                        @if ($value !== \App\Models\AccountingCreditNote::STATUS_CANCELLED)
                                            <option value="{{ $value }}" @selected($defaultStatus === $value)>{{ $label }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('main.currency') }}</label>
                                <input type="text" class="form-control" value="{{ $invoice->currency }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="creditNoteReason" class="form-label">{{ __('main.credit_note_reason') }}</label>
                                <textarea id="creditNoteReason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" placeholder="{{ __('main.credit_note_reason_placeholder') }}">{{ old('reason') }}</textarea>
                                @error('reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> {{ __('main.credit_note_lines') }}</span>
                            </div>

                            <div class="proforma-line-list">
                                @foreach ($oldLines as $index => $line)
                                    <div class="proforma-line-card">
                                        <input type="hidden" name="lines[{{ $index }}][sales_invoice_line_id]" value="{{ $line['sales_invoice_line_id'] ?? '' }}">
                                        <input type="hidden" name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}">
                                        <input type="hidden" name="lines[{{ $index }}][details]" value="{{ $line['details'] ?? '' }}">
                                        <div class="row g-3">
                                            <div class="col-lg-5">
                                                <label class="form-label">{{ __('main.description') }}</label>
                                                <input type="text" class="form-control" value="{{ $line['description'] ?? '' }}" readonly>
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label">{{ __('main.remaining_quantity') }}</label>
                                                <input type="text" class="form-control text-end" value="{{ number_format((float) ($line['max_quantity'] ?? 0), 2, ',', ' ') }}" readonly>
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label">{{ __('main.quantity_to_credit') }}</label>
                                                <input name="lines[{{ $index }}][quantity]" type="number" min="0" max="{{ $line['max_quantity'] ?? 0 }}" step="0.01" class="form-control text-end @error("lines.$index.quantity") is-invalid @enderror" value="{{ old("lines.$index.quantity", $line['quantity'] ?? '0') }}" placeholder="0">
                                                @error("lines.$index.quantity")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label">{{ __('main.unit_price') }}</label>
                                                <input name="lines[{{ $index }}][unit_price]" type="number" min="0" step="0.01" class="form-control text-end @error("lines.$index.unit_price") is-invalid @enderror" value="{{ old("lines.$index.unit_price", $line['unit_price'] ?? '0') }}" placeholder="0">
                                                @error("lines.$index.unit_price")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-lg-1">
                                                <label class="form-label">{{ __('main.currency') }}</label>
                                                <input type="text" class="form-control text-center" value="{{ $invoice->currency }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>

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
</body>
</html>

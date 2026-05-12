<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.credit_note_print_title', ['reference' => $creditNote->reference]) }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <style>
        @page { margin: 28px 38px 118px 38px; }
        body, body * { font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important; }
        body { margin: 0; color: #233247; background: #ffffff; font-size: 12px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .header td, .intro-table td, .summary-table td, .conditions-signature td { vertical-align: top; }
        .brand-side { width: 56%; padding-top: 8px; }
        .document-side { width: 44%; text-align: right; }
        .brand-logo { width: 70px; height: 70px; border-radius: 9px; background: #eef6ff; color: #2c6ecb; font-size: 24px; font-weight: bold; text-align: center; vertical-align: middle; }
        .brand-logo img { max-width: 70px; max-height: 70px; }
        .brand-info { padding-left: 16px; vertical-align: middle; }
        .company-name { margin: 0; color: #172033; font-size: 23px; font-weight: bold; letter-spacing: .02em; text-transform: uppercase; }
        .company-subtitle { margin-top: 2px; color: #485a70; font-size: 12px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
        .document-title { margin: 0; color: #2f70c8; font-size: 34px; font-weight: bold; letter-spacing: .08em; line-height: 1; text-transform: uppercase; }
        .rule-table { margin-top: 16px; margin-bottom: 36px; }
        .rule-blue { width: 84px; height: 3px; background: #40aef4; }
        .rule-grey { height: 3px; background: #a9b3bf; }
        .intro-table { margin-bottom: 24px; }
        .bill-to { width: 55%; }
        .document-meta { width: 45%; text-align: right; }
        .muted-label, .meta-line { color: #233247; font-size: 13px; }
        .client-name { margin-top: 4px; color: #172033; font-size: 17px; font-weight: bold; }
        .client-line { margin-top: 6px; color: #4d5f75; font-size: 13px; }
        .meta-line { margin-top: 6px; }
        .reason { margin: 10px 0 14px; color: #172033; font-size: 13px; }
        .items { margin-top: 10px; margin-bottom: 14px; }
        .items th { padding: 6px 7px; background: #2f70c8; color: #ffffff; font-size: 11px; font-weight: bold; letter-spacing: .06em; text-transform: uppercase; }
        .items td { padding: 6px 7px; color: #243247; font-size: 12px; }
        .items tbody tr:nth-child(even) td { background: #c9e5f5; }
        .items .no { width: 34px; text-align: center; }
        .items .description { width: 46%; }
        .items .qty { width: 80px; text-align: center; }
        .items .amount { text-align: right; white-space: nowrap; }
        .line-detail { display: block; margin-top: 2px; color: #58708d; font-size: 10px; }
        .summary-left { width: 55%; vertical-align: top; }
        .summary-right { width: 45%; vertical-align: top; }
        .totals td { padding: 4px 0; font-size: 12px; }
        .totals .label { color: #34495f; text-align: right; }
        .totals .value { width: 122px; color: #243247; font-weight: 700; text-align: right; white-space: nowrap; }
        .grand-total { margin-top: 7px; }
        .grand-total td { padding: 7px 8px; background: #2f70c8; color: #ffffff; font-size: 13px; font-weight: bold; letter-spacing: .04em; text-transform: uppercase; }
        .grand-total .grand-value { text-align: right; white-space: nowrap; }
        .notice { margin-top: 20px; padding-top: 12px; border-top: 2px solid #526275; color: #172033; font-size: 12px; font-weight: bold; }
        .document-qr { margin-top: 14px; }
        .document-qr img { width: 96px; height: 96px; border: 1px solid #d6e1ee; padding: 4px; background: #ffffff; }
        .signature { width: 45%; padding-top: 18px; color: #172033; text-align: right; }
        .signature-line { width: 170px; margin-left: auto; margin-right: 0; padding-top: 26px; border-bottom: 1px solid #9aa8b8; }
        .signature-name { margin-top: 7px; font-size: 13px; font-weight: bold; }
        .signature-role { font-size: 11px; font-weight: bold; }
        .pdf-footer { position: fixed; left: 0; right: 0; bottom: -92px; color: #172033; font-size: 9.5px; line-height: 1.25; }
        .pdf-footer-line { width: 100%; margin-bottom: 4px; }
        .pdf-footer-emphasis { color: #0b55ff; font-style: italic; }
    </style>
</head>
<body>
    @php
        $formatAmount = fn ($value) => number_format((float) $value, 2, ',', ' ');
        $formatDate = fn ($date) => $date ? $date->format('d/m/Y') : '-';
        $client = $creditNote->client;
        $invoice = $creditNote->salesInvoice;
        $signatory = $creditNote->creator ?: $user;
        $currency = $creditNote->currency;
        $companyInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($company->name, 0, 2));
        $primaryAccount = $company->accounts->sortByDesc('is_primary')->first();
        $logoDataUri = null;

        if (filled($company->logo) && ! \Illuminate\Support\Str::startsWith($company->logo, ['http://', 'https://']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo);
            $logoMime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <main>
        <table class="header">
            <tr>
                <td class="brand-side">
                    <table>
                        <tr>
                            <td class="brand-logo">
                                @if ($logoDataUri)
                                    <img src="{{ $logoDataUri }}" alt="{{ $company->name }}">
                                @else
                                    {{ $companyInitials }}
                                @endif
                            </td>
                            <td class="brand-info">
                                <h1 class="company-name">{{ $company->name }}</h1>
                                <div class="company-subtitle">{{ $site->name }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="document-side">
                    <h2 class="document-title">{{ __('main.credit_note_pdf_title') }}</h2>
                </td>
            </tr>
        </table>

        <table class="rule-table"><tr><td class="rule-blue"></td><td class="rule-grey"></td></tr></table>

        <table class="intro-table">
            <tr>
                <td class="bill-to">
                    <div class="muted-label">{{ __('main.credit_to') }}</div>
                    <div class="client-name">{{ $client?->display_name ?? '-' }}</div>
                    @if ($client?->address)
                        <div class="client-line">{{ $client->address }}</div>
                    @endif
                </td>
                <td class="document-meta">
                    <div class="meta-line"><strong>{{ __('main.credit_note_no') }} :</strong> {{ $creditNote->reference }}</div>
                    <div class="meta-line">{{ __('main.date') }} : {{ $formatDate($creditNote->credit_date) }}</div>
                    <div class="meta-line">{{ __('main.sales_invoice') }} : {{ $invoice?->reference ?? '-' }}</div>
                    <div class="meta-line">{{ __('main.status') }} : {{ $statusLabels[$creditNote->status] ?? $creditNote->status }}</div>
                </td>
            </tr>
        </table>

        @if ($creditNote->reason)
            <div class="reason"><strong>{{ __('main.credit_note_reason') }} :</strong> {{ $creditNote->reason }}</div>
        @endif

        <table class="items">
            <thead>
                <tr>
                    <th class="no">NO</th>
                    <th class="description">{{ __('main.description') }}</th>
                    <th class="qty">{{ __('main.quantity') }}</th>
                    <th class="amount">{{ __('main.unit_price') }}</th>
                    <th class="amount">{{ __('main.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($creditNote->lines as $line)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="description">
                            {{ $line->description }}
                            @if ($line->details)
                                <span class="line-detail">{{ $line->details }}</span>
                            @endif
                        </td>
                        <td class="qty">{{ $formatAmount($line->quantity) }}</td>
                        <td class="amount">{{ $formatAmount($line->unit_price) }} {{ $currency }}</td>
                        <td class="amount">{{ $formatAmount($line->line_total) }} {{ $currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="summary-left">
                    <div class="notice">{{ __('main.credit_note_notice') }}</div>
                    @if ($creditNoteQrCodeDataUri)
                        <div class="document-qr">
                            <img src="{{ $creditNoteQrCodeDataUri }}" alt="{{ __('main.credit_note_qr_alt') }}">
                        </div>
                    @endif
                </td>
                <td class="summary-right">
                    <table class="totals">
                        <tr><td class="label">{{ __('main.total_ht') }} :</td><td class="value">{{ $formatAmount($creditNote->subtotal) }} {{ $currency }}</td></tr>
                        <tr><td class="label">{{ __('main.global_vat_rate') }} (%) :</td><td class="value">{{ $formatAmount($creditNote->tax_rate) }} %</td></tr>
                        <tr><td class="label">{{ __('main.vat_amount') }} :</td><td class="value">{{ $formatAmount($creditNote->tax_amount) }} {{ $currency }}</td></tr>
                    </table>
                    <table class="grand-total">
                        <tr>
                            <td>{{ __('main.credit_note_total') }} :</td>
                            <td class="grand-value">{{ $formatAmount($creditNote->total_ttc) }} {{ $currency }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="conditions-signature">
            <tr>
                <td class="summary-left"></td>
                <td class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $signatory?->name ?? '-' }}</div>
                    @if ($signatory?->grade)
                        <div class="signature-role">{{ $signatory->grade }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </main>

    <footer class="pdf-footer">
        <table class="pdf-footer-line"><tr><td class="rule-blue"></td><td class="rule-grey"></td></tr></table>
        <div><strong>{{ $company->name }}</strong></div>
        @if ($company->rccm || $company->id_nat || $company->nif)
            <div>
                @if ($company->rccm) RCCM : {{ $company->rccm }} @endif
                @if ($company->id_nat) - IDNAT : {{ $company->id_nat }} @endif
                @if ($company->nif) - NIF : {{ $company->nif }} @endif
            </div>
        @endif
        @if ($company->phone)
            <div>Contact : {{ $company->phone }}</div>
        @endif
        @if ($company->email || $company->website)
            <div>
                @if ($company->email) Email : {{ $company->email }} @endif
                @if ($company->website) - Web : {{ $company->website }} @endif
            </div>
        @endif
        @if ($company->address || $company->country)
            <div>Adresse : {{ collect([$company->address, $company->country])->filter()->implode(' - ') }}</div>
        @endif
        @if ($primaryAccount)
            <div>{{ $primaryAccount->bank_name }} / {{ $primaryAccount->account_number }} - {{ $primaryAccount->currency }}</div>
        @endif
        <div class="pdf-footer-emphasis">{{ __('main.invoice_generated_by') }}</div>
    </footer>
</body>
</html>

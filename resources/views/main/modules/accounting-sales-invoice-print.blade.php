<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.sales_invoice_print_title', ['reference' => $invoice->reference]) }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <style>
        @page { margin: 28px 38px 118px 38px; }
        body,
        body * {
            font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important;
        }
        body {
            margin: 0;
            color: #233247;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.35;
        }
        table { width: 100%; border-collapse: collapse; }
        .header td, .intro-table td, .summary-table td, .conditions-signature td { vertical-align: top; }
        .brand-side { width: 56%; padding-top: 8px; }
        .invoice-side { width: 44%; text-align: right; }
        .brand-logo {
            width: 70px;
            height: 70px;
            border-radius: 9px;
            background: #eef6ff;
            color: #2c6ecb;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .brand-logo img { max-width: 70px; max-height: 70px; }
        .brand-info { padding-left: 16px; vertical-align: middle; }
        .company-name {
            margin: 0;
            color: #172033;
            font-size: 23px;
            font-weight: bold;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .company-subtitle {
            margin-top: 2px;
            color: #485a70;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .invoice-title {
            margin: 0;
            color: #2f70c8;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: .08em;
            line-height: 1;
            text-transform: uppercase;
        }
        .rule-table { margin-top: 16px; margin-bottom: 36px; }
        .rule-blue { width: 84px; height: 3px; background: #40aef4; }
        .rule-grey { height: 3px; background: #a9b3bf; }
        .intro-table { margin-bottom: 24px; }
        .bill-to { width: 55%; }
        .invoice-meta { width: 45%; text-align: right; }
        .muted-label, .meta-line { color: #233247; font-size: 13px; }
        .client-name {
            margin-top: 4px;
            color: #172033;
            font-size: 17px;
            font-weight: bold;
        }
        .client-line { margin-top: 6px; color: #4d5f75; font-size: 13px; }
        .meta-line { margin-top: 6px; }
        .letter-subject {
            margin-top: 4px;
            margin-bottom: 12px;
            color: #172033;
            font-size: 13px;
        }
        .items { margin-top: 10px; margin-bottom: 14px; }
        .items th {
            padding: 6px 7px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .items td { padding: 6px 7px; color: #243247; font-size: 12px; }
        .items tbody tr:nth-child(even) td { background: #c9e5f5; }
        .items .no { width: 34px; text-align: center; }
        .items .description { width: 39%; }
        .items .qty { width: 68px; text-align: center; }
        .items .amount { text-align: right; white-space: nowrap; }
        .line-detail { display: block; margin-top: 2px; color: #58708d; font-size: 10px; }
        .summary-left { width: 55%; vertical-align: top; }
        .summary-right { width: 45%; vertical-align: top; }
        .totals td { padding: 4px 0; font-size: 12px; }
        .totals .label { color: #34495f; text-align: right; }
        .totals .value {
            width: 122px;
            color: #243247;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }
        .grand-total { margin-top: 7px; }
        .grand-total td {
            padding: 7px 8px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .grand-total .grand-value { text-align: right; white-space: nowrap; }
        .payment-title {
            display: inline-block;
            min-width: 145px;
            padding: 6px 8px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .payment-box { margin-top: 15px; color: #37485c; font-size: 12px; }
        .thanks {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #526275;
            color: #172033;
            font-size: 12px;
            font-weight: bold;
        }
        .conditions { width: 55%; padding-top: 18px; color: #6a7a8d; font-size: 11px; }
        .conditions strong { display: block; margin-bottom: 5px; color: #172033; font-size: 12px; }
        .conditions-body { white-space: pre-line; }
        .invoice-qr { margin-top: 14px; }
        .invoice-qr img {
            width: 96px;
            height: 96px;
            border: 1px solid #d6e1ee;
            padding: 4px;
            background: #ffffff;
        }
        .signature { width: 45%; padding-top: 18px; color: #172033; text-align: right; }
        .signature-line {
            width: 170px;
            margin-left: auto;
            margin-right: 0;
            padding-top: 26px;
            border-bottom: 1px solid #9aa8b8;
        }
        .signature-name { margin-top: 7px; font-size: 13px; font-weight: bold; }
        .signature-role { font-size: 11px; font-weight: bold; }
        .pdf-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -92px;
            color: #172033;
            font-size: 9.5px;
            line-height: 1.25;
        }
        .pdf-footer-line { width: 100%; margin-bottom: 4px; }
        .pdf-footer-emphasis { color: #0b55ff; font-style: italic; }
    </style>
</head>
<body>
    @php
        $formatAmount = fn ($value) => number_format((float) $value, 2, ',', ' ');
        $formatDate = fn ($date) => $date ? $date->format('d/m/Y') : '-';
        $client = $invoice->client;
        $signatory = $invoice->creator ?: $user;
        $currency = $invoice->currency;
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
                <td class="invoice-side">
                    <h2 class="invoice-title">{{ __('main.sales_invoice') }}</h2>
                </td>
            </tr>
        </table>

        <table class="rule-table"><tr><td class="rule-blue"></td><td class="rule-grey"></td></tr></table>

        <table class="intro-table">
            <tr>
                <td class="bill-to">
                    <div class="muted-label">{{ __('main.invoice_to') }}</div>
                    <div class="client-name">{{ $client?->display_name ?? '-' }}</div>
                    @if ($client?->address)
                        <div class="client-line">{{ $client->address }}</div>
                    @endif
                </td>
                <td class="invoice-meta">
                    <div class="meta-line"><strong>{{ __('main.invoice_no') }} :</strong> {{ $invoice->reference }}</div>
                    <div class="meta-line">{{ __('main.date') }} : {{ $formatDate($invoice->invoice_date) }}</div>
                    <div class="meta-line">{{ __('main.due_date') }} : {{ $formatDate($invoice->due_date) }}</div>
                    @if ($invoice->customerOrder)
                        <div class="meta-line">{{ __('main.customer_order') }} : {{ $invoice->customerOrder->reference }}</div>
                    @endif
                    @if ($invoice->deliveryNote)
                        <div class="meta-line">{{ __('main.delivery_note') }} : {{ $invoice->deliveryNote->reference }}</div>
                    @endif
                </td>
            </tr>
        </table>

        @if ($invoice->title && $invoice->title !== \App\Models\AccountingSalesInvoice::TITLE_CASH_REGISTER)
            <div class="letter-subject"><strong>{{ __('main.invoice_subject') }} :</strong> {{ $invoice->title }}</div>
        @endif

        <table class="items">
            <thead>
                <tr>
                    <th class="no">NO</th>
                    <th class="description">{{ __('main.description') }}</th>
                    <th class="qty">{{ __('main.quantity') }}</th>
                    <th class="amount">{{ __('main.unit_price') }}</th>
                    <th class="amount">{{ __('main.discount') }}</th>
                    <th class="amount">{{ __('main.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    @php
                        $lineLabel = $line->description
                            ?: ($line->item?->name ?? $line->service?->name ?? ($lineTypeLabels[$line->line_type] ?? $line->line_type));
                        $discountLabel = $line->discount_type === \App\Models\AccountingSalesInvoiceLine::DISCOUNT_PERCENT
                            ? $formatAmount($line->discount_amount).' %'
                            : $formatAmount($line->discount_amount).' '.$currency;
                    @endphp
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="description">
                            {{ $lineLabel }}
                            @if ($line->details)
                                <span class="line-detail">{{ $line->details }}</span>
                            @endif
                        </td>
                        <td class="qty">{{ $formatAmount($line->quantity) }}</td>
                        <td class="amount">{{ $formatAmount($line->unit_price) }} {{ $currency }}</td>
                        <td class="amount">{{ $discountLabel }}</td>
                        <td class="amount">{{ $formatAmount($line->line_total) }} {{ $currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="summary-left">
                    <span class="payment-title">{{ __('main.payment_terms') }} :</span>
                    <div class="payment-box">
                        {{ $paymentTermLabels[$invoice->payment_terms] ?? __('main.payment_terms_to_discuss') }}
                        @if ($primaryAccount)
                            <br><br>
                            {{ __('main.bank') }} : {{ $primaryAccount->bank_name ?: '-' }}<br>
                            {{ __('main.account_number') }} : {{ $primaryAccount->account_number ?: '-' }}<br>
                            {{ __('main.currency') }} : {{ $primaryAccount->currency ?: '-' }}
                        @endif
                    </div>
                    <div class="thanks">{{ __('main.thank_you_business') }}</div>
                </td>
                <td class="summary-right">
                    <table class="totals">
                        <tr><td class="label">{{ __('main.subtotal') }} :</td><td class="value">{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td></tr>
                        <tr><td class="label">{{ __('main.discount_total') }} :</td><td class="value">{{ $formatAmount($invoice->discount_total) }} {{ $currency }}</td></tr>
                        <tr><td class="label">{{ __('main.total_ht') }} :</td><td class="value">{{ $formatAmount($invoice->total_ht) }} {{ $currency }}</td></tr>
                        <tr><td class="label">{{ __('main.global_vat_rate') }} :</td><td class="value">{{ $formatAmount($invoice->tax_rate) }} %</td></tr>
                        <tr><td class="label">{{ __('main.vat_amount') }} :</td><td class="value">{{ $formatAmount($invoice->tax_amount) }} {{ $currency }}</td></tr>
                        <tr><td class="label">{{ __('main.paid_total') }} :</td><td class="value">{{ $formatAmount($invoice->paid_total) }} {{ $currency }}</td></tr>
                        <tr><td class="label">{{ __('main.balance_due') }} :</td><td class="value">{{ $formatAmount($invoice->balance_due) }} {{ $currency }}</td></tr>
                    </table>
                    <table class="grand-total">
                        <tr>
                            <td>{{ __('main.grand_total') }} :</td>
                            <td class="grand-value">{{ $formatAmount($invoice->total_ttc) }} {{ $currency }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="conditions-signature">
            <tr>
                <td class="conditions">
                    <strong>{{ __('main.terms_and_conditions') }} :</strong>
                    <div class="conditions-body">{{ $invoice->terms ?: $invoice->notes ?: __('main.default_sales_invoice_terms') }}</div>
                    <div class="invoice-qr">
                        <img src="{{ $invoiceQrCodeDataUri }}" alt="{{ __('main.sales_invoice_qr_alt') }}">
                    </div>
                </td>
                <td class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $signatory->name }}</div>
                    @if (filled($signatory->grade))
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
                @if ($company->id_nat) - ID NAT : {{ $company->id_nat }} @endif
                @if ($company->nif) - NIF : {{ $company->nif }} @endif
            </div>
        @endif
        @if ($company->phone_number)<div>Contact : {{ $company->phone_number }}</div>@endif
        @if ($company->email || $company->website)
            <div>
                @if ($company->email) Email : {{ $company->email }} @endif
                @if ($company->website) - Web : {{ $company->website }} @endif
            </div>
        @endif
        @if ($company->address || $company->country)<div>Adresse : {{ $company->address ?: $company->country }}</div>@endif
        @if ($primaryAccount)
            <div>Compte : {{ $primaryAccount->account_number ?: '-' }} - {{ $primaryAccount->currency ?: '-' }} @if ($primaryAccount->bank_name) - {{ $primaryAccount->bank_name }} @endif</div>
        @endif
        <div class="pdf-footer-emphasis">{{ __('main.invoice_generated_by') }}</div>
    </footer>
</body>
</html>

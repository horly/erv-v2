<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.proforma_print_title', ['reference' => $proforma->reference]) }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <style>
        @page {
            margin: 28px 38px 118px 38px;
        }

        body {
            margin: 0;
            color: #233247;
            background: #ffffff;
            font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace;
            font-size: 12px;
            line-height: 1.35;
        }

        body,
        body *,
        h1,
        h2,
        h3,
        p,
        div,
        span,
        strong,
        table,
        thead,
        tbody,
        tr,
        th,
        td {
            font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .document {
            width: 100%;
        }

        .header td {
            vertical-align: top;
        }

        .brand-side {
            width: 56%;
            padding-top: 8px;
        }

        .invoice-side {
            width: 44%;
            text-align: right;
        }

        .brand-logo {
            width: 66px;
            height: 66px;
            border-radius: 9px;
            background: #eef6ff;
            color: #2c6ecb;
            font-size: 23px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .brand-logo img {
            max-width: 66px;
            max-height: 66px;
        }

        .brand-info {
            padding-left: 16px;
            vertical-align: middle;
        }

        .company-name {
            margin: 0;
            color: #172033;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .company-subtitle {
            margin-top: 2px;
            color: #485a70;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .invoice-title {
            margin: 0;
            color: #2f70c8;
            font-size: 34px;
            font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important;
            font-weight: bold;
            letter-spacing: .08em;
            line-height: 1;
            text-transform: uppercase;
        }

        .site-url {
            margin-top: 12px;
            color: #3e4b5d;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .rule-table {
            margin-top: 16px;
            margin-bottom: 36px;
        }

        .rule-blue {
            width: 74px;
            height: 3px;
            background: #40aef4;
        }

        .rule-grey {
            height: 2px;
            background: #a9b3bf;
        }

        .intro-table {
            margin-bottom: 28px;
        }

        .intro-table td {
            vertical-align: top;
        }

        .bill-to {
            width: 55%;
        }

        .invoice-meta {
            width: 45%;
            text-align: right;
        }

        .muted-label {
            color: #4d5f75;
            font-size: 12px;
        }

        .client-name {
            margin-top: 4px;
            color: #172033;
            font-size: 17px;
            font-weight: bold;
        }

        .client-line {
            margin-top: 6px;
            color: #6a7a8d;
            font-size: 12px;
        }

        .meta-line {
            margin-top: 5px;
            color: #172033;
            font-size: 12px;
        }

        .meta-line strong {
            font-weight: bold;
        }

        .items {
            margin-top: 10px;
            margin-bottom: 14px;
        }

        .letter-subject {
            margin-top: 4px;
            margin-bottom: 10px;
            color: #172033;
            font-size: 12px;
        }

        .letter-subject strong {
            font-weight: bold;
        }

        .items th {
            padding: 5px 7px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .items td {
            padding: 5px 7px;
            color: #243247;
            font-size: 11px;
        }

        .items tbody tr:nth-child(odd) td {
            background: #ffffff;
        }

        .items tbody tr:nth-child(even) td {
            background: #c9e5f5;
        }

        .items .no {
            width: 34px;
            text-align: center;
        }

        .items .description {
            width: 39%;
        }

        .items .qty {
            width: 60px;
            text-align: center;
        }

        .items .amount {
            text-align: right;
            white-space: nowrap;
        }

        .line-detail {
            display: block;
            margin-top: 2px;
            color: #58708d;
            font-size: 9.5px;
        }

        .summary-table {
            margin-top: 4px;
            margin-bottom: 20px;
        }

        .summary-left {
            width: 55%;
            vertical-align: top;
        }

        .summary-right {
            width: 45%;
            vertical-align: top;
        }

        .totals td {
            padding: 4px 0;
            font-size: 11px;
        }

        .totals .label {
            color: #34495f;
            text-align: right;
        }

        .totals .value {
            width: 116px;
            color: #243247;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .grand-total {
            margin-top: 7px;
        }

        .grand-total td {
            padding: 6px 8px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .grand-total .grand-label {
            text-align: left;
        }

        .grand-total .grand-value {
            text-align: right;
            white-space: nowrap;
        }

        .payment-title {
            display: inline-block;
            min-width: 145px;
            padding: 6px 8px;
            background: #2f70c8;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .payment-box {
            margin-top: 15px;
            color: #37485c;
            font-size: 11px;
        }

        .thanks {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #526275;
            color: #172033;
            font-size: 12px;
            font-weight: bold;
        }

        .conditions-signature td {
            vertical-align: bottom;
        }

        .conditions {
            width: 55%;
            padding-top: 18px;
            color: #6a7a8d;
            font-size: 10px;
        }

        .conditions strong {
            display: block;
            margin-bottom: 5px;
            color: #172033;
            font-size: 11.5px;
        }

        .conditions-body {
            white-space: pre-line;
        }

        .invoice-qr {
            margin-top: 14px;
        }

        .invoice-qr img {
            width: 84px;
            height: 84px;
            border: 1px solid #d6e1ee;
            padding: 4px;
            background: #ffffff;
        }

        .signature {
            width: 45%;
            padding-top: 18px;
            color: #172033;
            text-align: right;
        }

        .signature-line {
            width: 170px;
            margin-left: auto;
            margin-right: 0;
            padding-top: 26px;
            border-bottom: 1px solid #9aa8b8;
        }

        .signature-name {
            margin-top: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .signature-role {
            font-size: 11px;
            font-weight: bold;
        }

        .pdf-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -92px;
            color: #172033;
            font-size: 9.5px;
            line-height: 1.25;
        }

        .pdf-footer-line {
            width: 100%;
            margin-bottom: 4px;
            border-collapse: collapse;
        }

        .pdf-footer-line .rule-blue,
        .pdf-footer-line .rule-grey {
            height: 3px;
        }

        .pdf-footer strong {
            font-weight: bold;
        }

        .pdf-footer-emphasis {
            color: #0b55ff;
            font-style: italic;
        }

        .footer-rule,
        .footer-contact {
            display: none;
        }
    </style>
</head>
<body>
    @php
        $formatAmount = fn ($value) => number_format((float) $value, 2, ',', ' ');
        $formatDate = fn ($date) => $date ? $date->format('d/m/Y') : '-';
        $client = $proforma->client;
        $signatory = $proforma->creator ?: $user;
        $currency = $proforma->currency;
        $companyInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($company->name, 0, 2));
        $primaryAccount = $company->accounts->sortByDesc('is_primary')->first();
        $logoDataUri = null;

        if (filled($company->logo) && ! \Illuminate\Support\Str::startsWith($company->logo, ['http://', 'https://']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo);
            $logoMime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <main class="document">
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
                    <h2 class="invoice-title">{{ __('main.proforma_invoice') }}</h2>
                    @if ($company->website)
                        <div class="site-url">{{ $company->website }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="rule-table">
            <tr>
                <td class="rule-blue"></td>
                <td class="rule-grey"></td>
            </tr>
        </table>

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
                    <div class="meta-line"><strong>{{ __('main.invoice_no') }} :</strong> {{ $proforma->reference }}</div>
                    <div class="meta-line">{{ __('main.date') }} : {{ $formatDate($proforma->issue_date) }}</div>
                    <div class="meta-line">{{ __('main.offer_validity') }} : {{ $formatDate($proforma->expiration_date) }}</div>
                </td>
            </tr>
        </table>

        @if ($proforma->title)
            <div class="letter-subject"><strong>{{ __('main.proforma_title') }} :</strong> {{ $proforma->title }}</div>
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
                @foreach ($proforma->lines as $line)
                    @php
                        $lineLabel = $line->description
                            ?: ($line->item?->name ?? $line->service?->name ?? ($lineTypeLabels[$line->line_type] ?? $line->line_type));
                        $discountLabel = $line->discount_type === \App\Models\AccountingProformaInvoiceLine::DISCOUNT_PERCENT
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
                        {{ $paymentTermLabels[$proforma->payment_terms] ?? __('main.payment_terms_to_discuss') }}
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
                        <tr>
                            <td class="label">{{ __('main.subtotal') }} :</td>
                            <td class="value">{{ $formatAmount($proforma->subtotal) }} {{ $currency }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ __('main.discount_total') }} :</td>
                            <td class="value">{{ $formatAmount($proforma->discount_total) }} {{ $currency }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ __('main.total_ht') }} :</td>
                            <td class="value">{{ $formatAmount($proforma->total_ht) }} {{ $currency }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ __('main.global_vat_rate') }} :</td>
                            <td class="value">{{ $formatAmount($proforma->tax_rate) }} %</td>
                        </tr>
                        <tr>
                            <td class="label">{{ __('main.vat_amount') }} :</td>
                            <td class="value">{{ $formatAmount($proforma->tax_amount) }} {{ $currency }}</td>
                        </tr>
                    </table>
                    <table class="grand-total">
                        <tr>
                            <td class="grand-label">{{ __('main.grand_total') }} :</td>
                            <td class="grand-value">{{ $formatAmount($proforma->total_ttc) }} {{ $currency }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="conditions-signature">
            <tr>
                <td class="conditions">
                    <strong>{{ __('main.terms_and_conditions') }} :</strong>
                    <div class="conditions-body">{{ $proforma->terms ?: $proforma->notes ?: __('main.default_invoice_terms') }}</div>
                    <div class="invoice-qr">
                        <img src="{{ $proformaQrCodeDataUri }}" alt="{{ __('main.proforma_qr_alt') }}">
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

        <table class="rule-table footer-rule">
            <tr>
                <td class="rule-blue"></td>
                <td class="rule-grey"></td>
            </tr>
        </table>

        <table class="footer-contact">
            <tr>
                <td><span class="contact-icon">☎</span> {{ $company->phone_number ?: '-' }}</td>
                <td><span class="contact-icon">✉</span> {{ $company->email ?: '-' }}</td>
                <td><span class="contact-icon">⌖</span> {{ $company->address ?: $company->country ?: '-' }}</td>
            </tr>
        </table>
    </main>

    <footer class="pdf-footer">
        <table class="pdf-footer-line">
            <tr>
                <td class="rule-blue"></td>
                <td class="rule-grey"></td>
            </tr>
        </table>
        <div><strong>{{ $company->name }}</strong></div>
        @if ($company->rccm || $company->id_nat || $company->nif)
            <div>
                @if ($company->rccm) RCCM : {{ $company->rccm }} @endif
                @if ($company->id_nat) - ID NAT : {{ $company->id_nat }} @endif
                @if ($company->nif) - NIF : {{ $company->nif }} @endif
            </div>
        @endif
        @if ($company->phone_number)
            <div>Contact : {{ $company->phone_number }}</div>
        @endif
        @if ($company->email || $company->website)
            <div>
                @if ($company->email) Email : {{ $company->email }} @endif
                @if ($company->website) - Web : {{ $company->website }} @endif
            </div>
        @endif
        @if ($company->address || $company->country)
            <div>Adresse : {{ $company->address ?: $company->country }}</div>
        @endif
        @if ($primaryAccount)
            <div>Compte : {{ $primaryAccount->account_number ?: '-' }} - {{ $primaryAccount->currency ?: '-' }} @if ($primaryAccount->bank_name) - {{ $primaryAccount->bank_name }} @endif</div>
        @endif
        <div class="pdf-footer-emphasis">{{ __('main.invoice_generated_by') }}</div>
    </footer>
</body>
</html>

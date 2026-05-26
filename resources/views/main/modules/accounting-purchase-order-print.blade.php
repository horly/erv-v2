@php
    $pdfSettings = $site->accountingModuleSetting;
    $pdfPrimaryColor = $pdfSettings?->pdf_primary_color ?: '#2F70C8';
    $pdfAccentColor = $pdfSettings?->pdf_accent_color ?: '#40AEF4';
    $pdfTintColor = $pdfSettings?->pdf_tint_color ?: '#D7EEF8';
    $pdfShowQrCode = $pdfSettings?->pdf_show_qr_code ?? true;
    $pdfShowFooterBranding = $pdfSettings?->pdf_show_footer_branding ?? true;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.purchase_order_print_title', ['reference' => $purchaseOrder->reference]) }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <style>
        @page { margin: 28px 38px 118px 38px; }
        * { box-sizing: border-box; }
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
        .header,
        .header table {
            margin-top: 0;
        }
        .header td {
            vertical-align: top;
            border-bottom: 0;
            padding: 0;
        }
        .header .brand-side {
            width: 56%;
            padding-top: 8px;
        }
        .document-side {
            width: 44%;
            text-align: right;
        }
        .brand-logo {
            width: 70px;
            height: 70px;
            border-radius: 9px;
            background: #eef6ff;
            color: #2c6ecb;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle !important;
        }
        .brand-logo img {
            max-width: 70px;
            max-height: 70px;
        }
        .brand-info {
            padding-left: 16px !important;
            vertical-align: middle !important;
        }
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
        .document-title {
            margin: 0 0 8px;
            color: #2f70c8;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: .08em;
            line-height: 1;
            text-transform: uppercase;
        }
        .document-meta {
            color: #172033;
            font-size: 12px;
            font-weight: bold;
        }
        h2 { clear: both; margin: 28px 0 12px; font-size: 15px; text-transform: uppercase; }
        .rule-table {
            margin-top: 16px;
            margin-bottom: 36px;
        }
        .rule-blue {
            width: 84px;
            height: 3px;
            background: #40aef4;
        }
        .rule-grey {
            height: 3px;
            background: #a9b3bf;
        }
        .rule-table td,
        .pdf-footer-line td {
            padding: 0;
            border-bottom: 0;
        }
        .grid { width: 100%; margin-bottom: 22px; }
        .grid td { width: 50%; vertical-align: top; }
        .muted { color: #63728a; }
        .strong { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #2f6fcf; color: #fff; padding: 8px 10px; font-weight: bold; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #d9e3f1; vertical-align: top; }
        tbody tr:nth-child(even) td { background: #cbe8f5; }
        .right { text-align: right; }
        .totals { width: 44%; margin-left: auto; margin-top: 18px; }
        .totals td { border-bottom: 0; padding: 5px 8px; }
        .grand td { background: #2f6fcf !important; color: #fff; font-weight: bold; padding: 9px 8px; }
        .conditions-signature td {
            border-bottom: 0;
            background: transparent !important;
        }
        .conditions {
            width: 55%;
            padding-top: 18px;
            color: #6a7a8d;
            font-size: 11px;
        }
        .conditions strong {
            display: block;
            margin-bottom: 5px;
            color: #172033;
            font-size: 12px;
        }
        .conditions-body {
            white-space: pre-line;
        }
        .invoice-qr {
            margin-top: 14px;
        }
        .invoice-qr img {
            width: 96px;
            height: 96px;
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
        }
        .pdf-footer-emphasis {
            color: #0b55ff;
            font-style: italic;
        }
        .document-title, .pdf-footer-emphasis { color: {{ $pdfPrimaryColor }}; }
        .rule-blue { background: {{ $pdfAccentColor }}; }
        th, .grand td { background: {{ $pdfPrimaryColor }} !important; }
        tbody tr:nth-child(even) td { background: {{ $pdfTintColor }}; }
    </style>
</head>
<body>
    @php
        $companyInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($company->name, 0, 2));
        $signatory = $purchaseOrder->creator ?: $user;
        $primaryAccount = $company->accounts->sortByDesc('is_primary')->first();
        $logoDataUri = null;

        if (filled($company->logo) && ! \Illuminate\Support\Str::startsWith($company->logo, ['http://', 'https://']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo);
            $logoMime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
        }
    @endphp

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
                <h2 class="document-title">{{ __('main.purchase_order_document_title') }}</h2>
                <div class="document-meta">{{ $purchaseOrder->reference }}</div>
                <div>{{ $statusLabels[$purchaseOrder->status] ?? $purchaseOrder->status }}</div>
            </td>
        </tr>
    </table>

    <table class="rule-table">
        <tr>
            <td class="rule-blue"></td>
            <td class="rule-grey"></td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <span class="muted">{{ __('main.ordered_from') }}</span><br>
                <span class="strong" style="font-size: 14px;">{{ $purchaseOrder->supplier?->name ?? '-' }}</span><br>
                @if ($purchaseOrder->supplier?->address){{ $purchaseOrder->supplier->address }}<br>@endif
                @if ($purchaseOrder->supplier?->email){{ $purchaseOrder->supplier->email }}<br>@endif
                @if ($purchaseOrder->supplier?->phone){{ $purchaseOrder->supplier->phone }}@endif
            </td>
            <td class="right">
                <span class="strong">{{ __('main.reference') }} :</span> {{ $purchaseOrder->reference }}<br>
                <span class="strong">{{ __('main.date') }} :</span> {{ optional($purchaseOrder->order_date)->format('d/m/Y') }}<br>
                <span class="strong">{{ __('main.expected_delivery_date') }} :</span> {{ optional($purchaseOrder->expected_delivery_date)->format('d/m/Y') ?: '-' }}<br>
                @if ($purchaseOrder->supplier_reference)
                    <span class="strong">{{ __('main.supplier_reference') }} :</span> {{ $purchaseOrder->supplier_reference }}
                @endif
            </td>
        </tr>
    </table>

    @if ($purchaseOrder->title)
        <p><span class="strong">{{ __('main.object') }} :</span> {{ $purchaseOrder->title }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">NO</th>
                <th>{{ __('main.description') }}</th>
                <th class="right" style="width: 15%;">{{ __('main.quantity') }}</th>
                <th class="right" style="width: 18%;">{{ __('main.unit_price') }}</th>
                <th class="right" style="width: 14%;">{{ __('main.discount') }}</th>
                <th class="right" style="width: 18%;">{{ __('main.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->lines as $line)
                <tr>
                    <td class="right">{{ $loop->iteration }}</td>
                    <td>
                        {{ $line->description }}
                        @if ($line->details)
                            <br><span class="muted">{!! nl2br(e($line->details)) !!}</span>
                        @endif
                    </td>
                    <td class="right">{{ number_format((float) $line->quantity, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format((float) $line->unit_price, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td>
                    <td class="right">
                        {{ number_format((float) $line->discount_amount, 2, ',', ' ') }}
                        {{ $line->discount_type === \App\Models\AccountingPurchaseOrderLine::DISCOUNT_PERCENT ? '%' : $purchaseOrder->currency }}
                    </td>
                    <td class="right">{{ number_format((float) $line->line_total, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>{{ __('main.subtotal') }} :</td><td class="right strong">{{ number_format((float) $purchaseOrder->subtotal, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td></tr>
        <tr><td>{{ __('main.discount_total') }} :</td><td class="right strong">{{ number_format((float) $purchaseOrder->discount_total, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td></tr>
        <tr><td>{{ __('main.total_ht') }} :</td><td class="right strong">{{ number_format((float) $purchaseOrder->total_ht, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td></tr>
        <tr><td>{{ __('main.global_vat_rate') }} :</td><td class="right strong">{{ number_format((float) $purchaseOrder->tax_rate, 2, ',', ' ') }} %</td></tr>
        <tr><td>{{ __('main.vat_amount') }} :</td><td class="right strong">{{ number_format((float) $purchaseOrder->tax_amount, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td></tr>
        <tr class="grand"><td>{{ __('main.total_ttc') }} :</td><td class="right">{{ number_format((float) $purchaseOrder->total_ttc, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td></tr>
    </table>

    <table class="conditions-signature">
        <tr>
            <td class="conditions">
                <strong>{{ __('main.terms_and_conditions') }} :</strong>
                <div class="conditions-body">{{ $purchaseOrder->terms ?: $purchaseOrder->notes ?: __('main.default_invoice_terms') }}</div>
                @if ($pdfShowQrCode)
                    <div class="invoice-qr">
                        <img src="{{ $purchaseOrderQrCodeDataUri }}" alt="{{ __('main.purchase_order_document_title') }}">
                    </div>
                @endif
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
        @if ($pdfShowFooterBranding)
            <div class="pdf-footer-emphasis">{{ __('main.invoice_generated_by', ['app' => app_brand_name()]) }}</div>
        @endif
    </footer>
</body>
</html>

@php
    $pdfSettings = $site->accountingModuleSetting;
    $pdfPrimaryColor = $pdfSettings?->pdf_primary_color ?: '#2F70C8';
    $pdfAccentColor = $pdfSettings?->pdf_accent_color ?: '#40AEF4';
    $pdfTintColor = $pdfSettings?->pdf_tint_color ?: '#D7EEF8';
    $pdfShowFooterBranding = $pdfSettings?->pdf_show_footer_branding ?? true;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.payment_reminder_letter') }} {{ $reminder->reference }}</title>
    <style>
        @page { margin: 28px 38px 94px; }
        * { box-sizing: border-box; }
        body, body * { font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important; }
        body { margin: 0; color: #172033; background: #fff; font-size: 11px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        .header td { padding: 0; vertical-align: top; }
        .brand-side { width: 56%; padding-top: 8px; }
        .document-side { width: 44%; text-align: right; }
        .brand-name { margin: 0; font-size: 21px; font-weight: bold; text-transform: uppercase; }
        .brand-site { margin-top: 3px; color: #52647c; font-weight: bold; text-transform: uppercase; }
        .document-title { margin: 0 0 8px; color: #2f70c8; font-size: 29px; line-height: 1.05; font-weight: bold; text-transform: uppercase; }
        .rule { margin: 22px 0 34px; }
        .rule td:first-child { width: 84px; height: 3px; background: #40aef4; }
        .rule td:last-child { height: 3px; background: #a9b3bf; }
        .intro td { vertical-align: top; }
        .customer { width: 56%; }
        .meta { width: 44%; text-align: right; }
        .label { color: #5e718c; }
        .customer-name { margin-top: 5px; font-size: 16px; font-weight: bold; }
        .meta div { margin-bottom: 5px; }
        .subject {
            margin: 30px 0 17px;
            padding-bottom: 7px;
            border-bottom: 1px solid #d8e3f1;
            color: #172033;
            font-size: 12px;
            font-weight: bold;
        }
        .message { min-height: 88px; white-space: pre-line; color: #34495f; font-size: 12px; line-height: 1.65; }
        .balance {
            margin: 27px 0 24px;
            border: 1px solid #d7e2f0;
        }
        .balance td { padding: 11px 12px; }
        .balance .value { color: #195ae0; font-size: 17px; font-weight: bold; text-align: right; }
        .close { margin-top: 17px; color: #34495f; }
        .signature { margin-top: 40px; width: 42%; margin-left: auto; text-align: right; }
        .signature-line { padding-top: 22px; border-bottom: 1px solid #9aa8b8; }
        .signature-name { margin-top: 7px; font-weight: bold; }
        .footer { position: fixed; left: 0; right: 0; bottom: -67px; font-size: 9px; line-height: 1.25; }
        .footer .rule { margin: 0 0 5px; }
        .footer em { color: #0b55ff; }
        .document-title, .footer em, .balance .value { color: {{ $pdfPrimaryColor }}; }
        .rule td:first-child { background: {{ $pdfAccentColor }}; }
        .balance { background: {{ $pdfTintColor }}; }
    </style>
</head>
<body>
    @php
        $dueDate = $reminder->salesInvoice?->due_date ?: $reminder->debtor?->due_date;
        $money = number_format((float) $balance, 2, ',', ' ').' '.$currency;
    @endphp

    <table class="header">
        <tr>
            <td class="brand-side">
                <div class="brand-name">{{ $company->name }}</div>
                <div class="brand-site">{{ $site->name }}</div>
            </td>
            <td class="document-side">
                <h1 class="document-title">{{ __('main.payment_reminder_letter') }}</h1>
                <strong>{{ $reminder->reference }}</strong><br>
                {{ $levelLabels[$reminder->level] ?? $reminder->level }}
            </td>
        </tr>
    </table>
    <table class="rule"><tr><td></td><td></td></tr></table>

    <table class="intro">
        <tr>
            <td class="customer">
                <div class="label">{{ __('main.customer') }}</div>
                <div class="customer-name">{{ $customerName ?: '-' }}</div>
            </td>
            <td class="meta">
                <div>{{ __('main.reference') }} : <strong>{{ $sourceReference ?: '-' }}</strong></div>
                <div>{{ __('main.date') }} : <strong>{{ optional($reminder->sent_at)->format('d/m/Y') }}</strong></div>
                @if ($dueDate)<div>{{ __('main.due_date') }} : <strong>{{ $dueDate->format('d/m/Y') }}</strong></div>@endif
            </td>
        </tr>
    </table>

    <div class="subject">{{ __('main.subject') }} : {{ $reminder->subject }}</div>
    <p>{{ __('main.payment_reminder_letter_intro') }}</p>
    <div class="message">{{ $reminder->message }}</div>

    <table class="balance">
        <tr>
            <td>{{ __('main.payment_reminder_amount_due') }}</td>
            <td class="value">{{ $money }}</td>
        </tr>
    </table>

    <p class="close">{{ __('main.payment_reminder_polite_close') }}</p>

    <div class="signature">
        <div class="signature-line"></div>
        <div class="signature-name">{{ $reminder->creator?->name ?: $user->name }}</div>
        @if (($reminder->creator?->grade ?: $user->grade))
            <div>{{ $reminder->creator?->grade ?: $user->grade }}</div>
        @endif
    </div>

    <div class="footer">
        <table class="rule"><tr><td></td><td></td></tr></table>
        <strong>{{ $company->name }}</strong><br>
        {{ $site->name }}@if ($company->email) | {{ $company->email }}@endif<br>
        @if ($pdfShowFooterBranding)
            <em>{{ __('main.generated_by_exad_erp', ['app' => app_brand_name()]) }}</em>
        @endif
    </div>
</body>
</html>

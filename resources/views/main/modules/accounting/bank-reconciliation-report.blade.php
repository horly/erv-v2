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
    <title>{{ __('main.bank_reconciliation_report') }} {{ $reconciliation->reference }}</title>
    <style>
        @page { margin: 28px 38px 96px; }
        * { box-sizing: border-box; }
        body, body * { font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important; }
        body { margin: 0; color: #172033; background: #fff; font-size: 11px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .header td { padding: 0; vertical-align: top; }
        .brand-side { width: 56%; }
        .document-side { width: 44%; text-align: right; }
        .brand-name { margin: 0; font-size: 21px; font-weight: bold; text-transform: uppercase; }
        .brand-site { margin-top: 3px; color: #52647c; font-weight: bold; text-transform: uppercase; }
        .document-title { margin: 0 0 8px; color: #2f70c8; font-size: 29px; line-height: 1.05; font-weight: bold; text-transform: uppercase; }
        .rule { margin: 22px 0 30px; }
        .rule td:first-child { width: 84px; height: 3px; background: #40aef4; }
        .rule td:last-child { height: 3px; background: #a9b3bf; }
        h2 { margin: 24px 0 10px; font-size: 14px; text-transform: uppercase; }
        .meta td { padding: 8px 10px; border-bottom: 1px solid #d9e3f1; }
        .meta tr:nth-child(even) td { background: #d7eef8; }
        .metric-table { margin-top: 15px; }
        .metric-table td { width: 25%; padding: 12px 10px; border: 1px solid #d8e3f1; }
        .metric-table span { display: block; margin-bottom: 7px; color: #60718a; }
        .metric-table strong { font-size: 15px; }
        .lines { margin-top: 10px; }
        .lines th { padding: 8px 9px; color: #fff; background: #2f70c8; text-align: left; }
        .lines td { padding: 8px 9px; border-bottom: 1px solid #d9e3f1; vertical-align: top; }
        .lines tr:nth-child(even) td { background: #d7eef8; }
        .right { text-align: right !important; }
        .status { font-weight: bold; }
        .signature { margin-top: 30px; width: 34%; margin-left: auto; text-align: right; }
        .signature-line { padding-top: 24px; border-bottom: 1px solid #9aa8b8; }
        .signature-name { margin-top: 7px; font-weight: bold; }
        .footer { position: fixed; left: 0; right: 0; bottom: -70px; font-size: 9px; line-height: 1.25; }
        .footer .rule { margin: 0 0 5px; }
        .footer em { color: #0b55ff; }
        .document-title, .footer em { color: {{ $pdfPrimaryColor }}; }
        .rule td:first-child { background: {{ $pdfAccentColor }}; }
        .lines th { background: {{ $pdfPrimaryColor }}; }
        .meta tr:nth-child(even) td, .lines tr:nth-child(even) td { background: {{ $pdfTintColor }}; }
    </style>
</head>
<body>
    @php
        $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$reconciliation->currency;
    @endphp

    <table class="header">
        <tr>
            <td class="brand-side">
                <div class="brand-name">{{ $company->name }}</div>
                <div class="brand-site">{{ $site->name }}</div>
            </td>
            <td class="document-side">
                <h1 class="document-title">{{ __('main.bank_reconciliation_report') }}</h1>
                <strong>{{ $reconciliation->reference }}</strong><br>
                {{ $statusLabels[$reconciliation->status] ?? $reconciliation->status }}
            </td>
        </tr>
    </table>
    <table class="rule"><tr><td></td><td></td></tr></table>

    <h2>{{ __('main.reconciliation_details') }}</h2>
    <table class="meta">
        <tr>
            <td>{{ __('main.bank_account') }}</td>
            <td><strong>{{ $reconciliation->paymentMethod?->name }}</strong></td>
            <td>{{ __('main.period') }}</td>
            <td>{{ optional($reconciliation->period_start)->format('d/m/Y') }} - {{ optional($reconciliation->period_end)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>{{ __('main.prepared_by') }}</td>
            <td>{{ $reconciliation->creator?->name ?: '-' }}</td>
            <td>{{ __('main.validated_by') }}</td>
            <td>{{ $reconciliation->closer?->name ?: '-' }}</td>
        </tr>
    </table>

    <table class="metric-table">
        <tr>
            <td><span>{{ __('main.statement_opening_balance') }}</span><strong>{{ $money($reconciliation->statement_opening_balance) }}</strong></td>
            <td><span>{{ __('main.statement_closing_balance') }}</span><strong>{{ $money($reconciliation->statement_closing_balance) }}</strong></td>
            <td><span>{{ __('main.erp_closing_balance') }}</span><strong>{{ $money($reconciliation->erp_closing_balance) }}</strong></td>
            <td><span>{{ __('main.difference') }}</span><strong>{{ $money($reconciliation->difference) }}</strong></td>
        </tr>
    </table>

    <h2>{{ __('main.bank_statement_lines') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.date') }}</th>
                <th>{{ __('main.description') }}</th>
                <th>{{ __('main.direction') }}</th>
                <th class="right">{{ __('main.amount') }}</th>
                <th>{{ __('main.erp_correspondence') }}</th>
                <th>{{ __('main.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reconciliation->lines as $line)
                @php $movement = $line->matches->first()?->treasuryMovement; @endphp
                <tr>
                    <td>{{ optional($line->transaction_date)->format('d/m/Y') }}</td>
                    <td>{{ $line->description }}</td>
                    <td>{{ $directionLabels[$line->direction] ?? $line->direction }}</td>
                    <td class="right">{{ $money($line->amount) }}</td>
                    <td>{{ $movement?->reference ?: '-' }}</td>
                    <td class="status">{{ $lineStatusLabels[$line->status] ?? $line->status }}</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('main.no_bank_statement_lines') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($reconciliation->notes)
        <h2>{{ __('main.notes') }}</h2>
        <p>{{ $reconciliation->notes }}</p>
    @endif

    @if ($reconciliation->closer)
        <div class="signature">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $reconciliation->closer->name }}</div>
            @if ($reconciliation->closer->grade)<div>{{ $reconciliation->closer->grade }}</div>@endif
        </div>
    @endif

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

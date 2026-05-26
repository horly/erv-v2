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
    <title>{{ __('main.reports') }} - {{ $sections[$section] }}</title>
    <style>
        @page { margin: 28px 38px 92px; }
        * { box-sizing: border-box; }
        body, body * { font-family: "Courier", "Courier New", "DejaVu Sans Mono", monospace !important; }
        body { margin: 0; color: #172033; background: #fff; font-size: 10px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .header td { padding: 0; vertical-align: top; }
        .brand-side { width: 52%; }
        .document-side { width: 48%; text-align: right; }
        .brand-name { margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .brand-site { margin-top: 3px; color: #52647c; font-weight: bold; text-transform: uppercase; }
        .document-title { margin: 0 0 6px; color: #2f70c8; font-size: 27px; line-height: 1.06; font-weight: bold; text-transform: uppercase; }
        .document-section { color: #52647c; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .rule { margin: 20px 0 24px; }
        .rule td:first-child { width: 84px; height: 3px; background: #40aef4; }
        .rule td:last-child { height: 3px; background: #a9b3bf; }
        h2 { margin: 20px 0 10px; font-size: 13px; text-transform: uppercase; }
        .period { margin: 0 0 12px; color: #52647c; }
        .metric-table td { width: 25%; padding: 11px 10px; border: 1px solid #d8e3f1; background: #f8fbff; vertical-align: top; }
        .metric-table span { display: block; margin-bottom: 7px; color: #60718a; }
        .metric-table strong { font-size: 13px; }
        .lines { margin-top: 10px; }
        .lines th { padding: 8px 8px; color: #fff; background: #2f70c8; text-align: left; font-size: 9px; text-transform: uppercase; }
        .lines td { padding: 8px 8px; border-bottom: 1px solid #d9e3f1; vertical-align: top; }
        .lines tr:nth-child(even) td { background: #d7eef8; }
        .right { text-align: right !important; white-space: nowrap; }
        .status { font-weight: bold; text-transform: uppercase; }
        .empty { padding: 16px !important; text-align: center; color: #60718a; }
        .footer { position: fixed; left: 0; right: 0; bottom: -68px; font-size: 9px; line-height: 1.25; }
        .footer .rule { margin: 0 0 5px; }
        .footer em { color: #0b55ff; }
        .document-title, .footer em { color: {{ $pdfPrimaryColor }}; }
        .rule td:first-child { background: {{ $pdfAccentColor }}; }
        .lines th { background: {{ $pdfPrimaryColor }}; }
        .lines tr:nth-child(even) td { background: {{ $pdfTintColor }}; }
    </style>
</head>
<body>
    @php
        $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$currency;
        $number = fn ($value) => number_format((float) $value, 2, ',', ' ');
    @endphp

    <table class="header">
        <tr>
            <td class="brand-side">
                <div class="brand-name">{{ $company->name }}</div>
                <div class="brand-site">{{ $site->name }}</div>
            </td>
            <td class="document-side">
                <h1 class="document-title">{{ __('main.reports') }}</h1>
                <div class="document-section">{{ $sections[$section] }}</div>
            </td>
        </tr>
    </table>
    <table class="rule"><tr><td></td><td></td></tr></table>

    <p class="period">{{ __('main.report_period_label') }} <strong>{{ $periodLabel }}</strong> | {{ $currency }}</p>

    <table class="metric-table">
        <tr>
            @foreach ($metrics as $metric)
                <td>
                    <span>{{ $metric['label'] }}</span>
                    <strong>{{ $metric['isMoney'] ? $money($metric['value']) : number_format($metric['value'], 0, ',', ' ') }}</strong>
                </td>
            @endforeach
        </tr>
    </table>

    <h2>{{ __('main.report_details') }}</h2>
    <table class="lines">
        @if ($section === 'sales')
            <thead><tr>
                <th>{{ __('main.reference') }}</th>
                <th>{{ __('main.customer') }}</th>
                <th>{{ __('main.date') }}</th>
                <th class="right">{{ __('main.total_ttc') }}</th>
                <th class="right">{{ __('main.paid_total') }}</th>
                <th class="right">{{ __('main.balance_due') }}</th>
                <th>{{ __('main.status') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->reference }}</td>
                        <td>{{ $record->client?->display_name ?: '-' }}</td>
                        <td>{{ optional($record->invoice_date)->format('d/m/Y') }}</td>
                        <td class="right">{{ $money($record->total_ttc) }}</td>
                        <td class="right">{{ $money($record->paid_total) }}</td>
                        <td class="right">{{ $money($record->balance_due) }}</td>
                        <td class="status">{{ $statusLabels[$record->status] ?? $record->status }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="7">{{ __('main.report_no_results') }}</td></tr>
                @endforelse
            </tbody>
        @elseif ($section === 'receipts')
            <thead><tr>
                <th>{{ __('main.payment_date') }}</th>
                <th>{{ __('main.reference') }}</th>
                <th>{{ __('main.sales_invoices') }}</th>
                <th>{{ __('main.customer') }}</th>
                <th>{{ __('main.payment_method') }}</th>
                <th class="right">{{ __('main.amount') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ optional($record->payment_date)->format('d/m/Y') }}</td>
                        <td>{{ $record->reference }}</td>
                        <td>{{ $record->salesInvoice?->reference ?: '-' }}</td>
                        <td>{{ $record->salesInvoice?->client?->display_name ?: '-' }}</td>
                        <td>{{ $record->paymentMethod?->name ?: '-' }}</td>
                        <td class="right">{{ $money($record->amount) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="6">{{ __('main.report_no_results') }}</td></tr>
                @endforelse
            </tbody>
        @elseif ($section === 'purchases')
            <thead><tr>
                <th>{{ __('main.reference') }}</th>
                <th>{{ __('main.supplier') }}</th>
                <th>{{ __('main.purchase_date') }}</th>
                <th class="right">{{ __('main.total_ttc') }}</th>
                <th class="right">{{ __('main.paid_total') }}</th>
                <th class="right">{{ __('main.balance_due') }}</th>
                <th>{{ __('main.status') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->reference }}</td>
                        <td>{{ $record->supplier?->name ?: '-' }}</td>
                        <td>{{ optional($record->purchase_date)->format('d/m/Y') }}</td>
                        <td class="right">{{ $money($record->total_ttc) }}</td>
                        <td class="right">{{ $money($record->paid_total) }}</td>
                        <td class="right">{{ $money($record->balance_due) }}</td>
                        <td class="status">{{ $statusLabels[$record->status] ?? $record->status }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="7">{{ __('main.report_no_results') }}</td></tr>
                @endforelse
            </tbody>
        @elseif ($section === 'treasury')
            <thead><tr>
                <th>{{ __('main.date') }}</th>
                <th>{{ __('main.treasury_source') }}</th>
                <th>{{ __('main.type') }}</th>
                <th>{{ __('main.payment_method') }}</th>
                <th>{{ __('main.direction') }}</th>
                <th class="right">{{ __('main.amount') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ optional($record->movement_date)->format('d/m/Y') }}</td>
                        <td>{{ $record->source_reference ?: $record->label }}</td>
                        <td>{{ $movementTypeLabels[$record->movement_type] ?? $record->movement_type }}</td>
                        <td>{{ $record->paymentMethod?->name ?: '-' }}</td>
                        <td>{{ $movementDirectionLabels[$record->direction] ?? $record->direction }}</td>
                        <td class="right">{{ $money($record->amount) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="6">{{ __('main.report_no_results') }}</td></tr>
                @endforelse
            </tbody>
        @else
            <thead><tr>
                <th>{{ __('main.reference') }}</th>
                <th>{{ __('main.items') }}</th>
                <th>{{ __('main.categories') }}</th>
                <th>{{ __('main.subcategories') }}</th>
                <th class="right">{{ __('main.current_stock') }}</th>
                <th class="right">{{ __('main.min_stock') }}</th>
                <th class="right">{{ __('main.report_inventory_value') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->reference }}</td>
                        <td>{{ $record->name }}</td>
                        <td>{{ $record->category?->name ?: '-' }}</td>
                        <td>{{ $record->subcategory?->name ?: '-' }}</td>
                        <td class="right">{{ $number($record->current_stock) }}</td>
                        <td class="right">{{ $number($record->min_stock) }}</td>
                        <td class="right">{{ $money($record->current_stock * $record->purchase_price) }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="7">{{ __('main.report_no_results') }}</td></tr>
                @endforelse
            </tbody>
        @endif
    </table>

    <div class="footer">
        <table class="rule"><tr><td></td><td></td></tr></table>
        <strong>{{ $company->name }}</strong><br>
        {{ $site->name }}@if ($company->email) | {{ $company->email }}@endif<br>
        @if ($pdfShowFooterBranding)
            <em>{{ __('main.generated_by_exad_erp', ['app' => app_brand_name()]) }}</em> -
        @endif
        {{ __('main.report_generated_on', ['date' => now()->format('d/m/Y H:i')]) }}
    </div>
</body>
</html>

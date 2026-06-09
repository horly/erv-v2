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
    <title>{{ __('main.ged_reports') }}</title>
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
        .metric-table small { display: block; margin-top: 4px; color: #60718a; }
        .lines { margin-top: 10px; }
        .lines th { padding: 8px 8px; color: #fff; background: #2f70c8; text-align: left; font-size: 9px; text-transform: uppercase; }
        .lines td { padding: 8px 8px; border-bottom: 1px solid #d9e3f1; vertical-align: top; }
        .lines tr:nth-child(even) td { background: #d7eef8; }
        .right { text-align: right !important; white-space: nowrap; }
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
    <table class="header">
        <tr>
            <td class="brand-side">
                <div class="brand-name">{{ $company->name }}</div>
                <div class="brand-site">{{ $site->name }}</div>
            </td>
            <td class="document-side">
                <h1 class="document-title">{{ __('main.ged_reports') }}</h1>
                <div class="document-section">{{ __('main.module_document_management') }}</div>
            </td>
        </tr>
    </table>
    <table class="rule"><tr><td></td><td></td></tr></table>

    <p class="period">
        {{ __('main.ged_report_period') }} <strong>{{ $periodLabel }}</strong>
        | {{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}
        | {{ __('main.hr_report_generated_by') }} <strong>{{ $user->name }}</strong>
    </p>

    <table class="metric-table">
        <tr>
            @foreach ($metrics as $metric)
                <td>
                    <span>{{ $metric['label'] }}</span>
                    <strong>{{ number_format((float) $metric['value'], 0, ',', ' ') }}</strong>
                    <small>{{ $metric['meta'] }}</small>
                </td>
            @endforeach
        </tr>
    </table>

    <h2>{{ __('main.ged_report_type_breakdown') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.ged_document_type') }}</th>
                <th class="right">{{ __('main.ged_documents_count') }}</th>
                <th class="right">{{ __('main.ged_report_open') }}</th>
                <th class="right">{{ __('main.ged_report_validated') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($typeRows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="right">{{ $row['count'] }}</td>
                    <td class="right">{{ $row['open'] }}</td>
                    <td class="right">{{ $row['validated'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="4">{{ __('main.report_no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('main.ged_report_status_breakdown') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.status') }}</th>
                <th class="right">{{ __('main.ged_documents_count') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statusRows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="right">{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="2">{{ __('main.report_no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('main.ged_report_folder_breakdown') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.ged_folder') }}</th>
                <th>{{ __('main.ged_category') }}</th>
                <th class="right">{{ __('main.ged_documents_count') }}</th>
                <th class="right">{{ __('main.ged_report_urgent_documents') }}</th>
                <th>{{ __('main.ged_last_activity') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($folderRows->take(8) as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['category'] ?: '-' }}</td>
                    <td class="right">{{ $row['count'] }}</td>
                    <td class="right">{{ $row['urgent'] }}</td>
                    <td>{{ $row['last_activity'] ? \Illuminate\Support\Carbon::parse($row['last_activity'])->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">{{ __('main.report_no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('main.ged_report_validation_breakdown') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.status') }}</th>
                <th class="right">{{ __('main.ged_validation_requests_title') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($validationRows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="right">{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="2">{{ __('main.report_no_results') }}</td></tr>
            @endforelse
        </tbody>
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

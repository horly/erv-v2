@php
    $pdfSettings = $site->accountingModuleSetting;
    $pdfPrimaryColor = $pdfSettings?->pdf_primary_color ?: '#2F70C8';
    $pdfAccentColor = $pdfSettings?->pdf_accent_color ?: '#40AEF4';
    $pdfTintColor = $pdfSettings?->pdf_tint_color ?: '#D7EEF8';
    $pdfShowFooterBranding = $pdfSettings?->pdf_show_footer_branding ?? true;
    $amount = fn ($value, $currency) => number_format((float) $value, 2, ',', ' ').' '.$currency;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.hr_reports') }}</title>
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
                <h1 class="document-title">{{ __('main.hr_reports') }}</h1>
                <div class="document-section">{{ __('main.module_human_resources') }}</div>
            </td>
        </tr>
    </table>
    <table class="rule"><tr><td></td><td></td></tr></table>

    <p class="period">
        {{ __('main.hr_report_period') }} <strong>{{ $periodLabel }}</strong>
        | {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        | {{ __('main.hr_report_generated_by') }} <strong>{{ $user->name }}</strong>
    </p>

    <table class="metric-table">
        <tr>
            @foreach ($metrics as $metric)
                <td>
                    <span>{{ $metric['label'] }}</span>
                    <strong>{{ is_numeric($metric['value']) ? number_format($metric['value'], 0, ',', ' ') : $metric['value'] }}</strong>
                    <small>{{ $metric['meta'] }}</small>
                </td>
            @endforeach
        </tr>
    </table>

    <h2>{{ __('main.hr_report_attendance_breakdown') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.total') }}</th>
                <th>{{ __('main.hr_attendance_status_present') }}</th>
                <th>{{ __('main.hr_attendance_status_late') }}</th>
                <th>{{ __('main.hr_attendance_status_absent') }}</th>
                <th>{{ __('main.hr_attendance_status_remote') }}</th>
                <th>{{ __('main.hr_attendance_status_on_leave') }}</th>
                <th class="right">{{ __('main.hr_worked_hours') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $attendanceReport['total'] }}</td>
                <td>{{ $attendanceReport['present'] }}</td>
                <td>{{ $attendanceReport['late'] }}</td>
                <td>{{ $attendanceReport['absent'] }}</td>
                <td>{{ $attendanceReport['remote'] }}</td>
                <td>{{ $attendanceReport['on_leave'] }}</td>
                <td class="right">{{ number_format((float) $attendanceReport['worked_hours'], 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('main.hr_report_departments') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.hr_departments') }}</th>
                <th class="right">{{ __('main.hr_employees') }}</th>
                <th class="right">{{ __('main.hr_active_employees') }}</th>
                <th class="right">{{ __('main.hr_contracts') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departmentRows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td class="right">{{ $row['employees'] }}</td>
                    <td class="right">{{ $row['active_employees'] }}</td>
                    <td class="right">{{ $row['active_contracts'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="4">{{ __('main.report_no_results') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('main.hr_report_payroll') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.reference') }}</th>
                <th>{{ __('main.hr_employee') }}</th>
                <th>{{ __('main.period') }}</th>
                <th class="right">{{ __('main.hr_gross_salary') }}</th>
                <th class="right">{{ __('main.hr_net_salary') }}</th>
                <th>{{ __('main.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payrollEntries as $entry)
                <tr>
                    <td>{{ $entry->reference }}</td>
                    <td>{{ $entry->employee?->full_name ?? '-' }}</td>
                    <td>{{ optional($entry->period_month)->translatedFormat('F Y') }}</td>
                    <td class="right">{{ $amount($entry->gross_salary, $entry->currency) }}</td>
                    <td class="right">{{ $amount($entry->net_salary, $entry->currency) }}</td>
                    <td>{{ $payrollStatuses[$entry->status] ?? $entry->status }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="6">{{ __('main.report_no_results') }}</td></tr>
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

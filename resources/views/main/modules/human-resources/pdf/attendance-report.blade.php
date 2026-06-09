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
    <title>{{ __('main.hr_attendance_report_title') }}</title>
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
        .metric-table td { width: 16.66%; padding: 11px 10px; border: 1px solid #d8e3f1; background: #f8fbff; vertical-align: top; }
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
    <table class="header">
        <tr>
            <td class="brand-side">
                <div class="brand-name">{{ $company->name }}</div>
                <div class="brand-site">{{ $site->name }}</div>
            </td>
            <td class="document-side">
                <h1 class="document-title">{{ __('main.hr_attendance_report_title') }}</h1>
                <div class="document-section">{{ __('main.hr_attendance') }}</div>
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
            <td><span>{{ __('main.total') }}</span><strong>{{ $attendanceReport['total'] }}</strong></td>
            <td><span>{{ __('main.hr_attendance_status_present') }}</span><strong>{{ $attendanceReport['present'] }}</strong></td>
            <td><span>{{ __('main.hr_attendance_status_late') }}</span><strong>{{ $attendanceReport['late'] }}</strong></td>
            <td><span>{{ __('main.hr_attendance_status_absent') }}</span><strong>{{ $attendanceReport['absent'] }}</strong></td>
            <td><span>{{ __('main.hr_attendance_status_on_leave') }}</span><strong>{{ $attendanceReport['on_leave'] }}</strong></td>
            <td><span>{{ __('main.hr_worked_hours') }}</span><strong>{{ number_format((float) $attendanceReport['worked_hours'], 2, ',', ' ') }}</strong></td>
        </tr>
    </table>

    <h2>{{ __('main.report_details') }}</h2>
    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('main.date') }}</th>
                <th>{{ __('main.number') }}</th>
                <th>{{ __('main.hr_employee') }}</th>
                <th>{{ __('main.hr_departments') }}</th>
                <th>{{ __('main.status') }}</th>
                <th>{{ __('main.hr_check_in') }}</th>
                <th>{{ __('main.hr_check_out') }}</th>
                <th class="right">{{ __('main.hr_worked_hours') }}</th>
                <th>{{ __('main.notes') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ optional($attendance->work_date)->format('d/m/Y') }}</td>
                    <td>{{ $attendance->employee?->employee_number ?? '-' }}</td>
                    <td>{{ $attendance->employee?->full_name ?? '-' }}</td>
                    <td>{{ $attendance->employee?->department?->name ?? '-' }}</td>
                    <td class="status">{{ $attendanceStatuses[$attendance->status] ?? $attendance->status }}</td>
                    <td>{{ $attendance->check_in_at ? substr($attendance->check_in_at, 0, 5) : '-' }}</td>
                    <td>{{ $attendance->check_out_at ? substr($attendance->check_out_at, 0, 5) : '-' }}</td>
                    <td class="right">{{ number_format((float) $attendance->worked_hours, 2, ',', ' ') }}</td>
                    <td>{{ $attendance->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="9">{{ __('main.report_no_results') }}</td></tr>
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

@php
    $pdfPrimaryColor = $pdfSettings?->pdf_primary_color ?: '#2F70C8';
    $pdfAccentColor = $pdfSettings?->pdf_accent_color ?: '#40AEF4';
    $pdfTintColor = $pdfSettings?->pdf_tint_color ?: '#D7EEF8';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('main.archive_reports') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; }
        .header { border-bottom: 4px solid {{ $pdfPrimaryColor }}; padding-bottom: 12px; margin-bottom: 18px; }
        h1 { margin: 0; color: {{ $pdfPrimaryColor }}; font-size: 22px; }
        .meta { color: #64748b; margin-top: 4px; }
        .kpis { width: 100%; margin-bottom: 16px; }
        .kpis td { width: 25%; padding: 10px; background: {{ $pdfTintColor }}; border-right: 4px solid #fff; }
        .kpis strong { display: block; font-size: 18px; color: {{ $pdfPrimaryColor }}; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: {{ $pdfPrimaryColor }}; color: #fff; text-align: left; padding: 8px; }
        td { border-bottom: 1px solid #dbe5f1; padding: 8px; }
        h2 { color: {{ $pdfAccentColor }}; font-size: 15px; margin: 14px 0 8px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #dbe5f1; padding-top: 8px; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('main.archive_reports') }}</h1>
        <div class="meta">{{ $company->name }} / {{ $site->name }} · {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <table class="kpis"><tr>@foreach ($metrics as $metric)<td>{{ $metric['label'] }}<strong>{{ $metric['value'] }}</strong><span>{{ $metric['meta'] }}</span></td>@endforeach</tr></table>
    <h2>{{ __('main.archive_report_by_type') }}</h2>
    <table><thead><tr><th>{{ __('main.type') }}</th><th>{{ __('main.documents') }}</th></tr></thead><tbody>@foreach ($typeRows as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td></tr>@endforeach</tbody></table>
    <h2>{{ __('main.archive_report_by_location') }}</h2>
    <table><thead><tr><th>{{ __('main.location') }}</th><th>{{ __('main.type') }}</th><th>{{ __('main.archive_containers') }}</th><th>{{ __('main.documents') }}</th></tr></thead><tbody>@foreach ($locationRows as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['type'] }}</td><td>{{ $row['containers'] }}</td><td>{{ $row['records'] }}</td></tr>@endforeach</tbody></table>
    <div class="footer">{{ app_brand_name() }} · {{ __('main.module_archiving') }}</div>
</body>
</html>

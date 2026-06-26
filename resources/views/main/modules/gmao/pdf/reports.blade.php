@php
    $pdfPrimaryColor = $pdfSettings?->pdf_primary_color ?? '#2F70C8';
    $pdfAccentColor = $pdfSettings?->pdf_accent_color ?? '#40AEF4';
    $pdfTintColor = $pdfSettings?->pdf_tint_color ?? '#D7EEF8';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('main.gmao_reports') }}</title><style>
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
</style></head>
<body>
<div class="header"><h1>{{ __('main.gmao_reports') }}</h1><div class="meta">{{ $company->name }} / {{ $site->name }} · {{ now()->format('d/m/Y H:i') }}</div></div>
<table class="kpis"><tr>@foreach ($metrics as $metric)<td>{{ $metric['label'] }}<strong>{{ $metric['value'] }}</strong><span>{{ $metric['meta'] }}</span></td>@endforeach</tr></table>
<h2>{{ __('main.gmao_capex_opex') }}</h2><table><thead><tr><th>{{ __('main.gmao_maintenance_cost') }}</th><th>CAPEX</th><th>OPEX</th><th>{{ __('main.currency') }}</th></tr></thead><tbody><tr><td>{{ number_format((float) $financialSummary['maintenance_cost'], 2, ',', ' ') }}</td><td>{{ number_format((float) $financialSummary['capex'], 2, ',', ' ') }}</td><td>{{ number_format((float) $financialSummary['opex'], 2, ',', ' ') }}</td><td>{{ $financialSummary['currency'] }}</td></tr></tbody></table>
<h2>{{ __('main.gmao_maintenance_routes') }}</h2><table><thead><tr><th>{{ __('main.reference') }}</th><th>{{ __('main.title') }}</th><th>{{ __('main.frequency') }}</th><th>{{ __('main.gmao_tasks') }}</th></tr></thead><tbody>@forelse ($routeRows as $route)<tr><td>{{ $route->reference }}</td><td>{{ $route->title }}</td><td>{{ $frequencyLabels[$route->frequency] ?? $route->frequency }}</td><td>{{ $route->tasks_count }}</td></tr>@empty<tr><td colspan="4">{{ __('main.gmao_no_maintenance_routes') }}</td></tr>@endforelse</tbody></table>
<h2>{{ __('main.gmao_equipment_by_status') }}</h2><table><thead><tr><th>{{ __('main.status') }}</th><th>{{ __('main.equipment') }}</th><th>{{ __('main.gmao_critical_assets') }}</th></tr></thead><tbody>@foreach ($equipmentRows as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['critical'] }}</td></tr>@endforeach</tbody></table>
<h2>{{ __('main.gmao_orders_by_status') }}</h2><table><thead><tr><th>{{ __('main.status') }}</th><th>{{ __('main.gmao_work_orders') }}</th><th>{{ __('main.hours') }}</th></tr></thead><tbody>@foreach ($orderRows as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['hours'] }}</td></tr>@endforeach</tbody></table>
<div class="footer">{{ app_brand_name() }} · {{ __('main.module_gmao') }}</div>
</body>
</html>

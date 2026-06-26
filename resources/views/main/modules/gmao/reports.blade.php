<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_reports') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar', ['activeGmaoPage' => 'reports'])
        <main class="dashboard-main">
            <header class="dashboard-topbar"><div><h1>{{ __('main.gmao_reports') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
            <section class="dashboard-content module-dashboard-page gmao-page archive-reports-page">
                <a class="back-link" href="{{ route('main.gmao.dashboard', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.gmao_dashboard') }}</a>
                <section class="page-heading"><div><h1>{{ __('main.gmao_reports') }}</h1><p>{{ __('main.gmao_reports_subtitle') }}</p></div><a class="primary-action" href="{{ route('main.gmao.reports.pdf', [$company, $site]) }}"><i class="bi bi-file-earmark-pdf"></i>{{ __('main.export_pdf') }}</a></section>
                <section class="report-metric-grid">@foreach ($metrics as $metric)<article class="archive-report-metric kpi-{{ $metric['tone'] }}"><span><i class="bi {{ $metric['icon'] }}"></i></span><div><strong>{{ $metric['value'] }}</strong><small>{{ $metric['label'] }}</small><em>{{ $metric['meta'] }}</em></div></article>@endforeach</section>
                <section class="gmao-report-grid">
                    <article class="company-card archive-report-card"><div class="hr-panel-header"><div><span>{{ __('main.gmao_finance') }}</span><h3>{{ __('main.gmao_capex_opex') }}</h3></div><strong>{{ $financialSummary['currency'] }}</strong></div><div class="archive-report-list"><div><span>{{ __('main.gmao_maintenance_cost') }}</span><strong>{{ number_format((float) $financialSummary['maintenance_cost'], 2, ',', ' ') }}</strong></div><div><span>CAPEX</span><strong>{{ number_format((float) $financialSummary['capex'], 2, ',', ' ') }}</strong></div><div><span>OPEX</span><strong>{{ number_format((float) $financialSummary['opex'], 2, ',', ' ') }}</strong></div></div></article>
                    <article class="company-card archive-report-card"><div class="hr-panel-header"><div><span>{{ __('main.gmao_preventive') }}</span><h3>{{ __('main.gmao_maintenance_routes') }}</h3></div><strong>{{ $routeRows->count() }} {{ __('main.rows') }}</strong></div><div class="hr-activity-list">@foreach ($routeRows as $route)<div class="hr-activity-row"><span><i class="bi bi-list-check"></i></span><div><strong>{{ $route->title }}</strong><small>{{ $route->reference }} · {{ $route->tasks_count }} {{ __('main.gmao_tasks') }}</small></div></div>@endforeach</div></article>
                    <article class="company-card archive-report-card"><div class="hr-panel-header"><div><span>{{ __('main.gmao_assets') }}</span><h3>{{ __('main.gmao_equipment_by_status') }}</h3></div><strong>{{ $equipmentRows->sum('count') }} {{ __('main.rows') }}</strong></div><div class="archive-report-list">@foreach ($equipmentRows as $row)<div><span class="status-pill gmao-status-{{ $row['status'] }}">{{ $row['label'] }}</span><strong>{{ $row['count'] }}</strong><small>{{ __('main.gmao_critical_value', ['value' => $row['critical']]) }}</small></div>@endforeach</div></article>
                    <article class="company-card archive-report-card"><div class="hr-panel-header"><div><span>{{ __('main.gmao_operations') }}</span><h3>{{ __('main.gmao_orders_by_status') }}</h3></div><strong>{{ $orderRows->sum('count') }} {{ __('main.rows') }}</strong></div><div class="archive-report-list">@foreach ($orderRows as $row)<div><span class="status-pill gmao-status-{{ $row['status'] }}">{{ $row['label'] }}</span><strong>{{ $row['count'] }}</strong><small>{{ $row['hours'] }} h</small></div>@endforeach</div></article>
                    <article class="company-card archive-report-card"><div class="hr-panel-header"><div><span>{{ __('main.gmao_requests') }}</span><h3>{{ __('main.gmao_recent_requests') }}</h3></div><strong>{{ $requestRows->count() }} {{ __('main.rows') }}</strong></div><div class="hr-activity-list">@foreach ($requestRows as $request)<div class="hr-activity-row"><span><i class="bi bi-exclamation-diamond"></i></span><div><strong>{{ $request->title }}</strong><small>{{ $request->reference }} · {{ $request->equipment?->name ?? '-' }}</small></div></div>@endforeach</div></article>
                    <article class="company-card archive-report-card"><div class="hr-panel-header"><div><span>{{ __('main.gmao_inventory') }}</span><h3>{{ __('main.gmao_stock_watch') }}</h3></div><strong>{{ $stockRows->count() }} {{ __('main.rows') }}</strong></div><div class="hr-activity-list">@foreach ($stockRows as $part)<div class="hr-activity-row"><span><i class="bi bi-nut"></i></span><div><strong>{{ $part->name }}</strong><small>{{ $part->reference }} · {{ number_format((float) $part->stock_quantity, 2, ',', ' ') }} {{ $part->unit }}</small></div></div>@endforeach</div></article>
                </section>
            </section>
        </main>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

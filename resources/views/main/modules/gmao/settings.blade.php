<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_settings') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar', ['activeGmaoPage' => 'settings'])
        <main class="dashboard-main">
            <header class="dashboard-topbar"><div><h1>{{ __('main.gmao_settings') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
            <section class="dashboard-content module-dashboard-page gmao-page">
                <a class="back-link" href="{{ route('main.gmao.dashboard', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.gmao_dashboard') }}</a>
                <section class="page-heading"><div><h1>{{ __('main.gmao_settings') }}</h1><p>{{ __('main.gmao_settings_subtitle') }}</p></div></section>
                <section class="settings-grid">
                    <article class="company-card settings-panel">
                        <div class="hr-panel-header"><div><span>{{ __('main.gmao_governance') }}</span><h3>{{ __('main.gmao_access_scope') }}</h3></div><i class="bi bi-shield-check"></i></div>
                        <p>{{ __('main.gmao_settings_access_text') }}</p>
                        <div class="gmao-settings-tags">@foreach ($menuKeys as $key)<span>{{ $key }}</span>@endforeach</div>
                    </article>
                    <article class="company-card settings-panel">
                        <div class="hr-panel-header"><div><span>{{ __('main.gmao_reports') }}</span><h3>{{ __('main.gmao_report_identity') }}</h3></div><i class="bi bi-file-earmark-pdf"></i></div>
                        <p>{{ __('main.gmao_settings_report_text') }}</p>
                    </article>
                </section>
            </section>
        </main>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

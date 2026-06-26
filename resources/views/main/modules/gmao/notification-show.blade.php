<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.notification_details') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar', ['activeGmaoPage' => 'traceability'])
        <main class="dashboard-main">
            <header class="dashboard-topbar"><div><h1>{{ __('main.notification_details') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
            <section class="dashboard-content module-dashboard-page gmao-page">
                <a class="back-link" href="{{ route('main.gmao.notifications', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.notifications') }}</a>
                <article class="company-card gmao-notification-detail">
                    <span class="module-heading-icon module-gmao"><i class="bi bi-tools"></i></span>
                    <div>
                        <h2>{{ $actionLabels[$notification->action] ?? $notification->title }}</h2>
                        <p>{{ $notification->description }}</p>
                    </div>
                </article>
                <article class="company-card gmao-detail-grid">
                    <div><span>{{ __('main.user') }}</span><strong>{{ $notification->actor?->name ?? __('main.system') }}</strong></div>
                    <div><span>{{ __('main.reference') }}</span><strong>{{ $notification->reference ?? '-' }}</strong></div>
                    <div><span>{{ __('main.module') }}</span><strong>{{ __('main.module_gmao_short') }}</strong></div>
                    <div><span>{{ __('main.date') }}</span><strong>{{ $notification->created_at?->format('d/m/Y H:i') }}</strong></div>
                </article>
                <div class="page-actions-right"><a class="primary-action" href="{{ route('main.gmao.dashboard', [$company, $site]) }}"><i class="bi bi-box-arrow-up-right"></i>{{ __('main.open_related_module') }}</a></div>
            </section>
        </main>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

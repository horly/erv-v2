<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.notifications') }} | {{ app_brand_name() }}</title>
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
            <header class="dashboard-topbar"><div><h1>{{ __('main.notifications') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
            <section class="dashboard-content module-dashboard-page accounting-list-page gmao-page">
                <a class="back-link" href="{{ route('main.gmao.dashboard', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.gmao_dashboard') }}</a>
                <section class="page-heading"><div><h1>{{ __('main.notifications') }}</h1><p>{{ __('main.gmao_notifications_subtitle') }}</p></div></section>
                <section class="company-card notification-page-list">
                    @forelse ($notifications as $notification)
                        <a class="notification-row" href="{{ route('main.gmao.notifications.show', [$company, $site, $notification]) }}">
                            <span><i class="bi bi-tools" aria-hidden="true"></i></span>
                            <div><strong>{{ $actionLabels[$notification->action] ?? $notification->title }}</strong><small>{{ $notification->reference }} · {{ $notification->actor?->name ?? __('main.system') }} · {{ $notification->created_at?->diffForHumans() }}</small></div>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    @empty
                        <div class="module-empty-state"><i class="bi bi-bell"></i><strong>{{ __('main.no_notifications') }}</strong></div>
                    @endforelse
                </section>
                @if ($notifications->hasPages())<section class="subscriptions-pagination">{{ $notifications->links() }}</section>@endif
            </section>
        </main>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

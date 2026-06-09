<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.notification_details') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
    <div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
        @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'notifications'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.notification_details') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-notifications-page archiving-page">
                <a class="back-link" href="{{ route('main.archiving.notifications', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.notifications') }}
                </a>

                <article class="notification-detail-card">
                    <span class="notification-item-icon"><i class="bi {{ $notification->icon }}" aria-hidden="true"></i></span>
                    <div>
                        <h1>{{ $notification->title }}</h1>
                        <p>{{ $notification->message }}</p>
                    </div>
                </article>

                <section class="settings-panel notification-detail-grid">
                    <div><span>{{ __('main.actor') }}</span><strong>{{ $notification->actor?->name ?: __('main.system_user') }}</strong></div>
                    <div><span>{{ __('main.reference') }}</span><strong>{{ $notification->subject_reference ?: '-' }}</strong></div>
                    <div><span>{{ __('main.module') }}</span><strong>{{ $moduleLabel ?? $notification->module_key }}</strong></div>
                    <div><span>{{ __('main.date') }}</span><strong>{{ $notification->occurred_at?->translatedFormat('d/m/Y H:i') }}</strong></div>
                </section>

                @if ($moduleUrl)
                    <div class="settings-actions">
                        <a class="primary-action" href="{{ $moduleUrl }}"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>{{ __('main.open_related_module') }}</a>
                    </div>
                @endif
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

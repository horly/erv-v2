<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.notifications') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'notifications'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.notifications')])

            <section class="dashboard-content module-dashboard-page accounting-notifications-page">
                <a class="back-link" href="{{ route('main.companies.sites.modules.show', [$company, $site, $module]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.notifications') }}</h1>
                        <p>{{ __('main.notifications_subtitle') }}</p>
                    </div>
                </section>

                <div class="report-section-tabs notification-tabs">
                    <a class="{{ $status !== 'unread' ? 'active' : '' }}" href="{{ route('main.accounting.notifications', [$company, $site]) }}">{{ __('main.all_notifications') }}</a>
                    <a class="{{ $status === 'unread' ? 'active' : '' }}" href="{{ route('main.accounting.notifications', [$company, $site, 'status' => 'unread']) }}">{{ __('main.unread_notifications') }}</a>
                </div>

                <section class="notifications-table-card">
                    @forelse ($notifications as $notification)
                        @php($isRead = $notification->isReadBy($user))
                        <a class="notification-row {{ $isRead ? '' : 'unread' }}" href="{{ route('main.accounting.notifications.show', [$company, $site, $notification]) }}">
                            <span class="notification-item-icon"><i class="bi {{ $notification->icon }}" aria-hidden="true"></i></span>
                            <span>
                                <strong>{{ $notification->actor?->name ?: __('main.system_user') }}</strong>
                                {{ $notification->title }}
                                @if ($notification->subject_reference)
                                    <em>{{ $notification->subject_reference }}</em>
                                @endif
                                <small>{{ $notification->message }}</small>
                            </span>
                            <time>{{ $notification->occurred_at?->diffForHumans() }}</time>
                        </a>
                    @empty
                        <p class="settings-empty">{{ __('main.no_notifications') }}</p>
                    @endforelse
                </section>

                @if ($notifications->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $notifications->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $notifications->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $notifications->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($notifications->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $notifications->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($notifications->getUrlRange(1, $notifications->lastPage()) as $page => $url)
                                @if ($page === $notifications->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($notifications->hasMorePages())<a href="{{ $notifications->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

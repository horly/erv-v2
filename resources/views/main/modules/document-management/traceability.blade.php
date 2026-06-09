<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_traceability') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalActivities = $activities->total();
        $fallbackActionLabel = fn ($action) => str($action)->replace('_', ' ')->title();
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'history'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_traceability') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page document-management-page ged-traceability-page">
                <a class="back-link" href="{{ route('main.document-management.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.ged_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.ged_traceability') }}</h1>
                        <p>{{ __('main.ged_traceability_subtitle') }}</p>
                    </div>
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <form class="search-box" method="GET" action="{{ route('main.document-management.traceability', [$company, $site]) }}">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.ged_trace_search_placeholder') }}" autocomplete="off">
                    </form>
                    <span class="row-count">
                        <strong>{{ $activities->count() }}</strong>
                        /
                        <strong>{{ $totalActivities }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table ged-trace-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.ged_document') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.ged_trace_action') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.ged_trace_actor') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.ged_trace_change') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activities as $activity)
                                    <tr>
                                        <td class="ged-trace-date" data-sort-value="{{ $activity->created_at?->format('Y-m-d H:i:s') }}">
                                            <strong>{{ $activity->created_at?->format('d/m/Y') }}</strong>
                                            <span>{{ $activity->created_at?->format('H:i') }}</span>
                                        </td>
                                        <td class="ged-mail-cell">
                                            <strong>{{ $activity->record?->reference ?? '-' }}</strong>
                                            <span>{{ $activity->record?->subject ?? '-' }}</span>
                                            <small>{{ $typeLabels[$activity->record?->record_type] ?? '-' }} &middot; {{ $activity->record?->folder?->name ?? __('main.ged_without_folder') }}</small>
                                        </td>
                                        <td class="ged-trace-action-cell">
                                            <span class="ged-trace-action-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
                                            <div>
                                                <strong>{{ $actionLabels[$activity->action] ?? $fallbackActionLabel($activity->action) }}</strong>
                                                @if ($activity->comment)
                                                    <small>{{ $activity->comment }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="ged-validator-cell">
                                            <strong>{{ $activity->actor?->name ?? __('main.system') }}</strong>
                                            <span>{{ $activity->actor?->email ?? '-' }}</span>
                                        </td>
                                        <td class="ged-trace-status-cell">
                                            @if ($activity->from_status || $activity->to_status)
                                                <span>{{ __('main.ged_trace_from') }} : <strong>{{ $statusLabels[$activity->from_status] ?? ($activity->from_status ?: '-') }}</strong></span>
                                                <span>{{ __('main.ged_trace_to') }} : <strong>{{ $statusLabels[$activity->to_status] ?? ($activity->to_status ?: '-') }}</strong></span>
                                            @else
                                                <span>{{ __('main.ged_trace_no_status_change') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.ged_trace_no_activity') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($activities->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $activities->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $activities->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalActivities }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($activities->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $activities->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($activities->getUrlRange(1, $activities->lastPage()) as $page => $url)
                                @if ($page === $activities->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($activities->hasMorePages())<a href="{{ $activities->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
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

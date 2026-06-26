<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_traceability') }} | {{ app_brand_name() }}</title>
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
            <header class="dashboard-topbar"><div><h1>{{ __('main.gmao_traceability') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
            <section class="dashboard-content module-dashboard-page accounting-list-page gmao-page">
                <a class="back-link" href="{{ route('main.gmao.dashboard', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.gmao_dashboard') }}</a>
                <section class="page-heading"><div><h1>{{ __('main.gmao_traceability') }}</h1><p>{{ __('main.gmao_traceability_subtitle') }}</p></div></section>
                <section class="table-tools"><form class="search-box" method="GET"><i class="bi bi-search"></i><input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.gmao_search_traceability') }}" autocomplete="off"></form><span class="row-count"><strong>{{ $activities->count() }}</strong> / <strong>{{ $activities->total() }}</strong> {{ __('admin.rows') }}</span></section>
                <section class="company-card"><div class="table-responsive"><table class="company-table" id="companyTable">
                    <thead><tr><th><button class="table-sort" data-sort-index="0" type="button">{{ __('main.date') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" data-sort-index="1" type="button">{{ __('main.reference') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" data-sort-index="2" type="button">{{ __('main.action') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" data-sort-index="3" type="button">{{ __('main.user') }} <i class="bi bi-arrow-down-up"></i></button></th></tr></thead>
                    <tbody>
                    @forelse ($activities as $activity)
                        <tr><td><strong>{{ $activity->created_at?->format('d/m/Y') }}</strong><small class="d-block text-muted">{{ $activity->created_at?->format('H:i') }}</small></td><td>{{ $activity->reference ?? '-' }}</td><td><strong>{{ $actionLabels[$activity->action] ?? $activity->title }}</strong><small class="d-block text-muted">{{ $activity->description }}</small></td><td>{{ $activity->actor?->name ?? __('main.system') }}</td></tr>
                    @empty
                        <tr class="empty-row"><td colspan="4">{{ __('main.gmao_no_records') }}</td></tr>
                    @endforelse
                    <tr class="empty-row search-empty-row" hidden><td colspan="4">{{ __('admin.no_results') }}</td></tr>
                    </tbody>
                </table></div></section>
                @if ($activities->hasPages())<section class="subscriptions-pagination"><span>{{ __('admin.showing') }} <strong>{{ $activities->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $activities->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $activities->total() }}</strong></span><nav class="pagination-shell">@if ($activities->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $activities->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif @foreach ($activities->getUrlRange(1, $activities->lastPage()) as $page => $url) @if ($page === $activities->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif @endforeach @if ($activities->hasMorePages())<a href="{{ $activities->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif</nav></section>@endif
            </section>
        </main>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

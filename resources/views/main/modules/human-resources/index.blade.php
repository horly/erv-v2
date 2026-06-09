<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => $activeHumanResourcesPage])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page human-resources-page">
                <a class="back-link" href="{{ route('main.human-resources.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.hr_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ $pageTitle }}</h1>
                        <p>{{ $pageSubtitle }}</p>
                    </div>
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $rows->count() }}</strong>
                        /
                        <strong>{{ method_exists($rows, 'total') ? $rows->total() : $rows->count() }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-data-table" id="companyTable">
                            <thead>
                                <tr>
                                    @foreach ($columns as $columnIndex => $column)
                                        <th>
                                            <button class="table-sort" type="button" data-sort-index="{{ $columnIndex }}" data-sort-type="{{ $column['sort'] ?? 'text' }}">
                                                {{ $column['label'] }}
                                                <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
                                            </button>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        @foreach ($columns as $column)
                                            <td>{{ $row[$column['key']] ?? '-' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="{{ count($columns) }}">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="{{ count($columns) }}">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if (method_exists($rows, 'hasPages') && $rows->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $rows->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $rows->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $rows->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($rows->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $rows->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($rows->getUrlRange(1, $rows->lastPage()) as $page => $url)
                                @if ($page === $rows->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($rows->hasMorePages())<a href="{{ $rows->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
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

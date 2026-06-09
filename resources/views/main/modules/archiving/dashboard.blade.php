<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_dashboard') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
    @php
        $dashboard = array_merge([
            'metrics' => [],
            'recentRecords' => collect(),
            'locations' => collect(),
            'containers' => collect(),
            'expiringRecords' => collect(),
            'priorityLocations' => collect(),
            'recentActivities' => collect(),
            'recordStatusRows' => collect(),
            'locationTypeRows' => collect(),
            'capacity' => ['total' => 0, 'occupied' => 0, 'percent' => 0],
            'risks' => ['fullLocations' => 0, 'sealedContainers' => 0, 'expiredRecords' => 0],
            'locationTypeLabels' => [],
            'locationStatusLabels' => [],
            'recordStatusLabels' => [],
        ], $dashboard ?? []);
    @endphp
    <div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
        @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'dashboard'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.archive_dashboard') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page archiving-page">
                <a class="back-link" href="{{ route('main.companies.sites.show', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ $site->name }}
                </a>

                <section class="module-heading">
                    <span class="module-heading-icon {{ $moduleMeta['class'] }}">
                        <i class="bi {{ $moduleMeta['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('main.archive_dashboard') }}</h2>
                        <p>{{ __('main.archive_dashboard_subtitle') }}</p>
                    </div>
                </section>

                <section class="dashboard-content module-dashboard-content archiving-dashboard-content">
                    <section class="kpi-grid module-kpi-grid" aria-label="{{ __('admin.indicators') }}">
                        @foreach ($dashboard['metrics'] as $metric)
                            <article class="kpi-card kpi-{{ $metric['tone'] }}">
                                <div class="kpi-icon">
                                    <i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <strong>{{ $metric['value'] }}</strong>
                                <span>{{ $metric['label'] }}</span>
                                <small>{{ $metric['meta'] }}</small>
                            </article>
                        @endforeach
                    </section>

                    <section class="archive-command-grid">
                        <article class="archive-map-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.archive_physical_structure') }}</span>
                                    <h3>{{ __('main.archive_physical_plan') }}</h3>
                                </div>
                                <strong>{{ $dashboard['locations']->count() }} {{ __('main.rows') }}</strong>
                            </div>

                            <div class="archive-location-stack">
                                @forelse ($dashboard['priorityLocations'] as $location)
                                    <article class="archive-location-row">
                                        <span class="archive-location-icon archive-location-box">
                                            <i class="bi bi-archive" aria-hidden="true"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $location->name }}</strong>
                                            <small>{{ $location->physical_path }}</small>
                                        </div>
                                        <em>{{ $location->containers_count ?? 0 }} {{ __('main.archive_containers') }} · {{ $location->records_count ?? 0 }} {{ __('main.documents') }}</em>
                                    </article>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-building-add" aria-hidden="true"></i>
                                        <strong>{{ __('main.archive_no_locations') }}</strong>
                                        <span>{{ __('main.archive_physical_plan_empty') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="archive-control-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.archive_governance') }}</span>
                                    <h3>{{ __('main.archive_capacity_overview') }}</h3>
                                </div>
                                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                            </div>

                            <div class="archive-capacity-card">
                                <strong>{{ $dashboard['capacity']['percent'] }}%</strong>
                                <span>{{ __('main.archive_capacity_used') }}</span>
                                <div class="hr-progress" aria-label="{{ __('main.archive_capacity_used') }}">
                                    <span style="width: {{ $dashboard['capacity']['percent'] }}%"></span>
                                </div>
                                <small>{{ $dashboard['capacity']['occupied'] }} / {{ $dashboard['capacity']['total'] ?: '-' }} {{ __('main.archive_capacity_units') }}</small>
                            </div>

                            <div class="archive-risk-grid">
                                <div><strong>{{ $dashboard['risks']['fullLocations'] }}</strong><span>{{ __('main.archive_full_locations') }}</span></div>
                                <div><strong>{{ $dashboard['risks']['sealedContainers'] }}</strong><span>{{ __('main.archive_sealed_classers') }}</span></div>
                                <div><strong>{{ $dashboard['risks']['expiredRecords'] }}</strong><span>{{ __('main.archive_expired_records') }}</span></div>
                            </div>
                        </article>
                    </section>

                    <section class="archive-dashboard-grid">
                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.archive_recent_records') }}</span>
                                    <h3>{{ __('main.archive_records') }}</h3>
                                </div>
                                <strong>{{ $dashboard['recentRecords']->count() }} {{ __('main.rows') }}</strong>
                            </div>

                            <div class="archive-record-list">
                                @forelse ($dashboard['recentRecords'] as $record)
                                    <article class="archive-record-row">
                                        <span><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $record->title }}</strong>
                                            <small>{{ $record->reference }} &middot; {{ $record->container?->title ?? $record->location?->name ?? '-' }}</small>
                                        </div>
                                        <span class="status-pill archive-status-{{ $record->status }}">{{ $dashboard['recordStatusLabels'][$record->status] ?? $record->status }}</span>
                                    </article>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                        <strong>{{ __('main.archive_no_records') }}</strong>
                                        <span>{{ __('main.archive_records_empty_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.archive_retention') }}</span>
                                    <h3>{{ __('main.archive_expiring_soon') }}</h3>
                                </div>
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($dashboard['expiringRecords'] as $record)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $record->title }}</strong>
                                            <small>{{ $record->reference }} &middot; {{ $record->retention_until?->format('d/m/Y') ?? '-' }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                                        <strong>{{ __('main.archive_no_expiring_records') }}</strong>
                                        <span>{{ __('main.archive_retention_clear_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.archive_traceability') }}</span>
                                    <h3>{{ __('main.archive_recent_activity') }}</h3>
                                </div>
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($dashboard['recentActivities'] as $activity)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-activity" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $activity->actor?->name ?? __('main.system') }}</strong>
                                            <small>{{ __('main.archive_action_'.$activity->action) }} &middot; {{ $activity->created_at?->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                        <strong>{{ __('main.archive_no_activity') }}</strong>
                                        <span>{{ __('main.archive_no_activity_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    </section>
                </section>
            </section>
        </main>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

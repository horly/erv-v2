<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_dashboard') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $documentManagementDashboard = array_merge([
            'metrics' => [],
            'records' => collect(),
            'incoming' => collect(),
            'outgoing' => collect(),
            'internal' => collect(),
            'openRecords' => collect(),
            'urgentRecords' => collect(),
            'overdueRecords' => collect(),
            'folders' => collect(),
            'recentRecords' => collect(),
            'recentActivities' => collect(),
            'statusLabels' => [],
            'priorityLabels' => [],
            'typeLabels' => [],
        ], $documentManagementDashboard ?? []);
        $statusLabels = $documentManagementDashboard['statusLabels'];
        $priorityLabels = $documentManagementDashboard['priorityLabels'];
        $typeLabels = $documentManagementDashboard['typeLabels'];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'dashboard'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_dashboard') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page document-management-page">
                <a class="back-link" href="{{ route('main.companies.sites.show', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ $site->name }}
                </a>

                <section class="module-heading">
                    <span class="module-heading-icon {{ $moduleMeta['class'] }}">
                        <i class="bi {{ $moduleMeta['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('main.ged_dashboard') }}</h2>
                        <p>{{ __('main.ged_dashboard_subtitle') }}</p>
                    </div>
                </section>

                <section class="dashboard-content module-dashboard-content document-management-dashboard-content">
                    <section class="kpi-grid module-kpi-grid" aria-label="{{ __('admin.indicators') }}">
                        @foreach ($documentManagementDashboard['metrics'] as $kpi)
                            <article class="kpi-card kpi-{{ $kpi['tone'] }}">
                                <div class="kpi-icon">
                                    <i class="bi {{ $kpi['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <strong>{{ $kpi['value'] }}</strong>
                                <span>{{ $kpi['label'] }}</span>
                                <small>{{ $kpi['meta'] }}</small>
                            </article>
                        @endforeach
                    </section>

                    <section class="ged-hero-grid">
                        <article class="ged-command-panel">
                            <div class="ged-panel-header">
                                <div>
                                    <span>{{ __('main.ged_registry_office') }}</span>
                                    <h3>{{ __('main.ged_recent_records') }}</h3>
                                </div>
                                <strong>{{ $documentManagementDashboard['records']->count() }} {{ __('main.rows') }}</strong>
                            </div>

                            <div class="ged-record-list">
                                @forelse ($documentManagementDashboard['recentRecords'] as $record)
                                    <article class="ged-record-row">
                                        <span class="ged-record-icon ged-record-{{ $record->record_type }}">
                                            <i class="bi {{ $record->record_type === 'incoming' ? 'bi-inbox' : ($record->record_type === 'outgoing' ? 'bi-send' : 'bi-file-earmark-text') }}" aria-hidden="true"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $record->subject }}</strong>
                                            <small>{{ $record->reference }} &middot; {{ $typeLabels[$record->record_type] ?? $record->record_type }} &middot; {{ $record->folder?->name ?? __('main.ged_without_folder') }}</small>
                                        </div>
                                        <span class="status-pill ged-status-{{ $record->status }}">{{ $statusLabels[$record->status] ?? $record->status }}</span>
                                    </article>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                        <strong>{{ __('main.ged_no_records') }}</strong>
                                        <span>{{ __('main.ged_no_records_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="ged-priority-panel">
                            <div class="ged-panel-header">
                                <div>
                                    <span>{{ __('main.ged_monitoring') }}</span>
                                    <h3>{{ __('main.ged_priorities_and_deadlines') }}</h3>
                                </div>
                                <i class="bi bi-exclamation-diamond" aria-hidden="true"></i>
                            </div>

                            <div class="ged-priority-stack">
                                <div>
                                    <strong>{{ $documentManagementDashboard['urgentRecords']->count() }}</strong>
                                    <span>{{ __('main.ged_urgent_files') }}</span>
                                </div>
                                <div>
                                    <strong>{{ $documentManagementDashboard['overdueRecords']->count() }}</strong>
                                    <span>{{ __('main.ged_overdue_records') }}</span>
                                </div>
                                <div>
                                    <strong>{{ $documentManagementDashboard['openRecords']->count() }}</strong>
                                    <span>{{ __('main.ged_pending_processing') }}</span>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section class="ged-dashboard-grid">
                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.ged_classification') }}</span>
                                    <h3>{{ __('main.ged_folders') }}</h3>
                                </div>
                                <i class="bi bi-folder2-open" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($documentManagementDashboard['folders']->take(5) as $folder)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-folder" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $folder->name }}</strong>
                                            <small>{{ $folder->reference }} &middot; {{ $folder->records_count }} {{ __('main.rows') }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-folder-plus" aria-hidden="true"></i>
                                        <strong>{{ __('main.ged_no_folders') }}</strong>
                                        <span>{{ __('main.ged_no_folders_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.ged_processing') }}</span>
                                    <h3>{{ __('main.ged_assigned_records') }}</h3>
                                </div>
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($documentManagementDashboard['openRecords']->take(5) as $record)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-person-check" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $record->assignee?->name ?? __('main.ged_unassigned') }}</strong>
                                            <small>{{ $record->reference }} &middot; {{ $priorityLabels[$record->priority] ?? $record->priority }} &middot; {{ $record->due_at?->format('d/m/Y') ?? '-' }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                                        <strong>{{ __('main.ged_no_pending_records') }}</strong>
                                        <span>{{ __('main.ged_no_pending_records_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.ged_traceability') }}</span>
                                    <h3>{{ __('main.ged_recent_activity') }}</h3>
                                </div>
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($documentManagementDashboard['recentActivities'] as $item)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-activity" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $item['activity']->actor?->name ?? __('main.system') }}</strong>
                                            <small>{{ $item['record']->reference }} &middot; {{ $item['activity']->comment ?: $item['activity']->action }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                        <strong>{{ __('main.ged_no_activity') }}</strong>
                                        <span>{{ __('main.ged_no_activity_text') }}</span>
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

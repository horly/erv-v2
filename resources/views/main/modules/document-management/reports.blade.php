<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_reports') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $exportParams = collect(request()->query())->except('page')->all();
        $typeMax = max(1, (int) $typeRows->max('count'));
        $statusMax = max(1, (int) $statusRows->max('count'));
        $folderMax = max(1, (int) $folderRows->max('count'));
        $validationMax = max(1, (int) $validationRows->max('count'));
        $ratio = fn ($value, $max) => min(100, max(0, round(((float) $value / max(1, (float) $max)) * 100)));
        $recordIcon = fn ($type) => match ($type) {
            \App\Models\DocumentManagementRecord::TYPE_INCOMING => 'bi-inbox',
            \App\Models\DocumentManagementRecord::TYPE_OUTGOING => 'bi-send',
            default => 'bi-file-earmark-text',
        };
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'reports'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_reports') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-dashboard-content accounting-reports-page document-management-page">
                <a class="back-link" href="{{ route('main.document-management.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.ged_dashboard') }}
                </a>

                <section class="page-heading reports-heading">
                    <div>
                        <h1>{{ __('main.ged_reports') }}</h1>
                        <p>{{ __('main.ged_reports_subtitle') }}</p>
                    </div>
                    <div class="report-export-actions">
                        <a class="primary-action" href="{{ route('main.document-management.reports.pdf', [$company, $site] + $exportParams) }}" target="_blank" rel="noopener">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            {{ __('main.print_pdf') }}
                        </a>
                    </div>
                </section>

                <section class="company-card receipt-filter-card report-filter-card">
                    <form method="GET" action="{{ route('main.document-management.reports', [$company, $site]) }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label" for="reportPeriod">{{ __('main.period') }}</label>
                                <select id="reportPeriod" name="period" class="form-select">
                                    <option value="today" @selected($filters['period'] === 'today')>{{ __('main.hr_period_today') }}</option>
                                    <option value="week" @selected($filters['period'] === 'week')>{{ __('admin.week') }}</option>
                                    <option value="month" @selected($filters['period'] === 'month')>{{ __('admin.month') }}</option>
                                    <option value="year" @selected($filters['period'] === 'year')>{{ __('admin.year') }}</option>
                                    <option value="custom" @selected($filters['period'] === 'custom')>{{ __('main.hr_period_custom') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label" for="reportDateFrom">{{ __('main.date_from') }}</label>
                                <input id="reportDateFrom" name="date_from" class="form-control" type="date" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label" for="reportDateTo">{{ __('main.date_to') }}</label>
                                <input id="reportDateTo" name="date_to" class="form-control" type="date" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-12 col-xl-3 d-flex justify-content-end gap-2 receipt-filter-actions">
                                <a class="modal-cancel" href="{{ route('main.document-management.reports', [$company, $site]) }}">{{ __('main.reset_filters') }}</a>
                                <button class="modal-submit" type="submit">{{ __('main.apply_filters') }}</button>
                            </div>
                        </div>
                    </form>
                </section>

                <div class="report-period-note">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    {{ __('main.ged_report_period') }} <strong>{{ $periodLabel }}</strong>
                    <span>{{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}</span>
                </div>

                <section class="report-metric-grid" aria-label="{{ __('main.ged_reports') }}">
                    @foreach ($metrics as $metric)
                        <article class="report-metric report-metric-{{ $metric['tone'] }}">
                            <span class="report-metric-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                            <p>{{ $metric['label'] }}</p>
                            <strong>{{ number_format((float) $metric['value'], 0, ',', ' ') }}</strong>
                            <small>{{ $metric['meta'] }}</small>
                        </article>
                    @endforeach
                </section>

                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <section class="company-card h-100 ged-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon"><i class="bi bi-files" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.ged_report_type_breakdown') }}</h2>
                                </div>
                                <span>{{ $typeRows->count() }} {{ __('admin.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.ged_document_type') }}</th>
                                            <th class="text-end">{{ __('main.ged_documents_count') }}</th>
                                            <th class="text-end">{{ __('main.ged_report_open') }}</th>
                                            <th class="text-end">{{ __('main.ged_report_validated') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($typeRows as $row)
                                            <tr>
                                                <td>
                                                    <div class="ged-report-label-cell">
                                                        <span class="ged-report-row-icon ged-report-row-{{ $row['type'] }}"><i class="bi {{ $recordIcon($row['type']) }}" aria-hidden="true"></i></span>
                                                        <strong>{{ $row['label'] }}</strong>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['count'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['count'], $typeMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><span class="ged-report-soft-count">{{ $row['open'] }}</span></td>
                                                <td class="text-end"><span class="ged-report-soft-count ged-report-soft-success">{{ $row['validated'] }}</span></td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="4">{{ __('main.ged_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-12 col-xl-6">
                        <section class="company-card h-100 ged-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon"><i class="bi bi-ui-checks" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.ged_report_status_breakdown') }}</h2>
                                </div>
                                <span>{{ $statusRows->count() }} {{ __('admin.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.status') }}</th>
                                            <th class="text-end">{{ __('main.ged_documents_count') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($statusRows as $row)
                                            <tr>
                                                <td><span class="status-pill ged-status-{{ $row['status'] }}">{{ $row['label'] }}</span></td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['count'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['count'], $statusMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="2">{{ __('main.ged_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-12 col-xl-7">
                        <section class="company-card h-100 ged-report-card ged-report-card-wide">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.ged_report_folder_breakdown') }}</h2>
                                </div>
                                <span>{{ $folderRows->count() }} {{ __('admin.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.ged_folder') }}</th>
                                            <th>{{ __('main.ged_category') }}</th>
                                            <th class="text-end">{{ __('main.ged_documents_count') }}</th>
                                            <th class="text-end">{{ __('main.ged_report_urgent_documents') }}</th>
                                            <th>{{ __('main.ged_last_activity') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($folderRows->take(5) as $row)
                                            <tr>
                                                <td>
                                                    <div class="ged-report-label-cell">
                                                        <span class="ged-report-row-icon ged-report-row-folder"><i class="bi bi-folder" aria-hidden="true"></i></span>
                                                        <strong>{{ $row['name'] }}</strong>
                                                    </div>
                                                </td>
                                                <td><span class="ged-report-category">{{ $row['category'] ?: '-' }}</span></td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['count'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['count'], $folderMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><span class="ged-report-soft-count {{ $row['urgent'] > 0 ? 'ged-report-soft-danger' : '' }}">{{ $row['urgent'] }}</span></td>
                                                <td><span class="ged-report-date">{{ $row['last_activity'] ? \Illuminate\Support\Carbon::parse($row['last_activity'])->format('d/m/Y H:i') : '-' }}</span></td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="5">{{ __('main.ged_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-12 col-xl-5">
                        <section class="company-card h-100 ged-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon"><i class="bi bi-check2-square" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.ged_report_validation_breakdown') }}</h2>
                                </div>
                                <span>{{ $validationRows->count() }} {{ __('admin.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.status') }}</th>
                                            <th class="text-end">{{ __('main.ged_validation_requests_title') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($validationRows as $row)
                                            <tr>
                                                <td><span class="status-pill ged-validation-request-{{ $row['status'] }}">{{ $row['label'] }}</span></td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['count'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['count'], $validationMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="2">{{ __('main.ged_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="company-card ged-report-card">
                    <header class="hr-report-card-header ged-report-card-header">
                        <div>
                            <span class="ged-report-heading-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
                            <h2>{{ __('main.ged_report_recent_activity') }}</h2>
                        </div>
                        <span>{{ $recentActivities->count() }} {{ __('admin.rows') }}</span>
                    </header>
                    <div class="table-responsive">
                        <table class="company-table report-table ged-report-table ged-report-activity-table">
                            <thead>
                                <tr>
                                    <th>{{ __('main.date') }}</th>
                                    <th>{{ __('main.ged_document') }}</th>
                                    <th>{{ __('main.ged_trace_action') }}</th>
                                    <th>{{ __('main.ged_trace_actor') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentActivities as $activity)
                                    <tr>
                                        <td><span class="ged-report-date">{{ $activity->created_at?->format('d/m/Y H:i') }}</span></td>
                                        <td>
                                            <div class="ged-report-document-cell">
                                                <strong>{{ $activity->record?->reference ?? '-' }}</strong>
                                                <span>{{ $activity->record?->subject ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td><span class="ged-report-action">{{ $activity->comment ?: $activity->action }}</span></td>
                                        <td><strong>{{ $activity->actor?->name ?? __('main.system') }}</strong></td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="4">{{ __('main.ged_trace_no_activity') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

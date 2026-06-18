<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_reports') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
    @php
        $typeMax = max(1, (int) $typeRows->max('count'));
        $statusMax = max(1, (int) $statusRows->max('count'));
        $locationMax = max(1, (int) max($locationRows->max('records') ?? 0, $locationRows->max('containers') ?? 0));
        $containerMax = max(1, (int) $containerRows->max('records'));
        $ratio = fn ($value, $max) => min(100, max(0, round(((float) $value / max(1, (float) $max)) * 100)));
    @endphp

    <div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
        @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'reports'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.archive_reports') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-dashboard-content accounting-reports-page archiving-page archive-reports-page">
                <a class="back-link" href="{{ route('main.archiving.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.archive_dashboard') }}
                </a>

                <section class="page-heading reports-heading">
                    <div>
                        <h1>{{ __('main.archive_reports') }}</h1>
                        <p>{{ __('main.archive_reports_subtitle') }}</p>
                    </div>
                    <div class="report-export-actions">
                        <a class="primary-action" href="{{ route('main.archiving.reports.pdf', [$company, $site]) }}" target="_blank" rel="noopener">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            {{ __('main.print_pdf') }}
                        </a>
                    </div>
                </section>

                <div class="report-period-note">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    {{ __('main.archive_report_generated') }}
                    <span>{{ now()->format('d/m/Y H:i') }}</span>
                </div>

                <section class="report-metric-grid" aria-label="{{ __('main.archive_reports') }}">
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
                        <section class="company-card h-100 ged-report-card archive-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon archive-report-heading-icon"><i class="bi bi-files" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.archive_report_by_type') }}</h2>
                                </div>
                                <span>{{ $typeRows->count() }} {{ __('main.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table archive-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.type') }}</th>
                                            <th class="text-end">{{ __('main.documents') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($typeRows as $row)
                                            <tr>
                                                <td>
                                                    <div class="ged-report-label-cell">
                                                        <span class="ged-report-row-icon archive-report-row-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                                        <strong>{{ $row['label'] }}</strong>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['count'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['count'], $typeMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="2">{{ __('main.archive_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-12 col-xl-6">
                        <section class="company-card h-100 ged-report-card archive-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon archive-report-heading-icon"><i class="bi bi-ui-checks" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.archive_report_by_status') }}</h2>
                                </div>
                                <span>{{ $statusRows->count() }} {{ __('main.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table archive-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.status') }}</th>
                                            <th class="text-end">{{ __('main.documents') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($statusRows as $row)
                                            <tr>
                                                <td><span class="status-pill archive-status-{{ $row['status'] }}">{{ $row['label'] }}</span></td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['count'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['count'], $statusMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="2">{{ __('main.archive_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-12 col-xl-7">
                        <section class="company-card h-100 ged-report-card archive-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon archive-report-heading-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.archive_report_by_location') }}</h2>
                                </div>
                                <span>{{ $locationRows->count() }} {{ __('main.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table archive-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.location') }}</th>
                                            <th>{{ __('main.status') }}</th>
                                            <th class="text-end">{{ __('main.archive_containers') }}</th>
                                            <th class="text-end">{{ __('main.documents') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($locationRows as $row)
                                            <tr>
                                                <td>
                                                    <div class="ged-report-document-cell">
                                                        <strong>{{ $row['name'] }}</strong>
                                                        <span>{{ $row['label'] }}</span>
                                                    </div>
                                                </td>
                                                <td><span class="ged-report-category">{{ $row['status'] }}</span></td>
                                                <td class="text-end"><span class="ged-report-soft-count">{{ $row['containers'] }}</span></td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['records'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['records'], $locationMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="4">{{ __('main.archive_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-12 col-xl-5">
                        <section class="company-card h-100 ged-report-card archive-report-card">
                            <header class="hr-report-card-header ged-report-card-header">
                                <div>
                                    <span class="ged-report-heading-icon archive-report-heading-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                                    <h2>{{ __('main.archive_report_by_container') }}</h2>
                                </div>
                                <span>{{ $containerRows->count() }} {{ __('main.rows') }}</span>
                            </header>
                            <div class="table-responsive">
                                <table class="company-table report-table ged-report-table archive-report-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.archive_container') }}</th>
                                            <th class="text-end">{{ __('main.documents') }}</th>
                                            <th>{{ __('main.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($containerRows as $row)
                                            <tr>
                                                <td>
                                                    <div class="ged-report-document-cell">
                                                        <strong>{{ $row['label'] }}</strong>
                                                        <span>{{ $row['category'] }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="ged-report-number-cell">
                                                        <strong>{{ $row['records'] }}</strong>
                                                        <span class="ged-report-progress"><span style="width: {{ $ratio($row['records'], $containerMax) }}%"></span></span>
                                                    </div>
                                                </td>
                                                <td><span class="status-pill archive-status-{{ $row['status_key'] }}">{{ $row['status'] }}</span></td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="3">{{ __('main.archive_report_no_data') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

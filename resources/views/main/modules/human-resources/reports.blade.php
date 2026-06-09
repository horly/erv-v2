<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_reports') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $exportParams = collect(request()->query())->except('page')->all();
        $amount = fn ($value, $currency) => number_format((float) $value, 2, ',', ' ').' '.$currency;
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'reports'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_reports') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-dashboard-content accounting-reports-page human-resources-page">
                <a class="back-link" href="{{ route('main.human-resources.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.hr_dashboard') }}
                </a>

                <section class="page-heading reports-heading">
                    <div>
                        <h1>{{ __('main.hr_reports') }}</h1>
                        <p>{{ __('main.hr_reports_subtitle') }}</p>
                    </div>
                    <div class="report-export-actions">
                        <a class="primary-action" href="{{ route('main.human-resources.reports.pdf', [$company, $site] + $exportParams) }}" target="_blank" rel="noopener">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            {{ __('main.print_pdf') }}
                        </a>
                    </div>
                </section>

                <section class="company-card receipt-filter-card report-filter-card">
                    <form method="GET" action="{{ route('main.human-resources.reports', [$company, $site]) }}" class="receipt-filter-form">
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
                                <a class="modal-cancel" href="{{ route('main.human-resources.reports', [$company, $site]) }}">{{ __('main.reset_filters') }}</a>
                                <button class="modal-submit" type="submit">{{ __('main.apply_filters') }}</button>
                            </div>
                        </div>
                    </form>
                </section>

                <div class="report-period-note">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    {{ __('main.hr_report_period') }} <strong>{{ $periodLabel }}</strong>
                    <span>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</span>
                </div>

                <section class="report-metric-grid" aria-label="{{ __('main.hr_reports') }}">
                    @foreach ($metrics as $metric)
                        <article class="report-metric report-metric-{{ $metric['tone'] }}">
                            <span class="report-metric-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                            <p>{{ $metric['label'] }}</p>
                            <strong>{{ is_numeric($metric['value']) ? number_format($metric['value'], 0, ',', ' ') : $metric['value'] }}</strong>
                            <small>{{ $metric['meta'] }}</small>
                        </article>
                    @endforeach
                </section>

                <section class="hr-report-grid" aria-label="{{ __('main.hr_report_attendance_breakdown') }}">
                    @foreach ([
                        ['label' => __('main.hr_attendance_status_present'), 'value' => $attendanceReport['present'], 'icon' => 'bi-check-circle'],
                        ['label' => __('main.hr_attendance_status_late'), 'value' => $attendanceReport['late'], 'icon' => 'bi-clock-history'],
                        ['label' => __('main.hr_attendance_status_absent'), 'value' => $attendanceReport['absent'], 'icon' => 'bi-x-circle'],
                        ['label' => __('main.hr_attendance_status_remote'), 'value' => $attendanceReport['remote'], 'icon' => 'bi-laptop'],
                        ['label' => __('main.hr_attendance_status_on_leave'), 'value' => $attendanceReport['on_leave'], 'icon' => 'bi-calendar-check'],
                    ] as $item)
                        <article>
                            <span><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <strong>{{ $item['value'] }}</strong>
                                <small>{{ $item['label'] }}</small>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="company-card">
                    <header class="hr-report-card-header">
                        <h2>{{ __('main.hr_report_departments') }}</h2>
                        <span>{{ $departmentRows->count() }} {{ __('admin.rows') }}</span>
                    </header>
                    <div class="table-responsive">
                        <table class="company-table report-table">
                            <thead>
                                <tr>
                                    <th>{{ __('main.hr_departments') }}</th>
                                    <th class="text-end">{{ __('main.hr_employees') }}</th>
                                    <th class="text-end">{{ __('main.hr_active_employees') }}</th>
                                    <th class="text-end">{{ __('main.hr_contracts') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departmentRows as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="text-end">{{ $row['employees'] }}</td>
                                        <td class="text-end">{{ $row['active_employees'] }}</td>
                                        <td class="text-end">{{ $row['active_contracts'] }}</td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="4">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="company-card">
                    <header class="hr-report-card-header">
                        <h2>{{ __('main.hr_report_payroll') }}</h2>
                        <span>{{ $payrollEntries->count() }} {{ __('admin.rows') }}</span>
                    </header>
                    <div class="table-responsive">
                        <table class="company-table report-table">
                            <thead>
                                <tr>
                                    <th>{{ __('main.reference') }}</th>
                                    <th>{{ __('main.hr_employee') }}</th>
                                    <th>{{ __('main.period') }}</th>
                                    <th class="text-end">{{ __('main.hr_gross_salary') }}</th>
                                    <th class="text-end">{{ __('main.hr_net_salary') }}</th>
                                    <th>{{ __('main.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payrollEntries as $entry)
                                    <tr>
                                        <td>{{ $entry->reference }}</td>
                                        <td>{{ $entry->employee?->full_name ?? '-' }}</td>
                                        <td>{{ optional($entry->period_month)->translatedFormat('F Y') }}</td>
                                        <td class="text-end">{{ $amount($entry->gross_salary, $entry->currency) }}</td>
                                        <td class="text-end">{{ $amount($entry->net_salary, $entry->currency) }}</td>
                                        <td>{{ $payrollStatuses[$entry->status] ?? __('main.hr_payroll_status_'.$entry->status) }}</td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="6">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="company-card">
                    <header class="hr-report-card-header">
                        <h2>{{ __('main.hr_report_leave') }}</h2>
                        <span>{{ $leaves->count() }} {{ __('admin.rows') }}</span>
                    </header>
                    <div class="table-responsive">
                        <table class="company-table report-table">
                            <thead>
                                <tr>
                                    <th>{{ __('main.reference') }}</th>
                                    <th>{{ __('main.hr_employee') }}</th>
                                    <th>{{ __('main.date') }}</th>
                                    <th class="text-end">{{ __('main.hr_leave_days') }}</th>
                                    <th>{{ __('main.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaves as $leave)
                                    <tr>
                                        <td>{{ $leave->reference }}</td>
                                        <td>{{ $leave->employee?->full_name ?? '-' }}</td>
                                        <td>{{ optional($leave->start_date)->format('d/m/Y') }} - {{ optional($leave->end_date)->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ number_format((float) $leave->days_count, 2, ',', ' ') }}</td>
                                        <td>{{ $leaveStatuses[$leave->status] ?? __('main.hr_leave_status_'.$leave->status) }}</td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.hr_no_records') }}</td></tr>
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

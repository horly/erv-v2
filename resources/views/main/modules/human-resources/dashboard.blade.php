<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_dashboard') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $humanResourcesDashboard = array_merge([
            'kpis' => [],
            'employees' => collect(),
            'departments' => collect(),
            'leaveRequests' => collect(),
            'todayAttendances' => collect(),
            'payrollEntries' => collect(),
            'profileRecords' => collect(),
            'latestResourceRecords' => collect(),
            'presentToday' => 0,
            'profileCompletion' => 0,
            'foundation' => [],
        ], $humanResourcesDashboard ?? []);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'dashboard'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_dashboard') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page human-resources-page">
                <a class="back-link" href="{{ route('main.companies.sites.show', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ $site->name }}
                </a>

                <section class="module-heading">
                    <span class="module-heading-icon {{ $moduleMeta['class'] }}">
                        <i class="bi {{ $moduleMeta['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>{{ __('main.hr_dashboard') }}</h2>
                        <p>{{ __('main.hr_dashboard_subtitle') }}</p>
                    </div>
                </section>

                <section class="dashboard-content module-dashboard-content human-resources-dashboard-content">
                    <section class="kpi-grid module-kpi-grid" aria-label="{{ __('admin.indicators') }}">
                        @foreach ($humanResourcesDashboard['kpis'] as $kpi)
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

                    <section class="human-resources-overview">
                        <article class="hr-panel hr-panel-wide">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.hr_live_directory') }}</span>
                                    <h3>{{ __('main.hr_employees') }}</h3>
                                </div>
                                <strong>{{ $humanResourcesDashboard['employees']->count() }} {{ __('main.rows') }}</strong>
                            </div>

                            <div class="hr-user-list">
                                @forelse ($humanResourcesDashboard['employees']->take(6) as $employee)
                                    <div class="hr-user-row">
                                        <span class="avatar">{{ strtoupper(mb_substr($employee->first_name, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $employee->full_name }}</strong>
                                            <span>{{ $employee->job_title ?: __('main.hr_employee') }} &middot; {{ $employee->department?->name ?? __('main.hr_no_department') }}</span>
                                        </div>
                                        <em>{{ $employee->employee_number }}</em>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                                        <strong>{{ __('main.hr_no_collaborators') }}</strong>
                                        <span>{{ __('main.hr_no_collaborators_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.hr_attendance') }}</span>
                                    <h3>{{ __('main.hr_today_presence') }}</h3>
                                </div>
                                <i class="bi bi-calendar-week" aria-hidden="true"></i>
                            </div>

                            <div class="hr-responsible-card">
                                <strong>{{ $humanResourcesDashboard['presentToday'] }} / {{ $humanResourcesDashboard['employees']->count() }}</strong>
                                <span>{{ __('main.hr_present_collaborators') }}</span>
                                <div class="hr-progress" aria-label="{{ __('main.hr_profile_completion') }}">
                                    <span style="width: {{ min(100, max(0, (int) $humanResourcesDashboard['profileCompletion'])) }}%"></span>
                                </div>
                                <small>{{ __('main.hr_profile_completion') }} : {{ $humanResourcesDashboard['profileCompletion'] }}%</small>
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.hr_payroll') }}</span>
                                    <h3>{{ __('main.hr_recent_payroll') }}</h3>
                                </div>
                                <i class="bi bi-cash-stack" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($humanResourcesDashboard['payrollEntries'] as $payroll)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-cash" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $payroll->employee?->full_name ?? $payroll->reference }}</strong>
                                            <small>{{ $payroll->reference }} &middot; {{ number_format((float) $payroll->net_salary, 2, ',', ' ') }} {{ $payroll->currency }} &middot; {{ __('main.hr_payroll_status_'.$payroll->status) }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                                        <strong>{{ __('main.hr_no_payroll_entries') }}</strong>
                                        <span>{{ __('main.hr_no_payroll_entries_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    </section>

                    <section class="human-resources-overview human-resources-secondary">
                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.hr_organization') }}</span>
                                    <h3>{{ __('main.hr_departments') }}</h3>
                                </div>
                                <i class="bi bi-diagram-3" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($humanResourcesDashboard['departments']->take(5) as $department)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $department->name }}</strong>
                                            <small>{{ trans_choice('main.hr_department_employees_count', $department->employees_count, ['count' => $department->employees_count]) }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                        <strong>{{ __('main.hr_no_departments') }}</strong>
                                        <span>{{ __('main.hr_no_departments_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.hr_leave') }}</span>
                                    <h3>{{ __('main.hr_recent_leave_requests') }}</h3>
                                </div>
                                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($humanResourcesDashboard['leaveRequests'] as $leave)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-calendar2-range" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $leave->employee?->full_name ?? $leave->reference }}</strong>
                                            <small>{{ $leave->reference }} &middot; {{ __('main.hr_leave_status_'.$leave->status) }} &middot; {{ optional($leave->start_date)->format('d/m/Y') }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                        <strong>{{ __('main.hr_no_leave_requests') }}</strong>
                                        <span>{{ __('main.hr_no_leave_requests_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        <article class="hr-panel">
                            <div class="hr-panel-header">
                                <div>
                                    <span>{{ __('main.hr_activity') }}</span>
                                    <h3>{{ __('main.hr_recent_records') }}</h3>
                                </div>
                                <i class="bi bi-folder2-open" aria-hidden="true"></i>
                            </div>

                            <div class="hr-activity-list">
                                @forelse ($humanResourcesDashboard['latestResourceRecords'] as $record)
                                    <div class="hr-activity-row">
                                        <span><i class="bi bi-folder-check" aria-hidden="true"></i></span>
                                        <div>
                                            <strong>{{ $record->title }}</strong>
                                            <small>{{ $record->employee?->full_name ?? __('main.module_human_resources') }} &middot; {{ $record->reference }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="module-empty-state module-empty-state-small">
                                        <i class="bi bi-folder2-open" aria-hidden="true"></i>
                                        <strong>{{ __('main.hr_no_resource_records') }}</strong>
                                        <span>{{ __('main.hr_no_resource_records_text') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    </section>

                    <section class="human-resources-foundation" aria-label="{{ __('main.hr_foundation') }}">
                        @foreach ($humanResourcesDashboard['foundation'] as $item)
                            <article>
                                <span><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i></span>
                                <div>
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['status'] }}</small>
                                </div>
                            </article>
                        @endforeach
                    </section>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_attendance') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalAttendances = $attendances->total();
        $attendancePayload = fn ($attendance) => [
            'human_resource_employee_id' => $attendance->human_resource_employee_id,
            'work_date' => optional($attendance->work_date)->format('Y-m-d'),
            'check_in_at' => $attendance->check_in_at ? substr($attendance->check_in_at, 0, 5) : '',
            'check_out_at' => $attendance->check_out_at ? substr($attendance->check_out_at, 0, 5) : '',
            'worked_hours' => number_format((float) $attendance->worked_hours, 2, '.', ''),
            'status' => $attendance->status,
            'notes' => $attendance->notes,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'attendance'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_attendance') }}</h1>
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
                        <h1>{{ __('main.hr_attendance') }}</h1>
                        <p>{{ __('main.hr_attendance_subtitle') }}</p>
                    </div>
                    <div class="hr-actions">
                        <button class="secondary-action" type="button" data-bs-toggle="modal" data-bs-target="#attendanceImportModal">
                            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                            {{ __('main.hr_import_attendance') }}
                        </button>
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#attendanceModal" data-attendance-mode="create">
                            <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                            {{ __('main.hr_new_attendance') }}
                        </button>
                    </div>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                @if (session('hr_import_errors'))
                    <div class="modal-total-strip tax-information-strip">
                        <span><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ __('main.hr_import_errors_title') }}</span>
                        <strong>{{ implode(' | ', session('hr_import_errors')) }}</strong>
                    </div>
                @endif

                <form class="hr-report-filter" method="GET" action="{{ route('main.human-resources.attendance', [$company, $site]) }}">
                    <select name="period" class="form-select">
                        <option value="today" @selected($period === 'today')>{{ __('main.hr_period_today') }}</option>
                        <option value="week" @selected($period === 'week')>{{ __('main.hr_period_week') }}</option>
                        <option value="month" @selected($period === 'month')>{{ __('main.hr_period_month') }}</option>
                        <option value="year" @selected($period === 'year')>{{ __('main.hr_period_year') }}</option>
                        <option value="custom" @selected($period === 'custom')>{{ __('main.hr_period_custom') }}</option>
                    </select>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    <button class="primary-action" type="submit">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i>
                        {{ __('main.hr_generate_report') }}
                    </button>
                    <a class="secondary-action" href="{{ route('main.human-resources.attendance.report.pdf', [$company, $site, 'period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        {{ __('main.hr_export_attendance_pdf') }}
                    </a>
                </form>

                <section class="hr-report-grid" aria-label="{{ __('main.hr_reports') }}">
                    @foreach ([
                        ['label' => __('main.hr_attendance_status_present'), 'value' => $attendanceReport['present'], 'icon' => 'bi-check-circle'],
                        ['label' => __('main.hr_attendance_status_late'), 'value' => $attendanceReport['late'], 'icon' => 'bi-clock-history'],
                        ['label' => __('main.hr_attendance_status_absent'), 'value' => $attendanceReport['absent'], 'icon' => 'bi-x-circle'],
                        ['label' => __('main.hr_attendance_status_on_leave'), 'value' => $attendanceReport['on_leave'], 'icon' => 'bi-calendar-check'],
                        ['label' => __('main.hr_worked_hours'), 'value' => number_format((float) $attendanceReport['worked_hours'], 2, ',', ' '), 'icon' => 'bi-hourglass-split'],
                    ] as $metric)
                        <article>
                            <span><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <strong>{{ $metric['value'] }}</strong>
                                <small>{{ $metric['label'] }}</small>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count"><strong id="visibleCount">{{ $attendances->count() }}</strong> / <strong>{{ $totalAttendances }}</strong> {{ __('admin.rows') }}</span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-attendance-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.hr_employee') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th>{{ __('main.hr_check_in') }}</th>
                                    <th>{{ __('main.hr_check_out') }}</th>
                                    <th class="text-end">{{ __('main.hr_worked_hours') }}</th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendances as $attendance)
                                    <tr>
                                        <td>{{ ($attendances->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>{{ optional($attendance->work_date)->format('d/m/Y') }}</td>
                                        <td>{{ $attendance->employee?->full_name ?? '-' }}</td>
                                        <td><span class="status-pill hr-status-{{ $attendance->status }}">{{ $attendanceStatuses[$attendance->status] ?? $attendance->status }}</span></td>
                                        <td>{{ $attendance->check_in_at ? substr($attendance->check_in_at, 0, 5) : '-' }}</td>
                                        <td>{{ $attendance->check_out_at ? substr($attendance->check_out_at, 0, 5) : '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $attendance->worked_hours, 2, ',', ' ') }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#attendanceModal" data-attendance-mode="edit" data-attendance-action="{{ route('main.human-resources.attendance.update', [$company, $site, $attendance]) }}" data-attendance-id="{{ $attendance->id }}" data-attendance-values="{{ base64_encode(json_encode($attendancePayload($attendance))) }}" aria-label="{{ __('admin.edit') }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                                                <form method="POST" action="{{ route('main.human-resources.attendance.destroy', [$company, $site, $attendance]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" data-delete-trigger data-delete-title="{{ __('main.hr_delete_attendance_title') }}" data-delete-text="{{ __('main.hr_delete_attendance_text') }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}" aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($attendances->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $attendances->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $attendances->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalAttendances }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($attendances->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $attendances->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($attendances->getUrlRange(1, $attendances->lastPage()) as $page => $url)
                                @if ($page === $attendances->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($attendances->hasMorePages())<a href="{{ $attendances->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal hr-attendance-modal" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-attendance-form" method="POST" action="{{ $attendanceFormAction }}" data-create-action="{{ route('main.human-resources.attendance.store', [$company, $site]) }}" data-title-create="{{ __('main.hr_new_attendance') }}" data-title-edit="{{ __('main.hr_edit_attendance') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="attendanceHttpMethod" value="PUT" @disabled(! $isEditingAttendance)>
                <input type="hidden" name="form_mode" id="attendanceFormMode" value="{{ $isEditingAttendance ? 'edit' : 'create' }}">
                <input type="hidden" name="attendance_id" id="attendanceId" value="{{ old('attendance_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="attendanceModalLabel"><i class="bi bi-calendar-week" aria-hidden="true"></i>{{ $isEditingAttendance ? __('main.hr_edit_attendance') : __('main.hr_new_attendance') }}</h2>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="attendance_employee" class="form-label">{{ __('main.hr_employee') }} *</label>
                            <select id="attendance_employee" name="human_resource_employee_id" class="form-select @error('human_resource_employee_id') is-invalid @enderror" data-attendance-field data-default-value="">
                                <option value="">{{ __('main.select') }}</option>
                                @foreach ($employeeOptions as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) old('human_resource_employee_id') === (string) $employee->id)>{{ $employee->employee_number }} - {{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                            @error('human_resource_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="attendance_work_date" class="form-label">{{ __('main.date') }} *</label>
                            <input id="attendance_work_date" name="work_date" type="date" class="form-control @error('work_date') is-invalid @enderror" value="{{ old('work_date', now()->toDateString()) }}" data-attendance-field data-default-value="{{ now()->toDateString() }}">
                            @error('work_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="attendance_check_in" class="form-label">{{ __('main.hr_check_in') }}</label>
                            <input id="attendance_check_in" name="check_in_at" type="time" class="form-control @error('check_in_at') is-invalid @enderror" value="{{ old('check_in_at') }}" data-attendance-field data-default-value="">
                            @error('check_in_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="attendance_check_out" class="form-label">{{ __('main.hr_check_out') }}</label>
                            <input id="attendance_check_out" name="check_out_at" type="time" class="form-control @error('check_out_at') is-invalid @enderror" value="{{ old('check_out_at') }}" data-attendance-field data-default-value="">
                            @error('check_out_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="attendance_worked_hours" class="form-label">{{ __('main.hr_worked_hours') }}</label>
                            <input id="attendance_worked_hours" name="worked_hours" type="number" min="0" max="24" step="0.01" class="form-control @error('worked_hours') is-invalid @enderror" value="{{ old('worked_hours') }}" data-attendance-field data-default-value="">
                            @error('worked_hours')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="attendance_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="attendance_status" name="status" class="form-select @error('status') is-invalid @enderror" data-attendance-field data-default-value="{{ \App\Models\HumanResourceAttendance::STATUS_PRESENT }}">
                                @foreach ($attendanceStatuses as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected(old('status', \App\Models\HumanResourceAttendance::STATUS_PRESENT) === $statusValue)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="attendance_notes" class="form-label">{{ __('main.notes') }}</label>
                            <textarea id="attendance_notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" data-attendance-field data-default-value="">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="attendanceSubmit">{{ $isEditingAttendance ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade subscription-modal hr-attendance-import-modal" id="attendanceImportModal" tabindex="-1" aria-labelledby="attendanceImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form" method="POST" action="{{ route('main.human-resources.attendance.import', [$company, $site]) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="attendanceImportModalLabel"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>{{ __('main.hr_import_attendance') }}</h2>
                    <p class="form-text">{{ __('main.hr_attendance_import_help') }}</p>
                    <label for="attendance_file" class="form-label">{{ __('main.file') }} *</label>
                    <input id="attendance_file" name="attendance_file" type="file" accept=".csv,.txt,.xlsx" class="form-control @error('attendance_file') is-invalid @enderror">
                    @error('attendance_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action">{{ __('main.import') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('attendanceModal');
            const form = modal?.querySelector('.hr-attendance-form');
            const title = document.getElementById('attendanceModalLabel');
            const method = document.getElementById('attendanceHttpMethod');
            const mode = document.getElementById('attendanceFormMode');
            const id = document.getElementById('attendanceId');
            const submit = document.getElementById('attendanceSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-attendance-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.attendanceMode === 'edit';
                form.action = isEdit ? button.dataset.attendanceAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.attendanceId : '';
                method.disabled = !isEdit;
                fields.forEach((field) => field.value = field.dataset.defaultValue || '');
                if (!isEdit) return;
                const values = JSON.parse(atob(button.dataset.attendanceValues || 'e30='));
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) field.value = values[field.name] ?? '';
                });
            };

            modal.addEventListener('show.bs.modal', (event) => setMode(event.relatedTarget));
            @if ($hasAttendanceErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

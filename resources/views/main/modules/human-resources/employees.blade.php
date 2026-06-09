<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_employees') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalEmployees = $employees->total();
        $employeePayload = fn ($employee) => [
            'employee_number' => $employee->employee_number,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'human_resource_department_id' => $employee->human_resource_department_id,
            'professional_email' => $employee->professional_email,
            'personal_email' => $employee->personal_email,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'job_title' => $employee->job_title,
            'employment_type' => $employee->employment_type,
            'hire_date' => optional($employee->hire_date)->format('Y-m-d'),
            'termination_date' => optional($employee->termination_date)->format('Y-m-d'),
            'status' => $employee->status,
            'emergency_contact_name' => $employee->emergency_contact_name,
            'emergency_contact_phone' => $employee->emergency_contact_phone,
            'notes' => $employee->notes,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'employees'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_employees') }}</h1>
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
                        <h1>{{ __('main.hr_employees') }}</h1>
                        <p>{{ __('main.hr_employees_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#employeeModal" data-employee-mode="create">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                        {{ __('main.hr_new_employee') }}
                    </button>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $employees->count() }}</strong>
                        /
                        <strong>{{ $totalEmployees }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-employees-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.number') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.hr_departments') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.hr_contracts') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employees as $employee)
                                    <tr>
                                        <td>{{ $employee->employee_number }}</td>
                                        <td>
                                            <strong>{{ $employee->full_name }}</strong>
                                            @if ($employee->job_title || $employee->professional_email)
                                                <small class="d-block text-muted">{{ $employee->job_title ?: $employee->professional_email }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $employee->department?->name ?? '-' }}</td>
                                        <td>{{ $employee->activeContract?->reference ?? '-' }}</td>
                                        <td><span class="status-pill hr-status-{{ $employee->status }}">{{ $employeeStatuses[$employee->status] ?? $employee->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#employeeModal" data-employee-mode="edit" data-employee-action="{{ route('main.human-resources.employees.update', [$company, $site, $employee]) }}" data-employee-id="{{ $employee->id }}" data-employee-values="{{ base64_encode(json_encode($employeePayload($employee))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.human-resources.employees.destroy', [$company, $site, $employee]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.hr_delete_employee_title') }}" data-delete-text="{{ __('main.hr_delete_employee_text', ['name' => $employee->full_name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="6">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="6">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($employees->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $employees->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $employees->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalEmployees }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($employees->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $employees->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($employees->getUrlRange(1, $employees->lastPage()) as $page => $url)
                                @if ($page === $employees->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($employees->hasMorePages())<a href="{{ $employees->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal hr-employee-modal" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-employee-form" method="POST" action="{{ $employeeFormAction }}" data-create-action="{{ route('main.human-resources.employees.store', [$company, $site]) }}" data-title-create="{{ __('main.hr_new_employee') }}" data-title-edit="{{ __('main.hr_edit_employee') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasEmployeeErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="employeeHttpMethod" value="PUT" @disabled(! $isEditingEmployee)>
                <input type="hidden" name="form_mode" id="employeeFormMode" value="{{ $isEditingEmployee ? 'edit' : 'create' }}">
                <input type="hidden" name="employee_id" id="employeeId" value="{{ old('employee_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="employeeModalLabel"><i class="bi bi-person-plus" aria-hidden="true"></i>{{ $isEditingEmployee ? __('main.hr_edit_employee') : __('main.hr_new_employee') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">{{ __('main.first_name') }} *</label>
                                <input id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" placeholder="{{ __('main.hr_first_name_placeholder') }}" data-employee-field data-default-value="">
                                @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">{{ __('main.last_name') }} *</label>
                                <input id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" placeholder="{{ __('main.hr_last_name_placeholder') }}" data-employee-field data-default-value="">
                                @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="employee_number" class="form-label">{{ __('main.number') }}</label>
                                <input id="employee_number" name="employee_number" class="form-control @error('employee_number') is-invalid @enderror" value="{{ old('employee_number') }}" placeholder="{{ __('main.hr_employee_number_placeholder') }}" data-employee-field data-default-value="">
                                @error('employee_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="employee_status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="employee_status" name="status" class="form-select @error('status') is-invalid @enderror" data-employee-field data-default-value="{{ \App\Models\HumanResourceEmployee::STATUS_ACTIVE }}">
                                    @foreach ($employeeStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', \App\Models\HumanResourceEmployee::STATUS_ACTIVE) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-briefcase" aria-hidden="true"></i> {{ __('main.hr_employee_work_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="human_resource_department_id" class="form-label">{{ __('main.hr_departments') }}</label>
                                <select id="human_resource_department_id" name="human_resource_department_id" class="form-select @error('human_resource_department_id') is-invalid @enderror" data-employee-field data-default-value="">
                                    <option value="">{{ __('main.hr_no_department') }}</option>
                                    @foreach ($departmentOptions as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('human_resource_department_id') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('human_resource_department_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="job_title" class="form-label">{{ __('main.job_title') }}</label>
                                <input id="job_title" name="job_title" class="form-control @error('job_title') is-invalid @enderror" value="{{ old('job_title') }}" placeholder="{{ __('main.hr_job_title_placeholder') }}" data-employee-field data-default-value="">
                                @error('job_title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="employment_type" class="form-label">{{ __('main.hr_employment_type') }} *</label>
                                <select id="employment_type" name="employment_type" class="form-select @error('employment_type') is-invalid @enderror" data-employee-field data-default-value="{{ \App\Models\HumanResourceEmployee::EMPLOYMENT_FULL_TIME }}">
                                    @foreach ($employmentTypes as $type => $label)
                                        <option value="{{ $type }}" @selected(old('employment_type', \App\Models\HumanResourceEmployee::EMPLOYMENT_FULL_TIME) === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('employment_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="hire_date" class="form-label">{{ __('main.hire_date') }}</label>
                                <input id="hire_date" name="hire_date" type="date" class="form-control @error('hire_date') is-invalid @enderror" value="{{ old('hire_date') }}" data-employee-field data-default-value="">
                                @error('hire_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-person-lines-fill" aria-hidden="true"></i> {{ __('main.hr_employee_contact_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="professional_email" class="form-label">{{ __('main.professional_email') }}</label>
                                <input id="professional_email" name="professional_email" type="email" class="form-control @error('professional_email') is-invalid @enderror" value="{{ old('professional_email') }}" placeholder="{{ __('main.hr_professional_email_placeholder') }}" data-employee-field data-default-value="">
                                @error('professional_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">{{ __('main.phone') }}</label>
                                <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}" data-employee-field data-default-value="">
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_contact_name" class="form-label">{{ __('main.hr_emergency_contact_name') }}</label>
                                <input id="emergency_contact_name" name="emergency_contact_name" class="form-control @error('emergency_contact_name') is-invalid @enderror" value="{{ old('emergency_contact_name') }}" placeholder="{{ __('main.hr_emergency_contact_name') }}" data-employee-field data-default-value="">
                                @error('emergency_contact_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_contact_phone" class="form-label">{{ __('main.hr_emergency_contact_phone') }}</label>
                                <input id="emergency_contact_phone" name="emergency_contact_phone" class="form-control @error('emergency_contact_phone') is-invalid @enderror" value="{{ old('emergency_contact_phone') }}" placeholder="{{ __('main.phone') }}" data-employee-field data-default-value="">
                                @error('emergency_contact_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">{{ __('main.address') }}</label>
                                <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="{{ __('main.address') }}" data-employee-field data-default-value="">{{ old('address') }}</textarea>
                                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('main.notes') }}" data-employee-field data-default-value="">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="employeeSubmit">{{ $isEditingEmployee ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('employeeModal');
            const form = modal?.querySelector('.hr-employee-form');
            const title = document.getElementById('employeeModalLabel');
            const method = document.getElementById('employeeHttpMethod');
            const mode = document.getElementById('employeeFormMode');
            const id = document.getElementById('employeeId');
            const submit = document.getElementById('employeeSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-employee-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.employeeMode === 'edit';
                form.action = isEdit ? button.dataset.employeeAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.employeeId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.employeeValues || 'e30='));
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                        field.value = values[field.name] ?? '';
                    }
                });
            };

            modal.addEventListener('show.bs.modal', (event) => {
                if (!event.relatedTarget && form.dataset.hasErrors === '1') return;
                setMode(event.relatedTarget);
            });

            @if ($hasEmployeeErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

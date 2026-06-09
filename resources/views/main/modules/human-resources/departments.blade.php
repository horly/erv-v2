<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_departments') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalDepartments = $departments->total();
        $departmentPayload = fn ($department) => [
            'code' => $department->code,
            'name' => $department->name,
            'manager_employee_id' => $department->manager_employee_id,
            'description' => $department->description,
            'status' => $department->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'departments'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_departments') }}</h1>
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
                        <h1>{{ __('main.hr_departments') }}</h1>
                        <p>{{ __('main.hr_departments_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#departmentModal" data-department-mode="create">
                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                        {{ __('main.hr_new_department') }}
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
                        <strong id="visibleCount">{{ $departments->count() }}</strong>
                        /
                        <strong>{{ $totalDepartments }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-departments-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.code') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.manager') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.hr_employees') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departments as $department)
                                    <tr>
                                        <td>{{ ($departments->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>{{ $department->code ?: '-' }}</td>
                                        <td>
                                            <strong>{{ $department->name }}</strong>
                                            @if ($department->description)
                                                <small class="d-block text-muted">{{ $department->description }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $department->manager?->full_name ?? '-' }}</td>
                                        <td data-sort-value="{{ $department->employees_count }}">{{ $department->employees_count }}</td>
                                        <td><span class="status-pill hr-status-{{ $department->status }}">{{ __('main.'.$department->status) }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#departmentModal" data-department-mode="edit" data-department-action="{{ route('main.human-resources.departments.update', [$company, $site, $department]) }}" data-department-id="{{ $department->id }}" data-department-values="{{ base64_encode(json_encode($departmentPayload($department))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.human-resources.departments.destroy', [$company, $site, $department]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.hr_delete_department_title') }}" data-delete-text="{{ __('main.hr_delete_department_text', ['name' => $department->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.hr_no_departments') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($departments->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $departments->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $departments->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalDepartments }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($departments->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $departments->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($departments->getUrlRange(1, $departments->lastPage()) as $page => $url)
                                @if ($page === $departments->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($departments->hasMorePages())<a href="{{ $departments->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal hr-department-modal" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-department-form" method="POST" action="{{ $departmentFormAction }}" data-create-action="{{ route('main.human-resources.departments.store', [$company, $site]) }}" data-title-create="{{ __('main.hr_new_department') }}" data-title-edit="{{ __('main.hr_edit_department') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="departmentHttpMethod" value="PUT" @disabled(! $isEditingDepartment)>
                <input type="hidden" name="form_mode" id="departmentFormMode" value="{{ $isEditingDepartment ? 'edit' : 'create' }}">
                <input type="hidden" name="department_id" id="departmentId" value="{{ old('department_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="departmentModalLabel"><i class="bi bi-diagram-3" aria-hidden="true"></i>{{ $isEditingDepartment ? __('main.hr_edit_department') : __('main.hr_new_department') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="department_code" class="form-label">{{ __('main.code') }}</label>
                            <input id="department_code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="{{ __('main.hr_department_code_placeholder') }}" data-department-field data-default-value="">
                            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label for="department_name" class="form-label">{{ __('main.name') }} *</label>
                            <input id="department_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.hr_department_name_placeholder') }}" data-department-field data-default-value="">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label for="department_manager_employee_id" class="form-label">{{ __('main.manager') }}</label>
                            <select id="department_manager_employee_id" name="manager_employee_id" class="form-select @error('manager_employee_id') is-invalid @enderror" data-department-field data-default-value="">
                                <option value="">{{ __('main.hr_no_manager') }}</option>
                                @foreach ($managerOptions as $manager)
                                    <option value="{{ $manager->id }}" @selected((string) old('manager_employee_id') === (string) $manager->id)>{{ $manager->full_name }} - {{ $manager->employee_number }}</option>
                                @endforeach
                            </select>
                            @error('manager_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="department_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="department_status" name="status" class="form-select @error('status') is-invalid @enderror" data-department-field data-default-value="{{ \App\Models\HumanResourceDepartment::STATUS_ACTIVE }}">
                                <option value="{{ \App\Models\HumanResourceDepartment::STATUS_ACTIVE }}" @selected(old('status', \App\Models\HumanResourceDepartment::STATUS_ACTIVE) === \App\Models\HumanResourceDepartment::STATUS_ACTIVE)>{{ __('main.active') }}</option>
                                <option value="{{ \App\Models\HumanResourceDepartment::STATUS_INACTIVE }}" @selected(old('status') === \App\Models\HumanResourceDepartment::STATUS_INACTIVE)>{{ __('main.inactive') }}</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="department_description" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="department_description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('main.hr_department_description_placeholder') }}" data-department-field data-default-value="">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="departmentSubmit">{{ $isEditingDepartment ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('departmentModal');
            const form = modal?.querySelector('.hr-department-form');
            const title = document.getElementById('departmentModalLabel');
            const method = document.getElementById('departmentHttpMethod');
            const mode = document.getElementById('departmentFormMode');
            const id = document.getElementById('departmentId');
            const submit = document.getElementById('departmentSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-department-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.departmentMode === 'edit';
                form.action = isEdit ? button.dataset.departmentAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.departmentId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.departmentValues || 'e30='));
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                        field.value = values[field.name] ?? '';
                    }
                });
            };

            modal.addEventListener('show.bs.modal', (event) => setMode(event.relatedTarget));

            @if ($hasDepartmentErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

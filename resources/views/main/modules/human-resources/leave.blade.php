<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_leave') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalLeaves = $leaves->total();
        $leavePayload = fn ($leave) => [
            'human_resource_employee_id' => $leave->human_resource_employee_id,
            'reference' => $leave->reference,
            'type' => $leave->type,
            'status' => $leave->status,
            'start_date' => optional($leave->start_date)->format('Y-m-d'),
            'end_date' => optional($leave->end_date)->format('Y-m-d'),
            'reason' => $leave->reason,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'leave'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_leave') }}</h1>
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
                        <h1>{{ __('main.hr_leave') }}</h1>
                        <p>{{ __('main.hr_leave_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#leaveModal" data-leave-mode="create">
                        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                        {{ __('main.hr_new_leave') }}
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
                        <strong id="visibleCount">{{ $leaves->count() }}</strong>
                        /
                        <strong>{{ $totalLeaves }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-leave-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.hr_employee') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.hr_leave_days') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaves as $leave)
                                    <tr>
                                        <td>{{ $leave->reference }}</td>
                                        <td>
                                            <strong>{{ $leave->employee?->full_name ?? '-' }}</strong>
                                            @if ($leave->employee?->department)
                                                <small class="d-block text-muted">{{ $leave->employee->department->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $leaveTypes[$leave->type] ?? $leave->type }}</td>
                                        <td>{{ optional($leave->start_date)->format('d/m/Y') }} - {{ optional($leave->end_date)->format('d/m/Y') }}</td>
                                        <td class="text-end" data-sort-value="{{ $leave->days_count }}">{{ number_format((float) $leave->days_count, 2, ',', ' ') }}</td>
                                        <td><span class="status-pill hr-leave-status-{{ $leave->status }}">{{ $leaveStatuses[$leave->status] ?? $leave->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#leaveModal" data-leave-mode="edit" data-leave-action="{{ route('main.human-resources.leave.update', [$company, $site, $leave]) }}" data-leave-id="{{ $leave->id }}" data-leave-values="{{ base64_encode(json_encode($leavePayload($leave))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.human-resources.leave.destroy', [$company, $site, $leave]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.hr_delete_leave_title') }}" data-delete-text="{{ __('main.hr_delete_leave_text', ['reference' => $leave->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.hr_no_leave_requests') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($leaves->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $leaves->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $leaves->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalLeaves }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($leaves->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $leaves->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($leaves->getUrlRange(1, $leaves->lastPage()) as $page => $url)
                                @if ($page === $leaves->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($leaves->hasMorePages())<a href="{{ $leaves->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal hr-leave-modal" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-leave-form" method="POST" action="{{ $leaveFormAction }}" data-create-action="{{ route('main.human-resources.leave.store', [$company, $site]) }}" data-title-create="{{ __('main.hr_new_leave') }}" data-title-edit="{{ __('main.hr_edit_leave') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasLeaveErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="leaveHttpMethod" value="PUT" @disabled(! $isEditingLeave)>
                <input type="hidden" name="form_mode" id="leaveFormMode" value="{{ $isEditingLeave ? 'edit' : 'create' }}">
                <input type="hidden" name="leave_id" id="leaveId" value="{{ old('leave_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="leaveModalLabel"><i class="bi bi-calendar-check" aria-hidden="true"></i>{{ $isEditingLeave ? __('main.hr_edit_leave') : __('main.hr_new_leave') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="human_resource_employee_id" class="form-label">{{ __('main.hr_employee') }} *</label>
                                <select id="human_resource_employee_id" name="human_resource_employee_id" class="form-select @error('human_resource_employee_id') is-invalid @enderror" data-leave-field data-default-value="">
                                    <option value="">{{ __('main.select') }}</option>
                                    @foreach ($employeeOptions as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) old('human_resource_employee_id') === (string) $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                                    @endforeach
                                </select>
                                @error('human_resource_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.hr_leave_reference_placeholder') }}" data-leave-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-calendar-range" aria-hidden="true"></i> {{ __('main.hr_leave_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="leave_type" class="form-label">{{ __('main.type') }} *</label>
                                <select id="leave_type" name="type" class="form-select @error('type') is-invalid @enderror" data-leave-field data-default-value="{{ \App\Models\HumanResourceLeaveRequest::TYPE_ANNUAL }}">
                                    @foreach ($leaveTypes as $type => $label)
                                        <option value="{{ $type }}" @selected(old('type', \App\Models\HumanResourceLeaveRequest::TYPE_ANNUAL) === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="leave_status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="leave_status" name="status" class="form-select @error('status') is-invalid @enderror" data-leave-field data-default-value="{{ \App\Models\HumanResourceLeaveRequest::STATUS_PENDING }}">
                                    @foreach ($leaveStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', \App\Models\HumanResourceLeaveRequest::STATUS_PENDING) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">{{ __('main.start_date') }} *</label>
                                <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}" data-leave-field data-default-value="{{ now()->toDateString() }}">
                                @error('start_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">{{ __('main.end_date') }} *</label>
                                <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->toDateString()) }}" data-leave-field data-default-value="{{ now()->toDateString() }}">
                                @error('end_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="reason" class="form-label">{{ __('main.reason') }}</label>
                                <textarea id="reason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" placeholder="{{ __('main.hr_leave_reason_placeholder') }}" data-leave-field data-default-value="">{{ old('reason') }}</textarea>
                                @error('reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="leaveSubmit">{{ $isEditingLeave ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('leaveModal');
            const form = modal?.querySelector('.hr-leave-form');
            const title = document.getElementById('leaveModalLabel');
            const method = document.getElementById('leaveHttpMethod');
            const mode = document.getElementById('leaveFormMode');
            const id = document.getElementById('leaveId');
            const submit = document.getElementById('leaveSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-leave-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.leaveMode === 'edit';
                form.action = isEdit ? button.dataset.leaveAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.leaveId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.leaveValues || 'e30='));
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

            @if ($hasLeaveErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

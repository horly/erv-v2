<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_assignments') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalRecords = $records->total();
        $isEditingAssignment = old('form_mode') === 'edit' && old('record_id');
        $assignmentFormAction = $isEditingAssignment
            ? route('main.document-management.assignments.update', [$company, $site, old('record_id')])
            : '#';
        $recordOpenRoute = fn ($record) => match ($record->record_type) {
            \App\Models\DocumentManagementRecord::TYPE_INCOMING => route('main.document-management.incoming', [$company, $site]),
            \App\Models\DocumentManagementRecord::TYPE_OUTGOING => route('main.document-management.outgoing', [$company, $site]),
            \App\Models\DocumentManagementRecord::TYPE_INTERNAL => route('main.document-management.internal', [$company, $site]),
            default => route('main.document-management.dashboard', [$company, $site]),
        };
        $assignmentPayload = fn ($record) => [
            'assigned_to' => $record->assigned_to,
            'priority' => $record->priority,
            'status' => $record->status,
            'due_at' => optional($record->due_at)->format('Y-m-d'),
            'assignment_comment' => '',
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'assignments'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_assignments') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page document-management-page">
                <a class="back-link" href="{{ route('main.document-management.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.ged_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.ged_assignments') }}</h1>
                        <p>{{ __('main.ged_assignments_subtitle') }}</p>
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

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $records->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table ged-assignments-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.ged_document') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th>{{ __('main.ged_assignment') }}</th>
                                    <th>{{ __('main.status') }}</th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr>
                                        <td><strong>{{ $record->reference }}</strong></td>
                                        <td class="ged-mail-cell">
                                            <strong>{{ $record->subject }}</strong>
                                            <span>{{ $typeLabels[$record->record_type] ?? $record->record_type }} &middot; {{ $record->folder?->name ?? __('main.ged_without_folder') }}</span>
                                            <small>
                                                @if ($record->record_type === \App\Models\DocumentManagementRecord::TYPE_OUTGOING)
                                                    {{ __('main.ged_recipient') }} : {{ $record->recipient ?? '-' }}
                                                @else
                                                    {{ __('main.ged_sender') }} : {{ $record->sender ?? '-' }}
                                                @endif
                                            </small>
                                        </td>
                                        <td class="ged-tracking-cell">
                                            <strong>{{ $record->assignee?->name ?? __('main.ged_unassigned') }}</strong>
                                            <span>{{ __('main.ged_due_at') }} : {{ $record->due_at?->format('d/m/Y') ?? '-' }}</span>
                                            <span class="status-pill ged-priority-{{ $record->priority }}">{{ $priorityLabels[$record->priority] ?? $record->priority }}</span>
                                        </td>
                                        <td><span class="status-pill ged-status-{{ $record->status }}">{{ $statusLabels[$record->status] ?? $record->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ $recordOpenRoute($record) }}" aria-label="{{ __('main.ged_open_related_document') }}">
                                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                                </a>
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#assignmentModal" data-assignment-action="{{ route('main.document-management.assignments.update', [$company, $site, $record]) }}" data-assignment-id="{{ $record->id }}" data-assignment-title="{{ $record->reference }} - {{ $record->subject }}" data-assignment-values="{{ base64_encode(json_encode($assignmentPayload($record))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-person-check" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.ged_no_assignments') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="5">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($records->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $records->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $records->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($records->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $records->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($records->getUrlRange(1, $records->lastPage()) as $page => $url)
                                @if ($page === $records->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal ged-assignment-modal" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-assignment-form" method="POST" action="{{ $assignmentFormAction }}" data-title="{{ __('main.ged_edit_assignment') }}" data-submit="{{ __('admin.update') }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="form_mode" id="assignmentFormMode" value="edit">
                <input type="hidden" name="record_id" id="assignmentId" value="{{ old('record_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="assignmentModalLabel"><i class="bi bi-person-check" aria-hidden="true"></i>{{ __('main.ged_edit_assignment') }}</h2>
                    <p class="modal-helper-text" id="assignmentDocumentTitle">{{ __('main.ged_assignment_document_placeholder') }}</p>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="assigned_to">{{ __('main.ged_assigned_to') }}</label>
                                <select id="assigned_to" name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" data-assignment-field data-default-value="">
                                    <option value="">{{ __('main.ged_unassigned') }}</option>
                                    @foreach ($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected((string) old('assigned_to') === (string) $assignee->id)>{{ $assignee->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="due_at">{{ __('main.ged_due_at') }}</label>
                                <input id="due_at" name="due_at" type="date" class="form-control @error('due_at') is-invalid @enderror" value="{{ old('due_at') }}" data-assignment-field data-default-value="">
                                @error('due_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="priority">{{ __('main.priority') }} *</label>
                                <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror" data-assignment-field data-default-value="{{ \App\Models\DocumentManagementRecord::PRIORITY_NORMAL }}">
                                    @foreach ($priorityLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('priority', \App\Models\DocumentManagementRecord::PRIORITY_NORMAL) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="status">{{ __('main.status') }} *</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-assignment-field data-default-value="{{ \App\Models\DocumentManagementRecord::STATUS_ASSIGNED }}">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', \App\Models\DocumentManagementRecord::STATUS_ASSIGNED) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="assignment_comment">{{ __('main.ged_assignment_comment') }}</label>
                                <textarea id="assignment_comment" name="assignment_comment" class="form-control @error('assignment_comment') is-invalid @enderror" rows="4" placeholder="{{ __('main.ged_assignment_comment_placeholder') }}" data-assignment-field data-default-value="">{{ old('assignment_comment') }}</textarea>
                                @error('assignment_comment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action">{{ __('admin.update') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('assignmentModal');
            const form = modal?.querySelector('.ged-assignment-form');
            const id = document.getElementById('assignmentId');
            const title = document.getElementById('assignmentDocumentTitle');
            const fields = form ? Array.from(form.querySelectorAll('[data-assignment-field]')) : [];

            if (!modal || !form) return;

            const setAssignment = (button) => {
                if (!button) return;

                form.action = button.dataset.assignmentAction;
                id.value = button.dataset.assignmentId || '';
                title.textContent = button.dataset.assignmentTitle || '';

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                const values = JSON.parse(atob(button.dataset.assignmentValues || 'e30='));
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                        field.value = values[field.name] ?? '';
                    }
                });
            };

            modal.addEventListener('show.bs.modal', (event) => {
                if (!event.relatedTarget && form.dataset.hasErrors === '1') return;
                setAssignment(event.relatedTarget);
            });

            @if ($errors->any())
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

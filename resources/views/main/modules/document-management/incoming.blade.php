<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_incoming_mail') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalRecords = $records->total();
        $isEditingIncoming = old('form_mode') === 'edit' && old('record_id');
        $incomingFormAction = $isEditingIncoming
            ? route('main.document-management.incoming.update', [$company, $site, old('record_id')])
            : route('main.document-management.incoming.store', [$company, $site]);
        $incomingPayload = fn ($record) => [
            'document_management_folder_id' => $record->document_management_folder_id,
            'assigned_to' => $record->assigned_to,
            'subject' => $record->subject,
            'sender' => $record->sender,
            'category' => $record->category,
            'priority' => $record->priority,
            'status' => $record->status,
            'received_at' => optional($record->received_at)->format('Y-m-d'),
            'due_at' => optional($record->due_at)->format('Y-m-d'),
            'summary' => $record->summary,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'incoming'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_incoming_mail') }}</h1>
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
                        <h1>{{ __('main.ged_incoming_mail') }}</h1>
                        <p>{{ __('main.ged_incoming_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#incomingMailModal" data-incoming-mode="create">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                        {{ __('main.ged_new_incoming_mail') }}
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
                        <strong id="visibleCount">{{ $records->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table ged-incoming-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.ged_subject') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th>{{ __('main.ged_tracking') }}</th>
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
                                            <span>{{ $record->sender }} &middot; {{ $record->received_at?->format('d/m/Y') ?? '-' }}</span>
                                            <small>{{ $record->folder?->name ?? __('main.ged_without_folder') }} @if ($record->category) &middot; {{ $record->category }} @endif</small>
                                            @if ($record->file_path)
                                                <a class="ged-file-link" href="{{ public_storage_url($record->file_path) }}" target="_blank" rel="noopener">
                                                    <i class="bi bi-paperclip" aria-hidden="true"></i>
                                                    {{ __('main.ged_attachment') }}
                                                </a>
                                            @endif
                                        </td>
                                        <td class="ged-tracking-cell">
                                            <strong>{{ $record->assignee?->name ?? __('main.ged_unassigned') }}</strong>
                                            <span>{{ __('main.ged_due_at') }} : {{ $record->due_at?->format('d/m/Y') ?? '-' }}</span>
                                            <span class="status-pill ged-priority-{{ $record->priority }}">{{ $priorityLabels[$record->priority] ?? $record->priority }}</span>
                                        </td>
                                        <td><span class="status-pill ged-status-{{ $record->status }}">{{ $statusLabels[$record->status] ?? $record->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#incomingMailModal" data-incoming-mode="edit" data-incoming-action="{{ route('main.document-management.incoming.update', [$company, $site, $record]) }}" data-incoming-id="{{ $record->id }}" data-incoming-values="{{ base64_encode(json_encode($incomingPayload($record))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.document-management.incoming.destroy', [$company, $site, $record]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.ged_delete_incoming_title') }}" data-delete-text="{{ __('main.ged_delete_incoming_text', ['reference' => $record->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.ged_no_incoming_mail') }}</td></tr>
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

    <div class="modal fade subscription-modal accounting-proforma-modal ged-incoming-modal" id="incomingMailModal" tabindex="-1" aria-labelledby="incomingMailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-incoming-form" method="POST" action="{{ $incomingFormAction }}" enctype="multipart/form-data" data-create-action="{{ route('main.document-management.incoming.store', [$company, $site]) }}" data-title-create="{{ __('main.ged_new_incoming_mail') }}" data-title-edit="{{ __('main.ged_edit_incoming_mail') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="incomingHttpMethod" value="PUT" @disabled(! $isEditingIncoming)>
                <input type="hidden" name="form_mode" id="incomingFormMode" value="{{ $isEditingIncoming ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="incomingId" value="{{ old('record_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="incomingMailModalLabel"><i class="bi bi-inbox" aria-hidden="true"></i>{{ $isEditingIncoming ? __('main.ged_edit_incoming_mail') : __('main.ged_new_incoming_mail') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="subject">{{ __('main.ged_subject') }} *</label>
                                <input id="subject" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="{{ __('main.ged_subject_placeholder') }}" data-incoming-field data-default-value="">
                                @error('subject')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="received_at">{{ __('main.ged_received_at') }} *</label>
                                <input id="received_at" name="received_at" type="date" class="form-control @error('received_at') is-invalid @enderror" value="{{ old('received_at', now()->format('Y-m-d')) }}" data-incoming-field data-default-value="{{ now()->format('Y-m-d') }}">
                                @error('received_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sender">{{ __('main.ged_sender') }} *</label>
                                <input id="sender" name="sender" class="form-control @error('sender') is-invalid @enderror" value="{{ old('sender') }}" placeholder="{{ __('main.ged_sender_placeholder') }}" data-incoming-field data-default-value="">
                                @error('sender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category">{{ __('main.ged_category') }}</label>
                                <input id="category" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" placeholder="{{ __('main.ged_category_placeholder') }}" data-incoming-field data-default-value="">
                                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="document_management_folder_id">{{ __('main.ged_folder') }}</label>
                                <select id="document_management_folder_id" name="document_management_folder_id" class="form-select @error('document_management_folder_id') is-invalid @enderror" data-incoming-field data-default-value="">
                                    <option value="">{{ __('main.ged_select_folder') }}</option>
                                    @foreach ($folders as $folder)
                                        <option value="{{ $folder->id }}" @selected((string) old('document_management_folder_id') === (string) $folder->id)>{{ $folder->name }}</option>
                                    @endforeach
                                </select>
                                @error('document_management_folder_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="assigned_to">{{ __('main.ged_assigned_to') }}</label>
                                <select id="assigned_to" name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" data-incoming-field data-default-value="">
                                    <option value="">{{ __('main.ged_select_assignee') }}</option>
                                    @foreach ($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected((string) old('assigned_to') === (string) $assignee->id)>{{ $assignee->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="due_at">{{ __('main.ged_due_at') }}</label>
                                <input id="due_at" name="due_at" type="date" class="form-control @error('due_at') is-invalid @enderror" value="{{ old('due_at') }}" data-incoming-field data-default-value="">
                                @error('due_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="priority">{{ __('main.priority') }} *</label>
                                <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror" data-incoming-field data-default-value="{{ \App\Models\DocumentManagementRecord::PRIORITY_NORMAL }}">
                                    @foreach ($priorityLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('priority', \App\Models\DocumentManagementRecord::PRIORITY_NORMAL) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="incoming_status">{{ __('main.status') }} *</label>
                                <select id="incoming_status" name="status" class="form-select @error('status') is-invalid @enderror" data-incoming-field data-default-value="{{ \App\Models\DocumentManagementRecord::STATUS_REGISTERED }}">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', \App\Models\DocumentManagementRecord::STATUS_REGISTERED) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="attachment">{{ __('main.ged_attachment') }}</label>
                                <input id="attachment" name="attachment" type="file" class="form-control @error('attachment') is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                <small class="form-hint">{{ __('main.ged_attachment_hint') }}</small>
                                @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="summary">{{ __('main.ged_summary') }}</label>
                                <textarea id="summary" name="summary" class="form-control @error('summary') is-invalid @enderror" rows="4" placeholder="{{ __('main.ged_summary_placeholder') }}" data-incoming-field data-default-value="">{{ old('summary') }}</textarea>
                                @error('summary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="incomingSubmit">{{ $isEditingIncoming ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('incomingMailModal');
            const form = modal?.querySelector('.ged-incoming-form');
            const title = document.getElementById('incomingMailModalLabel');
            const method = document.getElementById('incomingHttpMethod');
            const mode = document.getElementById('incomingFormMode');
            const id = document.getElementById('incomingId');
            const submit = document.getElementById('incomingSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-incoming-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.incomingMode === 'edit';
                form.action = isEdit ? button.dataset.incomingAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.incomingId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.incomingValues || 'e30='));
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

            @if ($errors->any())
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

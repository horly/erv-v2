<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_outgoing_mail') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalRecords = $records->total();
        $isEditingOutgoing = old('form_mode') === 'edit' && old('record_id');
        $outgoingFormAction = $isEditingOutgoing
            ? route('main.document-management.outgoing.update', [$company, $site, old('record_id')])
            : route('main.document-management.outgoing.store', [$company, $site]);
        $outgoingPayload = fn ($record) => [
            'document_management_folder_id' => $record->document_management_folder_id,
            'assigned_to' => $record->assigned_to,
            'subject' => $record->subject,
            'recipient' => $record->recipient,
            'sender' => $record->sender,
            'category' => $record->category,
            'priority' => $record->priority,
            'status' => $record->status,
            'sent_at' => optional($record->sent_at)->format('Y-m-d'),
            'due_at' => optional($record->due_at)->format('Y-m-d'),
            'summary' => $record->summary,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'outgoing'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_outgoing_mail') }}</h1>
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
                        <h1>{{ __('main.ged_outgoing_mail') }}</h1>
                        <p>{{ __('main.ged_outgoing_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#outgoingMailModal" data-outgoing-mode="create">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        {{ __('main.ged_new_outgoing_mail') }}
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
                        <table class="company-table ged-outgoing-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.ged_subject') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th>{{ __('main.ged_sending') }}</th>
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
                                            <span>{{ __('main.ged_recipient') }} : {{ $record->recipient }}</span>
                                            <small>{{ $record->folder?->name ?? __('main.ged_without_folder') }} @if ($record->category) &middot; {{ $record->category }} @endif</small>
                                            @if ($record->file_path)
                                                <a class="ged-file-link" href="{{ public_storage_url($record->file_path) }}" target="_blank" rel="noopener">
                                                    <i class="bi bi-paperclip" aria-hidden="true"></i>
                                                    {{ __('main.ged_attachment') }}
                                                </a>
                                            @endif
                                        </td>
                                        <td class="ged-tracking-cell">
                                            <strong>{{ $record->sender ?: $site->name }}</strong>
                                            <span>{{ __('main.ged_sent_at') }} : {{ $record->sent_at?->format('d/m/Y') ?? '-' }}</span>
                                            <span>{{ __('main.ged_assigned_to') }} : {{ $record->assignee?->name ?? __('main.ged_unassigned') }}</span>
                                            <span class="status-pill ged-priority-{{ $record->priority }}">{{ $priorityLabels[$record->priority] ?? $record->priority }}</span>
                                        </td>
                                        <td><span class="status-pill ged-status-{{ $record->status }}">{{ $statusLabels[$record->status] ?? $record->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#outgoingMailModal" data-outgoing-mode="edit" data-outgoing-action="{{ route('main.document-management.outgoing.update', [$company, $site, $record]) }}" data-outgoing-id="{{ $record->id }}" data-outgoing-values="{{ base64_encode(json_encode($outgoingPayload($record))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.document-management.outgoing.destroy', [$company, $site, $record]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.ged_delete_outgoing_title') }}" data-delete-text="{{ __('main.ged_delete_outgoing_text', ['reference' => $record->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.ged_no_outgoing_mail') }}</td></tr>
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

    <div class="modal fade subscription-modal accounting-proforma-modal ged-outgoing-modal" id="outgoingMailModal" tabindex="-1" aria-labelledby="outgoingMailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-outgoing-form" method="POST" action="{{ $outgoingFormAction }}" enctype="multipart/form-data" data-create-action="{{ route('main.document-management.outgoing.store', [$company, $site]) }}" data-title-create="{{ __('main.ged_new_outgoing_mail') }}" data-title-edit="{{ __('main.ged_edit_outgoing_mail') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="outgoingHttpMethod" value="PUT" @disabled(! $isEditingOutgoing)>
                <input type="hidden" name="form_mode" id="outgoingFormMode" value="{{ $isEditingOutgoing ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="outgoingId" value="{{ old('record_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="outgoingMailModalLabel"><i class="bi bi-send" aria-hidden="true"></i>{{ $isEditingOutgoing ? __('main.ged_edit_outgoing_mail') : __('main.ged_new_outgoing_mail') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="subject">{{ __('main.ged_subject') }} *</label>
                                <input id="subject" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="{{ __('main.ged_outgoing_subject_placeholder') }}" data-outgoing-field data-default-value="">
                                @error('subject')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sent_at">{{ __('main.ged_sent_at') }}</label>
                                <input id="sent_at" name="sent_at" type="date" class="form-control @error('sent_at') is-invalid @enderror" value="{{ old('sent_at') }}" data-outgoing-field data-default-value="">
                                @error('sent_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="recipient">{{ __('main.ged_recipient') }} *</label>
                                <input id="recipient" name="recipient" class="form-control @error('recipient') is-invalid @enderror" value="{{ old('recipient') }}" placeholder="{{ __('main.ged_recipient_placeholder') }}" data-outgoing-field data-default-value="">
                                @error('recipient')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sender">{{ __('main.ged_requesting_service') }}</label>
                                <input id="sender" name="sender" class="form-control @error('sender') is-invalid @enderror" value="{{ old('sender', $site->name) }}" placeholder="{{ $site->name }}" data-outgoing-field data-default-value="{{ $site->name }}">
                                @error('sender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="document_management_folder_id">{{ __('main.ged_folder') }}</label>
                                <select id="document_management_folder_id" name="document_management_folder_id" class="form-select @error('document_management_folder_id') is-invalid @enderror" data-outgoing-field data-default-value="">
                                    <option value="">{{ __('main.ged_select_folder') }}</option>
                                    @foreach ($folders as $folder)
                                        <option value="{{ $folder->id }}" @selected((string) old('document_management_folder_id') === (string) $folder->id)>{{ $folder->name }}</option>
                                    @endforeach
                                </select>
                                @error('document_management_folder_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="assigned_to">{{ __('main.ged_responsible_internal') }}</label>
                                <select id="assigned_to" name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" data-outgoing-field data-default-value="">
                                    <option value="">{{ __('main.ged_select_assignee') }}</option>
                                    @foreach ($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected((string) old('assigned_to') === (string) $assignee->id)>{{ $assignee->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="due_at">{{ __('main.ged_due_at') }}</label>
                                <input id="due_at" name="due_at" type="date" class="form-control @error('due_at') is-invalid @enderror" value="{{ old('due_at') }}" data-outgoing-field data-default-value="">
                                @error('due_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="category">{{ __('main.ged_sending_method') }}</label>
                                <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" data-outgoing-field data-default-value="">
                                    <option value="">{{ __('main.select') }}</option>
                                    @foreach (['Remise physique', 'Email', 'Coursier', 'Transporteur', 'Autre'] as $method)
                                        <option value="{{ $method }}" @selected(old('category') === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="priority">{{ __('main.priority') }} *</label>
                                <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror" data-outgoing-field data-default-value="{{ \App\Models\DocumentManagementRecord::PRIORITY_NORMAL }}">
                                    @foreach ($priorityLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('priority', \App\Models\DocumentManagementRecord::PRIORITY_NORMAL) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="outgoing_status">{{ __('main.status') }} *</label>
                                <select id="outgoing_status" name="status" class="form-select @error('status') is-invalid @enderror" data-outgoing-field data-default-value="{{ \App\Models\DocumentManagementRecord::STATUS_REGISTERED }}">
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
                                <textarea id="summary" name="summary" class="form-control @error('summary') is-invalid @enderror" rows="4" placeholder="{{ __('main.ged_outgoing_summary_placeholder') }}" data-outgoing-field data-default-value="">{{ old('summary') }}</textarea>
                                @error('summary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="outgoingSubmit">{{ $isEditingOutgoing ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('outgoingMailModal');
            const form = modal?.querySelector('.ged-outgoing-form');
            const title = document.getElementById('outgoingMailModalLabel');
            const method = document.getElementById('outgoingHttpMethod');
            const mode = document.getElementById('outgoingFormMode');
            const id = document.getElementById('outgoingId');
            const submit = document.getElementById('outgoingSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-outgoing-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.outgoingMode === 'edit';
                form.action = isEdit ? button.dataset.outgoingAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.outgoingId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.outgoingValues || 'e30='));
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

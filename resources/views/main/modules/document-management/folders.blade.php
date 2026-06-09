<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_folders') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalFolders = $folders->total();
        $isEditingFolder = old('form_mode') === 'edit' && old('folder_id');
        $folderFormAction = $isEditingFolder
            ? route('main.document-management.folders.update', [$company, $site, old('folder_id')])
            : route('main.document-management.folders.store', [$company, $site]);
        $folderPayload = fn ($folder) => [
            'name' => $folder->name,
            'category' => $folder->category,
            'status' => $folder->status,
            'description' => $folder->description,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'folders'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_folders') }}</h1>
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
                        <h1>{{ __('main.ged_folders') }}</h1>
                        <p>{{ __('main.ged_folders_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#folderModal" data-folder-mode="create">
                        <i class="bi bi-folder-plus" aria-hidden="true"></i>
                        {{ __('main.ged_new_folder') }}
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
                    <form class="search-box" method="GET" action="{{ route('main.document-management.folders', [$company, $site]) }}">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.ged_folder_search_placeholder') }}" autocomplete="off">
                    </form>
                    <span class="row-count">
                        <strong>{{ $folders->count() }}</strong>
                        /
                        <strong>{{ $totalFolders }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table ged-folders-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.ged_folder') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.ged_category') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="number">{{ __('main.ged_documents_count') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5" data-sort-type="date">{{ __('main.ged_last_activity') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($folders as $folder)
                                    <tr>
                                        <td><strong>{{ $folder->reference }}</strong></td>
                                        <td class="ged-mail-cell">
                                            <strong>{{ $folder->name }}</strong>
                                            <span>{{ $folder->creator?->name ?? __('main.system') }}</span>
                                            @if ($folder->description)
                                                <small>{{ $folder->description }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $folder->category ?: '-' }}</td>
                                        <td data-sort-value="{{ $folder->records_count }}"><strong>{{ $folder->records_count }}</strong></td>
                                        <td><span class="status-pill ged-folder-status-{{ $folder->status }}">{{ $folderStatusLabels[$folder->status] ?? $folder->status }}</span></td>
                                        <td data-sort-value="{{ $folder->records_max_updated_at ? \Illuminate\Support\Carbon::parse($folder->records_max_updated_at)->format('Y-m-d H:i:s') : '' }}">{{ $folder->records_max_updated_at ? \Illuminate\Support\Carbon::parse($folder->records_max_updated_at)->format('d/m/Y H:i') : '-' }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ route('main.document-management.folders.show', [$company, $site, $folder]) }}" aria-label="{{ __('main.ged_open_folder') }}">
                                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                                </a>
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#folderModal" data-folder-mode="edit" data-folder-action="{{ route('main.document-management.folders.update', [$company, $site, $folder]) }}" data-folder-id="{{ $folder->id }}" data-folder-values="{{ base64_encode(json_encode($folderPayload($folder))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.document-management.folders.destroy', [$company, $site, $folder]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.ged_delete_folder_title') }}" data-delete-text="{{ __('main.ged_delete_folder_text', ['reference' => $folder->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.ged_no_folders') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($totalFolders > 0)
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $folders->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $folders->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalFolders }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($folders->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $folders->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($folders->getUrlRange(1, $folders->lastPage()) as $page => $url)
                                @if ($page === $folders->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($folders->hasMorePages())<a href="{{ $folders->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal ged-folder-modal" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-folder-form" method="POST" action="{{ $folderFormAction }}" data-create-action="{{ route('main.document-management.folders.store', [$company, $site]) }}" data-title-create="{{ __('main.ged_new_folder') }}" data-title-edit="{{ __('main.ged_edit_folder') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="folderHttpMethod" value="PUT" @disabled(! $isEditingFolder)>
                <input type="hidden" name="form_mode" id="folderFormMode" value="{{ $isEditingFolder ? 'edit' : 'create' }}">
                <input type="hidden" name="folder_id" id="folderId" value="{{ old('folder_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="folderModalLabel"><i class="bi bi-folder-plus" aria-hidden="true"></i>{{ $isEditingFolder ? __('main.ged_edit_folder') : __('main.ged_new_folder') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="name">{{ __('main.ged_folder_name') }} *</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.ged_folder_name_placeholder') }}" data-folder-field data-default-value="">
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">{{ __('main.status') }} *</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-folder-field data-default-value="{{ \App\Models\DocumentManagementFolder::STATUS_ACTIVE }}">
                                    @foreach ($folderStatusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', \App\Models\DocumentManagementFolder::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="category">{{ __('main.ged_category') }}</label>
                                <input id="category" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" placeholder="{{ __('main.ged_folder_category_placeholder') }}" data-folder-field data-default-value="">
                                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">{{ __('main.description') }}</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="{{ __('main.ged_folder_description_placeholder') }}" data-folder-field data-default-value="">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="folderSubmit">{{ $isEditingFolder ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('folderModal');
            const form = modal?.querySelector('.ged-folder-form');
            const title = document.getElementById('folderModalLabel');
            const method = document.getElementById('folderHttpMethod');
            const mode = document.getElementById('folderFormMode');
            const id = document.getElementById('folderId');
            const submit = document.getElementById('folderSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-folder-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.folderMode === 'edit';
                form.action = isEdit ? button.dataset.folderAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.folderId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.folderValues || 'e30='));
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

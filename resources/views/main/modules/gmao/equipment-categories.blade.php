<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_equipment_categories') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    @php
        $hasErrors = $errors->any() && old('gmao_form') === 'equipment_category';
        $isEditing = $hasErrors && old('form_mode') === 'edit' && old('record_id');
        $formAction = $isEditing
            ? route('main.gmao.equipment-categories.update', [$company, $site, old('record_id')])
            : route('main.gmao.equipment-categories.store', [$company, $site]);
        $categoryPayload = fn ($category) => [
            'reference' => $category->reference,
            'code' => $category->code,
            'name' => $category->name,
            'family' => $category->family,
            'default_criticality' => $category->default_criticality,
            'status' => $category->status,
            'description' => $category->description,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar')

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.gmao_equipment_categories') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page gmao-page">
                <a class="back-link" href="{{ route('main.gmao.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.gmao_dashboard') }}
                </a>

                <section class="page-heading">
                    <div class="page-heading-with-icon">
                        <span class="module-heading-icon module-gmao"><i class="bi bi-tags" aria-hidden="true"></i></span>
                        <div>
                            <h1>{{ __('main.gmao_equipment_categories') }}</h1>
                            <p>{{ __('main.gmao_equipment_categories_subtitle') }}</p>
                        </div>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#gmaoEquipmentCategoryModal" data-category-mode="create">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        {{ __('main.gmao_new_equipment_category') }}
                    </button>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-exclamation-triangle' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <form class="search-box" method="GET">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.gmao_search_equipment_categories') }}" autocomplete="off">
                    </form>
                    <span class="row-count">
                        <strong>{{ $categories->count() }}</strong>
                        /
                        <strong>{{ $categories->total() }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.category') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.gmao_family') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.gmao_default_criticality') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.gmao_equipment') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    <tr>
                                        <td><strong>{{ $category->reference }}</strong></td>
                                        <td>
                                            <strong>{{ $category->name }}</strong>
                                            <small class="d-block text-muted">{{ $category->code ?: '-' }}</small>
                                            @if ($category->description)
                                                <small class="d-block text-muted">{{ $category->description }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $category->family ?: '-' }}</td>
                                        <td>{{ $criticalityLabels[$category->default_criticality] ?? $category->default_criticality }}</td>
                                        <td><strong>{{ $category->equipment_count }}</strong></td>
                                        <td><span class="status-pill gmao-status-{{ $category->status }}">{{ $statusLabels[$category->status] ?? $category->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#gmaoEquipmentCategoryModal" data-category-mode="edit" data-category-id="{{ $category->id }}" data-category-action="{{ route('main.gmao.equipment-categories.update', [$company, $site, $category]) }}" data-category-values="{{ base64_encode(json_encode($categoryPayload($category))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.gmao.equipment-categories.destroy', [$company, $site, $category]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.gmao_equipment_category_delete_title') }}" data-delete-text="{{ __('main.gmao_equipment_category_delete_text', ['reference' => $category->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.gmao_no_equipment_categories') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($categories->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $categories->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $categories->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $categories->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($categories->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $categories->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($categories->getUrlRange(1, $categories->lastPage()) as $page => $url)
                                @if ($page === $categories->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($categories->hasMorePages())<a href="{{ $categories->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal gmao-equipment-modal" id="gmaoEquipmentCategoryModal" tabindex="-1" aria-labelledby="gmaoEquipmentCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form gmao-equipment-category-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.gmao.equipment-categories.store', [$company, $site]) }}" data-title-create="{{ __('main.gmao_new_equipment_category') }}" data-title-edit="{{ __('main.gmao_edit_equipment_category') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="categoryHttpMethod" value="PUT" @disabled(! $isEditing)>
                <input type="hidden" name="gmao_form" value="equipment_category">
                <input type="hidden" name="form_mode" id="categoryFormMode" value="{{ $isEditing ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="categoryRecordId" value="{{ old('record_id') }}">

                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="gmaoEquipmentCategoryModalLabel"><i class="bi bi-tags" aria-hidden="true"></i>{{ $isEditing ? __('main.gmao_edit_equipment_category') : __('main.gmao_new_equipment_category') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.automatic_if_empty') }}" data-category-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="code" class="form-label">{{ __('main.code') }}</label>
                                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Ex. ENERGIE" data-category-field data-default-value="">
                                @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-category-field data-default-value="{{ \App\Models\GmaoEquipmentCategory::STATUS_ACTIVE }}">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', \App\Models\GmaoEquipmentCategory::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="name" class="form-label">{{ __('main.name') }} *</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.gmao_equipment_category_name_placeholder') }}" data-category-field data-default-value="">
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="default_criticality" class="form-label">{{ __('main.gmao_default_criticality') }} *</label>
                                <select id="default_criticality" name="default_criticality" class="form-select @error('default_criticality') is-invalid @enderror" data-category-field data-default-value="medium">
                                    @foreach ($criticalityLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('default_criticality', 'medium') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('default_criticality')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="family" class="form-label">{{ __('main.gmao_family') }}</label>
                                <input id="family" name="family" class="form-control @error('family') is-invalid @enderror" value="{{ old('family') }}" placeholder="{{ __('main.gmao_equipment_category_family_placeholder') }}" data-category-field data-default-value="">
                                @error('family')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">{{ __('main.description') }}</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('main.gmao_equipment_category_description_placeholder') }}" data-category-field data-default-value="">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="modal-submit" id="categorySubmit">{{ $isEditing ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('gmaoEquipmentCategoryModal');
            const form = modal?.querySelector('.gmao-equipment-category-form');
            const title = document.getElementById('gmaoEquipmentCategoryModalLabel');
            const method = document.getElementById('categoryHttpMethod');
            const mode = document.getElementById('categoryFormMode');
            const recordId = document.getElementById('categoryRecordId');
            const submit = document.getElementById('categorySubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-category-field]')) : [];

            if (!modal || !form) return;

            const resetFields = () => {
                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });
            };

            const applyValues = (values) => {
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                        field.value = values[field.name] ?? '';
                    }
                });
            };

            modal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const isEdit = button?.dataset.categoryMode === 'edit';

                if (!button && form.dataset.hasErrors === '1') return;

                form.action = isEdit ? button.dataset.categoryAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                recordId.value = isEdit ? button.dataset.categoryId : '';
                method.disabled = !isEdit;
                resetFields();

                if (isEdit) {
                    applyValues(JSON.parse(atob(button.dataset.categoryValues || 'e30=')));
                }
            });

            @if ($hasErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

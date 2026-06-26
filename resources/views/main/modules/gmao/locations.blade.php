<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_locations') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    @php
        $hasErrors = $errors->any() && old('gmao_form') === 'location';
        $isEditing = $hasErrors && old('form_mode') === 'edit' && old('record_id');
        $formAction = $isEditing
            ? route('main.gmao.locations.update', [$company, $site, old('record_id')])
            : route('main.gmao.locations.store', [$company, $site]);
        $locationPayload = fn ($location) => [
            'parent_id' => $location->parent_id,
            'reference' => $location->reference,
            'name' => $location->name,
            'type' => $location->type,
            'building' => $location->building,
            'floor' => $location->floor,
            'description' => $location->description,
            'status' => $location->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar')

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.gmao_locations') }}</h1>
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
                        <span class="module-heading-icon module-gmao"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                        <div>
                            <h1>{{ __('main.gmao_locations') }}</h1>
                            <p>{{ __('main.gmao_locations_subtitle') }}</p>
                        </div>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#gmaoLocationModal" data-location-mode="create">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        {{ __('main.gmao_new_location') }}
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
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.gmao_search_locations') }}" autocomplete="off">
                    </form>
                    <span class="row-count"><strong>{{ $locations->count() }}</strong> / <strong>{{ $locations->total() }}</strong> {{ __('admin.rows') }}</span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table gmao-locations-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.gmao_location') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.gmao_physical_path') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.gmao_equipment') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($locations as $location)
                                    <tr>
                                        <td><strong>{{ $location->reference }}</strong></td>
                                        <td>
                                            <div class="archive-location-cell" style="--archive-location-depth: {{ $depthResolver($location) }}">
                                                <span class="archive-tree-line" aria-hidden="true"></span>
                                                <span>
                                                    <strong>{{ $location->name }}</strong>
                                                    <small>{{ $location->description ?: '-' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $typeLabels[$location->type] ?? $location->type }}</td>
                                        <td>
                                            <strong>{{ $pathResolver($location) }}</strong>
                                            <small>{{ collect([$location->building, $location->floor])->filter()->implode(' / ') ?: '-' }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $location->equipment_count }}</strong>
                                            <small>{{ $location->children_count }} {{ __('main.gmao_child_locations') }}</small>
                                        </td>
                                        <td><span class="status-pill gmao-status-{{ $location->status }}">{{ $statusLabels[$location->status] ?? $location->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#gmaoLocationModal" data-location-mode="edit" data-location-id="{{ $location->id }}" data-location-action="{{ route('main.gmao.locations.update', [$company, $site, $location]) }}" data-location-values="{{ base64_encode(json_encode($locationPayload($location))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.gmao.locations.destroy', [$company, $site, $location]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.gmao_location_delete_title') }}" data-delete-text="{{ __('main.gmao_location_delete_text', ['reference' => $location->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.gmao_no_locations') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($locations->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $locations->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $locations->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $locations->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($locations->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $locations->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($locations->getUrlRange(1, $locations->lastPage()) as $page => $url)
                                @if ($page === $locations->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($locations->hasMorePages())<a href="{{ $locations->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal gmao-equipment-modal" id="gmaoLocationModal" tabindex="-1" aria-labelledby="gmaoLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form gmao-location-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.gmao.locations.store', [$company, $site]) }}" data-title-create="{{ __('main.gmao_new_location') }}" data-title-edit="{{ __('main.gmao_edit_location') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="locationHttpMethod" value="PUT" @disabled(! $isEditing)>
                <input type="hidden" name="gmao_form" value="location">
                <input type="hidden" name="form_mode" id="locationFormMode" value="{{ $isEditing ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="locationRecordId" value="{{ old('record_id') }}">

                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="gmaoLocationModalLabel"><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $isEditing ? __('main.gmao_edit_location') : __('main.gmao_new_location') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.automatic_if_empty') }}" data-location-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="name" class="form-label">{{ __('main.name') }} *</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.gmao_location_name_placeholder') }}" data-location-field data-default-value="">
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">{{ __('main.type') }} *</label>
                                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" data-location-field data-default-value="room">
                                    @foreach ($typeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', 'room') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="parent_id" class="form-label">{{ __('main.gmao_parent_location') }}</label>
                                <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror" data-location-field data-default-value="">
                                    <option value="" data-location-type="root">{{ __('main.gmao_root_location') }}</option>
                                    @foreach ($parentOptions as $option)
                                        <option value="{{ $option->id }}" data-location-type="{{ $option->type }}" @selected((string) old('parent_id') === (string) $option->id)>{{ $option->reference }} - {{ $option->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="building" class="form-label">{{ __('main.gmao_building') }}</label>
                                <input id="building" name="building" class="form-control @error('building') is-invalid @enderror" value="{{ old('building') }}" placeholder="Ex. Bloc A" data-location-field data-default-value="">
                                @error('building')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="floor" class="form-label">{{ __('main.gmao_floor') }}</label>
                                <input id="floor" name="floor" class="form-control @error('floor') is-invalid @enderror" value="{{ old('floor') }}" placeholder="Ex. RDC" data-location-field data-default-value="">
                                @error('floor')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-location-field data-default-value="active">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">{{ __('main.description') }}</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="{{ __('main.gmao_location_description_placeholder') }}" data-location-field data-default-value="">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="modal-submit" id="locationSubmit">{{ $isEditing ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('gmaoLocationModal');
            const form = modal?.querySelector('.gmao-location-form');
            const title = document.getElementById('gmaoLocationModalLabel');
            const method = document.getElementById('locationHttpMethod');
            const mode = document.getElementById('locationFormMode');
            const recordId = document.getElementById('locationRecordId');
            const submit = document.getElementById('locationSubmit');
            const typeSelect = document.getElementById('type');
            const parentSelect = document.getElementById('parent_id');
            const fields = form ? Array.from(form.querySelectorAll('[data-location-field]')) : [];
            const parentRules = @json($parentRules);

            if (!modal || !form) return;

            const resetFields = () => fields.forEach((field) => { field.value = field.dataset.defaultValue || ''; });
            const applyValues = (values) => fields.forEach((field) => {
                if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                    field.value = values[field.name] ?? '';
                }
            });
            const filterParentOptions = () => {
                if (!typeSelect || !parentSelect) return;

                const expectedParentType = parentRules[typeSelect.value] ?? null;
                let currentOptionAllowed = false;

                Array.from(parentSelect.options).forEach((option) => {
                    const isRoot = option.value === '';
                    const matchesHierarchy = expectedParentType === null
                        ? isRoot
                        : option.dataset.locationType === expectedParentType;
                    const isSelfLocked = option.dataset.selfLocked === '1';

                    option.hidden = !matchesHierarchy;
                    option.disabled = !matchesHierarchy || isSelfLocked;

                    if (option.selected && !option.disabled) {
                        currentOptionAllowed = true;
                    }
                });

                if (!currentOptionAllowed) {
                    const firstAllowed = Array.from(parentSelect.options).find((option) => !option.disabled && !option.hidden);
                    parentSelect.value = firstAllowed?.value ?? '';
                }
            };
            const lockSelfParent = (locationId) => {
                if (!parentSelect) return;
                Array.from(parentSelect.options).forEach((option) => {
                    option.dataset.selfLocked = option.value !== '' && option.value === String(locationId) ? '1' : '0';
                });
                filterParentOptions();
            };

            modal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const isEdit = button?.dataset.locationMode === 'edit';

                if (!button && form.dataset.hasErrors === '1') return;

                form.action = isEdit ? button.dataset.locationAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                recordId.value = isEdit ? button.dataset.locationId : '';
                method.disabled = !isEdit;
                resetFields();
                lockSelfParent(isEdit ? button.dataset.locationId : null);

                if (isEdit) {
                    applyValues(JSON.parse(atob(button.dataset.locationValues || 'e30=')));
                    filterParentOptions();
                }
            });

            typeSelect?.addEventListener('change', filterParentOptions);
            filterParentOptions();

            @if ($hasErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

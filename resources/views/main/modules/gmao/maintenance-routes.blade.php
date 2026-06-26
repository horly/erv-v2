<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_maintenance_routes') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    @php
        $hasErrors = $errors->any() && old('gmao_form') === 'maintenance_route';
        $isEditing = $hasErrors && old('form_mode') === 'edit' && old('record_id');
        $formAction = $isEditing
            ? route('main.gmao.maintenance-routes.update', [$company, $site, old('record_id')])
            : route('main.gmao.maintenance-routes.store', [$company, $site]);
        $routePayload = fn ($route) => [
            'reference' => $route->reference,
            'title' => $route->title,
            'gmao_equipment_category_id' => $route->gmao_equipment_category_id,
            'frequency' => $route->frequency,
            'estimated_duration_hours' => $route->estimated_duration_hours,
            'status' => $route->status,
            'instructions' => $route->instructions,
            'tasks' => $route->tasks->pluck('instructions')->implode("\n"),
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar')

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.gmao_maintenance_routes') }}</h1>
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
                        <span class="module-heading-icon module-gmao"><i class="bi bi-list-check" aria-hidden="true"></i></span>
                        <div>
                            <h1>{{ __('main.gmao_maintenance_routes') }}</h1>
                            <p>{{ __('main.gmao_maintenance_routes_subtitle') }}</p>
                        </div>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#gmaoMaintenanceRouteModal" data-route-mode="create">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        {{ __('main.gmao_new_maintenance_route') }}
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
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.gmao_search_maintenance_routes') }}" autocomplete="off">
                    </form>
                    <span class="row-count"><strong>{{ $routes->count() }}</strong> / <strong>{{ $routes->total() }}</strong> {{ __('admin.rows') }}</span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.gmao_maintenance_route') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.category') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.frequency') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.gmao_tasks') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($routes as $route)
                                    <tr>
                                        <td><strong>{{ $route->reference }}</strong></td>
                                        <td>
                                            <strong>{{ $route->title }}</strong>
                                            <small class="d-block text-muted">{{ $route->instructions ?: '-' }}</small>
                                        </td>
                                        <td>{{ $route->equipmentCategory?->name ?? '-' }}</td>
                                        <td>
                                            <strong>{{ $frequencyLabels[$route->frequency] ?? $route->frequency }}</strong>
                                            <small class="d-block text-muted">{{ number_format((float) $route->estimated_duration_hours, 2, ',', ' ') }} h</small>
                                        </td>
                                        <td><strong>{{ $route->tasks->count() }}</strong></td>
                                        <td><span class="status-pill gmao-status-{{ $route->status }}">{{ $statusLabels[$route->status] ?? $route->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#gmaoMaintenanceRouteModal" data-route-mode="edit" data-route-id="{{ $route->id }}" data-route-action="{{ route('main.gmao.maintenance-routes.update', [$company, $site, $route]) }}" data-route-values="{{ base64_encode(json_encode($routePayload($route))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.gmao.maintenance-routes.destroy', [$company, $site, $route]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.gmao_maintenance_route_delete_title') }}" data-delete-text="{{ __('main.gmao_maintenance_route_delete_text', ['reference' => $route->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.gmao_no_maintenance_routes') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($routes->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $routes->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $routes->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $routes->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($routes->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $routes->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($routes->getUrlRange(1, $routes->lastPage()) as $page => $url)
                                @if ($page === $routes->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($routes->hasMorePages())<a href="{{ $routes->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal gmao-equipment-modal" id="gmaoMaintenanceRouteModal" tabindex="-1" aria-labelledby="gmaoMaintenanceRouteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form gmao-maintenance-route-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.gmao.maintenance-routes.store', [$company, $site]) }}" data-title-create="{{ __('main.gmao_new_maintenance_route') }}" data-title-edit="{{ __('main.gmao_edit_maintenance_route') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="routeHttpMethod" value="PUT" @disabled(! $isEditing)>
                <input type="hidden" name="gmao_form" value="maintenance_route">
                <input type="hidden" name="form_mode" id="routeFormMode" value="{{ $isEditing ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="routeRecordId" value="{{ old('record_id') }}">

                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="gmaoMaintenanceRouteModalLabel"><i class="bi bi-list-check" aria-hidden="true"></i>{{ $isEditing ? __('main.gmao_edit_maintenance_route') : __('main.gmao_new_maintenance_route') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.automatic_if_empty') }}" data-route-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="title" class="form-label">{{ __('main.title') }} *</label>
                                <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="{{ __('main.gmao_maintenance_route_placeholder') }}" data-route-field data-default-value="">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="gmao_equipment_category_id" class="form-label">{{ __('main.category') }}</label>
                                <select id="gmao_equipment_category_id" name="gmao_equipment_category_id" class="form-select @error('gmao_equipment_category_id') is-invalid @enderror" data-route-field data-default-value="">
                                    <option value="">{{ __('main.all_categories') }}</option>
                                    @foreach ($equipmentCategories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('gmao_equipment_category_id') === (string) $category->id)>{{ $category->reference }} - {{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('gmao_equipment_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="frequency" class="form-label">{{ __('main.frequency') }} *</label>
                                <select id="frequency" name="frequency" class="form-select @error('frequency') is-invalid @enderror" data-route-field data-default-value="monthly">
                                    @foreach ($frequencyLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('frequency', 'monthly') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('frequency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-route-field data-default-value="active">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="estimated_duration_hours" class="form-label">{{ __('main.gmao_estimated_duration') }}</label>
                                <input id="estimated_duration_hours" name="estimated_duration_hours" type="number" min="0" step="0.25" class="form-control @error('estimated_duration_hours') is-invalid @enderror" value="{{ old('estimated_duration_hours') }}" placeholder="0" data-route-field data-default-value="0">
                                @error('estimated_duration_hours')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="instructions" class="form-label">{{ __('main.instructions') }}</label>
                                <input id="instructions" name="instructions" class="form-control @error('instructions') is-invalid @enderror" value="{{ old('instructions') }}" placeholder="{{ __('main.gmao_maintenance_route_instructions_placeholder') }}" data-route-field data-default-value="">
                                @error('instructions')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="tasks" class="form-label">{{ __('main.gmao_tasks') }}</label>
                                <textarea id="tasks" name="tasks" class="form-control @error('tasks') is-invalid @enderror" rows="5" placeholder="{{ __('main.gmao_tasks_placeholder') }}" data-route-field data-default-value="">{{ old('tasks') }}</textarea>
                                @error('tasks')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="modal-submit" id="routeSubmit">{{ $isEditing ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('gmaoMaintenanceRouteModal');
            const form = modal?.querySelector('.gmao-maintenance-route-form');
            const title = document.getElementById('gmaoMaintenanceRouteModalLabel');
            const method = document.getElementById('routeHttpMethod');
            const mode = document.getElementById('routeFormMode');
            const recordId = document.getElementById('routeRecordId');
            const submit = document.getElementById('routeSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-route-field]')) : [];

            if (!modal || !form) return;

            const resetFields = () => fields.forEach((field) => { field.value = field.dataset.defaultValue || ''; });
            const applyValues = (values) => fields.forEach((field) => {
                if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                    field.value = values[field.name] ?? '';
                }
            });

            modal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const isEdit = button?.dataset.routeMode === 'edit';

                if (!button && form.dataset.hasErrors === '1') return;

                form.action = isEdit ? button.dataset.routeAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                recordId.value = isEdit ? button.dataset.routeId : '';
                method.disabled = !isEdit;
                resetFields();

                if (isEdit) {
                    applyValues(JSON.parse(atob(button.dataset.routeValues || 'e30=')));
                }
            });

            @if ($hasErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

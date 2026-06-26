<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    @php
        $resourceActions ??= null;
        $records ??= $rows;
        $isEquipmentPage = $resourceActions === 'equipment';
        $hasEquipmentErrors = $isEquipmentPage && (($errors->any() && old('gmao_form') === 'equipment') || false);
        $isEditingEquipment = $hasEquipmentErrors && old('form_mode') === 'edit' && old('record_id');
        $equipmentFormAction = $isEquipmentPage
            ? ($isEditingEquipment
                ? route('main.gmao.equipment.update', [$company, $site, old('record_id')])
                : route('main.gmao.equipment.store', [$company, $site]))
            : '#';
        $equipmentPayload = fn ($record) => [
            'gmao_location_id' => $record->gmao_location_id,
            'gmao_equipment_category_id' => $record->gmao_equipment_category_id,
            'reference' => $record->reference,
            'asset_code' => $record->asset_code,
            'name' => $record->name,
            'criticality' => $record->criticality,
            'brand' => $record->brand,
            'model' => $record->model,
            'serial_number' => $record->serial_number,
            'supplier' => $record->supplier,
            'acquisition_cost' => $record->acquisition_cost,
            'expense_type' => $record->expense_type,
            'cost_center' => $record->cost_center,
            'meter_unit' => $record->meter_unit,
            'current_meter' => $record->current_meter,
            'last_meter_read_at' => $record->last_meter_read_at?->format('Y-m-d\TH:i'),
            'expected_lifetime_months' => $record->expected_lifetime_months,
            'commissioned_at' => $record->commissioned_at?->format('Y-m-d'),
            'warranty_until' => $record->warranty_until?->format('Y-m-d'),
            'status' => $record->status,
            'notes' => $record->notes,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar')

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ $title }}</h1>
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
                        <span class="module-heading-icon module-gmao"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                        <div>
                            <h1>{{ $title }}</h1>
                            <p>{{ $subtitle }}</p>
                        </div>
                    </div>
                    @if ($isEquipmentPage)
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#gmaoEquipmentModal" data-equipment-mode="create">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            {{ __('main.gmao_new_equipment') }}
                        </button>
                    @endif
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
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}" autocomplete="off">
                    </form>
                    <span class="row-count">
                        <strong>{{ $rows->count() }}</strong>
                        /
                        <strong>{{ $rows->total() }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table" id="companyTable">
                            <thead>
                                <tr>
                                    @foreach ($columns as $index => $column)
                                        <th><button class="table-sort" type="button" data-sort-index="{{ $index }}">{{ $column }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @endforeach
                                    @if ($resourceActions)
                                        <th class="text-end">{{ __('admin.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $record = $records->get($loop->index);
                                    @endphp
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>
                                                @if (is_array($cell) && array_key_exists('badge', $cell))
                                                    <span class="status-pill gmao-status-{{ $cell['class'] }}">{{ $cell['badge'] }}</span>
                                                @elseif (is_array($cell))
                                                    <strong>{{ $cell['strong'] }}</strong>
                                                    @if (! empty($cell['small']))
                                                        <small class="d-block text-muted">{{ $cell['small'] }}</small>
                                                    @endif
                                                @else
                                                    {{ $cell }}
                                                @endif
                                            </td>
                                        @endforeach
                                        @if ($resourceActions === 'equipment' && $record)
                                            <td>
                                                <div class="table-actions">
                                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#gmaoEquipmentModal" data-equipment-mode="edit" data-equipment-id="{{ $record->id }}" data-equipment-action="{{ route('main.gmao.equipment.update', [$company, $site, $record]) }}" data-equipment-values="{{ base64_encode(json_encode($equipmentPayload($record))) }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('main.gmao.equipment.destroy', [$company, $site, $record]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.gmao_equipment_delete_title') }}" data-delete-text="{{ __('main.gmao_equipment_delete_text', ['reference' => $record->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="{{ count($columns) + ($resourceActions ? 1 : 0) }}">{{ __('main.gmao_no_records') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="{{ count($columns) + ($resourceActions ? 1 : 0) }}">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($rows->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $rows->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $rows->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $rows->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($rows->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $rows->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($rows->getUrlRange(1, $rows->lastPage()) as $page => $url)
                                @if ($page === $rows->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($rows->hasMorePages())<a href="{{ $rows->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @if ($isEquipmentPage)
        <div class="modal fade subscription-modal gmao-equipment-modal" id="gmaoEquipmentModal" tabindex="-1" aria-labelledby="gmaoEquipmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content admin-form gmao-equipment-form" method="POST" action="{{ $equipmentFormAction }}" data-create-action="{{ route('main.gmao.equipment.store', [$company, $site]) }}" data-title-create="{{ __('main.gmao_new_equipment') }}" data-title-edit="{{ __('main.gmao_edit_equipment') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasEquipmentErrors ? '1' : '0' }}" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="equipmentHttpMethod" value="PUT" @disabled(! $isEditingEquipment)>
                    <input type="hidden" name="gmao_form" value="equipment">
                    <input type="hidden" name="form_mode" id="equipmentFormMode" value="{{ $isEditingEquipment ? 'edit' : 'create' }}">
                    <input type="hidden" name="record_id" id="equipmentRecordId" value="{{ old('record_id') }}">

                    <div class="modal-body">
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <h2 id="gmaoEquipmentModalLabel"><i class="bi bi-cpu" aria-hidden="true"></i>{{ $isEditingEquipment ? __('main.gmao_edit_equipment') : __('main.gmao_new_equipment') }}</h2>

                        <section class="client-type-panel">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                    <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.automatic_if_empty') }}" data-equipment-field data-default-value="">
                                    @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="name" class="form-label">{{ __('main.name') }} *</label>
                                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.gmao_equipment_name_placeholder') }}" data-equipment-field data-default-value="">
                                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="asset_code" class="form-label">{{ __('main.gmao_asset_code') }}</label>
                                    <input id="asset_code" name="asset_code" class="form-control @error('asset_code') is-invalid @enderror" value="{{ old('asset_code') }}" placeholder="Ex. VDC-KIN-UPS-001" data-equipment-field data-default-value="">
                                    @error('asset_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="gmao_equipment_category_id" class="form-label">{{ __('main.category') }} *</label>
                                    <select id="gmao_equipment_category_id" name="gmao_equipment_category_id" class="form-select @error('gmao_equipment_category_id') is-invalid @enderror" data-equipment-field data-default-value="">
                                        <option value="">{{ __('main.select') }}</option>
                                        @forelse ($equipmentCategories as $category)
                                            <option value="{{ $category->id }}" data-default-criticality="{{ $category->default_criticality }}" @selected((string) old('gmao_equipment_category_id') === (string) $category->id)>
                                                {{ $category->reference }} - {{ $category->name }}
                                            </option>
                                        @empty
                                            <option value="" disabled>{{ __('main.gmao_no_active_equipment_categories') }}</option>
                                        @endforelse
                                    </select>
                                    <a class="modal-inline-link" href="{{ route('main.gmao.equipment-categories', [$company, $site]) }}">
                                        <i class="bi bi-tags" aria-hidden="true"></i>
                                        {{ __('main.gmao_manage_equipment_categories') }}
                                    </a>
                                    @error('gmao_equipment_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="criticality" class="form-label">{{ __('main.gmao_criticality') }} *</label>
                                    <select id="criticality" name="criticality" class="form-select @error('criticality') is-invalid @enderror" data-equipment-field data-default-value="medium">
                                        @foreach ($criticalityLabels as $value => $label)
                                            <option value="{{ $value }}" @selected(old('criticality', 'medium') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('criticality')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">{{ __('main.status') }} *</label>
                                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-equipment-field data-default-value="{{ \App\Models\GmaoEquipment::STATUS_OPERATIONAL }}">
                                        @foreach ($equipmentStatusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', \App\Models\GmaoEquipment::STATUS_OPERATIONAL) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="gmao_location_id" class="form-label">{{ __('main.location') }}</label>
                                    <select id="gmao_location_id" name="gmao_location_id" class="form-select @error('gmao_location_id') is-invalid @enderror" data-equipment-field data-default-value="">
                                        <option value="">{{ __('main.select') }}</option>
                                        @foreach ($equipmentLocations as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('gmao_location_id') === (string) $location->id)>{{ $location->name }} - {{ $location->reference }}</option>
                                        @endforeach
                                    </select>
                                    @error('gmao_location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="serial_number" class="form-label">{{ __('main.serial_number') }}</label>
                                    <input id="serial_number" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number') }}" placeholder="{{ __('main.serial_number_placeholder') }}" data-equipment-field data-default-value="">
                                    @error('serial_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="supplier" class="form-label">{{ __('main.supplier') }}</label>
                                    <input id="supplier" name="supplier" class="form-control @error('supplier') is-invalid @enderror" value="{{ old('supplier') }}" placeholder="Ex. Fournisseur Central" data-equipment-field data-default-value="">
                                    @error('supplier')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="brand" class="form-label">{{ __('main.gmao_brand') }}</label>
                                    <input id="brand" name="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}" placeholder="Ex. Caterpillar" data-equipment-field data-default-value="">
                                    @error('brand')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="model" class="form-label">{{ __('main.gmao_model') }}</label>
                                    <input id="model" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model') }}" placeholder="Ex. XZ-200" data-equipment-field data-default-value="">
                                    @error('model')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="acquisition_cost" class="form-label">{{ __('main.gmao_acquisition_cost') }}</label>
                                    <input id="acquisition_cost" name="acquisition_cost" type="number" min="0" step="0.01" class="form-control @error('acquisition_cost') is-invalid @enderror" value="{{ old('acquisition_cost') }}" placeholder="0.00" data-equipment-field data-default-value="0">
                                    @error('acquisition_cost')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="expense_type" class="form-label">{{ __('main.gmao_expense_type') }}</label>
                                    <select id="expense_type" name="expense_type" class="form-select @error('expense_type') is-invalid @enderror" data-equipment-field data-default-value="capex">
                                        <option value="capex" @selected(old('expense_type', 'capex') === 'capex')>CAPEX</option>
                                        <option value="opex" @selected(old('expense_type') === 'opex')>OPEX</option>
                                    </select>
                                    @error('expense_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="cost_center" class="form-label">{{ __('main.gmao_cost_center') }}</label>
                                    <input id="cost_center" name="cost_center" class="form-control @error('cost_center') is-invalid @enderror" value="{{ old('cost_center') }}" placeholder="Ex. KIN-NOC" data-equipment-field data-default-value="">
                                    @error('cost_center')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="commissioned_at" class="form-label">{{ __('main.gmao_commissioned_at') }}</label>
                                    <input id="commissioned_at" name="commissioned_at" type="date" class="form-control @error('commissioned_at') is-invalid @enderror" value="{{ old('commissioned_at') }}" data-equipment-field data-default-value="">
                                    @error('commissioned_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="warranty_until" class="form-label">{{ __('main.gmao_warranty_until') }}</label>
                                    <input id="warranty_until" name="warranty_until" type="date" class="form-control @error('warranty_until') is-invalid @enderror" value="{{ old('warranty_until') }}" data-equipment-field data-default-value="">
                                    @error('warranty_until')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="meter_unit" class="form-label">{{ __('main.gmao_meter_unit') }}</label>
                                    <input id="meter_unit" name="meter_unit" class="form-control @error('meter_unit') is-invalid @enderror" value="{{ old('meter_unit') }}" placeholder="h, km, cycles" data-equipment-field data-default-value="">
                                    @error('meter_unit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="current_meter" class="form-label">{{ __('main.gmao_current_meter') }}</label>
                                    <input id="current_meter" name="current_meter" type="number" min="0" step="0.01" class="form-control @error('current_meter') is-invalid @enderror" value="{{ old('current_meter') }}" placeholder="0" data-equipment-field data-default-value="0">
                                    @error('current_meter')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="last_meter_read_at" class="form-label">{{ __('main.gmao_last_meter_read_at') }}</label>
                                    <input id="last_meter_read_at" name="last_meter_read_at" type="datetime-local" class="form-control @error('last_meter_read_at') is-invalid @enderror" value="{{ old('last_meter_read_at') }}" data-equipment-field data-default-value="">
                                    @error('last_meter_read_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="expected_lifetime_months" class="form-label">{{ __('main.gmao_lifetime_months') }}</label>
                                    <input id="expected_lifetime_months" name="expected_lifetime_months" type="number" min="0" step="1" class="form-control @error('expected_lifetime_months') is-invalid @enderror" value="{{ old('expected_lifetime_months') }}" placeholder="60" data-equipment-field data-default-value="">
                                    @error('expected_lifetime_months')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label">{{ __('main.notes') }}</label>
                                    <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('main.gmao_equipment_notes_placeholder') }}" data-equipment-field data-default-value="">{{ old('notes') }}</textarea>
                                    @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </section>

                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                            <button type="submit" class="modal-submit" id="equipmentSubmit">{{ $isEditingEquipment ? __('admin.update') : __('admin.create') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    @if ($isEquipmentPage)
        <script>
            (() => {
                const modal = document.getElementById('gmaoEquipmentModal');
                const form = modal?.querySelector('.gmao-equipment-form');
                const title = document.getElementById('gmaoEquipmentModalLabel');
                const method = document.getElementById('equipmentHttpMethod');
                const mode = document.getElementById('equipmentFormMode');
                const recordId = document.getElementById('equipmentRecordId');
                const submit = document.getElementById('equipmentSubmit');
                const categorySelect = document.getElementById('gmao_equipment_category_id');
                const criticalitySelect = document.getElementById('criticality');
                const fields = form ? Array.from(form.querySelectorAll('[data-equipment-field]')) : [];

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
                    const isEdit = button?.dataset.equipmentMode === 'edit';

                    if (!button && form.dataset.hasErrors === '1') {
                        return;
                    }

                    form.action = isEdit ? button.dataset.equipmentAction : form.dataset.createAction;
                    title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                    submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                    mode.value = isEdit ? 'edit' : 'create';
                    recordId.value = isEdit ? button.dataset.equipmentId : '';
                    method.disabled = !isEdit;
                    resetFields();

                    if (isEdit) {
                        applyValues(JSON.parse(atob(button.dataset.equipmentValues || 'e30=')));
                    }
                });

                categorySelect?.addEventListener('change', () => {
                    const defaultCriticality = categorySelect.selectedOptions[0]?.dataset.defaultCriticality;
                    if (defaultCriticality && criticalitySelect) {
                        criticalitySelect.value = defaultCriticality;
                    }
                });

                @if ($hasEquipmentErrors)
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                @endif
            })();
        </script>
    @endif
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_contracts') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalContracts = $contracts->total();
        $contractPayload = fn ($contract) => [
            'human_resource_employee_id' => $contract->human_resource_employee_id,
            'reference' => $contract->reference,
            'type' => $contract->type,
            'status' => $contract->status,
            'start_date' => optional($contract->start_date)->format('Y-m-d'),
            'end_date' => optional($contract->end_date)->format('Y-m-d'),
            'probation_end_date' => optional($contract->probation_end_date)->format('Y-m-d'),
            'currency' => $contract->currency,
            'monthly_salary' => number_format((float) $contract->monthly_salary, 2, '.', ''),
            'notes' => $contract->notes,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'contracts'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_contracts') }}</h1>
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
                        <h1>{{ __('main.hr_contracts') }}</h1>
                        <p>{{ __('main.hr_contracts_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#contractModal" data-contract-mode="create">
                        <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                        {{ __('main.hr_new_contract') }}
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
                        <strong id="visibleCount">{{ $contracts->count() }}</strong>
                        /
                        <strong>{{ $totalContracts }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-contracts-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.hr_employee') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contracts as $contract)
                                    <tr>
                                        <td>{{ $contract->reference }}</td>
                                        <td>
                                            <strong>{{ $contract->employee?->full_name ?? '-' }}</strong>
                                            @if ($contract->employee?->department)
                                                <small class="d-block text-muted">{{ $contract->employee->department->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $contractTypes[$contract->type] ?? $contract->type }}</td>
                                        <td>{{ optional($contract->start_date)->format('d/m/Y') }} - {{ $contract->end_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="text-end" data-sort-value="{{ $contract->monthly_salary }}">{{ number_format((float) $contract->monthly_salary, 2, ',', ' ') }} {{ $contract->currency }}</td>
                                        <td><span class="status-pill hr-contract-status-{{ $contract->status }}">{{ $contractStatuses[$contract->status] ?? $contract->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#contractModal" data-contract-mode="edit" data-contract-action="{{ route('main.human-resources.contracts.update', [$company, $site, $contract]) }}" data-contract-id="{{ $contract->id }}" data-contract-values="{{ base64_encode(json_encode($contractPayload($contract))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.human-resources.contracts.destroy', [$company, $site, $contract]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.hr_delete_contract_title') }}" data-delete-text="{{ __('main.hr_delete_contract_text', ['reference' => $contract->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="7">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="7">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($contracts->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $contracts->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $contracts->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalContracts }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($contracts->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $contracts->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($contracts->getUrlRange(1, $contracts->lastPage()) as $page => $url)
                                @if ($page === $contracts->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($contracts->hasMorePages())<a href="{{ $contracts->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal hr-contract-modal" id="contractModal" tabindex="-1" aria-labelledby="contractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-contract-form" method="POST" action="{{ $contractFormAction }}" data-create-action="{{ route('main.human-resources.contracts.store', [$company, $site]) }}" data-title-create="{{ __('main.hr_new_contract') }}" data-title-edit="{{ __('main.hr_edit_contract') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasContractErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="contractHttpMethod" value="PUT" @disabled(! $isEditingContract)>
                <input type="hidden" name="form_mode" id="contractFormMode" value="{{ $isEditingContract ? 'edit' : 'create' }}">
                <input type="hidden" name="contract_id" id="contractId" value="{{ old('contract_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="contractModalLabel"><i class="bi bi-file-earmark-text" aria-hidden="true"></i>{{ $isEditingContract ? __('main.hr_edit_contract') : __('main.hr_new_contract') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="human_resource_employee_id" class="form-label">{{ __('main.hr_employee') }} *</label>
                                <select id="human_resource_employee_id" name="human_resource_employee_id" class="form-select @error('human_resource_employee_id') is-invalid @enderror" data-contract-field data-default-value="">
                                    <option value="">{{ __('main.select') }}</option>
                                    @foreach ($employeeOptions as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) old('human_resource_employee_id') === (string) $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                                    @endforeach
                                </select>
                                @error('human_resource_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.hr_contract_reference_placeholder') }}" data-contract-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-file-text" aria-hidden="true"></i> {{ __('main.hr_contract_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contract_type" class="form-label">{{ __('main.type') }} *</label>
                                <select id="contract_type" name="type" class="form-select @error('type') is-invalid @enderror" data-contract-field data-default-value="{{ \App\Models\HumanResourceContract::TYPE_PERMANENT }}">
                                    @foreach ($contractTypes as $type => $label)
                                        <option value="{{ $type }}" @selected(old('type', \App\Models\HumanResourceContract::TYPE_PERMANENT) === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contract_status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="contract_status" name="status" class="form-select @error('status') is-invalid @enderror" data-contract-field data-default-value="{{ \App\Models\HumanResourceContract::STATUS_ACTIVE }}">
                                    @foreach ($contractStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', \App\Models\HumanResourceContract::STATUS_ACTIVE) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">{{ __('main.start_date') }} *</label>
                                <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}" data-contract-field data-default-value="{{ now()->toDateString() }}">
                                @error('start_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">{{ __('main.end_date') }}</label>
                                <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" data-contract-field data-default-value="">
                                @error('end_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="probation_end_date" class="form-label">{{ __('main.hr_probation_end_date') }}</label>
                                <input id="probation_end_date" name="probation_end_date" type="date" class="form-control @error('probation_end_date') is-invalid @enderror" value="{{ old('probation_end_date') }}" data-contract-field data-default-value="">
                                @error('probation_end_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-cash-stack" aria-hidden="true"></i> {{ __('main.hr_contract_salary_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="currency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-contract-field data-default-value="USD">
                                    @foreach ($currencyOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('currency', 'USD') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="monthly_salary" class="form-label">{{ __('main.hr_monthly_salary') }} *</label>
                                <input id="monthly_salary" name="monthly_salary" type="number" min="0" step="0.01" class="form-control @error('monthly_salary') is-invalid @enderror" value="{{ old('monthly_salary', '0.00') }}" data-contract-field data-default-value="0.00">
                                @error('monthly_salary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="contract_notes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="contract_notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('main.notes') }}" data-contract-field data-default-value="">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="contractSubmit">{{ $isEditingContract ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('contractModal');
            const form = modal?.querySelector('.hr-contract-form');
            const title = document.getElementById('contractModalLabel');
            const method = document.getElementById('contractHttpMethod');
            const mode = document.getElementById('contractFormMode');
            const id = document.getElementById('contractId');
            const submit = document.getElementById('contractSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-contract-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.contractMode === 'edit';
                form.action = isEdit ? button.dataset.contractAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.contractId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.contractValues || 'e30='));
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

            @if ($hasContractErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_validation_circuits') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalCircuits = $circuits->total();
        $totalValidationRequests = $validationRequests->total();
        $isEditingCircuit = old('form_mode') === 'edit' && old('circuit_id');
        $circuitFormAction = $isEditingCircuit
            ? route('main.document-management.validation-circuits.update', [$company, $site, old('circuit_id')])
            : route('main.document-management.validation-circuits.store', [$company, $site]);
        $circuitPayload = fn ($circuit) => [
            'name' => $circuit->name,
            'document_type' => $circuit->document_type,
            'service_owner' => $circuit->service_owner,
            'status' => $circuit->status,
            'description' => $circuit->description,
            'steps' => $circuit->steps->map(fn ($step) => [
                'name' => $step->name,
                'role_name' => $step->role_name,
                'validator_id' => $step->validator_id,
                'due_days' => $step->due_days,
            ])->values(),
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'validation'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_validation_circuits') }}</h1>
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
                        <h1>{{ __('main.ged_validation_circuits') }}</h1>
                        <p>{{ __('main.ged_validation_circuits_subtitle') }}</p>
                    </div>
                    <div class="ged-validation-heading-actions">
                        <button class="secondary-action" type="button" data-bs-toggle="modal" data-bs-target="#validationRequestModal" @disabled($circuitsForValidation->isEmpty() || $documentsForValidation->isEmpty())>
                            <i class="bi bi-send-check" aria-hidden="true"></i>
                            {{ __('main.ged_start_validation') }}
                        </button>
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#validationCircuitModal" data-circuit-mode="create">
                            <i class="bi bi-check2-square" aria-hidden="true"></i>
                            {{ __('main.ged_new_validation_circuit') }}
                        </button>
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

                <nav class="report-section-tabs ged-validation-tabs" aria-label="{{ __('main.ged_validation_circuits') }}" data-ged-validation-tabs>
                    <a class="active" href="#validationCircuitsTable" data-ged-validation-tab="circuits">{{ __('main.ged_validation_tab_circuits') }}</a>
                    <a href="#validationRequestsTable" data-ged-validation-tab="requests">{{ __('main.ged_validation_tab_requests') }}</a>
                </nav>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}" data-ged-validation-panel="circuits">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $circuits->count() }}</strong>
                        /
                        <strong>{{ $totalCircuits }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card" id="validationCircuitsTable" data-ged-validation-panel="circuits">
                    <div class="table-responsive">
                        <table class="company-table ged-validation-circuits-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.ged_validation_circuit') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th>{{ __('main.ged_validation_steps') }}</th>
                                    <th>{{ __('main.status') }}</th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($circuits as $circuit)
                                    <tr>
                                        <td><strong>{{ $circuit->reference }}</strong></td>
                                        <td class="ged-mail-cell">
                                            <strong>{{ $circuit->name }}</strong>
                                            <span>{{ $documentTypeLabels[$circuit->document_type] ?? $circuit->document_type }} @if ($circuit->service_owner) &middot; {{ $circuit->service_owner }} @endif</span>
                                            @if ($circuit->description)
                                                <small>{{ $circuit->description }}</small>
                                            @endif
                                        </td>
                                        <td class="ged-tracking-cell">
                                            <strong>{{ trans_choice('main.ged_validation_steps_count', $circuit->steps_count, ['count' => $circuit->steps_count]) }}</strong>
                                            @foreach ($circuit->steps->take(3) as $step)
                                                <span>{{ $step->step_order }}. {{ $step->name }} @if ($step->validator) - {{ $step->validator->name }} @endif</span>
                                            @endforeach
                                        </td>
                                        <td><span class="status-pill status-{{ $circuit->status }}">{{ $circuitStatusLabels[$circuit->status] ?? $circuit->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#validationCircuitModal" data-circuit-mode="edit" data-circuit-action="{{ route('main.document-management.validation-circuits.update', [$company, $site, $circuit]) }}" data-circuit-id="{{ $circuit->id }}" data-circuit-values="{{ base64_encode(json_encode($circuitPayload($circuit))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.document-management.validation-circuits.destroy', [$company, $site, $circuit]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.ged_delete_validation_circuit_title') }}" data-delete-text="{{ __('main.ged_delete_validation_circuit_text', ['reference' => $circuit->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.ged_no_validation_circuits') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="5">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($circuits->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}" data-ged-validation-panel="circuits">
                        <span>{{ __('admin.showing') }} <strong>{{ $circuits->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $circuits->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalCircuits }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($circuits->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $circuits->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($circuits->getUrlRange(1, $circuits->lastPage()) as $page => $url)
                                @if ($page === $circuits->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($circuits->hasMorePages())<a href="{{ $circuits->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif

                <section class="company-card ged-validation-requests-card" id="validationRequestsTable" data-ged-validation-panel="requests" hidden>
                    <div class="hr-panel-header">
                        <div>
                            <span>{{ __('main.ged_validation_tab_requests') }}</span>
                            <h3>{{ __('main.ged_validation_requests_title') }}</h3>
                        </div>
                        <strong>{{ $totalValidationRequests }} {{ __('admin.rows') }}</strong>
                    </div>
                    @if ($validationRequests->isEmpty())
                        <div class="ged-validation-empty-state">
                            <span><i class="bi bi-check2-square" aria-hidden="true"></i></span>
                            <div>
                                <strong>{{ __('main.ged_no_validation_requests') }}</strong>
                                <p>{{ __('main.ged_no_validation_requests_text') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="company-table ged-validation-requests-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('main.reference') }}</th>
                                        <th>{{ __('main.ged_document') }}</th>
                                        <th>{{ __('main.ged_validation') }}</th>
                                        <th>{{ __('main.ged_expected_validator') }}</th>
                                        <th>{{ __('main.ged_due_at') }}</th>
                                        <th>{{ __('main.status') }}</th>
                                        <th class="text-end">{{ __('admin.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($validationRequests as $validationRequest)
                                        @php
                                            $canProcessValidation = $user->isAdmin()
                                                || $user->isSuperadmin()
                                                || $validationRequest->currentStep?->validator_id === null
                                                || (int) $validationRequest->currentStep?->validator_id === (int) $user->id;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $validationRequest->record?->reference ?? '-' }}</strong></td>
                                            <td class="ged-mail-cell">
                                                <strong>{{ $validationRequest->record?->subject ?? '-' }}</strong>
                                                <span>{{ $typeLabels[$validationRequest->record?->record_type] ?? '-' }} &middot; {{ $validationRequest->record?->folder?->name ?? __('main.ged_without_folder') }}</span>
                                            </td>
                                            <td class="ged-tracking-cell">
                                                <strong>{{ $validationRequest->circuit?->name ?? '-' }}</strong>
                                                <span>{{ __('main.ged_current_step') }} : {{ $validationRequest->currentStep?->name ?? '-' }}</span>
                                            </td>
                                            <td class="ged-validator-cell">
                                                <strong>{{ $validationRequest->currentStep?->validator?->name ?? __('main.ged_unassigned_validator') }}</strong>
                                                <span>{{ $validationRequest->currentStep?->role_name ?? __('main.ged_validation_role') }}</span>
                                            </td>
                                            <td>{{ $validationRequest->due_at?->format('d/m/Y') ?? '-' }}</td>
                                            <td><span class="status-pill ged-validation-request-{{ $validationRequest->status }}">{{ $requestStatusLabels[$validationRequest->status] ?? $validationRequest->status }}</span></td>
                                            <td>
                                                <div class="table-actions ged-validation-actions">
                                                    <button type="button" class="table-button table-button-convert" data-bs-toggle="modal" data-bs-target="#validationRequestActionModal" data-validation-action="{{ route('main.document-management.validation-requests.approve', [$company, $site, $validationRequest]) }}" data-validation-title="{{ __('main.ged_approve_validation_title') }}" data-validation-submit="{{ __('main.ged_approve') }}" data-validation-tone="approve" @disabled(! $canProcessValidation) aria-label="{{ __('main.ged_approve') }}">
                                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="table-button table-button-delete" data-bs-toggle="modal" data-bs-target="#validationRequestActionModal" data-validation-action="{{ route('main.document-management.validation-requests.reject', [$company, $site, $validationRequest]) }}" data-validation-title="{{ __('main.ged_reject_validation_title') }}" data-validation-submit="{{ __('main.ged_reject') }}" data-validation-tone="reject" @disabled(! $canProcessValidation) aria-label="{{ __('main.ged_reject') }}">
                                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                @if ($validationRequests->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}" data-ged-validation-panel="requests" hidden>
                        <span>{{ __('admin.showing') }} <strong>{{ $validationRequests->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $validationRequests->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalValidationRequests }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($validationRequests->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $validationRequests->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($validationRequests->getUrlRange(1, $validationRequests->lastPage()) as $page => $url)
                                @if ($page === $validationRequests->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($validationRequests->hasMorePages())<a href="{{ $validationRequests->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal ged-validation-modal" id="validationCircuitModal" tabindex="-1" aria-labelledby="validationCircuitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-validation-form" method="POST" action="{{ $circuitFormAction }}" data-create-action="{{ route('main.document-management.validation-circuits.store', [$company, $site]) }}" data-title-create="{{ __('main.ged_new_validation_circuit') }}" data-title-edit="{{ __('main.ged_edit_validation_circuit') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="circuitHttpMethod" value="PUT" @disabled(! $isEditingCircuit)>
                <input type="hidden" name="form_mode" id="circuitFormMode" value="{{ $isEditingCircuit ? 'edit' : 'create' }}">
                <input type="hidden" name="circuit_id" id="circuitId" value="{{ old('circuit_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="validationCircuitModalLabel"><i class="bi bi-check2-square" aria-hidden="true"></i>{{ $isEditingCircuit ? __('main.ged_edit_validation_circuit') : __('main.ged_new_validation_circuit') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="name">{{ __('main.ged_validation_circuit_name') }} *</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.ged_validation_circuit_name_placeholder') }}" data-circuit-field data-default-value="">
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="document_type">{{ __('main.ged_validation_document_type') }} *</label>
                                <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" data-circuit-field data-default-value="all">
                                    @foreach ($documentTypeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('document_type', 'all') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('document_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="service_owner">{{ __('main.ged_service_owner') }}</label>
                                <input id="service_owner" name="service_owner" class="form-control @error('service_owner') is-invalid @enderror" value="{{ old('service_owner') }}" placeholder="{{ __('main.ged_service_owner_placeholder') }}" data-circuit-field data-default-value="">
                                @error('service_owner')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">{{ __('main.status') }} *</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" data-circuit-field data-default-value="active">
                                    @foreach ($circuitStatusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">{{ __('main.description') }}</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('main.ged_validation_description_placeholder') }}" data-circuit-field data-default-value="">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-type-panel ged-validation-steps-panel">
                        <h3>{{ __('main.ged_validation_steps') }}</h3>
                        @for ($index = 0; $index < 3; $index++)
                            <div class="ged-validation-step-row" data-step-index="{{ $index }}">
                                <div>
                                    <label class="form-label" for="step_names_{{ $index }}">{{ __('main.ged_validation_step') }} {{ $index + 1 }} {{ $index === 0 ? '*' : '' }}</label>
                                    <input id="step_names_{{ $index }}" name="step_names[]" class="form-control @error('step_names.'.$index) is-invalid @enderror" value="{{ old('step_names.'.$index) }}" placeholder="{{ __('main.ged_validation_step_name_placeholder') }}" data-step-name>
                                    @error('step_names.'.$index)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="step_role_names_{{ $index }}">{{ __('main.ged_validation_role') }}</label>
                                    <input id="step_role_names_{{ $index }}" name="step_role_names[]" class="form-control" value="{{ old('step_role_names.'.$index) }}" placeholder="{{ __('main.ged_validation_role_placeholder') }}" data-step-role>
                                </div>
                                <div>
                                    <label class="form-label" for="step_validator_ids_{{ $index }}">{{ __('main.ged_validator') }}</label>
                                    <select id="step_validator_ids_{{ $index }}" name="step_validator_ids[]" class="form-select" data-step-validator>
                                        <option value="">{{ __('main.ged_select_assignee') }}</option>
                                        @foreach ($assignees as $assignee)
                                            <option value="{{ $assignee->id }}" @selected((string) old('step_validator_ids.'.$index) === (string) $assignee->id)>{{ $assignee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="step_due_days_{{ $index }}">{{ __('main.ged_due_days') }}</label>
                                    <input id="step_due_days_{{ $index }}" name="step_due_days[]" type="number" min="0" max="365" class="form-control" value="{{ old('step_due_days.'.$index) }}" placeholder="0" data-step-due>
                                </div>
                            </div>
                        @endfor
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="circuitSubmit">{{ $isEditingCircuit ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal ged-validation-modal" id="validationRequestModal" tabindex="-1" aria-labelledby="validationRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-validation-form" method="POST" action="{{ route('main.document-management.validation-requests.store', [$company, $site]) }}" novalidate>
                @csrf
                <input type="hidden" name="validation_form_mode" value="launch">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="validationRequestModalLabel"><i class="bi bi-send-check" aria-hidden="true"></i>{{ __('main.ged_start_validation') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="document_management_record_id">{{ __('main.ged_document') }} *</label>
                                <select id="document_management_record_id" name="document_management_record_id" class="form-select @error('document_management_record_id') is-invalid @enderror" required>
                                    <option value="">{{ __('main.ged_select_document') }}</option>
                                    @foreach ($documentsForValidation as $documentForValidation)
                                        <option value="{{ $documentForValidation->id }}" @selected((string) old('document_management_record_id') === (string) $documentForValidation->id)>
                                            {{ $documentForValidation->reference }} - {{ $documentForValidation->subject }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('document_management_record_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="document_management_validation_circuit_id">{{ __('main.ged_validation_circuit') }} *</label>
                                <select id="document_management_validation_circuit_id" name="document_management_validation_circuit_id" class="form-select @error('document_management_validation_circuit_id') is-invalid @enderror" required>
                                    <option value="">{{ __('main.ged_select_validation_circuit') }}</option>
                                    @foreach ($circuitsForValidation as $circuitForValidation)
                                        <option value="{{ $circuitForValidation->id }}" @selected((string) old('document_management_validation_circuit_id') === (string) $circuitForValidation->id)>
                                            {{ $circuitForValidation->reference }} - {{ $circuitForValidation->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('document_management_validation_circuit_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="validation_request_comment">{{ __('main.comment') }}</label>
                                <textarea id="validation_request_comment" name="comment" class="form-control @error('comment') is-invalid @enderror" rows="3" placeholder="{{ __('main.ged_validation_request_comment_placeholder') }}">{{ old('comment') }}</textarea>
                                @error('comment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" @disabled($circuitsForValidation->isEmpty() || $documentsForValidation->isEmpty())>{{ __('main.ged_start_validation') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal ged-validation-modal" id="validationRequestActionModal" tabindex="-1" aria-labelledby="validationRequestActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form ged-validation-form" id="validationRequestActionForm" method="POST" action="#" novalidate>
                @csrf
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="validationRequestActionModalLabel"><i class="bi bi-check2-square" aria-hidden="true"></i><span data-validation-action-title>{{ __('main.ged_process_validation') }}</span></h2>

                    <section class="client-type-panel">
                        <label class="form-label" for="validation_action_comment">{{ __('main.comment') }}</label>
                        <textarea id="validation_action_comment" name="comment" class="form-control" rows="4" placeholder="{{ __('main.ged_validation_action_comment_placeholder') }}"></textarea>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="validationRequestActionSubmit">{{ __('main.ged_process_validation') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('validationCircuitModal');
            const form = modal?.querySelector('.ged-validation-form');
            const title = document.getElementById('validationCircuitModalLabel');
            const method = document.getElementById('circuitHttpMethod');
            const mode = document.getElementById('circuitFormMode');
            const id = document.getElementById('circuitId');
            const submit = document.getElementById('circuitSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-circuit-field]')) : [];
            const stepRows = form ? Array.from(form.querySelectorAll('[data-step-index]')) : [];

            if (!modal || !form) return;

            const resetSteps = () => {
                stepRows.forEach((row) => {
                    row.querySelector('[data-step-name]').value = '';
                    row.querySelector('[data-step-role]').value = '';
                    row.querySelector('[data-step-validator]').value = '';
                    row.querySelector('[data-step-due]').value = '';
                });
            };

            const setMode = (button) => {
                const isEdit = button?.dataset.circuitMode === 'edit';
                form.action = isEdit ? button.dataset.circuitAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.circuitId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });
                resetSteps();

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.circuitValues || 'e30='));
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                        field.value = values[field.name] ?? '';
                    }
                });

                (values.steps || []).forEach((step, index) => {
                    const row = stepRows[index];
                    if (!row) return;

                    row.querySelector('[data-step-name]').value = step.name || '';
                    row.querySelector('[data-step-role]').value = step.role_name || '';
                    row.querySelector('[data-step-validator]').value = step.validator_id || '';
                    row.querySelector('[data-step-due]').value = step.due_days ?? '';
                });
            };

            modal.addEventListener('show.bs.modal', (event) => {
                if (!event.relatedTarget && form.dataset.hasErrors === '1') return;
                setMode(event.relatedTarget);
            });

            @if ($errors->any() && old('validation_form_mode') !== 'launch')
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();

        (() => {
            const actionModal = document.getElementById('validationRequestActionModal');
            const actionForm = document.getElementById('validationRequestActionForm');
            const actionTitle = actionModal?.querySelector('[data-validation-action-title]');
            const actionSubmit = document.getElementById('validationRequestActionSubmit');
            const actionComment = document.getElementById('validation_action_comment');

            if (!actionModal || !actionForm || !actionTitle || !actionSubmit) return;

            actionModal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                actionForm.action = button?.dataset.validationAction || '#';
                actionTitle.textContent = button?.dataset.validationTitle || '{{ __('main.ged_process_validation') }}';
                actionSubmit.textContent = button?.dataset.validationSubmit || '{{ __('main.ged_process_validation') }}';
                actionSubmit.classList.toggle('ged-validation-submit-reject', button?.dataset.validationTone === 'reject');
                actionComment.value = '';
            });
        })();

        (() => {
            const tabs = Array.from(document.querySelectorAll('[data-ged-validation-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-ged-validation-panel]'));

            if (!tabs.length || !panels.length) return;

            const activateTab = (tabName, updateHash = true) => {
                const activeName = tabName === 'requests' ? 'requests' : 'circuits';

                tabs.forEach((tab) => {
                    tab.classList.toggle('active', tab.dataset.gedValidationTab === activeName);
                    tab.setAttribute('aria-current', tab.dataset.gedValidationTab === activeName ? 'page' : 'false');
                });

                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.gedValidationPanel !== activeName;
                });

                if (updateHash) {
                    const target = activeName === 'requests' ? '#validationRequestsTable' : '#validationCircuitsTable';
                    history.replaceState(null, '', `${location.pathname}${location.search}${target}`);
                }
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', (event) => {
                    event.preventDefault();
                    activateTab(tab.dataset.gedValidationTab);
                });
            });

            activateTab(location.hash === '#validationRequestsTable' ? 'requests' : 'circuits', false);
        })();

        @if ($errors->any() && old('validation_form_mode') === 'launch')
            bootstrap.Modal.getOrCreateInstance(document.getElementById('validationRequestModal')).show();
        @endif
    </script>
</body>
</html>

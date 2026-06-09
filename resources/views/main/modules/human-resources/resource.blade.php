<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $resourceMeta['title'] }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalRecords = $records->total();
        $resourcePayload = fn ($record) => [
            'human_resource_employee_id' => $record->human_resource_employee_id,
            'reference' => $record->reference,
            'title' => $record->title,
            'category' => $record->category,
            'status' => $record->status,
            'date_from' => optional($record->date_from)->format('Y-m-d'),
            'date_to' => optional($record->date_to)->format('Y-m-d'),
            'amount' => $record->amount === null ? '' : number_format((float) $record->amount, 2, '.', ''),
            'currency' => $record->currency ?: 'USD',
            'score' => $record->score === null ? '' : number_format((float) $record->score, 2, '.', ''),
            'notes' => $record->notes,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => $resourceKey])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ $resourceMeta['title'] }}</h1>
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
                        <h1>{{ $resourceMeta['title'] }}</h1>
                        <p>{{ $resourceMeta['subtitle'] }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#hrResourceModal" data-resource-mode="create">
                        <i class="bi {{ $resourceMeta['icon'] }}" aria-hidden="true"></i>
                        {{ $resourceMeta['new'] }}
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
                        <table class="company-table hr-resource-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.record_title') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @if ($resourceMeta['show_employee'])
                                        <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.hr_employee') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @endif
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.category') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @if ($resourceMeta['show_amount'])
                                        <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.amount') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @endif
                                    @if ($resourceMeta['show_score'])
                                        <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.score') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @endif
                                    <th>{{ __('main.status') }}</th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr>
                                        <td>{{ $record->reference }}</td>
                                        <td><strong>{{ $record->title }}</strong></td>
                                        @if ($resourceMeta['show_employee'])
                                            <td>
                                                {{ $record->employee?->full_name ?? '-' }}
                                                @if ($record->employee?->department)
                                                    <small class="d-block text-muted">{{ $record->employee->department->name }}</small>
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ $record->category ?: '-' }}</td>
                                        <td>{{ $record->date_from?->format('d/m/Y') ?? '-' }} @if ($record->date_to) - {{ $record->date_to->format('d/m/Y') }} @endif</td>
                                        @if ($resourceMeta['show_amount'])
                                            <td class="text-end" data-sort-value="{{ $record->amount ?? 0 }}">{{ $record->amount === null ? '-' : number_format((float) $record->amount, 2, ',', ' ').' '.($record->currency ?: 'USD') }}</td>
                                        @endif
                                        @if ($resourceMeta['show_score'])
                                            <td class="text-end" data-sort-value="{{ $record->score ?? 0 }}">{{ $record->score === null ? '-' : number_format((float) $record->score, 2, ',', ' ') }}</td>
                                        @endif
                                        <td><span class="status-pill hr-resource-status">{{ $record->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#hrResourceModal" data-resource-mode="edit" data-resource-action="{{ route('main.human-resources.resources.update', [$company, $site, $resourceKey, $record]) }}" data-resource-id="{{ $record->id }}" data-resource-values="{{ base64_encode(json_encode($resourcePayload($record))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.human-resources.resources.destroy', [$company, $site, $resourceKey, $record]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.hr_delete_resource_title') }}" data-delete-text="{{ __('main.hr_delete_resource_text', ['reference' => $record->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.hr_no_records') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
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

    <div class="modal fade subscription-modal hr-resource-modal" id="hrResourceModal" tabindex="-1" aria-labelledby="hrResourceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-resource-form" method="POST" action="{{ $resourceFormAction }}" data-create-action="{{ route('main.human-resources.resources.store', [$company, $site, $resourceKey]) }}" data-title-create="{{ $resourceMeta['new'] }}" data-title-edit="{{ __('main.hr_edit_resource') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasResourceErrors ? '1' : '0' }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="resourceHttpMethod" value="PUT" @disabled(! $isEditingResource)>
                <input type="hidden" name="form_mode" id="resourceFormMode" value="{{ $isEditingResource ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="resourceId" value="{{ old('record_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="hrResourceModalLabel"><i class="bi {{ $resourceMeta['icon'] }}" aria-hidden="true"></i>{{ $isEditingResource ? __('main.hr_edit_resource') : $resourceMeta['new'] }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            @if ($resourceMeta['show_employee'])
                                <div class="col-md-8">
                                    <label for="human_resource_employee_id" class="form-label">{{ __('main.hr_employee') }}</label>
                                    <select id="human_resource_employee_id" name="human_resource_employee_id" class="form-select @error('human_resource_employee_id') is-invalid @enderror" data-resource-field data-default-value="">
                                        <option value="">{{ __('main.select') }}</option>
                                        @foreach ($employeeOptions as $employee)
                                            <option value="{{ $employee->id }}" @selected((string) old('human_resource_employee_id') === (string) $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                                        @endforeach
                                    </select>
                                    @error('human_resource_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            <div class="{{ $resourceMeta['show_employee'] ? 'col-md-4' : 'col-md-12' }}">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.automatic_if_empty') }}" data-resource-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label for="title" class="form-label">{{ __('main.record_title') }} *</label>
                                <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" data-resource-field data-default-value="">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">{{ __('main.category') }}</label>
                                <input id="category" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" data-resource-field data-default-value="">
                                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">{{ $resourceMeta['date_from_label'] }}</label>
                                <input id="date_from" name="date_from" type="date" class="form-control @error('date_from') is-invalid @enderror" value="{{ old('date_from') }}" data-resource-field data-default-value="">
                                @error('date_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">{{ $resourceMeta['date_to_label'] }}</label>
                                <input id="date_to" name="date_to" type="date" class="form-control @error('date_to') is-invalid @enderror" value="{{ old('date_to') }}" data-resource-field data-default-value="">
                                @error('date_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">{{ __('main.status') }} *</label>
                                <input id="status" name="status" class="form-control @error('status') is-invalid @enderror" value="{{ old('status', 'active') }}" data-resource-field data-default-value="active">
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            @if ($resourceMeta['show_amount'])
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">{{ __('main.amount') }}</label>
                                    <input id="amount" name="amount" type="number" min="0" step="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" data-resource-field data-default-value="">
                                    @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="currency" class="form-label">{{ __('main.currency') }}</label>
                                    <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-resource-field data-default-value="USD">
                                        @foreach ($currencyOptions as $code => $label)
                                            <option value="{{ $code }}" @selected(old('currency', 'USD') === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            @if ($resourceMeta['show_score'])
                                <div class="col-md-6">
                                    <label for="score" class="form-label">{{ __('main.score') }}</label>
                                    <input id="score" name="score" type="number" min="0" max="100" step="0.01" class="form-control @error('score') is-invalid @enderror" value="{{ old('score') }}" data-resource-field data-default-value="">
                                    @error('score')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            <div class="col-12">
                                <label for="notes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" data-resource-field data-default-value="">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="resourceSubmit">{{ $isEditingResource ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('hrResourceModal');
            const form = modal?.querySelector('.hr-resource-form');
            const title = document.getElementById('hrResourceModalLabel');
            const method = document.getElementById('resourceHttpMethod');
            const mode = document.getElementById('resourceFormMode');
            const id = document.getElementById('resourceId');
            const submit = document.getElementById('resourceSubmit');
            const fields = form ? Array.from(form.querySelectorAll('[data-resource-field]')) : [];

            if (!modal || !form) return;

            const setMode = (button) => {
                const isEdit = button?.dataset.resourceMode === 'edit';
                form.action = isEdit ? button.dataset.resourceAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.resourceId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (!isEdit) return;

                const values = JSON.parse(atob(button.dataset.resourceValues || 'e30='));
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

            @if ($hasResourceErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

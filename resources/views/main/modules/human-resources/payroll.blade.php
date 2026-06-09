<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.hr_payroll') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body human-resources-module-body">
    @php
        $totalPayrollEntries = $payrollEntries->total();
        $payrollPayload = fn ($entry) => [
            'human_resource_employee_id' => $entry->human_resource_employee_id,
            'reference' => $entry->reference,
            'period_month' => optional($entry->period_month)->format('Y-m-d'),
            'payment_date' => optional($entry->payment_date)->format('Y-m-d'),
            'status' => $entry->status,
            'currency' => $entry->currency,
            'gross_salary' => number_format((float) $entry->gross_salary, 2, '.', ''),
            'allowances' => number_format((float) $entry->allowances, 2, '.', ''),
            'deductions' => number_format((float) $entry->deductions, 2, '.', ''),
            'notes' => $entry->notes,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell human-resources-shell" data-theme="light">
        @include('main.modules.human-resources.partials.sidebar', ['activeHumanResourcesPage' => 'payroll'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.hr_payroll') }}</h1>
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
                        <h1>{{ __('main.hr_payroll') }}</h1>
                        <p>{{ __('main.hr_payroll_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#payrollModal" data-payroll-mode="create">
                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                        {{ __('main.hr_new_payroll') }}
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
                        <strong id="visibleCount">{{ $payrollEntries->count() }}</strong>
                        /
                        <strong>{{ $totalPayrollEntries }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table hr-payroll-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.hr_employee') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.period') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="3" data-sort-type="number">{{ __('main.hr_gross_salary') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.hr_net_salary') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payrollEntries as $entry)
                                    <tr>
                                        <td>{{ $entry->reference }}</td>
                                        <td>
                                            <strong>{{ $entry->employee?->full_name ?? '-' }}</strong>
                                            @if ($entry->employee?->department)
                                                <small class="d-block text-muted">{{ $entry->employee->department->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ optional($entry->period_month)->translatedFormat('F Y') }}</td>
                                        <td class="text-end" data-sort-value="{{ $entry->gross_salary }}">{{ number_format((float) $entry->gross_salary, 2, ',', ' ') }} {{ $entry->currency }}</td>
                                        <td class="text-end" data-sort-value="{{ $entry->net_salary }}">{{ number_format((float) $entry->net_salary, 2, ',', ' ') }} {{ $entry->currency }}</td>
                                        <td><span class="status-pill hr-payroll-status-{{ $entry->status }}">{{ $payrollStatuses[$entry->status] ?? $entry->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#payrollModal" data-payroll-mode="edit" data-payroll-action="{{ route('main.human-resources.payroll.update', [$company, $site, $entry]) }}" data-payroll-id="{{ $entry->id }}" data-payroll-values="{{ base64_encode(json_encode($payrollPayload($entry))) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.human-resources.payroll.destroy', [$company, $site, $entry]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.hr_delete_payroll_title') }}" data-delete-text="{{ __('main.hr_delete_payroll_text', ['reference' => $entry->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
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

                @if ($payrollEntries->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $payrollEntries->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $payrollEntries->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalPayrollEntries }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($payrollEntries->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $payrollEntries->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($payrollEntries->getUrlRange(1, $payrollEntries->lastPage()) as $page => $url)
                                @if ($page === $payrollEntries->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($payrollEntries->hasMorePages())<a href="{{ $payrollEntries->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal hr-payroll-modal" id="payrollModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form hr-payroll-form" method="POST" action="{{ $payrollFormAction }}" data-create-action="{{ route('main.human-resources.payroll.store', [$company, $site]) }}" data-title-create="{{ __('main.hr_new_payroll') }}" data-title-edit="{{ __('main.hr_edit_payroll') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-has-errors="{{ $hasPayrollErrors ? '1' : '0' }}" data-employee-defaults='@json($employeePayrollDefaults)' novalidate>
                @csrf
                <input type="hidden" name="_method" id="payrollHttpMethod" value="PUT" @disabled(! $isEditingPayroll)>
                <input type="hidden" name="form_mode" id="payrollFormMode" value="{{ $isEditingPayroll ? 'edit' : 'create' }}">
                <input type="hidden" name="payroll_id" id="payrollId" value="{{ old('payroll_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="payrollModalLabel"><i class="bi bi-cash-stack" aria-hidden="true"></i>{{ $isEditingPayroll ? __('main.hr_edit_payroll') : __('main.hr_new_payroll') }}</h2>

                    <section class="client-type-panel">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="human_resource_employee_id" class="form-label">{{ __('main.hr_employee') }} *</label>
                                <select id="human_resource_employee_id" name="human_resource_employee_id" class="form-select @error('human_resource_employee_id') is-invalid @enderror" data-payroll-field data-default-value="">
                                    <option value="">{{ __('main.select') }}</option>
                                    @foreach ($employeeOptions as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) old('human_resource_employee_id') === (string) $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}</option>
                                    @endforeach
                                </select>
                                @error('human_resource_employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="reference" class="form-label">{{ __('main.reference') }}</label>
                                <input id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="{{ __('main.hr_payroll_reference_placeholder') }}" data-payroll-field data-default-value="">
                                @error('reference')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-calendar-month" aria-hidden="true"></i> {{ __('main.hr_payroll_period_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="period_month" class="form-label">{{ __('main.period') }} *</label>
                                <input id="period_month" name="period_month" type="date" class="form-control @error('period_month') is-invalid @enderror" value="{{ old('period_month', $currentPeriod) }}" data-payroll-field data-default-value="{{ $currentPeriod }}">
                                @error('period_month')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="payment_date" class="form-label">{{ __('main.payment_date') }}</label>
                                <input id="payment_date" name="payment_date" type="date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date') }}" data-payroll-field data-default-value="">
                                @error('payment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="payroll_status" class="form-label">{{ __('main.status') }} *</label>
                                <select id="payroll_status" name="status" class="form-select @error('status') is-invalid @enderror" data-payroll-field data-default-value="{{ \App\Models\HumanResourcePayrollEntry::STATUS_DRAFT }}">
                                    @foreach ($payrollStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', \App\Models\HumanResourcePayrollEntry::STATUS_DRAFT) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="client-contacts-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-calculator" aria-hidden="true"></i> {{ __('main.hr_payroll_amount_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="currency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-payroll-field data-default-value="USD">
                                    @foreach ($currencyOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('currency', 'USD') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="gross_salary" class="form-label">{{ __('main.hr_gross_salary') }} *</label>
                                <input id="gross_salary" name="gross_salary" type="number" min="0" step="0.01" class="form-control @error('gross_salary') is-invalid @enderror" value="{{ old('gross_salary', '0.00') }}" data-payroll-field data-default-value="0.00">
                                @error('gross_salary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="allowances" class="form-label">{{ __('main.hr_allowances') }}</label>
                                <input id="allowances" name="allowances" type="number" min="0" step="0.01" class="form-control @error('allowances') is-invalid @enderror" value="{{ old('allowances', '0.00') }}" data-payroll-field data-default-value="0.00">
                                @error('allowances')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="deductions" class="form-label">{{ __('main.hr_deductions') }}</label>
                                <input id="deductions" name="deductions" type="number" min="0" step="0.01" class="form-control @error('deductions') is-invalid @enderror" value="{{ old('deductions', '0.00') }}" data-payroll-field data-default-value="0.00">
                                @error('deductions')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('main.hr_net_salary') }}</label>
                                <div class="payroll-net-preview" id="payrollNetPreview">0.00</div>
                            </div>
                            <div class="col-md-8">
                                <label for="payroll_notes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="payroll_notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('main.notes') }}" data-payroll-field data-default-value="">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="secondary-action" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="primary-action" id="payrollSubmit">{{ $isEditingPayroll ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modal = document.getElementById('payrollModal');
            const form = modal?.querySelector('.hr-payroll-form');
            const title = document.getElementById('payrollModalLabel');
            const method = document.getElementById('payrollHttpMethod');
            const mode = document.getElementById('payrollFormMode');
            const id = document.getElementById('payrollId');
            const submit = document.getElementById('payrollSubmit');
            const employee = document.getElementById('human_resource_employee_id');
            const gross = document.getElementById('gross_salary');
            const allowances = document.getElementById('allowances');
            const deductions = document.getElementById('deductions');
            const currency = document.getElementById('currency');
            const netPreview = document.getElementById('payrollNetPreview');
            const fields = form ? Array.from(form.querySelectorAll('[data-payroll-field]')) : [];
            const defaults = form ? JSON.parse(form.dataset.employeeDefaults || '{}') : {};

            if (!modal || !form) return;

            const amount = (input) => Number.parseFloat(input?.value || '0') || 0;
            const updateNet = () => {
                const net = Math.max(0, amount(gross) + amount(allowances) - amount(deductions));
                netPreview.textContent = net.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            const applyEmployeeDefaults = () => {
                const values = defaults[employee.value];
                if (!values) return;
                if (!gross.value || gross.value === '0.00' || gross.value === '0') gross.value = values.gross_salary || '0.00';
                if (values.currency) currency.value = values.currency;
                updateNet();
            };

            const setMode = (button) => {
                const isEdit = button?.dataset.payrollMode === 'edit';
                form.action = isEdit ? button.dataset.payrollAction : form.dataset.createAction;
                title.lastChild.textContent = isEdit ? form.dataset.titleEdit : form.dataset.titleCreate;
                submit.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
                mode.value = isEdit ? 'edit' : 'create';
                id.value = isEdit ? button.dataset.payrollId : '';
                method.disabled = !isEdit;

                fields.forEach((field) => {
                    field.value = field.dataset.defaultValue || '';
                });

                if (isEdit) {
                    const values = JSON.parse(atob(button.dataset.payrollValues || 'e30='));
                    fields.forEach((field) => {
                        if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                            field.value = values[field.name] ?? '';
                        }
                    });
                } else {
                    applyEmployeeDefaults();
                }

                updateNet();
            };

            modal.addEventListener('show.bs.modal', (event) => {
                if (!event.relatedTarget && form.dataset.hasErrors === '1') return;
                setMode(event.relatedTarget);
            });
            employee.addEventListener('change', applyEmployeeDefaults);
            [gross, allowances, deductions].forEach((input) => input.addEventListener('input', updateNet));
            updateNet();

            @if ($hasPayrollErrors)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        })();
    </script>
</body>
</html>

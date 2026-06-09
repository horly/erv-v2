<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.taxes') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRecords = $taxes->total();
        $currencySuffix = $site->currency ?: 'CDF';
        $hasTaxErrors = $errors->any();
        $isEditingTax = old('form_mode') === 'edit' && old('tax_id');
        $formAction = $isEditingTax
            ? route('main.accounting.taxes.update', [$company, $site, old('tax_id')])
            : route('main.accounting.taxes.store', [$company, $site]);
        $taxPayload = fn ($tax) => [
            'name' => $tax->name,
            'code' => $tax->code,
            'kind' => $tax->kind,
            'calculation_type' => $tax->calculation_type,
            'value' => number_format((float) $tax->value, 2, '.', ''),
            'nature' => $tax->nature,
            'applies_to' => $tax->applies_to,
            'description' => $tax->description,
            'is_default' => $tax->is_default ? '1' : '0',
            'status' => $tax->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'taxes'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.taxes')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.taxes') }}</h1>
                        <p>{{ __('main.taxes_subtitle') }}</p>
                    </div>
                    @if ($taxPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#taxModal" data-tax-mode="create">
                            <i class="bi bi-percent" aria-hidden="true"></i>
                            {{ __('main.new_tax') }}
                        </button>
                    @endif
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-exclamation-circle' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <div class="modal-total-strip tax-information-strip">
                    <span><i class="bi bi-info-circle" aria-hidden="true"></i> {{ __('main.tax_default_notice') }}</span>
                    <strong>{{ __('main.tax_snapshot_notice') }}</strong>
                </div>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $taxes->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table tax-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.internal_code') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.tax') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.tax_kind') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.tax_value') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.tax_applies_to') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.tax_default') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($taxes as $tax)
                                    <tr>
                                        <td>{{ ($taxes->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>{{ $tax->code ?: $tax->reference }}</td>
                                        <td>
                                            <strong>{{ $tax->name }}</strong>
                                            @if ($tax->description)
                                                <small class="d-block text-muted">{{ $tax->description }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $kindLabels[$tax->kind] ?? $tax->kind }}</td>
                                        <td class="text-end" data-sort-value="{{ (float) $tax->value }}">
                                            {{ number_format((float) $tax->value, 2, ',', ' ') }}
                                            {{ $tax->calculation_type === \App\Models\AccountingTax::CALCULATION_PERCENTAGE ? '%' : $currencySuffix }}
                                        </td>
                                        <td>{{ $applicationLabels[$tax->applies_to] ?? $tax->applies_to }}</td>
                                        <td>
                                            <span class="status-pill {{ $tax->is_default ? 'tax-status-default' : 'tax-status-secondary' }}">
                                                {{ $tax->is_default ? __('main.yes') : __('main.no') }}
                                            </span>
                                        </td>
                                        <td><span class="status-pill tax-status-{{ $tax->status }}">{{ $statusLabels[$tax->status] ?? $tax->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($taxPermissions['can_update'])
                                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#taxModal" data-tax-mode="edit" data-tax-action="{{ route('main.accounting.taxes.update', [$company, $site, $tax]) }}" data-tax-id="{{ $tax->id }}" data-tax-values="{{ base64_encode(json_encode($taxPayload($tax))) }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($taxPermissions['can_delete'] && ! $tax->is_default && ! $tax->is_system_default)
                                                    <form method="POST" action="{{ route('main.accounting.taxes.destroy', [$company, $site, $tax]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_tax_title') }}" data-delete-text="{{ __('main.delete_tax_text', ['name' => $tax->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.no_taxes') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($taxes->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $taxes->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $taxes->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($taxes->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $taxes->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($taxes->getUrlRange(1, $taxes->lastPage()) as $page => $url)
                                @if ($page === $taxes->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($taxes->hasMorePages())<a href="{{ $taxes->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-tax-modal" id="taxModal" tabindex="-1" aria-labelledby="taxModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form tax-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.accounting.taxes.store', [$company, $site]) }}" data-title-create="{{ __('main.new_tax') }}" data-title-edit="{{ __('main.edit_tax') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="taxHttpMethod" value="PUT" @disabled(! $isEditingTax)>
                <input type="hidden" name="form_mode" id="taxFormMode" value="{{ $isEditingTax ? 'edit' : 'create' }}">
                <input type="hidden" name="tax_id" id="taxId" value="{{ old('tax_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="taxModalLabel"><i class="bi bi-percent" aria-hidden="true"></i>{{ $isEditingTax ? __('main.edit_tax') : __('main.new_tax') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="tax_name" class="form-label">{{ __('main.tax') }} *</label>
                            <input id="tax_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.tax_name_placeholder') }}" data-tax-field data-default-value="">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="tax_code" class="form-label">{{ __('main.internal_code') }}</label>
                            <input id="tax_code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="{{ __('main.tax_code_placeholder') }}" data-tax-field data-default-value="">
                            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tax_kind" class="form-label">{{ __('main.tax_kind') }} *</label>
                            <select id="tax_kind" name="kind" class="form-select @error('kind') is-invalid @enderror" data-tax-field data-default-value="{{ \App\Models\AccountingTax::KIND_VAT }}">
                                @foreach ($kindLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('kind', \App\Models\AccountingTax::KIND_VAT) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('kind')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tax_calculation_type" class="form-label">{{ __('main.tax_calculation_type') }} *</label>
                            <select id="tax_calculation_type" name="calculation_type" class="form-select @error('calculation_type') is-invalid @enderror" data-tax-field data-default-value="{{ \App\Models\AccountingTax::CALCULATION_PERCENTAGE }}">
                                @foreach ($calculationTypeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('calculation_type', \App\Models\AccountingTax::CALCULATION_PERCENTAGE) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('calculation_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tax_value" class="form-label">{{ __('main.tax_value') }} *</label>
                            <input id="tax_value" name="value" type="number" min="0" step="0.01" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', '0.00') }}" placeholder="{{ __('main.tax_value_placeholder') }}" data-tax-field data-default-value="0.00">
                            @error('value')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tax_nature" class="form-label">{{ __('main.tax_fiscal_nature') }} *</label>
                            <select id="tax_nature" name="nature" class="form-select @error('nature') is-invalid @enderror" data-tax-field data-default-value="{{ \App\Models\AccountingTax::NATURE_COLLECTED }}">
                                @foreach ($natureLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('nature', \App\Models\AccountingTax::NATURE_COLLECTED) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('nature')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tax_applies_to" class="form-label">{{ __('main.tax_applies_to') }} *</label>
                            <select id="tax_applies_to" name="applies_to" class="form-select @error('applies_to') is-invalid @enderror" data-tax-field data-default-value="{{ \App\Models\AccountingTax::APPLIES_BOTH }}">
                                @foreach ($applicationLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('applies_to', \App\Models\AccountingTax::APPLIES_BOTH) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('applies_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tax_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="tax_status" name="status" class="form-select @error('status') is-invalid @enderror" data-tax-field data-default-value="{{ \App\Models\AccountingTax::STATUS_ACTIVE }}">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', \App\Models\AccountingTax::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="form-check form-switch form-toggle">
                                <input type="hidden" name="is_default" value="0">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" @checked(old('is_default') === '1') data-tax-field data-default-value="0">
                                <span>{{ __('main.set_as_default') }}</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label for="tax_description" class="form-label">{{ __('main.description') }}</label>
                            <textarea id="tax_description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.tax_description_placeholder') }}" data-tax-field data-default-value="">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <p class="form-help mt-3">{{ __('main.tax_snapshot_notice') }}</p>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" id="taxCancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="taxSubmit" type="submit">{{ $isEditingTax ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasTaxErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('taxModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-taxes.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-taxes.js')) !!}</script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.suppliers') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalSuppliers = $suppliers->total();
        $hasSupplierErrors = $errors->any();
        $isEditingSupplier = old('form_mode') === 'edit' && old('supplier_id');
        $supplierFormAction = $isEditingSupplier
            ? route('main.accounting.suppliers.update', [$company, $site, old('supplier_id')])
            : route('main.accounting.suppliers.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingSupplier::TYPE_INDIVIDUAL);
        $oldStatus = old('status', \App\Models\AccountingSupplier::STATUS_ACTIVE);
        $oldContacts = old('contacts', [['full_name' => '', 'position' => '', 'department' => '', 'email' => '', 'phone' => '']]);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'suppliers'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.suppliers') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                <div class="header-actions">
                    <button class="icon-button" type="button" id="themeButton" aria-label="{{ __('auth.theme_dark') }}" title="{{ __('auth.theme_dark') }}">
                        <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
                    </button>
                    <div class="language-menu">
                        <button class="language-button" type="button" id="languageButton" aria-label="{{ __('auth.language_switch') }}" aria-expanded="false" aria-controls="languageDropdown" title="{{ __('auth.language_switch') }}">
                            <i class="bi bi-globe2" aria-hidden="true"></i>
                            <span>{{ strtoupper($currentLocale) }}</span>
                            <i class="bi bi-chevron-down language-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="language-dropdown" id="languageDropdown" aria-labelledby="languageButton">
                            <a class="language-option {{ $currentLocale === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}">
                                <span class="language-code">FR</span>
                                <span class="language-name">{{ __('auth.language_fr') }}</span>
                                @if ($currentLocale === 'fr')
                                    <i class="bi bi-check-lg language-check" aria-hidden="true"></i>
                                @endif
                            </a>
                            <a class="language-option {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">
                                <span class="language-code">EN</span>
                                <span class="language-name">{{ __('auth.language_en') }}</span>
                                @if ($currentLocale === 'en')
                                    <i class="bi bi-check-lg language-check" aria-hidden="true"></i>
                                @endif
                            </a>
                        </div>
                    </div>
                    <div class="profile-menu">
                        <button class="profile-button" type="button" id="profileButton" aria-expanded="false" aria-controls="profileDropdown">
                            @include('partials.user-avatar', ['avatarUser' => $user])
                            <span class="profile-name">{{ $user->name }}</span>
                            <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                            <div class="profile-summary">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                                <em>{{ $user->role === 'admin' ? __('main.admin_badge') : strtoupper($user->role) }}</em>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="profile-link">
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                                {{ __('main.profile') }}
                            </a>
                            @if ($user->isAdmin())
                                <a href="{{ route('main.users') }}" class="profile-link">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    {{ __('main.users') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="profile-link logout-link" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    {{ __('main.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.suppliers') }}</h1>
                        <p>{{ __('main.suppliers_subtitle') }}</p>
                    </div>
                    @if ($supplierPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#supplierModal" data-supplier-mode="create">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            {{ __('main.new_supplier') }}
                        </button>
                    @endif
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
                        <strong id="visibleCount">{{ $suppliers->count() }}</strong>
                        /
                        <strong>{{ $totalSuppliers }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table suppliers-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.supplier') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.phone') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.email') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($suppliers as $supplier)
                                    @php
                                        $supplierTypeLabel = $supplier->type === \App\Models\AccountingSupplier::TYPE_COMPANY ? __('main.supplier_company') : __('main.supplier_individual');
                                        $primaryPhone = $supplier->isCompany() ? ($supplier->contacts->first()?->phone ?? '-') : ($supplier->phone ?: '-');
                                        $primaryEmail = $supplier->isCompany() ? ($supplier->contacts->first()?->email ?? '-') : ($supplier->email ?: '-');
                                        $contactsPayload = $supplier->contacts->map(fn ($contact) => [
                                            'full_name' => $contact->full_name,
                                            'position' => $contact->position,
                                            'department' => $contact->department,
                                            'email' => $contact->email,
                                            'phone' => $contact->phone,
                                        ])->values();
                                    @endphp
                                    <tr>
                                        <td>{{ ($suppliers->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $supplier->reference }}</span></td>
                                        <td>
                                            <span class="supplier-name">
                                                <span class="supplier-icon supplier-icon-{{ $supplier->type }}">
                                                    <i class="bi {{ $supplier->isCompany() ? 'bi-building' : 'bi-person' }}" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $supplier->name }}</strong>
                                                    <small>{{ $supplier->isCompany() ? ($supplier->rccm ?: '-') : ($supplier->profession ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill supplier-type-{{ $supplier->type }}">{{ $supplierTypeLabel }}</span></td>
                                        <td>{{ $primaryPhone }}</td>
                                        <td>{{ $primaryEmail }}</td>
                                        <td><span class="status-pill {{ $supplier->status === \App\Models\AccountingSupplier::STATUS_ACTIVE ? 'status-active' : 'status-inactive' }}">{{ $supplier->status === \App\Models\AccountingSupplier::STATUS_ACTIVE ? __('main.active') : __('main.inactive') }}</span></td>
                                        <td>
                                            @if ($supplierPermissions['can_update'] || $supplierPermissions['can_delete'])
                                                <div class="table-actions">
                                                    @if ($supplierPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#supplierModal" data-supplier-mode="edit" data-supplier-action="{{ route('main.accounting.suppliers.update', [$company, $site, $supplier]) }}" data-supplier-id="{{ $supplier->id }}" data-supplier-type="{{ $supplier->type }}" data-supplier-name="{{ $supplier->name }}" data-supplier-profession="{{ $supplier->profession }}" data-supplier-phone="{{ $supplier->phone }}" data-supplier-email="{{ $supplier->email }}" data-supplier-address="{{ $supplier->address }}" data-supplier-rccm="{{ $supplier->rccm }}" data-supplier-id-nat="{{ $supplier->id_nat }}" data-supplier-nif="{{ $supplier->nif }}" data-supplier-bank-name="{{ $supplier->bank_name }}" data-supplier-account-number="{{ $supplier->account_number }}" data-supplier-currency="{{ $supplier->currency }}" data-supplier-website="{{ $supplier->website }}" data-supplier-status="{{ $supplier->status }}" data-supplier-contacts='@json($contactsPayload)' aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($supplierPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.suppliers.destroy', [$company, $site, $supplier]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_supplier_title') }}" data-delete-text="{{ __('main.delete_supplier_text', ['name' => $supplier->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="muted-dash">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_suppliers') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($suppliers->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $suppliers->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $suppliers->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalSuppliers }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($suppliers->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $suppliers->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($suppliers->getUrlRange(1, $suppliers->lastPage()) as $page => $url)
                                @if ($page === $suppliers->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($suppliers->hasMorePages())<a href="{{ $suppliers->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-supplier-modal" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form supplier-form" method="POST" action="{{ $supplierFormAction }}" data-create-action="{{ route('main.accounting.suppliers.store', [$company, $site]) }}" data-title-create="{{ __('main.new_supplier') }}" data-title-edit="{{ __('main.edit_supplier') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="supplierMethod" value="PUT" @disabled(! $isEditingSupplier)>
                <input type="hidden" name="form_mode" id="supplierFormMode" value="{{ $isEditingSupplier ? 'edit' : 'create' }}">
                <input type="hidden" name="supplier_id" id="supplierId" value="{{ old('supplier_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="supplierModalLabel"><i class="bi bi-person-plus" aria-hidden="true"></i>{{ $isEditingSupplier ? __('main.edit_supplier') : __('main.new_supplier') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="supplierType" class="form-label">{{ __('main.supplier_type') }} *</label>
                            <select id="supplierType" name="type" class="form-select @error('type') is-invalid @enderror" data-required-message="{{ __('validation.required', ['attribute' => __('main.supplier_type')]) }}">
                                <option value="{{ \App\Models\AccountingSupplier::TYPE_INDIVIDUAL }}" @selected($oldType === \App\Models\AccountingSupplier::TYPE_INDIVIDUAL)>{{ __('main.supplier_individual') }}</option>
                                <option value="{{ \App\Models\AccountingSupplier::TYPE_COMPANY }}" @selected($oldType === \App\Models\AccountingSupplier::TYPE_COMPANY)>{{ __('main.supplier_company') }}</option>
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.supplier_type')]) }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="supplierName" class="form-label" data-label-individual="{{ __('main.full_name') }} *" data-label-company="{{ __('main.company_name') }} *">{{ $oldType === \App\Models\AccountingSupplier::TYPE_COMPANY ? __('main.company_name') : __('main.full_name') }} *</label>
                            <input id="supplierName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.full_name') }}" data-placeholder-individual="{{ __('main.full_name') }}" data-placeholder-company="{{ __('main.company_name') }}" data-required-message="{{ __('validation.required', ['attribute' => __('main.name')]) }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.name')]) }}</div>@enderror
                        </div>
                    </div>

                    <div class="supplier-type-panel" data-supplier-panel="individual" @hidden($oldType !== \App\Models\AccountingSupplier::TYPE_INDIVIDUAL)>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="supplierProfession" class="form-label">{{ __('main.profession') }}</label>
                                <input id="supplierProfession" name="profession" type="text" class="form-control @error('profession') is-invalid @enderror" value="{{ old('profession') }}" placeholder="{{ __('main.profession') }}">
                                @error('profession')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="supplierPhone" class="form-label">{{ __('main.phone') }}</label>
                                <input id="supplierPhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="supplierEmail" class="form-label">{{ __('main.email') }}</label>
                                <input id="supplierEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="supplierAddress" class="form-label">{{ __('main.address') }}</label>
                                <input id="supplierAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="supplier-type-panel" data-supplier-panel="company" @hidden($oldType !== \App\Models\AccountingSupplier::TYPE_COMPANY)>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="supplierRccm" class="form-label">{{ __('admin.rccm') }}</label>
                                <input id="supplierRccm" name="rccm" type="text" class="form-control @error('rccm') is-invalid @enderror" value="{{ old('rccm') }}" placeholder="{{ __('admin.rccm') }}">
                                @error('rccm')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="supplierIdNat" class="form-label">{{ __('admin.id_nat') }}</label>
                                <input id="supplierIdNat" name="id_nat" type="text" class="form-control @error('id_nat') is-invalid @enderror" value="{{ old('id_nat') }}" placeholder="{{ __('admin.id_nat') }}">
                                @error('id_nat')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="supplierNif" class="form-label">{{ __('admin.nif') }}</label>
                                <input id="supplierNif" name="nif" type="text" class="form-control @error('nif') is-invalid @enderror" value="{{ old('nif') }}" placeholder="{{ __('admin.nif') }}">
                                @error('nif')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="supplierWebsite" class="form-label">{{ __('admin.website') }}</label>
                                <input id="supplierWebsite" name="website" type="text" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}" placeholder="{{ __('admin.website') }}">
                                @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="supplier-contacts-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-person-lines-fill" aria-hidden="true"></i> {{ __('main.supplier_contacts') }}</span>
                                <button type="button" class="light-action" data-add-supplier-contact>
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    {{ __('main.add_contact') }}
                                </button>
                            </div>
                            <div class="supplier-contact-list" data-supplier-contact-list>
                                @foreach ($oldContacts as $index => $contact)
                                    <div class="supplier-contact-card" data-supplier-contact-row>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('main.full_name') }} *</label>
                                                <input name="contacts[{{ $index }}][full_name]" type="text" class="form-control @error("contacts.$index.full_name") is-invalid @enderror" value="{{ $contact['full_name'] ?? '' }}" placeholder="{{ __('main.full_name') }}">
                                                @error("contacts.$index.full_name")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">{{ __('main.position_or_grade') }}</label>
                                                <input name="contacts[{{ $index }}][position]" type="text" class="form-control @error("contacts.$index.position") is-invalid @enderror" value="{{ $contact['position'] ?? '' }}" placeholder="{{ __('main.position_or_grade') }}">
                                                @error("contacts.$index.position")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-1 d-flex justify-content-end">
                                                <button type="button" class="icon-light-button" data-remove-supplier-contact aria-label="{{ __('admin.delete') }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('main.department') }}</label>
                                                <input name="contacts[{{ $index }}][department]" type="text" class="form-control @error("contacts.$index.department") is-invalid @enderror" value="{{ $contact['department'] ?? '' }}" placeholder="{{ __('main.department') }}">
                                                @error("contacts.$index.department")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('main.email') }}</label>
                                                <input name="contacts[{{ $index }}][email]" type="email" class="form-control @error("contacts.$index.email") is-invalid @enderror" value="{{ $contact['email'] ?? '' }}" placeholder="{{ __('main.email') }}">
                                                @error("contacts.$index.email")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('main.phone') }}</label>
                                                <input name="contacts[{{ $index }}][phone]" type="text" class="form-control @error("contacts.$index.phone") is-invalid @enderror" value="{{ $contact['phone'] ?? '' }}" placeholder="{{ __('main.phone') }}">
                                                @error("contacts.$index.phone")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>

                    <section class="supplier-bank-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-bank" aria-hidden="true"></i> {{ __('admin.account_numbers') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="supplierBankName" class="form-label">{{ __('admin.bank_name') }}</label>
                                <input id="supplierBankName" name="bank_name" type="text" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="{{ __('admin.bank_name') }}">
                                @error('bank_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="supplierAccountNumber" class="form-label">{{ __('main.account_number') }}</label>
                                <input id="supplierAccountNumber" name="account_number" type="text" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="{{ __('main.account_number') }}">
                                @error('account_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="supplierCurrency" class="form-label">{{ __('main.account_currency') }}</label>
                                <select id="supplierCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="">{{ __('admin.currency') }}</option>
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency') === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="supplierStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="supplierStatus" name="status" class="form-select @error('status') is-invalid @enderror" data-required-message="{{ __('validation.required', ['attribute' => __('main.status')]) }}">
                                    <option value="{{ \App\Models\AccountingSupplier::STATUS_ACTIVE }}" @selected($oldStatus === \App\Models\AccountingSupplier::STATUS_ACTIVE)>{{ __('main.active') }}</option>
                                    <option value="{{ \App\Models\AccountingSupplier::STATUS_INACTIVE }}" @selected($oldStatus === \App\Models\AccountingSupplier::STATUS_INACTIVE)>{{ __('main.inactive') }}</option>
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.status')]) }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="supplierSubmit" type="submit">{{ $isEditingSupplier ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="supplierContactTemplate">
        <div class="supplier-contact-card" data-supplier-contact-row>
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">{{ __('main.full_name') }} *</label>
                    <input data-name="contacts[__INDEX__][full_name]" type="text" class="form-control" placeholder="{{ __('main.full_name') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('main.position_or_grade') }}</label>
                    <input data-name="contacts[__INDEX__][position]" type="text" class="form-control" placeholder="{{ __('main.position_or_grade') }}">
                </div>
                <div class="col-md-1 d-flex justify-content-end">
                    <button type="button" class="icon-light-button" data-remove-supplier-contact aria-label="{{ __('admin.delete') }}">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('main.department') }}</label>
                    <input data-name="contacts[__INDEX__][department]" type="text" class="form-control" placeholder="{{ __('main.department') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('main.email') }}</label>
                    <input data-name="contacts[__INDEX__][email]" type="email" class="form-control" placeholder="{{ __('main.email') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('main.phone') }}</label>
                    <input data-name="contacts[__INDEX__][phone]" type="text" class="form-control" placeholder="{{ __('main.phone') }}">
                </div>
            </div>
        </div>
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasSupplierErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-suppliers.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-suppliers.js')) !!}</script>
</body>
</html>


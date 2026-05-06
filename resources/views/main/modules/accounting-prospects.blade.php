<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.prospects') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalProspects = $prospects->total();
        $hasProspectErrors = $errors->any();
        $isEditingProspect = old('form_mode') === 'edit' && old('prospect_id');
        $prospectFormAction = $isEditingProspect
            ? route('main.accounting.prospects.update', [$company, $site, old('prospect_id')])
            : route('main.accounting.prospects.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingProspect::TYPE_INDIVIDUAL);
        $oldSource = old('source', \App\Models\AccountingProspect::SOURCE_OTHER);
        $oldStatus = old('status', \App\Models\AccountingProspect::STATUS_NEW);
        $oldInterest = old('interest_level', \App\Models\AccountingProspect::INTEREST_WARM);
        $oldContacts = old('contacts', [['full_name' => '', 'position' => '', 'department' => '', 'email' => '', 'phone' => '']]);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'prospects'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.prospects') }}</h1>
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
                        <h1>{{ __('main.prospects') }}</h1>
                        <p>{{ __('main.prospects_subtitle') }}</p>
                    </div>
                    @if ($prospectPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#prospectModal" data-prospect-mode="create">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            {{ __('main.new_prospect') }}
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
                        <strong id="visibleCount">{{ $prospects->count() }}</strong>
                        /
                        <strong>{{ $totalProspects }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table prospects-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.prospect') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.source') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.interest_level') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($prospects as $prospect)
                                    @php
                                        $prospectTypeLabel = $prospect->type === \App\Models\AccountingProspect::TYPE_COMPANY ? __('main.prospect_company') : __('main.prospect_individual');
                                        $contactsPayload = $prospect->contacts->map(fn ($contact) => [
                                            'full_name' => $contact->full_name,
                                            'position' => $contact->position,
                                            'department' => $contact->department,
                                            'email' => $contact->email,
                                            'phone' => $contact->phone,
                                        ])->values();
                                    @endphp
                                    <tr>
                                        <td>{{ ($prospects->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $prospect->reference }}</span></td>
                                        <td>
                                            <span class="prospect-name">
                                                <span class="prospect-icon prospect-icon-{{ $prospect->type }}">
                                                    <i class="bi {{ $prospect->isCompany() ? 'bi-building' : 'bi-person' }}" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $prospect->name }}</strong>
                                                    <small>{{ $prospect->isCompany() ? ($prospect->rccm ?: '-') : ($prospect->profession ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill prospect-type-{{ $prospect->type }}">{{ $prospectTypeLabel }}</span></td>
                                        <td><span class="status-pill prospect-source-{{ $prospect->source }}">{{ $sourceLabels[$prospect->source] ?? $prospect->source }}</span></td>
                                        <td><span class="status-pill prospect-status-{{ $prospect->status }}">{{ $statusLabels[$prospect->status] ?? $prospect->status }}</span></td>
                                        <td><span class="status-pill prospect-interest-{{ $prospect->interest_level }}">{{ $interestLabels[$prospect->interest_level] ?? $prospect->interest_level }}</span></td>
                                        <td>
                                            @if ($prospectPermissions['can_update'] || $prospectPermissions['can_delete'] || ($prospectPermissions['can_create'] && ! $prospect->isConverted()))
                                                <div class="table-actions">
                                                    @if ($prospectPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#prospectModal" data-prospect-mode="edit" data-prospect-action="{{ route('main.accounting.prospects.update', [$company, $site, $prospect]) }}" data-prospect-id="{{ $prospect->id }}" data-prospect-type="{{ $prospect->type }}" data-prospect-name="{{ $prospect->name }}" data-prospect-profession="{{ $prospect->profession }}" data-prospect-phone="{{ $prospect->phone }}" data-prospect-email="{{ $prospect->email }}" data-prospect-address="{{ $prospect->address }}" data-prospect-rccm="{{ $prospect->rccm }}" data-prospect-id-nat="{{ $prospect->id_nat }}" data-prospect-nif="{{ $prospect->nif }}" data-prospect-website="{{ $prospect->website }}" data-prospect-source="{{ $prospect->source }}" data-prospect-status="{{ $prospect->status }}" data-prospect-interest-level="{{ $prospect->interest_level }}" data-prospect-notes="{{ $prospect->notes }}" data-prospect-contacts='@json($contactsPayload)' aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($prospectPermissions['can_create'] && ! $prospect->isConverted())
                                                        <form method="POST" action="{{ route('main.accounting.prospects.convert', [$company, $site, $prospect]) }}">
                                                            @csrf
                                                            <button type="submit" class="table-button table-button-convert" aria-label="{{ __('main.convert_to_client') }}" title="{{ __('main.convert_to_client') }}">
                                                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($prospectPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.prospects.destroy', [$company, $site, $prospect]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_prospect_title') }}" data-delete-text="{{ __('main.delete_prospect_text', ['name' => $prospect->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
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
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_prospects') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($prospects->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $prospects->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $prospects->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalProspects }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($prospects->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $prospects->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($prospects->getUrlRange(1, $prospects->lastPage()) as $page => $url)
                                @if ($page === $prospects->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($prospects->hasMorePages())<a href="{{ $prospects->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-prospect-modal" id="prospectModal" tabindex="-1" aria-labelledby="prospectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form prospect-form" method="POST" action="{{ $prospectFormAction }}" data-create-action="{{ route('main.accounting.prospects.store', [$company, $site]) }}" data-title-create="{{ __('main.new_prospect') }}" data-title-edit="{{ __('main.edit_prospect') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="prospectMethod" value="PUT" @disabled(! $isEditingProspect)>
                <input type="hidden" name="form_mode" id="prospectFormMode" value="{{ $isEditingProspect ? 'edit' : 'create' }}">
                <input type="hidden" name="prospect_id" id="prospectId" value="{{ old('prospect_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="prospectModalLabel"><i class="bi bi-person-plus" aria-hidden="true"></i>{{ $isEditingProspect ? __('main.edit_prospect') : __('main.new_prospect') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="prospectType" class="form-label">{{ __('main.prospect_type') }} *</label>
                            <select id="prospectType" name="type" class="form-select @error('type') is-invalid @enderror" data-required-message="{{ __('validation.required', ['attribute' => __('main.prospect_type')]) }}">
                                <option value="{{ \App\Models\AccountingProspect::TYPE_INDIVIDUAL }}" @selected($oldType === \App\Models\AccountingProspect::TYPE_INDIVIDUAL)>{{ __('main.prospect_individual') }}</option>
                                <option value="{{ \App\Models\AccountingProspect::TYPE_COMPANY }}" @selected($oldType === \App\Models\AccountingProspect::TYPE_COMPANY)>{{ __('main.prospect_company') }}</option>
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.prospect_type')]) }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="prospectName" class="form-label" data-label-individual="{{ __('main.full_name') }} *" data-label-company="{{ __('main.company_name') }} *">{{ $oldType === \App\Models\AccountingProspect::TYPE_COMPANY ? __('main.company_name') : __('main.full_name') }} *</label>
                            <input id="prospectName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.full_name') }}" data-placeholder-individual="{{ __('main.full_name') }}" data-placeholder-company="{{ __('main.company_name') }}" data-required-message="{{ __('validation.required', ['attribute' => __('main.name')]) }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.name')]) }}</div>@enderror
                        </div>
                    </div>

                    <div class="prospect-type-panel" data-prospect-panel="individual" @hidden($oldType !== \App\Models\AccountingProspect::TYPE_INDIVIDUAL)>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="prospectProfession" class="form-label">{{ __('main.profession') }}</label>
                                <input id="prospectProfession" name="profession" type="text" class="form-control @error('profession') is-invalid @enderror" value="{{ old('profession') }}" placeholder="{{ __('main.profession') }}">
                                @error('profession')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prospectPhone" class="form-label">{{ __('main.phone') }}</label>
                                <input id="prospectPhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prospectEmail" class="form-label">{{ __('main.email') }}</label>
                                <input id="prospectEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="prospect-type-panel" data-prospect-panel="company" @hidden($oldType !== \App\Models\AccountingProspect::TYPE_COMPANY)>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="prospectRccm" class="form-label">{{ __('admin.rccm') }}</label>
                                <input id="prospectRccm" name="rccm" type="text" class="form-control @error('rccm') is-invalid @enderror" value="{{ old('rccm') }}" placeholder="{{ __('admin.rccm') }}">
                                @error('rccm')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="prospectIdNat" class="form-label">{{ __('admin.id_nat') }}</label>
                                <input id="prospectIdNat" name="id_nat" type="text" class="form-control @error('id_nat') is-invalid @enderror" value="{{ old('id_nat') }}" placeholder="{{ __('admin.id_nat') }}">
                                @error('id_nat')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="prospectNif" class="form-label">{{ __('admin.nif') }}</label>
                                <input id="prospectNif" name="nif" type="text" class="form-control @error('nif') is-invalid @enderror" value="{{ old('nif') }}" placeholder="{{ __('admin.nif') }}">
                                @error('nif')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prospectWebsite" class="form-label">{{ __('admin.website') }}</label>
                                <input id="prospectWebsite" name="website" type="text" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}" placeholder="{{ __('admin.website') }}">
                                @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="prospect-contacts-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-person-lines-fill" aria-hidden="true"></i> {{ __('main.prospect_contacts') }}</span>
                                <button type="button" class="light-action" data-add-prospect-contact>
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    {{ __('main.add_contact') }}
                                </button>
                            </div>
                            <div class="prospect-contact-list" data-prospect-contact-list>
                                @foreach ($oldContacts as $index => $contact)
                                    <div class="prospect-contact-card" data-prospect-contact-row>
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
                                                <button type="button" class="icon-light-button" data-remove-prospect-contact aria-label="{{ __('admin.delete') }}">
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

                    <div class="row g-3">
                        <div class="col-md-6" data-prospect-address-wrapper>
                            <label for="prospectAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="prospectAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="prospect-crm-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-kanban" aria-hidden="true"></i> {{ __('main.commercial_tracking') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="prospectSource" class="form-label">{{ __('main.source') }} *</label>
                                <select id="prospectSource" name="source" class="form-select @error('source') is-invalid @enderror">
                                    @foreach ($sourceLabels as $source => $label)
                                        <option value="{{ $source }}" @selected($oldSource === $source)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('source')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="prospectStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="prospectStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $status => $label)
                                        <option value="{{ $status }}" @selected($oldStatus === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="prospectInterest" class="form-label">{{ __('main.interest_level') }} *</label>
                                <select id="prospectInterest" name="interest_level" class="form-select @error('interest_level') is-invalid @enderror">
                                    @foreach ($interestLabels as $interest => $label)
                                        <option value="{{ $interest }}" @selected($oldInterest === $interest)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('interest_level')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="prospectNotes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="prospectNotes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('main.notes') }}">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="prospectSubmit" type="submit">{{ $isEditingProspect ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="prospectContactTemplate">
        <div class="prospect-contact-card" data-prospect-contact-row>
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
                    <button type="button" class="icon-light-button" data-remove-prospect-contact aria-label="{{ __('admin.delete') }}">
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
    @if ($hasProspectErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('prospectModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-prospects.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-prospects.js')) !!}</script>
</body>
</html>

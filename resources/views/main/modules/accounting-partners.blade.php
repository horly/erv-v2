<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.partners') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $module = \App\Models\CompanySite::MODULE_ACCOUNTING;
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalPartners = $partners->total();
        $hasPartnerErrors = $errors->any();
        $isEditingPartner = old('form_mode') === 'edit' && old('partner_id');
        $partnerFormAction = $isEditingPartner
            ? route('main.accounting.partners.update', [$company, $site, old('partner_id')])
            : route('main.accounting.partners.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingPartner::TYPE_BUSINESS_REFERRER);
        $oldStatus = old('status', \App\Models\AccountingPartner::STATUS_ACTIVE);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'partners'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.partners') }}</h1>
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
                        <h1>{{ __('main.partners') }}</h1>
                        <p>{{ __('main.partners_subtitle') }}</p>
                    </div>
                    @if ($partnerPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#partnerModal" data-partner-mode="create">
                            <i class="bi bi-diagram-3" aria-hidden="true"></i>
                            {{ __('main.new_partner') }}
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
                        <strong id="visibleCount">{{ $partners->count() }}</strong>
                        /
                        <strong>{{ $totalPartners }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table partners-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.partner') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.contact') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.activity_domain') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($partners as $partner)
                                    <tr>
                                        <td>{{ ($partners->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $partner->reference }}</span></td>
                                        <td>
                                            <span class="partner-name">
                                                <span class="partner-icon partner-icon-{{ $partner->type }}">
                                                    <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $partner->name }}</strong>
                                                    <small>{{ $partner->website ?: ($partner->email ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill partner-type-{{ $partner->type }}">{{ $typeLabels[$partner->type] ?? $partner->type }}</span></td>
                                        <td>
                                            {{ $partner->contact_name ?: '-' }}
                                            @if ($partner->contact_position)
                                                <small class="d-block text-muted">{{ $partner->contact_position }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $partner->activity_domain ?: '-' }}</td>
                                        <td><span class="status-pill partner-status-{{ $partner->status }}">{{ $statusLabels[$partner->status] ?? $partner->status }}</span></td>
                                        <td>
                                            @if ($partnerPermissions['can_update'] || $partnerPermissions['can_delete'])
                                                <div class="table-actions">
                                                    @if ($partnerPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#partnerModal" data-partner-mode="edit" data-partner-action="{{ route('main.accounting.partners.update', [$company, $site, $partner]) }}" data-partner-id="{{ $partner->id }}" data-partner-type="{{ $partner->type }}" data-partner-name="{{ $partner->name }}" data-partner-contact-name="{{ $partner->contact_name }}" data-partner-contact-position="{{ $partner->contact_position }}" data-partner-phone="{{ $partner->phone }}" data-partner-email="{{ $partner->email }}" data-partner-address="{{ $partner->address }}" data-partner-website="{{ $partner->website }}" data-partner-activity-domain="{{ $partner->activity_domain }}" data-partner-partnership-started-at="{{ $partner->partnership_started_at?->format('Y-m-d') }}" data-partner-status="{{ $partner->status }}" data-partner-notes="{{ $partner->notes }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($partnerPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.partners.destroy', [$company, $site, $partner]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_partner_title') }}" data-delete-text="{{ __('main.delete_partner_text', ['name' => $partner->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
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
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_partners') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($partners->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $partners->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $partners->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalPartners }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($partners->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $partners->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($partners->getUrlRange(1, $partners->lastPage()) as $page => $url)
                                @if ($page === $partners->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($partners->hasMorePages())<a href="{{ $partners->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-partner-modal" id="partnerModal" tabindex="-1" aria-labelledby="partnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form partner-form" method="POST" action="{{ $partnerFormAction }}" data-create-action="{{ route('main.accounting.partners.store', [$company, $site]) }}" data-title-create="{{ __('main.new_partner') }}" data-title-edit="{{ __('main.edit_partner') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="partnerMethod" value="PUT" @disabled(! $isEditingPartner)>
                <input type="hidden" name="form_mode" id="partnerFormMode" value="{{ $isEditingPartner ? 'edit' : 'create' }}">
                <input type="hidden" name="partner_id" id="partnerId" value="{{ old('partner_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="partnerModalLabel"><i class="bi bi-diagram-3" aria-hidden="true"></i>{{ $isEditingPartner ? __('main.edit_partner') : __('main.new_partner') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="partnerType" class="form-label">{{ __('main.partner_type') }} *</label>
                            <select id="partnerType" name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $type => $label)
                                    <option value="{{ $type }}" @selected($oldType === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="partnerStatus" class="form-label">{{ __('main.status') }} *</label>
                            <select id="partnerStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach ($statusLabels as $status => $label)
                                    <option value="{{ $status }}" @selected($oldStatus === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="partnerName" class="form-label">{{ __('main.partner_name') }} *</label>
                            <input id="partnerName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.partner_name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="partner-relation-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-person-vcard" aria-hidden="true"></i> {{ __('main.contact') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="partnerContactName" class="form-label">{{ __('main.partner_contact_name') }}</label>
                                <input id="partnerContactName" name="contact_name" type="text" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name') }}" placeholder="{{ __('main.partner_contact_name') }}">
                                @error('contact_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="partnerContactPosition" class="form-label">{{ __('main.partner_contact_position') }}</label>
                                <input id="partnerContactPosition" name="contact_position" type="text" class="form-control @error('contact_position') is-invalid @enderror" value="{{ old('contact_position') }}" placeholder="{{ __('main.partner_contact_position') }}">
                                @error('contact_position')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="partnerPhone" class="form-label">{{ __('main.phone') }}</label>
                                <input id="partnerPhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="partnerEmail" class="form-label">{{ __('main.email') }}</label>
                                <input id="partnerEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="partnerAddress" class="form-label">{{ __('main.address') }}</label>
                                <input id="partnerAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="partner-relation-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-diagram-3" aria-hidden="true"></i> {{ __('main.partnership') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="partnerWebsite" class="form-label">{{ __('main.website') }}</label>
                                <input id="partnerWebsite" name="website" type="text" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}" placeholder="{{ __('main.website') }}">
                                @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="partnerActivityDomain" class="form-label">{{ __('main.activity_domain') }}</label>
                                <input id="partnerActivityDomain" name="activity_domain" type="text" class="form-control @error('activity_domain') is-invalid @enderror" value="{{ old('activity_domain') }}" placeholder="{{ __('main.activity_domain') }}">
                                @error('activity_domain')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="partnerStartedAt" class="form-label">{{ __('main.partnership_started_at') }}</label>
                                <input id="partnerStartedAt" name="partnership_started_at" type="date" class="form-control @error('partnership_started_at') is-invalid @enderror" value="{{ old('partnership_started_at') }}" placeholder="{{ __('main.partnership_started_at') }}">
                                @error('partnership_started_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="partnerNotes" class="form-label">{{ __('main.partner_notes') }}</label>
                                <textarea id="partnerNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.partner_notes') }}">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="partnerSubmit" type="submit">{{ $isEditingPartner ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasPartnerErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('partnerModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-partners.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-partners.js')) !!}</script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.companies') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $totalCompanies = $companies->total();
    @endphp

    <div class="dashboard-shell main-shell" data-theme="light">
        <aside class="dashboard-sidebar">
            <a class="sidebar-brand" href="{{ route('admin.dashboard') }}" aria-label="EXAD ERP">
                <span class="sidebar-logo">
                    <img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services">
                </span>
                <span>
                    <strong>EXAD ERP</strong>
                    <small>{{ __('admin.console') }}</small>
                </span>
            </a>

            <button
                class="sidebar-toggle"
                type="button"
                id="sidebarToggle"
                aria-label="{{ __('admin.collapse_sidebar') }}"
                title="{{ __('admin.collapse_sidebar') }}"
                data-label-collapse="{{ __('admin.collapse_sidebar') }}"
                data-label-expand="{{ __('admin.expand_sidebar') }}"
            >
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>

            <nav class="sidebar-nav" aria-label="{{ __('admin.superadmin_navigation') }}">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    {{ __('admin.dashboard') }}
                </a>
                <a class="nav-link" href="{{ route('admin.subscriptions') }}">
                    <i class="bi bi-stack" aria-hidden="true"></i>
                    {{ __('admin.subscriptions') }}
                </a>
                <a class="nav-link" href="{{ route('admin.users') }}">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    {{ __('admin.users') }}
                </a>
                <a class="nav-link active" href="{{ route('admin.companies') }}">
                    <i class="bi bi-buildings" aria-hidden="true"></i>
                    {{ __('admin.companies') }}
                </a>
            </nav>

            <div class="sidebar-footer">
                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                <span>{{ __('admin.version') }}</span>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('admin.companies') }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / {{ __('admin.companies') }}</p>
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
                                <em>{{ strtoupper($user->role) }}</em>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="profile-link">
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                                {{ __('admin.profile') }}
                            </a>
                            <a href="{{ route('admin.users') }}" class="profile-link">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                {{ __('admin.user_management') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="profile-link logout-link" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    {{ __('admin.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            @if (session('success') || $errors->any())
                <div class="flash-toast {{ session('toast_type') === 'danger' || $errors->any() ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                    <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                    <span>{{ session('success') ?: $errors->first() }}</span>
                    <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <span class="flash-progress" aria-hidden="true"></span>
                </div>
            @endif            <section class="dashboard-content subscriptions-page companies-page">
                <div class="subscription-actions">
                    <a class="primary-action" href="{{ route('admin.companies.create') }}">
                        <i class="bi bi-building-add" aria-hidden="true"></i>
                        {{ __('admin.new_company') }}
                    </a>
                </div>
<section class="table-tools subscriptions-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $companies->count() }}</strong>
                        /
                        <strong>{{ $totalCompanies }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="admin-table-card">
                    <div class="table-responsive">
                        <table class="admin-table companies-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="text">{{ __('admin.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="text">{{ __('admin.subscription') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="number">{{ __('admin.sites') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('admin.users') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5" data-sort-type="text">{{ __('admin.country') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6" data-sort-type="text">{{ __('admin.email') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companies as $company)
                                    <tr>
                                        <td>{{ $companies->firstItem() + $loop->index }}</td>
                                        <td>
                                            <span class="company-cell">
                                                @if ($company->logo_url)
                                                    <span class="company-logo">
                                                        <img src="{{ $company->logo_url }}" alt="{{ $company->name }}">
                                                    </span>
                                                @else
                                                    <span class="company-logo placeholder-logo" aria-hidden="true">
                                                        <i class="bi bi-building" aria-hidden="true"></i>
                                                    </span>
                                                @endif
                                                <span class="linked-subscription">{{ $company->name }}</span>
                                            </span>
                                        </td>
                                        <td>{{ $company->subscription?->name ?? '-' }}</td>
                                        <td>{{ $company->sites_count }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                class="count-link"
                                                data-bs-toggle="modal"
                                                data-bs-target="#companyUsersModal-{{ $company->id }}"
                                                aria-label="{{ __('admin.company_users_title', ['name' => $company->name]) }}"
                                            >
                                                {{ $company->users_count }}
                                            </button>
                                        </td>
                                        <td>
                                            @php
                                                $countryMeta = collect($countries)->first(fn ($country) => in_array($company->country, [$country['name_fr'], $country['name_en'], $country['iso']], true));
                                                $countryDisplay = data_get($countryMeta, 'name_'.app()->getLocale(), $company->country ?: 'Congo (RDC)');
                                            @endphp
                                            {{ $countryDisplay }}
                                        </td>
                                        <td>{{ $company->email ?: '-' }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="{{ route('admin.companies.edit', $company) }}" class="table-button table-button-edit" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </a>
                                                @if ($company->sites_count === 0)
                                                    <form method="POST" action="{{ route('admin.companies.destroy', $company) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="button"
                                                            class="table-button table-button-delete"
                                                            aria-label="{{ __('admin.delete') }}"
                                                            data-delete-trigger
                                                            data-delete-title="{{ __('admin.delete_company_title') }}"
                                                            data-delete-text="{{ __('admin.delete_company_text', ['name' => $company->name]) }}"
                                                            data-delete-confirm="{{ __('admin.delete_company_confirm') }}"
                                                            data-delete-cancel="{{ __('admin.delete_company_cancel') }}"
                                                        >
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.company_has_sites') }}" title="{{ __('admin.company_has_sites') }}" disabled>
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="8">{{ __('admin.no_companies') }}</td>
                                    </tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden>
                                    <td colspan="8">{{ __('admin.no_results') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($companies->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>
                            {{ __('admin.showing') }}
                            <strong>{{ $companies->firstItem() ?? 0 }}</strong>
                            {{ __('admin.to') }}
                            <strong>{{ $companies->lastItem() ?? 0 }}</strong>
                            {{ __('admin.on') }}
                            <strong>{{ $totalCompanies }}</strong>
                        </span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($companies->onFirstPage())
                                <span class="disabled">{{ __('admin.previous') }}</span>
                            @else
                                <a href="{{ $companies->previousPageUrl() }}">{{ __('admin.previous') }}</a>
                            @endif

                            @foreach ($companies->getUrlRange(1, $companies->lastPage()) as $page => $url)
                                @if ($page === $companies->currentPage())
                                    <span class="active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($companies->hasMorePages())
                                <a href="{{ $companies->nextPageUrl() }}">{{ __('admin.next') }}</a>
                            @else
                                <span class="disabled">{{ __('admin.next') }}</span>
                            @endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($companies as $company)
        <div class="modal fade subscription-modal company-users-modal" id="companyUsersModal-{{ $company->id }}" tabindex="-1" aria-labelledby="companyUsersModalLabel-{{ $company->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-body">
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                        <h2 id="companyUsersModalLabel-{{ $company->id }}">{{ __('admin.company_users_title', ['name' => $company->name]) }}</h2>

                        <div data-datatable>
                            <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                                <label class="search-box">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <input type="search" placeholder="{{ __('admin.search') }}" autocomplete="off" data-datatable-search>
                                </label>
                                <span class="row-count">
                                    <strong data-datatable-visible-count>{{ $company->users->count() }}</strong>
                                    /
                                    <strong>{{ $company->users->count() }}</strong>
                                    {{ __('admin.rows') }}
                                </span>
                            </section>

                            <div class="table-responsive">
                                <table class="admin-table modal-table" data-datatable-table>
                                    <thead>
                                        <tr>
                                            <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="text">{{ __('admin.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="text">{{ __('admin.email') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="text">{{ __('admin.role') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="text">{{ __('admin.phone') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                            <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="text">{{ __('admin.grade') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($company->users as $companyUser)
                                            <tr>
                                                <td>{{ $companyUser->name }}</td>
                                                <td>{{ $companyUser->email }}</td>
                                                <td>
                                                    <span class="status-pill role-{{ $companyUser->role }}">
                                                        {{ __('admin.'.$companyUser->role.'_role') }}
                                                    </span>
                                                </td>
                                                <td>{{ $companyUser->phone_number ?: '-' }}</td>
                                                <td>{{ $companyUser->grade ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row">
                                                <td colspan="5">{{ __('admin.no_company_users') }}</td>
                                            </tr>
                                        @endforelse
                                        <tr class="empty-row search-empty-row" hidden>
                                            <td colspan="5">{{ __('admin.no_results') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

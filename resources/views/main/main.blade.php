<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.title') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $companyCount = $companies->count();
        $totalCompanies = $companies->total();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
    @endphp

    <div class="main-shell" data-theme="light">
        <header class="app-header">
            <a class="brand-block" href="{{ route('main') }}" aria-label="EXAD ERP">
                <span class="brand-logo">
                    <img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services">
                </span>
                <span>
                    <strong>EXAD ERP</strong>
                    <small>{{ __('main.management_space') }}</small>
                </span>
            </a>

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
                        <span class="avatar">{{ $initial }}</span>
                        <span class="profile-name">{{ $user->name }}</span>
                        <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                        <div class="profile-summary">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                            <em>{{ $user->role === 'admin' ? __('main.admin_badge') : strtoupper($user->role) }}</em>
                        </div>
                        <a href="#" class="profile-link">
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

        <main class="content-wrap">
            <section class="page-heading">
                <div>
                    <h1>{{ __('main.title') }}</h1>
                    <p>{{ __('main.subtitle') }}</p>
                </div>
                <a class="primary-action" href="{{ route('main.companies.create') }}">
                    <i class="bi bi-building-add" aria-hidden="true"></i>
                    {{ __('main.new_company') }}
                </a>
            </section>

            @if (session('success') || $errors->any())
                <div class="flash-toast {{ session('toast_type') === 'danger' || $errors->any() ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                    <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                    <span>{{ session('success') ?: $errors->first() }}</span>
                    <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <span class="flash-progress" aria-hidden="true"></span>
                </div>
            @endif

            <section class="table-tools" aria-label="Outils de recherche">
                <label class="search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="companySearch" placeholder="{{ __('main.search') }}" autocomplete="off">
                </label>
                <span class="row-count">
                    <strong id="visibleCount">{{ $companyCount }}</strong>
                    /
                    <strong>{{ $totalCompanies }}</strong>
                    {{ __('main.rows') }}
                </span>
            </section>

            <section class="company-card">
                <div class="table-responsive">
                    <table class="company-table" id="companyTable">
                        <thead>
                            <tr>
                                <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="text">{{ __('main.company') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="number">{{ __('main.sites') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="text">{{ __('main.country') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="text">{{ __('main.email') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th class="text-end">{{ __('main.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $company)
                                <tr>
                                    <td>{{ ($companies->firstItem() ?? 1) + $loop->index }}</td>
                                    <td>
                                        <span class="company-name">
                                            @if ($company->logo_url)
                                                <span class="company-logo"><img src="{{ $company->logo_url }}" alt="{{ $company->name }}"></span>
                                            @else
                                                <span class="company-icon">{{ strtoupper(mb_substr($company->name, 0, 1)) }}</span>
                                            @endif
                                            <a href="{{ route('main.companies.sites', $company) }}">{{ $company->name }}</a>
                                        </span>
                                    </td>
                                    <td>{{ $company->sites_count }}</td>
                                    <td>
                                        @php
                                            $countryMeta = collect($countries)->first(fn ($country) => in_array($company->country, [$country['name_fr'], $country['name_en'], $country['iso']], true));
                                            $countryDisplay = data_get($countryMeta, 'name_'.app()->getLocale(), $company->country ?: __('main.default_country'));
                                        @endphp
                                        {{ $countryDisplay }}
                                    </td>
                                    <td>{{ $company->email ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{ route('main.companies.edit', $company) }}" class="table-button" aria-label="{{ __('admin.edit') }}">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                            @if ($company->sites_count === 0)
                                                <form method="POST" action="{{ route('main.companies.destroy', $company) }}">
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
                                    <td colspan="6">{{ __('main.empty') }}</td>
                                </tr>
                            @endforelse
                            <tr class="empty-row search-empty-row" hidden>
                                <td colspan="6">{{ __('admin.no_results') }}</td>
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
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

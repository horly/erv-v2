<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.title') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $companyCount = $companies->count();
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
                        <a href="#" class="profile-link">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            {{ __('main.users') }}
                        </a>
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
                <button class="primary-action" type="button">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    {{ __('main.new_company') }}
                </button>
            </section>

            <section class="table-tools" aria-label="Outils de recherche">
                <label class="search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="companySearch" placeholder="{{ __('main.search') }}" autocomplete="off">
                </label>
                <span class="row-count">
                    <strong id="visibleCount">{{ $companyCount }}</strong>
                    /
                    <strong>{{ $companyCount }}</strong>
                    {{ __('main.rows') }}
                </span>
            </section>

            <section class="company-card">
                <div class="table-responsive">
                    <table class="company-table" id="companyTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('main.company') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                                <th>{{ __('main.email') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                                <th>{{ __('main.phone') }}</th>
                                <th>{{ __('main.country') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                                <th class="text-end">{{ __('main.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $company)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="#" class="company-name">
                                            <span class="company-icon">{{ strtoupper(mb_substr($company->name, 0, 1)) }}</span>
                                            {{ $company->name }}
                                        </a>
                                    </td>
                                    <td>{{ $company->email ?? '-' }}</td>
                                    <td>{{ $company->phone_number ?? '-' }}</td>
                                    <td>{{ __('main.default_country') }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="table-button" aria-label="Modifier">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="table-button" aria-label="Supprimer">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-row">
                                    <td colspan="6">{{ __('main.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

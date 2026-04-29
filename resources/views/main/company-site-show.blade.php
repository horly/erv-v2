<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $site->name }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $typeLabel = $typeLabels[$site->type] ?? $site->type;
        $statusIsActive = $site->status === \App\Models\CompanySite::STATUS_ACTIVE;
        $moduleIcons = [
            \App\Models\CompanySite::MODULE_ACCOUNTING => 'bi-receipt',
            \App\Models\CompanySite::MODULE_HUMAN_RESOURCES => 'bi-people',
            \App\Models\CompanySite::MODULE_ARCHIVING => 'bi-archive',
            \App\Models\CompanySite::MODULE_DOCUMENT_MANAGEMENT => 'bi-file-earmark-text',
        ];
        $moduleClasses = [
            \App\Models\CompanySite::MODULE_ACCOUNTING => 'module-accounting',
            \App\Models\CompanySite::MODULE_HUMAN_RESOURCES => 'module-human-resources',
            \App\Models\CompanySite::MODULE_ARCHIVING => 'module-archiving',
            \App\Models\CompanySite::MODULE_DOCUMENT_MANAGEMENT => 'module-document-management',
        ];
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

        <main class="content-wrap site-detail-page">
            <a class="back-link" href="{{ route('main.companies.sites', $company) }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ __('main.back_to_company_sites', ['name' => $company->name]) }}
            </a>

            <section class="site-detail-heading">
                @if ($company->logo_url)
                    <span class="company-logo"><img src="{{ $company->logo_url }}" alt="{{ $company->name }}"></span>
                @else
                    <span class="company-icon">{{ strtoupper(mb_substr($company->name, 0, 1)) }}</span>
                @endif
                <div>
                    <h1>{{ $site->name }}</h1>
                    <div class="site-detail-meta">
                        <span class="site-badge">{{ $typeLabel }}</span>
                        <span class="status-pill {{ $statusIsActive ? 'is-active' : 'is-expired' }}">
                            <i class="bi bi-circle-fill" aria-hidden="true"></i>
                            {{ $statusIsActive ? __('main.active') : __('main.inactive') }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="site-detail-layout">
                <article class="site-detail-card">
                    <h2>
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        {{ __('main.site_details') }}
                    </h2>
                    <dl class="site-detail-list">
                        <div>
                            <dt>{{ __('main.responsible') }}</dt>
                            <dd>{{ $site->responsible?->name ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('main.phone') }}</dt>
                            <dd>{{ $site->phone ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('main.email') }}</dt>
                            <dd>{{ $site->email ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('main.address') }}</dt>
                            <dd>{{ $site->address ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('main.plan') }}</dt>
                            <dd>{{ $planRules['name'] }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="site-detail-card site-modules-card">
                    <h2>
                        <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                        {{ __('main.modules') }}
                    </h2>
                    <p>{{ __('main.site_modules_intro') }}</p>

                    <div class="site-module-link-grid">
                        @forelse ($modules as $module)
                            <a class="site-module-link {{ $moduleClasses[$module] ?? '' }}" href="{{ route('main.companies.sites.modules.show', [$company, $site, $module]) }}" aria-label="{{ __('main.open_module', ['module' => $moduleLabels[$module] ?? $module]) }}">
                                <span class="module-card-icon">
                                    <i class="bi {{ $moduleIcons[$module] ?? 'bi-grid' }}" aria-hidden="true"></i>
                                </span>
                                <strong>{{ $moduleLabels[$module] ?? $module }}</strong>
                                <i class="bi bi-arrow-up-right module-link-arrow" aria-hidden="true"></i>
                            </a>
                        @empty
                            <div class="site-module-empty">{{ __('main.no_site_modules') }}</div>
                        @endforelse
                    </div>
                </article>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

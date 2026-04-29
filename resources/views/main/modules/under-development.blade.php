<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $moduleMeta['label'] }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
    @endphp

    <div class="main-shell module-shell" data-theme="light">
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

        <main class="content-wrap module-page">
            <a class="back-link" href="{{ route('main.companies.sites.show', [$company, $site]) }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ $site->name }}
            </a>

            <section class="module-heading">
                <span class="module-heading-icon {{ $moduleMeta['class'] }}">
                    <i class="bi {{ $moduleMeta['icon'] }}" aria-hidden="true"></i>
                </span>
                <div>
                    <h1>{{ $moduleMeta['label'] }}</h1>
                    <p>{{ $moduleMeta['description'] }}</p>
                </div>
            </section>

            <section class="module-development-card">
                <span class="module-development-icon {{ $moduleMeta['class'] }}">
                    <i class="bi {{ $moduleMeta['icon'] }}" aria-hidden="true"></i>
                </span>
                <strong>{{ __('main.module_under_development') }}</strong>
                <p>{{ __('main.module_under_development_text') }}</p>
                <small>
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    {{ __('main.connected_as', ['name' => $user->name]) }}
                </small>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.pending_access_title') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
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
                        </a>
                        <a class="language-option {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">
                            <span class="language-code">EN</span>
                            <span class="language-name">{{ __('auth.language_en') }}</span>
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
                            {{ __('main.profile') }}
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

        <main class="content-wrap pending-wrap">
            <section class="pending-panel">
                <span class="pending-icon"><i class="bi bi-shield-exclamation" aria-hidden="true"></i></span>
                <h1>{{ __('main.pending_access_title') }}</h1>
                <p>{{ __('main.pending_access_text') }}</p>
                <div class="pending-user">
                    @include('partials.user-avatar', ['avatarUser' => $user])
                    <span>
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $user->email }}</small>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="primary-action" type="submit">
                        <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                        {{ __('main.logout') }}
                    </button>
                </form>
            </section>
        </main>
    </div>

    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

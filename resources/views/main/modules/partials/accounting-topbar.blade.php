@php($currentLocale = app()->getLocale())

<header class="dashboard-topbar">
    <div>
        <h1>{{ $title }}</h1>
        <p>{{ $company->name }} / {{ $site->name }}</p>
    </div>

    <div class="header-actions">
        <button
            class="icon-button"
            type="button"
            id="fullscreenButton"
            aria-label="{{ __('main.fullscreen_enter') }}"
            title="{{ __('main.fullscreen_enter') }}"
            data-label-enter="{{ __('main.fullscreen_enter') }}"
            data-label-exit="{{ __('main.fullscreen_exit') }}"
        >
            <i class="bi bi-fullscreen" aria-hidden="true"></i>
        </button>
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

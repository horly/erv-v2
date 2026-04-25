<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.meta_title') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/auth/login.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $nextLocale = $currentLocale === 'fr' ? 'en' : 'fr';
    @endphp

    <main class="auth-shell" data-theme="light">
        <section class="brand-side" aria-label="{{ __('auth.brand_aria') }}">
            <div class="brand-inner">
                <div class="logo-card" aria-label="EXAD Solution & Services">
                    <img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services" class="app-logo">
                </div>

                <div>
                    <div class="brand-copy">
                        <h4 class="brand-title">
                            {{ __('auth.brand_title_line_1') }}
                            <span>{{ __('auth.brand_title_line_2') }}</span>
                        </h4>
                        <p class="brand-lead">{{ __('auth.brand_lead') }}</p>
                    </div>

                    <div class="feature-list" aria-label="{{ __('auth.benefits_aria') }}">
                        <article class="feature-item">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-bar-chart-fill"></i></span>
                            <div>
                                <p class="feature-title">{{ __('auth.feature_data_title') }}</p>
                                <p class="feature-text">{{ __('auth.feature_data_text') }}</p>
                            </div>
                        </article>
                        <article class="feature-item">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
                            <div>
                                <p class="feature-title">{{ __('auth.feature_security_title') }}</p>
                                <p class="feature-text">{{ __('auth.feature_security_text') }}</p>
                            </div>
                        </article>
                        <article class="feature-item">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-diagram-3-fill"></i></span>
                            <div>
                                <p class="feature-title">{{ __('auth.feature_modules_title') }}</p>
                                <p class="feature-text">{{ __('auth.feature_modules_text') }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-side" aria-label="{{ __('auth.form_aria') }}">
            <div class="top-tools">
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
            </div>

            <div class="login-wrap">
                <span class="access-badge">{{ __('auth.badge') }}</span>
                <h2 class="login-title">{{ __('auth.login_title') }}</h2>
                <p class="login-description">{{ __('auth.login_description') }}</p>

                @if ($errors->any())
                    <div class="alert alert-danger mb-3" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success mb-3" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('auth.email_label') }}</label>
                        <div class="field-row">
                            <span class="field-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                class="form-control"
                                placeholder="{{ __('auth.email_placeholder') }}"
                                autocomplete="email"
                                required
                            >
                        </div>
                        <div class="invalid-feedback">{{ __('auth.email_error') }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="field-meta mb-2">
                            <label for="password" class="form-label mb-0">{{ __('auth.password_label') }}</label>
                            <a href="#" class="forgot-link small">{{ __('auth.password_forgot') }}</a>
                        </div>
                        <div class="field-row">
                            <span class="field-icon"><i class="bi bi-lock" aria-hidden="true"></i></span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control"
                                placeholder="{{ __('auth.password_placeholder') }}"
                                autocomplete="current-password"
                                minlength="6"
                                required
                            >
                            <button class="password-toggle" type="button" id="passwordToggle" aria-label="{{ __('auth.password_show') }}">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">{{ __('auth.password_error') }}</div>
                    </div>

                    <div class="form-check d-flex align-items-center gap-2 mb-4">
                        <input class="form-check-input mt-0" type="checkbox" value="1" id="remember" name="remember" checked>
                        <label class="form-check-label" for="remember">{{ __('auth.remember') }}</label>
                    </div>

                    <button class="primary-button" type="submit">
                        {{ __('auth.submit') }}
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="login-note">
                    {{ __('auth.no_account') }}
                    <a href="#" class="admin-link">{{ __('auth.contact_admin') }}</a>
                </p>
            </div>

            <footer class="page-footer">
                <span>{{ __('auth.copyright') }}</span>
                <a href="#">{{ __('auth.privacy') }}</a>
            </footer>
        </section>
    </main>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/auth/login.js')) !!}</script>
</body>
</html>

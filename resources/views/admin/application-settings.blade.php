<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.application_settings') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $brandColorDefaults = \App\Support\AppBranding::defaults();
    @endphp

    <div class="dashboard-shell main-shell" data-theme="light">
        <aside class="dashboard-sidebar">
            <a class="sidebar-brand" href="{{ route('admin.dashboard') }}" aria-label="{{ app_brand_name() }}">
                <span class="sidebar-logo">
                    <img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}">
                </span>
                <span>
                    <strong>{{ app_brand_short_name() }}</strong>
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
                <a class="nav-link" href="{{ route('admin.companies') }}">
                    <i class="bi bi-buildings" aria-hidden="true"></i>
                    {{ __('admin.companies') }}
                </a>
                <a class="nav-link active" href="{{ route('admin.application-settings') }}">
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                    {{ __('admin.application_settings') }}
                </a>
            </nav>

            <div class="sidebar-footer">
                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                <span>{{ app_brand_short_name() }} · v.2.0</span>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('admin.application_settings') }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / {{ __('admin.application_settings') }}</p>
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
                            @if ($user->isSuperadmin())
                            <a href="{{ route('admin.dashboard') }}" class="profile-link">
                                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                                {{ __('admin.dashboard') }}
                            </a>
                        @endif
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

            <section class="dashboard-content company-form-page">
                @if (session('success'))
                    <div class="alert alert-{{ session('toast_type', 'success') === 'danger' ? 'danger' : 'success' }} mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <form class="company-create-form" method="POST" action="{{ route('admin.application-settings.update') }}" enctype="multipart/form-data" data-loading-form>
                    @csrf
                    @method('PUT')

                    <section class="form-section">
                        <h2><i class="bi bi-window-sidebar" aria-hidden="true"></i>{{ __('admin.brand_identity') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="appName">{{ __('admin.application_name') }} *</label>
                                <input id="appName" name="app_name" type="text" class="form-control @error('app_name') is-invalid @enderror" value="{{ old('app_name', $branding['app_name'] ?? '') }}" placeholder="{{ __('admin.application_name_placeholder') }}" required>
                                @error('app_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shortName">{{ __('admin.application_short_name') }}</label>
                                <input id="shortName" name="short_name" type="text" class="form-control @error('short_name') is-invalid @enderror" value="{{ old('short_name', $branding['short_name'] ?? '') }}" placeholder="{{ __('admin.application_short_name_placeholder') }}">
                                @error('short_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tagline">{{ __('admin.application_tagline') }}</label>
                                <input id="tagline" name="tagline" type="text" class="form-control @error('tagline') is-invalid @enderror" value="{{ old('tagline', $branding['tagline'] ?? '') }}" placeholder="{{ __('admin.application_tagline_placeholder') }}">
                                @error('tagline')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="website">{{ __('admin.website') }}</label>
                                <input id="website" name="website" type="url" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $branding['website'] ?? '') }}" placeholder="https://exemple.com">
                                @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">{{ __('admin.application_description') }}</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('admin.application_description_placeholder') }}">{{ old('description', $branding['description'] ?? '') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section visual-section">
                        <h2><i class="bi bi-image" aria-hidden="true"></i>{{ __('admin.visual_identity') }}</h2>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="logo-upload-row">
                                    <span class="logo-preview" data-brand-preview="logo">
                                        <img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}">
                                    </span>
                                    <div>
                                        <label class="file-action section-add-button" for="appLogo">
                                            <i class="bi bi-upload" aria-hidden="true"></i>
                                            {{ __('admin.change_logo') }}
                                        </label>
                                        <input id="appLogo" name="logo" type="file" class="visually-hidden @error('logo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/svg+xml" data-brand-image-input="logo">
                                        <input id="croppedLogo" name="cropped_logo" type="hidden" value="">
                                        <p class="file-help">{{ __('admin.logo_help') }}</p>
                                        @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="logo-upload-row">
                                    <span class="logo-preview" data-brand-preview="favicon">
                                        <img src="{{ app_brand_favicon_url() }}" alt="{{ __('admin.favicon') }}">
                                    </span>
                                    <div>
                                        <label class="file-action section-add-button" for="appFavicon">
                                            <i class="bi bi-upload" aria-hidden="true"></i>
                                            {{ __('admin.change_favicon') }}
                                        </label>
                                        <input id="appFavicon" name="favicon" type="file" class="visually-hidden @error('favicon') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" data-brand-image-input="favicon">
                                        <input id="croppedFavicon" name="cropped_favicon" type="hidden" value="">
                                        <p class="file-help">{{ __('admin.favicon_help') }}</p>
                                        @error('favicon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="brand-color-heading">
                            <h2><i class="bi bi-palette" aria-hidden="true"></i>{{ __('admin.application_colors') }}</h2>
                            <button class="section-add-button brand-color-reset" type="button" data-reset-brand-colors>
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                {{ __('admin.restore_default_colors') }}
                            </button>
                        </div>
                        <div class="brand-color-grid">
                            <label class="brand-color-field" for="primaryColor">
                                <span>{{ __('admin.primary_color') }}</span>
                                <input id="primaryColor" name="primary_color" type="color" class="@error('primary_color') is-invalid @enderror" value="{{ old('primary_color', $branding['primary_color'] ?? $brandColorDefaults['primary_color']) }}" data-brand-default-color="{{ $brandColorDefaults['primary_color'] }}">
                                @error('primary_color')<em>{{ $message }}</em>@enderror
                            </label>
                            <label class="brand-color-field" for="primaryHoverColor">
                                <span>{{ __('admin.primary_hover_color') }}</span>
                                <input id="primaryHoverColor" name="primary_hover_color" type="color" class="@error('primary_hover_color') is-invalid @enderror" value="{{ old('primary_hover_color', $branding['primary_hover_color'] ?? $brandColorDefaults['primary_hover_color']) }}" data-brand-default-color="{{ $brandColorDefaults['primary_hover_color'] }}">
                                @error('primary_hover_color')<em>{{ $message }}</em>@enderror
                            </label>
                            <label class="brand-color-field" for="accentColor">
                                <span>{{ __('admin.accent_color') }}</span>
                                <input id="accentColor" name="accent_color" type="color" class="@error('accent_color') is-invalid @enderror" value="{{ old('accent_color', $branding['accent_color'] ?? $brandColorDefaults['accent_color']) }}" data-brand-default-color="{{ $brandColorDefaults['accent_color'] }}">
                                @error('accent_color')<em>{{ $message }}</em>@enderror
                            </label>
                            <label class="brand-color-field" for="sidebarColor">
                                <span>{{ __('admin.sidebar_color') }}</span>
                                <input id="sidebarColor" name="sidebar_color" type="color" class="@error('sidebar_color') is-invalid @enderror" value="{{ old('sidebar_color', $branding['sidebar_color'] ?? $brandColorDefaults['sidebar_color']) }}" data-brand-default-color="{{ $brandColorDefaults['sidebar_color'] }}">
                                @error('sidebar_color')<em>{{ $message }}</em>@enderror
                            </label>
                            <label class="brand-color-field" for="sidebarSecondaryColor">
                                <span>{{ __('admin.sidebar_secondary_color') }}</span>
                                <input id="sidebarSecondaryColor" name="sidebar_secondary_color" type="color" class="@error('sidebar_secondary_color') is-invalid @enderror" value="{{ old('sidebar_secondary_color', $branding['sidebar_secondary_color'] ?? $brandColorDefaults['sidebar_secondary_color']) }}" data-brand-default-color="{{ $brandColorDefaults['sidebar_secondary_color'] }}">
                                @error('sidebar_secondary_color')<em>{{ $message }}</em>@enderror
                            </label>
                        </div>
                        <p class="brand-color-help">{{ __('admin.application_colors_help') }}</p>
                    </section>

                    <section class="form-section">
                        <h2><i class="bi bi-headset" aria-hidden="true"></i>{{ __('admin.support_information') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="supportEmail">{{ __('admin.support_email') }}</label>
                                <input id="supportEmail" name="support_email" type="email" class="form-control @error('support_email') is-invalid @enderror" value="{{ old('support_email', $branding['support_email'] ?? '') }}" placeholder="support@exemple.com">
                                @error('support_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="supportPhone">{{ __('admin.support_phone') }}</label>
                                <input id="supportPhone" name="support_phone" type="text" class="form-control @error('support_phone') is-invalid @enderror" value="{{ old('support_phone', $branding['support_phone'] ?? '') }}" placeholder="+243 ...">
                                @error('support_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="copyright">{{ __('admin.copyright_text') }}</label>
                                <input id="copyright" name="copyright" type="text" class="form-control @error('copyright') is-invalid @enderror" value="{{ old('copyright', $branding['copyright'] ?? '') }}" placeholder="(c) 2026 ...">
                                @error('copyright')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="page-form-actions">
                        <a class="modal-cancel" href="{{ route('admin.dashboard') }}">{{ __('admin.cancel') }}</a>
                        <button class="modal-submit" type="submit">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            {{ __('admin.save_changes') }}
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <div class="modal fade branding-crop-modal" id="brandingCropModal" tabindex="-1" aria-labelledby="brandingCropModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="brandingCropModalLabel">
                        <i class="bi bi-crop" aria-hidden="true"></i>
                        {{ __('admin.crop_image') }}
                    </h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="branding-cropper-stage">
                        <img id="brandingCropImage" alt="">
                    </div>
                    <div class="branding-cropper-toolbar" aria-label="{{ __('admin.crop_image') }}">
                        <button type="button" class="table-button" data-brand-crop-action="zoom-in" title="{{ __('admin.zoom_in') }}" aria-label="{{ __('admin.zoom_in') }}"><i class="bi bi-zoom-in" aria-hidden="true"></i></button>
                        <button type="button" class="table-button" data-brand-crop-action="zoom-out" title="{{ __('admin.zoom_out') }}" aria-label="{{ __('admin.zoom_out') }}"><i class="bi bi-zoom-out" aria-hidden="true"></i></button>
                        <button type="button" class="table-button" data-brand-crop-action="rotate-left" title="{{ __('admin.rotate_left') }}" aria-label="{{ __('admin.rotate_left') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                        <button type="button" class="table-button" data-brand-crop-action="rotate-right" title="{{ __('admin.rotate_right') }}" aria-label="{{ __('admin.rotate_right') }}"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                    <button type="button" class="modal-submit" id="brandingCropApply">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        {{ __('admin.apply_crop') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>{!! file_get_contents(resource_path('js/admin/application-settings.js')) !!}</script>
</body>
</html>



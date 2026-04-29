<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('profile.title') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $homeRoute = $user->isSuperadmin() ? route('admin.dashboard') : route('main');
        $homeLabel = $user->isSuperadmin() ? __('profile.back_admin') : __('profile.back_main');
        $roleLabel = match ($user->role) {
            \App\Models\User::ROLE_SUPERADMIN => __('main.superadmin_badge'),
            \App\Models\User::ROLE_ADMIN => __('main.admin_badge'),
            default => strtoupper($user->role),
        };
        $statusMessage = match (session('profile_status')) {
            'information-updated' => __('profile.information_updated'),
            'email-updated' => __('profile.email_updated'),
            'password-updated' => __('profile.password_updated'),
            'photo-updated' => __('profile.photo_updated'),
            'two-factor-started' => __('profile.two_factor_started'),
            'two-factor-enabled' => __('profile.two_factor_enabled'),
            'two-factor-disabled' => __('profile.two_factor_disabled'),
            default => null,
        };
        $twoFactorHasSecret = filled($user->two_factor_secret);
        $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
        $twoFactorQrCodeSvg = $twoFactorHasSecret ? $user->twoFactorQrCodeSvg() : null;
    @endphp

    <div class="{{ $user->isSuperadmin() ? 'dashboard-shell main-shell' : 'main-shell' }}" data-theme="light">
        @if ($user->isSuperadmin())
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
                    <a class="nav-link" href="{{ route('admin.companies') }}">
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        {{ __('admin.companies') }}
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                    <span>{{ __('admin.version') }}</span>
                </div>
            </aside>
        @endif

        <div class="{{ $user->isSuperadmin() ? 'dashboard-main' : '' }}">
        <header class="{{ $user->isSuperadmin() ? 'dashboard-topbar' : 'app-header' }}">
            @if ($user->isSuperadmin())
                <div>
                    <h1>{{ __('profile.title') }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / {{ __('profile.title') }}</p>
                </div>
            @else
                <a class="brand-block" href="{{ $homeRoute }}" aria-label="EXAD ERP">
                    <span class="brand-logo">
                        <img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services">
                    </span>
                    <span>
                        <strong>EXAD ERP</strong>
                        <small>{{ __('main.management_space') }}</small>
                    </span>
                </a>
            @endif

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
                        <span class="avatar">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                            @else
                                {{ $initial }}
                            @endif
                        </span>
                        <span class="profile-name">{{ $user->name }}</span>
                        <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                        <div class="profile-summary">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                            <em>{{ $roleLabel }}</em>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="profile-link">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            {{ __('main.profile') }}
                        </a>
                        @if ($user->isSuperadmin())
                            <a href="{{ route('admin.users') }}" class="profile-link">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                {{ __('admin.user_management') }}
                            </a>
                        @elseif ($user->isAdmin())
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

        <main class="{{ $user->isSuperadmin() ? 'dashboard-content profile-page' : 'content-wrap profile-page' }}">
            <a class="back-link" href="{{ $homeRoute }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ $homeLabel }}
            </a>

            <section class="page-heading profile-heading">
                <div>
                    <h1>{{ __('profile.title') }}</h1>
                    <p>{{ __('profile.subtitle') }}</p>
                </div>
                <div class="profile-meta">
                    <span class="status-pill role-{{ $user->role }}">{{ $roleLabel }}</span>
                    <span>{{ __('profile.subscription') }} : {{ $user->subscription?->name ?? __('profile.not_assigned') }}</span>
                </div>
            </section>

            @if ($statusMessage || $errors->any())
                <div class="flash-toast {{ $errors->any() ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                    <span class="flash-icon">
                        <i class="bi {{ $errors->any() ? 'bi-exclamation-triangle' : 'bi-check2-circle' }}" aria-hidden="true"></i>
                    </span>
                    <span>{{ $statusMessage ?: $errors->first() }}</span>
                    <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <span class="flash-progress" aria-hidden="true"></span>
                </div>
            @endif

            <div class="profile-grid">
                <form class="admin-form profile-panel profile-photo-panel" method="POST" action="{{ route('profile.photo.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="profile-panel-heading">
                        <h2><i class="bi bi-camera" aria-hidden="true"></i>{{ __('profile.photo_section') }}</h2>
                        <p>{{ __('profile.photo_help') }}</p>
                    </div>

                    <div class="profile-photo-row">
                        <span class="profile-photo-preview" data-profile-photo-preview>
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                            @else
                                {{ $initial }}
                            @endif
                        </span>
                        <div class="profile-photo-actions">
                            <label class="secondary-action profile-photo-picker" for="profilePhotoInput">
                                <i class="bi bi-upload" aria-hidden="true"></i>
                                {{ __('profile.choose_photo') }}
                            </label>
                            <input id="profilePhotoInput" type="file" class="visually-hidden @error('cropped_photo', 'updateProfilePhoto') is-invalid @enderror" accept="image/png,image/jpeg,image/webp" data-profile-photo-input>
                            <input type="hidden" name="cropped_photo" id="profileCroppedPhoto">
                            @error('cropped_photo', 'updateProfilePhoto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </form>

                <section class="admin-form profile-panel profile-panel-wide two-factor-panel" aria-labelledby="twoFactorTitle">
                    <div class="profile-panel-heading two-factor-heading">
                        <div>
                            <h2 id="twoFactorTitle"><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('profile.two_factor_title') }}</h2>
                            <p>{{ __('profile.two_factor_help') }}</p>
                        </div>
                        <span class="status-pill {{ $twoFactorEnabled ? 'status-active' : 'status-neutral' }}">
                            {{ $twoFactorEnabled ? __('profile.two_factor_badge_enabled') : __('profile.two_factor_badge_not_configured') }}
                        </span>
                    </div>

                    @if (! $twoFactorHasSecret)
                        <div class="two-factor-empty">
                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                            <div>
                                <strong>{{ __('profile.two_factor_not_configured_title') }}</strong>
                                <p>{{ __('profile.two_factor_not_configured_text') }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('profile.two-factor.enable') }}" class="two-factor-actions">
                            @csrf
                            <button type="submit" class="primary-action">
                                <i class="bi bi-qr-code" aria-hidden="true"></i>
                                {{ __('profile.two_factor_configure') }}
                            </button>
                        </form>
                    @else
                        <div class="two-factor-configured">
                            <div class="two-factor-qr" aria-label="{{ __('profile.two_factor_qr_alt') }}">
                                {!! $twoFactorQrCodeSvg !!}
                            </div>
                            <div class="two-factor-copy">
                                @if ($twoFactorEnabled)
                                    <strong>{{ __('profile.two_factor_enabled_title') }}</strong>
                                    <p>{{ __('profile.two_factor_enabled_text') }}</p>
                                @else
                                    <strong>{{ __('profile.two_factor_pending_title') }}</strong>
                                    <p>{{ __('profile.two_factor_pending_text') }}</p>
                                @endif
                            </div>
                        </div>

                        @if (! $twoFactorEnabled)
                            <form method="POST" action="{{ route('profile.two-factor.confirm') }}" class="two-factor-confirm-form" novalidate>
                                @csrf
                                <div>
                                    <label for="twoFactorCode" class="form-label">{{ __('profile.two_factor_code') }} *</label>
                                    <input
                                        id="twoFactorCode"
                                        name="code"
                                        type="text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="6"
                                        class="form-control @error('code', 'confirmTwoFactorAuthentication') is-invalid @enderror"
                                        value="{{ old('code') }}"
                                        autocomplete="one-time-code"
                                        data-required-message="{{ __('profile.two_factor_code_required') }}"
                                    >
                                    @error('code', 'confirmTwoFactorAuthentication')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('profile.two_factor_code_required') }}</div>@enderror
                                </div>
                                <button type="submit" class="primary-action">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                    {{ __('profile.two_factor_confirm') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('profile.two-factor.disable') }}" class="two-factor-disable-form" novalidate>
                                @csrf
                                @method('DELETE')
                                <div>
                                    <label for="twoFactorCurrentPassword" class="form-label">{{ __('profile.current_password') }} *</label>
                                    <div class="password-control">
                                        <input id="twoFactorCurrentPassword" name="current_password" type="password" class="form-control @error('current_password', 'disableTwoFactorAuthentication') is-invalid @enderror" autocomplete="current-password" data-required-message="{{ __('profile.current_password_required') }}">
                                        <button type="button" class="password-toggle" data-password-toggle="twoFactorCurrentPassword" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    </div>
                                    @error('current_password', 'disableTwoFactorAuthentication')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('profile.current_password_required') }}</div>@enderror
                                </div>
                                <button type="submit" class="danger-action">
                                    <i class="bi bi-shield-x" aria-hidden="true"></i>
                                    {{ __('profile.two_factor_disable') }}
                                </button>
                            </form>
                        @endif
                    @endif
                </section>

                <form class="admin-form profile-panel profile-panel-wide" method="POST" action="{{ route('profile.information.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="profile-panel-heading">
                        <h2><i class="bi bi-person-lines-fill" aria-hidden="true"></i>{{ __('profile.personal_info') }}</h2>
                        <p>{{ __('profile.personal_info_help') }}</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="profileName" class="form-label">{{ __('profile.name') }} *</label>
                            <input id="profileName" name="name" type="text" class="form-control @error('name', 'updateProfileInformation') is-invalid @enderror" value="{{ old('name', $user->name) }}" autocomplete="name" data-required-message="{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.name'))]) }}">
                            @error('name', 'updateProfileInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.name'))]) }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="profilePhone" class="form-label">{{ __('profile.phone_number') }}</label>
                            <input id="profilePhone" name="phone_number" type="text" class="form-control @error('phone_number', 'updateProfileInformation') is-invalid @enderror" value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel">
                            @error('phone_number', 'updateProfileInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="profileGrade" class="form-label">{{ __('profile.grade') }}</label>
                            <input id="profileGrade" name="grade" type="text" class="form-control @error('grade', 'updateProfileInformation') is-invalid @enderror" value="{{ old('grade', $user->grade) }}">
                            @error('grade', 'updateProfileInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('profile.role') }}</label>
                            <input type="text" class="form-control" value="{{ $roleLabel }}" disabled>
                        </div>
                        <div class="col-12">
                            <label for="profileAddress" class="form-label">{{ __('profile.address') }}</label>
                            <textarea id="profileAddress" name="address" rows="3" class="form-control @error('address', 'updateProfileInformation') is-invalid @enderror">{{ old('address', $user->address) }}</textarea>
                            @error('address', 'updateProfileInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="primary-action">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            {{ __('profile.update_information') }}
                        </button>
                    </div>
                </form>

                <form class="admin-form profile-panel" method="POST" action="{{ route('profile.email.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="profile-panel-heading">
                        <h2><i class="bi bi-envelope-at" aria-hidden="true"></i>{{ __('profile.email_section') }}</h2>
                        <p>{{ __('profile.email_help') }}</p>
                    </div>

                    <div class="profile-stack">
                        <div>
                            <label for="profileEmail" class="form-label">{{ __('profile.email') }} *</label>
                            <input id="profileEmail" name="email" type="email" class="form-control @error('email', 'updateEmail') is-invalid @enderror" value="{{ old('email', $user->email) }}" autocomplete="email" data-required-message="{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.email'))]) }}" data-email-message="{{ __('validation.email', ['attribute' => mb_strtolower(__('profile.email'))]) }}">
                            @error('email', 'updateEmail')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.email'))]) }}</div>@enderror
                        </div>
                        <div>
                            <label for="profileEmailCurrentPassword" class="form-label">{{ __('profile.current_password') }} *</label>
                            <div class="password-control">
                                <input id="profileEmailCurrentPassword" name="current_password" type="password" class="form-control @error('current_password', 'updateEmail') is-invalid @enderror" autocomplete="current-password" data-required-message="{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.current_password'))]) }}">
                                <button type="button" class="password-toggle" data-password-toggle="profileEmailCurrentPassword" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            @error('current_password', 'updateEmail')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.current_password'))]) }}</div>@enderror
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="primary-action">
                            <i class="bi bi-envelope-check" aria-hidden="true"></i>
                            {{ __('profile.update_email') }}
                        </button>
                    </div>
                </form>

                <form class="admin-form profile-panel" method="POST" action="{{ route('profile.password.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="profile-panel-heading">
                        <h2><i class="bi bi-shield-lock" aria-hidden="true"></i>{{ __('profile.password_section') }}</h2>
                        <p>{{ __('profile.password_help') }}</p>
                    </div>

                    <div class="profile-stack">
                        <div>
                            <label for="profileCurrentPassword" class="form-label">{{ __('profile.current_password') }} *</label>
                            <div class="password-control">
                                <input id="profileCurrentPassword" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" data-required-message="{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.current_password'))]) }}">
                                <button type="button" class="password-toggle" data-password-toggle="profileCurrentPassword" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            @error('current_password', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => mb_strtolower(__('profile.current_password'))]) }}</div>@enderror
                        </div>
                        <div>
                            <label for="profilePassword" class="form-label">{{ __('profile.new_password') }} *</label>
                            <div class="password-control">
                                <input id="profilePassword" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" data-required-message="{{ __('admin.required_admin_password') }}" data-password-rules-target="profilePasswordRules">
                                <button type="button" class="password-toggle" data-password-toggle="profilePassword" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            @error('password', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_admin_password') }}</div>@enderror
                            <div class="password-rules" id="profilePasswordRules" aria-live="polite">
                                <span class="password-rule" data-rule="length">{{ __('admin.password_rule_length') }}</span>
                                <span class="password-rule" data-rule="case">{{ __('admin.password_rule_case') }}</span>
                                <span class="password-rule" data-rule="alphanumeric">{{ __('admin.password_rule_alphanumeric') }}</span>
                                <span class="password-rule" data-rule="special">{{ __('admin.password_rule_special') }}</span>
                            </div>
                        </div>
                        <div>
                            <label for="profilePasswordConfirmation" class="form-label">{{ __('profile.confirm_password') }} *</label>
                            <div class="password-control">
                                <input id="profilePasswordConfirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password" data-required-message="{{ __('admin.required_admin_password_confirmation') }}" data-password-confirmation-for="profilePassword" data-password-match-target="profilePasswordMatch">
                                <button type="button" class="password-toggle" data-password-toggle="profilePasswordConfirmation" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            @error('password_confirmation', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_admin_password_confirmation') }}</div>@enderror
                            <div class="password-match-feedback" id="profilePasswordMatch" data-valid-message="{{ __('admin.password_confirmation_match') }}" data-invalid-message="{{ __('admin.password_confirmation_mismatch') }}" data-empty-message="{{ __('admin.required_admin_password_confirmation') }}" aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="primary-action">
                            <i class="bi bi-key" aria-hidden="true"></i>
                            {{ __('profile.update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </main>
        </div>
    </div>

    <div class="modal fade profile-crop-modal" id="profileCropModal" tabindex="-1" aria-labelledby="profileCropModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="profileCropModalLabel">
                        <i class="bi bi-crop" aria-hidden="true"></i>
                        {{ __('profile.crop_photo') }}
                    </h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="cropper-stage">
                        <img id="profileCropImage" alt="">
                    </div>
                    <div class="cropper-toolbar" aria-label="{{ __('profile.crop_photo') }}">
                        <button type="button" class="table-button" data-cropper-action="zoom-in" title="{{ __('profile.zoom_in') }}" aria-label="{{ __('profile.zoom_in') }}">
                            <i class="bi bi-zoom-in" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="table-button" data-cropper-action="zoom-out" title="{{ __('profile.zoom_out') }}" aria-label="{{ __('profile.zoom_out') }}">
                            <i class="bi bi-zoom-out" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="table-button" data-cropper-action="rotate-left" title="{{ __('profile.rotate_left') }}" aria-label="{{ __('profile.rotate_left') }}">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="table-button" data-cropper-action="rotate-right" title="{{ __('profile.rotate_right') }}" aria-label="{{ __('profile.rotate_right') }}">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('profile.cancel') }}</button>
                    <button type="button" class="primary-action" id="profileCropSubmit">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        {{ __('profile.save_photo') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script src="{{ asset('js/profile.js') }}"></script>
</body>
</html>

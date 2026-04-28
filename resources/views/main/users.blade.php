<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.users') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $totalUsers = $users->total();
        $hasUserErrors = $errors->hasAny(['name', 'email', 'password', 'password_confirmation', 'role', 'site_id', 'modules', 'phone_number', 'grade', 'address']);
        $isEditingUser = old('form_mode') === 'edit' && old('user_id');
        $userFormAction = $isEditingUser ? route('main.users.update', old('user_id')) : route('main.users.store');
        $siteModules = $siteOptions->mapWithKeys(fn ($site) => [
            (string) $site->id => [
                'modules' => array_values($site->modules ?? []),
                'labels' => collect($site->modules ?? [])->mapWithKeys(fn ($module) => [$module => $moduleLabels[$module] ?? $module])->all(),
            ],
        ])->all();
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
                        <span class="avatar">{{ $initial }}</span>
                        <span class="profile-name">{{ $user->name }}</span>
                        <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                        <div class="profile-summary">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                            <em>{{ __('main.admin_badge') }}</em>
                        </div>
                        <a href="#" class="profile-link">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            {{ __('main.profile') }}
                        </a>
                        <a href="{{ route('main.users') }}" class="profile-link">
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
            <a class="back-link" href="{{ route('main') }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ __('main.back_to_companies') }}
            </a>

            <section class="page-heading">
                <div>
                    <h1>{{ __('main.users') }}</h1>
                    <p>{{ __('main.users_subtitle') }}</p>
                </div>
                <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#userModal" data-user-mode="create" @disabled($siteOptions->isEmpty())>
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    {{ __('admin.new_user') }}
                </button>
            </section>

            @if (session('success') || $errors->any())
                <div class="flash-toast {{ session('toast_type') === 'danger' || $errors->any() ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                    <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                    <span>{{ session('success') ?: $errors->first() }}</span>
                    <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <span class="flash-progress" aria-hidden="true"></span>
                </div>
            @endif

            <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                <label class="search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                </label>
                <span class="row-count">
                    <strong id="visibleCount">{{ $users->count() }}</strong>
                    /
                    <strong>{{ $totalUsers }}</strong>
                    {{ __('admin.rows') }}
                </span>
            </section>

            <section class="company-card">
                <div class="table-responsive">
                    <table class="company-table users-table" id="companyTable">
                        <thead>
                            <tr>
                                <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="1">{{ __('admin.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="text">{{ __('admin.role') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.site') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th>{{ __('main.permissions') }}</th>
                                <th class="text-end">{{ __('admin.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $account)
                                @php
                                    $isManagedAdmin = $account->isAdmin();
                                    $assignedSite = $account->sites->first();
                                    $sitePermissions = $assignedSite ? json_decode($assignedSite->pivot->module_permissions ?: '[]', true) : [];
                                    $enabledModules = array_keys($sitePermissions ?: []);
                                    $assignedSiteId = $isManagedAdmin ? '' : $assignedSite?->id;
                                    $assignedSiteLabel = $isManagedAdmin ? __('main.all_sites') : ($assignedSite?->name ?? '-');
                                    $roleLabel = match ($account->role) {
                                        \App\Models\User::ROLE_ADMIN => __('admin.admin_role'),
                                        default => __('admin.user_role'),
                                    };
                                @endphp
                                <tr @class(['user-edit-row' => ! $user->is($account)])
                                    @if (! $user->is($account))
                                        data-user-mode="edit"
                                        data-user-action="{{ route('main.users.update', $account) }}"
                                        data-user-id="{{ $account->id }}"
                                        data-user-name="{{ $account->name }}"
                                        data-user-email="{{ $account->email }}"
                                        data-user-role="{{ $account->role }}"
                                        data-user-subscription-id="{{ $user->subscription_id }}"
                                        data-user-phone="{{ $account->phone_number }}"
                                        data-user-grade="{{ $account->grade }}"
                                        data-user-address="{{ $account->address }}"
                                        data-user-site-id="{{ $assignedSiteId }}"
                                        data-user-modules="{{ e(json_encode($enabledModules)) }}"
                                        data-user-module-permissions="{{ e(json_encode($sitePermissions)) }}"
                                    @endif
                                >
                                    <td>{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
                                    <td>
                                        <span class="user-identity">
                                            <span class="avatar">{{ strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                                            <span>
                                                <strong>{{ $account->name }}</strong>
                                                <small>{{ $account->email }}</small>
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-pill role-{{ $account->role }}">{{ $roleLabel }}</span>
                                    </td>
                                    <td>{{ $assignedSiteLabel }}</td>
                                    <td>
                                        @if ($isManagedAdmin)
                                            <span class="module-pill">{{ __('main.all_permissions') }}</span>
                                        @else
                                            <span class="module-list">
                                                @forelse ($enabledModules as $module)
                                                    <span class="module-pill">{{ $moduleLabels[$module] ?? $module }}</span>
                                                @empty
                                                    -
                                                @endforelse
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (! $user->is($account))
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-history" data-login-history-trigger data-login-history-url="{{ route('main.users.login-history', $account) }}" data-login-history-name="{{ $account->name }}" aria-label="{{ __('main.history') }}">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#userModal" data-user-mode="edit" data-user-action="{{ route('main.users.update', $account) }}" data-user-id="{{ $account->id }}" data-user-name="{{ $account->name }}" data-user-email="{{ $account->email }}" data-user-role="{{ $account->role }}" data-user-subscription-id="{{ $user->subscription_id }}" data-user-phone="{{ $account->phone_number }}" data-user-grade="{{ $account->grade }}" data-user-address="{{ $account->address }}" data-user-site-id="{{ $assignedSiteId }}" data-user-modules="{{ e(json_encode($enabledModules)) }}" data-user-module-permissions="{{ e(json_encode($sitePermissions)) }}" aria-label="{{ __('admin.edit') }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="POST" action="{{ route('main.users.destroy', $account) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('admin.delete_user_title') }}" data-delete-text="{{ __('admin.delete_user_text', ['name' => $account->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="table-actions">
                                                <button type="button" class="table-button table-button-history" data-login-history-trigger data-login-history-url="{{ route('main.users.login-history', $account) }}" data-login-history-name="{{ $account->name }}" aria-label="{{ __('main.history') }}">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-row"><td colspan="6">{{ __('admin.no_users') }}</td></tr>
                            @endforelse
                            <tr class="empty-row search-empty-row" hidden><td colspan="6">{{ __('admin.no_results') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($users->hasPages())
                <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                    <span>{{ __('admin.showing') }} <strong>{{ $users->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $users->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalUsers }}</strong></span>
                    <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                        @if ($users->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $users->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                        @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                            @if ($page === $users->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                        @endforeach
                        @if ($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                    </nav>
                </section>
            @endif
        </main>
    </div>

    <div class="modal fade subscription-modal user-modal main-user-modal" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form user-form" method="POST" action="{{ $userFormAction }}" data-create-action="{{ route('main.users.store') }}" data-title-create="{{ __('admin.new_user') }}" data-title-edit="{{ __('admin.edit_user') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="userMethod" value="PUT" @disabled(! $isEditingUser)>
                <input type="hidden" name="form_mode" id="userFormMode" value="{{ $isEditingUser ? 'edit' : 'create' }}">
                <input type="hidden" name="user_id" id="userId" value="{{ old('user_id') }}">
                <input type="hidden" name="subscription_id" id="userSubscription" value="{{ $user->subscription_id }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="userModalLabel"><i class="bi bi-person-plus" aria-hidden="true"></i>{{ $isEditingUser ? __('admin.edit_user') : __('admin.new_user') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userName" class="form-label">{{ __('admin.name') }} *</label>
                            <input id="userName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" data-required-message="{{ __('admin.required_user_name') }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_name') }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="userEmail" class="form-label">{{ __('admin.email') }} *</label>
                            <input id="userEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" data-required-message="{{ __('admin.required_user_email') }}" data-email-message="{{ __('admin.invalid_user_email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_email') }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="userPhone" class="form-label">{{ __('admin.phone') }}</label>
                            <input id="userPhone" name="phone_number" type="text" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number') }}">
                            @error('phone_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="userGrade" class="form-label">{{ __('admin.grade') }}</label>
                            <input id="userGrade" name="grade" type="text" class="form-control @error('grade') is-invalid @enderror" value="{{ old('grade') }}">
                            @error('grade')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="userAddress" class="form-label">{{ __('admin.address') }}</label>
                            <input id="userAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="userSite" class="form-label">{{ __('main.site') }} <span data-user-site-required>*</span></label>
                            <select id="userSite" name="site_id" class="form-select @error('site_id') is-invalid @enderror" data-required-message="{{ __('main.required_user_site') }}">
                                <option value="">{{ __('main.select_site') }}</option>
                                @foreach ($siteOptions as $site)
                                    <option value="{{ $site->id }}" @selected((string) old('site_id') === (string) $site->id)>{{ $site->name }} - {{ $site->company?->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="adminAllSitesNotice" hidden>{{ __('main.admin_all_sites_notice') }}</small>
                            @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('main.required_user_site') }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="userRole" class="form-label">{{ __('admin.role') }} *</label>
                            <select id="userRole" name="role" class="form-select @error('role') is-invalid @enderror" data-required-message="{{ __('admin.required_user_role') }}">
                                <option value="user" @selected(old('role', 'user') === 'user')>{{ __('admin.user_role') }}</option>
                                <option value="admin" @selected(old('role') === 'admin')>{{ __('admin.admin_role') }}</option>
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_role') }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label for="userPassword" class="form-label" id="userPasswordLabel" data-create-label="{{ __('admin.password') }} *" data-edit-label="{{ __('admin.password_optional_edit') }}">{{ $isEditingUser ? __('admin.password_optional_edit') : __('admin.password').' *' }}</label>
                            <div class="password-control">
                                <input id="userPassword" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" data-required-message="{{ __('admin.required_admin_password') }}" data-password-rules-target="userPasswordRules" data-password-optional="{{ $isEditingUser ? 'true' : 'false' }}">
                                <button type="button" class="password-toggle" data-password-toggle="userPassword" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_admin_password') }}</div>@enderror
                            <div class="password-rules" id="userPasswordRules" aria-live="polite">
                                <span class="password-rule" data-rule="length">{{ __('admin.password_rule_length') }}</span>
                                <span class="password-rule" data-rule="case">{{ __('admin.password_rule_case') }}</span>
                                <span class="password-rule" data-rule="alphanumeric">{{ __('admin.password_rule_alphanumeric') }}</span>
                                <span class="password-rule" data-rule="special">{{ __('admin.password_rule_special') }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="userPasswordConfirmation" class="form-label" id="userPasswordConfirmationLabel" data-create-label="{{ __('admin.password_confirmation') }} *" data-edit-label="{{ __('admin.password_confirmation_optional_edit') }}">{{ $isEditingUser ? __('admin.password_confirmation_optional_edit') : __('admin.password_confirmation').' *' }}</label>
                            <div class="password-control">
                                <input id="userPasswordConfirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password" data-required-message="{{ __('admin.required_admin_password_confirmation') }}" data-password-confirmation-for="userPassword" data-password-match-target="userPasswordMatch">
                                <button type="button" class="password-toggle" data-password-toggle="userPasswordConfirmation" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            @error('password_confirmation')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_admin_password_confirmation') }}</div>@enderror
                            <div class="password-match-feedback" id="userPasswordMatch" data-valid-message="{{ __('admin.password_confirmation_match') }}" data-invalid-message="{{ __('admin.password_confirmation_mismatch') }}" data-empty-message="{{ __('admin.required_admin_password_confirmation') }}" aria-live="polite"></div>
                        </div>
                    </div>

                    <section class="permission-panel">
                        <h3><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('main.module_permissions') }}</h3>
                        <p class="permission-empty" id="modulePermissionsEmpty">{{ __('main.select_site_for_modules') }}</p>
                        <div class="permission-table-wrap" id="modulePermissionsWrap" hidden>
                            <table class="permission-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('main.module') }}</th>
                                        <th>{{ __('main.access') }}</th>
                                        <th>{{ __('main.can_create') }}</th>
                                        <th>{{ __('main.can_update') }}</th>
                                        <th>{{ __('main.can_delete') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="modulePermissionsBody"></tbody>
                            </table>
                            @error('modules')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="modal-submit" id="userSubmit">{{ $isEditingUser ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade subscription-modal login-history-modal" id="loginHistoryModal" tabindex="-1" aria-labelledby="loginHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="loginHistoryModalLabel"><i class="bi bi-clock-history" aria-hidden="true"></i>{{ __('main.login_history') }}</h2>

                    <div class="login-history-state" id="loginHistoryState">{{ __('main.loading_history') }}</div>

                    <div class="login-history-table-wrap" id="loginHistoryTableWrap" hidden>
                        <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                            <label class="search-box">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" id="loginHistorySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                            </label>
                            <span class="row-count">
                                <strong id="loginHistoryVisibleCount">0</strong>
                                /
                                <strong id="loginHistoryTotalCount">0</strong>
                                {{ __('admin.rows') }}
                            </span>
                        </section>
                        <div class="login-history-table-frame">
                            <table class="login-history-table">
                                <thead>
                                    <tr>
                                        <th><button class="table-sort" type="button" data-history-sort="date">{{ __('main.number') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-history-sort="device">{{ __('main.device') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-history-sort="ip">{{ __('main.ip_address') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-history-sort="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    </tr>
                                </thead>
                                <tbody id="loginHistoryBody"></tbody>
                            </table>
                        </div>
                        <div class="login-history-footer">
                            <span id="loginHistoryCount"></span>
                            <nav class="pagination-shell" id="loginHistoryPagination" aria-label="{{ __('admin.pagination') }}"></nav>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasUserErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        (() => {
            const modalElement = document.getElementById('loginHistoryModal');
            const modalTitle = document.getElementById('loginHistoryModalLabel');
            const state = document.getElementById('loginHistoryState');
            const tableWrap = document.getElementById('loginHistoryTableWrap');
            const body = document.getElementById('loginHistoryBody');
            const count = document.getElementById('loginHistoryCount');
            const visibleCount = document.getElementById('loginHistoryVisibleCount');
            const totalCount = document.getElementById('loginHistoryTotalCount');
            const search = document.getElementById('loginHistorySearch');
            const pagination = document.getElementById('loginHistoryPagination');
            let currentUrl = '';
            let currentName = '';
            let currentSort = 'date';
            let currentDirection = 'desc';

            const escapeHtml = (value = '') => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const loadHistory = async (url, page = 1) => {
                currentUrl = url;
                state.hidden = false;
                state.textContent = @json(__('main.loading_history'));
                tableWrap.hidden = false;
                body.innerHTML = '';
                pagination.innerHTML = '';

                const params = new URLSearchParams({
                    page,
                    sort: currentSort,
                    direction: currentDirection,
                    search: search?.value || '',
                });
                const response = await fetch(`${url}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const payload = await response.json();

                modalTitle.innerHTML = `<i class="bi bi-clock-history" aria-hidden="true"></i>${escapeHtml(@json(__('main.login_history_for', ['name' => '__NAME__'])).replace('__NAME__', currentName))}`;

                if (!payload.data.length) {
                    state.hidden = true;
                    tableWrap.hidden = false;
                    body.innerHTML = `<tr class="empty-row"><td colspan="4">${escapeHtml(@json(__('main.no_login_history')))}</td></tr>`;
                    count.textContent = '';
                    visibleCount.textContent = '0';
                    totalCount.textContent = String(payload.meta.total);
                    return;
                }

                payload.data.forEach((entry, index) => {
                    const number = ((payload.meta.current_page - 1) * 5) + index + 1;
                    body.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${number}</td>
                            <td>${escapeHtml(entry.device)}</td>
                            <td>${escapeHtml(entry.ip)}</td>
                            <td>${escapeHtml(entry.date)}</td>
                        </tr>
                    `);
                });

                state.hidden = true;
                tableWrap.hidden = false;
                count.textContent = `${@json(__('admin.showing'))} ${payload.meta.from} ${@json(__('admin.to'))} ${payload.meta.to} ${@json(__('admin.on'))} ${payload.meta.total}`;
                visibleCount.textContent = String(payload.data.length);
                totalCount.textContent = String(payload.meta.total);
                pagination.hidden = payload.meta.total <= 5;

                const pageButton = (label, targetPage, disabled = false, active = false) => {
                    const tag = disabled || active ? 'span' : 'button';
                    const attrs = tag === 'button' ? ` type="button" data-history-page="${targetPage}"` : '';
                    return `<${tag}${attrs} class="${active ? 'active' : disabled ? 'disabled' : ''}">${label}</${tag}>`;
                };

                pagination.insertAdjacentHTML('beforeend', pageButton('Previous', payload.meta.current_page - 1, payload.meta.current_page === 1));

                for (let pageNumber = 1; pageNumber <= payload.meta.last_page; pageNumber += 1) {
                    pagination.insertAdjacentHTML('beforeend', pageButton(pageNumber, pageNumber, false, pageNumber === payload.meta.current_page));
                }

                pagination.insertAdjacentHTML('beforeend', pageButton('Next', payload.meta.current_page + 1, payload.meta.current_page === payload.meta.last_page));
            };

            pagination?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-history-page]');

                if (!button) {
                    return;
                }

                loadHistory(currentUrl, button.dataset.historyPage);
            });

            let searchTimer = null;
            search?.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => loadHistory(currentUrl), 250);
            });

            modalElement?.querySelectorAll('[data-history-sort]').forEach((button) => {
                button.addEventListener('click', () => {
                    const nextSort = button.dataset.historySort;
                    currentDirection = currentSort === nextSort && currentDirection === 'asc' ? 'desc' : 'asc';
                    currentSort = nextSort;
                    modalElement.querySelectorAll('[data-history-sort]').forEach((sortButton) => {
                        sortButton.classList.remove('is-sorted-asc', 'is-sorted-desc');
                    });
                    button.classList.add(currentDirection === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
                    loadHistory(currentUrl);
                });
            });

            document.querySelectorAll('[data-login-history-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    currentName = trigger.dataset.loginHistoryName || '';
                    currentSort = 'date';
                    currentDirection = 'desc';
                    if (search) {
                        search.value = '';
                    }
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                    loadHistory(trigger.dataset.loginHistoryUrl);
                });
            });
        })();

        (() => {
            const siteModules = @json($siteModules);
            const oldSiteId = @json(old('site_id'));
            const oldModules = @json(old('modules', []));
            const oldPermissions = @json(old('module_permissions', []));
            const siteSelect = document.getElementById('userSite');
            const roleSelect = document.getElementById('userRole');
            const siteRequiredMarker = document.querySelector('[data-user-site-required]');
            const adminAllSitesNotice = document.getElementById('adminAllSitesNotice');
            const body = document.getElementById('modulePermissionsBody');
            const wrap = document.getElementById('modulePermissionsWrap');
            const empty = document.getElementById('modulePermissionsEmpty');
            const emptyDefaultText = empty?.textContent || '';

            const checked = (value) => value ? ' checked' : '';
            const disabled = (value) => value ? ' disabled' : '';
            const escapeHtml = (value = '') => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const currentState = () => {
                const modules = [];
                const permissions = {};

                body.querySelectorAll('tr').forEach((row) => {
                    const moduleInput = row.querySelector('input[name="modules[]"]');

                    if (!moduleInput) {
                        return;
                    }

                    const module = moduleInput.value;
                    permissions[module] = {};

                    row.querySelectorAll('[data-permission]').forEach((permissionInput) => {
                        permissions[module][permissionInput.dataset.permission] = permissionInput.checked;
                    });

                    if (moduleInput.checked) {
                        modules.push(module);
                    }
                });

                return { modules, permissions };
            };

            const syncAdminPermissionState = () => {
                const isAdminRole = roleSelect?.value === 'admin';

                if (siteSelect) {
                    siteSelect.disabled = isAdminRole;

                    if (isAdminRole) {
                        siteSelect.value = '';
                    }
                }

                if (siteRequiredMarker) {
                    siteRequiredMarker.hidden = isAdminRole;
                }

                if (adminAllSitesNotice) {
                    adminAllSitesNotice.hidden = !isAdminRole;
                }

                if (empty) {
                    empty.textContent = isAdminRole ? @json(__('main.admin_all_sites_notice')) : emptyDefaultText;
                }

                body.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                    if (isAdminRole) {
                        input.checked = true;
                        input.disabled = true;
                    } else {
                        input.disabled = false;
                    }
                });
            };

            const renderModules = (siteId, selectedModules = [], permissions = {}) => {
                const config = siteModules[String(siteId)] || { modules: [], labels: {} };
                const isAdminRole = roleSelect?.value === 'admin';
                body.innerHTML = '';
                wrap.hidden = config.modules.length === 0;
                empty.hidden = config.modules.length > 0;

                config.modules.forEach((module) => {
                    const modulePermissions = permissions[module] || {};
                    const selected = isAdminRole || selectedModules.includes(module);
                    body.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td><span class="module-pill">${escapeHtml(config.labels[module] || module)}</span></td>
                            <td><input class="form-check-input" type="checkbox" name="modules[]" value="${escapeHtml(module)}"${checked(selected)}${disabled(isAdminRole)}></td>
                            <td><input class="form-check-input" type="checkbox" name="module_permissions[${escapeHtml(module)}][can_create]" value="1" data-permission="can_create"${checked(isAdminRole || modulePermissions.can_create)}${disabled(isAdminRole)}></td>
                            <td><input class="form-check-input" type="checkbox" name="module_permissions[${escapeHtml(module)}][can_update]" value="1" data-permission="can_update"${checked(isAdminRole || modulePermissions.can_update)}${disabled(isAdminRole)}></td>
                            <td><input class="form-check-input" type="checkbox" name="module_permissions[${escapeHtml(module)}][can_delete]" value="1" data-permission="can_delete"${checked(isAdminRole || modulePermissions.can_delete)}${disabled(isAdminRole)}></td>
                        </tr>
                    `);
                });

                syncAdminPermissionState();
            };

            siteSelect?.addEventListener('change', () => {
                renderModules(siteSelect.value, [], {});
                syncAdminPermissionState();
            });
            roleSelect?.addEventListener('change', () => {
                const state = currentState();
                renderModules(roleSelect.value === 'admin' ? '' : (siteSelect?.value || ''), state.modules, state.permissions);
                syncAdminPermissionState();
            });

            document.querySelectorAll('[data-user-mode]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const isEdit = trigger.dataset.userMode === 'edit';
                    const modules = isEdit ? JSON.parse(trigger.dataset.userModules || '[]') : [];
                    const permissions = isEdit ? JSON.parse(trigger.dataset.userModulePermissions || '{}') : {};
                    siteSelect.value = isEdit ? (trigger.dataset.userSiteId || '') : '';
                    renderModules(roleSelect.value === 'admin' ? '' : siteSelect.value, modules, permissions);
                    syncAdminPermissionState();
                });
            });

            renderModules(oldSiteId || siteSelect?.value || '', oldModules, oldPermissions);
            syncAdminPermissionState();
        })();
    </script>
</body>
</html>

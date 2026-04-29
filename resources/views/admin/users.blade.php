<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.users') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $totalUsers = $users->total();
        $hasUserErrors = $errors->hasAny(['name', 'email', 'password', 'password_confirmation', 'role', 'subscription_id', 'phone_number', 'grade', 'address']);
        $isEditingUser = old('form_mode') === 'edit' && old('user_id');
        $userFormAction = $isEditingUser ? route('admin.users.update', old('user_id')) : route('admin.users.store');
    @endphp

    <div class="dashboard-shell main-shell" data-theme="light">
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
                <a class="nav-link active" href="{{ route('admin.users') }}">
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

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('admin.users') }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / {{ __('admin.users') }}</p>
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

            
            @if (session('success'))
                <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                    <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <span class="flash-progress" aria-hidden="true"></span>
                </div>
            @endif
            <section class="dashboard-content subscriptions-page users-page">
                <div class="subscription-actions">
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#userModal" data-user-mode="create">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                        {{ __('admin.new_user') }}
                    </button>
                </div>

                <section class="table-tools subscriptions-tools" aria-label="{{ __('admin.search_tools') }}">
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

                <section class="admin-table-card">
                    <div class="table-responsive">
                        <table class="admin-table users-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="text">{{ __('admin.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="text">{{ __('admin.email') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="text">{{ __('admin.role') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="text">{{ __('admin.subscription') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5" data-sort-type="text">{{ __('admin.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $account)
                                    @php
                                        $subscription = $account->subscription;
                                        $isActive = $subscription ? $subscription->isCurrentlyActive() : true;
                                        $subscriptionType = $subscription ? strtoupper($subscription->type ?? 'standard') : null;
                                        $subscriptionLimit = $subscription ? match ($subscription->type ?? 'standard') {
                                            'business' => __('admin.unlimited'),
                                            'pro' => __('admin.two_companies'),
                                            default => __('admin.one_company'),
                                        } : null;
                                        $roleLabel = match ($account->role) {
                                            \App\Models\User::ROLE_SUPERADMIN => __('admin.superadmin_role'),
                                            \App\Models\User::ROLE_ADMIN => __('admin.admin_role'),
                                            default => __('admin.user_role'),
                                        };
                                    @endphp
                                    <tr class="{{ $account->isSuperadmin() ? '' : 'user-edit-row' }}"
                                        @unless ($account->isSuperadmin())
                                            data-user-mode="edit"
                                            data-user-action="{{ route('admin.users.update', $account) }}"
                                            data-user-id="{{ $account->id }}"
                                            data-user-name="{{ $account->name }}"
                                            data-user-email="{{ $account->email }}"
                                            data-user-role="{{ $account->role }}"
                                            data-user-subscription-id="{{ $account->subscription_id }}"
                                            data-user-phone="{{ $account->phone_number }}"
                                            data-user-grade="{{ $account->grade }}"
                                            data-user-address="{{ $account->address }}"
                                        @endunless
                                    >
                                        <td>{{ $users->firstItem() + $loop->index }}</td>
                                        <td class="subscription-name">
                                            <span class="user-identity">
                                                @include('partials.user-avatar', ['avatarUser' => $account])
                                                <strong>{{ $account->name }}</strong>
                                            </span>
                                        </td>
                                        <td>{{ $account->email }}</td>
                                        <td>
                                            <span class="status-pill role-{{ $account->role }}">{{ $roleLabel }}</span>
                                        </td>
                                        <td>
                                            @if ($subscription)
                                                <button
                                                    class="linked-subscription"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#subscriptionDetailsModal"
                                                    data-subscription-detail
                                                    data-name="{{ $subscription->name }}"
                                                    data-type="{{ __('admin.'.strtolower($subscriptionType)) }}"
                                                    data-type-class="type-{{ strtolower($subscriptionType) }}"
                                                    data-limit="{{ $subscriptionLimit }}"
                                                    data-status="{{ $isActive ? __('admin.up_to_date') : __('admin.expired') }}"
                                                    data-status-class="{{ $isActive ? 'is-active' : 'is-expired' }}"
                                                    data-expiration="{{ $subscription->expires_at?->format('d/m/Y') ?? '-' }}"
                                                    data-expiration-icon="{{ $isActive ? 'bi-calendar3' : 'bi-exclamation-circle' }}"
                                                    data-created="{{ $subscription->created_at?->format('d/m/Y') ?? '-' }}"
                                                    data-users="{{ $subscription->users_count ?? 0 }}"
                                                    data-companies="{{ $subscription->companies_count ?? 0 }}"
                                                >
                                                    <i class="bi bi-stack" aria-hidden="true"></i>
                                                    {{ $subscription->name }}
                                                </button>
                                            @else
                                                <span class="muted-dash">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $isActive ? 'is-active' : 'is-expired' }}">
                                                <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                                {{ $isActive ? __('admin.up_to_date') : __('admin.expired') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button
                                                    type="button"
                                                    class="table-button table-button-history"
                                                    aria-label="{{ __('main.history') }}"
                                                    data-login-history-trigger
                                                    data-login-history-url="{{ route('admin.users.login-history', $account) }}"
                                                    data-login-history-name="{{ $account->name }}"
                                                >
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </button>
                                                @if (! $account->isSuperadmin())
                                                    <button
                                                        type="button"
                                                        class="table-button table-button-edit"
                                                        aria-label="{{ __('admin.edit') }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#userModal"
                                                        data-user-mode="edit"
                                                        data-user-action="{{ route('admin.users.update', $account) }}"
                                                        data-user-id="{{ $account->id }}"
                                                        data-user-name="{{ $account->name }}"
                                                        data-user-email="{{ $account->email }}"
                                                        data-user-role="{{ $account->role }}"
                                                        data-user-subscription-id="{{ $account->subscription_id }}"
                                                        data-user-phone="{{ $account->phone_number }}"
                                                        data-user-grade="{{ $account->grade }}"
                                                        data-user-address="{{ $account->address }}"
                                                    >
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <form class="user-delete-form" method="POST" action="{{ route('admin.users.destroy', $account) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="button"
                                                            class="table-button table-button-delete"
                                                            aria-label="{{ __('admin.delete') }}"
                                                            data-delete-trigger
                                                            data-delete-title="{{ __('admin.delete_user_title') }}"
                                                            data-delete-text="{{ __('admin.delete_user_text', ['name' => $account->name]) }}"
                                                            data-delete-confirm="{{ __('admin.delete_user_confirm') }}"
                                                            data-delete-cancel="{{ __('admin.delete_user_cancel') }}"
                                                        >
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="7">{{ __('admin.no_users') }}</td>
                                    </tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden>
                                    <td colspan="7">{{ __('admin.no_results') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                    <span>
                        {{ __('admin.showing') }}
                        <strong>{{ $users->firstItem() ?? 0 }}</strong>
                        {{ __('admin.to') }}
                        <strong>{{ $users->lastItem() ?? 0 }}</strong>
                        {{ __('admin.on') }}
                        <strong>{{ $totalUsers }}</strong>
                    </span>
                    <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                        @if ($users->onFirstPage())
                            <span class="disabled">{{ __('admin.previous') }}</span>
                        @else
                            <a href="{{ $users->previousPageUrl() }}">{{ __('admin.previous') }}</a>
                        @endif

                        @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                            @if ($page === $users->currentPage())
                                <span class="active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}">{{ __('admin.next') }}</a>
                        @else
                            <span class="disabled">{{ __('admin.next') }}</span>
                        @endif
                    </nav>
                </section>
            </section>
        </main>
    </div>



    <div class="modal fade subscription-modal user-modal" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form user-form" method="POST" action="{{ $userFormAction }}" data-create-action="{{ route('admin.users.store') }}" data-title-create="{{ __('admin.new_user') }}" data-title-edit="{{ __('admin.edit_user') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="userMethod" value="PUT" @disabled(! $isEditingUser)>
                <input type="hidden" name="form_mode" id="userFormMode" value="{{ $isEditingUser ? 'edit' : 'create' }}">
                <input type="hidden" name="user_id" id="userId" value="{{ old('user_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <h2 id="userModalLabel">{{ $isEditingUser ? __('admin.edit_user') : __('admin.new_user') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userName" class="form-label">{{ __('admin.name') }} *</label>
                            <input id="userName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" data-required-message="{{ __('admin.required_user_name') }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_name') }}</div>@enderror
                            <div class="valid-feedback">{{ __('admin.valid_user_name') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="userEmail" class="form-label">{{ __('admin.email') }} *</label>
                            <input id="userEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" data-required-message="{{ __('admin.required_user_email') }}" data-email-message="{{ __('admin.invalid_user_email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_email') }}</div>@enderror
                            <div class="valid-feedback">{{ __('admin.valid_user_email') }}</div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="userPassword" class="form-label" id="userPasswordLabel" data-create-label="{{ __('admin.password') }} *" data-edit-label="{{ __('admin.password_optional_edit') }}">{{ $isEditingUser ? __('admin.password_optional_edit') : __('admin.password').' *' }}</label>
                        <div class="password-control">
                            <input id="userPassword" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" data-required-message="{{ __('admin.required_admin_password') }}" data-password-rules-target="userPasswordRules" data-password-optional="{{ $isEditingUser ? 'true' : 'false' }}">
                            <button type="button" class="password-toggle" data-password-toggle="userPassword" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_admin_password') }}</div>@enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_password') }}</div>
                        <div class="password-rules" id="userPasswordRules" aria-live="polite">
                            <span class="password-rule" data-rule="length">{{ __('admin.password_rule_length') }}</span>
                            <span class="password-rule" data-rule="case">{{ __('admin.password_rule_case') }}</span>
                            <span class="password-rule" data-rule="alphanumeric">{{ __('admin.password_rule_alphanumeric') }}</span>
                            <span class="password-rule" data-rule="special">{{ __('admin.password_rule_special') }}</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="userPasswordConfirmation" class="form-label" id="userPasswordConfirmationLabel" data-create-label="{{ __('admin.password_confirmation') }} *" data-edit-label="{{ __('admin.password_confirmation_optional_edit') }}">{{ $isEditingUser ? __('admin.password_confirmation_optional_edit') : __('admin.password_confirmation').' *' }}</label>
                        <div class="password-control">
                            <input id="userPasswordConfirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password" data-required-message="{{ __('admin.required_admin_password_confirmation') }}" data-password-confirmation-for="userPassword" data-password-match-target="userPasswordMatch">
                            <button type="button" class="password-toggle" data-password-toggle="userPasswordConfirmation" aria-label="{{ __('admin.password_toggle') }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                        @error('password_confirmation')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_admin_password_confirmation') }}</div>@enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_password_confirmation') }}</div>
                        <div class="password-match-feedback" id="userPasswordMatch" data-valid-message="{{ __('admin.password_confirmation_match') }}" data-invalid-message="{{ __('admin.password_confirmation_mismatch') }}" data-empty-message="{{ __('admin.required_admin_password_confirmation') }}" aria-live="polite"></div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="userRole" class="form-label">{{ __('admin.role') }} *</label>
                            <select id="userRole" name="role" class="form-select @error('role') is-invalid @enderror" data-required-message="{{ __('admin.required_user_role') }}">
                                <option value="user" @selected(old('role', 'user') === 'user')>{{ __('admin.user_role') }}</option>
                                <option value="admin" @selected(old('role') === 'admin')>{{ __('admin.admin_role') }}</option>
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_role') }}</div>@enderror
                            <div class="valid-feedback">{{ __('admin.valid_user_role') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="userSubscription" class="form-label">{{ __('admin.subscription') }} *</label>
                            <select id="userSubscription" name="subscription_id" class="form-select @error('subscription_id') is-invalid @enderror" data-required-message="{{ __('admin.required_user_subscription') }}">
                                <option value="">{{ __('admin.choose_subscription') }}</option>
                                @foreach ($subscriptionOptions as $subscriptionOption)
                                    <option value="{{ $subscriptionOption->id }}" @selected((string) old('subscription_id') === (string) $subscriptionOption->id)>{{ $subscriptionOption->name }} - {{ __('admin.'.$subscriptionOption->type) }}</option>
                                @endforeach
                            </select>
                            @error('subscription_id')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_subscription') }}</div>@enderror
                            <div class="valid-feedback">{{ __('admin.valid_user_subscription') }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
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
                    </div>

                    <div class="mt-3">
                        <label for="userAddress" class="form-label">{{ __('admin.address') }}</label>
                        <input id="userAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}">
                        @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

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
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
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
    <div class="modal fade subscription-modal details-modal" id="subscriptionDetailsModal" tabindex="-1" aria-labelledby="subscriptionDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>

                    <h2 id="subscriptionDetailsModalLabel" class="details-title">
                        <i class="bi bi-stack" aria-hidden="true"></i>
                        {{ __('admin.subscription_details') }}
                    </h2>

                    <dl class="details-list">
                        <div>
                            <dt>{{ __('admin.name') }}</dt>
                            <dd data-detail-name>-</dd>
                        </div>
                        <div>
                            <dt>{{ __('admin.type') }}</dt>
                            <dd>
                                <span class="status-pill" data-detail-type>-</span>
                                <span class="details-muted">— <span data-detail-limit>-</span></span>
                            </dd>
                        </div>
                        <div>
                            <dt>{{ __('admin.status') }}</dt>
                            <dd><span class="status-pill" data-detail-status><i class="bi bi-circle-fill" aria-hidden="true"></i> -</span></dd>
                        </div>
                        <div>
                            <dt>{{ __('admin.expiration') }}</dt>
                            <dd data-detail-expiration-wrap><i class="bi bi-calendar3" aria-hidden="true"></i> <span data-detail-expiration>-</span></dd>
                        </div>
                        <div>
                            <dt>{{ __('admin.created_at') }}</dt>
                            <dd data-detail-created>-</dd>
                        </div>
                        <div>
                            <dt>{{ __('admin.users') }}</dt>
                            <dd data-detail-users>-</dd>
                        </div>
                        <div>
                            <dt>{{ __('admin.companies') }}</dt>
                            <dd data-detail-companies>-</dd>
                        </div>
                    </dl>

                    <div class="details-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        {{ __('admin.subscription_readonly_note') }}
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

                pagination.insertAdjacentHTML('beforeend', pageButton(@json(__('admin.previous')), payload.meta.current_page - 1, payload.meta.current_page === 1));

                for (let pageNumber = 1; pageNumber <= payload.meta.last_page; pageNumber += 1) {
                    pagination.insertAdjacentHTML('beforeend', pageButton(pageNumber, pageNumber, false, pageNumber === payload.meta.current_page));
                }

                pagination.insertAdjacentHTML('beforeend', pageButton(@json(__('admin.next')), payload.meta.current_page + 1, payload.meta.current_page === payload.meta.last_page));
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
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.subscriptions') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $totalSubscriptions = $subscriptions->total();
        $hasSubscriptionErrors = $errors->hasAny(['name', 'type', 'expires_at']);
        $hasAdminErrors = $errors->hasAny(['admin_name', 'admin_email', 'password', 'password_confirmation', 'admin_subscription_id']);
        $isEditingSubscription = old('form_mode') === 'edit' && old('subscription_id');
        $subscriptionFormAction = $isEditingSubscription
            ? route('admin.subscriptions.update', old('subscription_id'))
            : route('admin.subscriptions.store');
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
                <a class="nav-link active" href="{{ route('admin.subscriptions') }}">
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

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('admin.subscriptions') }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / {{ __('admin.subscriptions') }}</p>
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
                            <span class="avatar">{{ $initial }}</span>
                            <span class="profile-name">{{ $user->name }}</span>
                            <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                            <div class="profile-summary">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                                <em>{{ strtoupper($user->role) }}</em>
                            </div>
                            <a href="#" class="profile-link">
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

            <section class="dashboard-content subscriptions-page">
                <div class="subscription-actions">
                    <button
                        class="primary-action"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#subscriptionModal"
                        data-subscription-mode="create"
                    >
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        {{ __('admin.new_subscription') }}
                    </button>
                    <button class="secondary-action" type="button" data-bs-toggle="modal" data-bs-target="#adminModal">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                        {{ __('admin.create_admin') }}
                    </button>
                </div>

                <section class="table-tools subscriptions-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $subscriptions->count() }}</strong>
                        /
                        <strong>{{ $totalSubscriptions }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="admin-table-card">
                    <div class="table-responsive">
                        <table class="admin-table subscriptions-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1" data-sort-type="text">{{ __('admin.name') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2" data-sort-type="text">{{ __('admin.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="text">{{ __('admin.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="text">{{ __('admin.company_limit') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('admin.users') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('admin.companies') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7" data-sort-type="date">{{ __('admin.expiration') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subscriptions as $subscription)
                                    @php
                                        $isActive = $subscription->isCurrentlyActive();
                                        $type = strtoupper($subscription->type ?? 'standard');
                                        $limit = match ($subscription->type ?? 'standard') {
                                            'business' => __('admin.unlimited'),
                                            'pro' => __('admin.three_companies'),
                                            default => __('admin.one_company'),
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $subscriptions->firstItem() + $loop->index }}</td>
                                        <td class="subscription-name">{{ $subscription->name }}</td>
                                        <td>
                                            <span class="status-pill type-{{ strtolower($type) }}">{{ __('admin.'.strtolower($type)) }}</span>
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $isActive ? 'is-active' : 'is-expired' }}">
                                                <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                                {{ $isActive ? __('admin.up_to_date') : __('admin.expired') }}
                                            </span>
                                        </td>
                                        <td>{{ $limit }}</td>
                                        <td>{{ $subscription->users_count }}</td>
                                        <td>{{ $subscription->companies_count }}</td>
                                        <td class="{{ $isActive ? '' : 'text-danger fw-bold' }}">
                                            <i class="bi {{ $isActive ? 'bi-calendar3' : 'bi-exclamation-circle' }}" aria-hidden="true"></i>
                                            {{ $subscription->expires_at?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button
                                                    type="button"
                                                    class="table-button table-button-edit"
                                                    aria-label="{{ __('admin.edit') }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#subscriptionModal"
                                                    data-subscription-mode="edit"
                                                    data-subscription-id="{{ $subscription->id }}"
                                                    data-subscription-name="{{ $subscription->name }}"
                                                    data-subscription-type="{{ $subscription->type ?? 'standard' }}"
                                                    data-subscription-expires-at="{{ $subscription->expires_at?->toDateString() }}"
                                                    data-subscription-action="{{ route('admin.subscriptions.update', $subscription) }}"
                                                >
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form class="subscription-delete-form" method="POST" action="{{ route('admin.subscriptions.destroy', $subscription) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="button"
                                                        class="table-button table-button-delete"
                                                        aria-label="{{ __('admin.delete') }}"
                                                        data-delete-trigger
                                                        data-delete-title="{{ __('admin.delete_subscription_title') }}"
                                                        data-delete-text="{{ __('admin.delete_subscription_text', ['name' => $subscription->name]) }}"
                                                        data-delete-confirm="{{ __('admin.delete_subscription_confirm') }}"
                                                        data-delete-cancel="{{ __('admin.delete_subscription_cancel') }}"
                                                    >
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="9">{{ __('admin.no_subscriptions') }}</td>
                                    </tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden>
                                    <td colspan="9">{{ __('admin.no_results') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                    <span>
                        {{ __('admin.showing') }}
                        <strong>{{ $subscriptions->firstItem() ?? 0 }}</strong>
                        {{ __('admin.to') }}
                        <strong>{{ $subscriptions->lastItem() ?? 0 }}</strong>
                        {{ __('admin.on') }}
                        <strong>{{ $totalSubscriptions }}</strong>
                    </span>
                    <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                        @if ($subscriptions->onFirstPage())
                            <span class="disabled">{{ __('admin.previous') }}</span>
                        @else
                            <a href="{{ $subscriptions->previousPageUrl() }}">{{ __('admin.previous') }}</a>
                        @endif

                        @foreach ($subscriptions->getUrlRange(1, $subscriptions->lastPage()) as $page => $url)
                            @if ($page === $subscriptions->currentPage())
                                <span class="active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($subscriptions->hasMorePages())
                            <a href="{{ $subscriptions->nextPageUrl() }}">{{ __('admin.next') }}</a>
                        @else
                            <span class="disabled">{{ __('admin.next') }}</span>
                        @endif
                    </nav>
                </section>
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form
                class="modal-content subscription-form"
                method="POST"
                action="{{ $subscriptionFormAction }}"
                data-create-action="{{ route('admin.subscriptions.store') }}"
                data-title-create="{{ __('admin.new_subscription') }}"
                data-title-edit="{{ __('admin.edit') }}"
                data-submit-create="{{ __('admin.create') }}"
                data-submit-edit="{{ __('admin.update') }}"
                novalidate
            >
                @csrf
                <input type="hidden" name="_method" id="subscriptionMethod" value="PUT" @disabled(! $isEditingSubscription)>
                <input type="hidden" name="form_mode" id="subscriptionFormMode" value="{{ $isEditingSubscription ? 'edit' : 'create' }}">
                <input type="hidden" name="subscription_id" id="subscriptionId" value="{{ old('subscription_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <h2 id="subscriptionModalLabel">{{ $isEditingSubscription ? __('admin.edit') : __('admin.new_subscription') }}</h2>

                    <div class="mb-4">
                        <label for="subscriptionName" class="form-label">{{ __('admin.name') }} *</label>
                        <input id="subscriptionName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" data-required-message="{{ __('admin.required_name') }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">{{ __('admin.required_name') }}</div>
                        @enderror
                        <div class="valid-feedback">{{ __('admin.valid_name') }}</div>
                    </div>

                    <div class="modal-fields">
                        <div>
                            <label for="subscriptionType" class="form-label">{{ __('admin.type') }} *</label>
                            <select id="subscriptionType" name="type" class="form-select @error('type') is-invalid @enderror" data-required-message="{{ __('admin.required_type') }}">
                                <option value="">{{ __('admin.select_type') }}</option>
                                <option value="standard" @selected(old('type', $isEditingSubscription ? null : 'standard') === 'standard')>{{ __('admin.standard_option') }}</option>
                                <option value="pro" @selected(old('type') === 'pro')>{{ __('admin.pro_option') }}</option>
                                <option value="business" @selected(old('type') === 'business')>{{ __('admin.business_option') }}</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">{{ __('admin.required_type') }}</div>
                            @enderror
                            <div class="valid-feedback">{{ __('admin.valid_type') }}</div>
                        </div>

                        <div>
                            <label for="subscriptionExpiry" class="form-label">{{ __('admin.expiry_date') }}</label>
                            <input id="subscriptionExpiry" name="expires_at" type="date" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at', $isEditingSubscription ? null : now()->addYear()->toDateString()) }}">
                            @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="modal-submit" id="subscriptionSubmit">{{ $isEditingSubscription ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade subscription-modal admin-modal" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form" method="POST" action="{{ route('admin.admins.store') }}" novalidate>
                @csrf
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                    <h2 id="adminModalLabel">{{ __('admin.create_admin_title') }}</h2>

                    <div class="mb-4">
                        <label for="adminName" class="form-label">{{ __('admin.name') }} *</label>
                        <input id="adminName" name="admin_name" type="text" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" data-required-message="{{ __('admin.required_admin_name') }}">
                        @error('admin_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">{{ __('admin.required_admin_name') }}</div>
                        @enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_name') }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="adminEmail" class="form-label">{{ __('admin.email') }} *</label>
                        <input id="adminEmail" name="admin_email" type="email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" data-required-message="{{ __('admin.required_admin_email') }}" data-email-message="{{ __('admin.invalid_user_email') }}">
                        @error('admin_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">{{ __('admin.required_admin_email') }}</div>
                        @enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_email') }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="adminPassword" class="form-label">{{ __('admin.password') }} *</label>
                        <div class="password-control">
                            <input id="adminPassword" name="password" type="password" class="form-control @error('password') is-invalid @enderror" data-required-message="{{ __('admin.required_admin_password') }}" data-password-rules-target="adminPasswordRules">
                            <button type="button" class="password-toggle" data-password-toggle="adminPassword" aria-label="{{ __('admin.password_toggle') }}">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">{{ __('admin.required_admin_password') }}</div>
                        @enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_password') }}</div>
                        <div class="password-rules" id="adminPasswordRules" aria-live="polite">
                            <span class="password-rule" data-rule="length">{{ __('admin.password_rule_length') }}</span>
                            <span class="password-rule" data-rule="case">{{ __('admin.password_rule_case') }}</span>
                            <span class="password-rule" data-rule="alphanumeric">{{ __('admin.password_rule_alphanumeric') }}</span>
                            <span class="password-rule" data-rule="special">{{ __('admin.password_rule_special') }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="adminPasswordConfirmation" class="form-label">{{ __('admin.password_confirmation') }} *</label>
                        <div class="password-control">
                            <input id="adminPasswordConfirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" data-required-message="{{ __('admin.required_admin_password_confirmation') }}" data-password-confirmation-for="adminPassword" data-password-match-target="adminPasswordMatch">
                            <button type="button" class="password-toggle" data-password-toggle="adminPasswordConfirmation" aria-label="{{ __('admin.password_toggle') }}">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">{{ __('admin.required_admin_password_confirmation') }}</div>
                        @enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_password_confirmation') }}</div>
                        <div class="password-match-feedback" id="adminPasswordMatch" data-valid-message="{{ __('admin.password_confirmation_match') }}" data-invalid-message="{{ __('admin.password_confirmation_mismatch') }}" data-empty-message="{{ __('admin.required_admin_password_confirmation') }}" aria-live="polite"></div>
                    </div>

                    <div class="mb-4">
                        <label for="adminSubscription" class="form-label">{{ __('admin.subscription') }} *</label>
                        <select id="adminSubscription" name="admin_subscription_id" class="form-select @error('admin_subscription_id') is-invalid @enderror" data-required-message="{{ __('admin.required_admin_subscription') }}">
                            <option value="">{{ __('admin.choose_subscription') }}</option>
                            @foreach ($subscriptionOptions as $subscriptionOption)
                                <option value="{{ $subscriptionOption->id }}" @selected((string) old('admin_subscription_id') === (string) $subscriptionOption->id)>
                                    {{ $subscriptionOption->name }} - {{ __('admin.'.$subscriptionOption->type) }}
                                </option>
                            @endforeach
                        </select>
                        @error('admin_subscription_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback">{{ __('admin.required_admin_subscription') }}</div>
                        @enderror
                        <div class="valid-feedback">{{ __('admin.valid_admin_subscription') }}</div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button type="submit" class="modal-submit">{{ __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    @if ($hasSubscriptionErrors)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('subscriptionModal')).show();
            });
        </script>
    @endif
    @if ($hasAdminErrors)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('adminModal')).show();
            });
        </script>
    @endif
</body>
</html>

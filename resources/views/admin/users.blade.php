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
                <a class="nav-link" href="#">
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

            <section class="dashboard-content subscriptions-page users-page">
                <div class="subscription-actions">
                    <button class="primary-action" type="button">
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
                                            'pro' => __('admin.three_companies'),
                                            default => __('admin.one_company'),
                                        } : null;
                                        $roleLabel = match ($account->role) {
                                            \App\Models\User::ROLE_SUPERADMIN => __('admin.superadmin_role'),
                                            \App\Models\User::ROLE_ADMIN => __('admin.admin_role'),
                                            default => __('admin.user_role'),
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $users->firstItem() + $loop->index }}</td>
                                        <td class="subscription-name">{{ $account->name }}</td>
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
                                            @if (! $account->isSuperadmin())
                                                <div class="table-actions">
                                                    <button type="button" class="table-button table-button-edit" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            @endif
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
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>
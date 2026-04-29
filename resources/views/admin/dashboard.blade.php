<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.dashboard') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $kpis = [
            ['label' => __('admin.subscriptions'), 'value' => $stats['subscriptions'], 'icon' => 'bi-stack', 'tone' => 'blue', 'trend' => '+100%'],
            ['label' => __('admin.users'), 'value' => $stats['users'], 'icon' => 'bi-people', 'tone' => 'violet', 'trend' => '+100%'],
            ['label' => __('admin.administrators'), 'value' => $stats['admins'], 'icon' => 'bi-shield-check', 'tone' => 'green', 'trend' => null],
            ['label' => __('admin.active_companies'), 'value' => $stats['companies'], 'icon' => 'bi-buildings', 'tone' => 'amber', 'trend' => '+100%'],
            ['label' => __('admin.sites'), 'value' => $stats['sites'], 'icon' => 'bi-geo-alt', 'tone' => 'rose', 'trend' => null],
        ];
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
                <a class="nav-link active" href="{{ route('admin.dashboard') }}">
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

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('admin.dashboard') }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / {{ __('admin.dashboard') }}</p>
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

            <section class="dashboard-content">
                <div class="period-tabs" aria-label="{{ __('admin.period') }}">
                    <button type="button" data-dashboard-period="week">{{ __('admin.week') }}</button>
                    <button type="button" class="active" data-dashboard-period="month">{{ __('admin.month') }}</button>
                    <button type="button" data-dashboard-period="year">{{ __('admin.year') }}</button>
                </div>

                <section class="kpi-grid" aria-label="{{ __('admin.indicators') }}">
                    @foreach ($kpis as $kpi)
                        <article class="kpi-card kpi-{{ $kpi['tone'] }}">
                            <div class="kpi-icon">
                                <i class="bi {{ $kpi['icon'] }}" aria-hidden="true"></i>
                            </div>
                            @if ($kpi['trend'])
                                <span class="kpi-trend">
                                    <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                    {{ $kpi['trend'] }}
                                </span>
                            @endif
                            <strong>{{ $kpi['value'] }}</strong>
                            <span>{{ $kpi['label'] }}</span>
                        </article>
                    @endforeach
                </section>

                <section class="dashboard-grid">
                    <article class="dashboard-panel panel-wide">
                        <h2>{{ __('admin.subscriptions_evolution') }}</h2>
                        <div class="apex-chart" id="subscriptionsEvolutionChart" aria-label="{{ __('admin.monthly_evolution') }}"></div>
                    </article>

                    <article class="dashboard-panel">
                        <h2>{{ __('admin.roles_distribution') }}</h2>
                        <div class="apex-chart donut-chart" id="rolesDistributionChart" aria-label="{{ __('admin.roles_distribution') }}"></div>
                    </article>

                    <article class="dashboard-panel panel-wide">
                        <h2>{{ __('admin.users_by_company') }}</h2>
                        <div class="apex-chart" id="usersByCompanyChart" aria-label="{{ __('admin.users_by_company') }}"></div>
                    </article>

                    <article class="dashboard-panel">
                        <h2>{{ __('admin.global_activity') }}</h2>
                        <div class="apex-chart compact-chart" id="globalActivityChart" aria-label="{{ __('admin.global_activity') }}"></div>
                    </article>

                    <article class="dashboard-panel panel-wide">
                        <h2>{{ __('admin.top_companies') }}</h2>
                        <div class="top-company-list">
                            @forelse ($topCompanies as $company)
                                <div class="top-company-row">
                                    <span class="rank-badge">{{ $loop->iteration }}</span>
                                    @if ($company['logo_url'])
                                        <span class="company-logo"><img src="{{ $company['logo_url'] }}" alt="{{ $company['name'] }}"></span>
                                    @else
                                        <span class="company-logo placeholder-logo" aria-hidden="true">{{ $company['initial'] }}</span>
                                    @endif
                                    <div class="top-company-main">
                                        <strong>{{ $company['name'] }}</strong>
                                        <span>
                                            <i class="bi bi-people" aria-hidden="true"></i> {{ $company['users_count'] }}
                                            <i class="bi bi-geo-alt ms-2" aria-hidden="true"></i> {{ $company['sites_count'] }}
                                        </span>
                                    </div>
                                    <span class="status-pill is-active"><i class="bi bi-circle-fill" aria-hidden="true"></i>{{ __('admin.up_to_date') }}</span>
                                </div>
                            @empty
                                <p class="empty-panel">{{ __('admin.no_companies') }}</p>
                            @endforelse
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <h2>{{ __('admin.recent_activity') }}</h2>
                        <div class="activity-list">
                            @forelse ($recentActivities as $activity)
                                <div class="activity-item">
                                    @php
                                        $activityIcon = [
                                            'user' => 'bi-person-plus',
                                            'site' => 'bi-geo-alt',
                                            'subscription' => 'bi-stack',
                                        ][$activity['type']] ?? 'bi-circle';
                                    @endphp
                                    <span class="activity-icon activity-{{ $activity['tone'] }}" aria-hidden="true">
                                        <i class="bi {{ $activityIcon }}"></i>
                                    </span>
                                    <div>
                                        <strong>{{ $activity['title'] }}</strong>
                                        <span>{{ $activity['subject'] }}</span>
                                        <em>{{ $activity['time'] }}</em>
                                    </div>
                                </div>
                            @empty
                                <p class="empty-panel">{{ __('admin.no_results') }}</p>
                            @endforelse
                        </div>
                    </article>
                </section>
            </section>
        </main>
    </div>

    <script type="application/json" id="dashboardChartData">@json($chartData)</script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/admin/dashboard.js -->
    <script>{!! file_get_contents(resource_path('js/admin/dashboard.js')) !!}</script>
</body>
</html>

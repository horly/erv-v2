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
        $totalRoles = max(array_sum($roleCounts), 1);
        $adminPercent = round(($roleCounts['admin'] / $totalRoles) * 100);
        $superadminPercent = round(($roleCounts['superadmin'] / $totalRoles) * 100);
        $userPercent = max(0, 100 - $adminPercent - $superadminPercent);
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
                            <a href="#" class="profile-link">
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
                    <button type="button">{{ __('admin.week') }}</button>
                    <button type="button" class="active">{{ __('admin.month') }}</button>
                    <button type="button">{{ __('admin.year') }}</button>
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
                        <div class="line-chart" aria-label="{{ __('admin.monthly_evolution') }}">
                            <svg viewBox="0 0 760 260" role="img" aria-hidden="true">
                                <g class="grid-lines">
                                    <line x1="40" y1="30" x2="735" y2="30" />
                                    <line x1="40" y1="85" x2="735" y2="85" />
                                    <line x1="40" y1="140" x2="735" y2="140" />
                                    <line x1="40" y1="195" x2="735" y2="195" />
                                    <line x1="40" y1="250" x2="735" y2="250" />
                                </g>
                                <line class="axis" x1="40" y1="30" x2="40" y2="250" />
                                <line class="axis" x1="40" y1="250" x2="735" y2="250" />
                                <path class="area-blue" d="M40 250 C210 250 390 250 565 250 C625 247 675 202 735 72 L735 250 Z" />
                                <path class="area-violet" d="M40 250 C220 250 410 250 575 250 C635 248 680 214 735 126 L735 250 Z" />
                                <path class="line-blue" d="M40 250 C210 250 390 250 565 250 C625 247 675 202 735 72" />
                                <path class="line-violet" d="M40 250 C220 250 410 250 575 250 C635 248 680 214 735 126" />
                            </svg>
                            <div class="chart-labels y-labels">
                                <span>16</span><span>12</span><span>8</span><span>4</span><span>0</span>
                            </div>
                            <div class="chart-labels x-labels">
                                <span>Nov 2025</span><span>Dec 2025</span><span>Jan 2026</span><span>Feb 2026</span><span>Mar 2026</span><span>Apr 2026</span>
                            </div>
                            <div class="chart-legend">
                                <span><i class="legend-blue"></i> {{ __('admin.subscriptions') }}</span>
                                <span><i class="legend-violet"></i> {{ __('admin.users') }}</span>
                            </div>
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <h2>{{ __('admin.roles_distribution') }}</h2>
                        <div class="donut-wrap">
                            <div class="role-donut" style="--admin: {{ $adminPercent }}%; --superadmin: {{ $superadminPercent }}%; --user: {{ $userPercent }}%;"></div>
                            <div class="chart-legend role-legend">
                                <span><i class="legend-blue"></i> {{ __('admin.admin_role') }}</span>
                                <span><i class="legend-rose"></i> {{ __('admin.superadmin_role') }}</span>
                                <span><i class="legend-violet"></i> {{ __('admin.user_role') }}</span>
                            </div>
                        </div>
                    </article>

                    <article class="dashboard-panel panel-wide">
                        <h2>{{ __('admin.users_by_company') }}</h2>
                        <div class="bar-chart">
                            <span class="bar-label">EXAD SARL</span>
                            <div class="bar-track">
                                <span style="width: 67%"></span>
                            </div>
                            <span class="bar-value">{{ $stats['users'] }}</span>
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <h2>{{ __('admin.global_activity') }}</h2>
                        <div class="activity-chart">
                            <svg viewBox="0 0 330 190" aria-hidden="true">
                                <g class="grid-lines">
                                    <line x1="30" y1="20" x2="315" y2="20" />
                                    <line x1="30" y1="75" x2="315" y2="75" />
                                    <line x1="30" y1="130" x2="315" y2="130" />
                                </g>
                                <line class="axis" x1="30" y1="20" x2="30" y2="170" />
                                <path class="line-blue" d="M30 170 C110 170 190 170 245 168 C278 166 298 96 315 45" />
                                <circle cx="315" cy="45" r="4" />
                            </svg>
                        </div>
                    </article>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

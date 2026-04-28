<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.company_sites_title', ['name' => $company->name]) }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $siteCount = $sites->count();
        $totalSites = $sites->total();
        $hasReachedSiteLimit = $planRules['site_limit'] !== null && $company->sites_count >= $planRules['site_limit'];
        $siteFormId = 'siteModal';
        $siteModalValidationId = old('_site_modal_id');
        $hasSiteModalValidation = $errors->any() && filled($siteModalValidationId);
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
                            <em>{{ $user->role === 'admin' ? __('main.admin_badge') : strtoupper($user->role) }}</em>
                        </div>
                        <a href="#" class="profile-link">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            {{ __('main.profile') }}
                        </a>
                        @if ($user->isAdmin())
                            <a href="#" class="profile-link">
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

        <main class="content-wrap">
            <a class="back-link" href="{{ route('main') }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ __('main.back_to_companies') }}
            </a>

            <section class="page-heading sites-heading">
                <div class="sites-title-block">
                    @if ($company->logo_url)
                        <span class="company-logo"><img src="{{ $company->logo_url }}" alt="{{ $company->name }}"></span>
                    @else
                        <span class="company-icon">{{ strtoupper(mb_substr($company->name, 0, 1)) }}</span>
                    @endif
                    <span>
                        <h1>{{ __('main.company_sites_title', ['name' => $company->name]) }}</h1>
                        <p>{{ __('main.company_sites_subtitle') }}</p>
                    </span>
                </div>
                <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#{{ $siteFormId }}" @disabled($hasReachedSiteLimit)>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    {{ __('main.new_site') }}
                </button>
            </section>

            @if (session('success') || ($errors->any() && ! $hasSiteModalValidation))
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
                    <input type="search" id="companySearch" placeholder="{{ __('main.search') }}" autocomplete="off">
                </label>
                <span class="row-count">
                    <strong id="visibleCount">{{ $siteCount }}</strong>
                    /
                    <strong>{{ $totalSites }}</strong>
                    {{ __('main.rows') }}
                </span>
            </section>

            <section class="company-card">
                <div class="table-responsive">
                    <table class="company-table sites-table" id="companyTable">
                        <thead>
                            <tr>
                                <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.site') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.city') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.responsible') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th>{{ __('main.modules') }}</th>
                                <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.phone') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                <th class="text-end">{{ __('main.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sites as $site)
                                <tr>
                                    <td>{{ ($sites->firstItem() ?? 1) + $loop->index }}</td>
                                    <td><strong>{{ $site->name }}</strong></td>
                                    <td><span class="site-badge">{{ $typeLabels[$site->type] ?? $site->type }}</span></td>
                                    <td>{{ $site->city ?: '-' }}</td>
                                    <td>{{ $site->responsible?->name ?: '-' }}</td>
                                    <td>
                                        <span class="module-list">
                                            @forelse ($site->modules ?? [] as $module)
                                                <span class="module-pill">{{ $moduleLabels[$module] ?? $module }}</span>
                                            @empty
                                                -
                                            @endforelse
                                        </span>
                                    </td>
                                    <td>{{ $site->phone ?: '-' }}</td>
                                    <td>
                                        <span class="status-pill {{ $site->status === 'active' ? 'is-active' : 'is-expired' }}">
                                            <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                            {{ $site->status === 'active' ? __('main.active') : __('main.inactive') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="table-button" data-bs-toggle="modal" data-bs-target="#siteEditModal-{{ $site->id }}" aria-label="{{ __('admin.edit') }}">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </button>
                                            <form method="POST" action="{{ route('main.companies.sites.destroy', [$company, $site]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('admin.delete_company_title') }}" data-delete-text="{{ $site->name }}" data-delete-confirm="{{ __('admin.delete_company_confirm') }}" data-delete-cancel="{{ __('admin.delete_company_cancel') }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-row"><td colspan="9">{{ __('main.no_sites') }}</td></tr>
                            @endforelse
                            <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($sites->hasPages())
                <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                    <span>{{ __('admin.showing') }} <strong>{{ $sites->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $sites->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalSites }}</strong></span>
                    <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                        @if ($sites->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $sites->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                        @foreach ($sites->getUrlRange(1, $sites->lastPage()) as $page => $url)
                            @if ($page === $sites->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                        @endforeach
                        @if ($sites->hasMorePages())<a href="{{ $sites->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                    </nav>
                </section>
            @endif
        </main>
    </div>

    @include('main.partials.site-form-modal', [
        'modalId' => $siteFormId,
        'title' => __('main.new_site'),
        'action' => route('main.companies.sites.store', $company),
        'method' => null,
        'site' => null,
    ])

    @foreach ($sites as $site)
        @include('main.partials.site-form-modal', [
            'modalId' => 'siteEditModal-'.$site->id,
            'title' => __('main.edit_site'),
            'action' => route('main.companies.sites.update', [$company, $site]),
            'method' => 'PUT',
            'site' => $site,
        ])
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    @if ($hasSiteModalValidation)
        <script data-reopen-site-modal="{{ $siteModalValidationId }}">
            document.addEventListener('DOMContentLoaded', () => {
                const modalElement = document.getElementById(@json($siteModalValidationId));

                if (modalElement && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endif
</body>
</html>

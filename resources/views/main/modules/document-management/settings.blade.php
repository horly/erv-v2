<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.ged_settings') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $selectedMenus = fn ($account) => old('menu_access.'.$account->id, $menuSelections->get($account->id, []));
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'settings'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_settings') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-settings-page document-management-page">
                <a class="back-link" href="{{ route('main.document-management.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.ged_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.ged_settings') }}</h1>
                        <p>{{ __('main.ged_module_settings_subtitle') }}</p>
                    </div>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-exclamation-triangle' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <form method="POST" action="{{ route('main.document-management.settings.update', [$company, $site]) }}" class="accounting-settings-form">
                    @csrf
                    @method('PUT')

                    <section class="settings-panel">
                        <header class="settings-panel-heading">
                            <div class="settings-panel-icon"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></div>
                            <div>
                                <h2>{{ __('main.ged_pdf_identity') }}</h2>
                                <p>{{ __('main.ged_pdf_identity_help') }}</p>
                            </div>
                        </header>

                        <div class="pdf-settings-layout">
                            <div class="pdf-settings-fields">
                                <label class="settings-color-field">
                                    <span>{{ __('main.pdf_primary_color') }}</span>
                                    <input type="color" name="pdf_primary_color" value="{{ old('pdf_primary_color', $settings->pdf_primary_color) }}" aria-label="{{ __('main.pdf_primary_color') }}">
                                    @error('pdf_primary_color')<small class="text-danger">{{ $message }}</small>@enderror
                                </label>
                                <label class="settings-color-field">
                                    <span>{{ __('main.pdf_accent_color') }}</span>
                                    <input type="color" name="pdf_accent_color" value="{{ old('pdf_accent_color', $settings->pdf_accent_color) }}" aria-label="{{ __('main.pdf_accent_color') }}">
                                    @error('pdf_accent_color')<small class="text-danger">{{ $message }}</small>@enderror
                                </label>
                                <label class="settings-color-field">
                                    <span>{{ __('main.pdf_tint_color') }}</span>
                                    <input type="color" name="pdf_tint_color" value="{{ old('pdf_tint_color', $settings->pdf_tint_color) }}" aria-label="{{ __('main.pdf_tint_color') }}">
                                    @error('pdf_tint_color')<small class="text-danger">{{ $message }}</small>@enderror
                                </label>

                                <label class="settings-toggle">
                                    <input type="hidden" name="pdf_show_qr_code" value="0">
                                    <input type="checkbox" name="pdf_show_qr_code" value="1" @checked(old('pdf_show_qr_code', $settings->pdf_show_qr_code))>
                                    <span>{{ __('main.pdf_show_qr_code') }}</span>
                                </label>
                                <label class="settings-toggle">
                                    <input type="hidden" name="pdf_show_footer_branding" value="0">
                                    <input type="checkbox" name="pdf_show_footer_branding" value="1" @checked(old('pdf_show_footer_branding', $settings->pdf_show_footer_branding))>
                                    <span>{{ __('main.pdf_show_footer_branding') }}</span>
                                </label>
                            </div>

                            <div class="pdf-theme-preview" style="--preview-primary: {{ old('pdf_primary_color', $settings->pdf_primary_color) }}; --preview-accent: {{ old('pdf_accent_color', $settings->pdf_accent_color) }}; --preview-tint: {{ old('pdf_tint_color', $settings->pdf_tint_color) }};">
                                <strong>{{ __('main.ged_pdf_preview') }}</strong>
                                <span class="pdf-preview-title">{{ __('main.module_document_management') }}</span>
                                <span class="pdf-preview-rule"><i></i><em></em></span>
                                <span class="pdf-preview-row pdf-preview-head"></span>
                                <span class="pdf-preview-row"></span>
                                <span class="pdf-preview-row alt"></span>
                            </div>
                        </div>
                    </section>

                    <section class="settings-panel">
                        <header class="settings-panel-heading">
                            <div class="settings-panel-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
                            <div>
                                <h2>{{ __('main.ged_menu_access_management') }}</h2>
                                <p>{{ __('main.ged_menu_access_management_help') }}</p>
                            </div>
                        </header>

                        <div class="settings-info">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            {{ __('main.ged_all_access_for_admins_notice') }}
                        </div>

                        @if ($managedUsers->total() > 0)
                            <section class="settings-users-pager-head" aria-label="{{ __('admin.pagination') }}">
                                <span>{{ __('admin.showing') }} <strong>{{ $managedUsers->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $managedUsers->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $managedUsers->total() }}</strong></span>
                                @if ($managedUsers->hasPages())
                                    <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                                        @if ($managedUsers->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $managedUsers->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                                        @foreach ($managedUsers->getUrlRange(1, $managedUsers->lastPage()) as $page => $url)
                                            @if ($page === $managedUsers->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                                        @endforeach
                                        @if ($managedUsers->hasMorePages())<a href="{{ $managedUsers->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                                    </nav>
                                @endif
                            </section>
                        @endif

                        @forelse ($managedUsers as $account)
                            @php
                                $accountSelections = $selectedMenus($account);
                                $accountInitial = strtoupper(mb_substr($account->name, 0, 1));
                            @endphp
                            <article class="menu-access-card">
                                <input type="hidden" name="access_user_ids[]" value="{{ $account->id }}">
                                <header class="menu-access-user-row">
                                    <span class="menu-access-avatar" aria-hidden="true">
                                        <span class="menu-access-avatar-letter">{{ $accountInitial }}</span>
                                    </span>
                                    <div class="menu-access-user-name">
                                        <strong>{{ $account->name }}</strong>
                                        <span>{{ $account->email }}</span>
                                    </div>
                                </header>
                                <div class="menu-access-groups">
                                    @foreach ($menuGroups as $menuGroup)
                                        <fieldset class="menu-access-group">
                                            <legend>{{ $menuGroup['label'] }}</legend>
                                            @foreach ($menuGroup['items'] as $menuItem)
                                                <label>
                                                    <input type="checkbox" name="menu_access[{{ $account->id }}][]" value="{{ $menuItem['key'] }}" @checked(in_array($menuItem['key'], $accountSelections, true))>
                                                    <span>{{ $menuItem['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </fieldset>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <p class="settings-empty">{{ __('main.no_site_users_for_settings') }}</p>
                        @endforelse

                        @if ($managedUsers->hasPages())
                            <section class="subscriptions-pagination settings-users-pagination" aria-label="{{ __('admin.pagination') }}">
                                <span>{{ __('admin.showing') }} <strong>{{ $managedUsers->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $managedUsers->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $managedUsers->total() }}</strong></span>
                                <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                                    @if ($managedUsers->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $managedUsers->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                                    @foreach ($managedUsers->getUrlRange(1, $managedUsers->lastPage()) as $page => $url)
                                        @if ($page === $managedUsers->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                                    @endforeach
                                    @if ($managedUsers->hasMorePages())<a href="{{ $managedUsers->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                                </nav>
                            </section>
                        @endif
                    </section>

                    <div class="settings-actions">
                        <button class="primary-action" type="submit">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            {{ __('main.save_settings') }}
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

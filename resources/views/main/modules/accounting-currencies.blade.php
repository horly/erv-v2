<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.currencies') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRecords = $accountingCurrencies->total();
        $hasCurrencyErrors = $errors->any();
        $isEditingCurrency = old('form_mode') === 'edit' && old('currency_id');
        $formAction = $isEditingCurrency
            ? route('main.accounting.currencies.update', [$company, $site, old('currency_id')])
            : route('main.accounting.currencies.store', [$company, $site]);
        $currencyPayload = fn ($currency) => [
            'code' => $currency->code,
            'exchange_rate' => number_format((float) $currency->exchange_rate, 2, '.', ''),
            'status' => $currency->status,
        ];
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'currencies'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.currencies') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
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
                                <em>{{ $user->role === 'admin' ? __('main.admin_badge') : strtoupper($user->role) }}</em>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="profile-link">
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                                {{ __('main.profile') }}
                            </a>
                            @if ($user->isAdmin())
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

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.currencies') }}</h1>
                        <p>{{ __('main.currencies_subtitle') }}</p>
                    </div>
                    @if ($currencyPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#currencyModal" data-currency-mode="create">
                            <i class="bi bi-currency-exchange" aria-hidden="true"></i>
                            {{ __('main.new_currency') }}
                        </button>
                    @endif
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
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
                        <strong id="visibleCount">{{ $accountingCurrencies->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table currency-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.currency') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.iso_code') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.symbol') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.exchange_rate') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.base_currency') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($accountingCurrencies as $currency)
                                    <tr>
                                        <td>{{ ($accountingCurrencies->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>{{ $currency->name }}</td>
                                        <td>{{ $currency->code }}</td>
                                        <td>{{ $currency->symbol ?: '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $currency->exchange_rate }}">{{ number_format((float) $currency->exchange_rate, 2, ',', ' ') }}</td>
                                        <td>
                                            <span class="status-pill {{ $currency->is_base ? 'currency-status-base' : 'currency-status-secondary' }}">
                                                {{ $currency->is_base ? __('main.yes') : __('main.no') }}
                                            </span>
                                        </td>
                                        <td><span class="status-pill currency-status-{{ $currency->status }}">{{ $statusLabels[$currency->status] ?? $currency->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($currency->is_default)
                                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#currencyModal" data-currency-mode="view" data-currency-id="{{ $currency->id }}" data-currency-values="{{ base64_encode(json_encode($currencyPayload($currency))) }}" aria-label="{{ __('main.view_details') }}">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                    </button>
                                                @else
                                                    @if ($currencyPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#currencyModal" data-currency-mode="edit" data-currency-action="{{ route('main.accounting.currencies.update', [$company, $site, $currency]) }}" data-currency-id="{{ $currency->id }}" data-currency-values="{{ base64_encode(json_encode($currencyPayload($currency))) }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($currencyPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.currencies.destroy', [$company, $site, $currency]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_currency_title') }}" data-delete-text="{{ __('main.delete_currency_text', ['name' => $currency->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_currencies') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($accountingCurrencies->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $accountingCurrencies->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $accountingCurrencies->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($accountingCurrencies->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $accountingCurrencies->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($accountingCurrencies->getUrlRange(1, $accountingCurrencies->lastPage()) as $page => $url)
                                @if ($page === $accountingCurrencies->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($accountingCurrencies->hasMorePages())<a href="{{ $accountingCurrencies->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-currency-modal" id="currencyModal" tabindex="-1" aria-labelledby="currencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form currency-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.accounting.currencies.store', [$company, $site]) }}" data-title-create="{{ __('main.new_currency') }}" data-title-edit="{{ __('main.edit_currency') }}" data-title-view="{{ __('main.view_currency') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-cancel-label="{{ __('admin.cancel') }}" data-close-label="{{ __('admin.close') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="currencyMethod" value="PUT" @disabled(! $isEditingCurrency)>
                <input type="hidden" name="form_mode" id="currencyFormMode" value="{{ $isEditingCurrency ? 'edit' : 'create' }}">
                <input type="hidden" name="currency_id" id="currencyId" value="{{ old('currency_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="currencyModalLabel"><i class="bi bi-currency-exchange" aria-hidden="true"></i>{{ $isEditingCurrency ? __('main.edit_currency') : __('main.new_currency') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="currency_code" class="form-label">{{ __('main.currency') }} *</label>
                            <select id="currency_code" name="code" class="form-select @error('code') is-invalid @enderror" data-currency-field data-default-value="">
                                <option value="">{{ __('admin.choose_subscription') }}</option>
                                @foreach ($currencyOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('code') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="currency_exchange_rate" class="form-label">{{ __('main.exchange_rate') }} *</label>
                            <input id="currency_exchange_rate" name="exchange_rate" type="number" min="0.01" step="0.01" class="form-control @error('exchange_rate') is-invalid @enderror" value="{{ old('exchange_rate', '1.00') }}" placeholder="{{ __('main.exchange_rate_placeholder') }}" data-currency-field data-default-value="1.00">
                            @error('exchange_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="currency_status" class="form-label">{{ __('main.status') }} *</label>
                            <select id="currency_status" name="status" class="form-select @error('status') is-invalid @enderror" data-currency-field data-default-value="active">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <p class="form-help mt-3">{{ __('main.currency_exchange_rate_help', ['currency' => $site->currency]) }}</p>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" id="currencyCancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="currencySubmit" type="submit">{{ $isEditingCurrency ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasCurrencyErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('currencyModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-currencies.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-currencies.js')) !!}</script>
</body>
</html>

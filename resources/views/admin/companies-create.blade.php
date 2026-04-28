<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($company ?? null) ? __('admin.edit_company') : __('admin.new_company') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $initial = strtoupper(mb_substr($user->name, 0, 1));
        $isEditingCompany = $company !== null;
        $selectedSubscriptionId = old('subscription_id', $company?->subscription_id);
        $selectedAdminId = old('admin_id', $company?->created_by);
        $oldPhones = old('phones', $isEditingCompany ? $company->phones->map(fn ($phone) => [
            'label' => $phone->label,
            'phone_number' => $phone->phone_number,
        ])->values()->all() : [['label' => '', 'phone_number' => '']]);
        $oldAccounts = old('accounts', $isEditingCompany ? $company->accounts->map(fn ($account) => [
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'currency' => $account->currency,
        ])->values()->all() : [['bank_name' => '', 'account_number' => '', 'currency' => '']]);
        $oldPhones = count($oldPhones) > 0 ? $oldPhones : [['label' => '', 'phone_number' => '']];
        $oldAccounts = count($oldAccounts) > 0 ? $oldAccounts : [['bank_name' => '', 'account_number' => '', 'currency' => '']];
        $currencyLocaleKey = 'name_'.app()->getLocale();
        $currencyLabel = fn (string $code, array $currency): string => sprintf(
            '%s (%s%s)',
            $currency[$currencyLocaleKey] ?? $currency['name_fr'],
            $code,
            blank($currency['symbol'] ?? null) ? '' : ' - '.$currency['symbol'],
        );
        $pageTitle = $isEditingCompany ? __('admin.edit_company') : __('admin.new_company');
        $formAction = $isEditingCompany ? route('admin.companies.update', $company) : route('admin.companies.store');
        $submitLabel = $isEditingCompany ? __('admin.update') : __('admin.create');
    @endphp

    <div class="dashboard-shell main-shell" data-theme="light">
        <aside class="dashboard-sidebar">
            <a class="sidebar-brand" href="{{ route('admin.dashboard') }}" aria-label="EXAD ERP">
                <span class="sidebar-logo"><img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services"></span>
                <span><strong>EXAD ERP</strong><small>{{ __('admin.console') }}</small></span>
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
                <a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2" aria-hidden="true"></i>{{ __('admin.dashboard') }}</a>
                <a class="nav-link" href="{{ route('admin.subscriptions') }}"><i class="bi bi-stack" aria-hidden="true"></i>{{ __('admin.subscriptions') }}</a>
                <a class="nav-link" href="{{ route('admin.users') }}"><i class="bi bi-people" aria-hidden="true"></i>{{ __('admin.users') }}</a>
                <a class="nav-link active" href="{{ route('admin.companies') }}"><i class="bi bi-buildings" aria-hidden="true"></i>{{ __('admin.companies') }}</a>
            </nav>
            <div class="sidebar-footer"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i><span>{{ __('admin.version') }}</span></div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>{{ __('admin.breadcrumb_admin') }} / <a class="breadcrumb-link" href="{{ route('admin.companies') }}">{{ __('admin.companies') }}</a> / {{ $isEditingCompany ? __('admin.edit') : __('admin.create') }}</p>
                </div>
                <div class="header-actions">
                    <button class="icon-button" type="button" id="themeButton" aria-label="{{ __('auth.theme_dark') }}" title="{{ __('auth.theme_dark') }}"><i class="bi bi-brightness-high-fill" aria-hidden="true"></i></button>
                    <div class="language-menu">
                        <button class="language-button" type="button" id="languageButton" aria-label="{{ __('auth.language_switch') }}" aria-expanded="false" aria-controls="languageDropdown" title="{{ __('auth.language_switch') }}">
                            <i class="bi bi-globe2" aria-hidden="true"></i><span>{{ strtoupper($currentLocale) }}</span><i class="bi bi-chevron-down language-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="language-dropdown" id="languageDropdown" aria-labelledby="languageButton">
                            <a class="language-option {{ $currentLocale === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}"><span class="language-code">FR</span><span class="language-name">{{ __('auth.language_fr') }}</span>@if ($currentLocale === 'fr')<i class="bi bi-check-lg language-check" aria-hidden="true"></i>@endif</a>
                            <a class="language-option {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}"><span class="language-code">EN</span><span class="language-name">{{ __('auth.language_en') }}</span>@if ($currentLocale === 'en')<i class="bi bi-check-lg language-check" aria-hidden="true"></i>@endif</a>
                        </div>
                    </div>
                    <div class="profile-menu">
                        <button class="profile-button" type="button" id="profileButton" aria-expanded="false" aria-controls="profileDropdown"><span class="avatar">{{ $initial }}</span><span class="profile-name">{{ $user->name }}</span><i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i></button>
                        <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                            <div class="profile-summary"><strong>{{ $user->name }}</strong><span>{{ $user->email }}</span><em>{{ strtoupper($user->role) }}</em></div>
                            <a href="#" class="profile-link"><i class="bi bi-person-circle" aria-hidden="true"></i>{{ __('admin.profile') }}</a>
                            <a href="{{ route('admin.users') }}" class="profile-link"><i class="bi bi-people" aria-hidden="true"></i>{{ __('admin.user_management') }}</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="profile-link logout-link" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>{{ __('admin.logout') }}</button></form>
                        </div>
                    </div>
                </div>
            </header>

            <section class="dashboard-content company-form-page">
                <form class="admin-form company-create-form" method="POST" action="{{ $formAction }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    @if ($isEditingCompany)
                        @method('PUT')
                    @endif

                    <section class="form-section">
                        <h2><i class="bi bi-diagram-3" aria-hidden="true"></i>{{ __('admin.assignment') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="companySubscription" class="form-label">{{ __('admin.subscription') }} *</label>
                                <select id="companySubscription" name="subscription_id" class="form-select @error('subscription_id') is-invalid @enderror" data-company-subscription data-required-message="{{ __('admin.required_user_subscription') }}">
                                    <option value="">{{ __('admin.choose_subscription') }}</option>
                                    @foreach ($subscriptions as $subscription)
                                        <option value="{{ $subscription->id }}" @selected((string) $selectedSubscriptionId === (string) $subscription->id)>{{ $subscription->name }} - {{ __('admin.'.$subscription->type) }}</option>
                                    @endforeach
                                </select>
                                @error('subscription_id')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_user_subscription') }}</div>@enderror
                                <div class="valid-feedback">{{ __('admin.valid_user_subscription') }}</div>
                            </div>
                            <div class="col-md-6 company-admin-field" data-company-admin-wrapper @if (! $selectedSubscriptionId) hidden @endif>
                                <label for="companyAdmin" class="form-label">{{ __('admin.administrator') }} *</label>
                                <select id="companyAdmin" name="admin_id" class="form-select @error('admin_id') is-invalid @enderror" data-company-admin data-required-message="{{ __('admin.required_company_admin') }}" @disabled(! $selectedSubscriptionId)>
                                    <option value="">{{ __('admin.choose_admin') }}</option>
                                    @foreach ($admins as $admin)
                                        <option value="{{ $admin->id }}" data-subscription-id="{{ $admin->subscription_id }}" @selected((string) $selectedAdminId === (string) $admin->id)>{{ $admin->name }} - {{ $admin->email }}</option>
                                    @endforeach
                                </select>
                                @error('admin_id')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_company_admin') }}</div>@enderror
                                <div class="valid-feedback">{{ __('admin.valid_company_admin') }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2><i class="bi bi-card-list" aria-hidden="true"></i>{{ __('admin.identification') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="companyName" class="form-label">{{ __('admin.name') }} *</label>
                                <input id="companyName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $company?->name) }}" data-required-message="{{ __('admin.required_company_name') }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_company_name') }}</div>@enderror
                                <div class="valid-feedback">{{ __('admin.valid_name') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label for="companyCountry" class="form-label">{{ __('admin.country') }} *</label>
                                <select id="companyCountry" name="country" class="form-select @error('country') is-invalid @enderror" data-required-message="{{ __('admin.required_country') }}">
                                    <option value="">{{ __('admin.select_country') }}</option>
                                    @foreach ($countries as $country)
                                        @php($countryName = $country['name_'.app()->getLocale()] ?? $country['name_fr'])
                                        <option
                                            value="{{ $country['name_fr'] }}"
                                            data-iso="{{ $country['iso'] }}"
                                            data-name-fr="{{ $country['name_fr'] }}"
                                            data-name-en="{{ $country['name_en'] }}"
                                            data-phone-code="{{ $country['phone_code'] }}"
                                            data-vat-rate="{{ $country['vat_rate'] }}"
                                            @selected(old('country', $company?->country ?? 'Congo (RDC)') === $country['name_fr'])
                                        >{{ $countryName }} ({{ $country['phone_code'] }} - {{ app()->getLocale() === 'en' ? 'VAT' : 'TVA' }} {{ number_format($country['vat_rate'], 2, ',', ' ') }}%)</option>
                                    @endforeach
                                </select>
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_country') }}</div>@enderror
                                <div class="valid-feedback">{{ __('admin.valid_country') }}</div>
                            </div>
                            <div class="col-12">
                                <label for="companySlogan" class="form-label">{{ __('admin.slogan') }}</label>
                                <input id="companySlogan" name="slogan" type="text" class="form-control" value="{{ old('slogan', $company?->slogan) }}">
                            </div>
                            <div class="col-md-4"><label for="companyRccm" class="form-label">{{ __('admin.rccm') }}</label><input id="companyRccm" name="rccm" type="text" class="form-control" value="{{ old('rccm', $company?->rccm) }}"></div>
                            <div class="col-md-4"><label for="companyIdNat" class="form-label">{{ __('admin.id_nat') }}</label><input id="companyIdNat" name="id_nat" type="text" class="form-control" value="{{ old('id_nat', $company?->id_nat) }}"></div>
                            <div class="col-md-4"><label for="companyNif" class="form-label">{{ __('admin.nif') }}</label><input id="companyNif" name="nif" type="text" class="form-control" value="{{ old('nif', $company?->nif) }}"></div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2><i class="bi bi-envelope" aria-hidden="true"></i>{{ __('admin.contact') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="companyEmail" class="form-label">{{ __('admin.email') }} *</label>
                                <input id="companyEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company?->email) }}" data-required-message="{{ __('admin.required_company_email') }}" data-email-message="{{ __('admin.invalid_user_email') }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('admin.required_company_email') }}</div>@enderror
                                <div class="valid-feedback">{{ __('admin.valid_user_email') }}</div>
                            </div>
                            <div class="col-md-6"><label for="companyWebsite" class="form-label">{{ __('admin.website') }}</label><input id="companyWebsite" name="website" type="url" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $company?->website) }}" placeholder="https://..."></div>
                            <div class="col-12"><label for="companyAddress" class="form-label">{{ __('admin.address') }}</label><input id="companyAddress" name="address" type="text" class="form-control" value="{{ old('address', $company?->address) }}"></div>
                        </div>
                    </section>

                    <section class="form-section dynamic-list-section">
                        <h2><i class="bi bi-telephone" aria-hidden="true"></i>{{ __('admin.phone_numbers') }} <button class="section-add-button" type="button" data-add-phone><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __('admin.add_phone') }}</button></h2>
                        <div class="dynamic-list" data-phone-list>
                            @foreach ($oldPhones as $index => $phone)
                                <div class="dynamic-row">
                                    <input name="phones[{{ $index }}][label]" type="text" class="form-control" value="{{ $phone['label'] ?? '' }}" placeholder="{{ __('admin.label') }}">
                                    <input name="phones[{{ $index }}][phone_number]" type="text" class="form-control" value="{{ $phone['phone_number'] ?? '' }}" placeholder="{{ __('admin.phone') }}">
                                    <button type="button" class="table-button table-button-delete" data-remove-row aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="form-section dynamic-list-section">
                        <h2><i class="bi bi-bank" aria-hidden="true"></i>{{ __('admin.account_numbers') }} <button class="section-add-button" type="button" data-add-account><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __('admin.add_account') }}</button></h2>
                        <div class="dynamic-list" data-account-list>
                            @foreach ($oldAccounts as $index => $account)
                                <div class="dynamic-row dynamic-row-accounts">
                                    <input name="accounts[{{ $index }}][bank_name]" type="text" class="form-control" value="{{ $account['bank_name'] ?? '' }}" placeholder="{{ __('admin.bank_name') }}">
                                    <input name="accounts[{{ $index }}][account_number]" type="text" class="form-control" value="{{ $account['account_number'] ?? '' }}" placeholder="{{ __('admin.account_number') }}">
                                    <select name="accounts[{{ $index }}][currency]" class="form-select" aria-label="{{ __('admin.currency') }}">
                                        <option value="">{{ __('admin.currency') }}</option>
                                        @foreach ($currencies as $code => $currency)
                                            <option value="{{ $code }}" @selected(($account['currency'] ?? '') === $code)>
                                                {{ $currencyLabel($code, $currency) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="table-button table-button-delete" data-remove-row aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="form-section visual-section">
                        <h2><i class="bi bi-image" aria-hidden="true"></i>{{ __('admin.visual_identity') }}</h2>
                        <div class="logo-upload-row">
                            <span class="logo-preview" data-logo-preview>
                                @if ($company?->logo_url)
                                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }}">
                                @else
                                    ?
                                @endif
                            </span>
                            <div>
                                <label class="secondary-action file-action" for="companyLogo"><i class="bi bi-upload" aria-hidden="true"></i>{{ __('admin.choose_image') }}</label>
                                <input id="companyLogo" name="logo" type="file" class="visually-hidden @error('logo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/svg+xml" data-logo-input>
                                <p class="file-help">{{ __('admin.logo_help') }}</p>
                                @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="page-form-actions">
                        <a class="modal-cancel" href="{{ route('admin.companies') }}">{{ __('admin.cancel') }}</a>
                        <button class="modal-submit" type="submit">{{ $submitLabel }}</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <template id="phoneRowTemplate">
        <div class="dynamic-row">
            <input data-name="phones[__INDEX__][label]" type="text" class="form-control" placeholder="{{ __('admin.label') }}">
            <input data-name="phones[__INDEX__][phone_number]" type="text" class="form-control" placeholder="{{ __('admin.phone') }}">
            <button type="button" class="table-button table-button-delete" data-remove-row aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </div>
    </template>
    <template id="accountRowTemplate">
        <div class="dynamic-row dynamic-row-accounts">
            <input data-name="accounts[__INDEX__][bank_name]" type="text" class="form-control" placeholder="{{ __('admin.bank_name') }}">
            <input data-name="accounts[__INDEX__][account_number]" type="text" class="form-control" placeholder="{{ __('admin.account_number') }}">
            <select data-name="accounts[__INDEX__][currency]" class="form-select" aria-label="{{ __('admin.currency') }}">
                <option value="">{{ __('admin.currency') }}</option>
                @foreach ($currencies as $code => $currency)
                    <option value="{{ $code }}">{{ $currencyLabel($code, $currency) }}</option>
                @endforeach
            </select>
            <button type="button" class="table-button table-button-delete" data-remove-row aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </div>
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

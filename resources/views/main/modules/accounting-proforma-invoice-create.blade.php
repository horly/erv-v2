<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isEditingProforma = isset($proforma) && $proforma?->exists;
        $pageTitle = $isEditingProforma ? __('main.edit_proforma_invoice') : __('main.new_proforma_invoice');
    @endphp
    <title>{{ $pageTitle }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.proforma-invoices', [$company, $site]);
        $formAction = $isEditingProforma
            ? route('main.accounting.proforma-invoices.update', [$company, $site, $proforma])
            : route('main.accounting.proforma-invoices.store', [$company, $site]);
        $defaultIssueDate = $isEditingProforma
            ? optional($proforma->issue_date)->format('Y-m-d')
            : now()->format('Y-m-d');
        $defaultExpirationDate = $isEditingProforma
            ? optional($proforma->expiration_date)->format('Y-m-d')
            : now()->format('Y-m-d');
        $defaultCurrency = $isEditingProforma
            ? $proforma->currency
            : ($site->currency ?: 'CDF');
        $defaultStatus = $isEditingProforma
            ? $proforma->status
            : \App\Models\AccountingProformaInvoice::STATUS_DRAFT;
        $defaultPaymentTerms = $isEditingProforma
            ? ($proforma->payment_terms ?: \App\Models\AccountingProformaInvoice::PAYMENT_TO_DISCUSS)
            : \App\Models\AccountingProformaInvoice::PAYMENT_TO_DISCUSS;
        $defaultTaxRate = $isEditingProforma
            ? number_format((float) $proforma->tax_rate, 2, '.', '')
            : number_format((float) $proformaDefaultTaxRate, 2, '.', '');
        $linePayload = fn ($line) => [
            'line_type' => $line->line_type,
            'item_id' => $line->item_id,
            'service_id' => $line->service_id,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => $line->discount_type ?: \App\Models\AccountingProformaInvoiceLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
        ];
        $defaultLines = $isEditingProforma
            ? $proforma->lines->map($linePayload)->values()->all()
            : [[
                'line_type' => \App\Models\AccountingProformaInvoiceLine::TYPE_FREE,
                'item_id' => '',
                'service_id' => '',
                'description' => '',
                'details' => '',
                'quantity' => '1',
                'unit_price' => '0',
                'discount_type' => \App\Models\AccountingProformaInvoiceLine::DISCOUNT_FIXED,
                'discount_amount' => '0',
            ]];
        $oldLines = old('lines', $defaultLines ?: [[
            'line_type' => \App\Models\AccountingProformaInvoiceLine::TYPE_FREE,
            'item_id' => '',
            'service_id' => '',
            'description' => '',
            'details' => '',
            'quantity' => '1',
            'unit_price' => '0',
            'discount_type' => \App\Models\AccountingProformaInvoiceLine::DISCOUNT_FIXED,
            'discount_amount' => '0',
        ]]);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'proforma-invoices'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ $pageTitle }}</h1>
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
                <a class="back-link" href="{{ $indexRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.proforma_invoices') }}
                </a>

                <p class="proforma-page-intro">{{ __('main.proforma_invoices_subtitle') }}</p>

                <section class="company-card proforma-page-card">
                    <form class="admin-form proforma-form proforma-page-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.accounting.proforma-invoices.store', [$company, $site]) }}" data-title-create="{{ __('main.new_proforma_invoice') }}" data-title-edit="{{ __('main.edit_proforma_invoice') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                        @csrf
                        @if ($isEditingProforma)
                            @method('PUT')
                        @endif
                        <input type="hidden" name="form_mode" id="proformaFormMode" value="{{ $isEditingProforma ? 'edit' : 'create' }}">
                        <input type="hidden" name="proforma_id" id="proformaId" value="{{ $isEditingProforma ? $proforma->id : '' }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="proformaClient" class="form-label">{{ __('main.customer') }} *</label>
                                <select id="proformaClient" name="client_id" class="form-select @error('client_id') is-invalid @enderror" data-proforma-field data-default-value="{{ $isEditingProforma ? $proforma->client_id : '' }}">
                                    <option value="">{{ __('main.choose_customer') }}</option>
                                    @foreach ($clients as $id => $label)
                                        <option value="{{ $id }}" @selected(old('client_id', $isEditingProforma ? $proforma->client_id : null) == $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="proformaTitle" class="form-label">{{ __('main.proforma_title') }}</label>
                                <input id="proformaTitle" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $isEditingProforma ? $proforma->title : '') }}" placeholder="{{ __('main.proforma_title_placeholder') }}" data-proforma-field data-default-value="{{ $isEditingProforma ? $proforma->title : '' }}">
                                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="proformaIssueDate" class="form-label">{{ __('main.date') }} *</label>
                                <input id="proformaIssueDate" name="issue_date" type="date" class="form-control @error('issue_date') is-invalid @enderror" value="{{ old('issue_date', $defaultIssueDate) }}" data-proforma-field data-default-value="{{ $defaultIssueDate }}">
                                @error('issue_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="proformaExpirationDate" class="form-label">{{ __('main.offer_validity') }} *</label>
                                <input id="proformaExpirationDate" name="expiration_date" type="date" class="form-control @error('expiration_date') is-invalid @enderror" value="{{ old('expiration_date', $defaultExpirationDate) }}" data-proforma-field data-default-value="{{ $defaultExpirationDate }}">
                                @error('expiration_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="proformaCurrency" class="form-label">{{ __('main.currency') }} *</label>
                                <select id="proformaCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-proforma-field data-default-value="{{ $defaultCurrency }}">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency', $defaultCurrency) === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label for="proformaStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="proformaStatus" name="status" class="form-select @error('status') is-invalid @enderror" data-proforma-field data-default-value="{{ $defaultStatus }}">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $defaultStatus) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="proforma-lines-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-list-check" aria-hidden="true"></i> {{ __('main.proforma_lines') }}</span>
                                <button type="button" class="light-action" data-add-proforma-line>
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    {{ __('main.add_line') }}
                                </button>
                            </div>

                            <div class="proforma-line-list" data-proforma-line-list>
                                @foreach ($oldLines as $index => $line)
                                    @include('main.modules.partials.proforma-line-row', ['index' => $index, 'line' => $line, 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
                                @endforeach
                            </div>
                        </section>

                        <section class="proforma-summary-grid">
                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="proformaPaymentTerms" class="form-label">{{ __('main.payment_terms') }}</label>
                                            <select id="proformaPaymentTerms" name="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror" data-proforma-field data-default-value="{{ $defaultPaymentTerms }}">
                                                @foreach ($paymentTermLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('payment_terms', $defaultPaymentTerms) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('payment_terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="proformaNotes" class="form-label">{{ __('main.notes') }}</label>
                                            <textarea id="proformaNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}" data-proforma-field data-default-value="{{ $isEditingProforma ? $proforma->notes : '' }}">{{ old('notes', $isEditingProforma ? $proforma->notes : '') }}</textarea>
                                            @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="proformaTerms" class="form-label">{{ __('main.terms') }}</label>
                                            <textarea id="proformaTerms" name="terms" rows="3" class="form-control @error('terms') is-invalid @enderror" placeholder="{{ __('main.terms') }}" data-proforma-field data-default-value="{{ $isEditingProforma ? $proforma->terms : '' }}">{{ old('terms', $isEditingProforma ? $proforma->terms : '') }}</textarea>
                                            @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="proforma-total-card">
                                        <label for="proformaTaxRate" class="form-label">{{ __('main.global_vat_rate') }} *</label>
                                        <input id="proformaTaxRate" name="tax_rate" type="number" min="0" max="100" step="0.01" class="form-control @error('tax_rate') is-invalid @enderror" value="{{ old('tax_rate', $defaultTaxRate) }}" placeholder="0" data-proforma-field data-default-value="{{ $defaultTaxRate }}">
                                        @error('tax_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        <dl>
                                            <div><dt>{{ __('main.subtotal') }}</dt><dd data-total-subtotal>0,00</dd></div>
                                            <div><dt>{{ __('main.discount_total') }}</dt><dd data-total-discount>0,00</dd></div>
                                            <div><dt>{{ __('main.total_ht') }}</dt><dd data-total-ht>0,00</dd></div>
                                            <div><dt>{{ __('main.vat_amount') }}</dt><dd data-total-tax>0,00</dd></div>
                                            <div class="total"><dt>{{ __('main.total_ttc') }}</dt><dd data-total-ttc>0,00</dd></div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="modal-actions">
                            <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('admin.cancel') }}</a>
                            <button class="modal-submit" id="proformaSubmit" type="submit">{{ $isEditingProforma ? __('admin.update') : __('admin.create') }}</button>
                        </div>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <template id="proformaLineTemplate">
        @include('main.modules.partials.proforma-line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items, 'services' => $services, 'lineTypeLabels' => $lineTypeLabels])
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-proforma-invoices.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-proforma-invoices.js')) !!}</script>
</body>
</html>

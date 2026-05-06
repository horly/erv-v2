<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.sales_representatives') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $module = \App\Models\CompanySite::MODULE_ACCOUNTING;
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRepresentatives = $representatives->total();
        $hasRepresentativeErrors = $errors->any();
        $isEditingRepresentative = old('form_mode') === 'edit' && old('representative_id');
        $representativeFormAction = $isEditingRepresentative
            ? route('main.accounting.sales-representatives.update', [$company, $site, old('representative_id')])
            : route('main.accounting.sales-representatives.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingSalesRepresentative::TYPE_INTERNAL);
        $oldStatus = old('status', \App\Models\AccountingSalesRepresentative::STATUS_ACTIVE);
        $oldCurrency = old('currency', $site->currency ?: 'CDF');
        $formatMoney = fn ($amount, $currency) => number_format((float) $amount, 0, ',', ' ').' '.$currency;
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'sales-representatives'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.sales_representatives') }}</h1>
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
                        <h1>{{ __('main.sales_representatives') }}</h1>
                        <p>{{ __('main.sales_representatives_subtitle') }}</p>
                    </div>
                    @if ($representativePermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#representativeModal" data-representative-mode="create">
                            <i class="bi bi-briefcase" aria-hidden="true"></i>
                            {{ __('main.new_sales_representative') }}
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
                        <strong id="visibleCount">{{ $representatives->count() }}</strong>
                        /
                        <strong>{{ $totalRepresentatives }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table sales-representatives-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.sales_representative') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.sales_area') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.monthly_target') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.commission_rate') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($representatives as $representative)
                                    <tr>
                                        <td>{{ ($representatives->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $representative->reference }}</span></td>
                                        <td>
                                            <span class="representative-name">
                                                <span class="representative-icon representative-icon-{{ $representative->type }}">
                                                    <i class="bi bi-briefcase" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $representative->name }}</strong>
                                                    <small>{{ $representative->email ?: ($representative->phone ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill representative-type-{{ $representative->type }}">{{ $typeLabels[$representative->type] ?? $representative->type }}</span></td>
                                        <td>{{ $representative->sales_area ?: '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $representative->monthly_target }}">{{ $formatMoney($representative->monthly_target, $representative->currency) }}</td>
                                        <td data-sort-value="{{ $representative->commission_rate }}">{{ number_format((float) $representative->commission_rate, 2, ',', ' ') }}%</td>
                                        <td><span class="status-pill representative-status-{{ $representative->status }}">{{ $statusLabels[$representative->status] ?? $representative->status }}</span></td>
                                        <td>
                                            @if ($representativePermissions['can_update'] || $representativePermissions['can_delete'])
                                                <div class="table-actions">
                                                    @if ($representativePermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#representativeModal" data-representative-mode="edit" data-representative-action="{{ route('main.accounting.sales-representatives.update', [$company, $site, $representative]) }}" data-representative-id="{{ $representative->id }}" data-representative-type="{{ $representative->type }}" data-representative-name="{{ $representative->name }}" data-representative-phone="{{ $representative->phone }}" data-representative-email="{{ $representative->email }}" data-representative-address="{{ $representative->address }}" data-representative-sales-area="{{ $representative->sales_area }}" data-representative-currency="{{ $representative->currency }}" data-representative-monthly-target="{{ $representative->monthly_target }}" data-representative-annual-target="{{ $representative->annual_target }}" data-representative-commission-rate="{{ $representative->commission_rate }}" data-representative-status="{{ $representative->status }}" data-representative-notes="{{ $representative->notes }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($representativePermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.sales-representatives.destroy', [$company, $site, $representative]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_sales_representative_title') }}" data-delete-text="{{ __('main.delete_sales_representative_text', ['name' => $representative->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="muted-dash">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.no_sales_representatives') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($representatives->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $representatives->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $representatives->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRepresentatives }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($representatives->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $representatives->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($representatives->getUrlRange(1, $representatives->lastPage()) as $page => $url)
                                @if ($page === $representatives->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($representatives->hasMorePages())<a href="{{ $representatives->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-representative-modal" id="representativeModal" tabindex="-1" aria-labelledby="representativeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form representative-form" method="POST" action="{{ $representativeFormAction }}" data-create-action="{{ route('main.accounting.sales-representatives.store', [$company, $site]) }}" data-title-create="{{ __('main.new_sales_representative') }}" data-title-edit="{{ __('main.edit_sales_representative') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="representativeMethod" value="PUT" @disabled(! $isEditingRepresentative)>
                <input type="hidden" name="form_mode" id="representativeFormMode" value="{{ $isEditingRepresentative ? 'edit' : 'create' }}">
                <input type="hidden" name="representative_id" id="representativeId" value="{{ old('representative_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="representativeModalLabel"><i class="bi bi-briefcase" aria-hidden="true"></i>{{ $isEditingRepresentative ? __('main.edit_sales_representative') : __('main.new_sales_representative') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="representativeType" class="form-label">{{ __('main.sales_representative_type') }} *</label>
                            <select id="representativeType" name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $type => $label)
                                    <option value="{{ $type }}" @selected($oldType === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="representativeStatus" class="form-label">{{ __('main.status') }} *</label>
                            <select id="representativeStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach ($statusLabels as $status => $label)
                                    <option value="{{ $status }}" @selected($oldStatus === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="representativeName" class="form-label">{{ __('main.sales_representative_name') }} *</label>
                            <input id="representativeName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.sales_representative_name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="representativePhone" class="form-label">{{ __('main.phone') }}</label>
                            <input id="representativePhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="representativeEmail" class="form-label">{{ __('main.email') }}</label>
                            <input id="representativeEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="representativeAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="representativeAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="representative-performance-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> {{ __('main.sales_performance') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="representativeSalesArea" class="form-label">{{ __('main.sales_area') }}</label>
                                <input id="representativeSalesArea" name="sales_area" type="text" class="form-control @error('sales_area') is-invalid @enderror" value="{{ old('sales_area') }}" placeholder="{{ __('main.sales_area') }}">
                                @error('sales_area')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="representativeCurrency" class="form-label">{{ __('admin.currency') }} *</label>
                                <select id="representativeCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected($oldCurrency === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="representativeMonthlyTarget" class="form-label">{{ __('main.monthly_target') }} *</label>
                                <input id="representativeMonthlyTarget" name="monthly_target" type="number" min="0" step="0.01" class="form-control @error('monthly_target') is-invalid @enderror" value="{{ old('monthly_target', 0) }}" placeholder="{{ __('main.monthly_target') }}">
                                @error('monthly_target')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="representativeAnnualTarget" class="form-label">{{ __('main.annual_target') }} *</label>
                                <input id="representativeAnnualTarget" name="annual_target" type="number" min="0" step="0.01" class="form-control @error('annual_target') is-invalid @enderror" value="{{ old('annual_target', 0) }}" placeholder="{{ __('main.annual_target') }}">
                                @error('annual_target')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="representativeCommissionRate" class="form-label">{{ __('main.commission_rate_percent') }} *</label>
                                <div class="input-group">
                                    <input id="representativeCommissionRate" name="commission_rate" type="number" min="0" max="100" step="0.01" class="form-control @error('commission_rate') is-invalid @enderror" value="{{ old('commission_rate', 0) }}" placeholder="Ex. 5">
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('commission_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="representativeNotes" class="form-label">{{ __('main.notes') }}</label>
                                <textarea id="representativeNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('main.notes') }}">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="representativeSubmit" type="submit">{{ $isEditingRepresentative ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasRepresentativeErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('representativeModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-sales-representatives.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-sales-representatives.js')) !!}</script>
</body>
</html>

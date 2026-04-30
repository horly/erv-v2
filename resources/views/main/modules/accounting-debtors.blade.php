<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.debtors') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalDebtors = $debtors->total();
        $hasDebtorErrors = $errors->any();
        $isEditingDebtor = old('form_mode') === 'edit' && old('debtor_id');
        $debtorFormAction = $isEditingDebtor
            ? route('main.accounting.debtors.update', [$company, $site, old('debtor_id')])
            : route('main.accounting.debtors.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingDebtor::TYPE_CLIENT);
        $oldCurrency = old('currency', $site->currency ?: 'CDF');
        $oldStatus = old('status', \App\Models\AccountingDebtor::STATUS_ACTIVE);
        $formatMoney = fn ($amount, $currency) => number_format((float) $amount, 0, ',', ' ').' '.$currency;
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'debtors'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.debtors') }}</h1>
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
                        <h1>{{ __('main.debtors') }}</h1>
                        <p>{{ __('main.debtors_subtitle') }}</p>
                    </div>
                    @if ($debtorPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#debtorModal" data-debtor-mode="create">
                            <i class="bi bi-arrow-down-left-circle" aria-hidden="true"></i>
                            {{ __('main.new_debtor') }}
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
                        <strong id="visibleCount">{{ $debtors->count() }}</strong>
                        /
                        <strong>{{ $totalDebtors }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table debtors-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.debtor') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.amount_receivable') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($debtors as $debtor)
                                    <tr>
                                        <td>{{ ($debtors->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $debtor->reference }}</span></td>
                                        <td>
                                            <span class="debtor-name">
                                                <span class="debtor-icon debtor-icon-{{ $debtor->type }}">
                                                    <i class="bi bi-arrow-down-left-circle" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $debtor->name }}</strong>
                                                    <small>{{ $debtor->email ?: ($debtor->phone ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill debtor-type-{{ $debtor->type }}">{{ $typeLabels[$debtor->type] ?? $debtor->type }}</span></td>
                                        <td data-sort-value="{{ $debtor->balanceReceivable() }}">{{ $formatMoney($debtor->balanceReceivable(), $debtor->currency) }}</td>
                                        <td>{{ $debtor->due_date ? $debtor->due_date->translatedFormat('d M Y') : '-' }}</td>
                                        <td><span class="status-pill debtor-status-{{ $debtor->status }}">{{ $statusLabels[$debtor->status] ?? $debtor->status }}</span></td>
                                        <td>
                                            @if ($debtorPermissions['can_update'] || $debtorPermissions['can_delete'])
                                                <div class="table-actions">
                                                    @if ($debtorPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#debtorModal" data-debtor-mode="edit" data-debtor-action="{{ route('main.accounting.debtors.update', [$company, $site, $debtor]) }}" data-debtor-id="{{ $debtor->id }}" data-debtor-type="{{ $debtor->type }}" data-debtor-name="{{ $debtor->name }}" data-debtor-phone="{{ $debtor->phone }}" data-debtor-email="{{ $debtor->email }}" data-debtor-address="{{ $debtor->address }}" data-debtor-currency="{{ $debtor->currency }}" data-debtor-initial-amount="{{ $debtor->initial_amount }}" data-debtor-received-amount="{{ $debtor->received_amount }}" data-debtor-due-date="{{ $debtor->due_date?->format('Y-m-d') }}" data-debtor-description="{{ $debtor->description }}" data-debtor-status="{{ $debtor->status }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($debtorPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.debtors.destroy', [$company, $site, $debtor]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_debtor_title') }}" data-delete-text="{{ __('main.delete_debtor_text', ['name' => $debtor->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
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
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_debtors') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($debtors->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $debtors->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $debtors->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalDebtors }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($debtors->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $debtors->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($debtors->getUrlRange(1, $debtors->lastPage()) as $page => $url)
                                @if ($page === $debtors->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($debtors->hasMorePages())<a href="{{ $debtors->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-debtor-modal" id="debtorModal" tabindex="-1" aria-labelledby="debtorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form debtor-form" method="POST" action="{{ $debtorFormAction }}" data-create-action="{{ route('main.accounting.debtors.store', [$company, $site]) }}" data-title-create="{{ __('main.new_debtor') }}" data-title-edit="{{ __('main.edit_debtor') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="debtorMethod" value="PUT" @disabled(! $isEditingDebtor)>
                <input type="hidden" name="form_mode" id="debtorFormMode" value="{{ $isEditingDebtor ? 'edit' : 'create' }}">
                <input type="hidden" name="debtor_id" id="debtorId" value="{{ old('debtor_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="debtorModalLabel"><i class="bi bi-arrow-down-left-circle" aria-hidden="true"></i>{{ $isEditingDebtor ? __('main.edit_debtor') : __('main.new_debtor') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="debtorType" class="form-label">{{ __('main.debtor_type') }} *</label>
                            <select id="debtorType" name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $type => $label)
                                    <option value="{{ $type }}" @selected($oldType === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorName" class="form-label">{{ __('main.debtor_name') }} *</label>
                            <input id="debtorName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.debtor_name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorPhone" class="form-label">{{ __('main.phone') }}</label>
                            <input id="debtorPhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="debtorEmail" class="form-label">{{ __('main.email') }}</label>
                            <input id="debtorEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="debtorAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="debtorAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="debtor-finance-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-cash-stack" aria-hidden="true"></i> {{ __('main.receivable_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="debtorCurrency" class="form-label">{{ __('admin.currency') }} *</label>
                                <select id="debtorCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected($oldCurrency === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="debtorInitialAmount" class="form-label">{{ __('main.initial_amount') }} *</label>
                                <input id="debtorInitialAmount" name="initial_amount" type="number" min="0" step="0.01" class="form-control @error('initial_amount') is-invalid @enderror" value="{{ old('initial_amount', 0) }}" placeholder="{{ __('main.initial_amount') }}">
                                @error('initial_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="debtorReceivedAmount" class="form-label">{{ __('main.received_amount') }} *</label>
                                <input id="debtorReceivedAmount" name="received_amount" type="number" min="0" step="0.01" class="form-control @error('received_amount') is-invalid @enderror" value="{{ old('received_amount', 0) }}" placeholder="{{ __('main.received_amount') }}">
                                @error('received_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="debtorDueDate" class="form-label">{{ __('main.due_date') }}</label>
                                <input id="debtorDueDate" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" placeholder="{{ __('main.due_date') }}">
                                @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="debtorStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="debtorStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $status => $label)
                                        <option value="{{ $status }}" @selected($oldStatus === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="debtorDescription" class="form-label">{{ __('main.description') }}</label>
                                <textarea id="debtorDescription" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.description') }}">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="debtorSubmit" type="submit">{{ $isEditingDebtor ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasDebtorErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('debtorModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-debtors.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-debtors.js')) !!}</script>
</body>
</html>

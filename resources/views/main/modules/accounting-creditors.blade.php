<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.creditors') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalCreditors = $creditors->total();
        $hasCreditorErrors = $errors->any();
        $isEditingCreditor = old('form_mode') === 'edit' && old('creditor_id');
        $creditorFormAction = $isEditingCreditor
            ? route('main.accounting.creditors.update', [$company, $site, old('creditor_id')])
            : route('main.accounting.creditors.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingCreditor::TYPE_SUPPLIER);
        $oldCurrency = old('currency', $site->currency ?: 'CDF');
        $oldPriority = old('priority', \App\Models\AccountingCreditor::PRIORITY_NORMAL);
        $oldStatus = old('status', \App\Models\AccountingCreditor::STATUS_ACTIVE);
        $formatMoney = fn ($amount, $currency) => number_format((float) $amount, 0, ',', ' ').' '.$currency;
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'creditors'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.creditors') }}</h1>
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
                        <h1>{{ __('main.creditors') }}</h1>
                        <p>{{ __('main.creditors_subtitle') }}</p>
                    </div>
                    @if ($creditorPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#creditorModal" data-creditor-mode="create">
                            <i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i>
                            {{ __('main.new_creditor') }}
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
                        <strong id="visibleCount">{{ $creditors->count() }}</strong>
                        /
                        <strong>{{ $totalCreditors }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table creditors-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.creditor') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.amount_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($creditors as $creditor)
                                    <tr>
                                        <td>{{ ($creditors->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $creditor->reference }}</span></td>
                                        <td>
                                            <span class="creditor-name">
                                                <span class="creditor-icon creditor-icon-{{ $creditor->type }}">
                                                    <i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $creditor->name }}</strong>
                                                    <small>{{ $creditor->email ?: ($creditor->phone ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill creditor-type-{{ $creditor->type }}">{{ $typeLabels[$creditor->type] ?? $creditor->type }}</span></td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $creditor->balanceDue() }}">{{ $formatMoney($creditor->balanceDue(), $creditor->currency) }}</td>
                                        <td>{{ $creditor->due_date ? $creditor->due_date->translatedFormat('d M Y') : '-' }}</td>
                                        <td><span class="status-pill creditor-status-{{ $creditor->status }}">{{ $statusLabels[$creditor->status] ?? $creditor->status }}</span></td>
                                        <td>
                                            @if ($creditorPermissions['can_update'] || $creditorPermissions['can_delete'])
                                                <div class="table-actions">
                                                    @if ($creditorPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#creditorModal" data-creditor-mode="edit" data-creditor-action="{{ route('main.accounting.creditors.update', [$company, $site, $creditor]) }}" data-creditor-id="{{ $creditor->id }}" data-creditor-type="{{ $creditor->type }}" data-creditor-name="{{ $creditor->name }}" data-creditor-phone="{{ $creditor->phone }}" data-creditor-email="{{ $creditor->email }}" data-creditor-address="{{ $creditor->address }}" data-creditor-currency="{{ $creditor->currency }}" data-creditor-initial-amount="{{ $creditor->initial_amount }}" data-creditor-paid-amount="{{ $creditor->paid_amount }}" data-creditor-due-date="{{ $creditor->due_date?->format('Y-m-d') }}" data-creditor-description="{{ $creditor->description }}" data-creditor-priority="{{ $creditor->priority }}" data-creditor-status="{{ $creditor->status }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($creditorPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.creditors.destroy', [$company, $site, $creditor]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_creditor_title') }}" data-delete-text="{{ __('main.delete_creditor_text', ['name' => $creditor->name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
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
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_creditors') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($creditors->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $creditors->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $creditors->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalCreditors }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($creditors->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $creditors->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($creditors->getUrlRange(1, $creditors->lastPage()) as $page => $url)
                                @if ($page === $creditors->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($creditors->hasMorePages())<a href="{{ $creditors->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-creditor-modal" id="creditorModal" tabindex="-1" aria-labelledby="creditorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form creditor-form" method="POST" action="{{ $creditorFormAction }}" data-create-action="{{ route('main.accounting.creditors.store', [$company, $site]) }}" data-title-create="{{ __('main.new_creditor') }}" data-title-edit="{{ __('main.edit_creditor') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="creditorMethod" value="PUT" @disabled(! $isEditingCreditor)>
                <input type="hidden" name="form_mode" id="creditorFormMode" value="{{ $isEditingCreditor ? 'edit' : 'create' }}">
                <input type="hidden" name="creditor_id" id="creditorId" value="{{ old('creditor_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="creditorModalLabel"><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i>{{ $isEditingCreditor ? __('main.edit_creditor') : __('main.new_creditor') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="creditorType" class="form-label">{{ __('main.creditor_type') }} *</label>
                            <select id="creditorType" name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $type => $label)
                                    <option value="{{ $type }}" @selected($oldType === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorName" class="form-label">{{ __('main.creditor_name') }} *</label>
                            <input id="creditorName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.creditor_name') }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorPhone" class="form-label">{{ __('main.phone') }}</label>
                            <input id="creditorPhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="creditorEmail" class="form-label">{{ __('main.email') }}</label>
                            <input id="creditorEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="creditorAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="creditorAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="creditor-finance-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-cash-stack" aria-hidden="true"></i> {{ __('main.debt_information') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="creditorCurrency" class="form-label">{{ __('admin.currency') }} *</label>
                                <select id="creditorCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected($oldCurrency === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="creditorInitialAmount" class="form-label">{{ __('main.initial_amount') }} *</label>
                                <input id="creditorInitialAmount" name="initial_amount" type="number" min="0" step="0.01" class="form-control @error('initial_amount') is-invalid @enderror" value="{{ old('initial_amount', 0) }}" placeholder="{{ __('main.initial_amount') }}">
                                @error('initial_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="creditorPaidAmount" class="form-label">{{ __('main.paid_amount') }} *</label>
                                <input id="creditorPaidAmount" name="paid_amount" type="number" min="0" step="0.01" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', 0) }}" placeholder="{{ __('main.paid_amount') }}">
                                @error('paid_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="creditorDueDate" class="form-label">{{ __('main.due_date') }}</label>
                                <input id="creditorDueDate" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" placeholder="{{ __('main.due_date') }}">
                                @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="creditorPriority" class="form-label">{{ __('main.priority') }} *</label>
                                <select id="creditorPriority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                                    @foreach ($priorityLabels as $priority => $label)
                                        <option value="{{ $priority }}" @selected($oldPriority === $priority)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="creditorStatus" class="form-label">{{ __('main.status') }} *</label>
                                <select id="creditorStatus" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusLabels as $status => $label)
                                        <option value="{{ $status }}" @selected($oldStatus === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="creditorDescription" class="form-label">{{ __('main.description') }}</label>
                                <textarea id="creditorDescription" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('main.description') }}">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="creditorSubmit" type="submit">{{ $isEditingCreditor ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasCreditorErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('creditorModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-creditors.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-creditors.js')) !!}</script>
</body>
</html>

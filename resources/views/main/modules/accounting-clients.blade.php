<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.customers') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalClients = $clients->total();
        $hasClientErrors = $errors->any();
        $isEditingClient = old('form_mode') === 'edit' && old('client_id');
        $clientFormAction = $isEditingClient
            ? route('main.accounting.clients.update', [$company, $site, old('client_id')])
            : route('main.accounting.clients.store', [$company, $site]);
        $oldType = old('type', \App\Models\AccountingClient::TYPE_INDIVIDUAL);
        $oldContacts = old('contacts', [['full_name' => '', 'position' => '', 'department' => '', 'email' => '', 'phone' => '']]);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'clients'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.customers') }}</h1>
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
                        <h1>{{ __('main.customers') }}</h1>
                        <p>{{ __('main.clients_subtitle') }}</p>
                    </div>
                    @if ($clientPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#clientModal" data-client-mode="create">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            {{ __('main.new_client') }}
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
                        <strong id="visibleCount">{{ $clients->count() }}</strong>
                        /
                        <strong>{{ $totalClients }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table clients-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    @php
                                        $clientTypeLabel = $client->type === \App\Models\AccountingClient::TYPE_COMPANY ? __('main.client_company') : __('main.client_individual');
                                        $contactsPayload = $client->contacts->map(fn ($contact) => [
                                            'full_name' => $contact->full_name,
                                            'position' => $contact->position,
                                            'department' => $contact->department,
                                            'email' => $contact->email,
                                            'phone' => $contact->phone,
                                        ])->values();
                                        $documentsPayload = collect()
                                            ->merge($client->proformaInvoices->map(fn ($document) => [
                                                'kind' => __('main.proforma_invoices'),
                                                'reference' => $document->reference,
                                                'date' => optional($document->issue_date)->format('d/m/Y') ?: '-',
                                                'date_sort' => optional($document->issue_date)->format('Y-m-d') ?: '',
                                                'status' => __('main.proforma_status_' . $document->status),
                                                'status_class' => 'proforma-status-' . $document->status,
                                                'total' => number_format((float) $document->total_ttc, 2, ',', ' ') . ' ' . $document->currency,
                                                'total_sort' => (float) $document->total_ttc,
                                                'print_url' => route('main.accounting.proforma-invoices.print', [$company, $site, $document]),
                                                'print_label' => __('main.print_pdf'),
                                            ]))
                                            ->merge($client->customerOrders->map(fn ($document) => [
                                                'kind' => __('main.customer_orders'),
                                                'reference' => $document->reference,
                                                'date' => optional($document->order_date)->format('d/m/Y') ?: '-',
                                                'date_sort' => optional($document->order_date)->format('Y-m-d') ?: '',
                                                'status' => __('main.customer_order_status_' . $document->status),
                                                'status_class' => 'customer-order-status-' . $document->status,
                                                'total' => number_format((float) $document->total_ttc, 2, ',', ' ') . ' ' . $document->currency,
                                                'total_sort' => (float) $document->total_ttc,
                                                'print_url' => null,
                                                'print_label' => null,
                                            ]))
                                            ->merge($client->deliveryNotes->map(fn ($document) => [
                                                'kind' => __('main.delivery_notes'),
                                                'reference' => $document->reference,
                                                'date' => optional($document->delivery_date)->format('d/m/Y') ?: '-',
                                                'date_sort' => optional($document->delivery_date)->format('Y-m-d') ?: '',
                                                'status' => __('main.delivery_note_status_' . $document->status),
                                                'status_class' => 'delivery-note-status-' . $document->status,
                                                'total' => '-',
                                                'total_sort' => 0,
                                                'print_url' => route('main.accounting.delivery-notes.print', [$company, $site, $document]),
                                                'print_label' => __('main.print_delivery_note'),
                                            ]))
                                            ->merge($client->salesInvoices->map(fn ($document) => [
                                                'kind' => __('main.sales_invoices'),
                                                'reference' => $document->reference,
                                                'date' => optional($document->invoice_date)->format('d/m/Y') ?: '-',
                                                'date_sort' => optional($document->invoice_date)->format('Y-m-d') ?: '',
                                                'status' => __('main.sales_invoice_status_' . $document->status),
                                                'status_class' => 'sales-invoice-status-' . $document->status,
                                                'total' => number_format((float) $document->total_ttc, 2, ',', ' ') . ' ' . $document->currency,
                                                'total_sort' => (float) $document->total_ttc,
                                                'print_url' => route('main.accounting.sales-invoices.print', [$company, $site, $document]),
                                                'print_label' => __('main.print_pdf'),
                                            ]))
                                            ->sortByDesc('date_sort')
                                            ->values();
                                        $clientDocumentsCount = $documentsPayload->count();
                                    @endphp
                                    <tr>
                                        <td>{{ ($clients->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $client->reference }}</span></td>
                                        <td>
                                            <span class="client-name">
                                                <span class="client-icon client-icon-{{ $client->type }}">
                                                    <i class="bi {{ $client->isCompany() ? 'bi-building' : 'bi-person' }}" aria-hidden="true"></i>
                                                </span>
                                                <span>
                                                    <strong>{{ $client->display_name }}</strong>
                                                    <small>{{ $client->isCompany() ? ($client->rccm ?: '-') : ($client->profession ?: '-') }}</small>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="status-pill client-type-{{ $client->type }}">{{ $clientTypeLabel }}</span></td>
                                        <td>
                                            @if ($clientDocumentsCount > 0 || $clientPermissions['can_update'] || $clientPermissions['can_delete'])
                                                <div class="table-actions">
                                                    @if ($clientDocumentsCount > 0)
                                                        <button type="button" class="table-button table-button-history" data-client-documents-trigger data-bs-toggle="modal" data-bs-target="#clientDocumentsModal" data-client-documents-title="{{ __('main.client_documents_title', ['name' => $client->display_name]) }}" data-client-documents='@json($documentsPayload)' aria-label="{{ __('main.view_client_documents') }}" title="{{ __('main.view_client_documents') }}">
                                                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($clientPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#clientModal" data-client-mode="edit" data-client-action="{{ route('main.accounting.clients.update', [$company, $site, $client]) }}" data-client-id="{{ $client->id }}" data-client-type="{{ $client->type }}" data-client-name="{{ $client->name }}" data-client-profession="{{ $client->profession }}" data-client-phone="{{ $client->phone }}" data-client-email="{{ $client->email }}" data-client-address="{{ $client->address }}" data-client-rccm="{{ $client->rccm }}" data-client-id-nat="{{ $client->id_nat }}" data-client-nif="{{ $client->nif }}" data-client-bank-name="{{ $client->bank_name }}" data-client-account-number="{{ $client->account_number }}" data-client-currency="{{ $client->currency }}" data-client-website="{{ $client->website }}" data-client-contacts='@json($contactsPayload)' aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($clientPermissions['can_delete'])
                                                        <form method="POST" action="{{ route('main.accounting.clients.destroy', [$company, $site, $client]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_client_title') }}" data-delete-text="{{ __('main.delete_client_text', ['name' => $client->display_name]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
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
                                    <tr class="empty-row"><td colspan="5">{{ __('main.no_clients') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="5">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($clients->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $clients->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $clients->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalClients }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($clients->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $clients->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($clients->getUrlRange(1, $clients->lastPage()) as $page => $url)
                                @if ($page === $clients->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($clients->hasMorePages())<a href="{{ $clients->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal related-table-modal" id="clientDocumentsModal" tabindex="-1" aria-labelledby="clientDocumentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content admin-form modal-table-dialog">
                <div class="modal-body" data-client-documents-table>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="clientDocumentsModalLabel">
                        <i class="bi bi-folder2-open" aria-hidden="true"></i>
                        {{ __('main.documents') }}
                    </h2>

                    <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                        <label class="search-box">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" data-client-documents-search placeholder="{{ __('admin.search') }}" autocomplete="off">
                        </label>
                        <span class="row-count">
                            <strong data-client-documents-visible-count>0</strong>
                            /
                            <strong data-client-documents-total-count>0</strong>
                            {{ __('admin.rows') }}
                        </span>
                    </section>

                    <div class="modal-table-frame">
                        <table class="company-table modal-data-table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-client-documents-sort="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-client-documents-sort="1">{{ __('main.type') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-client-documents-sort="2">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-client-documents-sort="3" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-client-documents-sort="4" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-client-documents-sort="5">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody data-client-documents-body></tbody>
                        </table>
                        <p class="modal-table-empty" data-client-documents-empty hidden>{{ __('main.no_client_documents') }}</p>
                    </div>

                    <section class="subscriptions-pagination modal-table-pagination" data-client-documents-pagination data-previous-label="{{ __('admin.previous') }}" data-next-label="{{ __('admin.next') }}" data-showing-label="{{ __('admin.showing') }}" data-to-label="{{ __('admin.to') }}" data-on-label="{{ __('admin.on') }}" hidden aria-label="{{ __('admin.pagination') }}">
                        <span data-client-documents-pagination-count></span>
                        <nav class="pagination-shell" data-client-documents-pagination-nav aria-label="{{ __('admin.pagination') }}"></nav>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade subscription-modal accounting-client-modal" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form client-form" method="POST" action="{{ $clientFormAction }}" data-create-action="{{ route('main.accounting.clients.store', [$company, $site]) }}" data-title-create="{{ __('main.new_client') }}" data-title-edit="{{ __('main.edit_client') }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="clientMethod" value="PUT" @disabled(! $isEditingClient)>
                <input type="hidden" name="form_mode" id="clientFormMode" value="{{ $isEditingClient ? 'edit' : 'create' }}">
                <input type="hidden" name="client_id" id="clientId" value="{{ old('client_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="clientModalLabel"><i class="bi bi-person-plus" aria-hidden="true"></i>{{ $isEditingClient ? __('main.edit_client') : __('main.new_client') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="clientType" class="form-label">{{ __('main.client_type') }} *</label>
                            <select id="clientType" name="type" class="form-select @error('type') is-invalid @enderror" data-required-message="{{ __('validation.required', ['attribute' => __('main.client_type')]) }}">
                                <option value="{{ \App\Models\AccountingClient::TYPE_INDIVIDUAL }}" @selected($oldType === \App\Models\AccountingClient::TYPE_INDIVIDUAL)>{{ __('main.client_individual') }}</option>
                                <option value="{{ \App\Models\AccountingClient::TYPE_COMPANY }}" @selected($oldType === \App\Models\AccountingClient::TYPE_COMPANY)>{{ __('main.client_company') }}</option>
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.client_type')]) }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="clientName" class="form-label" data-label-individual="{{ __('main.full_name') }} *" data-label-company="{{ __('main.company_name') }} *">{{ $oldType === \App\Models\AccountingClient::TYPE_COMPANY ? __('main.company_name') : __('main.full_name') }} *</label>
                            <input id="clientName" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('main.full_name') }}" data-placeholder-individual="{{ __('main.full_name') }}" data-placeholder-company="{{ __('main.company_name') }}" data-required-message="{{ __('validation.required', ['attribute' => __('main.name')]) }}">
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@else<div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('main.name')]) }}</div>@enderror
                        </div>
                    </div>

                    <div class="client-type-panel" data-client-panel="individual" @hidden($oldType !== \App\Models\AccountingClient::TYPE_INDIVIDUAL)>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="clientProfession" class="form-label">{{ __('main.profession') }}</label>
                                <input id="clientProfession" name="profession" type="text" class="form-control @error('profession') is-invalid @enderror" value="{{ old('profession') }}" placeholder="{{ __('main.profession') }}">
                                @error('profession')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="clientPhone" class="form-label">{{ __('main.phone') }}</label>
                                <input id="clientPhone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('main.phone') }}">
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="clientEmail" class="form-label">{{ __('main.email') }}</label>
                                <input id="clientEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('main.email') }}">
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="client-type-panel" data-client-panel="company" @hidden($oldType !== \App\Models\AccountingClient::TYPE_COMPANY)>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="clientRccm" class="form-label">{{ __('admin.rccm') }}</label>
                                <input id="clientRccm" name="rccm" type="text" class="form-control @error('rccm') is-invalid @enderror" value="{{ old('rccm') }}" placeholder="{{ __('admin.rccm') }}">
                                @error('rccm')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="clientIdNat" class="form-label">{{ __('admin.id_nat') }}</label>
                                <input id="clientIdNat" name="id_nat" type="text" class="form-control @error('id_nat') is-invalid @enderror" value="{{ old('id_nat') }}" placeholder="{{ __('admin.id_nat') }}">
                                @error('id_nat')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="clientNif" class="form-label">{{ __('admin.nif') }}</label>
                                <input id="clientNif" name="nif" type="text" class="form-control @error('nif') is-invalid @enderror" value="{{ old('nif') }}" placeholder="{{ __('admin.nif') }}">
                                @error('nif')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="clientWebsite" class="form-label">{{ __('admin.website') }}</label>
                                <input id="clientWebsite" name="website" type="text" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}" placeholder="{{ __('admin.website') }}">
                                @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <section class="client-contacts-section">
                            <div class="form-section-title">
                                <span><i class="bi bi-person-lines-fill" aria-hidden="true"></i> {{ __('main.client_contacts') }}</span>
                                <button type="button" class="light-action" data-add-client-contact>
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    {{ __('main.add_contact') }}
                                </button>
                            </div>
                            <div class="client-contact-list" data-client-contact-list>
                                @foreach ($oldContacts as $index => $contact)
                                    <div class="client-contact-card" data-client-contact-row>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('main.full_name') }} *</label>
                                                <input name="contacts[{{ $index }}][full_name]" type="text" class="form-control @error("contacts.$index.full_name") is-invalid @enderror" value="{{ $contact['full_name'] ?? '' }}" placeholder="{{ __('main.full_name') }}">
                                                @error("contacts.$index.full_name")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">{{ __('main.position_or_grade') }}</label>
                                                <input name="contacts[{{ $index }}][position]" type="text" class="form-control @error("contacts.$index.position") is-invalid @enderror" value="{{ $contact['position'] ?? '' }}" placeholder="{{ __('main.position_or_grade') }}">
                                                @error("contacts.$index.position")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-1 d-flex justify-content-end">
                                                <button type="button" class="icon-light-button" data-remove-client-contact aria-label="{{ __('admin.delete') }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('main.department') }}</label>
                                                <input name="contacts[{{ $index }}][department]" type="text" class="form-control @error("contacts.$index.department") is-invalid @enderror" value="{{ $contact['department'] ?? '' }}" placeholder="{{ __('main.department') }}">
                                                @error("contacts.$index.department")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('main.email') }}</label>
                                                <input name="contacts[{{ $index }}][email]" type="email" class="form-control @error("contacts.$index.email") is-invalid @enderror" value="{{ $contact['email'] ?? '' }}" placeholder="{{ __('main.email') }}">
                                                @error("contacts.$index.email")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('main.phone') }}</label>
                                                <input name="contacts[{{ $index }}][phone]" type="text" class="form-control @error("contacts.$index.phone") is-invalid @enderror" value="{{ $contact['phone'] ?? '' }}" placeholder="{{ __('main.phone') }}">
                                                @error("contacts.$index.phone")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6" data-client-address-wrapper>
                            <label for="clientAddress" class="form-label">{{ __('main.address') }}</label>
                            <input id="clientAddress" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('main.address') }}">
                            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <section class="client-bank-section">
                        <div class="form-section-title">
                            <span><i class="bi bi-bank" aria-hidden="true"></i> {{ __('admin.account_numbers') }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="clientBankName" class="form-label">{{ __('admin.bank_name') }}</label>
                                <input id="clientBankName" name="bank_name" type="text" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="{{ __('admin.bank_name') }}">
                                @error('bank_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="clientAccountNumber" class="form-label">{{ __('main.account_number') }}</label>
                                <input id="clientAccountNumber" name="account_number" type="text" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="{{ __('main.account_number') }}">
                                @error('account_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="clientCurrency" class="form-label">{{ __('main.account_currency') }}</label>
                                <select id="clientCurrency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="">{{ __('admin.currency') }}</option>
                                    @foreach ($currencies as $code => $currency)
                                        <option value="{{ $code }}" @selected(old('currency') === $code)>{{ \App\Support\CurrencyCatalog::label($code) }}</option>
                                    @endforeach
                                </select>
                                @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="clientSubmit" type="submit">{{ $isEditingClient ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="clientContactTemplate">
        <div class="client-contact-card" data-client-contact-row>
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">{{ __('main.full_name') }} *</label>
                    <input data-name="contacts[__INDEX__][full_name]" type="text" class="form-control" placeholder="{{ __('main.full_name') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('main.position_or_grade') }}</label>
                    <input data-name="contacts[__INDEX__][position]" type="text" class="form-control" placeholder="{{ __('main.position_or_grade') }}">
                </div>
                <div class="col-md-1 d-flex justify-content-end">
                    <button type="button" class="icon-light-button" data-remove-client-contact aria-label="{{ __('admin.delete') }}">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('main.department') }}</label>
                    <input data-name="contacts[__INDEX__][department]" type="text" class="form-control" placeholder="{{ __('main.department') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('main.email') }}</label>
                    <input data-name="contacts[__INDEX__][email]" type="email" class="form-control" placeholder="{{ __('main.email') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('main.phone') }}</label>
                    <input data-name="contacts[__INDEX__][phone]" type="text" class="form-control" placeholder="{{ __('main.phone') }}">
                </div>
            </div>
        </div>
    </template>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasClientErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('clientModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-clients.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-clients.js')) !!}</script>
</body>
</html>

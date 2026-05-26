<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.purchase_orders') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.purchase-orders', [$company, $site]);
        $totalRecords = $purchaseOrders->total();
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'purchase-orders'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.purchase_orders')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.purchase_orders') }}</h1>
                        <p>{{ __('main.purchase_orders_subtitle') }}</p>
                    </div>
                    @if ($purchaseOrderPermissions['can_create'])
                        <a class="primary-action" href="{{ route('main.accounting.purchase-orders.create', [$company, $site]) }}">
                            <i class="bi bi-clipboard-plus" aria-hidden="true"></i>
                            {{ __('main.new_purchase_order') }}
                        </a>
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

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label for="purchaseOrderSupplierFilter" class="form-label">{{ __('main.supplier') }}</label>
                                <select id="purchaseOrderSupplierFilter" name="supplier_id" class="form-select">
                                    <option value="0">{{ __('main.all_suppliers') }}</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected((int) $filters['supplier_id'] === (int) $supplier->id)>{{ $supplier->name }} ({{ $supplier->reference }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="purchaseOrderDateFromFilter" class="form-label">{{ __('main.date_from') }}</label>
                                <input id="purchaseOrderDateFromFilter" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="purchaseOrderDateToFilter" class="form-label">{{ __('main.date_to') }}</label>
                                <input id="purchaseOrderDateToFilter" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="purchaseOrderCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="purchaseOrderCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="purchaseOrderStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="purchaseOrderStatusFilter" name="status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex align-items-end justify-content-end gap-2 receipt-filter-actions">
                                <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('main.reset_filters') }}</a>
                                <button class="modal-submit" type="submit">{{ __('main.apply_filters') }}</button>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $purchaseOrders->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.supplier') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3" data-sort-type="date">{{ __('main.order_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="date">{{ __('main.expected_delivery_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseOrders as $purchaseOrder)
                                    <tr>
                                        <td>{{ ($purchaseOrders->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>
                                            <span class="reference-pill">{{ $purchaseOrder->reference }}</span>
                                            @if ($purchaseOrder->supplier_reference)
                                                <small class="d-block text-muted">{{ $purchaseOrder->supplier_reference }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $purchaseOrder->supplier?->name ?? '-' }}</td>
                                        <td data-sort-value="{{ optional($purchaseOrder->order_date)->format('Y-m-d') }}">{{ optional($purchaseOrder->order_date)->format('d/m/Y') }}</td>
                                        <td data-sort-value="{{ optional($purchaseOrder->expected_delivery_date)->format('Y-m-d') }}">{{ optional($purchaseOrder->expected_delivery_date)->format('d/m/Y') ?: '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $purchaseOrder->total_ttc }}">{{ number_format((float) $purchaseOrder->total_ttc, 2, ',', ' ') }} {{ $purchaseOrder->currency }}</td>
                                        <td><span class="status-pill purchase-status-{{ $purchaseOrder->status }}">{{ $statusLabels[$purchaseOrder->status] ?? $purchaseOrder->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ route('main.accounting.purchase-orders.print', [$company, $site, $purchaseOrder]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.print_pdf') }}" title="{{ __('main.print_pdf') }}">
                                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                                </a>
                                                @if ($purchaseOrderPermissions['can_create'] && $purchaseOrder->isConvertible())
                                                    <form method="POST" action="{{ route('main.accounting.purchase-orders.convert', [$company, $site, $purchaseOrder]) }}">
                                                        @csrf
                                                        <button type="submit" class="table-button table-button-history" aria-label="{{ __('main.convert_to_purchase') }}" title="{{ __('main.convert_to_purchase') }}">
                                                            <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($purchaseOrderPermissions['can_update'] && $purchaseOrder->isEditable())
                                                    <a class="table-button table-button-edit" href="{{ route('main.accounting.purchase-orders.edit', [$company, $site, $purchaseOrder]) }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if ($purchaseOrderPermissions['can_delete'] && $purchaseOrder->isEditable())
                                                    <form method="POST" action="{{ route('main.accounting.purchase-orders.destroy', [$company, $site, $purchaseOrder]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_purchase_order_title') }}" data-delete-text="{{ __('main.delete_purchase_order_text', ['reference' => $purchaseOrder->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_purchase_orders') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($purchaseOrders->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $purchaseOrders->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $purchaseOrders->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($purchaseOrders->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $purchaseOrders->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($purchaseOrders->getUrlRange(1, $purchaseOrders->lastPage()) as $page => $url)
                                @if ($page === $purchaseOrders->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($purchaseOrders->hasMorePages())<a href="{{ $purchaseOrders->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>

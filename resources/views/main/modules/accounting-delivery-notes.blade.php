<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.delivery_notes') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalDeliveryNotes = $deliveryNotes->total();
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'delivery-notes'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.delivery_notes')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.delivery_notes') }}</h1>
                        <p>{{ __('main.delivery_notes_subtitle') }}</p>
                    </div>
                    @if ($deliveryPermissions['can_create'])
                        <form class="delivery-note-create-shortcut" method="GET" action="{{ route('main.accounting.delivery-notes.create', [$company, $site]) }}">
                            <select name="order" class="form-select" required>
                                <option value="">{{ __('main.customer_order') }}</option>
                                @foreach ($orders as $order)
                                    <option value="{{ $order->id }}">{{ $order->reference }} - {{ $order->client?->display_name ?? '-' }}</option>
                                @endforeach
                            </select>
                            <button class="primary-action" type="submit" @disabled($orders->isEmpty())>
                                <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
                                {{ __('main.new_delivery_note') }}
                            </button>
                        </form>
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
                        <strong id="visibleCount">{{ $deliveryNotes->count() }}</strong>
                        /
                        <strong>{{ $totalDeliveryNotes }}</strong>
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
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.customer_order') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.delivery_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.delivered_quantity') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deliveryNotes as $deliveryNote)
                                    @php
                                        $deliveredQuantity = (float) $deliveryNote->lines->sum('quantity');
                                    @endphp
                                    <tr>
                                        <td>{{ ($deliveryNotes->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $deliveryNote->reference }}</span></td>
                                        <td>{{ $deliveryNote->client?->display_name ?? '-' }}</td>
                                        <td>{{ $deliveryNote->customerOrder?->reference ?? '-' }}</td>
                                        <td>{{ optional($deliveryNote->delivery_date)->format('d/m/Y') }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $deliveredQuantity }}">{{ number_format($deliveredQuantity, 2, ',', ' ') }}</td>
                                        <td><span class="status-pill delivery-note-status-{{ $deliveryNote->status }}">{{ $statusLabels[$deliveryNote->status] ?? $deliveryNote->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ route('main.accounting.delivery-notes.print', [$company, $site, $deliveryNote]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.print_delivery_note') }}" title="{{ __('main.print_delivery_note') }}">
                                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                                </a>
                                                @if ($deliveryPermissions['can_create'])
                                                    <a class="table-button table-button-print" href="{{ route('main.accounting.sales-invoices.create', [$company, $site, 'delivery_note' => $deliveryNote->id]) }}" aria-label="{{ __('main.new_sales_invoice') }}" title="{{ __('main.new_sales_invoice') }}">
                                                        <i class="bi bi-receipt" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if ($deliveryPermissions['can_delete'] && ! $deliveryNote->isStockReleased())
                                                    <form method="POST" action="{{ route('main.accounting.delivery-notes.destroy', [$company, $site, $deliveryNote]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_delivery_note_title') }}" data-delete-text="{{ __('main.delete_delivery_note_text', ['reference' => $deliveryNote->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_delivery_notes') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($deliveryNotes->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $deliveryNotes->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $deliveryNotes->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalDeliveryNotes }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($deliveryNotes->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $deliveryNotes->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($deliveryNotes->getUrlRange(1, $deliveryNotes->lastPage()) as $page => $url)
                                @if ($page === $deliveryNotes->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($deliveryNotes->hasMorePages())<a href="{{ $deliveryNotes->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
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

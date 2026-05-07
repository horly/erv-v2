<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.customer_orders') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalOrders = $orders->total();
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'customer-orders'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.customer_orders')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.customer_orders') }}</h1>
                        <p>{{ __('main.customer_orders_subtitle') }}</p>
                    </div>
                    @if ($orderPermissions['can_create'])
                        <a class="primary-action" href="{{ route('main.accounting.customer-orders.create', [$company, $site]) }}">
                            <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                            {{ __('main.new_customer_order') }}
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

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $orders->count() }}</strong>
                        /
                        <strong>{{ $totalOrders }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table customer-order-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.expected_delivery_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.margin') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td>{{ ($orders->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $order->reference }}</span></td>
                                        <td>{{ $order->client?->name ?? '-' }}</td>
                                        <td>{{ optional($order->order_date)->format('d/m/Y') }}</td>
                                        <td>{{ optional($order->expected_delivery_date)->format('d/m/Y') ?: '-' }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $order->margin_total }}">{{ number_format((float) $order->margin_total, 2, ',', ' ') }} {{ $order->currency }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $order->total_ttc }}">{{ number_format((float) $order->total_ttc, 2, ',', ' ') }} {{ $order->currency }}</td>
                                        <td><span class="status-pill customer-order-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($orderPermissions['can_update'])
                                                    <a class="table-button table-button-edit" href="{{ route('main.accounting.customer-orders.edit', [$company, $site, $order]) }}" aria-label="{{ __('admin.edit') }}">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if ($orderPermissions['can_delete'])
                                                    <form method="POST" action="{{ route('main.accounting.customer-orders.destroy', [$company, $site, $order]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_customer_order_title') }}" data-delete-text="{{ __('main.delete_customer_order_text', ['reference' => $order->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="9">{{ __('main.no_customer_orders') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="9">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($orders->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $orders->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $orders->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalOrders }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($orders->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $orders->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                                @if ($page === $orders->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
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

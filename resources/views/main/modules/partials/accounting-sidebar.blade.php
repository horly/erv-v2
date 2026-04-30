@php
    $activeAccountingPage ??= 'dashboard';
    $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, \App\Models\CompanySite::MODULE_ACCOUNTING]);
    $prospectsRoute = route('main.accounting.prospects', [$company, $site]);
    $clientsRoute = route('main.accounting.clients', [$company, $site]);
    $suppliersRoute = route('main.accounting.suppliers', [$company, $site]);
    $creditorsRoute = route('main.accounting.creditors', [$company, $site]);
    $debtorsRoute = route('main.accounting.debtors', [$company, $site]);
    $partnersRoute = route('main.accounting.partners', [$company, $site]);
    $salesRepresentativesRoute = route('main.accounting.sales-representatives', [$company, $site]);
    $stockRoute = fn (string $resource) => route('main.accounting.stock.index', [$company, $site, $resource]);
    $navigationGroups = [
        [
            'label' => __('main.contacts'),
            'icon' => 'bi-person-lines-fill',
            'items' => [
                ['label' => __('main.prospects'), 'icon' => 'bi-person-plus', 'href' => $prospectsRoute, 'active' => $activeAccountingPage === 'prospects'],
                ['label' => __('main.customers'), 'icon' => 'bi-person-check', 'href' => $clientsRoute, 'active' => $activeAccountingPage === 'clients'],
                ['label' => __('main.suppliers'), 'icon' => 'bi-truck', 'href' => $suppliersRoute, 'active' => $activeAccountingPage === 'suppliers'],
                ['label' => __('main.creditors'), 'icon' => 'bi-arrow-up-right-circle', 'href' => $creditorsRoute, 'active' => $activeAccountingPage === 'creditors'],
                ['label' => __('main.debtors'), 'icon' => 'bi-arrow-down-left-circle', 'href' => $debtorsRoute, 'active' => $activeAccountingPage === 'debtors'],
                ['label' => __('main.partners'), 'icon' => 'bi-diagram-3', 'href' => $partnersRoute, 'active' => $activeAccountingPage === 'partners'],
                ['label' => __('main.sales_representatives'), 'icon' => 'bi-briefcase', 'href' => $salesRepresentativesRoute, 'active' => $activeAccountingPage === 'sales-representatives'],
            ],
        ],
        [
            'label' => __('main.stock'),
            'icon' => 'bi-box-seam',
            'items' => [
                ['label' => __('main.items'), 'icon' => 'bi-box', 'href' => $stockRoute('items'), 'active' => $activeAccountingPage === 'stock-items'],
                ['label' => __('main.categories'), 'icon' => 'bi-folder', 'href' => $stockRoute('categories'), 'active' => $activeAccountingPage === 'stock-categories'],
                ['label' => __('main.subcategories'), 'icon' => 'bi-tags', 'href' => $stockRoute('subcategories'), 'active' => $activeAccountingPage === 'stock-subcategories'],
                ['label' => __('main.stock_warehouses'), 'icon' => 'bi-buildings', 'href' => $stockRoute('warehouses'), 'active' => $activeAccountingPage === 'stock-warehouses'],
                ['label' => __('main.stock_movements'), 'icon' => 'bi-arrow-left-right', 'href' => $stockRoute('movements'), 'active' => $activeAccountingPage === 'stock-movements'],
                ['label' => __('main.stock_inventories'), 'icon' => 'bi-clipboard-check', 'href' => $stockRoute('inventories'), 'active' => $activeAccountingPage === 'stock-inventories'],
                ['label' => __('main.stock_alerts'), 'icon' => 'bi-bell', 'href' => $stockRoute('alerts'), 'active' => $activeAccountingPage === 'stock-alerts'],
                ['label' => __('main.stock_units'), 'icon' => 'bi-rulers', 'href' => $stockRoute('units'), 'active' => $activeAccountingPage === 'stock-units'],
                ['label' => __('main.stock_batches'), 'icon' => 'bi-upc-scan', 'href' => $stockRoute('batches'), 'active' => $activeAccountingPage === 'stock-batches'],
                ['label' => __('main.stock_transfers'), 'icon' => 'bi-truck', 'href' => $stockRoute('transfers'), 'active' => $activeAccountingPage === 'stock-transfers'],
            ],
        ],
        [
            'label' => __('main.services'),
            'icon' => 'bi-grid-1x2',
            'items' => [
                ['label' => __('main.price_list'), 'icon' => 'bi-card-list', 'href' => '#', 'active' => false],
                ['label' => __('main.subcategories'), 'icon' => 'bi-tags', 'href' => '#', 'active' => false],
                ['label' => __('main.categories'), 'icon' => 'bi-folder', 'href' => '#', 'active' => false],
            ],
        ],
    ];
    $salesItems = [
        ['label' => __('main.sales_invoices'), 'icon' => 'bi-receipt'],
        ['label' => __('main.proforma_invoices'), 'icon' => 'bi-file-earmark-richtext'],
        ['label' => __('main.delivery_notes'), 'icon' => 'bi-box-arrow-up'],
        ['label' => __('main.cash_register'), 'icon' => 'bi-calculator'],
        ['label' => __('main.other_income'), 'icon' => 'bi-plus-circle'],
    ];
    $expenseItems = [
        ['label' => __('main.purchases'), 'icon' => 'bi-bag-check'],
        ['label' => __('main.purchase_orders'), 'icon' => 'bi-clipboard-check'],
        ['label' => __('main.expenses'), 'icon' => 'bi-wallet2'],
    ];
    $otherItems = [
        ['label' => __('main.debts'), 'icon' => 'bi-arrow-up-right'],
        ['label' => __('main.receivables'), 'icon' => 'bi-arrow-down-left'],
        ['label' => __('main.taxes'), 'icon' => 'bi-percent'],
        ['label' => __('main.cashflow'), 'icon' => 'bi-activity'],
        ['label' => __('main.bank_reconciliation'), 'icon' => 'bi-bank'],
        ['label' => __('main.payment_reminders'), 'icon' => 'bi-bell'],
    ];
@endphp

<aside class="dashboard-sidebar accounting-sidebar">
    <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="EXAD ERP">
        <span class="sidebar-logo">
            <img src="{{ asset('img/logo/exad-1200x1200.jpg') }}" alt="EXAD Solution & Services">
        </span>
        <span>
            <strong>EXAD ERP</strong>
            <small>{{ __('main.accounting_dashboard') }}</small>
        </span>
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

    <nav class="sidebar-nav accounting-nav" aria-label="{{ __('main.accounting_navigation') }}">
        <a class="nav-link {{ $activeAccountingPage === 'dashboard' ? 'active' : '' }}" href="{{ $moduleRoute }}">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>
            {{ __('main.dashboard') }}
        </a>

        @foreach ($navigationGroups as $group)
            <div class="sidebar-group {{ collect($group['items'])->contains(fn ($item) => $item['active']) ? 'open' : '' }}">
                <button class="sidebar-group-toggle" type="button" title="{{ $group['label'] }}" aria-expanded="{{ collect($group['items'])->contains(fn ($item) => $item['active']) ? 'true' : 'false' }}" data-accounting-submenu>
                    <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $group['label'] }}</span>
                    <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                </button>
                <div class="sidebar-subnav">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['href'] }}" title="{{ $item['label'] }}" class="{{ $item['active'] ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        <a class="nav-link" href="#">
            <i class="bi bi-currency-exchange" aria-hidden="true"></i>
            {{ __('main.currencies') }}
        </a>
        <a class="nav-link" href="#">
            <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
            {{ __('main.payment_methods') }}
        </a>

        <span class="sidebar-section-title">{{ __('main.billing') }}</span>

        <div class="sidebar-group">
            <button class="sidebar-group-toggle" type="button" title="{{ __('main.sales') }}" aria-expanded="false" data-accounting-submenu>
                <i class="bi bi-cart-check" aria-hidden="true"></i>
                <span>{{ __('main.sales') }}</span>
                <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
            </button>
            <div class="sidebar-subnav">
                @foreach ($salesItems as $item)
                    <a href="#" title="{{ $item['label'] }}">
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="sidebar-group">
            <button class="sidebar-group-toggle" type="button" title="{{ __('main.expenses_group') }}" aria-expanded="false" data-accounting-submenu>
                <i class="bi bi-cash-stack" aria-hidden="true"></i>
                <span>{{ __('main.expenses_group') }}</span>
                <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
            </button>
            <div class="sidebar-subnav">
                @foreach ($expenseItems as $item)
                    <a href="#" title="{{ $item['label'] }}">
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <span class="sidebar-section-title">{{ __('main.other') }}</span>

        @foreach ($otherItems as $item)
            <a class="nav-link" href="#">
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                {{ $item['label'] }}
            </a>
        @endforeach

        <a class="nav-link" href="#">
            <i class="bi bi-check2-square" aria-hidden="true"></i>
            {{ __('main.tasks') }}
        </a>

        <a class="nav-link" href="#">
            <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
            {{ __('main.reports') }}
        </a>

        <a class="nav-link" href="#">
            <i class="bi bi-sliders" aria-hidden="true"></i>
            {{ __('main.module_settings') }}
        </a>
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
        <span>{{ $site->name }}</span>
    </div>
</aside>

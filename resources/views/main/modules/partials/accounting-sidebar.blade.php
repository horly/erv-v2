@php
    $activeAccountingPage ??= 'dashboard';
    $visibleAccountingMenuKeys = request()->attributes->get('accounting_visible_menu_keys', \App\Support\AccountingModuleNavigation::keys());
    $canManageAccountingSettings = request()->attributes->get('can_manage_accounting_settings', $user->isAdmin() || $user->isSuperadmin());
    $canOpenAccountingMenu = fn (string $key) => $canManageAccountingSettings || in_array($key, $visibleAccountingMenuKeys, true);
    $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, \App\Models\CompanySite::MODULE_ACCOUNTING]);
    $prospectsRoute = route('main.accounting.prospects', [$company, $site]);
    $clientsRoute = route('main.accounting.clients', [$company, $site]);
    $suppliersRoute = route('main.accounting.suppliers', [$company, $site]);
    $creditorsRoute = route('main.accounting.creditors', [$company, $site]);
    $debtorsRoute = route('main.accounting.debtors', [$company, $site]);
    $partnersRoute = route('main.accounting.partners', [$company, $site]);
    $salesRepresentativesRoute = route('main.accounting.sales-representatives', [$company, $site]);
    $customerOrdersRoute = route('main.accounting.customer-orders', [$company, $site]);
    $deliveryNotesRoute = route('main.accounting.delivery-notes', [$company, $site]);
    $salesInvoicesRoute = route('main.accounting.sales-invoices', [$company, $site]);
    $creditNotesRoute = route('main.accounting.credit-notes', [$company, $site]);
    $receiptsRoute = route('main.accounting.receipts', [$company, $site]);
    $otherIncomesRoute = route('main.accounting.other-incomes', [$company, $site]);
    $cashRegisterRoute = route('main.accounting.cash-register', [$company, $site]);
    $purchasesRoute = route('main.accounting.purchases', [$company, $site]);
    $purchaseOrdersRoute = route('main.accounting.purchase-orders', [$company, $site]);
    $expensesRoute = route('main.accounting.expenses', [$company, $site]);
    $debtsRoute = route('main.accounting.debts', [$company, $site]);
    $receivablesRoute = route('main.accounting.receivables', [$company, $site]);
    $stockRoute = fn (string $resource) => route('main.accounting.stock.index', [$company, $site, $resource]);
    $serviceRoute = fn (string $resource) => route('main.accounting.services.index', [$company, $site, $resource]);
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
                ['label' => __('main.price_list'), 'icon' => 'bi-card-list', 'href' => $serviceRoute('price-list'), 'active' => $activeAccountingPage === 'service-price-list'],
                ['label' => __('main.service_categories'), 'icon' => 'bi-folder', 'href' => $serviceRoute('categories'), 'active' => $activeAccountingPage === 'service-categories'],
                ['label' => __('main.service_subcategories'), 'icon' => 'bi-tags', 'href' => $serviceRoute('subcategories'), 'active' => $activeAccountingPage === 'service-subcategories'],
                ['label' => __('main.service_units'), 'icon' => 'bi-rulers', 'href' => $serviceRoute('units'), 'active' => $activeAccountingPage === 'service-units'],
                ['label' => __('main.recurring_services'), 'icon' => 'bi-arrow-repeat', 'href' => $serviceRoute('recurring'), 'active' => $activeAccountingPage === 'service-recurring'],
            ],
        ],
    ];
    $salesItems = [
        ['label' => __('main.proforma_invoices'), 'icon' => 'bi-file-earmark-richtext', 'href' => route('main.accounting.proforma-invoices', [$company, $site]), 'active' => $activeAccountingPage === 'proforma-invoices'],
        ['label' => __('main.customer_orders'), 'icon' => 'bi-clipboard-check', 'href' => $customerOrdersRoute, 'active' => $activeAccountingPage === 'customer-orders'],
        ['label' => __('main.delivery_notes'), 'icon' => 'bi-box-arrow-up', 'href' => $deliveryNotesRoute, 'active' => $activeAccountingPage === 'delivery-notes'],
        ['label' => __('main.sales_invoices'), 'icon' => 'bi-receipt', 'href' => $salesInvoicesRoute, 'active' => $activeAccountingPage === 'sales-invoices'],
        ['label' => __('main.credit_notes'), 'icon' => 'bi-arrow-counterclockwise', 'href' => $creditNotesRoute, 'active' => $activeAccountingPage === 'credit-notes'],
        ['label' => __('main.payments_received'), 'icon' => 'bi-cash-coin', 'href' => $receiptsRoute, 'active' => $activeAccountingPage === 'receipts'],
        ['label' => __('main.cash_register'), 'icon' => 'bi-calculator', 'href' => $cashRegisterRoute, 'active' => $activeAccountingPage === 'cash-register'],
        ['label' => __('main.other_income'), 'icon' => 'bi-plus-circle', 'href' => $otherIncomesRoute, 'active' => $activeAccountingPage === 'other-incomes'],
    ];
    $salesIsOpen = collect($salesItems)->contains(fn ($item) => $item['active'] ?? false);
    $expenseItems = [
        ['label' => __('main.purchases'), 'icon' => 'bi-bag-check', 'href' => $purchasesRoute, 'active' => $activeAccountingPage === 'purchases'],
        ['label' => __('main.purchase_orders'), 'icon' => 'bi-clipboard-check', 'href' => $purchaseOrdersRoute, 'active' => $activeAccountingPage === 'purchase-orders'],
        ['label' => __('main.expenses'), 'icon' => 'bi-wallet2', 'href' => $expensesRoute, 'active' => $activeAccountingPage === 'expenses'],
    ];
    $expensesIsOpen = collect($expenseItems)->contains(fn ($item) => $item['active'] ?? false);
    $otherItems = [
        ['label' => __('main.debts'), 'icon' => 'bi-arrow-up-right', 'href' => $debtsRoute, 'active' => $activeAccountingPage === 'debts'],
        ['label' => __('main.receivables'), 'icon' => 'bi-arrow-down-left', 'href' => $receivablesRoute, 'active' => $activeAccountingPage === 'receivables'],
        ['label' => __('main.taxes'), 'icon' => 'bi-percent', 'href' => route('main.accounting.taxes', [$company, $site]), 'active' => $activeAccountingPage === 'taxes'],
        ['label' => __('main.cashflow'), 'icon' => 'bi-activity', 'href' => route('main.accounting.treasury', [$company, $site]), 'active' => $activeAccountingPage === 'treasury'],
        ['label' => __('main.bank_reconciliation'), 'icon' => 'bi-bank', 'href' => route('main.accounting.bank-reconciliations', [$company, $site]), 'active' => $activeAccountingPage === 'bank-reconciliations'],
        ['label' => __('main.payment_reminders'), 'icon' => 'bi-bell', 'href' => route('main.accounting.payment-reminders', [$company, $site]), 'active' => $activeAccountingPage === 'payment-reminders'],
    ];
    $menuRouteKeys = [
        $prospectsRoute => 'prospects',
        $clientsRoute => 'clients',
        $suppliersRoute => 'suppliers',
        $creditorsRoute => 'creditors',
        $debtorsRoute => 'debtors',
        $partnersRoute => 'partners',
        $salesRepresentativesRoute => 'sales-representatives',
        $stockRoute('items') => 'stock-items',
        $stockRoute('categories') => 'stock-categories',
        $stockRoute('subcategories') => 'stock-subcategories',
        $stockRoute('warehouses') => 'stock-warehouses',
        $stockRoute('movements') => 'stock-movements',
        $stockRoute('inventories') => 'stock-inventories',
        $stockRoute('alerts') => 'stock-alerts',
        $stockRoute('units') => 'stock-units',
        $stockRoute('batches') => 'stock-batches',
        $stockRoute('transfers') => 'stock-transfers',
        $serviceRoute('price-list') => 'service-price-list',
        $serviceRoute('categories') => 'service-categories',
        $serviceRoute('subcategories') => 'service-subcategories',
        $serviceRoute('units') => 'service-units',
        $serviceRoute('recurring') => 'service-recurring',
        route('main.accounting.proforma-invoices', [$company, $site]) => 'proforma-invoices',
        $customerOrdersRoute => 'customer-orders',
        $deliveryNotesRoute => 'delivery-notes',
        $salesInvoicesRoute => 'sales-invoices',
        $creditNotesRoute => 'credit-notes',
        $receiptsRoute => 'receipts',
        $cashRegisterRoute => 'cash-register',
        $otherIncomesRoute => 'other-incomes',
        $purchasesRoute => 'purchases',
        $purchaseOrdersRoute => 'purchase-orders',
        $expensesRoute => 'expenses',
        $debtsRoute => 'debts',
        $receivablesRoute => 'receivables',
        route('main.accounting.taxes', [$company, $site]) => 'taxes',
        route('main.accounting.treasury', [$company, $site]) => 'treasury',
        route('main.accounting.bank-reconciliations', [$company, $site]) => 'bank-reconciliations',
        route('main.accounting.payment-reminders', [$company, $site]) => 'payment-reminders',
    ];
    $itemIsVisible = fn (array $item) => $canOpenAccountingMenu($menuRouteKeys[$item['href']] ?? '');
    $navigationGroups = collect($navigationGroups)
        ->map(fn (array $group) => array_merge($group, ['items' => array_values(array_filter($group['items'], $itemIsVisible))]))
        ->filter(fn (array $group) => $group['items'] !== [])
        ->values()
        ->all();
    $salesItems = array_values(array_filter($salesItems, $itemIsVisible));
    $expenseItems = array_values(array_filter($expenseItems, $itemIsVisible));
    $otherItems = array_values(array_filter($otherItems, $itemIsVisible));
    $salesIsOpen = collect($salesItems)->contains(fn ($item) => $item['active'] ?? false);
    $expensesIsOpen = collect($expenseItems)->contains(fn ($item) => $item['active'] ?? false);
@endphp

<aside class="dashboard-sidebar accounting-sidebar">
    <a class="sidebar-brand" href="{{ $moduleRoute }}" aria-label="{{ app_brand_name() }}">
        <span class="sidebar-logo">
            <img src="{{ app_brand_logo_url() }}" alt="{{ app_brand_name() }}">
        </span>
        <span>
            <strong>{{ app_brand_short_name() }}</strong>
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
        @if ($canOpenAccountingMenu('dashboard'))
            <a class="nav-link {{ $activeAccountingPage === 'dashboard' ? 'active' : '' }}" href="{{ $moduleRoute }}">
                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                {{ __('main.dashboard') }}
            </a>
        @endif

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

        @if ($canOpenAccountingMenu('currencies'))
            <a class="nav-link {{ $activeAccountingPage === 'currencies' ? 'active' : '' }}" href="{{ route('main.accounting.currencies', [$company, $site]) }}">
                <i class="bi bi-currency-exchange" aria-hidden="true"></i>
                {{ __('main.currencies') }}
            </a>
        @endif
        @if ($canOpenAccountingMenu('payment-methods'))
            <a class="nav-link {{ $activeAccountingPage === 'payment-methods' ? 'active' : '' }}" href="{{ route('main.accounting.payment-methods', [$company, $site]) }}">
                <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                {{ __('main.payment_methods') }}
            </a>
        @endif

        <span class="sidebar-section-title">{{ __('main.billing') }}</span>

        <div class="sidebar-group {{ $salesIsOpen ? 'open' : '' }}">
            <button class="sidebar-group-toggle" type="button" title="{{ __('main.sales') }}" aria-expanded="{{ $salesIsOpen ? 'true' : 'false' }}" data-accounting-submenu>
                <i class="bi bi-cart-check" aria-hidden="true"></i>
                <span>{{ __('main.sales') }}</span>
                <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
            </button>
            <div class="sidebar-subnav">
                @foreach ($salesItems as $item)
                    <a href="{{ $item['href'] ?? '#' }}" title="{{ $item['label'] }}" class="{{ ($item['active'] ?? false) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="sidebar-group {{ $expensesIsOpen ? 'open' : '' }}">
            <button class="sidebar-group-toggle" type="button" title="{{ __('main.expenses_group') }}" aria-expanded="{{ $expensesIsOpen ? 'true' : 'false' }}" data-accounting-submenu>
                <i class="bi bi-cash-stack" aria-hidden="true"></i>
                <span>{{ __('main.expenses_group') }}</span>
                <i class="bi bi-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
            </button>
            <div class="sidebar-subnav">
                @foreach ($expenseItems as $item)
                    <a href="{{ $item['href'] ?? '#' }}" title="{{ $item['label'] }}" class="{{ ($item['active'] ?? false) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <span class="sidebar-section-title">{{ __('main.other') }}</span>

        @foreach ($otherItems as $item)
            <a class="nav-link {{ ($item['active'] ?? false) ? 'active' : '' }}" href="{{ $item['href'] ?? '#' }}">
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                {{ $item['label'] }}
            </a>
        @endforeach

        @if ($canOpenAccountingMenu('tasks'))
            <a class="nav-link {{ $activeAccountingPage === 'tasks' ? 'active' : '' }}" href="{{ route('main.accounting.tasks', [$company, $site]) }}">
                <i class="bi bi-check2-square" aria-hidden="true"></i>
                {{ __('main.tasks') }}
            </a>
        @endif

        @if ($canOpenAccountingMenu('reports'))
            <a class="nav-link {{ $activeAccountingPage === 'reports' ? 'active' : '' }}" href="{{ route('main.accounting.reports', [$company, $site]) }}">
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                {{ __('main.reports') }}
            </a>
        @endif

        @if ($canManageAccountingSettings)
            <a class="nav-link {{ $activeAccountingPage === 'settings' ? 'active' : '' }}" href="{{ route('main.accounting.settings', [$company, $site]) }}">
                <i class="bi bi-sliders" aria-hidden="true"></i>
                {{ __('main.module_settings') }}
            </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
        <span>{{ $site->name }}</span>
    </div>
</aside>

<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySite;
use Illuminate\Http\Request;

class AccountingModuleNavigation
{
    public const GROUPS = [
        'overview' => ['dashboard'],
        'contacts' => ['prospects', 'clients', 'suppliers', 'creditors', 'debtors', 'partners', 'sales-representatives'],
        'stock' => ['stock-items', 'stock-categories', 'stock-subcategories', 'stock-warehouses', 'stock-movements', 'stock-inventories', 'stock-alerts', 'stock-units', 'stock-batches', 'stock-transfers'],
        'services' => ['service-price-list', 'service-categories', 'service-subcategories', 'service-units', 'service-recurring'],
        'setup' => ['currencies', 'payment-methods', 'taxes'],
        'sales' => ['proforma-invoices', 'customer-orders', 'delivery-notes', 'sales-invoices', 'credit-notes', 'receipts', 'cash-register', 'other-incomes'],
        'expenses' => ['purchases', 'purchase-orders', 'expenses'],
        'monitoring' => ['debts', 'receivables', 'treasury', 'bank-reconciliations', 'payment-reminders', 'tasks', 'reports'],
    ];

    public static function keys(): array
    {
        return array_values(array_merge(...array_values(self::GROUPS)));
    }

    public static function keyForRequest(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();

        if ($routeName === 'main.companies.sites.modules.show'
            && $request->route('module') === 'accounting') {
            return 'dashboard';
        }

        if (! str_starts_with($routeName, 'main.accounting.')) {
            return null;
        }

        $suffix = substr($routeName, strlen('main.accounting.'));

        if (str_starts_with($suffix, 'settings')) {
            return 'settings';
        }

        if (str_starts_with($suffix, 'stock.')) {
            return 'stock-'.(string) $request->route('resource');
        }

        if (str_starts_with($suffix, 'services.')) {
            return 'service-'.(string) $request->route('resource');
        }

        foreach (self::keys() as $key) {
            if ($suffix === $key || str_starts_with($suffix, $key.'.')) {
                return $key;
            }
        }

        return null;
    }

    public static function urlForKey(string $key, Company $company, CompanySite $site): ?string
    {
        if ($key === 'dashboard') {
            return route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_ACCOUNTING]);
        }

        if (str_starts_with($key, 'stock-')) {
            return route('main.accounting.stock.index', [$company, $site, substr($key, strlen('stock-'))]);
        }

        if (str_starts_with($key, 'service-')) {
            return route('main.accounting.services.index', [$company, $site, substr($key, strlen('service-'))]);
        }

        if (! in_array($key, self::keys(), true)) {
            return null;
        }

        return route('main.accounting.'.$key, [$company, $site]);
    }
}

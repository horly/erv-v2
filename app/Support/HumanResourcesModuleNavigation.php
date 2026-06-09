<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySite;
use Illuminate\Http\Request;

class HumanResourcesModuleNavigation
{
    public const GROUPS = [
        'people' => ['hr-dashboard', 'hr-employees', 'hr-departments', 'hr-documents', 'hr-attendance'],
        'administration' => ['hr-contracts', 'hr-leave', 'hr-payroll', 'hr-salary-advances', 'hr-payroll-adjustments'],
        'development' => ['hr-schedules', 'hr-evaluations', 'hr-trainings'],
        'governance' => ['hr-sanctions', 'hr-recruitment', 'hr-reports'],
    ];

    public const RESOURCE_KEYS = [
        'documents' => 'hr-documents',
        'salary-advances' => 'hr-salary-advances',
        'payroll-adjustments' => 'hr-payroll-adjustments',
        'schedules' => 'hr-schedules',
        'evaluations' => 'hr-evaluations',
        'trainings' => 'hr-trainings',
        'sanctions' => 'hr-sanctions',
        'recruitment' => 'hr-recruitment',
    ];

    public static function keys(): array
    {
        return array_values(array_merge(...array_values(self::GROUPS)));
    }

    public static function keyForRequest(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();

        if (! str_starts_with($routeName, 'main.human-resources.')) {
            return null;
        }

        $suffix = substr($routeName, strlen('main.human-resources.'));

        if (str_starts_with($suffix, 'settings')) {
            return 'hr-settings';
        }

        if (str_starts_with($suffix, 'resources')) {
            $resource = (string) $request->route('hrResource');

            return self::RESOURCE_KEYS[$resource] ?? null;
        }

        foreach ([
            'dashboard' => 'hr-dashboard',
            'employees' => 'hr-employees',
            'departments' => 'hr-departments',
            'attendance' => 'hr-attendance',
            'contracts' => 'hr-contracts',
            'leave' => 'hr-leave',
            'payroll' => 'hr-payroll',
            'reports' => 'hr-reports',
        ] as $routePrefix => $key) {
            if ($suffix === $routePrefix || str_starts_with($suffix, $routePrefix.'.')) {
                return $key;
            }
        }

        return null;
    }

    public static function urlForKey(string $key, Company $company, CompanySite $site): ?string
    {
        return match ($key) {
            'hr-dashboard' => route('main.human-resources.dashboard', [$company, $site]),
            'hr-employees' => route('main.human-resources.employees', [$company, $site]),
            'hr-departments' => route('main.human-resources.departments', [$company, $site]),
            'hr-attendance' => route('main.human-resources.attendance', [$company, $site]),
            'hr-contracts' => route('main.human-resources.contracts', [$company, $site]),
            'hr-leave' => route('main.human-resources.leave', [$company, $site]),
            'hr-payroll' => route('main.human-resources.payroll', [$company, $site]),
            'hr-reports' => route('main.human-resources.reports', [$company, $site]),
            default => self::resourceUrlForKey($key, $company, $site),
        };
    }

    private static function resourceUrlForKey(string $key, Company $company, CompanySite $site): ?string
    {
        $resource = array_search($key, self::RESOURCE_KEYS, true);

        return $resource === false
            ? null
            : route('main.human-resources.resources', [$company, $site, $resource]);
    }
}

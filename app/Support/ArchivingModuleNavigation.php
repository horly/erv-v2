<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySite;
use Illuminate\Http\Request;

class ArchivingModuleNavigation
{
    public const GROUPS = [
        'physical' => ['archive-dashboard', 'archive-locations', 'archive-containers'],
        'records' => ['archive-records', 'archive-movements', 'archive-retention'],
        'governance' => ['archive-traceability', 'archive-reports'],
    ];

    public static function keys(): array
    {
        return array_values(array_merge(...array_values(self::GROUPS)));
    }

    public static function keyForRequest(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();

        if (! str_starts_with($routeName, 'main.archiving.')) {
            return null;
        }

        $suffix = substr($routeName, strlen('main.archiving.'));

        if (str_starts_with($suffix, 'settings')) {
            return 'archive-settings';
        }

        foreach ([
            'dashboard' => 'archive-dashboard',
            'locations' => 'archive-locations',
            'containers' => 'archive-containers',
            'records' => 'archive-records',
            'movements' => 'archive-movements',
            'retention' => 'archive-retention',
            'traceability' => 'archive-traceability',
            'reports' => 'archive-reports',
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
            'archive-dashboard' => route('main.archiving.dashboard', [$company, $site]),
            'archive-locations' => route('main.archiving.locations', [$company, $site]),
            'archive-containers' => route('main.archiving.containers', [$company, $site]),
            'archive-records' => route('main.archiving.records', [$company, $site]),
            'archive-movements' => route('main.archiving.movements', [$company, $site]),
            'archive-retention' => route('main.archiving.retention', [$company, $site]),
            'archive-traceability' => route('main.archiving.traceability', [$company, $site]),
            'archive-reports' => route('main.archiving.reports', [$company, $site]),
            default => null,
        };
    }
}

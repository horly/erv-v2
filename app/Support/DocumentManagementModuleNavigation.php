<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySite;
use Illuminate\Http\Request;

class DocumentManagementModuleNavigation
{
    public const GROUPS = [
        'registry_office' => ['ged-dashboard', 'ged-incoming', 'ged-outgoing', 'ged-internal'],
        'processing' => ['ged-assignments', 'ged-validation', 'ged-history'],
        'classification' => ['ged-folders'],
        'reports' => ['ged-reports'],
    ];

    public static function keys(): array
    {
        return array_values(array_merge(...array_values(self::GROUPS)));
    }

    public static function keyForRequest(Request $request): ?string
    {
        $routeName = (string) optional($request->route())->getName();

        if (! str_starts_with($routeName, 'main.document-management.')) {
            return null;
        }

        $suffix = substr($routeName, strlen('main.document-management.'));

        if (str_starts_with($suffix, 'settings')) {
            return 'ged-settings';
        }

        foreach ([
            'dashboard' => 'ged-dashboard',
            'incoming' => 'ged-incoming',
            'outgoing' => 'ged-outgoing',
            'internal' => 'ged-internal',
            'folders' => 'ged-folders',
            'assignments' => 'ged-assignments',
            'traceability' => 'ged-history',
            'validation-circuits' => 'ged-validation',
            'validation-requests' => 'ged-validation',
            'reports' => 'ged-reports',
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
            'ged-dashboard' => route('main.document-management.dashboard', [$company, $site]),
            'ged-incoming' => route('main.document-management.incoming', [$company, $site]),
            'ged-outgoing' => route('main.document-management.outgoing', [$company, $site]),
            'ged-internal' => route('main.document-management.internal', [$company, $site]),
            'ged-folders' => route('main.document-management.folders', [$company, $site]),
            'ged-assignments' => route('main.document-management.assignments', [$company, $site]),
            'ged-history' => route('main.document-management.traceability', [$company, $site]),
            'ged-validation' => route('main.document-management.validation-circuits', [$company, $site]),
            'ged-reports' => route('main.document-management.reports', [$company, $site]),
            default => null,
        };
    }
}

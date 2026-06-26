<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySite;
use Illuminate\Http\Request;

class GmaoModuleNavigation
{
    public static function keys(): array
    {
        return [
            'gmao-dashboard',
            'gmao-equipment',
            'gmao-equipment-categories',
            'gmao-locations',
            'gmao-requests',
            'gmao-work-orders',
            'gmao-preventive',
            'gmao-maintenance-routes',
            'gmao-technicians',
            'gmao-spare-parts',
            'gmao-traceability',
            'gmao-reports',
            'gmao-settings',
        ];
    }

    public static function keyForRequest(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        return match ($routeName) {
            'main.gmao.dashboard' => 'gmao-dashboard',
            'main.gmao.equipment' => 'gmao-equipment',
            'main.gmao.equipment-categories', 'main.gmao.equipment-categories.store', 'main.gmao.equipment-categories.update', 'main.gmao.equipment-categories.destroy' => 'gmao-equipment-categories',
            'main.gmao.locations', 'main.gmao.locations.store', 'main.gmao.locations.update', 'main.gmao.locations.destroy' => 'gmao-locations',
            'main.gmao.requests' => 'gmao-requests',
            'main.gmao.work-orders' => 'gmao-work-orders',
            'main.gmao.preventive' => 'gmao-preventive',
            'main.gmao.maintenance-routes', 'main.gmao.maintenance-routes.store', 'main.gmao.maintenance-routes.update', 'main.gmao.maintenance-routes.destroy' => 'gmao-maintenance-routes',
            'main.gmao.technicians' => 'gmao-technicians',
            'main.gmao.spare-parts' => 'gmao-spare-parts',
            'main.gmao.traceability' => 'gmao-traceability',
            'main.gmao.reports', 'main.gmao.reports.pdf' => 'gmao-reports',
            'main.gmao.settings', 'main.gmao.settings.update' => 'gmao-settings',
            default => null,
        };
    }

    public static function urlForKey(string $key, Company $company, CompanySite $site): ?string
    {
        return match ($key) {
            'gmao-dashboard' => route('main.gmao.dashboard', [$company, $site]),
            'gmao-equipment' => route('main.gmao.equipment', [$company, $site]),
            'gmao-equipment-categories' => route('main.gmao.equipment-categories', [$company, $site]),
            'gmao-locations' => route('main.gmao.locations', [$company, $site]),
            'gmao-requests' => route('main.gmao.requests', [$company, $site]),
            'gmao-work-orders' => route('main.gmao.work-orders', [$company, $site]),
            'gmao-preventive' => route('main.gmao.preventive', [$company, $site]),
            'gmao-maintenance-routes' => route('main.gmao.maintenance-routes', [$company, $site]),
            'gmao-technicians' => route('main.gmao.technicians', [$company, $site]),
            'gmao-spare-parts' => route('main.gmao.spare-parts', [$company, $site]),
            'gmao-traceability' => route('main.gmao.traceability', [$company, $site]),
            'gmao-reports' => route('main.gmao.reports', [$company, $site]),
            'gmao-settings' => route('main.gmao.settings', [$company, $site]),
            default => null,
        };
    }
}

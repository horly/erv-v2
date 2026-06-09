<?php

namespace App\Http\Middleware;

use App\Models\AccountingMenuPermission;
use App\Models\Company;
use App\Models\CompanySite;
use App\Support\AccountingModuleNavigation;
use App\Support\ArchivingModuleNavigation;
use App\Support\DocumentManagementModuleNavigation;
use App\Support\HumanResourcesModuleNavigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountingMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $menuKey = AccountingModuleNavigation::keyForRequest($request);

        if ($menuKey !== null) {
            return $this->handleModuleMenu(
                $request,
                $next,
                $menuKey,
                AccountingModuleNavigation::keys(),
                fn (string $key, Company $company, CompanySite $site): ?string => AccountingModuleNavigation::urlForKey($key, $company, $site),
                'settings',
                'accounting_visible_menu_keys',
                'can_manage_accounting_settings',
            );
        }

        $menuKey = HumanResourcesModuleNavigation::keyForRequest($request);

        if ($menuKey !== null) {
            return $this->handleModuleMenu(
                $request,
                $next,
                $menuKey,
                HumanResourcesModuleNavigation::keys(),
                fn (string $key, Company $company, CompanySite $site): ?string => HumanResourcesModuleNavigation::urlForKey($key, $company, $site),
                'hr-settings',
                'human_resources_visible_menu_keys',
                'can_manage_human_resources_settings',
            );
        }

        $menuKey = DocumentManagementModuleNavigation::keyForRequest($request);

        if ($menuKey === null) {
            $menuKey = ArchivingModuleNavigation::keyForRequest($request);

            if ($menuKey === null) {
                return $next($request);
            }

            return $this->handleModuleMenu(
                $request,
                $next,
                $menuKey,
                ArchivingModuleNavigation::keys(),
                fn (string $key, Company $company, CompanySite $site): ?string => ArchivingModuleNavigation::urlForKey($key, $company, $site),
                'archive-settings',
                'archiving_visible_menu_keys',
                'can_manage_archiving_settings',
            );
        }

        return $this->handleModuleMenu(
            $request,
            $next,
            $menuKey,
            DocumentManagementModuleNavigation::keys(),
            fn (string $key, Company $company, CompanySite $site): ?string => DocumentManagementModuleNavigation::urlForKey($key, $company, $site),
            'ged-settings',
            'document_management_visible_menu_keys',
            'can_manage_document_management_settings',
        );
    }

    /**
     * @param  array<int, string>  $allMenuKeys
     */
    private function handleModuleMenu(
        Request $request,
        Closure $next,
        string $menuKey,
        array $allMenuKeys,
        callable $urlForKey,
        string $settingsKey,
        string $visibleAttribute,
        string $manageAttribute,
    ): Response {

        $user = $request->user();
        $site = $request->route('site');
        $canManageSettings = $user && ($user->isAdmin() || $user->isSuperadmin());

        if ($menuKey === $settingsKey) {
            abort_unless($canManageSettings, Response::HTTP_FORBIDDEN);
        }

        if (! $user || ! $site instanceof CompanySite) {
            return $next($request);
        }

        $visibleMenuKeys = $allMenuKeys;

        if (! $canManageSettings) {
            $storedPermissions = AccountingMenuPermission::query()
                ->where('company_site_id', $site->id)
                ->where('user_id', $user->id)
                ->whereIn('menu_key', $allMenuKeys)
                ->get(['menu_key', 'is_allowed']);

            if ($storedPermissions->isNotEmpty()) {
                $visibleMenuKeys = $storedPermissions
                    ->where('is_allowed', true)
                    ->pluck('menu_key')
                    ->all();
            }

            if (! in_array($menuKey, $visibleMenuKeys, true)) {
                if (! $request->isMethod('GET')) {
                    abort(Response::HTTP_FORBIDDEN);
                }

                $company = $request->route('company');
                $fallbackKey = collect($allMenuKeys)
                    ->first(fn (string $key): bool => in_array($key, $visibleMenuKeys, true));

                if ($company instanceof Company && $fallbackKey) {
                    $fallbackUrl = $urlForKey($fallbackKey, $company, $site);

                    if ($fallbackUrl) {
                        return redirect($fallbackUrl)
                            ->with('success', __('main.accounting_access_changed_redirected'))
                            ->with('toast_type', 'danger');
                    }
                }

                return redirect()
                    ->route('main')
                    ->with('success', __('main.accounting_access_changed_no_menu'))
                    ->with('toast_type', 'danger');
            }
        }

        $request->attributes->set($visibleAttribute, $visibleMenuKeys);
        $request->attributes->set($manageAttribute, $canManageSettings);

        return $next($request);
    }
}

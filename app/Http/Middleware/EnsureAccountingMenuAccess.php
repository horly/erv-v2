<?php

namespace App\Http\Middleware;

use App\Models\AccountingMenuPermission;
use App\Models\Company;
use App\Models\CompanySite;
use App\Support\AccountingModuleNavigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountingMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $menuKey = AccountingModuleNavigation::keyForRequest($request);

        if ($menuKey === null) {
            return $next($request);
        }

        $user = $request->user();
        $site = $request->route('site');
        $canManageSettings = $user && ($user->isAdmin() || $user->isSuperadmin());

        if ($menuKey === 'settings') {
            abort_unless($canManageSettings, Response::HTTP_FORBIDDEN);
        }

        if (! $user || ! $site instanceof CompanySite) {
            return $next($request);
        }

        $visibleMenuKeys = AccountingModuleNavigation::keys();

        if (! $canManageSettings) {
            $storedPermissions = AccountingMenuPermission::query()
                ->where('company_site_id', $site->id)
                ->where('user_id', $user->id)
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
                $fallbackKey = collect(AccountingModuleNavigation::keys())
                    ->first(fn (string $key): bool => in_array($key, $visibleMenuKeys, true));

                if ($company instanceof Company && $fallbackKey) {
                    $fallbackUrl = AccountingModuleNavigation::urlForKey($fallbackKey, $company, $site);

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

        $request->attributes->set('accounting_visible_menu_keys', $visibleMenuKeys);
        $request->attributes->set('can_manage_accounting_settings', $canManageSettings);

        return $next($request);
    }
}

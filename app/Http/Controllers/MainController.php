<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\User;
use App\Support\CurrencyCatalog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class MainController extends Controller
{
    public function root(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function index(): View|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($user->isSuperadmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isUser()) {
            $site = $this->firstAssignedSite($user);

            if (! $site?->company) {
                return view('main.pending-access', ['user' => $user]);
            }

            return redirect()->route('main.companies.sites.show', [$site->company, $site]);
        }

        $companies = match (true) {
            $user->isAdmin() => Company::query()
                ->where('subscription_id', $user->subscription_id)
                ->withCount('sites')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            default => $user->companies()
                ->withCount('sites')
                ->latest('companies.created_at')
                ->paginate(5)
                ->withQueryString(),
        };

        return view('main.main', [
            'user' => $user,
            'companies' => $companies,
            'countries' => config('countries'),
        ]);
    }

    public function companySites(Company $company): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company)) {
            return $this->redirectMainArea($user);
        }

        $company->load('subscription')->loadCount('sites');

        return view('main.company-sites', [
            'user' => $user,
            'company' => $company,
            'sites' => $company->sites()
                ->with('responsible')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'responsibles' => $this->siteResponsibleOptions($company),
            'siteTypes' => CompanySite::types(),
            'siteModules' => CompanySite::modules(),
            'moduleLabels' => $this->siteModuleLabels(),
            'typeLabels' => $this->siteTypeLabels(),
            'planRules' => $this->sitePlanRules($company),
            'currencies' => CurrencyCatalog::sorted(),
        ]);
    }

    public function showCompanySite(Company $company, CompanySite $site): View|RedirectResponse
    {
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $this->canAccessCompanySite($user, $company, $site)) {
            return $this->redirectMainArea($user);
        }

        $company->load('subscription');
        $site->load('responsible');

        return view('main.company-site-show', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'modules' => $this->availableSiteModulesForUser($user, $site),
            'moduleLabels' => $this->siteModuleLabels(),
            'typeLabels' => $this->siteTypeLabels(),
            'planRules' => $this->sitePlanRules($company),
        ]);
    }

    public function showSiteModule(Company $company, CompanySite $site, string $module): View|RedirectResponse
    {
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $this->canAccessCompanySite($user, $company, $site)) {
            return $this->redirectMainArea($user);
        }

        $availableModules = $this->availableSiteModulesForUser($user, $site);

        if (! in_array($module, $availableModules, true)) {
            return redirect()->route('main.companies.sites.show', [$company, $site]);
        }

        $company->load('subscription');
        $site->load('responsible');

        $moduleMeta = $this->siteModuleMeta()[$module] ?? null;

        if (! $moduleMeta) {
            return redirect()->route('main.companies.sites.show', [$company, $site]);
        }

        $view = $module === CompanySite::MODULE_ACCOUNTING
            ? 'main.modules.accounting-dashboard'
            : 'main.modules.under-development';

        return view($view, [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => $module,
            'moduleMeta' => $moduleMeta,
            'planRules' => $this->sitePlanRules($company),
        ]);
    }

    public function users(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() || ! $user->subscription_id) {
            return $this->redirectMainArea($user);
        }

        $siteOptions = CompanySite::query()
            ->with('company')
            ->whereHas('company', fn ($query) => $query->where('subscription_id', $user->subscription_id))
            ->orderBy('name')
            ->get();

        return view('main.users', [
            'user' => $user,
            'users' => User::query()
                ->with(['sites.company'])
                ->where('subscription_id', $user->subscription_id)
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
                ->orderByRaw('case when id = ? then 0 else 1 end', [$user->id])
                ->orderByDesc('id')
                ->paginate(5)
                ->withQueryString(),
            'siteOptions' => $siteOptions,
            'moduleLabels' => $this->siteModuleLabels(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $admin = Auth::user();

        if (! $admin->isAdmin() || ! $admin->subscription_id) {
            return redirect()->route('main');
        }

        $isManagedAdmin = $request->input('role') === User::ROLE_ADMIN;
        $site = $isManagedAdmin ? null : $this->validatedAssignableSite($request, $admin);
        $validated = $request->validate($this->managedUserRules($site));

        if ($site) {
            $this->ensureUserModulesMatchSite($validated['modules'], $site);
        }

        DB::transaction(function () use ($validated, $admin, $site): void {
            $account = User::create($this->managedUserPayload($validated, $admin));

            if ($account->isAdmin()) {
                $this->syncManagedAdminSites($account, $admin);

                return;
            }

            $this->syncManagedUserSite($account, $site, $validated);
        });

        return redirect()
            ->route('main.users')
            ->with('success', __('admin.user_saved'));
    }

    public function updateUser(Request $request, User $account): RedirectResponse
    {
        $admin = Auth::user();

        if (! $admin->isAdmin() || ! $admin->subscription_id || ! $this->canManageSubscriptionUser($admin, $account) || $admin->is($account)) {
            return redirect()->route('main');
        }

        $isManagedAdmin = $request->input('role') === User::ROLE_ADMIN;
        $site = $isManagedAdmin ? null : $this->validatedAssignableSite($request, $admin);
        $validated = $request->validate($this->managedUserRules($site, $account));

        if ($site) {
            $this->ensureUserModulesMatchSite($validated['modules'], $site);
        }

        DB::transaction(function () use ($validated, $admin, $site, $account): void {
            $payload = $this->managedUserPayload($validated, $admin);

            if (blank($payload['password'] ?? null)) {
                unset($payload['password']);
            }

            $account->update($payload);

            if ($account->isAdmin()) {
                $this->syncManagedAdminSites($account, $admin);

                return;
            }

            $this->syncManagedUserSite($account, $site, $validated);
        });

        return redirect()
            ->route('main.users')
            ->with('success', __('admin.user_updated'));
    }

    public function destroyUser(User $account): RedirectResponse
    {
        $admin = Auth::user();

        if (! $admin->isAdmin() || ! $admin->subscription_id || ! $this->canManageSubscriptionUser($admin, $account) || $admin->is($account)) {
            return redirect()
                ->route('main.users')
                ->withErrors(['authorization' => __('auth.unauthorized')]);
        }

        $account->delete();

        return redirect()
            ->route('main.users')
            ->with('success', __('admin.user_deleted'))
            ->with('toast_type', 'danger');
    }

    public function userLoginHistory(Request $request, User $account): JsonResponse|RedirectResponse
    {
        $admin = Auth::user();

        if (! $admin->isAdmin() || ! $admin->subscription_id || ! $this->canManageSubscriptionUser($admin, $account)) {
            return redirect()->route('main');
        }

        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'date');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $sortColumn = match ($sort) {
            'device' => 'device',
            'ip' => 'ip_address',
            default => 'logged_in_at',
        };

        $histories = $account->loginHistories()
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('device', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('logged_in_at', 'like', "%{$search}%");
            }))
            ->orderBy($sortColumn, $direction)
            ->paginate(5)
            ->withQueryString();

        return response()->json([
            'user' => [
                'name' => $account->name,
                'email' => $account->email,
            ],
            'data' => $histories->getCollection()->map(fn ($history) => [
                'device' => $history->device ?: __('main.unknown_device'),
                'ip' => $history->ip_address ?: '-',
                'date' => $history->logged_in_at?->format('Y-m-d H:i:s') ?? '-',
            ])->values(),
            'meta' => [
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage(),
                'from' => $histories->firstItem(),
                'to' => $histories->lastItem(),
                'total' => $histories->total(),
            ],
        ]);
    }

    public function storeCompanySite(Request $request, Company $company): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company)) {
            return redirect()->route('main');
        }

        $company->load('subscription')->loadCount('sites');
        $rules = $this->sitePlanRules($company);

        if ($rules['site_limit'] !== null && $company->sites_count >= $rules['site_limit']) {
            throw ValidationException::withMessages(['site' => __('main.site_limit_reached')]);
        }

        $validated = $request->validate($this->siteRules($company));
        $this->ensureSiteModulesMatchPlan($validated['modules'], $rules);

        DB::transaction(function () use ($company, $validated): void {
            $site = $company->sites()->create($this->sitePayload($validated));
            $this->syncSiteUsers($site, $validated);
        });

        return redirect()
            ->route('main.companies.sites', $company)
            ->with('success', __('main.site_saved'));
    }

    public function updateCompanySite(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company) || $site->company_id !== $company->id) {
            return redirect()->route('main');
        }

        $company->load('subscription');
        $rules = $this->sitePlanRules($company);
        $validated = $request->validate($this->siteRules($company, $site));
        $this->ensureSiteModulesMatchPlan($validated['modules'], $rules);

        DB::transaction(function () use ($site, $validated): void {
            $site->update($this->sitePayload($validated));
            $this->syncSiteUsers($site, $validated);
        });

        return redirect()
            ->route('main.companies.sites', $company)
            ->with('success', __('main.site_updated'));
    }

    public function destroyCompanySite(Company $company, CompanySite $site): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company) || $site->company_id !== $company->id) {
            return redirect()->route('main');
        }

        $site->delete();

        return redirect()
            ->route('main.companies.sites', $company)
            ->with('success', __('main.site_deleted'))
            ->with('toast_type', 'danger');
    }

    public function createCompany(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() || ! $user->subscription_id) {
            return redirect()->route('main');
        }

        return $this->companyForm($user);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin() || ! $user->subscription_id) {
            return redirect()->route('main');
        }

        $validated = $request->validate($this->companyRules());

        DB::transaction(function () use ($request, $validated, $user): void {
            $logoPath = $request->file('logo')?->store('company-logos', 'public');

            $company = Company::create($this->companyPayload($validated, $user, $logoPath));

            $this->syncCompanyChildren($company, $validated);
            $company->users()->syncWithoutDetaching([
                $user->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
            ]);
        });

        return redirect()->route('main')->with('success', __('admin.company_saved'));
    }

    public function editCompany(Company $company): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company)) {
            return redirect()->route('main');
        }

        $company->load(['phones', 'accounts']);

        return $this->companyForm($user, $company);
    }

    public function updateCompany(Request $request, Company $company): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company)) {
            return redirect()->route('main');
        }

        $validated = $request->validate($this->companyRules());

        DB::transaction(function () use ($request, $validated, $user, $company): void {
            $logoPath = $company->logo;

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('company-logos', 'public');

                if ($company->logo && ! Str::startsWith($company->logo, ['http://', 'https://'])) {
                    Storage::disk('public')->delete($company->logo);
                }
            }

            $company->update($this->companyPayload($validated, $user, $logoPath));
            $company->phones()->delete();
            $company->accounts()->delete();
            $this->syncCompanyChildren($company, $validated);
            $company->users()->syncWithoutDetaching([
                $user->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
            ]);
        });

        return redirect()->route('main')->with('success', __('admin.company_updated'));
    }

    public function destroyCompany(Company $company): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->canManageCompanyRecord($user, $company)) {
            return redirect()->route('main');
        }

        if ($company->sites()->exists()) {
            return redirect()->route('main')->withErrors(['company' => __('admin.company_has_sites')]);
        }

        DB::transaction(function () use ($company): void {
            if ($company->logo && ! Str::startsWith($company->logo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($company->logo);
            }

            $company->delete();
        });

        return redirect()->route('main')->with('success', __('admin.company_deleted'))->with('toast_type', 'danger');
    }

    private function companyForm(User&Authenticatable $user, ?Company $company = null): View
    {
        return view('main.company-form', [
            'user' => $user,
            'company' => $company,
            'countries' => config('countries'),
            'currencies' => CurrencyCatalog::sorted(),
        ]);
    }

    private function canManageCompanyRecord(User&Authenticatable $user, Company $company): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdmin()
            && $user->subscription_id !== null
            && $company->subscription_id === $user->subscription_id;
    }

    private function canAccessCompanySite(User&Authenticatable $user, Company $company, CompanySite $site): bool
    {
        if ($this->canManageCompanyRecord($user, $company)) {
            return true;
        }

        return $user->isUser()
            && $user->sites()
                ->whereKey($site->getKey())
                ->exists();
    }

    private function redirectMainArea(User&Authenticatable $user): RedirectResponse
    {
        if ($user->isUser()) {
            $site = $this->firstAssignedSite($user);

            if ($site?->company) {
                return redirect()->route('main.companies.sites.show', [$site->company, $site]);
            }
        }

        return redirect()->route('main');
    }

    private function firstAssignedSite(User&Authenticatable $user): ?CompanySite
    {
        if (! $user->isUser()) {
            return null;
        }

        return $user->sites()
            ->with('company.subscription')
            ->orderBy('company_sites.id')
            ->first();
    }

    private function availableSiteModulesForUser(User&Authenticatable $user, CompanySite $site): array
    {
        if ($user->isAdmin() || $user->isSuperadmin()) {
            return array_values($site->modules ?? []);
        }

        $assignedSite = $user->sites()
            ->whereKey($site->getKey())
            ->first();

        if (! $assignedSite) {
            return [];
        }

        $permissions = json_decode((string) $assignedSite->pivot->module_permissions, true);

        if (is_array($permissions) && $permissions !== []) {
            return array_values(array_intersect(array_keys($permissions), $site->modules ?? []));
        }

        return array_values($site->modules ?? []);
    }

    private function canManageSubscriptionUser(User&Authenticatable $admin, User $account): bool
    {
        return ! $account->isSuperadmin()
            && $account->subscription_id !== null
            && $account->subscription_id === $admin->subscription_id;
    }

    private function validatedAssignableSite(Request $request, User&Authenticatable $admin): CompanySite
    {
        $siteId = $request->input('site_id');

        $site = CompanySite::query()
            ->with('company')
            ->whereKey($siteId)
            ->whereHas('company', fn ($query) => $query->where('subscription_id', $admin->subscription_id))
            ->first();

        if (! $site) {
            throw ValidationException::withMessages(['site_id' => __('main.required_user_site')]);
        }

        return $site;
    }

    private function managedUserRules(?CompanySite $site = null, ?User $account = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($account ? ','.$account->id : '')],
            'password' => [
                $account ? 'nullable' : 'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'site_id' => array_filter([$site ? 'required' : 'nullable', 'integer', $site ? Rule::in([$site->id]) : null]),
            'modules' => [$site ? 'required' : 'nullable', 'array', $site ? 'min:1' : 'min:0'],
            'modules.*' => ['required', 'string', Rule::in($site?->modules ?? CompanySite::modules())],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*.can_create' => ['nullable', 'boolean'],
            'module_permissions.*.can_update' => ['nullable', 'boolean'],
            'module_permissions.*.can_delete' => ['nullable', 'boolean'],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    private function managedUserPayload(array $validated, User&Authenticatable $admin): array
    {
        return [
            'subscription_id' => $admin->subscription_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'role' => $validated['role'],
            'phone_number' => $validated['phone_number'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'address' => $validated['address'] ?? null,
        ];
    }

    private function ensureUserModulesMatchSite(array $modules, CompanySite $site): void
    {
        if (array_diff(array_unique($modules), $site->modules ?? []) !== []) {
            throw ValidationException::withMessages(['modules' => __('main.module_not_allowed_for_site')]);
        }
    }

    private function syncManagedUserSite(User $account, CompanySite $site, array $validated): void
    {
        $modulePermissions = $this->modulePermissionPayload(
            $validated['modules'],
            $validated['module_permissions'] ?? [],
        );

        $canCreate = collect($modulePermissions)->contains(fn ($permissions) => $permissions['can_create']);
        $canUpdate = collect($modulePermissions)->contains(fn ($permissions) => $permissions['can_update']);
        $canDelete = collect($modulePermissions)->contains(fn ($permissions) => $permissions['can_delete']);

        $sitePivot = [
            'module_permissions' => json_encode($modulePermissions),
            'can_create' => $canCreate,
            'can_update' => $canUpdate,
            'can_delete' => $canDelete,
        ];

        $companyPivot = [
            'can_view' => true,
            'can_create' => $canCreate,
            'can_update' => $canUpdate,
            'can_delete' => $canDelete,
        ];

        $account->sites()->sync([$site->id => $sitePivot]);
        $account->companies()->sync([$site->company_id => $companyPivot]);
    }

    private function syncManagedAdminSites(User $account, User&Authenticatable $admin): void
    {
        $sites = CompanySite::query()
            ->with('company:id,subscription_id')
            ->whereHas('company', fn ($query) => $query->where('subscription_id', $admin->subscription_id))
            ->get();

        $sitePivots = [];
        $companyPivots = [];

        foreach ($sites as $site) {
            $modules = array_values($site->modules ?? []);
            $permissions = [];

            foreach ($modules as $module) {
                $permissions[$module] = [
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ];
            }

            $sitePivots[$site->id] = [
                'module_permissions' => json_encode($permissions),
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ];

            $companyPivots[$site->company_id] = [
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        $account->sites()->sync($sitePivots);
        $account->companies()->sync($companyPivots);
    }

    private function modulePermissionPayload(array $modules, array $permissions): array
    {
        $payload = [];

        foreach (array_unique($modules) as $module) {
            $modulePermissions = $permissions[$module] ?? [];

            $payload[$module] = [
                'can_create' => (bool) ($modulePermissions['can_create'] ?? false),
                'can_update' => (bool) ($modulePermissions['can_update'] ?? false),
                'can_delete' => (bool) ($modulePermissions['can_delete'] ?? false),
            ];
        }

        return $payload;
    }

    private function siteRules(Company $company, ?CompanySite $site = null): array
    {
        $userIds = $this->siteAssignableUsers($company, $site)->pluck('id')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(CompanySite::types())],
            'code' => ['nullable', 'string', 'max:100'],
            'responsible_id' => ['required', 'integer', Rule::in($userIds)],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', Rule::in(CompanySite::modules())],
            'email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
            'status' => ['required', 'string', Rule::in(CompanySite::statuses())],
        ];
    }

    private function sitePayload(array $validated): array
    {
        return [
            'responsible_id' => $validated['responsible_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'code' => $validated['code'] ?? null,
            'city' => $validated['city'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'modules' => array_values($validated['modules']),
            'currency' => $validated['currency'],
            'status' => $validated['status'],
        ];
    }

    private function syncSiteUsers(CompanySite $site, array $validated): void
    {
        $site->users()->sync([$validated['responsible_id']]);
    }

    private function siteAssignableUsers(Company $company, ?CompanySite $site = null)
    {
        return User::query()
            ->where('subscription_id', $company->subscription_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
            ->where(function ($query) use ($site): void {
                $query->where('role', User::ROLE_ADMIN)
                    ->orWhereDoesntHave('responsibleSites');

                if ($site) {
                    $query->orWhereHas('responsibleSites', fn ($siteQuery) => $siteQuery->whereKey($site->id));
                }
            })
            ->orderByRaw("case when role = 'admin' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function siteResponsibleOptions(Company $company)
    {
        return User::query()
            ->where('subscription_id', $company->subscription_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
            ->orderByRaw("case when role = 'admin' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function sitePlanRules(Company $company): array
    {
        return match ($company->subscription?->type ?? 'standard') {
            'business' => [
                'name' => 'BUSINESS',
                'site_limit' => null,
                'module_limit' => null,
                'allowed_modules' => CompanySite::modules(),
            ],
            'pro' => [
                'name' => 'PRO',
                'site_limit' => 2,
                'module_limit' => 2,
                'allowed_modules' => [
                    CompanySite::MODULE_ACCOUNTING,
                    CompanySite::MODULE_HUMAN_RESOURCES,
                ],
            ],
            default => [
                'name' => 'STANDARD',
                'site_limit' => 1,
                'module_limit' => 1,
                'allowed_modules' => [
                    CompanySite::MODULE_ACCOUNTING,
                ],
            ],
        };
    }

    private function ensureSiteModulesMatchPlan(array $modules, array $rules): void
    {
        $modules = array_unique($modules);

        if ($rules['module_limit'] !== null && count($modules) > $rules['module_limit']) {
            throw ValidationException::withMessages(['modules' => __('main.module_limit_reached')]);
        }

        if (array_diff($modules, $rules['allowed_modules']) !== []) {
            throw ValidationException::withMessages(['modules' => __('main.module_not_allowed')]);
        }
    }

    private function siteTypeLabels(): array
    {
        return [
            CompanySite::TYPE_PRODUCTION => __('main.site_type_production'),
            CompanySite::TYPE_WAREHOUSE => __('main.site_type_warehouse'),
            CompanySite::TYPE_OFFICE => __('main.site_type_office'),
            CompanySite::TYPE_SHOP => __('main.site_type_shop'),
            CompanySite::TYPE_ARCHIVE => __('main.site_type_archive'),
            CompanySite::TYPE_OTHER => __('main.site_type_other'),
        ];
    }

    private function siteModuleLabels(): array
    {
        return [
            CompanySite::MODULE_ACCOUNTING => __('main.module_accounting'),
            CompanySite::MODULE_HUMAN_RESOURCES => __('main.module_human_resources'),
            CompanySite::MODULE_ARCHIVING => __('main.module_archiving'),
            CompanySite::MODULE_DOCUMENT_MANAGEMENT => __('main.module_document_management'),
        ];
    }

    private function siteModuleMeta(): array
    {
        return [
            CompanySite::MODULE_ACCOUNTING => [
                'label' => __('main.module_accounting'),
                'description' => __('main.module_accounting_description'),
                'icon' => 'bi-receipt',
                'tone' => 'amber',
                'class' => 'module-accounting',
            ],
            CompanySite::MODULE_HUMAN_RESOURCES => [
                'label' => __('main.module_human_resources'),
                'description' => __('main.module_human_resources_description'),
                'icon' => 'bi-people',
                'tone' => 'violet',
                'class' => 'module-human-resources',
            ],
            CompanySite::MODULE_ARCHIVING => [
                'label' => __('main.module_archiving'),
                'description' => __('main.module_archiving_description'),
                'icon' => 'bi-archive',
                'tone' => 'amber',
                'class' => 'module-archiving',
            ],
            CompanySite::MODULE_DOCUMENT_MANAGEMENT => [
                'label' => __('main.module_document_management'),
                'description' => __('main.module_document_management_description'),
                'icon' => 'bi-file-earmark-text',
                'tone' => 'green',
                'class' => 'module-document-management',
            ],
        ];
    }

    private function companyRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'rccm' => ['nullable', 'string', 'max:255'],
            'id_nat' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'phones' => ['nullable', 'array'],
            'phones.*.label' => ['required_with:phones.*.phone_number', 'nullable', 'string', 'max:255'],
            'phones.*.phone_number' => ['required_with:phones.*.label', 'nullable', 'string', 'max:50'],
            'accounts' => ['nullable', 'array'],
            'accounts.*.bank_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.account_number' => ['required_with:accounts.*.bank_name', 'nullable', 'string', 'max:100'],
            'accounts.*.currency' => ['required_with:accounts.*.account_number', 'nullable', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
        ];
    }

    private function companyPayload(array $validated, User&Authenticatable $user, ?string $logoPath): array
    {
        return [
            'subscription_id' => $user->subscription_id,
            'created_by' => $user->id,
            'name' => $validated['name'],
            'rccm' => $validated['rccm'] ?? null,
            'id_nat' => $validated['id_nat'] ?? null,
            'nif' => $validated['nif'] ?? null,
            'website' => $validated['website'] ?? null,
            'slogan' => $validated['slogan'] ?? null,
            'country' => $validated['country'],
            'email' => $validated['email'],
            'address' => $validated['address'] ?? null,
            'logo' => $logoPath,
        ];
    }

    private function syncCompanyChildren(Company $company, array $validated): void
    {
        foreach ($validated['phones'] ?? [] as $phone) {
            if (blank($phone['label'] ?? null) && blank($phone['phone_number'] ?? null)) {
                continue;
            }

            $company->phones()->create([
                'label' => $phone['label'] ?? null,
                'phone_number' => $phone['phone_number'] ?? null,
            ]);
        }

        foreach ($validated['accounts'] ?? [] as $account) {
            if (blank($account['bank_name'] ?? null) && blank($account['account_number'] ?? null) && blank($account['currency'] ?? null)) {
                continue;
            }

            $company->accounts()->create([
                'bank_name' => $account['bank_name'] ?? null,
                'account_number' => $account['account_number'] ?? null,
                'currency' => $account['currency'] ?? null,
            ]);
        }
    }
}

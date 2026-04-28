<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\User;
use App\Support\CurrencyCatalog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        if ($user->isUser() && ! $user->sites()->exists()) {
            return view('main.pending-access', ['user' => $user]);
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
            return redirect()->route('main');
        }

        $company->load('subscription')->loadCount('sites');

        return view('main.company-sites', [
            'user' => $user,
            'company' => $company,
            'sites' => $company->sites()
                ->with(['responsible', 'users'])
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'responsibles' => $this->siteAssignableUsers($company),
            'siteTypes' => CompanySite::types(),
            'siteModules' => CompanySite::modules(),
            'moduleLabels' => $this->siteModuleLabels(),
            'typeLabels' => $this->siteTypeLabels(),
            'planRules' => $this->sitePlanRules($company),
            'currencies' => CurrencyCatalog::sorted(),
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
        $validated = $request->validate($this->siteRules($company));
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

    private function siteRules(Company $company): array
    {
        $userIds = $this->siteAssignableUsers($company)->pluck('id')->all();

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

    private function siteAssignableUsers(Company $company)
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

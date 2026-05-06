<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\AccountingClient;
use App\Models\AccountingCreditor;
use App\Models\AccountingCurrency;
use App\Models\AccountingDebtor;
use App\Models\AccountingPaymentMethod;
use App\Models\AccountingPartner;
use App\Models\AccountingProformaInvoice;
use App\Models\AccountingProformaInvoiceLine;
use App\Models\AccountingProspect;
use App\Models\AccountingRecurringService;
use App\Models\AccountingSalesRepresentative;
use App\Models\AccountingService;
use App\Models\AccountingServiceCategory;
use App\Models\AccountingServiceSubcategory;
use App\Models\AccountingServiceUnit;
use App\Models\AccountingSupplier;
use App\Models\AccountingStockAlert;
use App\Models\AccountingStockBatch;
use App\Models\AccountingStockCategory;
use App\Models\AccountingStockInventory;
use App\Models\AccountingStockItem;
use App\Models\AccountingStockMovement;
use App\Models\AccountingStockSubcategory;
use App\Models\AccountingStockTransfer;
use App\Models\AccountingStockUnit;
use App\Models\AccountingStockWarehouse;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\User;
use App\Support\CurrencyCatalog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            'clientStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingClientStats($site)
                : [],
            'supplierStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingSupplierStats($site)
                : [],
            'prospectStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingProspectStats($site)
                : [],
            'creditorStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingCreditorStats($site)
                : [],
            'debtorStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingDebtorStats($site)
                : [],
            'partnerStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingPartnerStats($site)
                : [],
            'salesRepresentativeStats' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingSalesRepresentativeStats($site)
                : [],
        ]);
    }

    public function accountingClients(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-clients', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'clientPermissions' => $this->sitePermissionFlags($user, $site),
            'clients' => AccountingClient::query()
                ->with('contacts')
                ->withCount('contacts')
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'currencies' => CurrencyCatalog::sorted(),
        ]);
    }

    public function storeAccountingClient(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.clients', [$company, $site]);
        }

        $validated = $request->validate($this->accountingClientRules());

        DB::transaction(function () use ($site, $user, $validated): void {
            $client = $site->accountingClients()->create($this->accountingClientPayload($validated, $user));
            $this->syncAccountingClientContacts($client, $validated);
        });

        return redirect()
            ->route('main.accounting.clients', [$company, $site])
            ->with('success', __('main.client_saved'));
    }

    public function updateAccountingClient(Request $request, Company $company, CompanySite $site, AccountingClient $client): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($client->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.clients', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.clients', [$company, $site]);
        }

        $validated = $request->validate($this->accountingClientRules());

        DB::transaction(function () use ($client, $user, $validated): void {
            $client->update($this->accountingClientPayload($validated, $user, false));
            $this->syncAccountingClientContacts($client, $validated);
        });

        return redirect()
            ->route('main.accounting.clients', [$company, $site])
            ->with('success', __('main.client_updated'));
    }

    public function destroyAccountingClient(Company $company, CompanySite $site, AccountingClient $client): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.clients', [$company, $site]);
        }

        if ($client->company_site_id === $site->id) {
            $client->delete();
        }

        return redirect()
            ->route('main.accounting.clients', [$company, $site])
            ->with('success', __('main.client_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingSuppliers(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-suppliers', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'supplierPermissions' => $this->sitePermissionFlags($user, $site),
            'suppliers' => AccountingSupplier::query()
                ->with('contacts')
                ->withCount('contacts')
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'currencies' => CurrencyCatalog::sorted(),
        ]);
    }

    public function storeAccountingSupplier(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.suppliers', [$company, $site]);
        }

        $validated = $request->validate($this->accountingSupplierRules());

        DB::transaction(function () use ($site, $user, $validated): void {
            $supplier = $site->accountingSuppliers()->create($this->accountingSupplierPayload($validated, $user));
            $this->syncAccountingSupplierContacts($supplier, $validated);
        });

        return redirect()
            ->route('main.accounting.suppliers', [$company, $site])
            ->with('success', __('main.supplier_saved'));
    }

    public function updateAccountingSupplier(Request $request, Company $company, CompanySite $site, AccountingSupplier $supplier): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($supplier->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.suppliers', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.suppliers', [$company, $site]);
        }

        $validated = $request->validate($this->accountingSupplierRules());

        DB::transaction(function () use ($supplier, $user, $validated): void {
            $supplier->update($this->accountingSupplierPayload($validated, $user, false));
            $this->syncAccountingSupplierContacts($supplier, $validated);
        });

        return redirect()
            ->route('main.accounting.suppliers', [$company, $site])
            ->with('success', __('main.supplier_updated'));
    }

    public function destroyAccountingSupplier(Company $company, CompanySite $site, AccountingSupplier $supplier): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.suppliers', [$company, $site]);
        }

        if ($supplier->company_site_id === $site->id) {
            $supplier->delete();
        }

        return redirect()
            ->route('main.accounting.suppliers', [$company, $site])
            ->with('success', __('main.supplier_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingCreditors(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-creditors', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'creditorPermissions' => $this->sitePermissionFlags($user, $site),
            'creditors' => AccountingCreditor::query()
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'currencies' => CurrencyCatalog::sorted(),
            'typeLabels' => $this->accountingCreditorTypeLabels(),
            'priorityLabels' => $this->accountingCreditorPriorityLabels(),
            'statusLabels' => $this->accountingCreditorStatusLabels(),
        ]);
    }

    public function storeAccountingCreditor(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.creditors', [$company, $site]);
        }

        $validated = $request->validate($this->accountingCreditorRules());

        $site->accountingCreditors()->create($this->accountingCreditorPayload($validated, $user));

        return redirect()
            ->route('main.accounting.creditors', [$company, $site])
            ->with('success', __('main.creditor_saved'));
    }

    public function updateAccountingCreditor(Request $request, Company $company, CompanySite $site, AccountingCreditor $creditor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($creditor->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.creditors', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.creditors', [$company, $site]);
        }

        $validated = $request->validate($this->accountingCreditorRules());

        $creditor->update($this->accountingCreditorPayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.creditors', [$company, $site])
            ->with('success', __('main.creditor_updated'));
    }

    public function destroyAccountingCreditor(Company $company, CompanySite $site, AccountingCreditor $creditor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.creditors', [$company, $site]);
        }

        if ($creditor->company_site_id === $site->id) {
            $creditor->delete();
        }

        return redirect()
            ->route('main.accounting.creditors', [$company, $site])
            ->with('success', __('main.creditor_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingDebtors(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-debtors', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'debtorPermissions' => $this->sitePermissionFlags($user, $site),
            'debtors' => AccountingDebtor::query()
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'currencies' => CurrencyCatalog::sorted(),
            'typeLabels' => $this->accountingDebtorTypeLabels(),
            'statusLabels' => $this->accountingDebtorStatusLabels(),
        ]);
    }

    public function storeAccountingDebtor(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.debtors', [$company, $site]);
        }

        $validated = $request->validate($this->accountingDebtorRules());

        $site->accountingDebtors()->create($this->accountingDebtorPayload($validated, $user));

        return redirect()
            ->route('main.accounting.debtors', [$company, $site])
            ->with('success', __('main.debtor_saved'));
    }

    public function updateAccountingDebtor(Request $request, Company $company, CompanySite $site, AccountingDebtor $debtor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($debtor->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.debtors', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.debtors', [$company, $site]);
        }

        $validated = $request->validate($this->accountingDebtorRules());

        $debtor->update($this->accountingDebtorPayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.debtors', [$company, $site])
            ->with('success', __('main.debtor_updated'));
    }

    public function destroyAccountingDebtor(Company $company, CompanySite $site, AccountingDebtor $debtor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.debtors', [$company, $site]);
        }

        if ($debtor->company_site_id === $site->id) {
            $debtor->delete();
        }

        return redirect()
            ->route('main.accounting.debtors', [$company, $site])
            ->with('success', __('main.debtor_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingPartners(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-partners', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'partnerPermissions' => $this->sitePermissionFlags($user, $site),
            'partners' => AccountingPartner::query()
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'typeLabels' => $this->accountingPartnerTypeLabels(),
            'statusLabels' => $this->accountingPartnerStatusLabels(),
        ]);
    }

    public function storeAccountingPartner(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.partners', [$company, $site]);
        }

        $validated = $request->validate($this->accountingPartnerRules());

        $site->accountingPartners()->create($this->accountingPartnerPayload($validated, $user));

        return redirect()
            ->route('main.accounting.partners', [$company, $site])
            ->with('success', __('main.partner_saved'));
    }

    public function updateAccountingPartner(Request $request, Company $company, CompanySite $site, AccountingPartner $partner): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($partner->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.partners', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.partners', [$company, $site]);
        }

        $validated = $request->validate($this->accountingPartnerRules());

        $partner->update($this->accountingPartnerPayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.partners', [$company, $site])
            ->with('success', __('main.partner_updated'));
    }

    public function destroyAccountingPartner(Company $company, CompanySite $site, AccountingPartner $partner): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.partners', [$company, $site]);
        }

        if ($partner->company_site_id === $site->id) {
            $partner->delete();
        }

        return redirect()
            ->route('main.accounting.partners', [$company, $site])
            ->with('success', __('main.partner_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingSalesRepresentatives(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-sales-representatives', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'representativePermissions' => $this->sitePermissionFlags($user, $site),
            'representatives' => AccountingSalesRepresentative::query()
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'typeLabels' => $this->accountingSalesRepresentativeTypeLabels(),
            'statusLabels' => $this->accountingSalesRepresentativeStatusLabels(),
            'currencies' => CurrencyCatalog::all(),
        ]);
    }

    public function storeAccountingSalesRepresentative(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.sales-representatives', [$company, $site]);
        }

        $validated = $request->validate($this->accountingSalesRepresentativeRules());

        $site->accountingSalesRepresentatives()->create($this->accountingSalesRepresentativePayload($validated, $user));

        return redirect()
            ->route('main.accounting.sales-representatives', [$company, $site])
            ->with('success', __('main.sales_representative_saved'));
    }

    public function updateAccountingSalesRepresentative(Request $request, Company $company, CompanySite $site, AccountingSalesRepresentative $representative): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($representative->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.sales-representatives', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.sales-representatives', [$company, $site]);
        }

        $validated = $request->validate($this->accountingSalesRepresentativeRules());

        $representative->update($this->accountingSalesRepresentativePayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.sales-representatives', [$company, $site])
            ->with('success', __('main.sales_representative_updated'));
    }

    public function destroyAccountingSalesRepresentative(Company $company, CompanySite $site, AccountingSalesRepresentative $representative): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.sales-representatives', [$company, $site]);
        }

        if ($representative->company_site_id === $site->id) {
            $representative->delete();
        }

        return redirect()
            ->route('main.accounting.sales-representatives', [$company, $site])
            ->with('success', __('main.sales_representative_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingStockIndex(Company $company, CompanySite $site, string $resource): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $config = $this->accountingStockResourceConfig($site, $resource);

        abort_if($config === null, 404);

        $recordsQuery = $config['model']::query()
            ->where('company_site_id', $site->id)
            ->with($config['relations'] ?? []);

        if (in_array($resource, ['categories', 'subcategories', 'warehouses', 'units'], true)) {
            $recordsQuery->orderByDesc('is_default');
        }

        $records = $recordsQuery
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.accounting-stock-resource', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'stockPermissions' => $this->sitePermissionFlags($user, $site),
            'resource' => $resource,
            'config' => $config,
            'records' => $records,
        ]);
    }

    public function storeAccountingStockResource(Request $request, Company $company, CompanySite $site, string $resource): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $config = $this->accountingStockResourceConfig($site, $resource);

        abort_if($config === null, 404);

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.stock.index', [$company, $site, $resource]);
        }

        $validated = $request->validate($this->accountingStockRules($site, $resource, $config));
        $payload = $this->accountingStockPayload($validated, $user, true);
        $record = $site->{$config['relation']}()->create($payload);

        $this->afterAccountingStockSaved($resource, $record, null);

        return redirect()
            ->route('main.accounting.stock.index', [$company, $site, $resource])
            ->with('success', __('main.stock_resource_saved', ['resource' => $config['singular_lower']]));
    }

    public function updateAccountingStockResource(Request $request, Company $company, CompanySite $site, string $resource, int $record): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $config = $this->accountingStockResourceConfig($site, $resource);

        abort_if($config === null, 404);

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.stock.index', [$company, $site, $resource]);
        }

        $model = $config['model']::query()
            ->where('company_site_id', $site->id)
            ->findOrFail($record);

        $original = $model->replicate();
        $validated = $request->validate($this->accountingStockRules($site, $resource, $config));

        $model->update($this->accountingStockPayload($validated, $user, false));
        $this->afterAccountingStockSaved($resource, $model->refresh(), $original);

        return redirect()
            ->route('main.accounting.stock.index', [$company, $site, $resource])
            ->with('success', __('main.stock_resource_updated', ['resource' => $config['singular_lower']]));
    }

    public function destroyAccountingStockResource(Company $company, CompanySite $site, string $resource, int $record): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $config = $this->accountingStockResourceConfig($site, $resource);

        abort_if($config === null, 404);

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.stock.index', [$company, $site, $resource]);
        }

        $model = $config['model']::query()
            ->where('company_site_id', $site->id)
            ->findOrFail($record);

        if ($this->isProtectedDefaultStockResource($resource, $model)) {
            return redirect()
                ->route('main.accounting.stock.index', [$company, $site, $resource])
                ->with('success', __('main.default_stock_resource_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        if ($resource === 'movements' && $model instanceof AccountingStockMovement) {
            $this->reverseAccountingStockMovement($model);
        }

        $model->delete();

        return redirect()
            ->route('main.accounting.stock.index', [$company, $site, $resource])
            ->with('success', __('main.stock_resource_deleted', ['resource' => $config['singular_lower']]))
            ->with('toast_type', 'danger');
    }

    public function accountingServiceIndex(Company $company, CompanySite $site, string $resource): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $config = $this->accountingServiceResourceConfig($site, $resource);

        abort_if($config === null, 404);

        $recordsQuery = $config['model']::query()
            ->where('company_site_id', $site->id)
            ->with($config['relations'] ?? []);

        if (in_array($resource, ['categories', 'subcategories', 'units'], true)) {
            $recordsQuery->orderByDesc('is_default');
        }

        return view('main.modules.accounting-service-resource', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'servicePermissions' => $this->sitePermissionFlags($user, $site),
            'resource' => $resource,
            'config' => $config,
            'records' => $recordsQuery->latest()->paginate(5)->withQueryString(),
        ]);
    }

    public function storeAccountingServiceResource(Request $request, Company $company, CompanySite $site, string $resource): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $config = $this->accountingServiceResourceConfig($site, $resource);

        abort_if($config === null, 404);

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.services.index', [$company, $site, $resource]);
        }

        $validated = $request->validate($this->accountingServiceRules($site, $resource));
        $site->{$config['relation']}()->create($this->accountingServicePayload($validated, $user, true));

        return redirect()
            ->route('main.accounting.services.index', [$company, $site, $resource])
            ->with('success', __('main.service_resource_saved', ['resource' => $config['singular_lower']]));
    }

    public function updateAccountingServiceResource(Request $request, Company $company, CompanySite $site, string $resource, int $record): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $config = $this->accountingServiceResourceConfig($site, $resource);

        abort_if($config === null, 404);

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.services.index', [$company, $site, $resource]);
        }

        $model = $config['model']::query()
            ->where('company_site_id', $site->id)
            ->findOrFail($record);

        $validated = $request->validate($this->accountingServiceRules($site, $resource));
        $model->update($this->accountingServicePayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.services.index', [$company, $site, $resource])
            ->with('success', __('main.service_resource_updated', ['resource' => $config['singular_lower']]));
    }

    public function destroyAccountingServiceResource(Company $company, CompanySite $site, string $resource, int $record): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $config = $this->accountingServiceResourceConfig($site, $resource);

        abort_if($config === null, 404);

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.services.index', [$company, $site, $resource]);
        }

        $model = $config['model']::query()
            ->where('company_site_id', $site->id)
            ->findOrFail($record);

        if ($this->isProtectedDefaultServiceResource($resource, $model)) {
            return redirect()
                ->route('main.accounting.services.index', [$company, $site, $resource])
                ->with('success', __('main.default_service_resource_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $model->delete();

        return redirect()
            ->route('main.accounting.services.index', [$company, $site, $resource])
            ->with('success', __('main.service_resource_deleted', ['resource' => $config['singular_lower']]))
            ->with('toast_type', 'danger');
    }

    public function accountingCurrencies(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-currencies', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'currencyPermissions' => $this->sitePermissionFlags($user, $site),
            'accountingCurrencies' => AccountingCurrency::query()
                ->where('company_site_id', $site->id)
                ->orderByDesc('is_default')
                ->orderByDesc('is_base')
                ->orderBy('name')
                ->paginate(5)
                ->withQueryString(),
            'currencyOptions' => collect(CurrencyCatalog::sorted())
                ->mapWithKeys(fn (array $currency, string $code) => [$code => CurrencyCatalog::label($code)])
                ->all(),
            'statusLabels' => $this->accountingCurrencyStatusLabels(),
        ]);
    }

    public function storeAccountingCurrency(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.currencies', [$company, $site]);
        }

        $validated = $request->validate($this->accountingCurrencyRules($site));
        $site->accountingCurrencies()->create($this->accountingCurrencyPayload($validated, $user));

        return redirect()
            ->route('main.accounting.currencies', [$company, $site])
            ->with('success', __('main.currency_saved'));
    }

    public function updateAccountingCurrency(Request $request, Company $company, CompanySite $site, AccountingCurrency $currency): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($currency->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.currencies', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.currencies', [$company, $site]);
        }

        if ($currency->is_default) {
            return redirect()
                ->route('main.accounting.currencies', [$company, $site])
                ->with('success', __('main.default_currency_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $validated = $request->validate($this->accountingCurrencyRules($site, $currency));
        $currency->update($this->accountingCurrencyPayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.currencies', [$company, $site])
            ->with('success', __('main.currency_updated'));
    }

    public function destroyAccountingCurrency(Company $company, CompanySite $site, AccountingCurrency $currency): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.currencies', [$company, $site]);
        }

        if ($currency->company_site_id === $site->id && ! $currency->is_default && ! $currency->is_base) {
            $currency->delete();

            return redirect()
                ->route('main.accounting.currencies', [$company, $site])
                ->with('success', __('main.currency_deleted'))
                ->with('toast_type', 'danger');
        }

        return redirect()
            ->route('main.accounting.currencies', [$company, $site])
            ->with('success', __('main.default_currency_cannot_delete'))
            ->with('toast_type', 'danger');
    }

    public function accountingPaymentMethods(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);

        return view('main.modules.accounting-payment-methods', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'paymentMethodPermissions' => $this->sitePermissionFlags($user, $site),
            'paymentMethods' => AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->orderByDesc('is_system_default')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->paginate(5)
                ->withQueryString(),
            'typeLabels' => $this->paymentMethodTypeLabels(),
            'statusLabels' => $this->paymentMethodStatusLabels(),
            'currencyOptions' => $this->siteCurrencyOptions($site),
        ]);
    }

    public function storeAccountingPaymentMethod(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.payment-methods', [$company, $site]);
        }

        $validated = $request->validate($this->paymentMethodRules($site));

        DB::transaction(function () use ($site, $validated, $user): void {
            $payload = $this->paymentMethodPayload($validated, $user);

            if ($payload['is_default']) {
                AccountingPaymentMethod::query()
                    ->where('company_site_id', $site->id)
                    ->update(['is_default' => false]);
            }

            $site->accountingPaymentMethods()->create($payload);
        });

        return redirect()
            ->route('main.accounting.payment-methods', [$company, $site])
            ->with('success', __('main.payment_method_saved'));
    }

    public function updateAccountingPaymentMethod(Request $request, Company $company, CompanySite $site, AccountingPaymentMethod $method): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($method->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.payment-methods', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.payment-methods', [$company, $site]);
        }

        if ($method->is_system_default) {
            return redirect()
                ->route('main.accounting.payment-methods', [$company, $site])
                ->with('success', __('main.default_payment_method_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $validated = $request->validate($this->paymentMethodRules($site, $method));

        DB::transaction(function () use ($site, $method, $validated, $user): void {
            $payload = $this->paymentMethodPayload($validated, $user, false);

            if ($payload['is_default']) {
                AccountingPaymentMethod::query()
                    ->where('company_site_id', $site->id)
                    ->whereKeyNot($method->id)
                    ->update(['is_default' => false]);
            }

            $method->update($payload);
            $this->ensureDefaultAccountingPaymentMethodRecord($site);
        });

        return redirect()
            ->route('main.accounting.payment-methods', [$company, $site])
            ->with('success', __('main.payment_method_updated'));
    }

    public function destroyAccountingPaymentMethod(Company $company, CompanySite $site, AccountingPaymentMethod $method): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.payment-methods', [$company, $site]);
        }

        if ($method->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.payment-methods', [$company, $site]);
        }

        if ($method->is_system_default || $method->is_default) {
            return redirect()
                ->route('main.accounting.payment-methods', [$company, $site])
                ->with('success', __('main.default_payment_method_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $method->delete();

        return redirect()
            ->route('main.accounting.payment-methods', [$company, $site])
            ->with('success', __('main.payment_method_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingProformaInvoices(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-proforma-invoices', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'proformaPermissions' => $this->sitePermissionFlags($user, $site),
            'proformas' => AccountingProformaInvoice::query()
                ->with(['client', 'lines'])
                ->where('company_site_id', $site->id)
                ->latest('issue_date')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->proformaItemOptions($site),
            'services' => $this->proformaServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->proformaStatusLabels(),
            'lineTypeLabels' => $this->proformaLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'proformaDefaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function createAccountingProformaInvoice(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-proforma-invoice-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->proformaItemOptions($site),
            'services' => $this->proformaServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->proformaStatusLabels(),
            'lineTypeLabels' => $this->proformaLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'proformaDefaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function editAccountingProformaInvoice(Company $company, CompanySite $site, AccountingProformaInvoice $proforma): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if ($proforma->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        if ($proforma->status === AccountingProformaInvoice::STATUS_CONVERTED) {
            return redirect()
                ->route('main.accounting.proforma-invoices', [$company, $site])
                ->with('success', __('main.converted_proforma_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-proforma-invoice-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'proforma' => $proforma->load('lines'),
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->proformaItemOptions($site),
            'services' => $this->proformaServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->proformaStatusLabels(),
            'lineTypeLabels' => $this->proformaLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'proformaDefaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function storeAccountingProformaInvoice(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        $validated = $request->validate($this->proformaRules($site));

        DB::transaction(function () use ($site, $user, $validated): void {
            $totals = $this->calculateProformaTotals($validated['lines'], (float) $validated['tax_rate']);
            $proforma = $site->accountingProformaInvoices()->create($this->proformaPayload($validated, $user, $totals));
            $this->syncProformaLines($proforma, $validated['lines']);
        });

        return redirect()
            ->route('main.accounting.proforma-invoices', [$company, $site])
            ->with('success', __('main.proforma_saved'));
    }

    public function updateAccountingProformaInvoice(Request $request, Company $company, CompanySite $site, AccountingProformaInvoice $proforma): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($proforma->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        if ($proforma->status === AccountingProformaInvoice::STATUS_CONVERTED) {
            return redirect()
                ->route('main.accounting.proforma-invoices', [$company, $site])
                ->with('success', __('main.converted_proforma_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        $validated = $request->validate($this->proformaRules($site, true));

        DB::transaction(function () use ($proforma, $user, $validated): void {
            $totals = $this->calculateProformaTotals($validated['lines'], (float) $validated['tax_rate']);
            $proforma->update($this->proformaPayload($validated, $user, $totals, false));
            $this->syncProformaLines($proforma, $validated['lines']);
        });

        return redirect()
            ->route('main.accounting.proforma-invoices', [$company, $site])
            ->with('success', __('main.proforma_updated'));
    }

    public function printAccountingProformaInvoice(Company $company, CompanySite $site, AccountingProformaInvoice $proforma): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($proforma->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        $proforma->load([
            'client',
            'creator',
            'lines.item.unit',
            'lines.service.unit',
        ]);

        $filename = 'facture-proforma-'.$proforma->reference.'.pdf';
        $proformaUrl = route('main.accounting.proforma-invoices.print', [$company, $site, $proforma], true);

        return Pdf::loadView('main.modules.accounting-proforma-invoice-print', [
            'user' => $user,
            'company' => $company->load(['subscription', 'accounts']),
            'site' => $site->load('responsible'),
            'proforma' => $proforma,
            'proformaUrl' => $proformaUrl,
            'proformaQrCodeDataUri' => $this->qrCodeSvgDataUri($proformaUrl),
            'statusLabels' => $this->proformaStatusLabels(),
            'lineTypeLabels' => $this->proformaLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'isPdf' => true,
        ])->setPaper('a4')->stream($filename);
    }

    public function destroyAccountingProformaInvoice(Company $company, CompanySite $site, AccountingProformaInvoice $proforma): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        if ($proforma->company_site_id === $site->id && $proforma->status !== AccountingProformaInvoice::STATUS_CONVERTED) {
            $proforma->delete();

            return redirect()
                ->route('main.accounting.proforma-invoices', [$company, $site])
                ->with('success', __('main.proforma_deleted'))
                ->with('toast_type', 'danger');
        }

        return redirect()
            ->route('main.accounting.proforma-invoices', [$company, $site])
            ->with('success', __('main.converted_proforma_cannot_delete'))
            ->with('toast_type', 'danger');
    }

    public function accountingProspects(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-prospects', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'prospectPermissions' => $this->sitePermissionFlags($user, $site),
            'prospects' => AccountingProspect::query()
                ->with('contacts')
                ->withCount('contacts')
                ->where('company_site_id', $site->id)
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'sourceLabels' => $this->accountingProspectSourceLabels(),
            'statusLabels' => $this->accountingProspectStatusLabels(),
            'interestLabels' => $this->accountingProspectInterestLabels(),
        ]);
    }

    public function storeAccountingProspect(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.prospects', [$company, $site]);
        }

        $validated = $request->validate($this->accountingProspectRules());

        DB::transaction(function () use ($site, $user, $validated): void {
            $prospect = $site->accountingProspects()->create($this->accountingProspectPayload($validated, $user));
            $this->syncAccountingProspectContacts($prospect, $validated);
        });

        return redirect()
            ->route('main.accounting.prospects', [$company, $site])
            ->with('success', __('main.prospect_saved'));
    }

    public function updateAccountingProspect(Request $request, Company $company, CompanySite $site, AccountingProspect $prospect): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($prospect->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.prospects', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.prospects', [$company, $site]);
        }

        $validated = $request->validate($this->accountingProspectRules());

        DB::transaction(function () use ($prospect, $user, $validated): void {
            $prospect->update($this->accountingProspectPayload($validated, $user, false));
            $this->syncAccountingProspectContacts($prospect, $validated);
        });

        return redirect()
            ->route('main.accounting.prospects', [$company, $site])
            ->with('success', __('main.prospect_updated'));
    }

    public function destroyAccountingProspect(Company $company, CompanySite $site, AccountingProspect $prospect): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.prospects', [$company, $site]);
        }

        if ($prospect->company_site_id === $site->id) {
            $prospect->delete();
        }

        return redirect()
            ->route('main.accounting.prospects', [$company, $site])
            ->with('success', __('main.prospect_deleted'))
            ->with('toast_type', 'danger');
    }

    public function convertAccountingProspect(Company $company, CompanySite $site, AccountingProspect $prospect): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($prospect->company_site_id !== $site->id || ! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.prospects', [$company, $site]);
        }

        if ($prospect->isConverted()) {
            return redirect()
                ->route('main.accounting.prospects', [$company, $site])
                ->with('success', __('main.prospect_already_converted'));
        }

        DB::transaction(function () use ($site, $user, $prospect): void {
            $client = $site->accountingClients()->create([
                'created_by' => $user->id,
                'type' => $prospect->type,
                'name' => $prospect->name,
                'profession' => $prospect->isCompany() ? null : $prospect->profession,
                'phone' => $prospect->isCompany() ? null : $prospect->phone,
                'email' => $prospect->isCompany() ? null : $prospect->email,
                'address' => $prospect->address,
                'rccm' => $prospect->isCompany() ? $prospect->rccm : null,
                'id_nat' => $prospect->isCompany() ? $prospect->id_nat : null,
                'nif' => $prospect->isCompany() ? $prospect->nif : null,
                'website' => $prospect->isCompany() ? $prospect->website : null,
            ]);

            if ($prospect->isCompany()) {
                foreach ($prospect->contacts as $contact) {
                    $client->contacts()->create([
                        'full_name' => $contact->full_name,
                        'position' => $contact->position,
                        'department' => $contact->department,
                        'email' => $contact->email,
                        'phone' => $contact->phone,
                    ]);
                }
            }

            $prospect->update([
                'status' => AccountingProspect::STATUS_WON,
                'converted_client_id' => $client->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()
            ->route('main.accounting.clients', [$company, $site])
            ->with('success', __('main.prospect_converted'));
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
            $this->ensureDefaultAccountingStockRecords($site);
            $this->ensureDefaultAccountingServiceRecords($site);
            $this->ensureDefaultAccountingCurrencyRecord($site);
            $this->ensureDefaultAccountingPaymentMethodRecord($site);
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
            $this->ensureDefaultAccountingStockRecords($site);
            $this->ensureDefaultAccountingServiceRecords($site);
            $this->ensureDefaultAccountingCurrencyRecord($site);
            $this->ensureDefaultAccountingPaymentMethodRecord($site);
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

    private function accountingAccess(Company $company, CompanySite $site): array|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $this->canAccessCompanySite($user, $company, $site)) {
            return $this->redirectMainArea($user);
        }

        if (! in_array(CompanySite::MODULE_ACCOUNTING, $this->availableSiteModulesForUser($user, $site), true)) {
            return redirect()->route('main.companies.sites.show', [$company, $site]);
        }

        return [
            $user,
            $this->siteModuleMeta()[CompanySite::MODULE_ACCOUNTING],
        ];
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

    private function sitePermissionFlags(User&Authenticatable $user, CompanySite $site): array
    {
        if ($user->isAdmin() || $user->isSuperadmin()) {
            return [
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        $assignedSite = $user->sites()
            ->whereKey($site->getKey())
            ->first();

        return [
            'can_create' => (bool) $assignedSite?->pivot->can_create,
            'can_update' => (bool) $assignedSite?->pivot->can_update,
            'can_delete' => (bool) $assignedSite?->pivot->can_delete,
        ];
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

    private function accountingClientRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingClient::types())],
            'name' => ['required', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'rccm' => ['nullable', 'string', 'max:255'],
            'id_nat' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
            'website' => ['nullable', 'string', 'max:255'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.full_name' => ['nullable', 'required_with:contacts.*.position,contacts.*.department,contacts.*.email,contacts.*.phone', 'string', 'max:255'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'contacts.*.department' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'client_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingSupplierRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingSupplier::types())],
            'name' => ['required', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'rccm' => ['nullable', 'string', 'max:255'],
            'id_nat' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
            'website' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(AccountingSupplier::statuses())],
            'contacts' => ['nullable', 'array'],
            'contacts.*.full_name' => ['nullable', 'required_with:contacts.*.position,contacts.*.department,contacts.*.email,contacts.*.phone', 'string', 'max:255'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'contacts.*.department' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'supplier_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingCreditorRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingCreditor::types())],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'currency' => ['required', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
            'initial_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0', 'lte:initial_amount'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'string', Rule::in(AccountingCreditor::priorities())],
            'status' => ['required', 'string', Rule::in(AccountingCreditor::statuses())],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'creditor_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingDebtorRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingDebtor::types())],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'currency' => ['required', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
            'initial_amount' => ['required', 'numeric', 'min:0'],
            'received_amount' => ['required', 'numeric', 'min:0', 'lte:initial_amount'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(AccountingDebtor::statuses())],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'debtor_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingPartnerRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingPartner::types())],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'activity_domain' => ['nullable', 'string', 'max:255'],
            'partnership_started_at' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(AccountingPartner::statuses())],
            'notes' => ['nullable', 'string'],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'partner_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingSalesRepresentativeRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingSalesRepresentative::types())],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'sales_area' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', Rule::in(array_keys(CurrencyCatalog::all()))],
            'monthly_target' => ['required', 'numeric', 'min:0'],
            'annual_target' => ['required', 'numeric', 'min:0'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'string', Rule::in(AccountingSalesRepresentative::statuses())],
            'notes' => ['nullable', 'string'],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'representative_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingProspectRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AccountingProspect::types())],
            'name' => ['required', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'rccm' => ['nullable', 'string', 'max:255'],
            'id_nat' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'source' => ['required', 'string', Rule::in(AccountingProspect::sources())],
            'status' => ['required', 'string', Rule::in(AccountingProspect::statuses())],
            'interest_level' => ['required', 'string', Rule::in(AccountingProspect::interestLevels())],
            'notes' => ['nullable', 'string'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.full_name' => ['nullable', 'required_with:contacts.*.position,contacts.*.department,contacts.*.email,contacts.*.phone', 'string', 'max:255'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'contacts.*.department' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'prospect_id' => ['nullable', 'integer'],
        ];
    }

    private function accountingClientStats(CompanySite $site): array
    {
        $baseQuery = AccountingClient::query()
            ->where('company_site_id', $site->id);

        $total = (clone $baseQuery)->count();
        $individuals = (clone $baseQuery)
            ->where('type', AccountingClient::TYPE_INDIVIDUAL)
            ->count();
        $companies = (clone $baseQuery)
            ->where('type', AccountingClient::TYPE_COMPANY)
            ->count();
        $contacts = DB::table('accounting_client_contacts')
            ->join('accounting_clients', 'accounting_client_contacts.accounting_client_id', '=', 'accounting_clients.id')
            ->where('accounting_clients.company_site_id', $site->id)
            ->count();

        return [
            'total' => $total,
            'individuals' => $individuals,
            'companies' => $companies,
            'contacts' => $contacts,
            'recent' => AccountingClient::query()
                ->where('company_site_id', $site->id)
                ->latest()
                ->take(5)
                ->get(['id', 'reference', 'type', 'name', 'created_at']),
        ];
    }

    private function accountingSupplierStats(CompanySite $site): array
    {
        $baseQuery = AccountingSupplier::query()
            ->where('company_site_id', $site->id);

        $total = (clone $baseQuery)->count();
        $contacts = DB::table('accounting_supplier_contacts')
            ->join('accounting_suppliers', 'accounting_supplier_contacts.accounting_supplier_id', '=', 'accounting_suppliers.id')
            ->where('accounting_suppliers.company_site_id', $site->id)
            ->count();

        return [
            'total' => $total,
            'contacts' => $contacts,
        ];
    }

    private function accountingProspectStats(CompanySite $site): array
    {
        $baseQuery = AccountingProspect::query()
            ->where('company_site_id', $site->id);

        $total = (clone $baseQuery)->count();
        $contacts = DB::table('accounting_prospect_contacts')
            ->join('accounting_prospects', 'accounting_prospect_contacts.accounting_prospect_id', '=', 'accounting_prospects.id')
            ->where('accounting_prospects.company_site_id', $site->id)
            ->count();

        return [
            'total' => $total,
            'contacts' => $contacts,
        ];
    }

    private function accountingCreditorStats(CompanySite $site): array
    {
        $baseQuery = AccountingCreditor::query()
            ->where('company_site_id', $site->id);
        $creditors = (clone $baseQuery)->get(['initial_amount', 'paid_amount']);

        return [
            'total' => $creditors->count(),
            'balance_due' => $creditors->sum(fn (AccountingCreditor $creditor) => $creditor->balanceDue()),
            'urgent' => (clone $baseQuery)
                ->where('priority', AccountingCreditor::PRIORITY_URGENT)
                ->count(),
        ];
    }

    private function accountingDebtorStats(CompanySite $site): array
    {
        $baseQuery = AccountingDebtor::query()
            ->where('company_site_id', $site->id);
        $debtors = (clone $baseQuery)->get(['initial_amount', 'received_amount']);

        return [
            'total' => $debtors->count(),
            'balance_receivable' => $debtors->sum(fn (AccountingDebtor $debtor) => $debtor->balanceReceivable()),
        ];
    }

    private function accountingPartnerStats(CompanySite $site): array
    {
        $baseQuery = AccountingPartner::query()
            ->where('company_site_id', $site->id);

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)
                ->where('status', AccountingPartner::STATUS_ACTIVE)
                ->count(),
        ];
    }

    private function accountingSalesRepresentativeStats(CompanySite $site): array
    {
        $baseQuery = AccountingSalesRepresentative::query()
            ->where('company_site_id', $site->id);

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)
                ->where('status', AccountingSalesRepresentative::STATUS_ACTIVE)
                ->count(),
        ];
    }

    private function accountingClientPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $isCompany = $validated['type'] === AccountingClient::TYPE_COMPANY;

        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'profession' => $isCompany ? null : ($validated['profession'] ?? null),
            'phone' => $isCompany ? null : ($validated['phone'] ?? null),
            'email' => $isCompany ? null : ($validated['email'] ?? null),
            'address' => $validated['address'] ?? null,
            'rccm' => $isCompany ? ($validated['rccm'] ?? null) : null,
            'id_nat' => $isCompany ? ($validated['id_nat'] ?? null) : null,
            'nif' => $isCompany ? ($validated['nif'] ?? null) : null,
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'website' => $isCompany ? ($validated['website'] ?? null) : null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function syncAccountingClientContacts(AccountingClient $client, array $validated): void
    {
        $client->contacts()->delete();

        if (($validated['type'] ?? null) !== AccountingClient::TYPE_COMPANY) {
            return;
        }

        foreach ($validated['contacts'] ?? [] as $contact) {
            $hasContactValue = collect($contact)->filter(fn ($value) => filled($value))->isNotEmpty();

            if (! $hasContactValue || blank($contact['full_name'] ?? null)) {
                continue;
            }

            $client->contacts()->create([
                'full_name' => $contact['full_name'],
                'position' => $contact['position'] ?? null,
                'department' => $contact['department'] ?? null,
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
            ]);
        }
    }

    private function accountingSupplierPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $isCompany = $validated['type'] === AccountingSupplier::TYPE_COMPANY;

        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'profession' => $isCompany ? null : ($validated['profession'] ?? null),
            'phone' => $isCompany ? null : ($validated['phone'] ?? null),
            'email' => $isCompany ? null : ($validated['email'] ?? null),
            'address' => $validated['address'] ?? null,
            'rccm' => $isCompany ? ($validated['rccm'] ?? null) : null,
            'id_nat' => $isCompany ? ($validated['id_nat'] ?? null) : null,
            'nif' => $isCompany ? ($validated['nif'] ?? null) : null,
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'website' => $isCompany ? ($validated['website'] ?? null) : null,
            'status' => $validated['status'],
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function accountingCreditorPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'currency' => $validated['currency'],
            'initial_amount' => $validated['initial_amount'],
            'paid_amount' => $validated['paid_amount'],
            'due_date' => $validated['due_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => $validated['status'],
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function accountingDebtorPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'currency' => $validated['currency'],
            'initial_amount' => $validated['initial_amount'],
            'received_amount' => $validated['received_amount'],
            'due_date' => $validated['due_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function accountingPartnerPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_position' => $validated['contact_position'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'website' => $validated['website'] ?? null,
            'activity_domain' => $validated['activity_domain'] ?? null,
            'partnership_started_at' => $validated['partnership_started_at'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function accountingSalesRepresentativePayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'sales_area' => $validated['sales_area'] ?? null,
            'currency' => $validated['currency'],
            'monthly_target' => $validated['monthly_target'],
            'annual_target' => $validated['annual_target'],
            'commission_rate' => $validated['commission_rate'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function accountingProspectPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $isCompany = $validated['type'] === AccountingProspect::TYPE_COMPANY;

        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'profession' => $isCompany ? null : ($validated['profession'] ?? null),
            'phone' => $isCompany ? null : ($validated['phone'] ?? null),
            'email' => $isCompany ? null : ($validated['email'] ?? null),
            'address' => $validated['address'] ?? null,
            'rccm' => $isCompany ? ($validated['rccm'] ?? null) : null,
            'id_nat' => $isCompany ? ($validated['id_nat'] ?? null) : null,
            'nif' => $isCompany ? ($validated['nif'] ?? null) : null,
            'website' => $isCompany ? ($validated['website'] ?? null) : null,
            'source' => $validated['source'],
            'status' => $validated['status'],
            'interest_level' => $validated['interest_level'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function syncAccountingSupplierContacts(AccountingSupplier $supplier, array $validated): void
    {
        $supplier->contacts()->delete();

        if (($validated['type'] ?? null) !== AccountingSupplier::TYPE_COMPANY) {
            return;
        }

        foreach ($validated['contacts'] ?? [] as $contact) {
            $hasContactValue = collect($contact)->filter(fn ($value) => filled($value))->isNotEmpty();

            if (! $hasContactValue || blank($contact['full_name'] ?? null)) {
                continue;
            }

            $supplier->contacts()->create([
                'full_name' => $contact['full_name'],
                'position' => $contact['position'] ?? null,
                'department' => $contact['department'] ?? null,
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
            ]);
        }
    }

    private function syncAccountingProspectContacts(AccountingProspect $prospect, array $validated): void
    {
        $prospect->contacts()->delete();

        if (($validated['type'] ?? null) !== AccountingProspect::TYPE_COMPANY) {
            return;
        }

        foreach ($validated['contacts'] ?? [] as $contact) {
            $hasContactValue = collect($contact)->filter(fn ($value) => filled($value))->isNotEmpty();

            if (! $hasContactValue || blank($contact['full_name'] ?? null)) {
                continue;
            }

            $prospect->contacts()->create([
                'full_name' => $contact['full_name'],
                'position' => $contact['position'] ?? null,
                'department' => $contact['department'] ?? null,
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
            ]);
        }
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

    private function ensureDefaultAccountingStockRecords(CompanySite $site): void
    {
        if (! in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true)) {
            return;
        }

        $warehouse = AccountingStockWarehouse::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'name' => 'Entrepot principal',
                'code' => 'DEP-DEFAULT',
                'status' => AccountingStockCategory::STATUS_ACTIVE,
            ]
        );

        $category = AccountingStockCategory::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'warehouse_id' => $warehouse->id,
                'created_by' => $site->responsible_id,
                'name' => 'Categorie generale',
                'description' => 'Categorie stock creee automatiquement par le systeme.',
                'status' => AccountingStockCategory::STATUS_ACTIVE,
            ]
        );

        if ((int) $category->warehouse_id !== (int) $warehouse->id) {
            $category->update(['warehouse_id' => $warehouse->id]);
        }

        AccountingStockSubcategory::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'category_id' => $category->id,
                'created_by' => $site->responsible_id,
                'name' => 'Sous-categorie generale',
                'description' => 'Sous-categorie stock creee automatiquement par le systeme.',
                'status' => AccountingStockCategory::STATUS_ACTIVE,
            ]
        );

        AccountingStockUnit::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'name' => 'Pièce',
                'symbol' => 'pc',
                'type' => AccountingStockUnit::TYPE_QUANTITY,
                'status' => AccountingStockCategory::STATUS_ACTIVE,
            ]
        );
    }

    private function isProtectedDefaultStockResource(string $resource, Model $record): bool
    {
        return in_array($resource, ['categories', 'subcategories', 'warehouses', 'units'], true)
            && (bool) data_get($record, 'is_default');
    }

    private function ensureDefaultAccountingServiceRecords(CompanySite $site): void
    {
        if (! in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true)) {
            return;
        }

        AccountingServiceUnit::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'name' => 'Forfait',
                'symbol' => 'forfait',
                'status' => AccountingServiceUnit::STATUS_ACTIVE,
            ]
        );

        $category = AccountingServiceCategory::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'name' => 'Services generaux',
                'description' => 'Categorie de services creee automatiquement par le systeme.',
                'status' => AccountingServiceCategory::STATUS_ACTIVE,
            ]
        );

        AccountingServiceSubcategory::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'category_id' => $category->id,
                'created_by' => $site->responsible_id,
                'name' => 'Prestations generales',
                'description' => 'Sous-categorie de services creee automatiquement par le systeme.',
                'status' => AccountingServiceSubcategory::STATUS_ACTIVE,
            ]
        );
    }

    private function ensureDefaultAccountingCurrencyRecord(CompanySite $site): void
    {
        if (! in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true)) {
            return;
        }

        $code = $site->currency ?: 'CDF';
        $currency = CurrencyCatalog::all()[$code] ?? null;
        $name = $currency['name_fr'] ?? $code;
        $symbol = $currency['symbol'] ?? null;

        $defaultCurrency = AccountingCurrency::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'code' => $code,
                'name' => $name,
                'symbol' => $symbol,
                'exchange_rate' => 1,
                'is_base' => true,
                'status' => AccountingCurrency::STATUS_ACTIVE,
            ]
        );

        if ($defaultCurrency->code !== $code) {
            $duplicate = AccountingCurrency::query()
                ->where('company_site_id', $site->id)
                ->where('code', $code)
                ->whereKeyNot($defaultCurrency->id)
                ->first();

            if ($duplicate) {
                $duplicate->delete();
            }
        }

        $defaultCurrency->forceFill([
            'code' => $code,
            'name' => $name,
            'symbol' => $symbol,
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ])->save();

        AccountingCurrency::query()
            ->where('company_site_id', $site->id)
            ->whereKeyNot($defaultCurrency->id)
            ->where('is_base', true)
            ->update(['is_base' => false]);
    }

    private function ensureDefaultAccountingPaymentMethodRecord(CompanySite $site): void
    {
        if (! in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true)) {
            return;
        }

        $method = AccountingPaymentMethod::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_system_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'name' => 'Espèces',
                'type' => AccountingPaymentMethod::TYPE_CASH,
                'currency_code' => $site->currency ?: 'CDF',
                'is_default' => true,
                'status' => AccountingPaymentMethod::STATUS_ACTIVE,
            ]
        );

        $method->forceFill([
            'name' => 'Espèces',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => $site->currency ?: 'CDF',
            'is_system_default' => true,
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
        ])->save();

        if (! AccountingPaymentMethod::query()->where('company_site_id', $site->id)->where('is_default', true)->exists()) {
            $method->forceFill(['is_default' => true])->save();
        }
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

    private function accountingProspectSourceLabels(): array
    {
        return [
            AccountingProspect::SOURCE_REFERRAL => __('main.prospect_source_referral'),
            AccountingProspect::SOURCE_WEBSITE => __('main.prospect_source_website'),
            AccountingProspect::SOURCE_CALL => __('main.prospect_source_call'),
            AccountingProspect::SOURCE_SOCIAL => __('main.prospect_source_social'),
            AccountingProspect::SOURCE_EVENT => __('main.prospect_source_event'),
            AccountingProspect::SOURCE_CAMPAIGN => __('main.prospect_source_campaign'),
            AccountingProspect::SOURCE_OTHER => __('main.prospect_source_other'),
        ];
    }

    private function accountingProspectStatusLabels(): array
    {
        return [
            AccountingProspect::STATUS_NEW => __('main.prospect_status_new'),
            AccountingProspect::STATUS_CONTACTED => __('main.prospect_status_contacted'),
            AccountingProspect::STATUS_QUALIFIED => __('main.prospect_status_qualified'),
            AccountingProspect::STATUS_PROPOSAL_SENT => __('main.prospect_status_proposal_sent'),
            AccountingProspect::STATUS_WON => __('main.prospect_status_won'),
            AccountingProspect::STATUS_LOST => __('main.prospect_status_lost'),
        ];
    }

    private function accountingProspectInterestLabels(): array
    {
        return [
            AccountingProspect::INTEREST_COLD => __('main.prospect_interest_cold'),
            AccountingProspect::INTEREST_WARM => __('main.prospect_interest_warm'),
            AccountingProspect::INTEREST_HOT => __('main.prospect_interest_hot'),
        ];
    }

    private function accountingCreditorTypeLabels(): array
    {
        return [
            AccountingCreditor::TYPE_SUPPLIER => __('main.creditor_type_supplier'),
            AccountingCreditor::TYPE_BANK => __('main.creditor_type_bank'),
            AccountingCreditor::TYPE_LANDLORD => __('main.creditor_type_landlord'),
            AccountingCreditor::TYPE_EMPLOYEE => __('main.creditor_type_employee'),
            AccountingCreditor::TYPE_TAX => __('main.creditor_type_tax'),
            AccountingCreditor::TYPE_PARTNER => __('main.creditor_type_partner'),
            AccountingCreditor::TYPE_LENDER => __('main.creditor_type_lender'),
            AccountingCreditor::TYPE_OTHER => __('main.creditor_type_other'),
        ];
    }

    private function accountingCreditorPriorityLabels(): array
    {
        return [
            AccountingCreditor::PRIORITY_NORMAL => __('main.priority_normal'),
            AccountingCreditor::PRIORITY_HIGH => __('main.priority_high'),
            AccountingCreditor::PRIORITY_URGENT => __('main.priority_urgent'),
        ];
    }

    private function accountingCreditorStatusLabels(): array
    {
        return [
            AccountingCreditor::STATUS_ACTIVE => __('main.active'),
            AccountingCreditor::STATUS_INACTIVE => __('main.inactive'),
            AccountingCreditor::STATUS_SETTLED => __('main.settled'),
        ];
    }

    private function accountingDebtorTypeLabels(): array
    {
        return [
            AccountingDebtor::TYPE_CLIENT => __('main.debtor_type_client'),
            AccountingDebtor::TYPE_EMPLOYEE => __('main.debtor_type_employee'),
            AccountingDebtor::TYPE_PARTNER => __('main.debtor_type_partner'),
            AccountingDebtor::TYPE_ASSOCIATE => __('main.debtor_type_associate'),
            AccountingDebtor::TYPE_ADVANCE => __('main.debtor_type_advance'),
            AccountingDebtor::TYPE_OTHER => __('main.debtor_type_other'),
        ];
    }

    private function accountingDebtorStatusLabels(): array
    {
        return [
            AccountingDebtor::STATUS_ACTIVE => __('main.active'),
            AccountingDebtor::STATUS_INACTIVE => __('main.inactive'),
            AccountingDebtor::STATUS_SETTLED => __('main.settled'),
        ];
    }

    private function accountingPartnerTypeLabels(): array
    {
        return [
            AccountingPartner::TYPE_BUSINESS_REFERRER => __('main.partner_type_business_referrer'),
            AccountingPartner::TYPE_DISTRIBUTOR => __('main.partner_type_distributor'),
            AccountingPartner::TYPE_SUBCONTRACTOR => __('main.partner_type_subcontractor'),
            AccountingPartner::TYPE_CONSULTING_FIRM => __('main.partner_type_consulting_firm'),
            AccountingPartner::TYPE_INSTITUTION => __('main.partner_type_institution'),
            AccountingPartner::TYPE_BANK => __('main.partner_type_bank'),
            AccountingPartner::TYPE_AGENCY => __('main.partner_type_agency'),
            AccountingPartner::TYPE_OTHER => __('main.partner_type_other'),
        ];
    }

    private function accountingPartnerStatusLabels(): array
    {
        return [
            AccountingPartner::STATUS_ACTIVE => __('main.partner_status_active'),
            AccountingPartner::STATUS_DISCUSSION => __('main.partner_status_discussion'),
            AccountingPartner::STATUS_SUSPENDED => __('main.partner_status_suspended'),
            AccountingPartner::STATUS_ENDED => __('main.partner_status_ended'),
        ];
    }

    private function accountingSalesRepresentativeTypeLabels(): array
    {
        return [
            AccountingSalesRepresentative::TYPE_INTERNAL => __('main.sales_representative_type_internal'),
            AccountingSalesRepresentative::TYPE_EXTERNAL => __('main.sales_representative_type_external'),
            AccountingSalesRepresentative::TYPE_INDEPENDENT_AGENT => __('main.sales_representative_type_independent_agent'),
            AccountingSalesRepresentative::TYPE_RESELLER => __('main.sales_representative_type_reseller'),
            AccountingSalesRepresentative::TYPE_BUSINESS_REFERRER => __('main.sales_representative_type_business_referrer'),
        ];
    }

    private function accountingSalesRepresentativeStatusLabels(): array
    {
        return [
            AccountingSalesRepresentative::STATUS_ACTIVE => __('main.active'),
            AccountingSalesRepresentative::STATUS_SUSPENDED => __('main.suspended'),
            AccountingSalesRepresentative::STATUS_INACTIVE => __('main.inactive'),
        ];
    }

    private function accountingStockResourceConfig(CompanySite $site, string $resource): ?array
    {
        $statusOptions = [
            'active' => __('main.active'),
            'inactive' => __('main.inactive'),
        ];
        $draftStatusOptions = [
            AccountingStockTransfer::STATUS_DRAFT => __('main.stock_status_draft'),
            AccountingStockTransfer::STATUS_VALIDATED => __('main.stock_status_validated'),
            AccountingStockTransfer::STATUS_CANCELLED => __('main.stock_status_cancelled'),
        ];
        $categoryOptions = $this->stockSelectOptions(AccountingStockCategory::class, $site, 'name');
        $subcategoryOptions = $this->stockSelectOptions(AccountingStockSubcategory::class, $site, 'name');
        $categoryOptionAttributes = AccountingStockCategory::query()
            ->where('company_site_id', $site->id)
            ->get()
            ->mapWithKeys(fn (AccountingStockCategory $category) => [
                $category->id => ['data-warehouse-id' => $category->warehouse_id],
            ])
            ->all();
        $subcategoryOptionAttributes = AccountingStockSubcategory::query()
            ->where('company_site_id', $site->id)
            ->get()
            ->mapWithKeys(fn (AccountingStockSubcategory $subcategory) => [
                $subcategory->id => ['data-category-id' => $subcategory->category_id],
            ])
            ->all();
        $unitOptions = $this->stockSelectOptions(AccountingStockUnit::class, $site, 'name', 'symbol');
        $warehouseOptions = $this->stockSelectOptions(AccountingStockWarehouse::class, $site, 'name', 'code');
        $itemOptions = $this->stockSelectOptions(AccountingStockItem::class, $site, 'name', 'reference');
        $batchOptions = $this->stockSelectOptions(AccountingStockBatch::class, $site, 'batch_number', 'reference');
        $currencyOptions = $this->siteCurrencyOptions($site);

        return [
            'categories' => [
                'model' => AccountingStockCategory::class,
                'relation' => 'accountingStockCategories',
                'title' => __('main.categories'),
                'singular_lower' => __('main.stock_category_lower'),
                'subtitle' => __('main.stock_categories_subtitle'),
                'new_label' => __('main.new_stock_category'),
                'edit_label' => __('main.edit_stock_category'),
                'icon' => 'bi-folder',
                'active' => 'stock-categories',
                'empty' => __('main.no_stock_categories'),
                'relations' => ['warehouse', 'items.unit', 'items.subcategory'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'warehouse.name', 'label' => __('main.warehouse')],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'warehouse_id', 'label' => __('main.warehouse'), 'type' => 'select', 'required' => true, 'options' => $warehouseOptions],
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'description', 'label' => __('main.description'), 'type' => 'textarea'],
                ],
            ],
            'subcategories' => [
                'model' => AccountingStockSubcategory::class,
                'relation' => 'accountingStockSubcategories',
                'title' => __('main.subcategories'),
                'singular_lower' => __('main.stock_subcategory_lower'),
                'subtitle' => __('main.stock_subcategories_subtitle'),
                'new_label' => __('main.new_stock_subcategory'),
                'edit_label' => __('main.edit_stock_subcategory'),
                'icon' => 'bi-tags',
                'active' => 'stock-subcategories',
                'empty' => __('main.no_stock_subcategories'),
                'relations' => ['category', 'items.unit'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'category.name', 'label' => __('main.category')],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'category_id', 'label' => __('main.category'), 'type' => 'select', 'required' => true, 'options' => $categoryOptions],
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'description', 'label' => __('main.description'), 'type' => 'textarea'],
                ],
            ],
            'units' => [
                'model' => AccountingStockUnit::class,
                'relation' => 'accountingStockUnits',
                'title' => __('main.stock_units'),
                'singular_lower' => __('main.stock_unit_lower'),
                'subtitle' => __('main.stock_units_subtitle'),
                'new_label' => __('main.new_stock_unit'),
                'edit_label' => __('main.edit_stock_unit'),
                'icon' => 'bi-rulers',
                'active' => 'stock-units',
                'empty' => __('main.no_stock_units'),
                'relations' => ['services.unit', 'services.subcategory'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'symbol', 'label' => __('main.symbol')],
                    ['key' => 'type', 'label' => __('main.type'), 'type' => 'unit_type'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'symbol', 'label' => __('main.symbol'), 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => __('main.type'), 'type' => 'select', 'required' => true, 'options' => $this->stockUnitTypeLabels(), 'default' => AccountingStockUnit::TYPE_UNIT],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                ],
            ],
            'warehouses' => [
                'model' => AccountingStockWarehouse::class,
                'relation' => 'accountingStockWarehouses',
                'title' => __('main.stock_warehouses'),
                'singular_lower' => __('main.stock_warehouse_lower'),
                'subtitle' => __('main.stock_warehouses_subtitle'),
                'new_label' => __('main.new_stock_warehouse'),
                'edit_label' => __('main.edit_stock_warehouse'),
                'icon' => 'bi-buildings',
                'active' => 'stock-warehouses',
                'empty' => __('main.no_stock_warehouses'),
                'relations' => [],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'code', 'label' => __('main.code')],
                    ['key' => 'manager_name', 'label' => __('main.manager')],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => __('main.code'), 'type' => 'text'],
                    ['name' => 'manager_name', 'label' => __('main.manager'), 'type' => 'text'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'address', 'label' => __('main.address'), 'type' => 'textarea'],
                ],
            ],
            'items' => [
                'model' => AccountingStockItem::class,
                'relation' => 'accountingStockItems',
                'title' => __('main.items'),
                'singular_lower' => __('main.stock_item_lower'),
                'subtitle' => __('main.stock_items_subtitle'),
                'new_label' => __('main.new_stock_item'),
                'edit_label' => __('main.edit_stock_item'),
                'icon' => 'bi-box',
                'active' => 'stock-items',
                'empty' => __('main.no_stock_items'),
                'relations' => ['category', 'subcategory', 'unit', 'defaultWarehouse'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.item')],
                    ['key' => 'category.name', 'label' => __('main.category')],
                    ['key' => 'current_stock', 'label' => __('main.current_stock'), 'type' => 'number'],
                    ['key' => 'sale_price', 'label' => __('main.sale_price'), 'type' => 'money'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'category_id', 'label' => __('main.category'), 'type' => 'select', 'required' => true, 'options' => $categoryOptions, 'option_attributes' => $categoryOptionAttributes],
                    ['name' => 'subcategory_id', 'label' => __('main.subcategory'), 'type' => 'select', 'options' => $subcategoryOptions, 'option_attributes' => $subcategoryOptionAttributes],
                    ['name' => 'unit_id', 'label' => __('main.stock_unit'), 'type' => 'select', 'required' => true, 'options' => $unitOptions],
                    ['name' => 'default_warehouse_id', 'label' => __('main.default_warehouse'), 'type' => 'select', 'options' => $warehouseOptions],
                    ['name' => 'name', 'label' => __('main.item_name'), 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => __('main.type'), 'type' => 'select', 'required' => true, 'options' => $this->stockItemTypeLabels(), 'default' => AccountingStockItem::TYPE_PRODUCT],
                    ['name' => 'sku', 'label' => __('main.sku'), 'type' => 'text'],
                    ['name' => 'barcode', 'label' => __('main.barcode'), 'type' => 'text'],
                    ['name' => 'currency', 'label' => __('admin.currency'), 'type' => 'select', 'required' => true, 'options' => $currencyOptions, 'default' => $site->currency ?: 'CDF'],
                    ['name' => 'purchase_price', 'label' => __('main.purchase_price'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'sale_price', 'label' => __('main.sale_price'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'current_stock', 'label' => __('main.current_stock'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'min_stock', 'label' => __('main.min_stock'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'description', 'label' => __('main.description'), 'type' => 'textarea'],
                ],
            ],
            'movements' => [
                'model' => AccountingStockMovement::class,
                'relation' => 'accountingStockMovements',
                'title' => __('main.stock_movements'),
                'singular_lower' => __('main.stock_movement_lower'),
                'subtitle' => __('main.stock_movements_subtitle'),
                'new_label' => __('main.new_stock_movement'),
                'edit_label' => __('main.edit_stock_movement'),
                'icon' => 'bi-arrow-left-right',
                'active' => 'stock-movements',
                'empty' => __('main.no_stock_movements'),
                'relations' => ['item', 'warehouse'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'item.name', 'label' => __('main.item')],
                    ['key' => 'warehouse.name', 'label' => __('main.warehouse')],
                    ['key' => 'type', 'label' => __('main.type'), 'type' => 'movement_type'],
                    ['key' => 'quantity', 'label' => __('main.quantity'), 'type' => 'number'],
                    ['key' => 'movement_date', 'label' => __('main.date'), 'type' => 'date'],
                ],
                'fields' => [
                    ['name' => 'item_id', 'label' => __('main.item'), 'type' => 'select', 'required' => true, 'options' => $itemOptions],
                    ['name' => 'warehouse_id', 'label' => __('main.warehouse'), 'type' => 'select', 'required' => true, 'options' => $warehouseOptions],
                    ['name' => 'batch_id', 'label' => __('main.batch'), 'type' => 'select', 'options' => $batchOptions],
                    ['name' => 'type', 'label' => __('main.type'), 'type' => 'select', 'required' => true, 'options' => $this->stockMovementTypeLabels(), 'default' => AccountingStockMovement::TYPE_ENTRY],
                    ['name' => 'quantity', 'label' => __('main.quantity'), 'type' => 'number', 'required' => true, 'default' => '0'],
                    ['name' => 'movement_date', 'label' => __('main.date'), 'type' => 'date'],
                    ['name' => 'reason', 'label' => __('main.reason'), 'type' => 'text'],
                    ['name' => 'notes', 'label' => __('main.notes'), 'type' => 'textarea'],
                ],
            ],
            'inventories' => [
                'model' => AccountingStockInventory::class,
                'relation' => 'accountingStockInventories',
                'title' => __('main.stock_inventories'),
                'singular_lower' => __('main.stock_inventory_lower'),
                'subtitle' => __('main.stock_inventories_subtitle'),
                'new_label' => __('main.new_stock_inventory'),
                'edit_label' => __('main.edit_stock_inventory'),
                'icon' => 'bi-clipboard-check',
                'active' => 'stock-inventories',
                'empty' => __('main.no_stock_inventories'),
                'relations' => ['warehouse'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'warehouse.name', 'label' => __('main.warehouse')],
                    ['key' => 'counted_at', 'label' => __('main.date'), 'type' => 'date'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'draft_status'],
                ],
                'fields' => [
                    ['name' => 'warehouse_id', 'label' => __('main.warehouse'), 'type' => 'select', 'required' => true, 'options' => $warehouseOptions],
                    ['name' => 'counted_at', 'label' => __('main.date'), 'type' => 'date'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $draftStatusOptions, 'default' => AccountingStockInventory::STATUS_DRAFT],
                    ['name' => 'notes', 'label' => __('main.notes'), 'type' => 'textarea'],
                ],
            ],
            'alerts' => [
                'model' => AccountingStockAlert::class,
                'relation' => 'accountingStockAlerts',
                'title' => __('main.stock_alerts'),
                'singular_lower' => __('main.stock_alert_lower'),
                'subtitle' => __('main.stock_alerts_subtitle'),
                'new_label' => __('main.new_stock_alert'),
                'edit_label' => __('main.edit_stock_alert'),
                'icon' => 'bi-bell',
                'active' => 'stock-alerts',
                'empty' => __('main.no_stock_alerts'),
                'relations' => ['item', 'warehouse'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'item.name', 'label' => __('main.item')],
                    ['key' => 'warehouse.name', 'label' => __('main.warehouse')],
                    ['key' => 'type', 'label' => __('main.type'), 'type' => 'alert_type'],
                    ['key' => 'threshold_quantity', 'label' => __('main.threshold_quantity'), 'type' => 'number'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'item_id', 'label' => __('main.item'), 'type' => 'select', 'required' => true, 'options' => $itemOptions],
                    ['name' => 'warehouse_id', 'label' => __('main.warehouse'), 'type' => 'select', 'options' => $warehouseOptions],
                    ['name' => 'type', 'label' => __('main.type'), 'type' => 'select', 'required' => true, 'options' => $this->stockAlertTypeLabels(), 'default' => AccountingStockAlert::TYPE_LOW_STOCK],
                    ['name' => 'threshold_quantity', 'label' => __('main.threshold_quantity'), 'type' => 'number', 'required' => true, 'default' => '0'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'notes', 'label' => __('main.notes'), 'type' => 'textarea'],
                ],
            ],
            'batches' => [
                'model' => AccountingStockBatch::class,
                'relation' => 'accountingStockBatches',
                'title' => __('main.stock_batches'),
                'singular_lower' => __('main.stock_batch_lower'),
                'subtitle' => __('main.stock_batches_subtitle'),
                'new_label' => __('main.new_stock_batch'),
                'edit_label' => __('main.edit_stock_batch'),
                'icon' => 'bi-upc-scan',
                'active' => 'stock-batches',
                'empty' => __('main.no_stock_batches'),
                'relations' => ['item', 'warehouse'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'item.name', 'label' => __('main.item')],
                    ['key' => 'warehouse.name', 'label' => __('main.warehouse')],
                    ['key' => 'batch_number', 'label' => __('main.batch_number')],
                    ['key' => 'expires_at', 'label' => __('main.expiration_date'), 'type' => 'date'],
                    ['key' => 'quantity', 'label' => __('main.quantity'), 'type' => 'number'],
                ],
                'fields' => [
                    ['name' => 'item_id', 'label' => __('main.item'), 'type' => 'select', 'required' => true, 'options' => $itemOptions],
                    ['name' => 'warehouse_id', 'label' => __('main.warehouse'), 'type' => 'select', 'required' => true, 'options' => $warehouseOptions],
                    ['name' => 'batch_number', 'label' => __('main.batch_number'), 'type' => 'text'],
                    ['name' => 'serial_number', 'label' => __('main.serial_number'), 'type' => 'text'],
                    ['name' => 'expires_at', 'label' => __('main.expiration_date'), 'type' => 'date'],
                    ['name' => 'quantity', 'label' => __('main.quantity'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                ],
            ],
            'transfers' => [
                'model' => AccountingStockTransfer::class,
                'relation' => 'accountingStockTransfers',
                'title' => __('main.stock_transfers'),
                'singular_lower' => __('main.stock_transfer_lower'),
                'subtitle' => __('main.stock_transfers_subtitle'),
                'new_label' => __('main.new_stock_transfer'),
                'edit_label' => __('main.edit_stock_transfer'),
                'icon' => 'bi-truck',
                'active' => 'stock-transfers',
                'empty' => __('main.no_stock_transfers'),
                'relations' => ['item', 'fromWarehouse', 'toWarehouse'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'item.name', 'label' => __('main.item')],
                    ['key' => 'fromWarehouse.name', 'label' => __('main.from_warehouse')],
                    ['key' => 'toWarehouse.name', 'label' => __('main.to_warehouse')],
                    ['key' => 'quantity', 'label' => __('main.quantity'), 'type' => 'number'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'draft_status'],
                ],
                'fields' => [
                    ['name' => 'item_id', 'label' => __('main.item'), 'type' => 'select', 'required' => true, 'options' => $itemOptions],
                    ['name' => 'from_warehouse_id', 'label' => __('main.from_warehouse'), 'type' => 'select', 'required' => true, 'options' => $warehouseOptions],
                    ['name' => 'to_warehouse_id', 'label' => __('main.to_warehouse'), 'type' => 'select', 'required' => true, 'options' => $warehouseOptions],
                    ['name' => 'quantity', 'label' => __('main.quantity'), 'type' => 'number', 'required' => true, 'default' => '0'],
                    ['name' => 'transfer_date', 'label' => __('main.date'), 'type' => 'date'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $draftStatusOptions, 'default' => AccountingStockTransfer::STATUS_DRAFT],
                    ['name' => 'notes', 'label' => __('main.notes'), 'type' => 'textarea'],
                ],
            ],
        ][$resource] ?? null;
    }

    private function stockSelectOptions(string $model, CompanySite $site, string $labelColumn, ?string $secondaryColumn = null): array
    {
        return $model::query()
            ->where('company_site_id', $site->id)
            ->orderBy($labelColumn)
            ->get()
            ->mapWithKeys(function (Model $record) use ($labelColumn, $secondaryColumn) {
                $label = (string) data_get($record, $labelColumn);
                $secondary = $secondaryColumn ? data_get($record, $secondaryColumn) : null;

                return [$record->id => filled($secondary) ? "{$label} ({$secondary})" : $label];
            })
            ->all();
    }

    private function accountingStockRules(CompanySite $site, string $resource, array $config): array
    {
        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);
        $base = [
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'record_id' => ['nullable', 'integer'],
        ];

        return array_merge($base, match ($resource) {
            'categories' => [
                'warehouse_id' => ['required', 'integer', $existsForSite('accounting_stock_warehouses')],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'subcategories' => [
                'category_id' => ['required', 'integer', $existsForSite('accounting_stock_categories')],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'units' => [
                'name' => ['required', 'string', 'max:255'],
                'symbol' => ['required', 'string', 'max:20'],
                'type' => ['required', Rule::in(AccountingStockUnit::types())],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'warehouses' => [
                'name' => ['required', 'string', 'max:255'],
                'code' => ['nullable', 'string', 'max:60'],
                'manager_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'items' => [
                'category_id' => ['required', 'integer', $existsForSite('accounting_stock_categories')],
                'subcategory_id' => ['nullable', 'integer', $existsForSite('accounting_stock_subcategories')],
                'unit_id' => ['required', 'integer', $existsForSite('accounting_stock_units')],
                'default_warehouse_id' => ['nullable', 'integer', $existsForSite('accounting_stock_warehouses')],
                'sku' => ['nullable', 'string', 'max:255'],
                'barcode' => ['nullable', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'type' => ['required', Rule::in(AccountingStockItem::types())],
                'purchase_price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['required', 'numeric', 'min:0'],
                'current_stock' => ['required', 'numeric', 'min:0'],
                'min_stock' => ['required', 'numeric', 'min:0'],
                'currency' => [
                    'required',
                    Rule::exists('accounting_currencies', 'code')
                        ->where('company_site_id', $site->id)
                        ->where('status', AccountingCurrency::STATUS_ACTIVE),
                ],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'description' => ['nullable', 'string'],
            ],
            'movements' => [
                'item_id' => ['required', 'integer', $existsForSite('accounting_stock_items')],
                'warehouse_id' => ['required', 'integer', $existsForSite('accounting_stock_warehouses')],
                'batch_id' => ['nullable', 'integer', $existsForSite('accounting_stock_batches')],
                'type' => ['required', Rule::in(AccountingStockMovement::types())],
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'movement_date' => ['nullable', 'date'],
                'reason' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
            ],
            'inventories' => [
                'warehouse_id' => ['required', 'integer', $existsForSite('accounting_stock_warehouses')],
                'counted_at' => ['nullable', 'date'],
                'status' => ['required', Rule::in(AccountingStockInventory::statuses())],
                'notes' => ['nullable', 'string'],
            ],
            'alerts' => [
                'item_id' => ['required', 'integer', $existsForSite('accounting_stock_items')],
                'warehouse_id' => ['nullable', 'integer', $existsForSite('accounting_stock_warehouses')],
                'type' => ['required', Rule::in(AccountingStockAlert::types())],
                'threshold_quantity' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'notes' => ['nullable', 'string'],
            ],
            'batches' => [
                'item_id' => ['required', 'integer', $existsForSite('accounting_stock_items')],
                'warehouse_id' => ['required', 'integer', $existsForSite('accounting_stock_warehouses')],
                'batch_number' => ['nullable', 'string', 'max:255'],
                'serial_number' => ['nullable', 'string', 'max:255'],
                'expires_at' => ['nullable', 'date'],
                'quantity' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'transfers' => [
                'item_id' => ['required', 'integer', $existsForSite('accounting_stock_items')],
                'from_warehouse_id' => ['required', 'integer', $existsForSite('accounting_stock_warehouses'), 'different:to_warehouse_id'],
                'to_warehouse_id' => ['required', 'integer', $existsForSite('accounting_stock_warehouses')],
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'transfer_date' => ['nullable', 'date'],
                'status' => ['required', Rule::in(AccountingStockTransfer::statuses())],
                'notes' => ['nullable', 'string'],
            ],
            default => [],
        });
    }

    private function accountingStockPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        unset($validated['form_mode'], $validated['record_id']);

        if ($withCreator) {
            $validated['created_by'] = $user->id;
        }

        return $validated;
    }

    private function afterAccountingStockSaved(string $resource, Model $record, ?Model $original): void
    {
        if ($resource === 'movements' && $record instanceof AccountingStockMovement) {
            if ($original instanceof AccountingStockMovement) {
                $this->reverseAccountingStockMovement($original);
            }

            $this->applyAccountingStockMovement($record);
        }
    }

    private function applyAccountingStockMovement(AccountingStockMovement $movement): void
    {
        $item = AccountingStockItem::query()->find($movement->item_id);

        if (! $item) {
            return;
        }

        if ($movement->type === AccountingStockMovement::TYPE_ENTRY) {
            $item->increment('current_stock', $movement->quantity);
            return;
        }

        if ($movement->type === AccountingStockMovement::TYPE_EXIT) {
            $item->update(['current_stock' => max(0, (float) $item->current_stock - (float) $movement->quantity)]);
            return;
        }

        $item->update(['current_stock' => $movement->quantity]);
    }

    private function reverseAccountingStockMovement(AccountingStockMovement $movement): void
    {
        $item = AccountingStockItem::query()->find($movement->item_id);

        if (! $item) {
            return;
        }

        if ($movement->type === AccountingStockMovement::TYPE_ENTRY) {
            $item->update(['current_stock' => max(0, (float) $item->current_stock - (float) $movement->quantity)]);
            return;
        }

        if ($movement->type === AccountingStockMovement::TYPE_EXIT) {
            $item->increment('current_stock', $movement->quantity);
        }
    }

    private function stockUnitTypeLabels(): array
    {
        return [
            AccountingStockUnit::TYPE_UNIT => __('main.stock_unit_type_unit'),
            AccountingStockUnit::TYPE_WEIGHT => __('main.stock_unit_type_weight'),
            AccountingStockUnit::TYPE_VOLUME => __('main.stock_unit_type_volume'),
            AccountingStockUnit::TYPE_LENGTH => __('main.stock_unit_type_length'),
            AccountingStockUnit::TYPE_PACKAGE => __('main.stock_unit_type_package'),
            AccountingStockUnit::TYPE_QUANTITY => __('main.stock_unit_type_quantity'),
        ];
    }

    private function stockItemTypeLabels(): array
    {
        return [
            AccountingStockItem::TYPE_PRODUCT => __('main.stock_item_type_product'),
            AccountingStockItem::TYPE_CONSUMABLE => __('main.stock_item_type_consumable'),
            AccountingStockItem::TYPE_SERVICE_ITEM => __('main.stock_item_type_service_item'),
        ];
    }

    private function stockMovementTypeLabels(): array
    {
        return [
            AccountingStockMovement::TYPE_ENTRY => __('main.stock_movement_type_entry'),
            AccountingStockMovement::TYPE_EXIT => __('main.stock_movement_type_exit'),
            AccountingStockMovement::TYPE_ADJUSTMENT => __('main.stock_movement_type_adjustment'),
        ];
    }

    private function stockAlertTypeLabels(): array
    {
        return [
            AccountingStockAlert::TYPE_LOW_STOCK => __('main.stock_alert_type_low_stock'),
            AccountingStockAlert::TYPE_OVERSTOCK => __('main.stock_alert_type_overstock'),
            AccountingStockAlert::TYPE_EXPIRATION => __('main.stock_alert_type_expiration'),
        ];
    }

    private function accountingServiceResourceConfig(CompanySite $site, string $resource): ?array
    {
        $statusOptions = ['active' => __('main.active'), 'inactive' => __('main.inactive')];
        $categoryOptions = $this->serviceSelectOptions(AccountingServiceCategory::class, $site, 'name');
        $subcategoryOptions = $this->serviceSelectOptions(AccountingServiceSubcategory::class, $site, 'name');
        $subcategoryOptionAttributes = AccountingServiceSubcategory::query()
            ->where('company_site_id', $site->id)
            ->get()
            ->mapWithKeys(fn (AccountingServiceSubcategory $subcategory) => [
                $subcategory->id => ['data-category-id' => $subcategory->category_id],
            ])
            ->all();
        $unitOptions = $this->serviceSelectOptions(AccountingServiceUnit::class, $site, 'name', 'symbol');
        $serviceOptions = $this->serviceSelectOptions(AccountingService::class, $site, 'name', 'reference');
        $currencyOptions = $this->siteCurrencyOptions($site);

        return [
            'price-list' => [
                'model' => AccountingService::class,
                'relation' => 'accountingServices',
                'title' => __('main.price_list'),
                'singular_lower' => __('main.service_lower'),
                'subtitle' => __('main.services_price_list_subtitle'),
                'new_label' => __('main.new_service'),
                'edit_label' => __('main.edit_service'),
                'icon' => 'bi-card-list',
                'active' => 'service-price-list',
                'empty' => __('main.no_services'),
                'relations' => ['category', 'subcategory', 'unit'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.service')],
                    ['key' => 'category.name', 'label' => __('main.category')],
                    ['key' => 'billing_type', 'label' => __('main.billing_type'), 'type' => 'service_billing'],
                    ['key' => 'price', 'label' => __('main.sale_price'), 'type' => 'money'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'category_id', 'label' => __('main.category'), 'type' => 'select', 'required' => true, 'options' => $categoryOptions],
                    ['name' => 'subcategory_id', 'label' => __('main.subcategory'), 'type' => 'select', 'options' => $subcategoryOptions, 'option_attributes' => $subcategoryOptionAttributes],
                    ['name' => 'unit_id', 'label' => __('main.service_unit'), 'type' => 'select', 'required' => true, 'options' => $unitOptions],
                    ['name' => 'name', 'label' => __('main.service_name'), 'type' => 'text', 'required' => true],
                    ['name' => 'billing_type', 'label' => __('main.billing_type'), 'type' => 'select', 'required' => true, 'options' => $this->serviceBillingTypeLabels(), 'default' => AccountingService::BILLING_FIXED],
                    ['name' => 'currency', 'label' => __('admin.currency'), 'type' => 'select', 'required' => true, 'options' => $currencyOptions, 'default' => $site->currency ?: 'CDF'],
                    ['name' => 'price', 'label' => __('main.sale_price'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'tax_rate', 'label' => __('main.tax_rate_percent'), 'type' => 'number', 'default' => '0'],
                    ['name' => 'estimated_duration', 'label' => __('main.estimated_duration_minutes'), 'type' => 'number'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'description', 'label' => __('main.description'), 'type' => 'textarea'],
                    ['name' => 'internal_notes', 'label' => __('main.internal_notes'), 'type' => 'textarea'],
                ],
            ],
            'categories' => [
                'model' => AccountingServiceCategory::class,
                'relation' => 'accountingServiceCategories',
                'title' => __('main.service_categories'),
                'singular_lower' => __('main.service_category_lower'),
                'subtitle' => __('main.service_categories_subtitle'),
                'new_label' => __('main.new_service_category'),
                'edit_label' => __('main.edit_service_category'),
                'icon' => 'bi-folder',
                'active' => 'service-categories',
                'empty' => __('main.no_service_categories'),
                'relations' => [],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'description', 'label' => __('main.description'), 'type' => 'textarea'],
                ],
            ],
            'subcategories' => [
                'model' => AccountingServiceSubcategory::class,
                'relation' => 'accountingServiceSubcategories',
                'title' => __('main.service_subcategories'),
                'singular_lower' => __('main.service_subcategory_lower'),
                'subtitle' => __('main.service_subcategories_subtitle'),
                'new_label' => __('main.new_service_subcategory'),
                'edit_label' => __('main.edit_service_subcategory'),
                'icon' => 'bi-tags',
                'active' => 'service-subcategories',
                'empty' => __('main.no_service_subcategories'),
                'relations' => ['category', 'services.unit'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'category.name', 'label' => __('main.category')],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'category_id', 'label' => __('main.category'), 'type' => 'select', 'required' => true, 'options' => $categoryOptions],
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'description', 'label' => __('main.description'), 'type' => 'textarea'],
                ],
            ],
            'units' => [
                'model' => AccountingServiceUnit::class,
                'relation' => 'accountingServiceUnits',
                'title' => __('main.service_units'),
                'singular_lower' => __('main.service_unit_lower'),
                'subtitle' => __('main.service_units_subtitle'),
                'new_label' => __('main.new_service_unit'),
                'edit_label' => __('main.edit_service_unit'),
                'icon' => 'bi-rulers',
                'active' => 'service-units',
                'empty' => __('main.no_service_units'),
                'relations' => [],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'symbol', 'label' => __('main.symbol')],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'symbol', 'label' => __('main.symbol'), 'type' => 'text', 'required' => true],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                ],
            ],
            'recurring' => [
                'model' => AccountingRecurringService::class,
                'relation' => 'accountingRecurringServices',
                'title' => __('main.recurring_services'),
                'singular_lower' => __('main.recurring_service_lower'),
                'subtitle' => __('main.recurring_services_subtitle'),
                'new_label' => __('main.new_recurring_service'),
                'edit_label' => __('main.edit_recurring_service'),
                'icon' => 'bi-arrow-repeat',
                'active' => 'service-recurring',
                'empty' => __('main.no_recurring_services'),
                'relations' => ['service'],
                'columns' => [
                    ['key' => 'reference', 'label' => __('main.reference')],
                    ['key' => 'name', 'label' => __('main.name')],
                    ['key' => 'service.name', 'label' => __('main.service')],
                    ['key' => 'frequency', 'label' => __('main.frequency'), 'type' => 'service_frequency'],
                    ['key' => 'next_invoice_date', 'label' => __('main.next_invoice_date'), 'type' => 'date'],
                    ['key' => 'status', 'label' => __('main.status'), 'type' => 'status'],
                ],
                'fields' => [
                    ['name' => 'service_id', 'label' => __('main.service'), 'type' => 'select', 'required' => true, 'options' => $serviceOptions],
                    ['name' => 'name', 'label' => __('main.name'), 'type' => 'text', 'required' => true],
                    ['name' => 'frequency', 'label' => __('main.frequency'), 'type' => 'select', 'required' => true, 'options' => $this->serviceFrequencyLabels(), 'default' => AccountingRecurringService::FREQUENCY_MONTHLY],
                    ['name' => 'start_date', 'label' => __('main.start_date'), 'type' => 'date'],
                    ['name' => 'next_invoice_date', 'label' => __('main.next_invoice_date'), 'type' => 'date'],
                    ['name' => 'status', 'label' => __('main.status'), 'type' => 'select', 'required' => true, 'options' => $statusOptions, 'default' => 'active'],
                    ['name' => 'notes', 'label' => __('main.notes'), 'type' => 'textarea'],
                ],
            ],
        ][$resource] ?? null;
    }

    private function serviceSelectOptions(string $model, CompanySite $site, string $labelColumn, ?string $secondaryColumn = null): array
    {
        $query = $model::query()
            ->where('company_site_id', $site->id);

        if (in_array($model, [AccountingServiceCategory::class, AccountingServiceSubcategory::class, AccountingServiceUnit::class], true)) {
            $query->orderByDesc('is_default');
        }

        return $query
            ->orderBy($labelColumn)
            ->get()
            ->mapWithKeys(function (Model $record) use ($labelColumn, $secondaryColumn) {
                $label = (string) data_get($record, $labelColumn);
                $secondary = $secondaryColumn ? data_get($record, $secondaryColumn) : null;

                return [$record->id => filled($secondary) ? "{$label} ({$secondary})" : $label];
            })
            ->all();
    }

    private function accountingServiceRules(CompanySite $site, string $resource): array
    {
        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);
        $base = [
            'form_mode' => ['nullable', Rule::in(['create', 'edit'])],
            'record_id' => ['nullable', 'integer'],
        ];

        return array_merge($base, match ($resource) {
            'price-list' => [
                'category_id' => ['required', 'integer', $existsForSite('accounting_service_categories')],
                'subcategory_id' => ['nullable', 'integer', $existsForSite('accounting_service_subcategories')],
                'unit_id' => ['required', 'integer', $existsForSite('accounting_service_units')],
                'name' => ['required', 'string', 'max:255'],
                'billing_type' => ['required', Rule::in(AccountingService::billingTypes())],
                'price' => ['required', 'numeric', 'min:0'],
                'currency' => [
                    'required',
                    Rule::exists('accounting_currencies', 'code')
                        ->where('company_site_id', $site->id)
                        ->where('status', AccountingCurrency::STATUS_ACTIVE),
                ],
                'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
                'estimated_duration' => ['nullable', 'integer', 'min:0'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'description' => ['nullable', 'string'],
                'internal_notes' => ['nullable', 'string'],
            ],
            'categories' => [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'subcategories' => [
                'category_id' => ['required', 'integer', $existsForSite('accounting_service_categories')],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'units' => [
                'name' => ['required', 'string', 'max:255'],
                'symbol' => ['required', 'string', 'max:30'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'recurring' => [
                'service_id' => ['required', 'integer', $existsForSite('accounting_services')],
                'name' => ['required', 'string', 'max:255'],
                'frequency' => ['required', Rule::in(AccountingRecurringService::frequencies())],
                'start_date' => ['nullable', 'date'],
                'next_invoice_date' => ['nullable', 'date'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'notes' => ['nullable', 'string'],
            ],
            default => [],
        });
    }

    private function accountingServicePayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        unset($validated['form_mode'], $validated['record_id']);

        if ($withCreator) {
            $validated['created_by'] = $user->id;
        }

        return $validated;
    }

    private function isProtectedDefaultServiceResource(string $resource, Model $record): bool
    {
        return in_array($resource, ['categories', 'subcategories', 'units'], true)
            && (bool) data_get($record, 'is_default');
    }

    private function serviceBillingTypeLabels(): array
    {
        return [
            AccountingService::BILLING_FIXED => __('main.service_billing_fixed'),
            AccountingService::BILLING_HOURLY => __('main.service_billing_hourly'),
            AccountingService::BILLING_DAILY => __('main.service_billing_daily'),
            AccountingService::BILLING_MONTHLY => __('main.service_billing_monthly'),
            AccountingService::BILLING_YEARLY => __('main.service_billing_yearly'),
        ];
    }

    private function serviceFrequencyLabels(): array
    {
        return [
            AccountingRecurringService::FREQUENCY_MONTHLY => __('main.frequency_monthly'),
            AccountingRecurringService::FREQUENCY_QUARTERLY => __('main.frequency_quarterly'),
            AccountingRecurringService::FREQUENCY_YEARLY => __('main.frequency_yearly'),
        ];
    }

    private function accountingCurrencyRules(CompanySite $site, ?AccountingCurrency $currency = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                Rule::in(array_keys(CurrencyCatalog::all())),
                Rule::unique('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->ignore($currency?->id),
            ],
            'exchange_rate' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in([AccountingCurrency::STATUS_ACTIVE, AccountingCurrency::STATUS_INACTIVE])],
        ];
    }

    private function accountingCurrencyPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $currency = CurrencyCatalog::all()[$validated['code']] ?? [];
        $payload = [
            'code' => $validated['code'],
            'name' => $currency['name_fr'] ?? $validated['code'],
            'symbol' => $currency['symbol'] ?? null,
            'exchange_rate' => $validated['exchange_rate'],
            'is_base' => false,
            'is_default' => false,
            'status' => $validated['status'],
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function accountingCurrencyStatusLabels(): array
    {
        return [
            AccountingCurrency::STATUS_ACTIVE => __('main.active'),
            AccountingCurrency::STATUS_INACTIVE => __('main.inactive'),
        ];
    }

    private function paymentMethodRules(CompanySite $site, ?AccountingPaymentMethod $method = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounting_payment_methods', 'name')
                    ->where('company_site_id', $site->id)
                    ->ignore($method?->id),
            ],
            'type' => ['required', Rule::in(AccountingPaymentMethod::types())],
            'currency_code' => ['required', 'string', Rule::exists('accounting_currencies', 'code')->where('company_site_id', $site->id)],
            'code' => ['nullable', 'string', 'max:60'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
            'bic_swift' => ['nullable', 'string', 'max:255'],
            'bank_address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([AccountingPaymentMethod::STATUS_ACTIVE, AccountingPaymentMethod::STATUS_INACTIVE])],
        ];
    }

    private function paymentMethodPayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'currency_code' => $validated['currency_code'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'is_system_default' => false,
            'status' => $validated['status'],
        ];

        if ($validated['type'] === AccountingPaymentMethod::TYPE_BANK) {
            $payload += [
                'bank_name' => $validated['bank_name'] ?? null,
                'account_holder' => $validated['account_holder'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'iban' => $validated['iban'] ?? null,
                'bic_swift' => $validated['bic_swift'] ?? null,
                'bank_address' => $validated['bank_address'] ?? null,
            ];
        } else {
            $payload += [
                'bank_name' => null,
                'account_holder' => null,
                'account_number' => null,
                'iban' => null,
                'bic_swift' => null,
                'bank_address' => null,
            ];
        }

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function paymentMethodTypeLabels(): array
    {
        return [
            AccountingPaymentMethod::TYPE_CASH => __('main.payment_method_type_cash'),
            AccountingPaymentMethod::TYPE_BANK => __('main.payment_method_type_bank'),
            AccountingPaymentMethod::TYPE_MOBILE_MONEY => __('main.payment_method_type_mobile_money'),
            AccountingPaymentMethod::TYPE_CARD => __('main.payment_method_type_card'),
            AccountingPaymentMethod::TYPE_CHECK => __('main.payment_method_type_check'),
            AccountingPaymentMethod::TYPE_CUSTOMER_CREDIT => __('main.payment_method_type_customer_credit'),
            AccountingPaymentMethod::TYPE_OTHER => __('main.payment_method_type_other'),
        ];
    }

    private function paymentMethodStatusLabels(): array
    {
        return [
            AccountingPaymentMethod::STATUS_ACTIVE => __('main.active'),
            AccountingPaymentMethod::STATUS_INACTIVE => __('main.inactive'),
        ];
    }

    private function siteCurrencyOptions(CompanySite $site): array
    {
        return AccountingCurrency::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingCurrency::STATUS_ACTIVE)
            ->orderByDesc('is_base')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingCurrency $currency) => [$currency->code => CurrencyCatalog::label($currency->code)])
            ->all();
    }

    private function proformaRules(CompanySite $site, bool $updating = false): array
    {
        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);

        return [
            'client_id' => ['required', 'integer', $existsForSite('accounting_clients')],
            'title' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'expiration_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'currency' => [
                'required',
                'string',
                Rule::exists('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingCurrency::STATUS_ACTIVE),
            ],
            'status' => [$updating ? 'required' : 'nullable', Rule::in(AccountingProformaInvoice::statuses())],
            'payment_terms' => ['nullable', Rule::in(AccountingProformaInvoice::paymentTerms())],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in(AccountingProformaInvoiceLine::types())],
            'lines.*.item_id' => ['nullable', 'integer', $existsForSite('accounting_stock_items')],
            'lines.*.service_id' => ['nullable', 'integer', $existsForSite('accounting_services')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', Rule::in(AccountingProformaInvoiceLine::discountTypes())],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function proformaPayload(array $validated, User&Authenticatable $user, array $totals, bool $withCreator = true): array
    {
        $payload = [
            'client_id' => $validated['client_id'],
            'title' => $validated['title'] ?? null,
            'issue_date' => $validated['issue_date'],
            'expiration_date' => $validated['expiration_date'],
            'currency' => $validated['currency'],
            'status' => $validated['status'] ?? AccountingProformaInvoice::STATUS_DRAFT,
            'payment_terms' => $validated['payment_terms'] ?? AccountingProformaInvoice::PAYMENT_TO_DISCUSS,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'total_ht' => $totals['total_ht'],
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $totals['tax_amount'],
            'total_ttc' => $totals['total_ttc'],
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function calculateProformaTotals(array $lines, float $taxRate): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $totalHt = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->proformaLineDiscountAmount($line, $rawTotal);
            $lineTotal = max(0, $rawTotal - $discount);

            $subtotal += $rawTotal;
            $discountTotal += $discount;
            $totalHt += $lineTotal;
        }

        $taxAmount = round($totalHt * ($taxRate / 100), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total_ht' => round($totalHt, 2),
            'tax_amount' => $taxAmount,
            'total_ttc' => round($totalHt + $taxAmount, 2),
        ];
    }

    private function syncProformaLines(AccountingProformaInvoice $proforma, array $lines): void
    {
        $proforma->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountType = $this->proformaLineDiscountType($line);
            $discountValue = (float) ($line['discount_amount'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->proformaLineDiscountAmount($line, $rawTotal);

            $proforma->lines()->create([
                'line_type' => $line['line_type'],
                'item_id' => ($line['line_type'] === AccountingProformaInvoiceLine::TYPE_ITEM) ? ($line['item_id'] ?? null) : null,
                'service_id' => ($line['line_type'] === AccountingProformaInvoiceLine::TYPE_SERVICE) ? ($line['service_id'] ?? null) : null,
                'description' => $line['description'],
                'details' => $line['details'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_type' => $discountType,
                'discount_amount' => $discountValue,
                'line_total' => max(0, $rawTotal - $discount),
            ]);
        }
    }

    private function proformaLineDiscountAmount(array $line, float $rawTotal): float
    {
        $discountType = $this->proformaLineDiscountType($line);
        $discountValue = max(0, (float) ($line['discount_amount'] ?? 0));

        if ($discountType === AccountingProformaInvoiceLine::DISCOUNT_PERCENT) {
            return round(min($discountValue, 100) * $rawTotal / 100, 2);
        }

        return round(min($discountValue, $rawTotal), 2);
    }

    private function proformaLineDiscountType(array $line): string
    {
        $discountType = $line['discount_type'] ?? AccountingProformaInvoiceLine::DISCOUNT_FIXED;

        return in_array($discountType, AccountingProformaInvoiceLine::discountTypes(), true)
            ? $discountType
            : AccountingProformaInvoiceLine::DISCOUNT_FIXED;
    }

    private function proformaClientOptions(CompanySite $site): array
    {
        return AccountingClient::query()
            ->where('company_site_id', $site->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingClient $client) => [$client->id => "{$client->name} ({$client->reference})"])
            ->all();
    }

    private function proformaItemOptions(CompanySite $site): array
    {
        return AccountingStockItem::query()
            ->where('company_site_id', $site->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingStockItem $item) => [$item->id => [
                'label' => "{$item->name} ({$item->reference})",
                'price' => (float) $item->sale_price,
            ]])
            ->all();
    }

    private function proformaServiceOptions(CompanySite $site): array
    {
        return AccountingService::query()
            ->where('company_site_id', $site->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingService $service) => [$service->id => [
                'label' => "{$service->name} ({$service->reference})",
                'price' => (float) $service->price,
            ]])
            ->all();
    }

    private function proformaStatusLabels(): array
    {
        return [
            AccountingProformaInvoice::STATUS_DRAFT => __('main.proforma_status_draft'),
            AccountingProformaInvoice::STATUS_SENT => __('main.proforma_status_sent'),
            AccountingProformaInvoice::STATUS_ACCEPTED => __('main.proforma_status_accepted'),
            AccountingProformaInvoice::STATUS_REJECTED => __('main.proforma_status_rejected'),
            AccountingProformaInvoice::STATUS_EXPIRED => __('main.proforma_status_expired'),
            AccountingProformaInvoice::STATUS_CONVERTED => __('main.proforma_status_converted'),
        ];
    }

    private function proformaLineTypeLabels(): array
    {
        return [
            AccountingProformaInvoiceLine::TYPE_ITEM => __('main.proforma_line_item'),
            AccountingProformaInvoiceLine::TYPE_SERVICE => __('main.proforma_line_service'),
            AccountingProformaInvoiceLine::TYPE_FREE => __('main.proforma_line_free'),
        ];
    }

    private function proformaPaymentTermLabels(): array
    {
        return [
            AccountingProformaInvoice::PAYMENT_FULL_ORDER => __('main.payment_terms_full_order'),
            AccountingProformaInvoice::PAYMENT_HALF_ORDER => __('main.payment_terms_half_order'),
            AccountingProformaInvoice::PAYMENT_THIRTY_ORDER => __('main.payment_terms_thirty_order'),
            AccountingProformaInvoice::PAYMENT_ON_DELIVERY => __('main.payment_terms_on_delivery'),
            AccountingProformaInvoice::PAYMENT_AFTER_DELIVERY => __('main.payment_terms_after_delivery'),
            AccountingProformaInvoice::PAYMENT_TO_DISCUSS => __('main.payment_terms_to_discuss'),
        ];
    }

    private function qrCodeSvgDataUri(string $content): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(108, 1),
            new SvgImageBackEnd()
        );

        $svg = (new Writer($renderer))->writeString($content);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function companyCountryVatRate(Company $company): float
    {
        $country = $company->country;

        if (blank($country)) {
            return 0.0;
        }

        foreach (config('countries') as $meta) {
            if (in_array($country, [$meta['iso'], $meta['name'], $meta['name_fr'], $meta['name_en']], true)) {
                return (float) $meta['vat_rate'];
            }
        }

        return 0.0;
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

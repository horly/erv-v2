<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\AccountingCashRegisterSession;
use App\Models\AccountingClient;
use App\Models\AccountingCreditor;
use App\Models\AccountingCreditNote;
use App\Models\AccountingCreditNoteLine;
use App\Models\AccountingCurrency;
use App\Models\AccountingCustomerOrder;
use App\Models\AccountingCustomerOrderLine;
use App\Models\AccountingDebtor;
use App\Models\AccountingDeliveryNote;
use App\Models\AccountingDeliveryNoteLine;
use App\Models\AccountingOtherIncome;
use App\Models\AccountingPaymentMethod;
use App\Models\AccountingPartner;
use App\Models\AccountingProformaInvoice;
use App\Models\AccountingProformaInvoiceLine;
use App\Models\AccountingProspect;
use App\Models\AccountingPurchase;
use App\Models\AccountingPurchaseLine;
use App\Models\AccountingPurchasePayment;
use App\Models\AccountingRecurringService;
use App\Models\AccountingSalesRepresentative;
use App\Models\AccountingSalesInvoice;
use App\Models\AccountingSalesInvoiceLine;
use App\Models\AccountingSalesInvoicePayment;
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
use Illuminate\Http\UploadedFile;
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

    private function tableSearch(Request $request): string
    {
        return trim((string) $request->query('search', ''));
    }

    private function applyTableSearch($query, string $search, array $columns)
    {
        return $query->where(function ($searchQuery) use ($search, $columns): void {
            foreach ($columns as $column) {
                if (is_callable($column)) {
                    $column($searchQuery, $search);

                    continue;
                }

                $searchQuery->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    private function relationTableSearch(string $relation, array $columns): callable
    {
        return function ($query, string $search) use ($relation, $columns): void {
            $query->orWhereHas($relation, function ($relationQuery) use ($columns, $search): void {
                $relationQuery->where(function ($searchQuery) use ($columns, $search): void {
                    foreach ($columns as $column) {
                        $searchQuery->orWhere($column, 'like', "%{$search}%");
                    }
                });
            });
        };
    }

    private function tableSearchColumnsFromConfig(array $columns): array
    {
        $directColumns = [];
        $relationColumns = [];

        foreach ($columns as $column) {
            $key = $column['key'] ?? null;

            if (! is_string($key) || $key === '') {
                continue;
            }

            if (str_contains($key, '.')) {
                [$relation, $field] = explode('.', $key, 2);
                $relationColumns[$relation][] = $field;

                continue;
            }

            $directColumns[] = $key;
        }

        foreach ($relationColumns as $relation => $fields) {
            $directColumns[] = $this->relationTableSearch($relation, array_values(array_unique($fields)));
        }

        return $directColumns;
    }

    public function index(Request $request): View|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'name',
                    'country',
                    'email',
                    'website',
                    'address',
                ]))
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            default => $user->companies()
                ->withCount('sites')
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'name',
                    'country',
                    'email',
                    'website',
                    'address',
                ]))
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

    public function companySites(Request $request, Company $company): View|RedirectResponse
    {
        $user = Auth::user();
        $search = $this->tableSearch($request);

        if (! $this->canManageCompanyRecord($user, $company)) {
            return $this->redirectMainArea($user);
        }

        $company->load('subscription')->loadCount('sites');

        return view('main.company-sites', [
            'user' => $user,
            'company' => $company,
            'sites' => $company->sites()
                ->with('responsible')
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'name',
                    'type',
                    'code',
                    'city',
                    'phone',
                    'email',
                    'address',
                    'currency',
                    'status',
                    $this->relationTableSearch('responsible', ['name', 'email', 'role']),
                ]))
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

    public function accountingClients(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->with([
                    'contacts',
                    'proformaInvoices' => fn ($query) => $query
                        ->select(['id', 'client_id', 'reference', 'issue_date', 'status', 'total_ttc', 'currency'])
                        ->latest('issue_date')
                        ->latest('id'),
                    'customerOrders' => fn ($query) => $query
                        ->select(['id', 'client_id', 'reference', 'order_date', 'status', 'total_ttc', 'currency'])
                        ->latest('order_date')
                        ->latest('id'),
                    'deliveryNotes' => fn ($query) => $query
                        ->select(['id', 'client_id', 'reference', 'delivery_date', 'status'])
                        ->latest('delivery_date')
                        ->latest('id'),
                    'salesInvoices' => fn ($query) => $query
                        ->select(['id', 'client_id', 'reference', 'invoice_date', 'status', 'total_ttc', 'currency'])
                        ->latest('invoice_date')
                        ->latest('id'),
                    'creditNotes' => fn ($query) => $query
                        ->select(['id', 'client_id', 'reference', 'credit_date', 'status', 'total_ttc', 'currency'])
                        ->latest('credit_date')
                        ->latest('id'),
                ])
                ->withCount(['contacts', 'proformaInvoices', 'customerOrders', 'deliveryNotes', 'salesInvoices', 'creditNotes'])
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'profession',
                    'phone',
                    'email',
                    'address',
                    'rccm',
                    'id_nat',
                    'nif',
                    'account_number',
                    'website',
                    $this->relationTableSearch('contacts', ['full_name', 'position', 'department', 'email', 'phone']),
                ]))
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

    public function accountingSuppliers(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'profession',
                    'phone',
                    'email',
                    'address',
                    'rccm',
                    'id_nat',
                    'nif',
                    'bank_name',
                    'account_number',
                    'currency',
                    'website',
                    'status',
                    $this->relationTableSearch('contacts', ['full_name', 'position', 'department', 'email', 'phone']),
                ]))
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

    public function accountingCreditors(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'phone',
                    'email',
                    'address',
                    'currency',
                    'due_date',
                    'description',
                    'priority',
                    'status',
                ]))
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

    public function accountingDebtors(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'phone',
                    'email',
                    'address',
                    'currency',
                    'due_date',
                    'description',
                    'status',
                ]))
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

    public function accountingPartners(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'contact_name',
                    'contact_position',
                    'phone',
                    'email',
                    'address',
                    'website',
                    'activity_domain',
                    'partnership_started_at',
                    'status',
                    'notes',
                ]))
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

    public function accountingSalesRepresentatives(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'phone',
                    'email',
                    'address',
                    'sales_area',
                    'currency',
                    'status',
                    'notes',
                ]))
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

    public function accountingStockIndex(Request $request, Company $company, CompanySite $site, string $resource): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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

        if ($search !== '') {
            $this->applyTableSearch($recordsQuery, $search, $config['search'] ?? $this->tableSearchColumnsFromConfig($config['columns'] ?? []));
        }

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

    public function accountingServiceIndex(Request $request, Company $company, CompanySite $site, string $resource): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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

        if ($search !== '') {
            $this->applyTableSearch($recordsQuery, $search, $config['search'] ?? $this->tableSearchColumnsFromConfig($config['columns'] ?? []));
        }

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

    public function accountingCurrencies(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'code',
                    'name',
                    'symbol',
                    'exchange_rate',
                    'status',
                ]))
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

    public function accountingPaymentMethods(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->with([
                    'salesInvoicePayments.salesInvoice.client',
                    'salesInvoicePayments.receiver',
                    'otherIncomes' => fn ($incomeQuery) => $incomeQuery->where('status', AccountingOtherIncome::STATUS_VALIDATED),
                    'otherIncomes.creator',
                ])
                ->withCount('salesInvoicePayments')
                ->withSum('salesInvoicePayments as receipts_total', 'amount')
                ->withCount(['otherIncomes' => fn ($incomeQuery) => $incomeQuery->where('status', AccountingOtherIncome::STATUS_VALIDATED)])
                ->withSum(['otherIncomes as other_incomes_total' => fn ($incomeQuery) => $incomeQuery->where('status', AccountingOtherIncome::STATUS_VALIDATED)], 'amount')
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'name',
                    'type',
                    'currency_code',
                    'code',
                    'bank_name',
                    'account_holder',
                    'account_number',
                    'iban',
                    'bic_swift',
                    'bank_address',
                    'description',
                    'status',
                ]))
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

        if ($method->salesInvoicePayments()->exists() || $method->otherIncomes()->where('status', AccountingOtherIncome::STATUS_VALIDATED)->exists()) {
            return redirect()
                ->route('main.accounting.payment-methods', [$company, $site])
                ->with('success', __('main.payment_method_with_movements_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $method->delete();

        return redirect()
            ->route('main.accounting.payment-methods', [$company, $site])
            ->with('success', __('main.payment_method_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingProformaInvoices(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'title',
                    'issue_date',
                    'expiration_date',
                    'currency',
                    'status',
                    'payment_terms',
                    'notes',
                    'terms',
                    $this->relationTableSearch('client', ['reference', 'name', 'email', 'phone', 'address']),
                ]))
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

    public function importAccountingProformaQuote(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        $validated = $request->validate([
            'supplier_quote_file' => ['required', 'file', 'max:5120'],
            'supplier_quote_create_stock_items' => ['nullable', 'boolean'],
        ]);

        $file = $validated['supplier_quote_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'txt', 'xlsx', 'pdf'], true)) {
            return redirect()
                ->route('main.accounting.proforma-invoices.create', [$company, $site])
                ->withErrors(['supplier_quote_file' => __('main.supplier_quote_unsupported')])
                ->withInput($request->except('supplier_quote_file'));
        }

        $importedLines = $this->parseSupplierQuoteFile($file);

        if ($importedLines === []) {
            return redirect()
                ->route('main.accounting.proforma-invoices.create', [$company, $site])
                ->withErrors(['supplier_quote_file' => __('main.supplier_quote_no_lines')])
                ->withInput($request->except('supplier_quote_file'));
        }

        $createStockItems = $request->boolean('supplier_quote_create_stock_items');
        $importedLines = array_map(function (array $line) use ($createStockItems): array {
            $line['create_stock_item'] = $createStockItems ? '1' : '0';

            return $line;
        }, $importedLines);

        $input = $request->except('supplier_quote_file');
        $input['supplier_quote_create_stock_items'] = $createStockItems ? '1' : '0';
        $input['lines'] = $this->mergeImportedProformaLines((array) ($input['lines'] ?? []), $importedLines);

        return redirect()
            ->route('main.accounting.proforma-invoices.create', [$company, $site])
            ->withInput($input)
            ->with('success', __('main.supplier_quote_imported', ['count' => count($importedLines)]));
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
        $this->ensureDefaultAccountingStockRecords($site);

        $validated = $request->validate($this->proformaRules($site));

        DB::transaction(function () use ($site, $user, $validated): void {
            $totals = $this->calculateProformaTotals($validated['lines'], (float) $validated['tax_rate']);
            $proforma = $site->accountingProformaInvoices()->create($this->proformaPayload($validated, $user, $totals));
            $this->syncProformaLines($proforma, $site, $user, $validated['lines'], $validated['currency']);
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
        $this->ensureDefaultAccountingStockRecords($site);

        $validated = $request->validate($this->proformaRules($site, true));

        DB::transaction(function () use ($proforma, $site, $user, $validated): void {
            $totals = $this->calculateProformaTotals($validated['lines'], (float) $validated['tax_rate']);
            $proforma->update($this->proformaPayload($validated, $user, $totals, false));
            $this->syncProformaLines($proforma, $site, $user, $validated['lines'], $validated['currency']);
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

    public function convertAccountingProformaToCustomerOrder(Company $company, CompanySite $site, AccountingProformaInvoice $proforma): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($proforma->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.proforma-invoices', [$company, $site]);
        }

        $existingOrder = AccountingCustomerOrder::query()
            ->where('company_site_id', $site->id)
            ->where('proforma_invoice_id', $proforma->id)
            ->first();

        if ($existingOrder) {
            $proforma->update(['status' => AccountingProformaInvoice::STATUS_CONVERTED]);

            return redirect()
                ->route('main.accounting.customer-orders', [$company, $site])
                ->with('success', __('main.proforma_already_converted_to_order', ['reference' => $existingOrder->reference]));
        }

        if ($proforma->status === AccountingProformaInvoice::STATUS_CONVERTED) {
            return redirect()
                ->route('main.accounting.proforma-invoices', [$company, $site])
                ->with('success', __('main.proforma_already_converted'))
                ->with('toast_type', 'danger');
        }

        if ($proforma->status !== AccountingProformaInvoice::STATUS_ACCEPTED) {
            return redirect()
                ->route('main.accounting.proforma-invoices', [$company, $site])
                ->with('success', __('main.proforma_must_be_accepted_before_conversion'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        DB::transaction(function () use ($proforma, $site, $user): void {
            $proforma->load(['lines.item', 'lines.service']);

            $lines = $proforma->lines
                ->map(fn (AccountingProformaInvoiceLine $line): array => $this->customerOrderLineFromProformaLine($line))
                ->all();

            $totals = $this->calculateCustomerOrderTotals($lines, (float) $proforma->tax_rate);

            $order = $site->accountingCustomerOrders()->create([
                'client_id' => $proforma->client_id,
                'proforma_invoice_id' => $proforma->id,
                'created_by' => $user->id,
                'title' => $proforma->title,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => $proforma->expiration_date?->toDateString(),
                'currency' => $proforma->currency,
                'status' => AccountingCustomerOrder::STATUS_CONFIRMED,
                'payment_terms' => $proforma->payment_terms,
                'subtotal' => $totals['subtotal'],
                'cost_total' => $totals['cost_total'],
                'margin_total' => $totals['margin_total'],
                'margin_rate' => $totals['margin_rate'],
                'discount_total' => $totals['discount_total'],
                'total_ht' => $totals['total_ht'],
                'tax_rate' => $proforma->tax_rate,
                'tax_amount' => $totals['tax_amount'],
                'total_ttc' => $totals['total_ttc'],
                'notes' => $proforma->notes,
                'terms' => $proforma->terms,
            ]);

            $this->syncCustomerOrderLines($order, $site, $user, $lines, $proforma->currency);

            $proforma->update(['status' => AccountingProformaInvoice::STATUS_CONVERTED]);
        });

        return redirect()
            ->route('main.accounting.customer-orders', [$company, $site])
            ->with('success', __('main.proforma_converted_to_order'));
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

    public function accountingCustomerOrders(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-customer-orders', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'orderPermissions' => $this->sitePermissionFlags($user, $site),
            'orders' => AccountingCustomerOrder::query()
                ->with(['client', 'lines'])
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'title',
                    'order_date',
                    'expected_delivery_date',
                    'currency',
                    'status',
                    'payment_terms',
                    'notes',
                    'terms',
                    $this->relationTableSearch('client', ['reference', 'name', 'email', 'phone', 'address']),
                    $this->relationTableSearch('lines', ['description', 'details', 'line_type']),
                ]))
                ->latest('order_date')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'statusLabels' => $this->customerOrderStatusLabels(),
        ]);
    }

    public function createAccountingCustomerOrder(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-customer-order-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->customerOrderItemOptions($site),
            'services' => $this->customerOrderServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->customerOrderStatusLabels(),
            'lineTypeLabels' => $this->customerOrderLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function editAccountingCustomerOrder(Company $company, CompanySite $site, AccountingCustomerOrder $order): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if ($order->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);

        return view('main.modules.accounting-customer-order-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'order' => $order->load('lines'),
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->customerOrderItemOptions($site),
            'services' => $this->customerOrderServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->customerOrderStatusLabels(),
            'lineTypeLabels' => $this->customerOrderLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function storeAccountingCustomerOrder(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->customerOrderRules($site));

        DB::transaction(function () use ($site, $user, $validated): void {
            $totals = $this->calculateCustomerOrderTotals($validated['lines'], (float) $validated['tax_rate']);
            $order = $site->accountingCustomerOrders()->create($this->customerOrderPayload($validated, $user, $totals));
            $this->syncCustomerOrderLines($order, $site, $user, $validated['lines'], $validated['currency']);
        });

        return redirect()
            ->route('main.accounting.customer-orders', [$company, $site])
            ->with('success', __('main.customer_order_saved'));
    }

    public function updateAccountingCustomerOrder(Request $request, Company $company, CompanySite $site, AccountingCustomerOrder $order): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($order->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->customerOrderRules($site, true));

        DB::transaction(function () use ($order, $site, $user, $validated): void {
            $totals = $this->calculateCustomerOrderTotals($validated['lines'], (float) $validated['tax_rate']);
            $order->update($this->customerOrderPayload($validated, $user, $totals, false));
            $this->syncCustomerOrderLines($order, $site, $user, $validated['lines'], $validated['currency']);
        });

        return redirect()
            ->route('main.accounting.customer-orders', [$company, $site])
            ->with('success', __('main.customer_order_updated'));
    }

    public function destroyAccountingCustomerOrder(Company $company, CompanySite $site, AccountingCustomerOrder $order): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.customer-orders', [$company, $site]);
        }

        if ($order->company_site_id === $site->id) {
            $order->delete();
        }

        return redirect()
            ->route('main.accounting.customer-orders', [$company, $site])
            ->with('success', __('main.customer_order_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingDeliveryNotes(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-delivery-notes', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'deliveryPermissions' => $this->sitePermissionFlags($user, $site),
            'deliveryNotes' => AccountingDeliveryNote::query()
                ->with(['client', 'customerOrder', 'lines'])
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'title',
                    'delivery_date',
                    'status',
                    'delivered_by',
                    'carrier',
                    'notes',
                    $this->relationTableSearch('client', ['reference', 'name', 'email', 'phone', 'address']),
                    $this->relationTableSearch('customerOrder', ['reference', 'title', 'status']),
                    $this->relationTableSearch('lines', ['description', 'details', 'line_type']),
                ]))
                ->latest('delivery_date')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'orders' => $this->deliverableCustomerOrders($site),
            'statusLabels' => $this->deliveryNoteStatusLabels(),
        ]);
    }

    public function createAccountingDeliveryNote(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.delivery-notes', [$company, $site]);
        }

        $order = AccountingCustomerOrder::query()
            ->with(['client', 'lines.item.defaultWarehouse', 'lines.service'])
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingCustomerOrder::STATUS_CONFIRMED, AccountingCustomerOrder::STATUS_IN_PROGRESS])
            ->whereKey((int) $request->query('order'))
            ->first();

        if (! $order) {
            return redirect()
                ->route('main.accounting.delivery-notes', [$company, $site])
                ->with('success', __('main.delivery_note_choose_order'))
                ->with('toast_type', 'danger');
        }

        $lines = $this->deliveryNoteLinesFromOrder($order);

        if ($lines === []) {
            return redirect()
                ->route('main.accounting.delivery-notes', [$company, $site])
                ->with('success', __('main.delivery_note_no_remaining_lines'))
                ->with('toast_type', 'danger');
        }

        return view('main.modules.accounting-delivery-note-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'order' => $order,
            'defaultLines' => $lines,
            'statusLabels' => $this->deliveryNoteStatusLabels(),
        ]);
    }

    public function storeAccountingDeliveryNote(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.delivery-notes', [$company, $site]);
        }

        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->deliveryNoteRules($site));

        $order = AccountingCustomerOrder::query()
            ->with(['client', 'lines.item.defaultWarehouse'])
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingCustomerOrder::STATUS_CONFIRMED, AccountingCustomerOrder::STATUS_IN_PROGRESS])
            ->whereKey($validated['customer_order_id'])
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['customer_order_id' => __('main.delivery_note_choose_order')]);
        }

        $linePayloads = $this->validatedDeliveryLines($order, $validated['lines']);

        if ($linePayloads === []) {
            throw ValidationException::withMessages(['lines' => __('main.delivery_note_no_quantity')]);
        }

        DB::transaction(function () use ($site, $user, $order, $validated, $linePayloads): void {
            $deliveryNote = $site->accountingDeliveryNotes()->create([
                'client_id' => $order->client_id,
                'customer_order_id' => $order->id,
                'created_by' => $user->id,
                'title' => $validated['title'] ?? null,
                'delivery_date' => $validated['delivery_date'],
                'status' => $validated['status'],
                'delivered_by' => $validated['delivered_by'] ?? null,
                'carrier' => $validated['carrier'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($linePayloads as $line) {
                $serialNumbers = $line['serial_numbers'] ?? [];
                unset($line['serial_numbers']);

                $deliveryLine = $deliveryNote->lines()->create($line);
                $this->syncDeliveryNoteLineSerials($deliveryLine, $serialNumbers);
            }

            if ($deliveryNote->releasesStock()) {
                $this->releaseDeliveryNoteStock($deliveryNote, $site, $user);
            }

            $this->refreshCustomerOrderDeliveryStatus($order);
        });

        return redirect()
            ->route('main.accounting.delivery-notes', [$company, $site])
            ->with('success', __('main.delivery_note_saved'));
    }

    public function printAccountingDeliveryNote(Company $company, CompanySite $site, AccountingDeliveryNote $deliveryNote): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($deliveryNote->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.delivery-notes', [$company, $site]);
        }

        $deliveryNote->load(['client', 'creator', 'customerOrder', 'lines.serials', 'lines.item.unit', 'lines.service.unit']);
        $filename = 'bon-livraison-'.$deliveryNote->reference.'.pdf';
        $deliveryNoteUrl = route('main.accounting.delivery-notes.print', [$company, $site, $deliveryNote], true);

        return Pdf::loadView('main.modules.accounting-delivery-note-print', [
            'user' => $user,
            'company' => $company->load(['subscription', 'accounts']),
            'site' => $site->load('responsible'),
            'deliveryNote' => $deliveryNote,
            'deliveryNoteQrCodeDataUri' => $this->qrCodeSvgDataUri($deliveryNoteUrl),
            'statusLabels' => $this->deliveryNoteStatusLabels(),
            'isPdf' => true,
        ])->setPaper('a4')->stream($filename);
    }

    public function destroyAccountingDeliveryNote(Company $company, CompanySite $site, AccountingDeliveryNote $deliveryNote): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.delivery-notes', [$company, $site]);
        }

        if ($deliveryNote->company_site_id === $site->id && ! $deliveryNote->isStockReleased()) {
            $order = $deliveryNote->customerOrder;
            $deliveryNote->delete();

            if ($order) {
                $this->refreshCustomerOrderDeliveryStatus($order);
            }
        }

        return redirect()
            ->route('main.accounting.delivery-notes', [$company, $site])
            ->with('success', __('main.delivery_note_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingSalesInvoices(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->refreshOverdueSalesInvoices($site);

        return view('main.modules.accounting-sales-invoices', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'invoicePermissions' => $this->sitePermissionFlags($user, $site),
            'invoices' => AccountingSalesInvoice::query()
                ->with(['client', 'customerOrder', 'deliveryNote', 'payments.paymentMethod', 'payments.receiver', 'creditNotes'])
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'title',
                    'invoice_date',
                    'due_date',
                    'currency',
                    'status',
                    'payment_terms',
                    'notes',
                    'terms',
                    $this->relationTableSearch('client', ['reference', 'name', 'email', 'phone', 'address']),
                    $this->relationTableSearch('customerOrder', ['reference', 'title', 'status']),
                    $this->relationTableSearch('deliveryNote', ['reference', 'title', 'status']),
                    $this->relationTableSearch('lines', ['description', 'details', 'line_type']),
                ]))
                ->latest('invoice_date')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'paymentMethods' => $this->salesInvoicePaymentMethodOptions($site),
            'statusLabels' => $this->salesInvoiceStatusLabels(),
        ]);
    }

    public function accountingCreditNotes(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.accounting-credit-notes', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'creditNotePermissions' => $this->sitePermissionFlags($user, $site),
            'creditNotes' => AccountingCreditNote::query()
                ->with(['client', 'salesInvoice', 'creator'])
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'credit_date',
                    'currency',
                    'status',
                    'reason',
                    $this->relationTableSearch('client', ['reference', 'name', 'email', 'phone', 'address']),
                    $this->relationTableSearch('salesInvoice', ['reference', 'title', 'status']),
                    $this->relationTableSearch('lines', ['description', 'details']),
                ]))
                ->latest('credit_date')
                ->latest()
                ->paginate(5)
                ->withQueryString(),
            'statusLabels' => $this->creditNoteStatusLabels(),
        ]);
    }

    public function accountingReceipts(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->refreshOverdueSalesInvoices($site);

        $clientId = (int) $request->query('client_id', 0);
        $paymentMethodId = (int) $request->query('payment_method_id', 0);
        $invoiceStatus = trim((string) $request->query('invoice_status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $query = AccountingSalesInvoicePayment::query()
            ->with(['salesInvoice.client', 'salesInvoice.customerOrder', 'paymentMethod', 'receiver'])
            ->whereHas('salesInvoice', fn ($invoiceQuery) => $invoiceQuery->where('company_site_id', $site->id))
            ->when($clientId > 0, fn ($paymentQuery) => $paymentQuery->whereHas(
                'salesInvoice',
                fn ($invoiceQuery) => $invoiceQuery->where('client_id', $clientId)
            ))
            ->when($paymentMethodId > 0, fn ($paymentQuery) => $paymentQuery->where('payment_method_id', $paymentMethodId))
            ->when($invoiceStatus !== '', fn ($paymentQuery) => $paymentQuery->whereHas(
                'salesInvoice',
                fn ($invoiceQuery) => $invoiceQuery->where('status', $invoiceStatus)
            ))
            ->when($currency !== '', fn ($paymentQuery) => $paymentQuery->where('currency', $currency))
            ->when($dateFrom !== '', fn ($paymentQuery) => $paymentQuery->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($paymentQuery) => $paymentQuery->whereDate('payment_date', '<=', $dateTo))
            ->when($search !== '', fn ($paymentQuery) => $this->applyTableSearch($paymentQuery, $search, [
                'payment_date',
                'amount',
                'currency',
                'reference',
                'notes',
                $this->relationTableSearch('paymentMethod', ['name', 'currency_code']),
                $this->relationTableSearch('receiver', ['name', 'email']),
                $this->relationTableSearch('salesInvoice', ['reference', 'title', 'status', 'currency', 'payment_terms']),
                function ($subQuery, string $term): void {
                    $subQuery->orWhereHas('salesInvoice.client', function ($clientQuery) use ($term): void {
                        $clientQuery->where(function ($searchQuery) use ($term): void {
                            $searchQuery
                                ->orWhere('reference', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%")
                                ->orWhere('phone', 'like', "%{$term}%")
                                ->orWhere('address', 'like', "%{$term}%");
                        });
                    });
                },
            ]));

        $payments = (clone $query)
            ->latest('payment_date')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $totalReceived = (float) (clone $query)->sum('amount');
        $receivedThisMonth = (float) (clone $query)
            ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');
        $paymentsCount = (int) (clone $query)->count();
        $paidInvoicesCount = (int) (clone $query)
            ->whereHas('salesInvoice', fn ($invoiceQuery) => $invoiceQuery->where('status', AccountingSalesInvoice::STATUS_PAID))
            ->distinct('sales_invoice_id')
            ->count('sales_invoice_id');
        $partiallyPaidInvoicesCount = (int) (clone $query)
            ->whereHas('salesInvoice', fn ($invoiceQuery) => $invoiceQuery->where('status', AccountingSalesInvoice::STATUS_PARTIALLY_PAID))
            ->distinct('sales_invoice_id')
            ->count('sales_invoice_id');

        return view('main.modules.accounting-receipts', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'receiptPermissions' => $this->sitePermissionFlags($user, $site),
            'receipts' => $payments,
            'clients' => AccountingClient::query()
                ->where('company_site_id', $site->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'paymentMethods' => AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->orderBy('name')
                ->get(['id', 'name', 'currency_code']),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->salesInvoiceStatusLabels(),
            'invoiceStatusOptions' => [
                AccountingSalesInvoice::STATUS_DRAFT,
                AccountingSalesInvoice::STATUS_ISSUED,
                AccountingSalesInvoice::STATUS_PARTIALLY_PAID,
                AccountingSalesInvoice::STATUS_PAID,
                AccountingSalesInvoice::STATUS_OVERDUE,
            ],
            'filters' => [
                'client_id' => $clientId,
                'payment_method_id' => $paymentMethodId,
                'invoice_status' => $invoiceStatus,
                'currency' => $currency,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'receiptMetrics' => [
                'total_received' => $totalReceived,
                'month_received' => $receivedThisMonth,
                'payments_count' => $paymentsCount,
                'paid_invoices_count' => $paidInvoicesCount,
                'partial_invoices_count' => $partiallyPaidInvoicesCount,
            ],
        ]);
    }

    public function accountingOtherIncomes(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);

        $paymentMethodId = (int) $request->query('payment_method_id', 0);
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $query = AccountingOtherIncome::query()
            ->with(['paymentMethod', 'creator'])
            ->where('company_site_id', $site->id)
            ->when($paymentMethodId > 0, fn ($incomeQuery) => $incomeQuery->where('payment_method_id', $paymentMethodId))
            ->when($type !== '', fn ($incomeQuery) => $incomeQuery->where('type', $type))
            ->when($status !== '', fn ($incomeQuery) => $incomeQuery->where('status', $status))
            ->when($currency !== '', fn ($incomeQuery) => $incomeQuery->where('currency', $currency))
            ->when($dateFrom !== '', fn ($incomeQuery) => $incomeQuery->whereDate('income_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($incomeQuery) => $incomeQuery->whereDate('income_date', '<=', $dateTo))
            ->when($search !== '', fn ($incomeQuery) => $this->applyTableSearch($incomeQuery, $search, [
                'reference',
                'income_date',
                'type',
                'label',
                'description',
                'amount',
                'currency',
                'payment_reference',
                'status',
                $this->relationTableSearch('paymentMethod', ['name', 'currency_code', 'code']),
                $this->relationTableSearch('creator', ['name', 'email']),
            ]));

        $incomes = (clone $query)
            ->latest('income_date')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.accounting-other-incomes', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'otherIncomePermissions' => $this->sitePermissionFlags($user, $site),
            'otherIncomes' => $incomes,
            'totalValidated' => (float) (clone $query)->where('status', AccountingOtherIncome::STATUS_VALIDATED)->sum('amount'),
            'typeLabels' => $this->otherIncomeTypeLabels(),
            'statusLabels' => $this->otherIncomeStatusLabels(),
            'paymentMethods' => AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'currency_code']),
            'currencies' => $this->siteCurrencyOptions($site),
            'filters' => [
                'payment_method_id' => $paymentMethodId,
                'type' => $type,
                'status' => $status,
                'currency' => $currency,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function storeAccountingOtherIncome(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.other-incomes', [$company, $site]);
        }

        $validated = $request->validate($this->otherIncomeRules($site));

        $site->accountingOtherIncomes()->create($this->otherIncomePayload($validated, $user));

        return redirect()
            ->route('main.accounting.other-incomes', [$company, $site])
            ->with('success', __('main.other_income_saved'));
    }

    public function updateAccountingOtherIncome(Request $request, Company $company, CompanySite $site, AccountingOtherIncome $income): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $income->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.other-incomes', [$company, $site]);
        }

        if (! $income->isDraft()) {
            return redirect()
                ->route('main.accounting.other-incomes', [$company, $site])
                ->with('success', __('main.other_income_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $validated = $request->validate($this->otherIncomeRules($site));
        $income->update($this->otherIncomePayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.other-incomes', [$company, $site])
            ->with('success', __('main.other_income_updated'));
    }

    public function validateAccountingOtherIncome(Company $company, CompanySite $site, AccountingOtherIncome $income): RedirectResponse
    {
        return $this->changeAccountingOtherIncomeStatus($company, $site, $income, AccountingOtherIncome::STATUS_VALIDATED, __('main.other_income_validated'));
    }

    public function cancelAccountingOtherIncome(Company $company, CompanySite $site, AccountingOtherIncome $income): RedirectResponse
    {
        return $this->changeAccountingOtherIncomeStatus($company, $site, $income, AccountingOtherIncome::STATUS_CANCELLED, __('main.other_income_cancelled'));
    }

    public function destroyAccountingOtherIncome(Company $company, CompanySite $site, AccountingOtherIncome $income): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $income->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.other-incomes', [$company, $site]);
        }

        if (! $income->isDraft()) {
            return redirect()
                ->route('main.accounting.other-incomes', [$company, $site])
                ->with('success', __('main.other_income_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $income->delete();

        return redirect()
            ->route('main.accounting.other-incomes', [$company, $site])
            ->with('success', __('main.other_income_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingPurchases(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->refreshOverdueAccountingPurchases($site);

        $supplierId = (int) $request->query('supplier_id', 0);
        $paymentMethodId = (int) $request->query('payment_method_id', 0);
        $status = trim((string) $request->query('status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $query = AccountingPurchase::query()
            ->with(['supplier', 'creator', 'lines.item', 'lines.service', 'payments.paymentMethod', 'payments.payer'])
            ->where('company_site_id', $site->id)
            ->when($supplierId > 0, fn ($purchaseQuery) => $purchaseQuery->where('supplier_id', $supplierId))
            ->when($paymentMethodId > 0, fn ($purchaseQuery) => $purchaseQuery->whereHas('payments', fn ($paymentQuery) => $paymentQuery->where('payment_method_id', $paymentMethodId)))
            ->when($status !== '', fn ($purchaseQuery) => $purchaseQuery->where('status', $status))
            ->when($currency !== '', fn ($purchaseQuery) => $purchaseQuery->where('currency', $currency))
            ->when($dateFrom !== '', fn ($purchaseQuery) => $purchaseQuery->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($purchaseQuery) => $purchaseQuery->whereDate('purchase_date', '<=', $dateTo))
            ->when($search !== '', fn ($purchaseQuery) => $this->applyTableSearch($purchaseQuery, $search, [
                'reference',
                'supplier_invoice_reference',
                'title',
                'purchase_date',
                'due_date',
                'currency',
                'status',
                'notes',
                'terms',
                $this->relationTableSearch('supplier', ['reference', 'name', 'email', 'phone', 'address']),
                $this->relationTableSearch('lines', ['description', 'details', 'line_type']),
                $this->relationTableSearch('payments', ['reference', 'notes']),
            ]));

        $purchases = (clone $query)
            ->latest('purchase_date')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.accounting-purchases', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'purchasePermissions' => $this->sitePermissionFlags($user, $site),
            'purchases' => $purchases,
            'totalBalanceDue' => (float) (clone $query)->whereNotIn('status', [
                AccountingPurchase::STATUS_DRAFT,
                AccountingPurchase::STATUS_CANCELLED,
            ])->sum('balance_due'),
            'suppliers' => AccountingSupplier::query()
                ->where('company_site_id', $site->id)
                ->orderBy('name')
                ->get(['id', 'name', 'reference']),
            'paymentMethods' => $this->salesInvoicePaymentMethodOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->purchaseStatusLabels(),
            'filters' => [
                'supplier_id' => $supplierId,
                'payment_method_id' => $paymentMethodId,
                'status' => $status,
                'currency' => $currency,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function createAccountingPurchase(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.purchases', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        return view('main.modules.accounting-purchase-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'purchase' => null,
            'suppliers' => $this->purchaseSupplierOptions($site),
            'items' => $this->purchaseItemOptions($site),
            'services' => $this->purchaseServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->purchaseStatusLabels(),
            'lineTypeLabels' => $this->purchaseLineTypeLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function editAccountingPurchase(Request $request, Company $company, CompanySite $site, AccountingPurchase $purchase): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if ((int) $purchase->company_site_id !== (int) $site->id) {
            return redirect()->route('main.accounting.purchases', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update'] || ! $purchase->isEditable()) {
            return redirect()
                ->route('main.accounting.purchases', [$company, $site])
                ->with('success', __('main.purchase_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        return view('main.modules.accounting-purchase-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'purchase' => $purchase->load('lines'),
            'suppliers' => $this->purchaseSupplierOptions($site),
            'items' => $this->purchaseItemOptions($site),
            'services' => $this->purchaseServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->purchaseStatusLabels(),
            'lineTypeLabels' => $this->purchaseLineTypeLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function storeAccountingPurchase(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.purchases', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->purchaseRules($site));

        DB::transaction(function () use ($site, $user, $validated): void {
            $totals = $this->calculatePurchaseTotals($validated['lines'], (float) $validated['tax_rate']);
            $purchase = $site->accountingPurchases()->create($this->purchasePayload($validated, $user, $totals));
            $this->syncPurchaseLines($purchase, $site, $user, $validated['lines'], $validated['currency']);
            $this->refreshPurchasePaymentStatus($purchase);
        });

        return redirect()
            ->route('main.accounting.purchases', [$company, $site])
            ->with('success', __('main.purchase_saved'));
    }

    public function updateAccountingPurchase(Request $request, Company $company, CompanySite $site, AccountingPurchase $purchase): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $purchase->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.purchases', [$company, $site]);
        }

        if (! $purchase->isEditable()) {
            return redirect()
                ->route('main.accounting.purchases', [$company, $site])
                ->with('success', __('main.purchase_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->purchaseRules($site, true));

        DB::transaction(function () use ($purchase, $site, $user, $validated): void {
            $totals = $this->calculatePurchaseTotals($validated['lines'], (float) $validated['tax_rate']);
            $purchase->update($this->purchasePayload($validated, $user, $totals, false));
            $this->syncPurchaseLines($purchase, $site, $user, $validated['lines'], $validated['currency']);
            $this->refreshPurchasePaymentStatus($purchase);
        });

        return redirect()
            ->route('main.accounting.purchases', [$company, $site])
            ->with('success', __('main.purchase_updated'));
    }

    public function storeAccountingPurchasePayment(Request $request, Company $company, CompanySite $site, AccountingPurchase $purchase): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $purchase->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.purchases', [$company, $site]);
        }

        if (in_array($purchase->status, [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED, AccountingPurchase::STATUS_PAID], true)) {
            return redirect()
                ->route('main.accounting.purchases', [$company, $site])
                ->with('success', __('main.purchase_payment_blocked'))
                ->with('toast_type', 'danger');
        }

        $this->refreshPurchasePaymentStatus($purchase);
        $purchase->refresh();
        $balanceDue = round((float) $purchase->balance_due, 2);

        $validated = $request->validate([
            'payment_purchase_id' => ['nullable', 'integer'],
            'payment_method_id' => ['required', 'integer', Rule::exists('accounting_payment_methods', 'id')->where('company_site_id', $site->id)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$balanceDue],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'amount.max' => __('main.purchase_payment_exceeds_balance', [
                'amount' => number_format($balanceDue, 2, ',', ' '),
                'currency' => $purchase->currency,
            ]),
        ]);

        DB::transaction(function () use ($purchase, $user, $validated): void {
            $purchase->payments()->create([
                'payment_method_id' => $validated['payment_method_id'],
                'paid_by' => $user->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'currency' => $purchase->currency,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->refreshPurchasePaymentStatus($purchase);
        });

        return redirect()
            ->route('main.accounting.purchases', [$company, $site])
            ->with('success', __('main.purchase_payment_saved'));
    }

    public function destroyAccountingPurchase(Company $company, CompanySite $site, AccountingPurchase $purchase): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $purchase->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.purchases', [$company, $site]);
        }

        if (! $purchase->isEditable()) {
            return redirect()
                ->route('main.accounting.purchases', [$company, $site])
                ->with('success', __('main.purchase_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $purchase->delete();

        return redirect()
            ->route('main.accounting.purchases', [$company, $site])
            ->with('success', __('main.purchase_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingCashRegister(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->refreshOverdueSalesInvoices($site);

        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $limitCashRegisterToCurrentUser = $user->isUser();

        $openCashSession = AccountingCashRegisterSession::query()
            ->with('opener')
            ->where('company_site_id', $site->id)
            ->where('status', AccountingCashRegisterSession::STATUS_OPEN)
            ->when($limitCashRegisterToCurrentUser, fn ($sessionQuery) => $sessionQuery->where('opened_by', $user->id))
            ->latest('opened_at')
            ->first();

        $sessionHistory = AccountingCashRegisterSession::query()
            ->with(['opener', 'closer', 'validator'])
            ->where('company_site_id', $site->id)
            ->when($limitCashRegisterToCurrentUser, fn ($sessionQuery) => $sessionQuery->where('opened_by', $user->id))
            ->latest('opened_at')
            ->take(8)
            ->get();

        $openSessionAmounts = $openCashSession
            ? $this->cashRegisterSessionExpectedAmounts($openCashSession)
            : [
                'cash' => 0.0,
                'other' => 0.0,
                'total' => 0.0,
                'sales_count' => 0,
            ];

        $query = AccountingSalesInvoice::query()
            ->with(['client', 'payments.paymentMethod', 'creator', 'lines.item', 'lines.service', 'cashRegisterSession'])
            ->where('company_site_id', $site->id)
            ->where('title', AccountingSalesInvoice::TITLE_CASH_REGISTER)
            ->when($limitCashRegisterToCurrentUser, fn ($invoiceQuery) => $invoiceQuery->where('created_by', $user->id))
            ->when($currency !== '', fn ($invoiceQuery) => $invoiceQuery->where('currency', $currency))
            ->when($dateFrom !== '', fn ($invoiceQuery) => $invoiceQuery->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($invoiceQuery) => $invoiceQuery->whereDate('invoice_date', '<=', $dateTo))
            ->when($search !== '', fn ($invoiceQuery) => $this->applyTableSearch($invoiceQuery, $search, [
                'invoice_date',
                'reference',
                'total_ttc',
                'currency',
                'notes',
                $this->relationTableSearch('client', ['reference', 'name', 'email', 'phone']),
                $this->relationTableSearch('creator', ['name', 'email']),
                $this->relationTableSearch('payments', ['reference', 'notes']),
                function ($subQuery, string $term): void {
                    $subQuery->orWhereHas('payments.paymentMethod', function ($methodQuery) use ($term): void {
                        $methodQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('type', 'like', "%{$term}%")
                            ->orWhere('currency_code', 'like', "%{$term}%");
                    });
                },
            ]));

        $tickets = (clone $query)
            ->latest('invoice_date')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $today = now()->toDateString();
        $totalSales = (float) (clone $query)->sum('total_ttc');
        $todaySales = (float) (clone $query)->whereDate('invoice_date', $today)->sum('total_ttc');
        $ticketsCount = (int) (clone $query)->count();

        return view('main.modules.accounting-cash-register', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'cashPermissions' => $this->sitePermissionFlags($user, $site),
            'openCashSession' => $openCashSession,
            'openSessionAmounts' => $openSessionAmounts,
            'cashSessionHistory' => $sessionHistory,
            'canCloseCashSession' => $user->isAdmin(),
            'tickets' => $tickets,
            'clients' => $this->proformaClientOptions($site),
            'posItems' => AccountingStockItem::query()
                ->with(['category:id,name', 'subcategory:id,name'])
                ->where('company_site_id', $site->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'category_id', 'subcategory_id', 'reference', 'name', 'sale_price', 'current_stock', 'currency']),
            'paymentMethods' => $this->salesInvoicePaymentMethodOptions($site),
            'paymentMethodTypeLabels' => $this->paymentMethodTypeLabels(),
            'currencies' => $this->siteCurrencyOptions($site),
            'invoiceStatusLabels' => $this->salesInvoiceStatusLabels(),
            'lineTypeLabels' => $this->salesInvoiceLineTypeLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
            'filters' => [
                'currency' => $currency,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'cashMetrics' => [
                'total_sales' => $totalSales,
                'today_sales' => $todaySales,
                'tickets_count' => $ticketsCount,
            ],
        ]);
    }

    public function openAccountingCashRegisterSession(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.cash-register', [$company, $site]);
        }

        $validated = $request->validate([
            'opening_float' => ['nullable', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string'],
        ]);

        $hasOpenSession = AccountingCashRegisterSession::query()
            ->where('company_site_id', $site->id)
            ->when($user->isUser(), fn ($sessionQuery) => $sessionQuery->where('opened_by', $user->id))
            ->where('status', AccountingCashRegisterSession::STATUS_OPEN)
            ->exists();

        if ($hasOpenSession) {
            return redirect()
                ->route('main.accounting.cash-register', [$company, $site])
                ->with('success', __('main.cash_register_session_already_open'))
                ->with('toast_type', 'danger');
        }

        $site->accountingCashRegisterSessions()->create([
            'opened_by' => $user->id,
            'status' => AccountingCashRegisterSession::STATUS_OPEN,
            'opening_float' => $validated['opening_float'] ?? 0,
            'opened_at' => now(),
            'opening_notes' => $validated['opening_notes'] ?? null,
        ]);

        return redirect()
            ->route('main.accounting.cash-register', [$company, $site])
            ->with('success', __('main.cash_register_session_opened'));
    }

    public function closeAccountingCashRegisterSession(Request $request, Company $company, CompanySite $site, AccountingCashRegisterSession $session): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $session->company_site_id !== (int) $site->id) {
            abort(404);
        }

        if (! $user->isAdmin()) {
            return redirect()
                ->route('main.accounting.cash-register', [$company, $site])
                ->with('success', __('main.cash_register_close_admin_required'))
                ->with('toast_type', 'danger');
        }

        $validated = $request->validate([
            'counted_cash_amount' => ['required', 'numeric', 'min:0'],
            'counted_other_amount' => ['nullable', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($session, $validated, $user): void {
            $lockedSession = AccountingCashRegisterSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSession->isOpen()) {
                throw ValidationException::withMessages([
                    'counted_cash_amount' => __('main.cash_register_session_not_open'),
                ]);
            }

            $amounts = $this->cashRegisterSessionExpectedAmounts($lockedSession);
            $countedCash = (float) $validated['counted_cash_amount'];
            $countedOther = (float) ($validated['counted_other_amount'] ?? 0);
            $countedTotal = $countedCash + $countedOther;

            $lockedSession->update([
                'status' => AccountingCashRegisterSession::STATUS_CLOSED,
                'closed_by' => $user->id,
                'closure_validated_by' => $user->id,
                'closed_at' => now(),
                'expected_cash_amount' => $amounts['cash'],
                'expected_other_amount' => $amounts['other'],
                'expected_total_amount' => $amounts['total'],
                'counted_cash_amount' => $countedCash,
                'counted_other_amount' => $countedOther,
                'counted_total_amount' => $countedTotal,
                'difference_amount' => $countedTotal - $amounts['total'],
                'closing_notes' => $validated['closing_notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('main.accounting.cash-register', [$company, $site])
            ->with('success', __('main.cash_register_session_closed'));
    }

    public function printAccountingCashRegisterSession(Company $company, CompanySite $site, AccountingCashRegisterSession $session): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if ((int) $session->company_site_id !== (int) $site->id) {
            abort(404);
        }

        if ($user->isUser() && (int) $session->opened_by !== (int) $user->id) {
            abort(404);
        }

        $session->load(['opener', 'closer', 'validator', 'salesInvoices.client', 'salesInvoices.payments.paymentMethod']);

        $paymentSummary = AccountingSalesInvoicePayment::query()
            ->selectRaw('payment_method_id, currency, SUM(amount) as total_amount, COUNT(*) as payments_count')
            ->whereHas('salesInvoice', fn ($query) => $query->where('cash_register_session_id', $session->id))
            ->with('paymentMethod:id,name,type')
            ->groupBy('payment_method_id', 'currency')
            ->orderBy('payment_method_id')
            ->get();

        $filename = 'rapport-caisse-'.$session->reference.'.pdf';

        return Pdf::loadView('main.modules.accounting-cash-register-session-report', [
            'user' => $user,
            'company' => $company->load(['subscription', 'accounts']),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'session' => $session,
            'paymentSummary' => $paymentSummary,
            'invoiceStatusLabels' => $this->salesInvoiceStatusLabels(),
            'isPdf' => true,
        ])->setPaper('a4')->stream($filename);
    }

    public function storeAccountingCashRegisterSale(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $permissions = $this->sitePermissionFlags($user, $site);

        if (! $permissions['can_create']) {
            return redirect()->route('main.accounting.cash-register', [$company, $site]);
        }

        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);

        $validated = $request->validate([
            'client_id' => ['nullable', 'integer', $existsForSite('accounting_clients')],
            'sale_date' => ['required', 'date'],
            'currency' => [
                'required',
                'string',
                Rule::exists('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingCurrency::STATUS_ACTIVE),
            ],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('accounting_payment_methods', 'id')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingPaymentMethod::STATUS_ACTIVE),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_received' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in([AccountingSalesInvoiceLine::TYPE_ITEM])],
            'lines.*.item_id' => ['required', 'integer', $existsForSite('accounting_stock_items')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', Rule::in(AccountingSalesInvoiceLine::discountTypes())],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.create_stock_item' => ['nullable', 'boolean'],
        ]);

        $openCashSession = AccountingCashRegisterSession::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingCashRegisterSession::STATUS_OPEN)
            ->when($user->isUser(), fn ($sessionQuery) => $sessionQuery->where('opened_by', $user->id))
            ->latest('opened_at')
            ->first();

        if (! $openCashSession) {
            return redirect()
                ->route('main.accounting.cash-register', [$company, $site])
                ->with('success', __('main.cash_register_open_required'))
                ->with('toast_type', 'danger');
        }

        DB::transaction(function () use ($site, $user, $validated, $openCashSession): void {
            $cashSession = AccountingCashRegisterSession::query()
                ->whereKey($openCashSession->id)
                ->where('status', AccountingCashRegisterSession::STATUS_OPEN)
                ->lockForUpdate()
                ->firstOrFail();

            $totals = $this->calculateSalesInvoiceTotals($validated['lines'], (float) $validated['tax_rate']);
            $paymentMethod = AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->whereKey((int) $validated['payment_method_id'])
                ->firstOrFail();
            $isCashPayment = $paymentMethod->type === AccountingPaymentMethod::TYPE_CASH;
            $paymentReceived = $isCashPayment ? (float) ($validated['payment_received'] ?? 0) : null;
            $changeDue = $isCashPayment ? $paymentReceived - (float) $totals['total_ttc'] : null;

            if ($isCashPayment && $paymentReceived < (float) $totals['total_ttc']) {
                throw ValidationException::withMessages([
                    'payment_received' => __('main.cash_received_too_low'),
                ]);
            }

            $clientId = $validated['client_id'] ?? $this->cashRegisterWalkInClient($site, $user)->id;

            $invoice = $site->accountingSalesInvoices()->create([
                'cash_register_session_id' => $cashSession->id,
                'client_id' => $clientId,
                'created_by' => $user->id,
                'title' => AccountingSalesInvoice::TITLE_CASH_REGISTER,
                'invoice_date' => $validated['sale_date'],
                'due_date' => $validated['sale_date'],
                'currency' => $validated['currency'],
                'status' => AccountingSalesInvoice::STATUS_ISSUED,
                'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'total_ht' => $totals['total_ht'],
                'tax_rate' => $validated['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'total_ttc' => $totals['total_ttc'],
                'balance_due' => $totals['total_ttc'],
                'notes' => $validated['notes'] ?? null,
                'terms' => __('main.cash_register_default_terms'),
            ]);

            $this->syncSalesInvoiceLines($invoice, $site, $user, $validated['lines'], $validated['currency']);
            $this->releaseCashRegisterSaleStock($validated['lines'], $invoice, $site, $user);

            $invoice->payments()->create([
                'payment_method_id' => $validated['payment_method_id'],
                'received_by' => $user->id,
                'payment_date' => $validated['sale_date'],
                'amount' => $totals['total_ttc'],
                'amount_received' => $paymentReceived,
                'change_due' => $changeDue,
                'currency' => $validated['currency'],
                'reference' => $validated['payment_reference'] ?? null,
                'notes' => __('main.cash_register_payment_note', ['reference' => $invoice->reference]),
            ]);

            $this->refreshSalesInvoicePaymentStatus($invoice);
        });

        return redirect()
            ->route('main.accounting.cash-register', [$company, $site])
            ->with('success', __('main.cash_register_sale_saved'));
    }

    public function createAccountingSalesInvoice(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        return view('main.modules.accounting-sales-invoice-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'source' => $this->salesInvoiceSourceFromRequest($request, $site),
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->proformaItemOptions($site),
            'services' => $this->proformaServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->salesInvoiceStatusLabels(),
            'lineTypeLabels' => $this->salesInvoiceLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function editAccountingSalesInvoice(Company $company, CompanySite $site, AccountingSalesInvoice $invoice): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if ($invoice->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if (! $invoice->isEditable()) {
            return redirect()
                ->route('main.accounting.sales-invoices', [$company, $site])
                ->with('success', __('main.sales_invoice_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        return view('main.modules.accounting-sales-invoice-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'invoice' => $invoice->load('lines'),
            'source' => null,
            'clients' => $this->proformaClientOptions($site),
            'items' => $this->proformaItemOptions($site),
            'services' => $this->proformaServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->salesInvoiceStatusLabels(),
            'lineTypeLabels' => $this->salesInvoiceLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'defaultTaxRate' => $this->companyCountryVatRate($company),
        ]);
    }

    public function storeAccountingSalesInvoice(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->salesInvoiceRules($site));

        DB::transaction(function () use ($site, $user, $validated): void {
            $totals = $this->calculateSalesInvoiceTotals($validated['lines'], (float) $validated['tax_rate']);
            $invoice = $site->accountingSalesInvoices()->create($this->salesInvoicePayload($validated, $user, $totals));
            $this->syncSalesInvoiceLines($invoice, $site, $user, $validated['lines'], $validated['currency']);
            $this->refreshSalesInvoicePaymentStatus($invoice);
        });

        return redirect()
            ->route('main.accounting.sales-invoices', [$company, $site])
            ->with('success', __('main.sales_invoice_saved'));
    }

    public function updateAccountingSalesInvoice(Request $request, Company $company, CompanySite $site, AccountingSalesInvoice $invoice): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($invoice->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if (! $invoice->isEditable()) {
            return redirect()
                ->route('main.accounting.sales-invoices', [$company, $site])
                ->with('success', __('main.sales_invoice_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);
        $validated = $request->validate($this->salesInvoiceRules($site, true));

        DB::transaction(function () use ($invoice, $site, $user, $validated): void {
            $totals = $this->calculateSalesInvoiceTotals($validated['lines'], (float) $validated['tax_rate']);
            $invoice->update($this->salesInvoicePayload($validated, $user, $totals, false));
            $this->syncSalesInvoiceLines($invoice, $site, $user, $validated['lines'], $validated['currency']);
            $this->refreshSalesInvoicePaymentStatus($invoice);
        });

        return redirect()
            ->route('main.accounting.sales-invoices', [$company, $site])
            ->with('success', __('main.sales_invoice_updated'));
    }

    public function printAccountingSalesInvoice(Company $company, CompanySite $site, AccountingSalesInvoice $invoice): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($invoice->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        $invoice->load([
            'client',
            'creator',
            'payments.paymentMethod',
            'customerOrder',
            'deliveryNote',
            'lines.item.unit',
            'lines.service.unit',
        ]);

        $filename = 'facture-vente-'.$invoice->reference.'.pdf';
        $invoiceUrl = route('main.accounting.sales-invoices.print', [$company, $site, $invoice], true);

        return Pdf::loadView('main.modules.accounting-sales-invoice-print', [
            'user' => $user,
            'company' => $company->load(['subscription', 'accounts']),
            'site' => $site->load('responsible'),
            'invoice' => $invoice,
            'invoiceQrCodeDataUri' => $this->qrCodeSvgDataUri($invoiceUrl),
            'statusLabels' => $this->salesInvoiceStatusLabels(),
            'lineTypeLabels' => $this->salesInvoiceLineTypeLabels(),
            'paymentTermLabels' => $this->proformaPaymentTermLabels(),
            'isPdf' => true,
        ])->setPaper('a4')->stream($filename);
    }

    public function storeAccountingSalesInvoicePayment(Request $request, Company $company, CompanySite $site, AccountingSalesInvoice $invoice): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($invoice->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if (in_array($invoice->status, [AccountingSalesInvoice::STATUS_CANCELLED, AccountingSalesInvoice::STATUS_PAID], true)) {
            return redirect()
                ->route('main.accounting.sales-invoices', [$company, $site])
                ->with('success', __('main.sales_invoice_payment_blocked'))
                ->with('toast_type', 'danger');
        }

        $this->refreshSalesInvoicePaymentStatus($invoice);
        $invoice->refresh();
        $balanceDue = round((float) $invoice->balance_due, 2);

        $validated = $request->validate([
            'payment_invoice_id' => ['nullable', 'integer'],
            'payment_method_id' => ['required', 'integer', Rule::exists('accounting_payment_methods', 'id')->where('company_site_id', $site->id)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$balanceDue],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'amount.max' => __('main.sales_invoice_payment_exceeds_balance', [
                'amount' => number_format($balanceDue, 2, ',', ' '),
                'currency' => $invoice->currency,
            ]),
        ]);

        DB::transaction(function () use ($invoice, $user, $validated): void {
            $invoice->payments()->create([
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'received_by' => $user->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'currency' => $invoice->currency,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->refreshSalesInvoicePaymentStatus($invoice);
        });

        return redirect()
            ->route('main.accounting.sales-invoices', [$company, $site])
            ->with('success', __('main.sales_invoice_payment_saved'));
    }

    public function destroyAccountingSalesInvoice(Company $company, CompanySite $site, AccountingSalesInvoice $invoice): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.sales-invoices', [$company, $site]);
        }

        if ($invoice->company_site_id === $site->id && $invoice->isEditable()) {
            $invoice->delete();

            return redirect()
                ->route('main.accounting.sales-invoices', [$company, $site])
                ->with('success', __('main.sales_invoice_deleted'))
                ->with('toast_type', 'danger');
        }

        return redirect()
            ->route('main.accounting.sales-invoices', [$company, $site])
            ->with('success', __('main.sales_invoice_cannot_delete'))
            ->with('toast_type', 'danger');
    }

    public function createAccountingCreditNote(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.credit-notes', [$company, $site]);
        }

        $invoice = AccountingSalesInvoice::query()
            ->with(['client', 'lines.item.unit', 'lines.service.unit', 'creditNotes.lines'])
            ->where('company_site_id', $site->id)
            ->whereKey((int) $request->query('invoice'))
            ->first();

        if (! $invoice || ! $this->salesInvoiceCanReceiveCreditNote($invoice)) {
            return redirect()
                ->route('main.accounting.sales-invoices', [$company, $site])
                ->with('success', __('main.credit_note_invoice_required'))
                ->with('toast_type', 'danger');
        }

        $this->refreshSalesInvoicePaymentStatus($invoice);
        $invoice->refresh()->load(['client', 'lines.item.unit', 'lines.service.unit', 'creditNotes.lines']);

        return view('main.modules.accounting-credit-note-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'invoice' => $invoice,
            'statusLabels' => $this->creditNoteStatusLabels(),
            'lineDefaults' => $this->creditNoteLineDefaults($invoice),
            'creditableAmount' => $invoice->creditableAmount(),
        ]);
    }

    public function storeAccountingCreditNote(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.credit-notes', [$company, $site]);
        }

        $validated = $request->validate($this->creditNoteRules($site));
        $invoice = AccountingSalesInvoice::query()
            ->with(['lines', 'creditNotes'])
            ->where('company_site_id', $site->id)
            ->whereKey((int) $validated['sales_invoice_id'])
            ->firstOrFail();

        if (! $this->salesInvoiceCanReceiveCreditNote($invoice)) {
            return redirect()
                ->route('main.accounting.sales-invoices', [$company, $site])
                ->with('success', __('main.credit_note_invoice_required'))
                ->with('toast_type', 'danger');
        }

        try {
            DB::transaction(function () use ($invoice, $site, $user, $validated): void {
                $preparedLines = $this->prepareCreditNoteLines($invoice, $validated['lines']);
                $totals = $this->calculateCreditNoteTotals($preparedLines, (float) $invoice->tax_rate);
                $creditableAmount = $invoice->creditableAmount();

                if ($totals['total_ttc'] <= 0) {
                    throw ValidationException::withMessages(['lines' => __('main.credit_note_no_lines')]);
                }

                if ($totals['total_ttc'] > $creditableAmount) {
                    throw ValidationException::withMessages([
                        'lines' => __('main.credit_note_exceeds_invoice_balance', [
                            'amount' => number_format($creditableAmount, 2, ',', ' '),
                            'currency' => $invoice->currency,
                        ]),
                    ]);
                }

                $creditNote = $site->accountingCreditNotes()->create([
                    'sales_invoice_id' => $invoice->id,
                    'client_id' => $invoice->client_id,
                    'created_by' => $user->id,
                    'credit_date' => $validated['credit_date'],
                    'currency' => $invoice->currency,
                    'status' => $validated['status'],
                    'reason' => $validated['reason'] ?? null,
                    'subtotal' => $totals['subtotal'],
                    'tax_rate' => $invoice->tax_rate,
                    'tax_amount' => $totals['tax_amount'],
                    'total_ttc' => $totals['total_ttc'],
                ]);

                foreach ($preparedLines as $line) {
                    $creditNote->lines()->create($line);
                }

                $this->refreshSalesInvoicePaymentStatus($invoice);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()
            ->route('main.accounting.credit-notes', [$company, $site])
            ->with('success', __('main.credit_note_saved'));
    }

    public function validateAccountingCreditNote(Company $company, CompanySite $site, AccountingCreditNote $creditNote): RedirectResponse
    {
        return $this->changeAccountingCreditNoteStatus($company, $site, $creditNote, AccountingCreditNote::STATUS_VALIDATED, __('main.credit_note_validated'));
    }

    public function cancelAccountingCreditNote(Company $company, CompanySite $site, AccountingCreditNote $creditNote): RedirectResponse
    {
        return $this->changeAccountingCreditNoteStatus($company, $site, $creditNote, AccountingCreditNote::STATUS_CANCELLED, __('main.credit_note_cancelled'));
    }

    public function printAccountingCreditNote(Company $company, CompanySite $site, AccountingCreditNote $creditNote): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $creditNote->company_site_id !== (int) $site->id) {
            return redirect()->route('main.accounting.credit-notes', [$company, $site]);
        }

        $creditNote->load(['client', 'creator', 'salesInvoice', 'lines.salesInvoiceLine']);
        $filename = 'avoir-'.$creditNote->reference.'.pdf';
        $creditNoteUrl = route('main.accounting.credit-notes.print', [$company, $site, $creditNote], true);

        return Pdf::loadView('main.modules.accounting-credit-note-print', [
            'user' => $user,
            'company' => $company->load(['subscription', 'accounts']),
            'site' => $site->load('responsible'),
            'creditNote' => $creditNote,
            'creditNoteQrCodeDataUri' => $this->qrCodeSvgDataUri($creditNoteUrl),
            'statusLabels' => $this->creditNoteStatusLabels(),
            'isPdf' => true,
        ])->setPaper('a4')->stream($filename);
    }

    public function destroyAccountingCreditNote(Company $company, CompanySite $site, AccountingCreditNote $creditNote): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_delete'] || (int) $creditNote->company_site_id !== (int) $site->id) {
            return redirect()->route('main.accounting.credit-notes', [$company, $site]);
        }

        if (! $creditNote->isDraft()) {
            return redirect()
                ->route('main.accounting.credit-notes', [$company, $site])
                ->with('success', __('main.credit_note_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $invoice = $creditNote->salesInvoice;
        $creditNote->delete();

        if ($invoice) {
            $this->refreshSalesInvoicePaymentStatus($invoice);
        }

        return redirect()
            ->route('main.accounting.credit-notes', [$company, $site])
            ->with('success', __('main.credit_note_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingProspects(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'type',
                    'name',
                    'profession',
                    'phone',
                    'email',
                    'address',
                    'rccm',
                    'id_nat',
                    'nif',
                    'website',
                    'source',
                    'status',
                    'interest_level',
                    'notes',
                    $this->relationTableSearch('contacts', ['full_name', 'position', 'department', 'email', 'phone']),
                ]))
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

    public function users(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $search = $this->tableSearch($request);

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
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'name',
                    'email',
                    'role',
                    'phone_number',
                    'grade',
                    'address',
                    $this->relationTableSearch('sites', ['name', 'type', 'city', 'email', 'phone', 'status']),
                    $this->relationTableSearch('sites.company', ['name', 'country', 'email']),
                ]))
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

    private function otherIncomeRules(CompanySite $site): array
    {
        return [
            'income_date' => ['required', 'date'],
            'type' => ['required', Rule::in(AccountingOtherIncome::types())],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => [
                'required',
                'string',
                Rule::exists('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingCurrency::STATUS_ACTIVE),
            ],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('accounting_payment_methods', 'id')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingPaymentMethod::STATUS_ACTIVE),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([AccountingOtherIncome::STATUS_DRAFT, AccountingOtherIncome::STATUS_VALIDATED])],
        ];
    }

    private function otherIncomePayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'income_date' => $validated['income_date'],
            'type' => $validated['type'],
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'amount' => round((float) $validated['amount'], 2),
            'currency' => $validated['currency'],
            'payment_method_id' => $validated['payment_method_id'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'status' => $validated['status'],
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function changeAccountingOtherIncomeStatus(Company $company, CompanySite $site, AccountingOtherIncome $income, string $status, string $message): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $income->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.other-incomes', [$company, $site]);
        }

        if ($status === AccountingOtherIncome::STATUS_VALIDATED && ! $income->isDraft()) {
            return redirect()->route('main.accounting.other-incomes', [$company, $site]);
        }

        if ($status === AccountingOtherIncome::STATUS_CANCELLED && ! $income->isValidated()) {
            return redirect()->route('main.accounting.other-incomes', [$company, $site]);
        }

        $income->update(['status' => $status]);

        return redirect()
            ->route('main.accounting.other-incomes', [$company, $site])
            ->with('success', $message);
    }

    private function otherIncomeTypeLabels(): array
    {
        return [
            AccountingOtherIncome::TYPE_OWNER_CONTRIBUTION => __('main.other_income_type_owner_contribution'),
            AccountingOtherIncome::TYPE_SUBSIDY => __('main.other_income_type_subsidy'),
            AccountingOtherIncome::TYPE_REFUND => __('main.other_income_type_refund'),
            AccountingOtherIncome::TYPE_EXCEPTIONAL_INCOME => __('main.other_income_type_exceptional_income'),
            AccountingOtherIncome::TYPE_BANK_INTEREST => __('main.other_income_type_bank_interest'),
            AccountingOtherIncome::TYPE_POSITIVE_ADJUSTMENT => __('main.other_income_type_positive_adjustment'),
            AccountingOtherIncome::TYPE_MISCELLANEOUS => __('main.other_income_type_miscellaneous'),
        ];
    }

    private function otherIncomeStatusLabels(): array
    {
        return [
            AccountingOtherIncome::STATUS_DRAFT => __('main.other_income_status_draft'),
            AccountingOtherIncome::STATUS_VALIDATED => __('main.other_income_status_validated'),
            AccountingOtherIncome::STATUS_CANCELLED => __('main.other_income_status_cancelled'),
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
            'lines.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', Rule::in(AccountingProformaInvoiceLine::discountTypes())],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.create_stock_item' => ['nullable', 'boolean'],
        ];
    }

    private function parseSupplierQuoteFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (! $path) {
            return [];
        }

        $rows = match (strtolower($file->getClientOriginalExtension())) {
            'csv', 'txt' => $this->readDelimitedQuoteFile($path),
            'xlsx' => $this->readXlsxQuoteFile($path),
            'pdf' => $this->readPdfQuoteFile($path),
            default => [],
        };

        return $this->supplierQuoteRowsToProformaLines($rows);
    }

    private function readDelimitedQuoteFile(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $content) ?: [], fn (string $line): bool => trim($line) !== ''));
        $delimiters = ["\t", ';', ','];
        $delimiter = ';';
        $bestScore = -1;

        foreach ($delimiters as $candidate) {
            $score = 0;
            foreach (array_slice($lines, 0, 10) as $line) {
                $score += substr_count($line, $candidate);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $delimiter = $candidate;
            }
        }

        return array_map(fn (string $line): array => str_getcsv($line, $delimiter), $lines);
    }

    private function readXlsxQuoteFile(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return [];
        }

        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if (is_string($sharedXml)) {
            $shared = simplexml_load_string($sharedXml);

            if ($shared !== false) {
                foreach ($shared->si as $item) {
                    if (isset($item->t)) {
                        $sharedStrings[] = (string) $item->t;
                        continue;
                    }

                    $text = '';
                    foreach ($item->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetXml)) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            return [];
        }

        $rows = [];

        foreach ($sheet->sheetData->row as $xmlRow) {
            $row = [];

            foreach ($xmlRow->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) ($attributes['r'] ?? '');
                $type = (string) ($attributes['t'] ?? '');
                $column = $this->xlsxColumnIndex($reference);
                $value = '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                if ($column !== null) {
                    $row[$column] = $value;
                }
            }

            if ($row !== []) {
                ksort($row);
                $rows[] = array_values($row);
            }
        }

        return $rows;
    }

    private function xlsxColumnIndex(string $reference): ?int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return null;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function readPdfQuoteFile(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        $text = preg_replace('/[^\P{C}\r\n\t ]+/u', ' ', $content) ?? $content;

        return $this->quoteTextToRows($text);
    }

    private function quoteTextToRows(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(.+?)\s+([0-9]+(?:[,.][0-9]+)?)\s+([0-9][0-9\s.,]*)$/u', $line, $matches)) {
                $rows[] = [$matches[1], $matches[2], $matches[3]];
                continue;
            }

            $parts = preg_split('/\s{2,}|\t+/', $line) ?: [];
            if (count($parts) >= 3) {
                $rows[] = $parts;
            }
        }

        return $rows;
    }

    private function supplierQuoteRowsToProformaLines(array $rows): array
    {
        $rows = array_values(array_filter(array_map(function (array $row): array {
            return array_values(array_map(fn ($value): string => trim((string) $value), $row));
        }, $rows), fn (array $row): bool => collect($row)->contains(fn (string $value): bool => $value !== '')));

        if ($rows === []) {
            return [];
        }

        [$headerIndex, $columns] = $this->detectSupplierQuoteHeader($rows);
        $dataRows = $headerIndex >= 0 ? array_slice($rows, $headerIndex + 1) : $rows;
        $lines = [];

        foreach ($dataRows as $row) {
            $description = $this->quoteCell($row, $columns['description'] ?? 0);
            $quantity = $this->localizedNumber($this->quoteCell($row, $columns['quantity'] ?? 1));
            $unitPrice = $this->localizedNumber($this->quoteCell($row, $columns['unit_price'] ?? 2));
            $total = isset($columns['total']) ? $this->localizedNumber($this->quoteCell($row, $columns['total'])) : 0;

            if ($quantity <= 0) {
                $quantity = 1;
            }

            if ($unitPrice <= 0 && $total > 0) {
                $unitPrice = round($total / $quantity, 2);
            }

            if ($description === '' || $this->looksLikeQuoteHeader($description)) {
                continue;
            }

            $lines[] = [
                'line_type' => AccountingProformaInvoiceLine::TYPE_FREE,
                'item_id' => '',
                'service_id' => '',
                'description' => Str::limit($description, 255, ''),
                'details' => isset($columns['reference'])
                    ? trim(__('main.reference') . ' : ' . $this->quoteCell($row, $columns['reference']))
                    : '',
                'quantity' => number_format($quantity, 2, '.', ''),
                'cost_price' => number_format($unitPrice, 2, '.', ''),
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'discount_type' => AccountingProformaInvoiceLine::DISCOUNT_FIXED,
                'discount_amount' => '0',
                'create_stock_item' => '0',
            ];
        }

        return $lines;
    }

    private function detectSupplierQuoteHeader(array $rows): array
    {
        $bestIndex = -1;
        $bestColumns = [];
        $bestScore = 0;

        foreach (array_slice($rows, 0, 10, true) as $index => $row) {
            $columns = [];
            $score = 0;

            foreach ($row as $column => $label) {
                $key = $this->quoteHeaderKey($label);

                if (in_array($key, ['designation', 'description', 'article', 'item', 'produit', 'product', 'service', 'libelle', 'libellearticle', 'nom', 'name'], true)) {
                    $columns['description'] = $column;
                    $score += 2;
                } elseif (in_array($key, ['quantite', 'quantity', 'qty', 'qte', 'nombre'], true)) {
                    $columns['quantity'] = $column;
                    $score += 1;
                } elseif (in_array($key, ['prixunitaire', 'unitprice', 'unitcost', 'price', 'prix', 'pu', 'rate', 'coutunitaire'], true)) {
                    $columns['unit_price'] = $column;
                    $score += 1;
                } elseif (in_array($key, ['total', 'montant', 'amount', 'linetotal'], true)) {
                    $columns['total'] = $column;
                    $score += 1;
                } elseif (in_array($key, ['reference', 'ref', 'sku', 'code'], true)) {
                    $columns['reference'] = $column;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = (int) $index;
                $bestColumns = $columns;
            }
        }

        return $bestScore >= 2
            ? [$bestIndex, $bestColumns]
            : [-1, ['description' => 0, 'quantity' => 1, 'unit_price' => 2]];
    }

    private function quoteHeaderKey(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function quoteCell(array $row, int $index): string
    {
        return trim((string) ($row[$index] ?? ''));
    }

    private function localizedNumber(string $value): float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        $value = preg_replace('/[^0-9,.\-]/', '', str_replace(["\xc2\xa0", ' '], '', $value)) ?? '';

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $thousands = $decimal === ',' ? '.' : ',';
            $value = str_replace($thousands, '', $value);
            $value = str_replace($decimal, '.', $value);
        } elseif ($lastComma !== false) {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    private function looksLikeQuoteHeader(string $value): bool
    {
        return in_array($this->quoteHeaderKey($value), ['designation', 'description', 'article', 'produit', 'service'], true);
    }

    private function mergeImportedProformaLines(array $currentLines, array $importedLines): array
    {
        $currentLines = array_values(array_filter($currentLines, function ($line): bool {
            return is_array($line) && ! $this->isBlankProformaLine($line);
        }));

        return array_values([...$currentLines, ...$importedLines]);
    }

    private function isBlankProformaLine(array $line): bool
    {
        return blank($line['description'] ?? null)
            && blank($line['item_id'] ?? null)
            && blank($line['service_id'] ?? null)
            && blank($line['details'] ?? null)
            && (float) ($line['unit_price'] ?? 0) <= 0;
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

    private function syncProformaLines(AccountingProformaInvoice $proforma, CompanySite $site, User&Authenticatable $user, array $lines, string $currency): void
    {
        $proforma->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountType = $this->proformaLineDiscountType($line);
            $discountValue = (float) ($line['discount_amount'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->proformaLineDiscountAmount($line, $rawTotal);
            $lineType = $line['line_type'];
            $itemId = ($lineType === AccountingProformaInvoiceLine::TYPE_ITEM) ? ($line['item_id'] ?? null) : null;

            if ($lineType === AccountingProformaInvoiceLine::TYPE_FREE && (bool) ($line['create_stock_item'] ?? false)) {
                $item = $this->createStockItemFromFreeLine($site, $user, $line, $currency);
                $lineType = AccountingProformaInvoiceLine::TYPE_ITEM;
                $itemId = $item->id;
            }

            $proforma->lines()->create([
                'line_type' => $lineType,
                'item_id' => $itemId,
                'service_id' => ($lineType === AccountingProformaInvoiceLine::TYPE_SERVICE) ? ($line['service_id'] ?? null) : null,
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

    private function customerOrderLineFromProformaLine(AccountingProformaInvoiceLine $line): array
    {
        $lineType = $line->line_type;
        $itemId = $line->item_id;
        $serviceId = $line->service_id;

        if ($lineType === AccountingProformaInvoiceLine::TYPE_ITEM && blank($itemId)) {
            $lineType = AccountingProformaInvoiceLine::TYPE_FREE;
        }

        if ($lineType === AccountingProformaInvoiceLine::TYPE_SERVICE && blank($serviceId)) {
            $lineType = AccountingProformaInvoiceLine::TYPE_FREE;
        }

        $costPrice = $lineType === AccountingProformaInvoiceLine::TYPE_ITEM
            ? (float) ($line->item?->purchase_price ?? 0)
            : 0;

        $quantity = (float) $line->quantity;
        $lineTotal = (float) $line->line_total;
        $costTotal = $quantity * $costPrice;

        return [
            'line_type' => $lineType,
            'item_id' => $lineType === AccountingProformaInvoiceLine::TYPE_ITEM ? $itemId : null,
            'service_id' => $lineType === AccountingProformaInvoiceLine::TYPE_SERVICE ? $serviceId : null,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => $quantity,
            'cost_price' => $costPrice,
            'unit_price' => (float) $line->unit_price,
            'margin_type' => AccountingCustomerOrderLine::MARGIN_FIXED,
            'margin_value' => max(0, $lineTotal - $costTotal),
            'discount_type' => $line->discount_type,
            'discount_amount' => (float) $line->discount_amount,
            'create_stock_item' => 0,
        ];
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
            ->mapWithKeys(fn (AccountingClient $client) => [$client->id => "{$client->display_name} ({$client->reference})"])
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

    private function salesInvoiceRules(CompanySite $site, bool $updating = false): array
    {
        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);

        return [
            'client_id' => ['required', 'integer', $existsForSite('accounting_clients')],
            'customer_order_id' => ['nullable', 'integer', $existsForSite('accounting_customer_orders')],
            'delivery_note_id' => ['nullable', 'integer', $existsForSite('accounting_delivery_notes')],
            'proforma_invoice_id' => ['nullable', 'integer', $existsForSite('accounting_proforma_invoices')],
            'title' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'currency' => [
                'required',
                'string',
                Rule::exists('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingCurrency::STATUS_ACTIVE),
            ],
            'status' => [$updating ? 'required' : 'nullable', Rule::in(AccountingSalesInvoice::statuses())],
            'payment_terms' => ['nullable', Rule::in(AccountingProformaInvoice::paymentTerms())],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in(AccountingSalesInvoiceLine::types())],
            'lines.*.item_id' => ['nullable', 'integer', $existsForSite('accounting_stock_items')],
            'lines.*.service_id' => ['nullable', 'integer', $existsForSite('accounting_services')],
            'lines.*.customer_order_line_id' => ['nullable', 'integer', Rule::exists('accounting_customer_order_lines', 'id')],
            'lines.*.delivery_note_line_id' => ['nullable', 'integer', Rule::exists('accounting_delivery_note_lines', 'id')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', Rule::in(AccountingSalesInvoiceLine::discountTypes())],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.create_stock_item' => ['nullable', 'boolean'],
        ];
    }

    private function salesInvoicePayload(array $validated, User&Authenticatable $user, array $totals, bool $withCreator = true): array
    {
        $status = $validated['status'] ?? AccountingSalesInvoice::STATUS_DRAFT;

        $payload = [
            'client_id' => $validated['client_id'],
            'customer_order_id' => $validated['customer_order_id'] ?? null,
            'delivery_note_id' => $validated['delivery_note_id'] ?? null,
            'proforma_invoice_id' => $validated['proforma_invoice_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'],
            'status' => $status,
            'payment_terms' => $validated['payment_terms'] ?? AccountingProformaInvoice::PAYMENT_TO_DISCUSS,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'total_ht' => $totals['total_ht'],
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $totals['tax_amount'],
            'total_ttc' => $totals['total_ttc'],
            'balance_due' => $totals['total_ttc'],
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function calculateSalesInvoiceTotals(array $lines, float $taxRate): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $totalHt = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->salesInvoiceLineDiscountAmount($line, $rawTotal);
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

    private function syncSalesInvoiceLines(AccountingSalesInvoice $invoice, CompanySite $site, User&Authenticatable $user, array $lines, string $currency): void
    {
        $invoice->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountType = $this->salesInvoiceLineDiscountType($line);
            $discountValue = (float) ($line['discount_amount'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->salesInvoiceLineDiscountAmount($line, $rawTotal);
            $lineType = $line['line_type'];
            $itemId = ($lineType === AccountingSalesInvoiceLine::TYPE_ITEM) ? ($line['item_id'] ?? null) : null;

            if ($lineType === AccountingSalesInvoiceLine::TYPE_FREE && (bool) ($line['create_stock_item'] ?? false)) {
                $item = $this->createStockItemFromFreeLine($site, $user, $line, $currency);
                $lineType = AccountingSalesInvoiceLine::TYPE_ITEM;
                $itemId = $item->id;
            }

            $invoice->lines()->create([
                'line_type' => $lineType,
                'item_id' => $itemId,
                'service_id' => ($lineType === AccountingSalesInvoiceLine::TYPE_SERVICE) ? ($line['service_id'] ?? null) : null,
                'customer_order_line_id' => $line['customer_order_line_id'] ?? null,
                'delivery_note_line_id' => $line['delivery_note_line_id'] ?? null,
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

    private function salesInvoiceLineDiscountAmount(array $line, float $rawTotal): float
    {
        $discountType = $this->salesInvoiceLineDiscountType($line);
        $discountValue = max(0, (float) ($line['discount_amount'] ?? 0));

        if ($discountType === AccountingSalesInvoiceLine::DISCOUNT_PERCENT) {
            return round(min($discountValue, 100) * $rawTotal / 100, 2);
        }

        return round(min($discountValue, $rawTotal), 2);
    }

    private function salesInvoiceLineDiscountType(array $line): string
    {
        $discountType = $line['discount_type'] ?? AccountingSalesInvoiceLine::DISCOUNT_FIXED;

        return in_array($discountType, AccountingSalesInvoiceLine::discountTypes(), true)
            ? $discountType
            : AccountingSalesInvoiceLine::DISCOUNT_FIXED;
    }

    private function salesInvoiceSourceFromRequest(Request $request, CompanySite $site): ?array
    {
        if ($request->filled('delivery_note')) {
            $deliveryNote = AccountingDeliveryNote::query()
                ->with(['customerOrder', 'lines.item', 'lines.service'])
                ->where('company_site_id', $site->id)
                ->whereKey((int) $request->query('delivery_note'))
                ->first();

            if ($deliveryNote) {
                return [
                    'delivery_note_id' => $deliveryNote->id,
                    'customer_order_id' => $deliveryNote->customer_order_id,
                    'proforma_invoice_id' => $deliveryNote->customerOrder?->proforma_invoice_id,
                    'client_id' => $deliveryNote->client_id,
                    'title' => $deliveryNote->title ?: $deliveryNote->customerOrder?->title,
                    'currency' => $deliveryNote->customerOrder?->currency ?: $site->currency,
                    'payment_terms' => $deliveryNote->customerOrder?->payment_terms,
                    'tax_rate' => $deliveryNote->customerOrder?->tax_rate,
                    'notes' => $deliveryNote->notes,
                    'terms' => $deliveryNote->customerOrder?->terms,
                    'lines' => $deliveryNote->lines->map(fn (AccountingDeliveryNoteLine $line): array => $this->salesInvoiceLineFromDeliveryNoteLine($line))->values()->all(),
                ];
            }
        }

        if ($request->filled('order')) {
            $order = AccountingCustomerOrder::query()
                ->with(['lines.item', 'lines.service'])
                ->where('company_site_id', $site->id)
                ->whereKey((int) $request->query('order'))
                ->first();

            if ($order) {
                return [
                    'customer_order_id' => $order->id,
                    'proforma_invoice_id' => $order->proforma_invoice_id,
                    'client_id' => $order->client_id,
                    'title' => $order->title,
                    'currency' => $order->currency,
                    'payment_terms' => $order->payment_terms,
                    'tax_rate' => $order->tax_rate,
                    'notes' => $order->notes,
                    'terms' => $order->terms,
                    'lines' => $order->lines->map(fn (AccountingCustomerOrderLine $line): array => $this->salesInvoiceLineFromCustomerOrderLine($line))->values()->all(),
                ];
            }
        }

        if ($request->filled('proforma')) {
            $proforma = AccountingProformaInvoice::query()
                ->with(['lines.item', 'lines.service'])
                ->where('company_site_id', $site->id)
                ->whereKey((int) $request->query('proforma'))
                ->first();

            if ($proforma) {
                return [
                    'proforma_invoice_id' => $proforma->id,
                    'client_id' => $proforma->client_id,
                    'title' => $proforma->title,
                    'currency' => $proforma->currency,
                    'payment_terms' => $proforma->payment_terms,
                    'tax_rate' => $proforma->tax_rate,
                    'notes' => $proforma->notes,
                    'terms' => $proforma->terms,
                    'lines' => $proforma->lines->map(fn (AccountingProformaInvoiceLine $line): array => $this->salesInvoiceLineFromProformaLine($line))->values()->all(),
                ];
            }
        }

        return null;
    }

    private function salesInvoiceLineFromCustomerOrderLine(AccountingCustomerOrderLine $line): array
    {
        return [
            'line_type' => $line->line_type,
            'item_id' => $line->line_type === AccountingCustomerOrderLine::TYPE_ITEM ? $line->item_id : null,
            'service_id' => $line->line_type === AccountingCustomerOrderLine::TYPE_SERVICE ? $line->service_id : null,
            'customer_order_line_id' => $line->id,
            'delivery_note_line_id' => null,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => $line->discount_type ?: AccountingSalesInvoiceLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
            'create_stock_item' => '0',
        ];
    }

    private function salesInvoiceLineFromDeliveryNoteLine(AccountingDeliveryNoteLine $line): array
    {
        return [
            'line_type' => $line->line_type,
            'item_id' => $line->line_type === AccountingCustomerOrderLine::TYPE_ITEM ? $line->item_id : null,
            'service_id' => $line->line_type === AccountingCustomerOrderLine::TYPE_SERVICE ? $line->service_id : null,
            'customer_order_line_id' => $line->customer_order_line_id,
            'delivery_note_line_id' => $line->id,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => AccountingSalesInvoiceLine::DISCOUNT_FIXED,
            'discount_amount' => '0',
            'create_stock_item' => '0',
        ];
    }

    private function salesInvoiceLineFromProformaLine(AccountingProformaInvoiceLine $line): array
    {
        return [
            'line_type' => $line->line_type,
            'item_id' => $line->line_type === AccountingProformaInvoiceLine::TYPE_ITEM ? $line->item_id : null,
            'service_id' => $line->line_type === AccountingProformaInvoiceLine::TYPE_SERVICE ? $line->service_id : null,
            'customer_order_line_id' => null,
            'delivery_note_line_id' => null,
            'description' => $line->description,
            'details' => $line->details,
            'quantity' => number_format((float) $line->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            'discount_type' => $line->discount_type ?: AccountingSalesInvoiceLine::DISCOUNT_FIXED,
            'discount_amount' => number_format((float) $line->discount_amount, 2, '.', ''),
            'create_stock_item' => '0',
        ];
    }

    private function creditNoteRules(CompanySite $site): array
    {
        return [
            'sales_invoice_id' => ['required', 'integer', Rule::exists('accounting_sales_invoices', 'id')->where('company_site_id', $site->id)],
            'credit_date' => ['required', 'date'],
            'status' => ['required', Rule::in([AccountingCreditNote::STATUS_DRAFT, AccountingCreditNote::STATUS_VALIDATED])],
            'reason' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_invoice_line_id' => ['required', 'integer', Rule::exists('accounting_sales_invoice_lines', 'id')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function salesInvoiceCanReceiveCreditNote(AccountingSalesInvoice $invoice): bool
    {
        return ! in_array($invoice->status, [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED, AccountingSalesInvoice::STATUS_CREDITED], true)
            && $invoice->creditableAmount() > 0;
    }

    private function creditNoteLineDefaults(AccountingSalesInvoice $invoice): array
    {
        return $invoice->lines->map(function (AccountingSalesInvoiceLine $line) use ($invoice): array {
            $creditedQuantity = (float) AccountingCreditNoteLine::query()
                ->where('sales_invoice_line_id', $line->id)
                ->whereHas('creditNote', fn ($query) => $query
                    ->where('sales_invoice_id', $invoice->id)
                    ->where('status', AccountingCreditNote::STATUS_VALIDATED))
                ->sum('quantity');

            $remainingQuantity = max(0, round((float) $line->quantity - $creditedQuantity, 2));

            return [
                'sales_invoice_line_id' => $line->id,
                'description' => $line->description,
                'details' => $line->details,
                'quantity' => '0',
                'max_quantity' => number_format($remainingQuantity, 2, '.', ''),
                'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
                'line_total' => '0.00',
            ];
        })->values()->all();
    }

    private function prepareCreditNoteLines(AccountingSalesInvoice $invoice, array $lines): array
    {
        $invoiceLines = $invoice->lines->keyBy('id');
        $remainingQuantities = collect($this->creditNoteLineDefaults($invoice))
            ->mapWithKeys(fn (array $line): array => [
                (int) $line['sales_invoice_line_id'] => (float) $line['max_quantity'],
            ]);
        $prepared = [];

        foreach ($lines as $line) {
            $invoiceLineId = (int) ($line['sales_invoice_line_id'] ?? 0);
            $invoiceLine = $invoiceLines->get($invoiceLineId);

            if (! $invoiceLine) {
                continue;
            }

            $quantity = round((float) ($line['quantity'] ?? 0), 2);

            if ($quantity <= 0) {
                continue;
            }

            $remainingQuantity = (float) ($remainingQuantities->get($invoiceLineId) ?? 0);

            if ($quantity > $remainingQuantity) {
                throw ValidationException::withMessages([
                    'lines' => __('main.credit_note_line_quantity_exceeds', [
                        'description' => $invoiceLine->description,
                        'quantity' => number_format($remainingQuantity, 2, ',', ' '),
                    ]),
                ]);
            }

            $unitPrice = round((float) ($line['unit_price'] ?? $invoiceLine->unit_price), 2);
            $lineTotal = round($quantity * $unitPrice, 2);

            $prepared[] = [
                'sales_invoice_line_id' => $invoiceLine->id,
                'description' => $line['description'] ?: $invoiceLine->description,
                'details' => $line['details'] ?? $invoiceLine->details,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return $prepared;
    }

    private function calculateCreditNoteTotals(array $lines, float $taxRate): array
    {
        $subtotal = round(array_sum(array_map(fn (array $line): float => (float) $line['line_total'], $lines)), 2);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_ttc' => round($subtotal + $taxAmount, 2),
        ];
    }

    private function changeAccountingCreditNoteStatus(Company $company, CompanySite $site, AccountingCreditNote $creditNote, string $status, string $message): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_update'] || (int) $creditNote->company_site_id !== (int) $site->id) {
            return redirect()->route('main.accounting.credit-notes', [$company, $site]);
        }

        DB::transaction(function () use ($creditNote, $status): void {
            $lockedCreditNote = AccountingCreditNote::query()
                ->whereKey($creditNote->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === AccountingCreditNote::STATUS_VALIDATED) {
                $invoice = $lockedCreditNote->salesInvoice()->with('creditNotes')->lockForUpdate()->firstOrFail();
                $creditableAmount = $invoice->creditableAmount();

                if ((float) $lockedCreditNote->total_ttc > $creditableAmount) {
                    throw ValidationException::withMessages([
                        'credit_note' => __('main.credit_note_exceeds_invoice_balance', [
                            'amount' => number_format($creditableAmount, 2, ',', ' '),
                            'currency' => $invoice->currency,
                        ]),
                    ]);
                }
            }

            $lockedCreditNote->update(['status' => $status]);
            $this->refreshSalesInvoicePaymentStatus($lockedCreditNote->salesInvoice);
        });

        return redirect()
            ->route('main.accounting.credit-notes', [$company, $site])
            ->with('success', $message);
    }

    private function refreshSalesInvoicePaymentStatus(AccountingSalesInvoice $invoice): void
    {
        $invoice->loadMissing('payments');
        $paidTotal = round((float) $invoice->payments()->sum('amount'), 2);
        $total = round((float) $invoice->total_ttc, 2);
        $creditTotal = round((float) $invoice->creditNotes()
            ->where('status', AccountingCreditNote::STATUS_VALIDATED)
            ->sum('total_ttc'), 2);
        $adjustedTotal = max(0, round($total - $creditTotal, 2));
        $balance = max(0, round($adjustedTotal - $paidTotal, 2));
        $status = $invoice->status;

        if ($status !== AccountingSalesInvoice::STATUS_CANCELLED) {
            if ($creditTotal >= $total && $total > 0) {
                $status = AccountingSalesInvoice::STATUS_CREDITED;
            } elseif ($paidTotal >= $adjustedTotal && $adjustedTotal > 0) {
                $status = AccountingSalesInvoice::STATUS_PAID;
            } elseif ($paidTotal > 0) {
                $status = AccountingSalesInvoice::STATUS_PARTIALLY_PAID;
            } elseif ($status !== AccountingSalesInvoice::STATUS_DRAFT) {
                $status = $invoice->due_date && $invoice->due_date->lt(now()->startOfDay())
                    ? AccountingSalesInvoice::STATUS_OVERDUE
                    : AccountingSalesInvoice::STATUS_ISSUED;
            }
        }

        $invoice->forceFill([
            'paid_total' => min($paidTotal, $total),
            'credit_total' => min($creditTotal, $total),
            'balance_due' => $balance,
            'status' => $status,
        ])->save();
    }

    private function refreshOverdueSalesInvoices(CompanySite $site): void
    {
        AccountingSalesInvoice::query()
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingSalesInvoice::STATUS_ISSUED, AccountingSalesInvoice::STATUS_PARTIALLY_PAID])
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('balance_due', '>', 0)
            ->update(['status' => AccountingSalesInvoice::STATUS_OVERDUE]);
    }

    private function salesInvoicePaymentMethodOptions(CompanySite $site)
    {
        $this->ensureDefaultAccountingPaymentMethodRecord($site);

        return AccountingPaymentMethod::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    private function salesInvoiceStatusLabels(): array
    {
        return [
            AccountingSalesInvoice::STATUS_DRAFT => __('main.sales_invoice_status_draft'),
            AccountingSalesInvoice::STATUS_ISSUED => __('main.sales_invoice_status_issued'),
            AccountingSalesInvoice::STATUS_PARTIALLY_PAID => __('main.sales_invoice_status_partially_paid'),
            AccountingSalesInvoice::STATUS_PAID => __('main.sales_invoice_status_paid'),
            AccountingSalesInvoice::STATUS_OVERDUE => __('main.sales_invoice_status_overdue'),
            AccountingSalesInvoice::STATUS_CANCELLED => __('main.sales_invoice_status_cancelled'),
            AccountingSalesInvoice::STATUS_CREDITED => __('main.sales_invoice_status_credited'),
        ];
    }

    private function creditNoteStatusLabels(): array
    {
        return [
            AccountingCreditNote::STATUS_DRAFT => __('main.credit_note_status_draft'),
            AccountingCreditNote::STATUS_VALIDATED => __('main.credit_note_status_validated'),
            AccountingCreditNote::STATUS_CANCELLED => __('main.credit_note_status_cancelled'),
        ];
    }

    private function salesInvoiceLineTypeLabels(): array
    {
        return [
            AccountingSalesInvoiceLine::TYPE_ITEM => __('main.proforma_line_item'),
            AccountingSalesInvoiceLine::TYPE_SERVICE => __('main.proforma_line_service'),
            AccountingSalesInvoiceLine::TYPE_FREE => __('main.proforma_line_free'),
        ];
    }

    private function purchaseSupplierOptions(CompanySite $site): array
    {
        return AccountingSupplier::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingSupplier::STATUS_ACTIVE)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingSupplier $supplier) => [$supplier->id => "{$supplier->name} ({$supplier->reference})"])
            ->all();
    }

    private function purchaseItemOptions(CompanySite $site): array
    {
        return AccountingStockItem::query()
            ->where('company_site_id', $site->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingStockItem $item) => [$item->id => [
                'label' => "{$item->name} ({$item->reference})",
                'price' => (float) $item->purchase_price,
            ]])
            ->all();
    }

    private function purchaseServiceOptions(CompanySite $site): array
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

    private function purchaseStatusLabels(): array
    {
        return [
            AccountingPurchase::STATUS_DRAFT => __('main.purchase_status_draft'),
            AccountingPurchase::STATUS_VALIDATED => __('main.purchase_status_validated'),
            AccountingPurchase::STATUS_PARTIALLY_PAID => __('main.purchase_status_partially_paid'),
            AccountingPurchase::STATUS_PAID => __('main.purchase_status_paid'),
            AccountingPurchase::STATUS_OVERDUE => __('main.purchase_status_overdue'),
            AccountingPurchase::STATUS_CANCELLED => __('main.purchase_status_cancelled'),
        ];
    }

    private function purchaseLineTypeLabels(): array
    {
        return [
            AccountingPurchaseLine::TYPE_ITEM => __('main.proforma_line_item'),
            AccountingPurchaseLine::TYPE_SERVICE => __('main.proforma_line_service'),
            AccountingPurchaseLine::TYPE_FREE => __('main.proforma_line_free'),
        ];
    }

    private function purchaseRules(CompanySite $site, bool $updating = false): array
    {
        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);

        return [
            'supplier_id' => ['required', 'integer', $existsForSite('accounting_suppliers')],
            'supplier_invoice_reference' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'currency' => [
                'required',
                'string',
                Rule::exists('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingCurrency::STATUS_ACTIVE),
            ],
            'status' => [$updating ? 'required' : 'nullable', Rule::in(AccountingPurchase::statuses())],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in(AccountingPurchaseLine::types())],
            'lines.*.item_id' => ['nullable', 'integer', $existsForSite('accounting_stock_items')],
            'lines.*.service_id' => ['nullable', 'integer', $existsForSite('accounting_services')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', Rule::in(AccountingPurchaseLine::discountTypes())],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.create_stock_item' => ['nullable', 'boolean'],
        ];
    }

    private function purchasePayload(array $validated, User&Authenticatable $user, array $totals, bool $withCreator = true): array
    {
        $status = $validated['status'] ?? AccountingPurchase::STATUS_DRAFT;
        $balanceDue = in_array($status, [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED], true)
            ? 0
            : $totals['total_ttc'];

        $payload = [
            'supplier_id' => $validated['supplier_id'],
            'supplier_invoice_reference' => $validated['supplier_invoice_reference'] ?? null,
            'title' => $validated['title'] ?? null,
            'purchase_date' => $validated['purchase_date'],
            'due_date' => $validated['due_date'] ?? null,
            'currency' => $validated['currency'],
            'status' => $status,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'total_ht' => $totals['total_ht'],
            'tax_rate' => $validated['tax_rate'],
            'tax_amount' => $totals['tax_amount'],
            'total_ttc' => $totals['total_ttc'],
            'balance_due' => $balanceDue,
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function calculatePurchaseTotals(array $lines, float $taxRate): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $totalHt = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->purchaseLineDiscountAmount($line, $rawTotal);
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

    private function syncPurchaseLines(AccountingPurchase $purchase, CompanySite $site, User&Authenticatable $user, array $lines, string $currency): void
    {
        $purchase->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountType = $this->purchaseLineDiscountType($line);
            $discountValue = (float) ($line['discount_amount'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->purchaseLineDiscountAmount($line, $rawTotal);
            $lineType = $line['line_type'];
            $itemId = ($lineType === AccountingPurchaseLine::TYPE_ITEM) ? ($line['item_id'] ?? null) : null;

            if ($lineType === AccountingPurchaseLine::TYPE_FREE && (bool) ($line['create_stock_item'] ?? false)) {
                $item = $this->createStockItemFromFreeLine($site, $user, [
                    'description' => $line['description'],
                    'details' => $line['details'] ?? null,
                    'cost_price' => $line['unit_price'],
                    'unit_price' => $line['unit_price'],
                ], $currency);
                $lineType = AccountingPurchaseLine::TYPE_ITEM;
                $itemId = $item->id;
            }

            $purchase->lines()->create([
                'line_type' => $lineType,
                'item_id' => $itemId,
                'service_id' => ($lineType === AccountingPurchaseLine::TYPE_SERVICE) ? ($line['service_id'] ?? null) : null,
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

    private function purchaseLineDiscountAmount(array $line, float $rawTotal): float
    {
        $discountType = $this->purchaseLineDiscountType($line);
        $discountValue = max(0, (float) ($line['discount_amount'] ?? 0));

        if ($discountType === AccountingPurchaseLine::DISCOUNT_PERCENT) {
            return round(min($discountValue, 100) * $rawTotal / 100, 2);
        }

        return round(min($discountValue, $rawTotal), 2);
    }

    private function purchaseLineDiscountType(array $line): string
    {
        $discountType = $line['discount_type'] ?? AccountingPurchaseLine::DISCOUNT_FIXED;

        return in_array($discountType, AccountingPurchaseLine::discountTypes(), true)
            ? $discountType
            : AccountingPurchaseLine::DISCOUNT_FIXED;
    }

    private function refreshPurchasePaymentStatus(AccountingPurchase $purchase): void
    {
        $paidTotal = round((float) $purchase->payments()->sum('amount'), 2);
        $totalTtc = round((float) $purchase->total_ttc, 2);
        $balanceDue = max(0, round($totalTtc - $paidTotal, 2));
        $status = $purchase->status;

        if (! in_array($status, [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED], true)) {
            if ($balanceDue <= 0.0 && $totalTtc > 0) {
                $status = AccountingPurchase::STATUS_PAID;
            } elseif ($paidTotal > 0) {
                $status = AccountingPurchase::STATUS_PARTIALLY_PAID;
            } elseif ($purchase->due_date && $purchase->due_date->isPast()) {
                $status = AccountingPurchase::STATUS_OVERDUE;
            } else {
                $status = AccountingPurchase::STATUS_VALIDATED;
            }
        }

        $purchase->forceFill([
            'paid_total' => $paidTotal,
            'balance_due' => in_array($status, [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED], true) ? 0 : $balanceDue,
            'status' => $status,
        ])->saveQuietly();
    }

    private function refreshOverdueAccountingPurchases(CompanySite $site): void
    {
        AccountingPurchase::query()
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingPurchase::STATUS_VALIDATED, AccountingPurchase::STATUS_PARTIALLY_PAID])
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('balance_due', '>', 0)
            ->update([
                'status' => AccountingPurchase::STATUS_OVERDUE,
                'updated_at' => now(),
            ]);
    }

    private function customerOrderRules(CompanySite $site, bool $updating = false): array
    {
        $existsForSite = fn (string $table) => Rule::exists($table, 'id')->where('company_site_id', $site->id);

        return [
            'client_id' => ['required', 'integer', $existsForSite('accounting_clients')],
            'title' => ['nullable', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'currency' => [
                'required',
                'string',
                Rule::exists('accounting_currencies', 'code')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingCurrency::STATUS_ACTIVE),
            ],
            'status' => [$updating ? 'required' : 'nullable', Rule::in(AccountingCustomerOrder::statuses())],
            'payment_terms' => ['nullable', Rule::in(AccountingProformaInvoice::paymentTerms())],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in(AccountingCustomerOrderLine::types())],
            'lines.*.item_id' => ['nullable', 'integer', $existsForSite('accounting_stock_items')],
            'lines.*.service_id' => ['nullable', 'integer', $existsForSite('accounting_services')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.cost_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.margin_type' => ['nullable', Rule::in(AccountingCustomerOrderLine::marginTypes())],
            'lines.*.margin_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', Rule::in(AccountingCustomerOrderLine::discountTypes())],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.create_stock_item' => ['nullable', 'boolean'],
        ];
    }

    private function customerOrderPayload(array $validated, User&Authenticatable $user, array $totals, bool $withCreator = true): array
    {
        $payload = [
            'client_id' => $validated['client_id'],
            'title' => $validated['title'] ?? null,
            'order_date' => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'currency' => $validated['currency'],
            'status' => $validated['status'] ?? AccountingCustomerOrder::STATUS_DRAFT,
            'payment_terms' => $validated['payment_terms'] ?? AccountingProformaInvoice::PAYMENT_TO_DISCUSS,
            'subtotal' => $totals['subtotal'],
            'cost_total' => $totals['cost_total'],
            'margin_total' => $totals['margin_total'],
            'margin_rate' => $totals['margin_rate'],
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

    private function calculateCustomerOrderTotals(array $lines, float $taxRate): array
    {
        $subtotal = 0;
        $costTotal = 0;
        $discountTotal = 0;
        $totalHt = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $costPrice = (float) ($line['cost_price'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $lineCostTotal = $quantity * $costPrice;
            $discount = $this->customerOrderLineDiscountAmount($line, $rawTotal);
            $lineTotal = max(0, $rawTotal - $discount);

            $subtotal += $rawTotal;
            $costTotal += $lineCostTotal;
            $discountTotal += $discount;
            $totalHt += $lineTotal;
        }

        $marginTotal = $totalHt - $costTotal;
        $taxAmount = round($totalHt * ($taxRate / 100), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'cost_total' => round($costTotal, 2),
            'margin_total' => round($marginTotal, 2),
            'margin_rate' => $costTotal > 0 ? round(($marginTotal / $costTotal) * 100, 2) : 0,
            'discount_total' => round($discountTotal, 2),
            'total_ht' => round($totalHt, 2),
            'tax_amount' => $taxAmount,
            'total_ttc' => round($totalHt + $taxAmount, 2),
        ];
    }

    private function syncCustomerOrderLines(AccountingCustomerOrder $order, CompanySite $site, User&Authenticatable $user, array $lines, string $currency): void
    {
        $order->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $costPrice = (float) ($line['cost_price'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountType = $this->customerOrderLineDiscountType($line);
            $discountValue = (float) ($line['discount_amount'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $costTotal = $quantity * $costPrice;
            $discount = $this->customerOrderLineDiscountAmount($line, $rawTotal);
            $lineTotal = max(0, $rawTotal - $discount);
            $lineType = $line['line_type'];
            $itemId = ($lineType === AccountingCustomerOrderLine::TYPE_ITEM) ? ($line['item_id'] ?? null) : null;

            if ($lineType === AccountingCustomerOrderLine::TYPE_FREE && (bool) ($line['create_stock_item'] ?? false)) {
                $item = $this->createStockItemFromFreeLine($site, $user, $line, $currency);
                $lineType = AccountingCustomerOrderLine::TYPE_ITEM;
                $itemId = $item->id;
            }

            $order->lines()->create([
                'line_type' => $lineType,
                'item_id' => $itemId,
                'service_id' => ($lineType === AccountingCustomerOrderLine::TYPE_SERVICE) ? ($line['service_id'] ?? null) : null,
                'description' => $line['description'],
                'details' => $line['details'] ?? null,
                'quantity' => $quantity,
                'cost_price' => $costPrice,
                'unit_price' => $unitPrice,
                'margin_type' => $this->customerOrderLineMarginType($line),
                'margin_value' => (float) ($line['margin_value'] ?? 0),
                'discount_type' => $discountType,
                'discount_amount' => $discountValue,
                'cost_total' => $costTotal,
                'margin_total' => $lineTotal - $costTotal,
                'line_total' => $lineTotal,
            ]);
        }
    }

    private function createStockItemFromFreeLine(CompanySite $site, User&Authenticatable $user, array $line, string $currency): AccountingStockItem
    {
        $this->ensureDefaultAccountingStockRecords($site);

        $warehouse = AccountingStockWarehouse::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();

        $category = AccountingStockCategory::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();

        $subcategory = AccountingStockSubcategory::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->first();

        $unit = AccountingStockUnit::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();

        return $site->accountingStockItems()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory?->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'name' => $line['description'],
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'purchase_price' => (float) ($line['cost_price'] ?? 0),
            'sale_price' => (float) ($line['unit_price'] ?? 0),
            'current_stock' => 0,
            'min_stock' => 0,
            'currency' => $currency,
            'status' => AccountingStockCategory::STATUS_ACTIVE,
            'description' => $line['details'] ?? null,
        ]);
    }

    private function customerOrderLineDiscountAmount(array $line, float $rawTotal): float
    {
        $discountType = $this->customerOrderLineDiscountType($line);
        $discountValue = max(0, (float) ($line['discount_amount'] ?? 0));

        if ($discountType === AccountingCustomerOrderLine::DISCOUNT_PERCENT) {
            return round(min($discountValue, 100) * $rawTotal / 100, 2);
        }

        return round(min($discountValue, $rawTotal), 2);
    }

    private function customerOrderLineDiscountType(array $line): string
    {
        $discountType = $line['discount_type'] ?? AccountingCustomerOrderLine::DISCOUNT_FIXED;

        return in_array($discountType, AccountingCustomerOrderLine::discountTypes(), true)
            ? $discountType
            : AccountingCustomerOrderLine::DISCOUNT_FIXED;
    }

    private function customerOrderLineMarginType(array $line): string
    {
        $marginType = $line['margin_type'] ?? AccountingCustomerOrderLine::MARGIN_FIXED;

        return in_array($marginType, AccountingCustomerOrderLine::marginTypes(), true)
            ? $marginType
            : AccountingCustomerOrderLine::MARGIN_FIXED;
    }

    private function customerOrderItemOptions(CompanySite $site): array
    {
        return AccountingStockItem::query()
            ->where('company_site_id', $site->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingStockItem $item) => [$item->id => [
                'label' => "{$item->name} ({$item->reference})",
                'price' => (float) $item->sale_price,
                'cost' => (float) $item->purchase_price,
            ]])
            ->all();
    }

    private function customerOrderServiceOptions(CompanySite $site): array
    {
        return AccountingService::query()
            ->where('company_site_id', $site->id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AccountingService $service) => [$service->id => [
                'label' => "{$service->name} ({$service->reference})",
                'price' => (float) $service->price,
                'cost' => 0,
            ]])
            ->all();
    }

    private function customerOrderStatusLabels(): array
    {
        return [
            AccountingCustomerOrder::STATUS_DRAFT => __('main.customer_order_status_draft'),
            AccountingCustomerOrder::STATUS_CONFIRMED => __('main.customer_order_status_confirmed'),
            AccountingCustomerOrder::STATUS_IN_PROGRESS => __('main.customer_order_status_in_progress'),
            AccountingCustomerOrder::STATUS_DELIVERED => __('main.customer_order_status_delivered'),
            AccountingCustomerOrder::STATUS_CANCELLED => __('main.customer_order_status_cancelled'),
        ];
    }

    private function customerOrderLineTypeLabels(): array
    {
        return [
            AccountingCustomerOrderLine::TYPE_ITEM => __('main.proforma_line_item'),
            AccountingCustomerOrderLine::TYPE_SERVICE => __('main.proforma_line_service'),
            AccountingCustomerOrderLine::TYPE_FREE => __('main.proforma_line_free'),
        ];
    }

    private function deliverableCustomerOrders(CompanySite $site)
    {
        return AccountingCustomerOrder::query()
            ->with(['client', 'lines'])
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingCustomerOrder::STATUS_CONFIRMED, AccountingCustomerOrder::STATUS_IN_PROGRESS])
            ->latest('order_date')
            ->get()
            ->filter(fn (AccountingCustomerOrder $order): bool => $this->deliveryNoteLinesFromOrder($order) !== [])
            ->values();
    }

    private function deliveryNoteRules(CompanySite $site): array
    {
        return [
            'customer_order_id' => [
                'required',
                'integer',
                Rule::exists('accounting_customer_orders', 'id')
                    ->where('company_site_id', $site->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'delivery_date' => ['required', 'date'],
            'status' => ['required', Rule::in(AccountingDeliveryNote::statuses())],
            'delivered_by' => ['nullable', 'string', 'max:255'],
            'carrier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.customer_order_line_id' => ['required', 'integer', Rule::exists('accounting_customer_order_lines', 'id')],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.serial_numbers' => ['nullable', 'array'],
            'lines.*.serial_numbers.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    private function deliveryNoteLinesFromOrder(AccountingCustomerOrder $order): array
    {
        $lines = [];

        foreach ($order->lines as $line) {
            $orderedQuantity = (float) $line->quantity;
            $alreadyDelivered = $this->deliveredQuantityForOrderLine($line);
            $remaining = max(0, $orderedQuantity - $alreadyDelivered);

            if ($remaining <= 0) {
                continue;
            }

            $lines[] = [
                'customer_order_line_id' => $line->id,
                'line_type' => $line->line_type,
                'description' => $line->description,
                'details' => $line->details,
                'ordered_quantity' => number_format($orderedQuantity, 2, '.', ''),
                'already_delivered_quantity' => number_format($alreadyDelivered, 2, '.', ''),
                'remaining_quantity' => number_format($remaining, 2, '.', ''),
                'quantity' => number_format($remaining, 2, '.', ''),
                'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            ];
        }

        return $lines;
    }

    private function validatedDeliveryLines(AccountingCustomerOrder $order, array $submittedLines): array
    {
        $orderLines = $order->lines->keyBy('id');
        $payload = [];

        foreach ($submittedLines as $index => $submittedLine) {
            $orderLineId = (int) ($submittedLine['customer_order_line_id'] ?? 0);
            $orderLine = $orderLines->get($orderLineId);

            if (! $orderLine) {
                throw ValidationException::withMessages(["lines.$index.customer_order_line_id" => __('validation.exists', ['attribute' => __('main.order_line')])]);
            }

            $quantity = (float) ($submittedLine['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $orderedQuantity = (float) $orderLine->quantity;
            $alreadyDelivered = $this->deliveredQuantityForOrderLine($orderLine);
            $remaining = max(0, $orderedQuantity - $alreadyDelivered);

            if ($quantity > $remaining) {
                throw ValidationException::withMessages(["lines.$index.quantity" => __('main.delivery_quantity_exceeds_remaining')]);
            }

            $serialNumbers = $this->deliverySerialNumbersForLine($submittedLine, $quantity, $orderLine, $index);

            $payload[] = [
                'customer_order_line_id' => $orderLine->id,
                'line_type' => $orderLine->line_type,
                'item_id' => $orderLine->line_type === AccountingCustomerOrderLine::TYPE_ITEM ? $orderLine->item_id : null,
                'service_id' => $orderLine->line_type === AccountingCustomerOrderLine::TYPE_SERVICE ? $orderLine->service_id : null,
                'description' => $orderLine->description,
                'details' => $orderLine->details,
                'ordered_quantity' => $orderedQuantity,
                'already_delivered_quantity' => $alreadyDelivered,
                'quantity' => $quantity,
                'unit_price' => (float) $orderLine->unit_price,
                'line_total' => round($quantity * (float) $orderLine->unit_price, 2),
                'serial_numbers' => $serialNumbers,
            ];
        }

        return $payload;
    }

    private function deliverySerialNumbersForLine(array $submittedLine, float $quantity, AccountingCustomerOrderLine $orderLine, int $index): array
    {
        if ($orderLine->line_type !== AccountingCustomerOrderLine::TYPE_ITEM) {
            return [];
        }

        $serialNumbers = collect($submittedLine['serial_numbers'] ?? [])
            ->map(fn ($serialNumber): string => trim((string) $serialNumber))
            ->filter()
            ->values();

        $uniqueSerialNumbers = $serialNumbers->uniqueStrict()->values();

        if ($uniqueSerialNumbers->count() !== $serialNumbers->count()) {
            throw ValidationException::withMessages(["lines.$index.serial_numbers" => __('main.delivery_serial_numbers_unique')]);
        }

        $maxSerials = max(0, (int) floor($quantity));

        if ($serialNumbers->count() > $maxSerials) {
            throw ValidationException::withMessages(["lines.$index.serial_numbers" => __('main.delivery_serial_numbers_limit', ['count' => $maxSerials])]);
        }

        return $serialNumbers->all();
    }

    private function syncDeliveryNoteLineSerials(AccountingDeliveryNoteLine $line, array $serialNumbers): void
    {
        foreach (array_values($serialNumbers) as $position => $serialNumber) {
            $line->serials()->create([
                'serial_number' => $serialNumber,
                'position' => $position + 1,
            ]);
        }
    }

    private function deliveredQuantityForOrderLine(AccountingCustomerOrderLine $line): float
    {
        return (float) AccountingDeliveryNoteLine::query()
            ->where('customer_order_line_id', $line->id)
            ->whereHas('deliveryNote', function ($query): void {
                $query->whereIn('status', [AccountingDeliveryNote::STATUS_PARTIAL, AccountingDeliveryNote::STATUS_DELIVERED]);
            })
            ->sum('quantity');
    }

    private function releaseDeliveryNoteStock(AccountingDeliveryNote $deliveryNote, CompanySite $site, User&Authenticatable $user): void
    {
        if ($deliveryNote->isStockReleased()) {
            return;
        }

        $deliveryNote->loadMissing('lines.item.defaultWarehouse');

        foreach ($deliveryNote->lines as $line) {
            if ($line->line_type !== AccountingCustomerOrderLine::TYPE_ITEM || ! $line->item_id || (float) $line->quantity <= 0) {
                continue;
            }

            $item = AccountingStockItem::query()
                ->where('company_site_id', $site->id)
                ->whereKey($line->item_id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                continue;
            }

            if ((float) $item->current_stock < (float) $line->quantity) {
                throw ValidationException::withMessages(['lines' => __('main.delivery_stock_unavailable', ['item' => $item->name])]);
            }

            $warehouseId = $item->default_warehouse_id ?: AccountingStockWarehouse::query()
                ->where('company_site_id', $site->id)
                ->where('is_default', true)
                ->value('id');

            $movement = $site->accountingStockMovements()->create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'created_by' => $user->id,
                'type' => AccountingStockMovement::TYPE_EXIT,
                'quantity' => (float) $line->quantity,
                'movement_date' => $deliveryNote->delivery_date?->format('Y-m-d'),
                'reason' => __('main.delivery_note_stock_reason', ['reference' => $deliveryNote->reference]),
                'notes' => $deliveryNote->notes,
            ]);

            $this->applyAccountingStockMovement($movement);
        }

        $deliveryNote->update(['stock_released_at' => now()]);
    }

    private function releaseCashRegisterSaleStock(array $lines, AccountingSalesInvoice $invoice, CompanySite $site, User&Authenticatable $user): void
    {
        foreach ($lines as $line) {
            if (($line['line_type'] ?? null) !== AccountingSalesInvoiceLine::TYPE_ITEM || empty($line['item_id'])) {
                continue;
            }

            $quantity = (float) ($line['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $item = AccountingStockItem::query()
                ->where('company_site_id', $site->id)
                ->whereKey((int) $line['item_id'])
                ->lockForUpdate()
                ->first();

            if (! $item) {
                continue;
            }

            if ((float) $item->current_stock < $quantity) {
                throw ValidationException::withMessages(['lines' => __('main.delivery_stock_unavailable', ['item' => $item->name])]);
            }

            $warehouseId = $item->default_warehouse_id ?: AccountingStockWarehouse::query()
                ->where('company_site_id', $site->id)
                ->where('is_default', true)
                ->value('id');

            $movement = $site->accountingStockMovements()->create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'created_by' => $user->id,
                'type' => AccountingStockMovement::TYPE_EXIT,
                'quantity' => $quantity,
                'movement_date' => $invoice->invoice_date?->format('Y-m-d'),
                'reason' => __('main.cash_register_stock_reason', ['reference' => $invoice->reference]),
                'notes' => $invoice->notes,
            ]);

            $this->applyAccountingStockMovement($movement);
        }
    }

    private function cashRegisterSessionExpectedAmounts(AccountingCashRegisterSession $session): array
    {
        $payments = AccountingSalesInvoicePayment::query()
            ->whereHas('salesInvoice', function ($query) use ($session): void {
                $query->where('cash_register_session_id', $session->id);
            })
            ->with('paymentMethod:id,type')
            ->get();

        $salesCount = AccountingSalesInvoice::query()
            ->where('cash_register_session_id', $session->id)
            ->count();

        $cashPayments = $payments
            ->filter(fn (AccountingSalesInvoicePayment $payment): bool => $payment->paymentMethod?->type === AccountingPaymentMethod::TYPE_CASH)
            ->sum(fn (AccountingSalesInvoicePayment $payment): float => (float) $payment->amount);

        $otherPayments = $payments
            ->reject(fn (AccountingSalesInvoicePayment $payment): bool => $payment->paymentMethod?->type === AccountingPaymentMethod::TYPE_CASH)
            ->sum(fn (AccountingSalesInvoicePayment $payment): float => (float) $payment->amount);

        $cash = (float) $session->opening_float + (float) $cashPayments;

        return [
            'cash' => $cash,
            'other' => (float) $otherPayments,
            'total' => $cash + (float) $otherPayments,
            'sales_count' => $salesCount,
        ];
    }

    private function cashRegisterWalkInClient(CompanySite $site, User&Authenticatable $user): AccountingClient
    {
        $client = AccountingClient::query()
            ->where('company_site_id', $site->id)
            ->whereIn('name', AccountingClient::walkInCustomerNames())
            ->first();

        if ($client) {
            return $client;
        }

        return AccountingClient::query()->create([
            'company_site_id' => $site->id,
            'created_by' => $user->id,
            'type' => AccountingClient::TYPE_INDIVIDUAL,
            'name' => __('main.walk_in_customer'),
        ]);
    }

    private function refreshCustomerOrderDeliveryStatus(AccountingCustomerOrder $order): void
    {
        if ($order->status === AccountingCustomerOrder::STATUS_CANCELLED) {
            return;
        }

        $order->loadMissing('lines');
        $orderedQuantity = (float) $order->lines->sum(fn (AccountingCustomerOrderLine $line): float => (float) $line->quantity);
        $deliveredQuantity = (float) $order->lines->sum(fn (AccountingCustomerOrderLine $line): float => $this->deliveredQuantityForOrderLine($line));

        if ($orderedQuantity > 0 && $deliveredQuantity >= $orderedQuantity) {
            $order->update(['status' => AccountingCustomerOrder::STATUS_DELIVERED]);
            return;
        }

        if ($deliveredQuantity > 0) {
            $order->update(['status' => AccountingCustomerOrder::STATUS_IN_PROGRESS]);
        }
    }

    private function deliveryNoteStatusLabels(): array
    {
        return [
            AccountingDeliveryNote::STATUS_DRAFT => __('main.delivery_note_status_draft'),
            AccountingDeliveryNote::STATUS_READY => __('main.delivery_note_status_ready'),
            AccountingDeliveryNote::STATUS_PARTIAL => __('main.delivery_note_status_partial'),
            AccountingDeliveryNote::STATUS_DELIVERED => __('main.delivery_note_status_delivered'),
            AccountingDeliveryNote::STATUS_CANCELLED => __('main.delivery_note_status_cancelled'),
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

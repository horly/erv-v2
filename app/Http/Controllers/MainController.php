<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use App\Models\AccountingBankReconciliation;
use App\Models\AccountingBankStatementLine;
use App\Models\AccountingCashRegisterSession;
use App\Models\AccountingClient;
use App\Models\AccountingCreditor;
use App\Models\AccountingCreditorPayment;
use App\Models\AccountingCreditNote;
use App\Models\AccountingCreditNoteLine;
use App\Models\AccountingCurrency;
use App\Models\AccountingCustomerOrder;
use App\Models\AccountingCustomerOrderLine;
use App\Models\AccountingMenuPermission;
use App\Models\AccountingModuleSetting;
use App\Models\AccountingNotification;
use App\Models\AccountingDebtor;
use App\Models\AccountingDebtorPayment;
use App\Models\AccountingDeliveryNote;
use App\Models\AccountingDeliveryNoteLine;
use App\Models\AccountingExpense;
use App\Models\AccountingExpenseCategory;
use App\Models\AccountingOtherIncome;
use App\Models\AccountingPaymentMethod;
use App\Models\AccountingPaymentPromise;
use App\Models\AccountingPaymentReminder;
use App\Models\AccountingPaymentReminderAction;
use App\Models\AccountingPartner;
use App\Models\AccountingProformaInvoice;
use App\Models\AccountingProformaInvoiceLine;
use App\Models\AccountingProspect;
use App\Models\AccountingPurchase;
use App\Models\AccountingPurchaseLine;
use App\Models\AccountingPurchaseOrder;
use App\Models\AccountingPurchaseOrderLine;
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
use App\Models\AccountingTask;
use App\Models\AccountingTaskActivity;
use App\Models\AccountingTax;
use App\Models\AccountingTreasuryMovement;
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
use App\Support\AccountingModuleNavigation;
use App\Support\AccountingActivityFeed;
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
            'accountingDashboard' => $module === CompanySite::MODULE_ACCOUNTING
                ? $this->accountingDashboardData($site)
                : [],
        ]);
    }

    public function accountingSettings(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        abort_unless($user->isAdmin() || $user->isSuperadmin(), Response::HTTP_FORBIDDEN);

        $settings = AccountingModuleSetting::query()
            ->firstOrNew(['company_site_id' => $site->id], AccountingModuleSetting::defaults());
        $managedUsers = $site->users()
            ->where('users.role', User::ROLE_USER)
            ->orderBy('users.name')
            ->paginate(1, ['users.*'], 'users_page')
            ->withQueryString();
        $savedMenuPermissions = AccountingMenuPermission::query()
            ->where('company_site_id', $site->id)
            ->whereIn('user_id', $managedUsers->getCollection()->pluck('id'))
            ->get()
            ->groupBy('user_id');
        $allMenuKeys = AccountingModuleNavigation::keys();
        $menuSelections = $managedUsers->getCollection()->mapWithKeys(function (User $account) use ($allMenuKeys, $savedMenuPermissions): array {
            $permissionRows = $savedMenuPermissions->get($account->id, collect());

            return [
                $account->id => $permissionRows->isEmpty()
                    ? $allMenuKeys
                    : $permissionRows->where('is_allowed', true)->pluck('menu_key')->all(),
            ];
        });

        return view('main.modules.accounting-settings', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'settings' => $settings,
            'managedUsers' => $managedUsers,
            'menuSelections' => $menuSelections,
            'menuGroups' => $this->accountingSettingsMenuGroups(),
        ]);
    }

    public function updateAccountingSettings(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless($user->isAdmin() || $user->isSuperadmin(), Response::HTTP_FORBIDDEN);

        $menuKeys = AccountingModuleNavigation::keys();
        $managedUserIds = $site->users()
            ->where('users.role', User::ROLE_USER)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validated = $request->validate([
            'pdf_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pdf_accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pdf_tint_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pdf_show_qr_code' => ['nullable', 'boolean'],
            'pdf_show_footer_branding' => ['nullable', 'boolean'],
            'access_user_ids' => ['nullable', 'array'],
            'access_user_ids.*' => ['integer', Rule::in($managedUserIds)],
            'menu_access' => ['nullable', 'array'],
            'menu_access.*' => ['nullable', 'array'],
            'menu_access.*.*' => ['string', Rule::in($menuKeys)],
        ], [
            'pdf_primary_color.regex' => __('main.pdf_color_invalid'),
            'pdf_accent_color.regex' => __('main.pdf_color_invalid'),
            'pdf_tint_color.regex' => __('main.pdf_color_invalid'),
        ]);

        $submittedUserIds = collect($validated['access_user_ids'] ?? array_keys($validated['menu_access'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->intersect($managedUserIds)
            ->values()
            ->all();

        DB::transaction(function () use ($request, $site, $validated, $menuKeys, $submittedUserIds): void {
            AccountingModuleSetting::query()->updateOrCreate(
                ['company_site_id' => $site->id],
                [
                    'pdf_primary_color' => strtoupper($validated['pdf_primary_color']),
                    'pdf_accent_color' => strtoupper($validated['pdf_accent_color']),
                    'pdf_tint_color' => strtoupper($validated['pdf_tint_color']),
                    'pdf_show_qr_code' => $request->boolean('pdf_show_qr_code'),
                    'pdf_show_footer_branding' => $request->boolean('pdf_show_footer_branding'),
                ],
            );

            AccountingMenuPermission::query()
                ->where('company_site_id', $site->id)
                ->whereIn('user_id', $submittedUserIds)
                ->delete();

            foreach ($submittedUserIds as $managedUserId) {
                $selectedMenuKeys = data_get($validated, 'menu_access.'.$managedUserId, []);

                foreach ($menuKeys as $menuKey) {
                    AccountingMenuPermission::query()->create([
                        'company_site_id' => $site->id,
                        'user_id' => $managedUserId,
                        'menu_key' => $menuKey,
                        'is_allowed' => in_array($menuKey, $selectedMenuKeys, true),
                    ]);
                }
            }
        });

        return redirect()
            ->route('main.accounting.settings', [$company, $site])
            ->with('success', __('main.module_settings_saved'));
    }

    public function accountingNotifications(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        AccountingActivityFeed::syncSite($site);

        $status = $request->query('status', 'all');
        $notifications = AccountingNotification::query()
            ->with(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('company_site_id', $site->id)
            ->when($status === 'unread', fn ($query) => $query->whereDoesntHave('reads', fn ($readQuery) => $readQuery->where('user_id', $user->id)->whereNotNull('read_at')))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('main.modules.accounting-notifications', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'notifications' => $notifications,
            'status' => $status,
        ]);
    }

    public function showAccountingNotification(Company $company, CompanySite $site, AccountingNotification $notification): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        abort_unless((int) $notification->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);

        $notification->load(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]);
        $notification->markReadBy($user);
        $moduleUrl = $this->canOpenAccountingNotificationModule($user, $site, $notification->module_key)
            ? AccountingModuleNavigation::urlForKey($notification->module_key, $company, $site)
            : null;

        return view('main.modules.accounting-notification-show', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'notification' => $notification->fresh(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]),
            'moduleLabel' => $this->accountingModuleLabel($notification->module_key),
            'moduleUrl' => $moduleUrl,
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

        $creditorsQuery = AccountingCreditor::query()
            ->selectRaw('
                MIN(id) as id,
                MIN(reference) as reference,
                company_site_id,
                type,
                name,
                phone,
                email,
                address,
                currency,
                SUM(initial_amount) as initial_amount,
                SUM(paid_amount) as paid_amount,
                MIN(due_date) as due_date,
                MIN(description) as description,
                MIN(priority) as priority,
                CASE
                    WHEN SUM(paid_amount) >= SUM(initial_amount) THEN ?
                    ELSE ?
                END as status,
                COUNT(*) as debt_count,
                MAX(created_at) as created_at,
                MAX(updated_at) as updated_at
            ', [AccountingCreditor::STATUS_SETTLED, AccountingCreditor::STATUS_ACTIVE])
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
            ->groupBy('company_site_id', 'type', 'name', 'phone', 'email', 'address', 'currency')
            ->latest('updated_at');

        return view('main.modules.accounting-creditors', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'creditorPermissions' => $this->sitePermissionFlags($user, $site),
            'creditors' => $creditorsQuery->paginate(5)->withQueryString(),
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

        $debtorsQuery = AccountingDebtor::query()
            ->selectRaw('
                MIN(id) as id,
                MIN(reference) as reference,
                company_site_id,
                type,
                name,
                phone,
                email,
                address,
                currency,
                SUM(initial_amount) as initial_amount,
                SUM(received_amount) as received_amount,
                MIN(due_date) as due_date,
                MIN(description) as description,
                CASE
                    WHEN SUM(received_amount) >= SUM(initial_amount) THEN ?
                    ELSE ?
                END as status,
                COUNT(*) as receivable_count,
                MAX(created_at) as created_at,
                MAX(updated_at) as updated_at
            ', [AccountingDebtor::STATUS_SETTLED, AccountingDebtor::STATUS_ACTIVE])
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
            ->groupBy('company_site_id', 'type', 'name', 'phone', 'email', 'address', 'currency')
            ->latest('updated_at');

        return view('main.modules.accounting-debtors', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'debtorPermissions' => $this->sitePermissionFlags($user, $site),
            'debtors' => $debtorsQuery->paginate(5)->withQueryString(),
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
                    'purchasePayments.purchase.supplier',
                    'purchasePayments.payer',
                    'expenses' => fn ($expenseQuery) => $expenseQuery->where('status', AccountingExpense::STATUS_VALIDATED),
                    'expenses.category',
                    'expenses.creator',
                    'creditorPayments.creditor',
                    'creditorPayments.payer',
                    'debtorPayments.debtor',
                    'debtorPayments.receiver',
                ])
                ->withCount('salesInvoicePayments')
                ->withCount('purchasePayments')
                ->withCount('creditorPayments')
                ->withCount('debtorPayments')
                ->withCount('bankReconciliations')
                ->withSum('salesInvoicePayments as receipts_total', 'amount')
                ->withSum('purchasePayments as disbursements_total', 'amount')
                ->withSum('creditorPayments as creditor_payments_total', 'amount')
                ->withSum('debtorPayments as debtor_payments_total', 'amount')
                ->withCount(['otherIncomes' => fn ($incomeQuery) => $incomeQuery->where('status', AccountingOtherIncome::STATUS_VALIDATED)])
                ->withSum(['otherIncomes as other_incomes_total' => fn ($incomeQuery) => $incomeQuery->where('status', AccountingOtherIncome::STATUS_VALIDATED)], 'amount')
                ->withCount(['expenses' => fn ($expenseQuery) => $expenseQuery->where('status', AccountingExpense::STATUS_VALIDATED)])
                ->withSum(['expenses as expenses_total' => fn ($expenseQuery) => $expenseQuery->where('status', AccountingExpense::STATUS_VALIDATED)], 'amount')
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

        if ($method->salesInvoicePayments()->exists()
            || $method->debtorPayments()->exists()
            || $method->otherIncomes()->where('status', AccountingOtherIncome::STATUS_VALIDATED)->exists()
            || $method->purchasePayments()->exists()
            || $method->creditorPayments()->exists()
            || $method->expenses()->where('status', AccountingExpense::STATUS_VALIDATED)->exists()
            || $method->bankReconciliations()->exists()) {
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

    public function accountingTaxes(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingTaxRecords($site, $company);

        return view('main.modules.accounting-taxes', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'taxPermissions' => $this->sitePermissionFlags($user, $site),
            'taxes' => AccountingTax::query()
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                    'reference',
                    'code',
                    'name',
                    'kind',
                    'calculation_type',
                    'value',
                    'nature',
                    'applies_to',
                    'status',
                ]))
                ->orderByDesc('is_default')
                ->orderByDesc('is_system_default')
                ->orderBy('name')
                ->paginate(5)
                ->withQueryString(),
            'kindLabels' => $this->accountingTaxKindLabels(),
            'calculationTypeLabels' => $this->accountingTaxCalculationTypeLabels(),
            'natureLabels' => $this->accountingTaxNatureLabels(),
            'applicationLabels' => $this->accountingTaxApplicationLabels(),
            'statusLabels' => $this->accountingTaxStatusLabels(),
        ]);
    }

    public function storeAccountingTax(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.taxes', [$company, $site]);
        }

        $validated = $request->validate($this->accountingTaxRules($site));
        $this->ensureAccountingTaxCanBeDefault($validated);

        DB::transaction(function () use ($site, $validated, $user): void {
            $payload = $this->accountingTaxPayload($validated, $user);

            if ($payload['is_default']) {
                AccountingTax::query()
                    ->where('company_site_id', $site->id)
                    ->update(['is_default' => false]);
            }

            $tax = $site->accountingTaxes()->create($payload);

            if (blank($tax->code)) {
                $tax->forceFill(['code' => $tax->reference])->save();
            }
        });

        return redirect()
            ->route('main.accounting.taxes', [$company, $site])
            ->with('success', __('main.tax_saved'));
    }

    public function updateAccountingTax(Request $request, Company $company, CompanySite $site, AccountingTax $tax): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($tax->company_site_id !== $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.taxes', [$company, $site]);
        }

        $validated = $request->validate($this->accountingTaxRules($site, $tax));
        $this->ensureAccountingTaxCanBeDefault($validated);

        DB::transaction(function () use ($site, $tax, $validated, $user): void {
            $payload = $this->accountingTaxPayload($validated, $user, false, $tax);

            if ($payload['is_default']) {
                AccountingTax::query()
                    ->where('company_site_id', $site->id)
                    ->whereKeyNot($tax->id)
                    ->update(['is_default' => false]);
            }

            $tax->update($payload);
        });

        return redirect()
            ->route('main.accounting.taxes', [$company, $site])
            ->with('success', __('main.tax_updated'));
    }

    public function destroyAccountingTax(Company $company, CompanySite $site, AccountingTax $tax): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($tax->company_site_id !== $site->id || ! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.taxes', [$company, $site]);
        }

        if ($tax->is_system_default || $tax->is_default) {
            return redirect()
                ->route('main.accounting.taxes', [$company, $site])
                ->with('success', __('main.default_tax_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        if ($this->accountingTaxIsUsed($site, $tax)) {
            return redirect()
                ->route('main.accounting.taxes', [$company, $site])
                ->with('success', __('main.tax_with_documents_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $tax->delete();

        return redirect()
            ->route('main.accounting.taxes', [$company, $site])
            ->with('success', __('main.tax_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingTreasury(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->syncAccountingTreasuryMovements($site);

        $currencyOptions = $this->siteCurrencyOptions($site);
        $currency = strtoupper(trim((string) $request->query('currency', $site->currency ?: array_key_first($currencyOptions) ?: 'CDF')));

        if (! array_key_exists($currency, $currencyOptions)) {
            $currency = strtoupper($site->currency ?: array_key_first($currencyOptions) ?: 'CDF');
        }

        $direction = trim((string) $request->query('direction', ''));
        $movementType = trim((string) $request->query('movement_type', ''));
        $paymentMethodId = (int) $request->query('payment_method_id', 0);
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $baseQuery = AccountingTreasuryMovement::query()
            ->with(['paymentMethod', 'creator'])
            ->where('company_site_id', $site->id)
            ->where('currency', $currency)
            ->when($direction !== '', fn ($query) => $query->where('direction', $direction))
            ->when($movementType !== '', fn ($query) => $query->where('movement_type', $movementType))
            ->when($paymentMethodId > 0, fn ($query) => $query->where('payment_method_id', $paymentMethodId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('movement_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('movement_date', '<=', $dateTo))
            ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                'reference',
                'source_reference',
                'movement_type',
                'direction',
                'label',
                'description',
                'amount',
                'currency',
                'movement_date',
                'status',
                $this->relationTableSearch('paymentMethod', ['name', 'code', 'currency_code']),
                $this->relationTableSearch('creator', ['name', 'email']),
            ]));

        $validatedQuery = AccountingTreasuryMovement::query()
            ->where('company_site_id', $site->id)
            ->where('currency', $currency)
            ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED);
        $totalInflows = (float) (clone $validatedQuery)->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)->sum('amount');
        $totalOutflows = (float) (clone $validatedQuery)->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)->sum('amount');
        $forecast = $this->accountingTreasuryForecast($site, $currency);

        $paymentMethods = AccountingPaymentMethod::query()
            ->where('company_site_id', $site->id)
            ->where('currency_code', $currency)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('main.modules.accounting-treasury', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'movements' => (clone $baseQuery)->latest('movement_date')->latest()->paginate(5)->withQueryString(),
            'movementCount' => (clone $baseQuery)->count(),
            'movementTypeLabels' => $this->accountingTreasuryMovementTypeLabels(),
            'movementDirectionLabels' => $this->accountingTreasuryDirectionLabels(),
            'movementStatusLabels' => $this->accountingTreasuryStatusLabels(),
            'currencyOptions' => $currencyOptions,
            'paymentMethods' => $paymentMethods,
            'accountBalances' => $paymentMethods->map(function (AccountingPaymentMethod $method) use ($validatedQuery): array {
                $methodMovements = (clone $validatedQuery)->where('payment_method_id', $method->id);
                $inflows = (float) (clone $methodMovements)->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)->sum('amount');
                $outflows = (float) (clone $methodMovements)->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)->sum('amount');

                return compact('method', 'inflows', 'outflows') + ['balance' => round($inflows - $outflows, 2)];
            }),
            'metrics' => [
                'inflows' => $totalInflows,
                'outflows' => $totalOutflows,
                'balance' => round($totalInflows - $totalOutflows, 2),
                'receivables' => $forecast['receivables'],
                'debts' => $forecast['debts'],
                'projected_balance' => round($totalInflows - $totalOutflows + $forecast['receivables'] - $forecast['debts'], 2),
            ],
            'chartData' => [
                'currency' => $currency,
                'labels' => [
                    'inflows' => __('main.treasury_inflows'),
                    'outflows' => __('main.treasury_outflows'),
                    'net' => __('main.treasury_net_flow'),
                    'balance' => __('main.treasury_available_balance'),
                ],
                'periods' => [
                    'week' => $this->accountingTreasuryPeriodData($site, $currency, 'week'),
                    'month' => $this->accountingTreasuryPeriodData($site, $currency, 'month'),
                    'year' => $this->accountingTreasuryPeriodData($site, $currency, 'year'),
                ],
                'accounts' => [
                    'labels' => $paymentMethods->pluck('name')->all(),
                    'series' => $paymentMethods->map(function (AccountingPaymentMethod $method) use ($validatedQuery): float {
                        $query = (clone $validatedQuery)->where('payment_method_id', $method->id);

                        return round(
                            (float) (clone $query)->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)->sum('amount')
                            - (float) (clone $query)->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)->sum('amount'),
                            2
                        );
                    })->all(),
                ],
                'forecast' => [
                    'labels' => [__('main.treasury_available_balance'), __('main.receivables'), __('main.debts'), __('main.treasury_projected_balance')],
                    'series' => [
                        round($totalInflows - $totalOutflows, 2),
                        $forecast['receivables'],
                        -$forecast['debts'],
                        round($totalInflows - $totalOutflows + $forecast['receivables'] - $forecast['debts'], 2),
                    ],
                ],
            ],
            'filters' => compact('currency', 'direction', 'movementType', 'paymentMethodId', 'dateFrom', 'dateTo'),
        ]);
    }

    public function accountingBankReconciliations(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->syncAccountingTreasuryMovements($site);

        $bankMethods = AccountingPaymentMethod::query()
            ->where('company_site_id', $site->id)
            ->where('type', AccountingPaymentMethod::TYPE_BANK)
            ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $reconciliations = AccountingBankReconciliation::query()
            ->with(['paymentMethod', 'creator', 'closer'])
            ->withCount('lines')
            ->where('company_site_id', $site->id)
            ->latest('period_end')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $selectedId = (int) $request->integer('reconciliation');
        $activeReconciliation = AccountingBankReconciliation::query()
            ->with(['paymentMethod', 'creator', 'closer', 'lines.matches.treasuryMovement'])
            ->where('company_site_id', $site->id)
            ->when($selectedId > 0, fn ($query) => $query->whereKey($selectedId))
            ->when($selectedId === 0, fn ($query) => $query->latest('period_end')->latest())
            ->first();

        $availableMovements = collect();

        if ($activeReconciliation) {
            $this->refreshAccountingBankReconciliationTotals($activeReconciliation);
            $activeReconciliation->refresh()->load(['paymentMethod', 'creator', 'closer', 'lines.matches.treasuryMovement']);

            if ($activeReconciliation->status !== AccountingBankReconciliation::STATUS_CLOSED) {
                $availableMovements = AccountingTreasuryMovement::query()
                    ->where('company_site_id', $site->id)
                    ->where('payment_method_id', $activeReconciliation->payment_method_id)
                    ->where('currency', $activeReconciliation->currency)
                    ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
                    ->whereBetween('movement_date', [$activeReconciliation->period_start, $activeReconciliation->period_end])
                    ->whereDoesntHave('reconciliationMatches')
                    ->orderBy('movement_date')
                    ->get();
            }
        }

        return view('main.modules.accounting-bank-reconciliations', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'bankMethods' => $bankMethods,
            'reconciliations' => $reconciliations,
            'activeReconciliation' => $activeReconciliation,
            'availableMovements' => $availableMovements,
            'reconciliationStatusLabels' => $this->accountingBankReconciliationStatusLabels(),
            'lineStatusLabels' => $this->accountingBankStatementLineStatusLabels(),
            'directionLabels' => $this->accountingTreasuryDirectionLabels(),
            'permissions' => $this->sitePermissionFlags($user, $site),
        ]);
    }

    public function storeAccountingBankReconciliation(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.bank-reconciliations', [$company, $site]);
        }

        $validated = $request->validate([
            'payment_method_id' => [
                'required',
                Rule::exists('accounting_payment_methods', 'id')->where(fn ($query) => $query
                    ->where('company_site_id', $site->id)
                    ->where('type', AccountingPaymentMethod::TYPE_BANK)
                    ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)),
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'statement_opening_balance' => ['required', 'numeric'],
            'statement_closing_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $method = AccountingPaymentMethod::query()->findOrFail((int) $validated['payment_method_id']);

        $reconciliation = AccountingBankReconciliation::create([
            'company_site_id' => $site->id,
            'payment_method_id' => $method->id,
            'created_by' => $user->id,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'statement_opening_balance' => $validated['statement_opening_balance'],
            'statement_closing_balance' => $validated['statement_closing_balance'],
            'currency' => $method->currency_code,
            'status' => AccountingBankReconciliation::STATUS_IN_PROGRESS,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->refreshAccountingBankReconciliationTotals($reconciliation);

        return redirect()
            ->route('main.accounting.bank-reconciliations', [$company, $site, 'reconciliation' => $reconciliation->id])
            ->with('success', __('main.bank_reconciliation_created'));
    }

    public function storeAccountingBankStatementLine(Request $request, Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureEditableBankReconciliation($site, $reconciliation);

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation);
        }

        $validated = $request->validate([
            'transaction_date' => ['required', 'date', 'after_or_equal:'.$reconciliation->period_start->format('Y-m-d'), 'before_or_equal:'.$reconciliation->period_end->format('Y-m-d')],
            'bank_reference' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'direction' => ['required', Rule::in([AccountingBankStatementLine::DIRECTION_INFLOW, AccountingBankStatementLine::DIRECTION_OUTFLOW])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reconciliation->lines()->create($validated + [
            'created_by' => $user->id,
            'status' => AccountingBankStatementLine::STATUS_UNMATCHED,
        ]);

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_statement_line_created'));
    }

    public function importAccountingBankStatement(Request $request, Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureEditableBankReconciliation($site, $reconciliation);

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation);
        }

        $validated = $request->validate([
            'statement_file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);
        $rows = $this->readAccountingBankStatementCsv($validated['statement_file'], $reconciliation);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'statement_file' => __('main.bank_statement_import_empty'),
            ]);
        }

        $batch = 'CSV-'.now()->format('YmdHis');

        DB::transaction(function () use ($rows, $reconciliation, $user, $batch): void {
            foreach ($rows as $row) {
                $reconciliation->lines()->create($row + [
                    'created_by' => $user->id,
                    'import_batch' => $batch,
                    'status' => AccountingBankStatementLine::STATUS_UNMATCHED,
                ]);
            }
        });

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_statement_imported', ['count' => count($rows)]));
    }

    public function matchAccountingBankStatementLine(Request $request, Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation, AccountingBankStatementLine $line): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation);
        }

        $this->ensureEditableBankReconciliation($site, $reconciliation, $line);
        $validated = $request->validate(['treasury_movement_id' => ['required', 'integer']]);

        $movement = AccountingTreasuryMovement::query()
            ->whereKey($validated['treasury_movement_id'])
            ->where('company_site_id', $site->id)
            ->where('payment_method_id', $reconciliation->payment_method_id)
            ->where('currency', $reconciliation->currency)
            ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
            ->where('direction', $line->direction)
            ->whereBetween('movement_date', [$reconciliation->period_start, $reconciliation->period_end])
            ->firstOrFail();

        if (abs((float) $movement->amount - (float) $line->amount) > 0.009) {
            throw ValidationException::withMessages([
                'treasury_movement_id' => __('main.bank_match_amount_mismatch'),
            ]);
        }

        if ($movement->reconciliationMatches()->where('statement_line_id', '!=', $line->id)->exists()) {
            throw ValidationException::withMessages([
                'treasury_movement_id' => __('main.bank_match_already_used'),
            ]);
        }

        DB::transaction(function () use ($line, $movement, $user): void {
            $line->matches()->delete();
            $line->matches()->create([
                'treasury_movement_id' => $movement->id,
                'created_by' => $user->id,
                'amount' => $line->amount,
                'matched_at' => now(),
            ]);
            $line->update(['status' => AccountingBankStatementLine::STATUS_MATCHED]);
        });

        $this->refreshAccountingBankReconciliationTotals($reconciliation);

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_line_matched'));
    }

    public function unmatchAccountingBankStatementLine(Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation, AccountingBankStatementLine $line): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation);
        }

        $this->ensureEditableBankReconciliation($site, $reconciliation, $line);

        DB::transaction(function () use ($line): void {
            $generatedAdjustments = $line->matches()
                ->with('treasuryMovement')
                ->get()
                ->pluck('treasuryMovement')
                ->filter(fn ($movement) => $movement
                    && $movement->movement_type === AccountingTreasuryMovement::TYPE_BANK_ADJUSTMENT
                    && $movement->source_type === AccountingBankStatementLine::class
                    && (int) $movement->source_id === $line->id);

            $line->matches()->delete();
            $generatedAdjustments->each->delete();
            $line->update(['status' => AccountingBankStatementLine::STATUS_UNMATCHED]);
        });

        $this->refreshAccountingBankReconciliationTotals($reconciliation);

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_line_unmatched'));
    }

    public function createAccountingBankStatementAdjustment(Request $request, Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation, AccountingBankStatementLine $line): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation);
        }

        $this->ensureEditableBankReconciliation($site, $reconciliation, $line);
        $validated = $request->validate([
            'adjustment_label' => ['required', 'string', 'max:255'],
        ]);

        if ($line->status !== AccountingBankStatementLine::STATUS_UNMATCHED) {
            throw ValidationException::withMessages(['adjustment_label' => __('main.bank_line_already_processed')]);
        }

        DB::transaction(function () use ($site, $reconciliation, $line, $validated, $user): void {
            $movement = AccountingTreasuryMovement::create([
                'company_site_id' => $site->id,
                'payment_method_id' => $reconciliation->payment_method_id,
                'created_by' => $user->id,
                'movement_type' => AccountingTreasuryMovement::TYPE_BANK_ADJUSTMENT,
                'source_type' => AccountingBankStatementLine::class,
                'source_id' => $line->id,
                'source_reference' => $line->bank_reference,
                'direction' => $line->direction,
                'label' => $validated['adjustment_label'],
                'description' => $line->description,
                'amount' => $line->amount,
                'currency' => $reconciliation->currency,
                'movement_date' => $line->transaction_date,
                'status' => AccountingTreasuryMovement::STATUS_VALIDATED,
            ]);

            $line->matches()->create([
                'treasury_movement_id' => $movement->id,
                'created_by' => $user->id,
                'amount' => $line->amount,
                'matched_at' => now(),
            ]);
            $line->update(['status' => AccountingBankStatementLine::STATUS_MATCHED]);
        });

        $this->refreshAccountingBankReconciliationTotals($reconciliation);

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_adjustment_created'));
    }

    public function ignoreAccountingBankStatementLine(Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation, AccountingBankStatementLine $line): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation);
        }

        $this->ensureEditableBankReconciliation($site, $reconciliation, $line);

        if ($line->matches()->exists()) {
            throw ValidationException::withMessages(['line' => __('main.bank_line_already_processed')]);
        }

        $line->update(['status' => AccountingBankStatementLine::STATUS_IGNORED]);
        $this->refreshAccountingBankReconciliationTotals($reconciliation);

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_line_ignored'));
    }

    public function closeAccountingBankReconciliation(Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureEditableBankReconciliation($site, $reconciliation);

        if (! $user->isAdmin()) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation)
                ->with('success', __('main.bank_close_admin_required'))
                ->with('toast_type', 'danger');
        }

        $this->refreshAccountingBankReconciliationTotals($reconciliation);
        $reconciliation->refresh();

        if (! $reconciliation->lines()->exists() || $reconciliation->lines()->where('status', AccountingBankStatementLine::STATUS_UNMATCHED)->exists()) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation)
                ->with('success', __('main.bank_close_pending_lines'))
                ->with('toast_type', 'danger');
        }

        if (abs((float) $reconciliation->difference) > 0.009) {
            return $this->bankReconciliationRedirect($company, $site, $reconciliation)
                ->with('success', __('main.bank_close_difference_remaining'))
                ->with('toast_type', 'danger');
        }

        $reconciliation->update([
            'status' => AccountingBankReconciliation::STATUS_CLOSED,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        return $this->bankReconciliationRedirect($company, $site, $reconciliation)
            ->with('success', __('main.bank_reconciliation_closed'));
    }

    public function printAccountingBankReconciliation(Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($reconciliation->company_site_id !== $site->id) {
            abort(404);
        }

        $this->refreshAccountingBankReconciliationTotals($reconciliation);
        $reconciliation->refresh()->load(['paymentMethod', 'creator', 'closer', 'lines.matches.treasuryMovement']);

        return Pdf::loadView('main.modules.accounting-bank-reconciliation-report', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'reconciliation' => $reconciliation,
            'statusLabels' => $this->accountingBankReconciliationStatusLabels(),
            'lineStatusLabels' => $this->accountingBankStatementLineStatusLabels(),
            'directionLabels' => $this->accountingTreasuryDirectionLabels(),
        ])->setPaper('a4')->stream('rapprochement-bancaire-'.$reconciliation->reference.'.pdf');
    }

    public function accountingPaymentReminders(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = Str::lower($this->tableSearch($request));

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->refreshOverdueSalesInvoices($site);
        $this->syncAccountingPaymentReminderStatuses($site, $user);

        $status = trim((string) $request->query('status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $today = now()->startOfDay();

        $reminders = AccountingPaymentReminder::query()
            ->with(['actions.creator', 'promises.creator'])
            ->where('company_site_id', $site->id)
            ->get()
            ->keyBy(fn (AccountingPaymentReminder $reminder): string => $reminder->sales_invoice_id
                ? 'invoice:'.$reminder->sales_invoice_id
                : 'receivable:'.$reminder->debtor_id);

        $invoiceRows = AccountingSalesInvoice::query()
            ->with('client')
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED, AccountingSalesInvoice::STATUS_CREDITED])
            ->where(function ($query): void {
                $query->where('balance_due', '>', 0)
                    ->orWhereHas('paymentReminders');
            })
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->get()
            ->map(function (AccountingSalesInvoice $invoice) use ($reminders, $today, $company, $site): array {
                $reminder = $reminders->get('invoice:'.$invoice->id);
                $overdueDays = $invoice->due_date && $invoice->due_date->lt($today)
                    ? $invoice->due_date->diffInDays($today)
                    : 0;

                return [
                    'source_type' => 'invoice',
                    'source_id' => $invoice->id,
                    'source_reference' => $invoice->reference,
                    'source_label' => __('main.sales_invoice'),
                    'customer' => $invoice->client?->display_name ?? '-',
                    'total' => (float) $invoice->total_ttc,
                    'paid' => (float) $invoice->paid_total + (float) $invoice->credit_total,
                    'balance' => (float) $invoice->balance_due,
                    'currency' => $invoice->currency,
                    'due_date' => $invoice->due_date,
                    'overdue_days' => $overdueDays,
                    'reminder' => $reminder,
                    'row_status' => $reminder?->status ?? ($overdueDays > 0 ? 'overdue' : 'due'),
                    'document_url' => route('main.accounting.sales-invoices.print', [$company, $site, $invoice]),
                ];
            })
            ->toBase();

        $receivableRows = AccountingDebtor::query()
            ->where('company_site_id', $site->id)
            ->where('status', '!=', AccountingDebtor::STATUS_INACTIVE)
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->get()
            ->filter(fn (AccountingDebtor $debtor): bool => $debtor->balanceReceivable() > 0 || $reminders->has('receivable:'.$debtor->id))
            ->map(function (AccountingDebtor $debtor) use ($reminders, $today): array {
                $reminder = $reminders->get('receivable:'.$debtor->id);
                $overdueDays = $debtor->due_date && $debtor->due_date->lt($today)
                    ? $debtor->due_date->diffInDays($today)
                    : 0;

                return [
                    'source_type' => 'receivable',
                    'source_id' => $debtor->id,
                    'source_reference' => $debtor->reference,
                    'source_label' => __('main.manual_receivable'),
                    'customer' => $debtor->name,
                    'total' => (float) $debtor->initial_amount,
                    'paid' => (float) $debtor->received_amount,
                    'balance' => $debtor->balanceReceivable(),
                    'currency' => $debtor->currency,
                    'due_date' => $debtor->due_date,
                    'overdue_days' => $overdueDays,
                    'reminder' => $reminder,
                    'row_status' => $reminder?->status ?? ($overdueDays > 0 ? 'overdue' : 'due'),
                    'document_url' => null,
                ];
            })
            ->toBase();

        $rows = $invoiceRows
            ->merge($receivableRows)
            ->when($status !== '', fn ($items) => $items->where('row_status', $status))
            ->when($search !== '', fn ($items) => $items->filter(fn (array $row): bool => Str::contains(Str::lower(implode(' ', [
                $row['source_reference'],
                $row['source_label'],
                $row['customer'],
                $row['currency'],
                $row['row_status'],
                $row['reminder']?->reference,
            ])), $search)))
            ->sortBy([
                fn (array $left, array $right) => $right['overdue_days'] <=> $left['overdue_days'],
                fn (array $left, array $right) => strcmp((string) $left['due_date'], (string) $right['due_date']),
            ])
            ->values();
        $outstandingRows = $rows->where('balance', '>', 0);

        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $followUps = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, 5)->values(),
            $rows->count(),
            5,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('main.modules.accounting-payment-reminders', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'followUps' => $followUps,
            'permissions' => $this->sitePermissionFlags($user, $site),
            'currencies' => $this->siteCurrencyOptions($site),
            'filters' => compact('status', 'currency'),
            'levelLabels' => $this->accountingPaymentReminderLevelLabels(),
            'channelLabels' => $this->accountingPaymentReminderChannelLabels(),
            'statusLabels' => $this->accountingPaymentReminderStatusLabels(),
            'actionLabels' => $this->accountingPaymentReminderActionLabels(),
            'promiseStatusLabels' => $this->accountingPaymentPromiseStatusLabels(),
            'metrics' => [
                'balances' => $outstandingRows->groupBy('currency')
                    ->map(fn ($items, string $code): array => [
                        'amount' => (float) $items->sum('balance'),
                        'currency' => $code,
                    ])
                    ->values(),
                'overdue' => $outstandingRows->where('overdue_days', '>', 0)->count(),
                'older_than_30' => $outstandingRows->where('overdue_days', '>', 30)->count(),
                'promises' => AccountingPaymentPromise::query()
                    ->whereHas('reminder', fn ($query) => $query->where('company_site_id', $site->id))
                    ->where('status', AccountingPaymentPromise::STATUS_PENDING)
                    ->count(),
            ],
        ]);
    }

    public function storeAccountingPaymentReminder(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.payment-reminders', [$company, $site]);
        }

        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['invoice', 'receivable'])],
            'source_id' => ['required', 'integer'],
            'level' => ['required', Rule::in(AccountingPaymentReminder::levels())],
            'channel' => ['required', Rule::in(AccountingPaymentReminder::channels())],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
            'next_reminder_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $source = $this->resolveAccountingPaymentReminderSource($site, $validated['source_type'], (int) $validated['source_id']);

        $reminder = DB::transaction(function () use ($site, $user, $validated, $source): AccountingPaymentReminder {
            $match = $source['type'] === 'invoice'
                ? ['sales_invoice_id' => $source['model']->id]
                : ['debtor_id' => $source['model']->id];

            $reminder = AccountingPaymentReminder::query()->updateOrCreate(
                ['company_site_id' => $site->id] + $match,
                [
                    'client_id' => $source['client_id'],
                    'created_by' => $user->id,
                    'level' => $validated['level'],
                    'channel' => $validated['channel'],
                    'status' => AccountingPaymentReminder::STATUS_SENT,
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'next_reminder_date' => $validated['next_reminder_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'sent_at' => now(),
                ]
            );

            $reminder->actions()->create([
                'created_by' => $user->id,
                'action_type' => AccountingPaymentReminderAction::TYPE_REMINDER_SENT,
                'channel' => $validated['channel'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'next_reminder_date' => $validated['next_reminder_date'] ?? null,
                'action_at' => now(),
            ]);

            return $reminder;
        });

        return redirect()
            ->route('main.accounting.payment-reminders', [$company, $site])
            ->with('success', __('main.payment_reminder_saved', ['reference' => $reminder->reference]));
    }

    public function storeAccountingPaymentPromise(Request $request, Company $company, CompanySite $site, AccountingPaymentReminder $reminder): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingPaymentReminderBelongsToSite($site, $reminder);

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.payment-reminders', [$company, $site]);
        }

        $outstanding = $this->accountingPaymentReminderBalance($reminder);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'promised_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float) $validated['amount'] > $outstanding) {
            throw ValidationException::withMessages(['amount' => __('main.payment_promise_amount_exceeds_balance')]);
        }

        DB::transaction(function () use ($reminder, $user, $validated): void {
            $reminder->promises()->create([
                'created_by' => $user->id,
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $this->accountingPaymentReminderCurrency($reminder),
                'promised_date' => $validated['promised_date'],
                'status' => AccountingPaymentPromise::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);
            $reminder->update(['status' => AccountingPaymentReminder::STATUS_PROMISE]);
            $reminder->actions()->create([
                'created_by' => $user->id,
                'action_type' => AccountingPaymentReminderAction::TYPE_PROMISE,
                'message' => $validated['notes'] ?? null,
                'action_at' => now(),
            ]);
        });

        return redirect()
            ->route('main.accounting.payment-reminders', [$company, $site])
            ->with('success', __('main.payment_promise_saved'));
    }

    public function suspendAccountingPaymentReminder(Company $company, CompanySite $site, AccountingPaymentReminder $reminder): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingPaymentReminderBelongsToSite($site, $reminder);

        if ($this->sitePermissionFlags($user, $site)['can_update']) {
            $reminder->update(['status' => AccountingPaymentReminder::STATUS_SUSPENDED]);
            $reminder->actions()->create([
                'created_by' => $user->id,
                'action_type' => AccountingPaymentReminderAction::TYPE_SUSPENDED,
                'action_at' => now(),
            ]);
        }

        return redirect()
            ->route('main.accounting.payment-reminders', [$company, $site])
            ->with('success', __('main.payment_reminder_suspended'));
    }

    public function disputeAccountingPaymentReminder(Request $request, Company $company, CompanySite $site, AccountingPaymentReminder $reminder): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingPaymentReminderBelongsToSite($site, $reminder);

        if (! $this->sitePermissionFlags($user, $site)['can_update'] || $this->accountingPaymentReminderBalance($reminder) <= 0) {
            return redirect()->route('main.accounting.payment-reminders', [$company, $site]);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($reminder, $user, $validated): void {
            $reminder->update([
                'status' => AccountingPaymentReminder::STATUS_DISPUTED,
                'notes' => $validated['reason'],
            ]);
            $reminder->actions()->create([
                'created_by' => $user->id,
                'action_type' => AccountingPaymentReminderAction::TYPE_DISPUTED,
                'message' => $validated['reason'],
                'action_at' => now(),
            ]);
        });

        return redirect()
            ->route('main.accounting.payment-reminders', [$company, $site])
            ->with('success', __('main.payment_reminder_disputed'));
    }

    public function printAccountingPaymentReminder(Company $company, CompanySite $site, AccountingPaymentReminder $reminder): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingPaymentReminderBelongsToSite($site, $reminder);
        $reminder->load(['client', 'salesInvoice.client', 'debtor', 'creator']);

        return Pdf::loadView('main.modules.accounting-payment-reminder-letter', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'reminder' => $reminder,
            'sourceReference' => $reminder->salesInvoice?->reference ?: $reminder->debtor?->reference,
            'customerName' => $reminder->client?->display_name ?: $reminder->debtor?->name,
            'balance' => $this->accountingPaymentReminderBalance($reminder),
            'currency' => $this->accountingPaymentReminderCurrency($reminder),
            'levelLabels' => $this->accountingPaymentReminderLevelLabels(),
        ])->setPaper('a4')->stream('relance-paiement-'.$reminder->reference.'.pdf');
    }

    public function accountingTasks(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->refreshOverdueSalesInvoices($site);
        $this->syncAccountingPaymentReminderStatuses($site, $user);
        $this->syncAccountingTasks($site, $user);

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'assigned_to' => (int) $request->integer('assigned_to'),
        ];
        $search = $this->tableSearch($request);
        $baseQuery = $this->accountingTaskQueryForUser($site, $user);

        $metrics = [
            'open' => (clone $baseQuery)->whereNotIn('status', [AccountingTask::STATUS_COMPLETED, AccountingTask::STATUS_CANCELLED])->count(),
            'overdue' => (clone $baseQuery)->whereNotIn('status', [AccountingTask::STATUS_COMPLETED, AccountingTask::STATUS_CANCELLED])->whereDate('due_date', '<', now()->toDateString())->count(),
            'due_today' => (clone $baseQuery)->whereNotIn('status', [AccountingTask::STATUS_COMPLETED, AccountingTask::STATUS_CANCELLED])->whereDate('due_date', now()->toDateString())->count(),
            'urgent' => (clone $baseQuery)->whereNotIn('status', [AccountingTask::STATUS_COMPLETED, AccountingTask::STATUS_CANCELLED])->where('priority', AccountingTask::PRIORITY_URGENT)->count(),
            'completed_this_week' => (clone $baseQuery)->where('status', AccountingTask::STATUS_COMPLETED)->where('completed_at', '>=', now()->startOfWeek())->count(),
        ];

        $tasks = $baseQuery
            ->with(['assignee', 'creator', 'client', 'supplier', 'activities.creator'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['priority'] !== '', fn ($query) => $query->where('priority', $filters['priority']))
            ->when($filters['assigned_to'] > 0, fn ($query) => $query->where('assigned_to', $filters['assigned_to']))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('reference', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('source_reference', 'like', "%{$search}%")
                        ->orWhereHas('assignee', fn ($relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('client', fn ($relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($relation) => $relation->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->latest('id')
            ->paginate(5)
            ->withQueryString();

        $tasks->getCollection()->each(function (AccountingTask $task) use ($company, $site): void {
            $task->setAttribute('document_url', $this->accountingTaskDocumentUrl($task, $company, $site));
        });

        return view('main.modules.accounting-tasks', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'tasks' => $tasks,
            'permissions' => $this->sitePermissionFlags($user, $site),
            'filters' => $filters,
            'metrics' => $metrics,
            'assignees' => $this->siteResponsibleOptions($company),
            'clients' => $site->accountingClients()->orderBy('name')->get(),
            'suppliers' => $site->accountingSuppliers()->orderBy('name')->get(),
            'documents' => $this->accountingTaskDocumentOptions($site),
            'typeLabels' => $this->accountingTaskTypeLabels(),
            'priorityLabels' => $this->accountingTaskPriorityLabels(),
            'statusLabels' => $this->accountingTaskStatusLabels(),
            'activityLabels' => $this->accountingTaskActivityLabels(),
        ]);
    }

    public function storeAccountingTask(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.tasks', [$company, $site]);
        }

        $validated = $request->validate($this->accountingTaskRules($company, $site));
        $task = DB::transaction(function () use ($validated, $user, $site): AccountingTask {
            $task = $site->accountingTasks()->create($this->accountingTaskPayload($validated, $site, $user));
            $task->activities()->create([
                'created_by' => $user->id,
                'action_type' => AccountingTaskActivity::TYPE_CREATED,
                'to_status' => $task->status,
            ]);

            return $task;
        });

        return redirect()
            ->route('main.accounting.tasks', [$company, $site])
            ->with('success', __('main.task_saved', ['reference' => $task->reference]));
    }

    public function updateAccountingTask(Request $request, Company $company, CompanySite $site, AccountingTask $task): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingTaskAccessible($site, $task, $user);

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.tasks', [$company, $site]);
        }

        $validated = $request->validate($this->accountingTaskRules($company, $site));
        DB::transaction(function () use ($validated, $task, $site, $user): void {
            $previousStatus = $task->status;
            $payload = $this->accountingTaskPayload($validated, $site, $user, $task);
            $task->update($payload);
            $actionType = $task->status === AccountingTask::STATUS_COMPLETED && $previousStatus !== AccountingTask::STATUS_COMPLETED
                ? AccountingTaskActivity::TYPE_COMPLETED
                : AccountingTaskActivity::TYPE_UPDATED;
            $task->activities()->create([
                'created_by' => $user->id,
                'action_type' => $actionType,
                'from_status' => $previousStatus,
                'to_status' => $task->status,
                'notes' => $validated['completion_notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('main.accounting.tasks', [$company, $site])
            ->with('success', __('main.task_updated'));
    }

    public function completeAccountingTask(Request $request, Company $company, CompanySite $site, AccountingTask $task): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingTaskAccessible($site, $task, $user);

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.tasks', [$company, $site]);
        }

        $validated = $request->validate(['completion_notes' => ['nullable', 'string', 'max:2000']]);
        $previousStatus = $task->status;
        $task->update([
            'status' => AccountingTask::STATUS_COMPLETED,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'completion_notes' => $validated['completion_notes'] ?? $task->completion_notes,
        ]);
        $task->activities()->create([
            'created_by' => $user->id,
            'action_type' => AccountingTaskActivity::TYPE_COMPLETED,
            'from_status' => $previousStatus,
            'to_status' => AccountingTask::STATUS_COMPLETED,
            'notes' => $validated['completion_notes'] ?? null,
        ]);

        return redirect()
            ->route('main.accounting.tasks', [$company, $site])
            ->with('success', __('main.task_completed'));
    }

    public function destroyAccountingTask(Company $company, CompanySite $site, AccountingTask $task): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->ensureAccountingTaskAccessible($site, $task, $user);

        if (! $this->sitePermissionFlags($user, $site)['can_delete'] || $task->is_automatic) {
            return redirect()
                ->route('main.accounting.tasks', [$company, $site])
                ->with('success', __('main.automatic_task_cannot_be_deleted'))
                ->with('toast_type', 'danger');
        }

        $task->delete();

        return redirect()
            ->route('main.accounting.tasks', [$company, $site])
            ->with('success', __('main.task_deleted'));
    }

    public function accountingReports(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->syncAccountingTreasuryMovements($site);
        $this->refreshOverdueSalesInvoices($site);
        $this->refreshOverdueAccountingPurchases($site);

        return view('main.modules.accounting-reports', array_merge([
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
        ], $this->accountingReportData($request, $site)));
    }

    public function printAccountingReport(Request $request, Company $company, CompanySite $site): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $this->syncAccountingTreasuryMovements($site);
        $this->refreshOverdueSalesInvoices($site);
        $this->refreshOverdueAccountingPurchases($site);

        return Pdf::loadView('main.modules.accounting-report-print', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
        ], $this->accountingReportData($request, $site, false)))
            ->setPaper('a4', 'landscape')
            ->stream('rapport-'.$site->id.'-'.now()->format('Ymd').'.pdf');
    }

    public function exportAccountingReport(Request $request, Company $company, CompanySite $site)
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        $this->syncAccountingTreasuryMovements($site);
        $this->refreshOverdueSalesInvoices($site);
        $this->refreshOverdueAccountingPurchases($site);

        $data = $this->accountingReportData($request, $site, false);
        $fileName = 'rapport-'.$data['section'].'-'.$site->id.'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->accountingReportCsvHeaders($data['section']), ';');

            foreach ($data['records'] as $record) {
                fputcsv($handle, $this->accountingReportCsvRow($data['section'], $record), ';');
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function accountingReportData(Request $request, CompanySite $site, bool $paginate = true): array
    {
        $sections = ['sales', 'receipts', 'purchases', 'treasury', 'stock'];
        $section = in_array($request->query('section'), $sections, true) ? (string) $request->query('section') : 'sales';
        [$period, $dateFrom, $dateTo] = $this->accountingReportPeriod($request);
        $currency = strtoupper($site->currency ?: 'CDF');
        $search = $this->tableSearch($request);
        $filters = [
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'client_id' => (int) $request->integer('client_id'),
            'supplier_id' => (int) $request->integer('supplier_id'),
            'payment_method_id' => (int) $request->integer('payment_method_id'),
            'status' => trim((string) $request->query('status', '')),
            'search' => $search,
        ];

        $metrics = [];
        $statusLabels = [];
        $query = null;

        if ($section === 'sales') {
            $query = AccountingSalesInvoice::query()
                ->with('client')
                ->where('company_site_id', $site->id)
                ->whereBetween('invoice_date', [$filters['date_from'], $filters['date_to']])
                ->when($filters['client_id'] > 0, fn ($builder) => $builder->where('client_id', $filters['client_id']))
                ->when($filters['status'] !== '', fn ($builder) => $builder->where('status', $filters['status']))
                ->when($search !== '', fn ($builder) => $this->applyTableSearch($builder, $search, [
                    'reference', 'title', 'invoice_date', 'due_date', 'status', 'total_ttc', 'paid_total', 'balance_due',
                    $this->relationTableSearch('client', ['reference', 'name']),
                ]));
            $financialQuery = clone $query;
            if ($filters['status'] === '') {
                $financialQuery->whereNotIn('status', [AccountingSalesInvoice::STATUS_CANCELLED]);
            }
            $metrics = [
                $this->reportMetric(__('main.report_revenue'), (float) (clone $financialQuery)->sum('total_ttc'), 'bi-receipt', 'blue'),
                $this->reportMetric(__('main.report_collected'), (float) (clone $financialQuery)->sum('paid_total'), 'bi-cash-coin', 'green'),
                $this->reportMetric(__('main.balance_due'), (float) (clone $financialQuery)->sum('balance_due'), 'bi-hourglass-split', 'amber'),
                $this->reportMetric(__('main.report_overdue_invoices'), (float) (clone $financialQuery)->where('status', AccountingSalesInvoice::STATUS_OVERDUE)->count(), 'bi-exclamation-circle', 'rose', false),
            ];
            $statusLabels = $this->salesInvoiceStatusLabels();
            $query->latest('invoice_date')->latest();
        } elseif ($section === 'receipts') {
            $query = AccountingSalesInvoicePayment::query()
                ->with(['salesInvoice.client', 'paymentMethod', 'receiver'])
                ->whereHas('salesInvoice', fn ($builder) => $builder->where('company_site_id', $site->id)
                    ->when($filters['client_id'] > 0, fn ($invoiceQuery) => $invoiceQuery->where('client_id', $filters['client_id'])))
                ->whereBetween('payment_date', [$filters['date_from'], $filters['date_to']])
                ->when($filters['payment_method_id'] > 0, fn ($builder) => $builder->where('payment_method_id', $filters['payment_method_id']))
                ->when($search !== '', fn ($builder) => $this->applyTableSearch($builder, $search, [
                    'reference', 'payment_date', 'amount', 'currency', 'notes',
                    $this->relationTableSearch('paymentMethod', ['name', 'code']),
                    $this->relationTableSearch('salesInvoice', ['reference']),
                ]));
            $total = (float) (clone $query)->sum('amount');
            $count = (clone $query)->count();
            $metrics = [
                $this->reportMetric(__('main.report_receipts_total'), $total, 'bi-cash-coin', 'green'),
                $this->reportMetric(__('main.report_payments_count'), (float) $count, 'bi-receipt-cutoff', 'blue', false),
                $this->reportMetric(__('main.report_average_payment'), $count > 0 ? $total / $count : 0, 'bi-calculator', 'violet'),
                $this->reportMetric(__('main.report_payment_methods_used'), (float) (clone $query)->distinct('payment_method_id')->count('payment_method_id'), 'bi-credit-card-2-front', 'amber', false),
            ];
            $query->latest('payment_date')->latest();
        } elseif ($section === 'purchases') {
            $query = AccountingPurchase::query()
                ->with('supplier')
                ->where('company_site_id', $site->id)
                ->whereBetween('purchase_date', [$filters['date_from'], $filters['date_to']])
                ->when($filters['supplier_id'] > 0, fn ($builder) => $builder->where('supplier_id', $filters['supplier_id']))
                ->when($filters['status'] !== '', fn ($builder) => $builder->where('status', $filters['status']))
                ->when($search !== '', fn ($builder) => $this->applyTableSearch($builder, $search, [
                    'reference', 'supplier_invoice_reference', 'title', 'purchase_date', 'due_date', 'status', 'total_ttc', 'paid_total', 'balance_due',
                    $this->relationTableSearch('supplier', ['reference', 'name']),
                ]));
            $financialQuery = clone $query;
            if ($filters['status'] === '') {
                $financialQuery->whereNotIn('status', [AccountingPurchase::STATUS_CANCELLED]);
            }
            $expenses = AccountingExpense::query()
                ->where('company_site_id', $site->id)
                ->where('status', AccountingExpense::STATUS_VALIDATED)
                ->whereBetween('expense_date', [$filters['date_from'], $filters['date_to']])
                ->sum('amount');
            $metrics = [
                $this->reportMetric(__('main.purchases'), (float) (clone $financialQuery)->sum('total_ttc'), 'bi-bag-check', 'blue'),
                $this->reportMetric(__('main.expenses'), (float) $expenses, 'bi-wallet2', 'rose'),
                $this->reportMetric(__('main.report_paid_suppliers'), (float) (clone $financialQuery)->sum('paid_total'), 'bi-cash-stack', 'green'),
                $this->reportMetric(__('main.debts'), (float) (clone $financialQuery)->sum('balance_due'), 'bi-hourglass-split', 'amber'),
            ];
            $statusLabels = $this->purchaseStatusLabels();
            $query->latest('purchase_date')->latest();
        } elseif ($section === 'treasury') {
            $query = AccountingTreasuryMovement::query()
                ->with('paymentMethod')
                ->where('company_site_id', $site->id)
                ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
                ->whereBetween('movement_date', [$filters['date_from'], $filters['date_to']])
                ->when($filters['payment_method_id'] > 0, fn ($builder) => $builder->where('payment_method_id', $filters['payment_method_id']))
                ->when($search !== '', fn ($builder) => $this->applyTableSearch($builder, $search, [
                    'reference', 'source_reference', 'label', 'movement_type', 'direction', 'amount', 'movement_date',
                    $this->relationTableSearch('paymentMethod', ['name', 'code']),
                ]));
            $inflows = (float) (clone $query)->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)->sum('amount');
            $outflows = (float) (clone $query)->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)->sum('amount');
            $forecast = $this->accountingTreasuryForecast($site, $currency);
            $metrics = [
                $this->reportMetric(__('main.treasury_inflows'), $inflows, 'bi-arrow-down-left-circle', 'green'),
                $this->reportMetric(__('main.treasury_outflows'), $outflows, 'bi-arrow-up-right-circle', 'rose'),
                $this->reportMetric(__('main.treasury_net_flow'), $inflows - $outflows, 'bi-activity', 'blue'),
                $this->reportMetric(__('main.treasury_projected_balance'), $inflows - $outflows + $forecast['receivables'] - $forecast['debts'], 'bi-graph-up-arrow', 'violet'),
            ];
            $query->latest('movement_date')->latest();
        } else {
            $query = AccountingStockItem::query()
                ->with(['category', 'subcategory', 'unit'])
                ->where('company_site_id', $site->id)
                ->when($search !== '', fn ($builder) => $this->applyTableSearch($builder, $search, [
                    'reference', 'name', 'sku', 'barcode', 'type', 'current_stock', 'min_stock', 'status',
                    $this->relationTableSearch('category', ['name']),
                    $this->relationTableSearch('subcategory', ['name']),
                ]));
            $movementsQuery = AccountingStockMovement::query()
                ->where('company_site_id', $site->id)
                ->whereBetween('movement_date', [$filters['date_from'], $filters['date_to']]);
            $lowStockCount = AccountingStockItem::query()
                ->where('company_site_id', $site->id)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->count();
            $inventoryValue = AccountingStockItem::query()
                ->where('company_site_id', $site->id)
                ->get(['current_stock', 'purchase_price'])
                ->sum(fn (AccountingStockItem $item) => $item->current_stock * $item->purchase_price);
            $metrics = [
                $this->reportMetric(__('main.items'), (float) (clone $query)->count(), 'bi-box-seam', 'blue', false),
                $this->reportMetric(__('main.report_inventory_value'), (float) $inventoryValue, 'bi-cash-stack', 'green'),
                $this->reportMetric(__('main.report_low_stock'), (float) $lowStockCount, 'bi-exclamation-triangle', 'rose', false),
                $this->reportMetric(__('main.report_stock_movements'), (float) (clone $movementsQuery)->count(), 'bi-arrow-left-right', 'amber', false),
            ];
            $query->orderByRaw('case when current_stock <= min_stock then 0 else 1 end')->orderBy('name');
        }

        $records = $paginate ? $query->paginate(5)->withQueryString() : $query->get();

        return [
            'section' => $section,
            'sections' => [
                'sales' => __('main.report_section_sales'),
                'receipts' => __('main.report_section_receipts'),
                'purchases' => __('main.report_section_purchases'),
                'treasury' => __('main.report_section_treasury'),
                'stock' => __('main.report_section_stock'),
            ],
            'filters' => $filters,
            'currency' => $currency,
            'records' => $records,
            'metrics' => $metrics,
            'statusLabels' => $statusLabels,
            'movementTypeLabels' => $this->accountingTreasuryMovementTypeLabels(),
            'movementDirectionLabels' => $this->accountingTreasuryDirectionLabels(),
            'clients' => AccountingClient::query()->where('company_site_id', $site->id)->orderBy('name')->get(),
            'suppliers' => AccountingSupplier::query()->where('company_site_id', $site->id)->orderBy('name')->get(),
            'paymentMethods' => AccountingPaymentMethod::query()->where('company_site_id', $site->id)->orderByDesc('is_default')->orderBy('name')->get(),
            'chartData' => $this->accountingReportChartData($site, $section, $filters),
            'periodLabel' => $dateFrom->translatedFormat('d M Y').' - '.$dateTo->translatedFormat('d M Y'),
        ];
    }

    private function accountingReportPeriod(Request $request): array
    {
        $period = in_array($request->query('period'), ['week', 'month', 'quarter', 'year', 'custom'], true)
            ? (string) $request->query('period')
            : 'month';
        $now = Carbon::now();

        if ($period === 'custom' && filled($request->query('date_from')) && filled($request->query('date_to'))) {
            try {
                $start = Carbon::parse((string) $request->query('date_from'))->startOfDay();
                $end = Carbon::parse((string) $request->query('date_to'))->endOfDay();

                if ($start->lte($end)) {
                    return [$period, $start, $end];
                }
            } catch (\Throwable) {
                $period = 'month';
            }
        }

        return match ($period) {
            'week' => [$period, $now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarter' => [$period, $now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$period, $now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => ['month', $now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function reportMetric(string $label, float $value, string $icon, string $tone, bool $isMoney = true): array
    {
        return compact('label', 'value', 'icon', 'tone', 'isMoney');
    }

    private function accountingReportChartData(CompanySite $site, string $section, array $filters): array
    {
        $buckets = $this->accountingReportBuckets(
            Carbon::parse($filters['date_from']),
            Carbon::parse($filters['date_to']),
            $filters['period']
        );
        $labels = $buckets->pluck('label')->all();
        $series = [];

        if ($section === 'sales') {
            $series = [
                ['name' => __('main.report_revenue'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingSalesInvoice::query()
                    ->where('company_site_id', $site->id)
                    ->whereBetween('invoice_date', [$bucket['start'], $bucket['end']])
                    ->whereNotIn('status', [AccountingSalesInvoice::STATUS_CANCELLED])
                    ->when($filters['client_id'] > 0, fn ($query) => $query->where('client_id', $filters['client_id']))
                    ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
                    ->sum('total_ttc'), 2))->all()],
                ['name' => __('main.report_collected'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingSalesInvoice::query()
                    ->where('company_site_id', $site->id)
                    ->whereBetween('invoice_date', [$bucket['start'], $bucket['end']])
                    ->whereNotIn('status', [AccountingSalesInvoice::STATUS_CANCELLED])
                    ->when($filters['client_id'] > 0, fn ($query) => $query->where('client_id', $filters['client_id']))
                    ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
                    ->sum('paid_total'), 2))->all()],
            ];
        } elseif ($section === 'receipts') {
            $series = [
                ['name' => __('main.report_receipts_total'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingSalesInvoicePayment::query()
                    ->whereHas('salesInvoice', fn ($query) => $query->where('company_site_id', $site->id)
                        ->when($filters['client_id'] > 0, fn ($invoiceQuery) => $invoiceQuery->where('client_id', $filters['client_id'])))
                    ->whereBetween('payment_date', [$bucket['start'], $bucket['end']])
                    ->when($filters['payment_method_id'] > 0, fn ($query) => $query->where('payment_method_id', $filters['payment_method_id']))
                    ->sum('amount'), 2))->all()],
            ];
        } elseif ($section === 'purchases') {
            $series = [
                ['name' => __('main.purchases'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingPurchase::query()
                    ->where('company_site_id', $site->id)
                    ->whereBetween('purchase_date', [$bucket['start'], $bucket['end']])
                    ->whereNotIn('status', [AccountingPurchase::STATUS_CANCELLED])
                    ->when($filters['supplier_id'] > 0, fn ($query) => $query->where('supplier_id', $filters['supplier_id']))
                    ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
                    ->sum('total_ttc'), 2))->all()],
                ['name' => __('main.expenses'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingExpense::query()
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingExpense::STATUS_VALIDATED)
                    ->whereBetween('expense_date', [$bucket['start'], $bucket['end']])
                    ->sum('amount'), 2))->all()],
            ];
        } elseif ($section === 'treasury') {
            $series = [
                ['name' => __('main.treasury_inflows'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingTreasuryMovement::query()
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
                    ->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)
                    ->whereBetween('movement_date', [$bucket['start'], $bucket['end']])
                    ->when($filters['payment_method_id'] > 0, fn ($query) => $query->where('payment_method_id', $filters['payment_method_id']))
                    ->sum('amount'), 2))->all()],
                ['name' => __('main.treasury_outflows'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingTreasuryMovement::query()
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
                    ->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)
                    ->whereBetween('movement_date', [$bucket['start'], $bucket['end']])
                    ->when($filters['payment_method_id'] > 0, fn ($query) => $query->where('payment_method_id', $filters['payment_method_id']))
                    ->sum('amount'), 2))->all()],
            ];
        } else {
            $series = [
                ['name' => __('main.report_entries'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingStockMovement::query()
                    ->where('company_site_id', $site->id)
                    ->where('type', AccountingStockMovement::TYPE_ENTRY)
                    ->whereBetween('movement_date', [$bucket['start'], $bucket['end']])
                    ->sum('quantity'), 2))->all()],
                ['name' => __('main.report_exits'), 'data' => $buckets->map(fn ($bucket) => round((float) AccountingStockMovement::query()
                    ->where('company_site_id', $site->id)
                    ->where('type', AccountingStockMovement::TYPE_EXIT)
                    ->whereBetween('movement_date', [$bucket['start'], $bucket['end']])
                    ->sum('quantity'), 2))->all()],
            ];
        }

        return [
            'currency' => $section === 'stock' ? '' : strtoupper($site->currency ?: 'CDF'),
            'labels' => $labels,
            'series' => $series,
            'title' => [
                'sales' => __('main.report_chart_sales'),
                'receipts' => __('main.report_chart_receipts'),
                'purchases' => __('main.report_chart_purchases'),
                'treasury' => __('main.report_chart_treasury'),
                'stock' => __('main.report_chart_stock'),
            ][$section],
        ];
    }

    private function accountingReportBuckets(Carbon $dateFrom, Carbon $dateTo, string $period)
    {
        $buckets = collect();
        $cursor = $dateFrom->copy()->startOfDay();

        while ($cursor->lte($dateTo)) {
            $end = match ($period) {
                'week' => $cursor->copy()->endOfDay(),
                'year' => $cursor->copy()->endOfMonth(),
                default => $cursor->copy()->addDays(6)->endOfDay(),
            };
            $end = $end->gt($dateTo) ? $dateTo->copy()->endOfDay() : $end;
            $label = $period === 'year'
                ? $cursor->translatedFormat('M')
                : ($period === 'week' ? $cursor->translatedFormat('d M') : $cursor->translatedFormat('d M').' - '.$end->translatedFormat('d M'));

            $buckets->push([
                'start' => $cursor->toDateString(),
                'end' => $end->toDateString(),
                'label' => $label,
            ]);
            $cursor = $end->copy()->addDay()->startOfDay();
        }

        return $buckets;
    }

    private function accountingReportCsvHeaders(string $section): array
    {
        return match ($section) {
            'receipts' => [__('main.date'), __('main.reference'), __('main.sales_invoices'), __('main.customer'), __('main.payment_method'), __('main.amount'), __('main.currency')],
            'purchases' => [__('main.reference'), __('main.supplier'), __('main.purchase_date'), __('main.due_date'), __('main.total_ttc'), __('main.paid_total'), __('main.balance_due'), __('main.status')],
            'treasury' => [__('main.date'), __('main.reference'), __('main.treasury_source'), __('main.type'), __('main.payment_method'), __('main.direction'), __('main.amount')],
            'stock' => [__('main.reference'), __('main.items'), __('main.categories'), __('main.subcategories'), __('main.current_stock'), __('main.min_stock'), __('main.report_inventory_value')],
            default => [__('main.reference'), __('main.customer'), __('main.date'), __('main.due_date'), __('main.total_ttc'), __('main.paid_total'), __('main.balance_due'), __('main.status')],
        };
    }

    private function accountingReportCsvRow(string $section, Model $record): array
    {
        return match ($section) {
            'receipts' => [
                optional($record->payment_date)->format('d/m/Y'), $record->reference, $record->salesInvoice?->reference,
                $record->salesInvoice?->client?->display_name, $record->paymentMethod?->name, number_format((float) $record->amount, 2, ',', ' '), $record->currency,
            ],
            'purchases' => [
                $record->reference, $record->supplier?->name, optional($record->purchase_date)->format('d/m/Y'),
                optional($record->due_date)->format('d/m/Y'), number_format((float) $record->total_ttc, 2, ',', ' '),
                number_format((float) $record->paid_total, 2, ',', ' '), number_format((float) $record->balance_due, 2, ',', ' '),
                $this->purchaseStatusLabels()[$record->status] ?? $record->status,
            ],
            'treasury' => [
                optional($record->movement_date)->format('d/m/Y'), $record->reference, $record->source_reference ?: $record->label,
                $this->accountingTreasuryMovementTypeLabels()[$record->movement_type] ?? $record->movement_type,
                $record->paymentMethod?->name, $this->accountingTreasuryDirectionLabels()[$record->direction] ?? $record->direction,
                number_format((float) $record->amount, 2, ',', ' '),
            ],
            'stock' => [
                $record->reference, $record->name, $record->category?->name, $record->subcategory?->name,
                number_format((float) $record->current_stock, 2, ',', ' '), number_format((float) $record->min_stock, 2, ',', ' '),
                number_format((float) ($record->current_stock * $record->purchase_price), 2, ',', ' '),
            ],
            default => [
                $record->reference, $record->client?->display_name, optional($record->invoice_date)->format('d/m/Y'),
                optional($record->due_date)->format('d/m/Y'), number_format((float) $record->total_ttc, 2, ',', ' '),
                number_format((float) $record->paid_total, 2, ',', ' '), number_format((float) $record->balance_due, 2, ',', ' '),
                $this->salesInvoiceStatusLabels()[$record->status] ?? $record->status,
            ],
        };
    }

    public function accountingPurchaseOrders(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        $filters = [
            'supplier_id' => (int) $request->integer('supplier_id'),
            'status' => (string) $request->query('status', ''),
            'currency' => (string) $request->query('currency', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        $query = AccountingPurchaseOrder::query()
            ->with(['supplier', 'purchase', 'lines'])
            ->where('company_site_id', $site->id)
            ->when($filters['supplier_id'] > 0, fn ($query) => $query->where('supplier_id', $filters['supplier_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['currency'] !== '', fn ($query) => $query->where('currency', $filters['currency']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('order_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('order_date', '<=', $filters['date_to']))
            ->when($search !== '', fn ($query) => $this->applyTableSearch($query, $search, [
                'reference',
                'supplier_reference',
                'title',
                'order_date',
                'expected_delivery_date',
                'currency',
                'status',
                'notes',
                'terms',
                $this->relationTableSearch('supplier', ['reference', 'name', 'email', 'phone', 'address']),
            ]));

        return view('main.modules.accounting-purchase-orders', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'purchaseOrderPermissions' => $this->sitePermissionFlags($user, $site),
            'purchaseOrders' => (clone $query)->latest('order_date')->latest()->paginate(5)->withQueryString(),
            'suppliers' => AccountingSupplier::query()->where('company_site_id', $site->id)->orderBy('name')->get(),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->purchaseOrderStatusLabels(),
            'filters' => $filters,
        ]);
    }

    public function createAccountingPurchaseOrder(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        return view('main.modules.accounting-purchase-order-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'suppliers' => $this->purchaseSupplierOptions($site),
            'items' => $this->purchaseItemOptions($site),
            'services' => $this->purchaseServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->purchaseOrderStatusLabels(),
            'lineTypeLabels' => $this->purchaseOrderLineTypeLabels(),
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_PURCHASES),
        ]);
    }

    public function editAccountingPurchaseOrder(Company $company, CompanySite $site, AccountingPurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if ($purchaseOrder->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $purchaseOrder->isEditable()) {
            return redirect()
                ->route('main.accounting.purchase-orders', [$company, $site])
                ->with('success', __('main.purchase_order_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        return view('main.modules.accounting-purchase-order-create', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'purchaseOrder' => $purchaseOrder->load('lines'),
            'suppliers' => $this->purchaseSupplierOptions($site),
            'items' => $this->purchaseItemOptions($site),
            'services' => $this->purchaseServiceOptions($site),
            'currencies' => $this->siteCurrencyOptions($site),
            'statusLabels' => $this->purchaseOrderStatusLabels(),
            'lineTypeLabels' => $this->purchaseOrderLineTypeLabels(),
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_PURCHASES),
        ]);
    }

    public function storeAccountingPurchaseOrder(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        $validated = $request->validate($this->purchaseOrderRules($site));

        DB::transaction(function () use ($site, $user, $validated): void {
            $totals = $this->calculatePurchaseOrderTotals($validated['lines'], (float) $validated['tax_rate']);
            $purchaseOrder = $site->accountingPurchaseOrders()->create($this->purchaseOrderPayload($validated, $user, $totals));
            $this->syncPurchaseOrderLines($purchaseOrder, $site, $user, $validated['lines'], $validated['currency']);
        });

        return redirect()
            ->route('main.accounting.purchase-orders', [$company, $site])
            ->with('success', __('main.purchase_order_saved'));
    }

    public function updateAccountingPurchaseOrder(Request $request, Company $company, CompanySite $site, AccountingPurchaseOrder $purchaseOrder): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($purchaseOrder->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $purchaseOrder->isEditable()) {
            return redirect()
                ->route('main.accounting.purchase-orders', [$company, $site])
                ->with('success', __('main.purchase_order_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingStockRecords($site);

        $validated = $request->validate($this->purchaseOrderRules($site, true));

        DB::transaction(function () use ($purchaseOrder, $site, $user, $validated): void {
            $totals = $this->calculatePurchaseOrderTotals($validated['lines'], (float) $validated['tax_rate']);
            $purchaseOrder->update($this->purchaseOrderPayload($validated, $user, $totals, false));
            $this->syncPurchaseOrderLines($purchaseOrder, $site, $user, $validated['lines'], $validated['currency']);
        });

        return redirect()
            ->route('main.accounting.purchase-orders', [$company, $site])
            ->with('success', __('main.purchase_order_updated'));
    }

    public function convertAccountingPurchaseOrderToPurchase(Company $company, CompanySite $site, AccountingPurchaseOrder $purchaseOrder): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($purchaseOrder->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $purchaseOrder->isConvertible()) {
            return redirect()
                ->route('main.accounting.purchase-orders', [$company, $site])
                ->with('success', __('main.purchase_order_cannot_convert'))
                ->with('toast_type', 'danger');
        }

        DB::transaction(function () use ($purchaseOrder, $site, $user): void {
            $purchaseOrder->load('lines');

            $purchase = $site->accountingPurchases()->create([
                'supplier_id' => $purchaseOrder->supplier_id,
                'created_by' => $user->id,
                'supplier_invoice_reference' => $purchaseOrder->reference,
                'title' => $purchaseOrder->title,
                'purchase_date' => now()->toDateString(),
                'due_date' => null,
                'currency' => $purchaseOrder->currency,
                'status' => AccountingPurchase::STATUS_VALIDATED,
                'subtotal' => $purchaseOrder->subtotal,
                'discount_total' => $purchaseOrder->discount_total,
                'total_ht' => $purchaseOrder->total_ht,
                'tax_rate' => $purchaseOrder->tax_rate,
                'tax_amount' => $purchaseOrder->tax_amount,
                'total_ttc' => $purchaseOrder->total_ttc,
                'paid_total' => 0,
                'balance_due' => $purchaseOrder->total_ttc,
                'notes' => $purchaseOrder->notes,
                'terms' => $purchaseOrder->terms,
            ]);

            foreach ($purchaseOrder->lines as $line) {
                $purchase->lines()->create([
                    'line_type' => $line->line_type,
                    'item_id' => $line->item_id,
                    'service_id' => $line->service_id,
                    'description' => $line->description,
                    'details' => $line->details,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_type' => $line->discount_type,
                    'discount_amount' => $line->discount_amount,
                    'line_total' => $line->line_total,
                ]);
            }

            $purchaseOrder->forceFill([
                'purchase_id' => $purchase->id,
                'status' => AccountingPurchaseOrder::STATUS_CONVERTED,
                'converted_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('main.accounting.purchases', [$company, $site])
            ->with('success', __('main.purchase_order_converted'));
    }

    public function printAccountingPurchaseOrder(Company $company, CompanySite $site, AccountingPurchaseOrder $purchaseOrder): Response|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($purchaseOrder->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        $purchaseOrder->load(['supplier', 'creator', 'lines.item.unit', 'lines.service.unit']);
        $purchaseOrderUrl = route('main.accounting.purchase-orders.print', [$company, $site, $purchaseOrder]);

        return Pdf::loadView('main.modules.accounting-purchase-order-print', [
            'user' => $user,
            'company' => $company->load(['subscription', 'accounts']),
            'site' => $site->load('responsible'),
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderQrCodeDataUri' => $this->qrCodeSvgDataUri($purchaseOrderUrl),
            'statusLabels' => $this->purchaseOrderStatusLabels(),
            'isPdf' => true,
        ])->setPaper('a4')->stream('bon-de-commande-'.$purchaseOrder->reference.'.pdf');
    }

    public function destroyAccountingPurchaseOrder(Company $company, CompanySite $site, AccountingPurchaseOrder $purchaseOrder): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($purchaseOrder->company_site_id !== $site->id) {
            return redirect()->route('main.accounting.purchase-orders', [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_delete'] || ! $purchaseOrder->isEditable()) {
            return redirect()
                ->route('main.accounting.purchase-orders', [$company, $site])
                ->with('success', __('main.purchase_order_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $purchaseOrder->delete();

        return redirect()
            ->route('main.accounting.purchase-orders', [$company, $site])
            ->with('success', __('main.purchase_order_deleted'))
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
            'proformaDefaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
            'proformaDefaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
            'proformaDefaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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

    public function accountingExpenses(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = $this->tableSearch($request);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->ensureDefaultAccountingExpenseCategories($site);

        $categoryId = (int) $request->query('expense_category_id', 0);
        $paymentMethodId = (int) $request->query('payment_method_id', 0);
        $status = trim((string) $request->query('status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $query = AccountingExpense::query()
            ->with(['category', 'paymentMethod', 'creator'])
            ->where('company_site_id', $site->id)
            ->when($categoryId > 0, fn ($expenseQuery) => $expenseQuery->where('expense_category_id', $categoryId))
            ->when($paymentMethodId > 0, fn ($expenseQuery) => $expenseQuery->where('payment_method_id', $paymentMethodId))
            ->when($status !== '', fn ($expenseQuery) => $expenseQuery->where('status', $status))
            ->when($currency !== '', fn ($expenseQuery) => $expenseQuery->where('currency', $currency))
            ->when($dateFrom !== '', fn ($expenseQuery) => $expenseQuery->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($expenseQuery) => $expenseQuery->whereDate('expense_date', '<=', $dateTo))
            ->when($search !== '', fn ($expenseQuery) => $this->applyTableSearch($expenseQuery, $search, [
                'reference',
                'expense_date',
                'label',
                'beneficiary',
                'description',
                'amount',
                'currency',
                'payment_reference',
                'status',
                $this->relationTableSearch('category', ['name', 'slug']),
                $this->relationTableSearch('paymentMethod', ['name', 'currency_code', 'code']),
                $this->relationTableSearch('creator', ['name', 'email']),
            ]));

        $expenses = (clone $query)
            ->latest('expense_date')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.accounting-expenses', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'expensePermissions' => $this->sitePermissionFlags($user, $site),
            'expenses' => $expenses,
            'totalValidated' => (float) (clone $query)->where('status', AccountingExpense::STATUS_VALIDATED)->sum('amount'),
            'statusLabels' => $this->expenseStatusLabels(),
            'categories' => AccountingExpenseCategory::query()
                ->where('company_site_id', $site->id)
                ->where('status', AccountingExpenseCategory::STATUS_ACTIVE)
                ->orderByDesc('is_system_default')
                ->orderBy('name')
                ->get(['id', 'name']),
            'paymentMethods' => AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'currency_code']),
            'currencies' => $this->siteCurrencyOptions($site),
            'filters' => [
                'expense_category_id' => $categoryId,
                'payment_method_id' => $paymentMethodId,
                'status' => $status,
                'currency' => $currency,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function storeAccountingExpense(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.expenses', [$company, $site]);
        }

        $this->ensureDefaultAccountingExpenseCategories($site);
        $validated = $request->validate($this->expenseRules($site));

        $site->accountingExpenses()->create($this->expensePayload($validated, $user));

        return redirect()
            ->route('main.accounting.expenses', [$company, $site])
            ->with('success', __('main.expense_saved'));
    }

    public function updateAccountingExpense(Request $request, Company $company, CompanySite $site, AccountingExpense $expense): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $expense->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.expenses', [$company, $site]);
        }

        if (! $expense->isDraft()) {
            return redirect()
                ->route('main.accounting.expenses', [$company, $site])
                ->with('success', __('main.expense_cannot_update'))
                ->with('toast_type', 'danger');
        }

        $this->ensureDefaultAccountingExpenseCategories($site);
        $validated = $request->validate($this->expenseRules($site));
        $expense->update($this->expensePayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.expenses', [$company, $site])
            ->with('success', __('main.expense_updated'));
    }

    public function validateAccountingExpense(Company $company, CompanySite $site, AccountingExpense $expense): RedirectResponse
    {
        return $this->changeAccountingExpenseStatus($company, $site, $expense, AccountingExpense::STATUS_VALIDATED, __('main.expense_validated'));
    }

    public function cancelAccountingExpense(Company $company, CompanySite $site, AccountingExpense $expense): RedirectResponse
    {
        return $this->changeAccountingExpenseStatus($company, $site, $expense, AccountingExpense::STATUS_CANCELLED, __('main.expense_cancelled'));
    }

    public function destroyAccountingExpense(Company $company, CompanySite $site, AccountingExpense $expense): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $expense->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.expenses', [$company, $site]);
        }

        if (! $expense->isDraft()) {
            return redirect()
                ->route('main.accounting.expenses', [$company, $site])
                ->with('success', __('main.expense_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $expense->delete();

        return redirect()
            ->route('main.accounting.expenses', [$company, $site])
            ->with('success', __('main.expense_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingDebts(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = Str::lower($this->tableSearch($request));

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->refreshOverdueAccountingPurchases($site);

        $source = trim((string) $request->query('source', ''));
        $status = trim((string) $request->query('status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $creditorsQuery = AccountingCreditor::query()
            ->with(['payments.paymentMethod', 'payments.payer'])
            ->where('company_site_id', $site->id)
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dateTo));

        $purchasesQuery = AccountingPurchase::query()
            ->with(['supplier', 'payments.paymentMethod', 'payments.payer'])
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dateTo));

        $manualDebts = $source === 'purchase' ? collect() : $creditorsQuery->get();
        $purchaseDebts = $source === 'manual' ? collect() : $purchasesQuery->get();

        $rows = $manualDebts
            ->map(fn (AccountingCreditor $creditor) => [
                'source' => 'manual',
                'id' => $creditor->id,
                'reference' => $creditor->reference,
                'third_party' => $creditor->name,
                'type' => $this->accountingCreditorTypeLabels()[$creditor->type] ?? $creditor->type,
                'date' => optional($creditor->created_at)->format('Y-m-d'),
                'due_date' => optional($creditor->due_date)->format('Y-m-d'),
                'total' => (float) $creditor->initial_amount,
                'paid' => (float) $creditor->paid_amount,
                'balance' => $creditor->balanceDue(),
                'currency' => $creditor->currency,
                'status' => $this->accountingCreditorStatusLabels()[$creditor->status] ?? $creditor->status,
                'raw_status' => $creditor->status,
                'model' => $creditor,
            ])
            ->merge($purchaseDebts->map(fn (AccountingPurchase $purchase) => [
                'source' => 'purchase',
                'id' => $purchase->id,
                'reference' => $purchase->reference,
                'third_party' => $purchase->supplier?->name ?? '-',
                'type' => __('main.supplier_purchase'),
                'date' => optional($purchase->purchase_date)->format('Y-m-d'),
                'due_date' => optional($purchase->due_date)->format('Y-m-d'),
                'total' => (float) $purchase->total_ttc,
                'paid' => (float) $purchase->paid_total,
                'balance' => (float) $purchase->balance_due,
                'currency' => $purchase->currency,
                'status' => $this->purchaseStatusLabels()[$purchase->status] ?? $purchase->status,
                'raw_status' => $purchase->status,
                'model' => $purchase,
            ]));

        if ($search !== '') {
            $rows = $rows->filter(fn (array $row) => Str::contains(Str::lower(implode(' ', [
                $row['reference'],
                $row['third_party'],
                $row['type'],
                $row['status'],
                $row['currency'],
            ])), $search));
        }

        $rows = $rows->sortBy([
            fn (array $a, array $b) => strcmp($a['due_date'] ?: '9999-12-31', $b['due_date'] ?: '9999-12-31'),
            fn (array $a, array $b) => strcmp($b['reference'], $a['reference']),
        ])->values();

        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 5;
        $debts = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalBalance = (float) $rows->sum('balance');
        $overdueBalance = (float) $rows
            ->filter(fn (array $row) => filled($row['due_date']) && $row['due_date'] < now()->toDateString() && (float) $row['balance'] > 0)
            ->sum('balance');

        return view('main.modules.accounting-debts', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'debtPermissions' => $this->sitePermissionFlags($user, $site),
            'debts' => $debts,
            'manualDebts' => $manualDebts,
            'purchaseDebts' => $purchaseDebts,
            'existingCreditors' => AccountingCreditor::query()
                ->where('company_site_id', $site->id)
                ->orderBy('name')
                ->get(['id', 'reference', 'type', 'name', 'phone', 'email', 'address', 'currency'])
                ->unique(fn (AccountingCreditor $creditor) => implode('|', [
                    $creditor->type,
                    Str::lower($creditor->name),
                    Str::lower((string) $creditor->phone),
                    Str::lower((string) $creditor->email),
                    Str::lower((string) $creditor->address),
                    $creditor->currency,
                ]))
                ->values(),
            'totalBalance' => $totalBalance,
            'overdueBalance' => $overdueBalance,
            'currencies' => $this->siteCurrencyOptions($site),
            'allCurrencies' => CurrencyCatalog::sorted(),
            'paymentMethods' => AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'currency_code']),
            'typeLabels' => $this->accountingCreditorTypeLabels(),
            'priorityLabels' => $this->accountingCreditorPriorityLabels(),
            'statusLabels' => $this->accountingCreditorStatusLabels(),
            'purchaseStatusLabels' => $this->purchaseStatusLabels(),
            'filters' => compact('source', 'status', 'currency', 'dateFrom', 'dateTo'),
        ]);
    }

    public function storeAccountingDebt(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.debts', [$company, $site]);
        }

        $validated = $request->validate($this->accountingCreditorRules());
        $existingCreditorId = (int) ($validated['existing_creditor_id'] ?? 0);

        if ($existingCreditorId > 0) {
            $creditor = AccountingCreditor::query()
                ->where('company_site_id', $site->id)
                ->find($existingCreditorId);

            if (! $creditor) {
                return redirect()
                    ->route('main.accounting.debts', [$company, $site])
                    ->withErrors(['existing_creditor_id' => __('main.existing_creditor_not_found')])
                    ->withInput();
            }

            if ($creditor->currency !== $validated['currency']) {
                return redirect()
                    ->route('main.accounting.debts', [$company, $site])
                    ->withErrors(['currency' => __('main.existing_creditor_currency_mismatch')])
                    ->withInput();
            }

            $site->accountingCreditors()->create([
                'created_by' => $user->id,
                'type' => $creditor->type,
                'name' => $creditor->name,
                'phone' => $creditor->phone,
                'email' => $creditor->email,
                'address' => $creditor->address,
                'currency' => $creditor->currency,
                'initial_amount' => round((float) $validated['initial_amount'], 2),
                'paid_amount' => round((float) $validated['paid_amount'], 2),
                'due_date' => $validated['due_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'],
                'status' => (float) $validated['paid_amount'] >= (float) $validated['initial_amount']
                    ? AccountingCreditor::STATUS_SETTLED
                    : $validated['status'],
            ]);

            return redirect()
                ->route('main.accounting.debts', [$company, $site])
                ->with('success', __('main.debt_added_to_existing_creditor'));
        }

        $payload = $this->accountingCreditorPayload($validated, $user);
        unset($payload['existing_creditor_id']);

        $site->accountingCreditors()->create($payload);

        return redirect()
            ->route('main.accounting.debts', [$company, $site])
            ->with('success', __('main.debt_saved'));
    }

    public function updateAccountingDebt(Request $request, Company $company, CompanySite $site, AccountingCreditor $creditor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $creditor->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.debts', [$company, $site]);
        }

        $validated = $request->validate($this->accountingCreditorRules());
        $creditor->update($this->accountingCreditorPayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.debts', [$company, $site])
            ->with('success', __('main.debt_updated'));
    }

    public function storeAccountingDebtPayment(Request $request, Company $company, CompanySite $site, AccountingCreditor $creditor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $creditor->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.debts', [$company, $site]);
        }

        $validated = $request->validate($this->creditorPaymentRules($site, $creditor));

        DB::transaction(function () use ($creditor, $validated, $user): void {
            $creditor->payments()->create([
                'payment_method_id' => $validated['payment_method_id'],
                'paid_by' => $user->id,
                'payment_date' => $validated['payment_date'],
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $creditor->currency,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $paidAmount = round((float) $creditor->paid_amount + (float) $validated['amount'], 2);
            $creditor->update([
                'paid_amount' => $paidAmount,
                'status' => $paidAmount >= (float) $creditor->initial_amount
                    ? AccountingCreditor::STATUS_SETTLED
                    : AccountingCreditor::STATUS_ACTIVE,
            ]);
        });

        return redirect()
            ->route('main.accounting.debts', [$company, $site])
            ->with('success', __('main.debt_payment_saved'));
    }

    public function destroyAccountingDebt(Company $company, CompanySite $site, AccountingCreditor $creditor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $creditor->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.debts', [$company, $site]);
        }

        if ($creditor->payments()->exists()) {
            return redirect()
                ->route('main.accounting.debts', [$company, $site])
                ->with('success', __('main.debt_with_payments_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $creditor->delete();

        return redirect()
            ->route('main.accounting.debts', [$company, $site])
            ->with('success', __('main.debt_deleted'))
            ->with('toast_type', 'danger');
    }

    public function accountingReceivables(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);
        $search = Str::lower($this->tableSearch($request));

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $this->ensureDefaultAccountingCurrencyRecord($site);
        $this->ensureDefaultAccountingPaymentMethodRecord($site);
        $this->refreshOverdueSalesInvoices($site);

        $source = trim((string) $request->query('source', ''));
        $status = trim((string) $request->query('status', ''));
        $currency = strtoupper(trim((string) $request->query('currency', '')));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $debtorsQuery = AccountingDebtor::query()
            ->with(['payments.paymentMethod', 'payments.receiver'])
            ->where('company_site_id', $site->id)
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dateTo));

        $invoicesQuery = AccountingSalesInvoice::query()
            ->with(['client', 'payments.paymentMethod', 'payments.receiver'])
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dateTo));

        $manualReceivables = $source === 'invoice' ? collect() : $debtorsQuery->get();
        $invoiceReceivables = $source === 'manual' ? collect() : $invoicesQuery->get();

        $rows = $manualReceivables
            ->map(fn (AccountingDebtor $debtor) => [
                'source' => 'manual',
                'id' => $debtor->id,
                'reference' => $debtor->reference,
                'third_party' => $debtor->name,
                'type' => $this->accountingDebtorTypeLabels()[$debtor->type] ?? $debtor->type,
                'date' => optional($debtor->created_at)->format('Y-m-d'),
                'due_date' => optional($debtor->due_date)->format('Y-m-d'),
                'total' => (float) $debtor->initial_amount,
                'received' => (float) $debtor->received_amount,
                'balance' => $debtor->balanceReceivable(),
                'currency' => $debtor->currency,
                'status' => $this->accountingDebtorStatusLabels()[$debtor->status] ?? $debtor->status,
                'raw_status' => $debtor->status,
                'model' => $debtor,
            ])
            ->merge($invoiceReceivables->map(fn (AccountingSalesInvoice $invoice) => [
                'source' => 'invoice',
                'id' => $invoice->id,
                'reference' => $invoice->reference,
                'third_party' => $invoice->client?->name ?? '-',
                'type' => __('main.sales_invoice'),
                'date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'total' => (float) $invoice->total_ttc,
                'received' => (float) $invoice->paid_total,
                'balance' => (float) $invoice->balance_due,
                'currency' => $invoice->currency,
                'status' => $this->salesInvoiceStatusLabels()[$invoice->status] ?? $invoice->status,
                'raw_status' => $invoice->status,
                'model' => $invoice,
            ]));

        if ($search !== '') {
            $rows = $rows->filter(fn (array $row) => Str::contains(Str::lower(implode(' ', [
                $row['reference'],
                $row['third_party'],
                $row['type'],
                $row['status'],
                $row['currency'],
            ])), $search));
        }

        $rows = $rows->sortBy([
            fn (array $a, array $b) => strcmp($a['due_date'] ?: '9999-12-31', $b['due_date'] ?: '9999-12-31'),
            fn (array $a, array $b) => strcmp($b['reference'], $a['reference']),
        ])->values();

        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 5;
        $receivables = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('main.modules.accounting-receivables', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_ACCOUNTING,
            'moduleMeta' => $moduleMeta,
            'receivablePermissions' => $this->sitePermissionFlags($user, $site),
            'receivables' => $receivables,
            'manualReceivables' => $manualReceivables,
            'invoiceReceivables' => $invoiceReceivables,
            'existingDebtors' => AccountingDebtor::query()
                ->where('company_site_id', $site->id)
                ->orderBy('name')
                ->get(['id', 'reference', 'type', 'name', 'phone', 'email', 'address', 'currency'])
                ->unique(fn (AccountingDebtor $debtor) => implode('|', [
                    $debtor->type,
                    Str::lower($debtor->name),
                    Str::lower((string) $debtor->phone),
                    Str::lower((string) $debtor->email),
                    Str::lower((string) $debtor->address),
                    $debtor->currency,
                ]))
                ->values(),
            'totalBalance' => (float) $rows->sum('balance'),
            'currencies' => $this->siteCurrencyOptions($site),
            'paymentMethods' => AccountingPaymentMethod::query()
                ->where('company_site_id', $site->id)
                ->where('status', AccountingPaymentMethod::STATUS_ACTIVE)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'currency_code']),
            'typeLabels' => $this->accountingDebtorTypeLabels(),
            'statusLabels' => $this->accountingDebtorStatusLabels(),
            'invoiceStatusLabels' => $this->salesInvoiceStatusLabels(),
            'filters' => compact('source', 'status', 'currency', 'dateFrom', 'dateTo'),
        ]);
    }

    public function storeAccountingReceivable(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->sitePermissionFlags($user, $site)['can_create']) {
            return redirect()->route('main.accounting.receivables', [$company, $site]);
        }

        $validated = $request->validate($this->accountingDebtorRules());
        $existingDebtorId = (int) ($validated['existing_debtor_id'] ?? 0);

        if ($existingDebtorId > 0) {
            $debtor = AccountingDebtor::query()
                ->where('company_site_id', $site->id)
                ->find($existingDebtorId);

            if (! $debtor) {
                return redirect()
                    ->route('main.accounting.receivables', [$company, $site])
                    ->withErrors(['existing_debtor_id' => __('main.existing_debtor_not_found')])
                    ->withInput();
            }

            if ($debtor->currency !== $validated['currency']) {
                return redirect()
                    ->route('main.accounting.receivables', [$company, $site])
                    ->withErrors(['currency' => __('main.existing_debtor_currency_mismatch')])
                    ->withInput();
            }

            $site->accountingDebtors()->create([
                'created_by' => $user->id,
                'type' => $debtor->type,
                'name' => $debtor->name,
                'phone' => $debtor->phone,
                'email' => $debtor->email,
                'address' => $debtor->address,
                'currency' => $debtor->currency,
                'initial_amount' => round((float) $validated['initial_amount'], 2),
                'received_amount' => round((float) $validated['received_amount'], 2),
                'due_date' => $validated['due_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => (float) $validated['received_amount'] >= (float) $validated['initial_amount']
                    ? AccountingDebtor::STATUS_SETTLED
                    : $validated['status'],
            ]);

            return redirect()
                ->route('main.accounting.receivables', [$company, $site])
                ->with('success', __('main.receivable_added_to_existing_debtor'));
        }

        $payload = $this->accountingDebtorPayload($validated, $user);
        unset($payload['existing_debtor_id']);

        $site->accountingDebtors()->create($payload);

        return redirect()
            ->route('main.accounting.receivables', [$company, $site])
            ->with('success', __('main.receivable_saved'));
    }

    public function updateAccountingReceivable(Request $request, Company $company, CompanySite $site, AccountingDebtor $debtor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $debtor->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.receivables', [$company, $site]);
        }

        $validated = $request->validate($this->accountingDebtorRules());
        $debtor->update($this->accountingDebtorPayload($validated, $user, false));

        return redirect()
            ->route('main.accounting.receivables', [$company, $site])
            ->with('success', __('main.receivable_updated'));
    }

    public function storeAccountingReceivablePayment(Request $request, Company $company, CompanySite $site, AccountingDebtor $debtor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $debtor->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.receivables', [$company, $site]);
        }

        $validated = $request->validate($this->debtorPaymentRules($site, $debtor));

        DB::transaction(function () use ($debtor, $validated, $user): void {
            $debtor->payments()->create([
                'payment_method_id' => $validated['payment_method_id'],
                'received_by' => $user->id,
                'payment_date' => $validated['payment_date'],
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $debtor->currency,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $receivedAmount = round((float) $debtor->received_amount + (float) $validated['amount'], 2);
            $debtor->update([
                'received_amount' => $receivedAmount,
                'status' => $receivedAmount >= (float) $debtor->initial_amount
                    ? AccountingDebtor::STATUS_SETTLED
                    : AccountingDebtor::STATUS_ACTIVE,
            ]);
        });

        return redirect()
            ->route('main.accounting.receivables', [$company, $site])
            ->with('success', __('main.receivable_payment_saved'));
    }

    public function destroyAccountingReceivable(Company $company, CompanySite $site, AccountingDebtor $debtor): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $debtor->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_delete']) {
            return redirect()->route('main.accounting.receivables', [$company, $site]);
        }

        if ($debtor->payments()->exists()) {
            return redirect()
                ->route('main.accounting.receivables', [$company, $site])
                ->with('success', __('main.receivable_with_payments_cannot_delete'))
                ->with('toast_type', 'danger');
        }

        $debtor->delete();

        return redirect()
            ->route('main.accounting.receivables', [$company, $site])
            ->with('success', __('main.receivable_deleted'))
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_PURCHASES),
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_PURCHASES),
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
        $returnRoute = $request->input('return_to') === 'debts'
            ? 'main.accounting.debts'
            : 'main.accounting.purchases';

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $purchase->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route($returnRoute, [$company, $site]);
        }

        if (in_array($purchase->status, [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED, AccountingPurchase::STATUS_PAID], true)) {
            return redirect()
                ->route($returnRoute, [$company, $site])
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
            ->route($returnRoute, [$company, $site])
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
            'defaultTaxRate' => $this->defaultAccountingTaxRate($site, $company, AccountingTax::APPLIES_SALES),
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
        $returnRoute = $request->input('return_to') === 'receivables'
            ? 'main.accounting.receivables'
            : 'main.accounting.sales-invoices';

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ($invoice->company_site_id !== $site->id) {
            return redirect()->route($returnRoute, [$company, $site]);
        }

        if (! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route($returnRoute, [$company, $site]);
        }

        if (in_array($invoice->status, [AccountingSalesInvoice::STATUS_CANCELLED, AccountingSalesInvoice::STATUS_PAID], true)) {
            return redirect()
                ->route($returnRoute, [$company, $site])
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
            ->route($returnRoute, [$company, $site])
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

    private function canOpenAccountingNotificationModule(User&Authenticatable $user, CompanySite $site, ?string $moduleKey): bool
    {
        if (! $moduleKey || ! in_array($moduleKey, AccountingModuleNavigation::keys(), true)) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperadmin()) {
            return true;
        }

        $permissions = AccountingMenuPermission::query()
            ->where('company_site_id', $site->id)
            ->where('user_id', $user->id)
            ->get();

        return $permissions->isEmpty()
            || $permissions->where('menu_key', $moduleKey)->where('is_allowed', true)->isNotEmpty();
    }

    private function accountingModuleLabel(?string $moduleKey): string
    {
        if (! $moduleKey) {
            return '-';
        }

        foreach ($this->accountingSettingsMenuGroups() as $group) {
            foreach ($group['items'] as $item) {
                if (($item['key'] ?? null) === $moduleKey) {
                    return $item['label'];
                }
            }
        }

        return Str::headline(str_replace('-', ' ', $moduleKey));
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

    private function accountingSettingsMenuGroups(): array
    {
        $labels = [
            'dashboard' => __('main.dashboard'),
            'prospects' => __('main.prospects'),
            'clients' => __('main.customers'),
            'suppliers' => __('main.suppliers'),
            'creditors' => __('main.creditors'),
            'debtors' => __('main.debtors'),
            'partners' => __('main.partners'),
            'sales-representatives' => __('main.sales_representatives'),
            'stock-items' => __('main.items'),
            'stock-categories' => __('main.categories'),
            'stock-subcategories' => __('main.subcategories'),
            'stock-warehouses' => __('main.stock_warehouses'),
            'stock-movements' => __('main.stock_movements'),
            'stock-inventories' => __('main.stock_inventories'),
            'stock-alerts' => __('main.stock_alerts'),
            'stock-units' => __('main.stock_units'),
            'stock-batches' => __('main.stock_batches'),
            'stock-transfers' => __('main.stock_transfers'),
            'service-price-list' => __('main.price_list'),
            'service-categories' => __('main.service_categories'),
            'service-subcategories' => __('main.service_subcategories'),
            'service-units' => __('main.service_units'),
            'service-recurring' => __('main.recurring_services'),
            'currencies' => __('main.currencies'),
            'payment-methods' => __('main.payment_methods'),
            'taxes' => __('main.taxes'),
            'proforma-invoices' => __('main.proforma_invoices'),
            'customer-orders' => __('main.customer_orders'),
            'delivery-notes' => __('main.delivery_notes'),
            'sales-invoices' => __('main.sales_invoices'),
            'credit-notes' => __('main.credit_notes'),
            'receipts' => __('main.payments_received'),
            'cash-register' => __('main.cash_register'),
            'other-incomes' => __('main.other_income'),
            'purchases' => __('main.purchases'),
            'purchase-orders' => __('main.purchase_orders'),
            'expenses' => __('main.expenses'),
            'debts' => __('main.debts'),
            'receivables' => __('main.receivables'),
            'treasury' => __('main.cashflow'),
            'bank-reconciliations' => __('main.bank_reconciliation'),
            'payment-reminders' => __('main.payment_reminders'),
            'tasks' => __('main.tasks'),
            'reports' => __('main.reports'),
        ];
        $groupLabels = [
            'overview' => __('main.dashboard'),
            'contacts' => __('main.contacts'),
            'stock' => __('main.stock'),
            'services' => __('main.services'),
            'setup' => __('main.module_configuration'),
            'sales' => __('main.sales'),
            'expenses' => __('main.expenses_group'),
            'monitoring' => __('main.other'),
        ];

        return collect(AccountingModuleNavigation::GROUPS)
            ->mapWithKeys(fn (array $keys, string $group): array => [
                $group => [
                    'label' => $groupLabels[$group],
                    'items' => collect($keys)->map(fn (string $key): array => [
                        'key' => $key,
                        'label' => $labels[$key],
                    ])->all(),
                ],
            ])
            ->all();
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
            'existing_creditor_id' => ['nullable', 'integer'],
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

    private function creditorPaymentRules(CompanySite $site, AccountingCreditor $creditor): array
    {
        $balanceDue = round($creditor->balanceDue(), 2);

        return [
            'payment_method_id' => ['required', 'integer', Rule::exists('accounting_payment_methods', 'id')->where('company_site_id', $site->id)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$balanceDue],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function accountingDebtorRules(): array
    {
        return [
            'existing_debtor_id' => ['nullable', 'integer'],
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

    private function debtorPaymentRules(CompanySite $site, AccountingDebtor $debtor): array
    {
        $balanceReceivable = round($debtor->balanceReceivable(), 2);

        return [
            'payment_method_id' => ['required', 'integer', Rule::exists('accounting_payment_methods', 'id')->where('company_site_id', $site->id)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$balanceReceivable],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
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

    private function accountingDashboardData(CompanySite $site): array
    {
        $currency = $site->currency ?: 'CDF';

        $this->refreshOverdueSalesInvoices($site);
        $this->refreshOverdueAccountingPurchases($site);
        $this->syncAccountingTreasuryMovements($site);

        $salesInvoiceBase = AccountingSalesInvoice::query()
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED]);
        $purchaseBase = AccountingPurchase::query()
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED]);
        $validatedExpenseBase = AccountingExpense::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingExpense::STATUS_VALIDATED);
        $revenue = $this->accountingDashboardNetSalesAmount(clone $salesInvoiceBase);
        $expenses = (float) (clone $purchaseBase)->sum('total_ttc')
            + (float) (clone $validatedExpenseBase)->sum('amount');
        $receivables = (float) AccountingSalesInvoice::query()
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_CANCELLED])
            ->sum('balance_due')
            + AccountingDebtor::query()
                ->where('company_site_id', $site->id)
                ->get(['initial_amount', 'received_amount'])
                ->sum(fn (AccountingDebtor $debtor) => $debtor->balanceReceivable());
        $debts = (float) AccountingPurchase::query()
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingPurchase::STATUS_CANCELLED])
            ->sum('balance_due')
            + AccountingCreditor::query()
                ->where('company_site_id', $site->id)
                ->get(['initial_amount', 'paid_amount'])
                ->sum(fn (AccountingCreditor $creditor) => $creditor->balanceDue());
        $validatedTreasuryBase = AccountingTreasuryMovement::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED);
        $treasuryInflows = (float) (clone $validatedTreasuryBase)
            ->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)
            ->sum('amount');
        $treasuryOutflows = (float) (clone $validatedTreasuryBase)
            ->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)
            ->sum('amount');
        $treasuryBalance = $treasuryInflows - $treasuryOutflows;
        $openTasksQuery = AccountingTask::query()
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingTask::STATUS_COMPLETED, AccountingTask::STATUS_CANCELLED]);
        $openRemindersQuery = AccountingPaymentReminder::query()
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingPaymentReminder::STATUS_SETTLED, AccountingPaymentReminder::STATUS_SUSPENDED]);
        $openReconciliationsQuery = AccountingBankReconciliation::query()
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingBankReconciliation::STATUS_IN_PROGRESS, AccountingBankReconciliation::STATUS_RECONCILED]);

        $chartData = [
            'emptyLabel' => __('main.no_accounting_documents'),
            'emptyClientsLabel' => __('main.no_clients'),
            'labels' => [
                'revenue' => __('main.revenue'),
                'sales' => __('main.sales'),
                'expenses' => __('main.expenses'),
                'customers' => __('main.customers'),
                'suppliers' => __('main.suppliers'),
                'prospects' => __('main.prospects'),
                'creditors' => __('main.creditors'),
                'debtors' => __('main.debtors'),
                'stock' => __('main.stock'),
                'services' => __('main.services'),
                'documents' => __('main.documents'),
                'cashflow' => __('main.cashflow'),
                'receivables' => __('main.receivables'),
                'debts' => __('main.debts'),
                'treasury_inflows' => __('main.treasury_inflows'),
                'treasury_outflows' => __('main.treasury_outflows'),
                'schedule' => __('main.schedule'),
            ],
            'periods' => [
                'week' => $this->accountingDashboardPeriodData($site, 'week'),
                'month' => $this->accountingDashboardPeriodData($site, 'month'),
                'year' => $this->accountingDashboardPeriodData($site, 'year'),
            ],
            'contacts' => [
                'labels' => [__('main.prospects'), __('main.customers'), __('main.suppliers'), __('main.partners'), __('main.sales_representatives'), __('main.client_contacts'), __('main.supplier_contacts')],
                'series' => [
                    AccountingProspect::query()->where('company_site_id', $site->id)->count(),
                    AccountingClient::query()->where('company_site_id', $site->id)->count(),
                    AccountingSupplier::query()->where('company_site_id', $site->id)->count(),
                    AccountingPartner::query()->where('company_site_id', $site->id)->count(),
                    AccountingSalesRepresentative::query()->where('company_site_id', $site->id)->count(),
                    DB::table('accounting_client_contacts')
                        ->join('accounting_clients', 'accounting_client_contacts.accounting_client_id', '=', 'accounting_clients.id')
                        ->where('accounting_clients.company_site_id', $site->id)
                        ->count(),
                    DB::table('accounting_supplier_contacts')
                        ->join('accounting_suppliers', 'accounting_supplier_contacts.accounting_supplier_id', '=', 'accounting_suppliers.id')
                        ->where('accounting_suppliers.company_site_id', $site->id)
                        ->count(),
                ],
            ],
            'stockServices' => [
                'labels' => [__('main.items'), __('main.categories'), __('main.services'), __('main.price_list')],
                'series' => [
                    AccountingStockItem::query()->where('company_site_id', $site->id)->count(),
                    AccountingStockCategory::query()->where('company_site_id', $site->id)->count(),
                    AccountingService::query()->where('company_site_id', $site->id)->count(),
                    AccountingService::query()->where('company_site_id', $site->id)->where('status', AccountingService::STATUS_ACTIVE)->count(),
                ],
            ],
            'documents' => [
                'labels' => [__('main.sales_invoices'), __('main.proforma_invoices'), __('main.customer_orders'), __('main.delivery_notes'), __('main.purchase_orders'), __('main.credit_notes')],
                'series' => [
                    AccountingSalesInvoice::query()->where('company_site_id', $site->id)->count(),
                    AccountingProformaInvoice::query()->where('company_site_id', $site->id)->count(),
                    AccountingCustomerOrder::query()->where('company_site_id', $site->id)->count(),
                    AccountingDeliveryNote::query()->where('company_site_id', $site->id)->count(),
                    AccountingPurchaseOrder::query()->where('company_site_id', $site->id)->count(),
                    AccountingCreditNote::query()->where('company_site_id', $site->id)->count(),
                ],
            ],
        ];

        return [
            'kpis' => [
                ['label' => __('main.revenue'), 'value' => $this->dashboardMoney($revenue, $currency), 'icon' => 'bi-graph-up-arrow', 'tone' => 'blue', 'trend' => null],
                ['label' => __('main.sales_invoices'), 'value' => (clone $salesInvoiceBase)->count(), 'icon' => 'bi-receipt', 'tone' => 'violet', 'trend' => null],
                ['label' => __('main.customers'), 'value' => AccountingClient::query()->where('company_site_id', $site->id)->count(), 'icon' => 'bi-person-check', 'tone' => 'green', 'trend' => null],
                ['label' => __('main.receivables'), 'value' => $this->dashboardMoney($receivables, $currency), 'icon' => 'bi-arrow-down-left-circle', 'tone' => 'amber', 'trend' => null],
                ['label' => __('main.expenses'), 'value' => $this->dashboardMoney($expenses, $currency), 'icon' => 'bi-wallet2', 'tone' => 'rose', 'trend' => null],
            ],
            'chartData' => $chartData,
            'operations' => [
                ['label' => __('main.treasury_balance'), 'value' => $this->dashboardMoney($treasuryBalance, $currency), 'meta' => __('main.treasury_inflows').' '.$this->dashboardMoney($treasuryInflows, $currency).' / '.__('main.treasury_outflows').' '.$this->dashboardMoney($treasuryOutflows, $currency), 'icon' => 'bi-activity', 'tone' => $treasuryBalance >= 0 ? 'green' : 'rose', 'route' => 'main.accounting.treasury'],
                ['label' => __('main.open_tasks'), 'value' => (clone $openTasksQuery)->count(), 'meta' => __('main.overdue_tasks').' '.(clone $openTasksQuery)->whereDate('due_date', '<', now()->toDateString())->count().' / '.__('main.urgent_tasks').' '.(clone $openTasksQuery)->where('priority', AccountingTask::PRIORITY_URGENT)->count(), 'icon' => 'bi-check2-square', 'tone' => 'blue', 'route' => 'main.accounting.tasks'],
                ['label' => __('main.payment_reminders'), 'value' => (clone $openRemindersQuery)->count(), 'meta' => __('main.pending_promises').' '.AccountingPaymentPromise::query()->whereHas('reminder', fn ($query) => $query->where('company_site_id', $site->id))->where('status', AccountingPaymentPromise::STATUS_PENDING)->count(), 'icon' => 'bi-bell', 'tone' => 'amber', 'route' => 'main.accounting.payment-reminders'],
                ['label' => __('main.bank_reconciliation'), 'value' => (clone $openReconciliationsQuery)->count(), 'meta' => __('main.unmatched_bank_lines').' '.AccountingBankStatementLine::query()->whereHas('reconciliation', fn ($query) => $query->where('company_site_id', $site->id))->where('status', AccountingBankStatementLine::STATUS_UNMATCHED)->count(), 'icon' => 'bi-bank', 'tone' => 'violet', 'route' => 'main.accounting.bank-reconciliations'],
                ['label' => __('main.taxes'), 'value' => AccountingTax::query()->where('company_site_id', $site->id)->where('status', AccountingTax::STATUS_ACTIVE)->count(), 'meta' => __('main.payment_methods').' '.AccountingPaymentMethod::query()->where('company_site_id', $site->id)->where('status', AccountingPaymentMethod::STATUS_ACTIVE)->count(), 'icon' => 'bi-percent', 'tone' => 'cyan', 'route' => 'main.accounting.taxes'],
                ['label' => __('main.cash_register'), 'value' => AccountingCashRegisterSession::query()->where('company_site_id', $site->id)->where('status', AccountingCashRegisterSession::STATUS_OPEN)->count(), 'meta' => __('main.purchase_orders').' '.AccountingPurchaseOrder::query()->where('company_site_id', $site->id)->whereNotIn('status', [AccountingPurchaseOrder::STATUS_CANCELLED, AccountingPurchaseOrder::STATUS_CONVERTED])->count(), 'icon' => 'bi-calculator', 'tone' => 'green', 'route' => 'main.accounting.cash-register'],
            ],
            'scheduleSummary' => [
                ['label' => __('main.clients_owe_me'), 'amount' => $this->dashboardMoney($receivables, $currency), 'tone' => 'green', 'icon' => 'bi-arrow-down-left-circle'],
                ['label' => __('main.i_owe_suppliers'), 'amount' => $this->dashboardMoney($debts, $currency), 'tone' => 'rose', 'icon' => 'bi-arrow-up-right-circle'],
            ],
            'scheduleItems' => $this->accountingDashboardScheduleItems($site, $currency),
        ];
    }

    private function accountingDashboardPeriodData(CompanySite $site, string $period): array
    {
        $buckets = $this->accountingDashboardBuckets($period);

        return [
            'labels' => $buckets->map(fn (array $bucket) => $bucket['label'])->all(),
            'revenue' => $buckets->map(fn (array $bucket) => $this->accountingDashboardRevenueForPeriod($site, $bucket['start'], $bucket['end']))->all(),
            'sales' => $buckets->map(fn (array $bucket) => $this->accountingDashboardSalesForPeriod($site, $bucket['start'], $bucket['end']))->all(),
            'expenses' => $buckets->map(fn (array $bucket) => $this->accountingDashboardExpensesForPeriod($site, $bucket['start'], $bucket['end']))->all(),
            'receivables' => $buckets->map(fn (array $bucket) => $this->accountingDashboardReceivablesForPeriod($site, $bucket['start'], $bucket['end']))->all(),
            'debts' => $buckets->map(fn (array $bucket) => $this->accountingDashboardDebtsForPeriod($site, $bucket['start'], $bucket['end']))->all(),
            'treasuryInflows' => $buckets->map(fn (array $bucket) => $this->accountingDashboardTreasuryForPeriod($site, $bucket['start'], $bucket['end'], AccountingTreasuryMovement::DIRECTION_INFLOW))->all(),
            'treasuryOutflows' => $buckets->map(fn (array $bucket) => $this->accountingDashboardTreasuryForPeriod($site, $bucket['start'], $bucket['end'], AccountingTreasuryMovement::DIRECTION_OUTFLOW))->all(),
        ];
    }

    private function accountingDashboardBuckets(string $period)
    {
        return match ($period) {
            'week' => collect(range(7, 0))->map(function (int $weeksAgo): array {
                $start = \Carbon\CarbonImmutable::now()->subWeeks($weeksAgo)->startOfWeek();

                return [
                    'start' => $start,
                    'end' => $start->endOfWeek(),
                    'label' => $start->translatedFormat('d M'),
                ];
            }),
            'year' => collect(range(4, 0))->map(function (int $yearsAgo): array {
                $start = \Carbon\CarbonImmutable::now()->subYears($yearsAgo)->startOfYear();

                return [
                    'start' => $start,
                    'end' => $start->endOfYear(),
                    'label' => $start->format('Y'),
                ];
            }),
            default => collect(range(5, 0))->map(function (int $monthsAgo): array {
                $start = \Carbon\CarbonImmutable::now()->subMonths($monthsAgo)->startOfMonth();

                return [
                    'start' => $start,
                    'end' => $start->endOfMonth(),
                    'label' => $start->translatedFormat('M Y'),
                ];
            }),
        };
    }

    private function accountingDashboardRevenueForPeriod(CompanySite $site, $start, $end): float
    {
        return $this->accountingDashboardNetSalesAmount(
            AccountingSalesInvoice::query()
                ->where('company_site_id', $site->id)
                ->whereNotIn('status', [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED])
                ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
        );
    }

    private function accountingDashboardSalesForPeriod(CompanySite $site, $start, $end): float
    {
        return round((float) AccountingSalesInvoice::query()
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED])
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_ttc'), 2);
    }

    private function accountingDashboardNetSalesAmount($query): float
    {
        return round((float) $query
            ->get(['total_ttc', 'credit_total'])
            ->sum(fn (AccountingSalesInvoice $invoice): float => max(0, (float) $invoice->total_ttc - (float) $invoice->credit_total)), 2);
    }

    private function accountingDashboardExpensesForPeriod(CompanySite $site, $start, $end): float
    {
        $purchases = (float) AccountingPurchase::query()
            ->where('company_site_id', $site->id)
            ->whereNotIn('status', [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED])
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_ttc');
        $expenses = (float) AccountingExpense::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingExpense::STATUS_VALIDATED)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        return round($purchases + $expenses, 2);
    }

    private function accountingDashboardReceivablesForPeriod(CompanySite $site, $start, $end): float
    {
        $invoices = (float) AccountingSalesInvoice::query()
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_CANCELLED])
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->sum('balance_due');
        $manual = AccountingDebtor::query()
            ->where('company_site_id', $site->id)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get(['initial_amount', 'received_amount'])
            ->sum(fn (AccountingDebtor $debtor) => $debtor->balanceReceivable());

        return round($invoices + $manual, 2);
    }

    private function accountingDashboardDebtsForPeriod(CompanySite $site, $start, $end): float
    {
        $purchases = (float) AccountingPurchase::query()
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingPurchase::STATUS_CANCELLED])
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->sum('balance_due');
        $manual = AccountingCreditor::query()
            ->where('company_site_id', $site->id)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get(['initial_amount', 'paid_amount'])
            ->sum(fn (AccountingCreditor $creditor) => $creditor->balanceDue());

        return round($purchases + $manual, 2);
    }

    private function accountingDashboardTreasuryForPeriod(CompanySite $site, $start, $end, string $direction): float
    {
        return round((float) AccountingTreasuryMovement::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
            ->where('direction', $direction)
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount'), 2);
    }

    private function accountingDashboardScheduleItems(CompanySite $site, string $currency): array
    {
        $invoiceItems = AccountingSalesInvoice::query()
            ->with('client')
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_CANCELLED])
            ->get()
            ->map(fn (AccountingSalesInvoice $invoice) => [
                'label' => __('main.customer_receivable'),
                'subject' => $invoice->client?->display_name ?? $invoice->client?->name ?? $invoice->reference,
                'amount' => $this->dashboardMoney((float) $invoice->balance_due, $invoice->currency ?: $currency),
                'date' => optional($invoice->due_date ?: $invoice->invoice_date)->translatedFormat('d M') ?: '-',
                'tone' => 'green',
                'sort_date' => optional($invoice->due_date ?: $invoice->invoice_date)->format('Y-m-d') ?: '9999-12-31',
            ]);
        $manualReceivableItems = AccountingDebtor::query()
            ->where('company_site_id', $site->id)
            ->get()
            ->filter(fn (AccountingDebtor $debtor) => $debtor->balanceReceivable() > 0)
            ->map(fn (AccountingDebtor $debtor) => [
                'label' => __('main.customer_receivable'),
                'subject' => $debtor->name,
                'amount' => $this->dashboardMoney($debtor->balanceReceivable(), $debtor->currency ?: $currency),
                'date' => optional($debtor->due_date)->translatedFormat('d M') ?: '-',
                'tone' => 'blue',
                'sort_date' => optional($debtor->due_date)->format('Y-m-d') ?: '9999-12-31',
            ]);
        $purchaseItems = AccountingPurchase::query()
            ->with('supplier')
            ->where('company_site_id', $site->id)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingPurchase::STATUS_CANCELLED])
            ->get()
            ->map(fn (AccountingPurchase $purchase) => [
                'label' => __('main.supplier_debt'),
                'subject' => $purchase->supplier?->display_name ?? $purchase->supplier?->name ?? $purchase->reference,
                'amount' => $this->dashboardMoney((float) $purchase->balance_due, $purchase->currency ?: $currency),
                'date' => optional($purchase->due_date ?: $purchase->purchase_date)->translatedFormat('d M') ?: '-',
                'tone' => 'amber',
                'sort_date' => optional($purchase->due_date ?: $purchase->purchase_date)->format('Y-m-d') ?: '9999-12-31',
            ]);
        $manualDebtItems = AccountingCreditor::query()
            ->where('company_site_id', $site->id)
            ->get()
            ->filter(fn (AccountingCreditor $creditor) => $creditor->balanceDue() > 0)
            ->map(fn (AccountingCreditor $creditor) => [
                'label' => __('main.supplier_debt'),
                'subject' => $creditor->name,
                'amount' => $this->dashboardMoney($creditor->balanceDue(), $creditor->currency ?: $currency),
                'date' => optional($creditor->due_date)->translatedFormat('d M') ?: '-',
                'tone' => 'rose',
                'sort_date' => optional($creditor->due_date)->format('Y-m-d') ?: '9999-12-31',
            ]);

        return collect()
            ->concat($invoiceItems)
            ->concat($manualReceivableItems)
            ->concat($purchaseItems)
            ->concat($manualDebtItems)
            ->sortBy('sort_date')
            ->take(4)
            ->map(fn (array $item) => collect($item)->except('sort_date')->all())
            ->values()
            ->all();
    }

    private function dashboardMoney(float $amount, string $currency): string
    {
        $absolute = abs($amount);

        if ($absolute >= 1000000000) {
            return number_format($amount / 1000000000, 1, ',', ' ').'Md '.$currency;
        }

        if ($absolute >= 1000000) {
            return number_format($amount / 1000000, 1, ',', ' ').'M '.$currency;
        }

        if ($absolute >= 1000) {
            return number_format($amount / 1000, 1, ',', ' ').'K '.$currency;
        }

        return number_format($amount, 2, ',', ' ').' '.$currency;
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

    private function ensureDefaultAccountingTaxRecords(CompanySite $site, Company $company): void
    {
        if (! in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true)) {
            return;
        }

        $defaultTax = AccountingTax::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'is_system_default' => true,
            ],
            [
                'created_by' => $site->responsible_id,
                'code' => 'TVA',
                'name' => __('main.vat'),
                'kind' => AccountingTax::KIND_VAT,
                'calculation_type' => AccountingTax::CALCULATION_PERCENTAGE,
                'value' => $this->companyCountryVatRate($company),
                'nature' => AccountingTax::NATURE_COLLECTED,
                'applies_to' => AccountingTax::APPLIES_BOTH,
                'is_default' => true,
                'status' => AccountingTax::STATUS_ACTIVE,
            ]
        );

        if (! AccountingTax::query()->where('company_site_id', $site->id)->where('is_default', true)->exists()) {
            $defaultTax->forceFill([
                'is_default' => true,
                'status' => AccountingTax::STATUS_ACTIVE,
            ])->save();
        }

        AccountingTax::query()->firstOrCreate(
            [
                'company_site_id' => $site->id,
                'kind' => AccountingTax::KIND_EXEMPTION,
            ],
            [
                'created_by' => $site->responsible_id,
                'code' => 'EXONERE',
                'name' => __('main.tax_exemption'),
                'calculation_type' => AccountingTax::CALCULATION_PERCENTAGE,
                'value' => 0,
                'nature' => AccountingTax::NATURE_COLLECTED,
                'applies_to' => AccountingTax::APPLIES_BOTH,
                'is_default' => false,
                'status' => AccountingTax::STATUS_ACTIVE,
            ]
        );
    }

    private function defaultAccountingTaxRate(CompanySite $site, Company $company, string $appliesTo): float
    {
        $this->ensureDefaultAccountingTaxRecords($site, $company);

        $tax = AccountingTax::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->where('status', AccountingTax::STATUS_ACTIVE)
            ->where('calculation_type', AccountingTax::CALCULATION_PERCENTAGE)
            ->whereIn('applies_to', [$appliesTo, AccountingTax::APPLIES_BOTH])
            ->first();

        return $tax ? (float) $tax->value : $this->companyCountryVatRate($company);
    }

    private function ensureDefaultAccountingExpenseCategories(CompanySite $site): void
    {
        if (! in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true)) {
            return;
        }

        $defaults = [
            'loyer' => __('main.expense_category_rent'),
            'transport' => __('main.expense_category_transport'),
            'carburant' => __('main.expense_category_fuel'),
            'communication' => __('main.expense_category_communication'),
            'internet' => __('main.expense_category_internet'),
            'electricite' => __('main.expense_category_electricity'),
            'eau' => __('main.expense_category_water'),
            'frais-bancaires' => __('main.expense_category_bank_fees'),
            'frais-administratifs' => __('main.expense_category_admin_fees'),
            'entretien' => __('main.expense_category_maintenance'),
            'mission' => __('main.expense_category_mission'),
            'restauration' => __('main.expense_category_meals'),
            'avances-salaires' => __('main.expense_category_salary_advances'),
            'taxes' => __('main.expense_category_taxes'),
            'autres-charges' => __('main.expense_category_other'),
        ];

        foreach ($defaults as $slug => $name) {
            AccountingExpenseCategory::query()->firstOrCreate(
                ['company_site_id' => $site->id, 'slug' => $slug],
                [
                    'name' => $name,
                    'description' => null,
                    'is_system_default' => true,
                    'status' => AccountingExpenseCategory::STATUS_ACTIVE,
                ]
            );
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
                'relations' => ['items.category', 'items.subcategory'],
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

    private function accountingTaxRules(CompanySite $site, ?AccountingTax $tax = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('accounting_taxes', 'code')
                    ->where('company_site_id', $site->id)
                    ->ignore($tax?->id),
            ],
            'kind' => ['required', Rule::in(AccountingTax::kinds())],
            'calculation_type' => ['required', Rule::in(AccountingTax::calculationTypes())],
            'value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when(request('calculation_type') === AccountingTax::CALCULATION_PERCENTAGE, ['max:100']),
            ],
            'nature' => ['required', Rule::in(AccountingTax::natures())],
            'applies_to' => ['required', Rule::in(AccountingTax::applications())],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([AccountingTax::STATUS_ACTIVE, AccountingTax::STATUS_INACTIVE])],
        ];
    }

    private function accountingTaxPayload(array $validated, User&Authenticatable $user, bool $withCreator = true, ?AccountingTax $tax = null): array
    {
        $isDefault = (bool) ($validated['is_default'] ?? false);
        $payload = [
            'name' => $validated['name'],
            'code' => filled($validated['code'] ?? null) ? Str::upper(trim($validated['code'])) : ($tax?->code ?? ''),
            'kind' => $validated['kind'],
            'calculation_type' => $validated['calculation_type'],
            'value' => $validated['value'],
            'nature' => $validated['nature'],
            'applies_to' => $validated['applies_to'],
            'description' => $validated['description'] ?? null,
            'is_default' => $isDefault,
            'status' => $isDefault ? AccountingTax::STATUS_ACTIVE : $validated['status'],
        ];

        if ($withCreator) {
            $payload['created_by'] = $user->id;
        }

        return $payload;
    }

    private function ensureAccountingTaxCanBeDefault(array $validated): void
    {
        if (! (bool) ($validated['is_default'] ?? false)) {
            return;
        }

        if ($validated['calculation_type'] !== AccountingTax::CALCULATION_PERCENTAGE) {
            throw ValidationException::withMessages([
                'calculation_type' => __('main.default_tax_must_be_percentage'),
            ]);
        }

        if ($validated['applies_to'] !== AccountingTax::APPLIES_BOTH) {
            throw ValidationException::withMessages([
                'applies_to' => __('main.default_tax_must_apply_to_both'),
            ]);
        }
    }

    private function accountingTaxKindLabels(): array
    {
        return [
            AccountingTax::KIND_VAT => __('main.tax_kind_vat'),
            AccountingTax::KIND_WITHHOLDING => __('main.tax_kind_withholding'),
            AccountingTax::KIND_STAMP_DUTY => __('main.tax_kind_stamp_duty'),
            AccountingTax::KIND_SPECIAL => __('main.tax_kind_special'),
            AccountingTax::KIND_EXEMPTION => __('main.tax_kind_exemption'),
        ];
    }

    private function accountingTaxCalculationTypeLabels(): array
    {
        return [
            AccountingTax::CALCULATION_PERCENTAGE => __('main.tax_calculation_percentage'),
            AccountingTax::CALCULATION_FIXED => __('main.tax_calculation_fixed'),
        ];
    }

    private function accountingTaxNatureLabels(): array
    {
        return [
            AccountingTax::NATURE_COLLECTED => __('main.tax_nature_collected'),
            AccountingTax::NATURE_DEDUCTIBLE => __('main.tax_nature_deductible'),
            AccountingTax::NATURE_WITHHELD => __('main.tax_nature_withheld'),
        ];
    }

    private function accountingTaxApplicationLabels(): array
    {
        return [
            AccountingTax::APPLIES_SALES => __('main.tax_applies_sales'),
            AccountingTax::APPLIES_PURCHASES => __('main.tax_applies_purchases'),
            AccountingTax::APPLIES_BOTH => __('main.tax_applies_both'),
        ];
    }

    private function accountingTaxStatusLabels(): array
    {
        return [
            AccountingTax::STATUS_ACTIVE => __('main.active'),
            AccountingTax::STATUS_INACTIVE => __('main.inactive'),
        ];
    }

    private function accountingTaxIsUsed(CompanySite $site, AccountingTax $tax): bool
    {
        if ($tax->calculation_type !== AccountingTax::CALCULATION_PERCENTAGE) {
            return false;
        }

        $documents = [
            AccountingProformaInvoice::class,
            AccountingCustomerOrder::class,
            AccountingSalesInvoice::class,
            AccountingPurchaseOrder::class,
            AccountingPurchase::class,
            AccountingCreditNote::class,
        ];

        return collect($documents)->contains(fn (string $document): bool => $document::query()
            ->where('company_site_id', $site->id)
            ->where('tax_rate', (float) $tax->value)
            ->exists());
    }

    private function syncAccountingTreasuryMovements(CompanySite $site): void
    {
        AccountingSalesInvoicePayment::query()
            ->with(['salesInvoice.client'])
            ->whereHas('salesInvoice', fn ($query) => $query->where('company_site_id', $site->id))
            ->get()
            ->each(function (AccountingSalesInvoicePayment $payment) use ($site): void {
                $invoice = $payment->salesInvoice;

                $this->syncAccountingTreasuryMovement($site, AccountingSalesInvoicePayment::class, $payment->id, [
                    'payment_method_id' => $payment->payment_method_id,
                    'created_by' => $payment->received_by,
                    'movement_type' => AccountingTreasuryMovement::TYPE_SALES_PAYMENT,
                    'source_reference' => $invoice?->reference ?: $payment->reference,
                    'direction' => AccountingTreasuryMovement::DIRECTION_INFLOW,
                    'label' => trim(implode(' - ', array_filter([$invoice?->reference, $invoice?->client?->name]))),
                    'description' => $payment->notes,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'movement_date' => $payment->payment_date,
                    'status' => AccountingTreasuryMovement::STATUS_VALIDATED,
                ]);
            });

        AccountingDebtorPayment::query()
            ->with('debtor')
            ->whereHas('debtor', fn ($query) => $query->where('company_site_id', $site->id))
            ->get()
            ->each(function (AccountingDebtorPayment $payment) use ($site): void {
                $this->syncAccountingTreasuryMovement($site, AccountingDebtorPayment::class, $payment->id, [
                    'payment_method_id' => $payment->payment_method_id,
                    'created_by' => $payment->received_by,
                    'movement_type' => AccountingTreasuryMovement::TYPE_RECEIVABLE_PAYMENT,
                    'source_reference' => $payment->debtor?->reference ?: $payment->reference,
                    'direction' => AccountingTreasuryMovement::DIRECTION_INFLOW,
                    'label' => trim(implode(' - ', array_filter([$payment->debtor?->reference, $payment->debtor?->name]))),
                    'description' => $payment->notes,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'movement_date' => $payment->payment_date,
                    'status' => AccountingTreasuryMovement::STATUS_VALIDATED,
                ]);
            });

        AccountingOtherIncome::query()
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingOtherIncome::STATUS_VALIDATED, AccountingOtherIncome::STATUS_CANCELLED])
            ->get()
            ->each(function (AccountingOtherIncome $income) use ($site): void {
                $this->syncAccountingTreasuryMovement($site, AccountingOtherIncome::class, $income->id, [
                    'payment_method_id' => $income->payment_method_id,
                    'created_by' => $income->created_by,
                    'movement_type' => AccountingTreasuryMovement::TYPE_OTHER_INCOME,
                    'source_reference' => $income->reference,
                    'direction' => AccountingTreasuryMovement::DIRECTION_INFLOW,
                    'label' => trim(implode(' - ', array_filter([$income->reference, $income->label]))),
                    'description' => $income->description,
                    'amount' => $income->amount,
                    'currency' => $income->currency,
                    'movement_date' => $income->income_date,
                    'status' => $income->status === AccountingOtherIncome::STATUS_VALIDATED
                        ? AccountingTreasuryMovement::STATUS_VALIDATED
                        : AccountingTreasuryMovement::STATUS_CANCELLED,
                ]);
            });

        AccountingPurchasePayment::query()
            ->with(['purchase.supplier'])
            ->whereHas('purchase', fn ($query) => $query->where('company_site_id', $site->id))
            ->get()
            ->each(function (AccountingPurchasePayment $payment) use ($site): void {
                $purchase = $payment->purchase;

                $this->syncAccountingTreasuryMovement($site, AccountingPurchasePayment::class, $payment->id, [
                    'payment_method_id' => $payment->payment_method_id,
                    'created_by' => $payment->paid_by,
                    'movement_type' => AccountingTreasuryMovement::TYPE_PURCHASE_PAYMENT,
                    'source_reference' => $purchase?->reference ?: $payment->reference,
                    'direction' => AccountingTreasuryMovement::DIRECTION_OUTFLOW,
                    'label' => trim(implode(' - ', array_filter([$purchase?->reference, $purchase?->supplier?->name]))),
                    'description' => $payment->notes,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'movement_date' => $payment->payment_date,
                    'status' => AccountingTreasuryMovement::STATUS_VALIDATED,
                ]);
            });

        AccountingCreditorPayment::query()
            ->with('creditor')
            ->whereHas('creditor', fn ($query) => $query->where('company_site_id', $site->id))
            ->get()
            ->each(function (AccountingCreditorPayment $payment) use ($site): void {
                $this->syncAccountingTreasuryMovement($site, AccountingCreditorPayment::class, $payment->id, [
                    'payment_method_id' => $payment->payment_method_id,
                    'created_by' => $payment->paid_by,
                    'movement_type' => AccountingTreasuryMovement::TYPE_DEBT_PAYMENT,
                    'source_reference' => $payment->creditor?->reference ?: $payment->reference,
                    'direction' => AccountingTreasuryMovement::DIRECTION_OUTFLOW,
                    'label' => trim(implode(' - ', array_filter([$payment->creditor?->reference, $payment->creditor?->name]))),
                    'description' => $payment->notes,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'movement_date' => $payment->payment_date,
                    'status' => AccountingTreasuryMovement::STATUS_VALIDATED,
                ]);
            });

        AccountingExpense::query()
            ->where('company_site_id', $site->id)
            ->whereIn('status', [AccountingExpense::STATUS_VALIDATED, AccountingExpense::STATUS_CANCELLED])
            ->get()
            ->each(function (AccountingExpense $expense) use ($site): void {
                $this->syncAccountingTreasuryMovement($site, AccountingExpense::class, $expense->id, [
                    'payment_method_id' => $expense->payment_method_id,
                    'created_by' => $expense->created_by,
                    'movement_type' => AccountingTreasuryMovement::TYPE_EXPENSE,
                    'source_reference' => $expense->reference,
                    'direction' => AccountingTreasuryMovement::DIRECTION_OUTFLOW,
                    'label' => trim(implode(' - ', array_filter([$expense->reference, $expense->label]))),
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'currency' => $expense->currency,
                    'movement_date' => $expense->expense_date,
                    'status' => $expense->status === AccountingExpense::STATUS_VALIDATED
                        ? AccountingTreasuryMovement::STATUS_VALIDATED
                        : AccountingTreasuryMovement::STATUS_CANCELLED,
                ]);
            });
    }

    private function syncAccountingTreasuryMovement(CompanySite $site, string $sourceType, int $sourceId, array $payload): void
    {
        $query = AccountingTreasuryMovement::query()
            ->where('company_site_id', $site->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId);

        if ($payload['status'] === AccountingTreasuryMovement::STATUS_CANCELLED && ! $query->exists()) {
            return;
        }

        AccountingTreasuryMovement::query()->updateOrCreate([
            'company_site_id' => $site->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ], $payload);
    }

    private function bankReconciliationRedirect(Company $company, CompanySite $site, AccountingBankReconciliation $reconciliation): RedirectResponse
    {
        return redirect()->route('main.accounting.bank-reconciliations', [
            $company,
            $site,
            'reconciliation' => $reconciliation->id,
        ]);
    }

    private function ensureEditableBankReconciliation(CompanySite $site, AccountingBankReconciliation $reconciliation, ?AccountingBankStatementLine $line = null): void
    {
        if ($reconciliation->company_site_id !== $site->id || ($line && $line->bank_reconciliation_id !== $reconciliation->id)) {
            abort(404);
        }

        if ($reconciliation->status === AccountingBankReconciliation::STATUS_CLOSED) {
            throw ValidationException::withMessages([
                'reconciliation' => __('main.bank_reconciliation_locked'),
            ]);
        }
    }

    private function refreshAccountingBankReconciliationTotals(AccountingBankReconciliation $reconciliation): void
    {
        $erpClosingBalance = AccountingTreasuryMovement::query()
            ->where('company_site_id', $reconciliation->company_site_id)
            ->where('payment_method_id', $reconciliation->payment_method_id)
            ->where('currency', $reconciliation->currency)
            ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
            ->whereDate('movement_date', '<=', $reconciliation->period_end)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = ? THEN amount ELSE -amount END), 0) as balance", [AccountingTreasuryMovement::DIRECTION_INFLOW])
            ->value('balance');
        $difference = round((float) $reconciliation->statement_closing_balance - (float) $erpClosingBalance, 2);
        $status = $reconciliation->status;

        if ($status !== AccountingBankReconciliation::STATUS_CLOSED) {
            $hasLines = $reconciliation->lines()->exists();
            $hasPendingLines = $reconciliation->lines()->where('status', AccountingBankStatementLine::STATUS_UNMATCHED)->exists();
            $status = $hasLines && ! $hasPendingLines && abs($difference) <= 0.009
                ? AccountingBankReconciliation::STATUS_RECONCILED
                : AccountingBankReconciliation::STATUS_IN_PROGRESS;
        }

        $reconciliation->update([
            'erp_closing_balance' => round((float) $erpClosingBalance, 2),
            'difference' => $difference,
            'status' => $status,
        ]);
    }

    private function accountingBankReconciliationStatusLabels(): array
    {
        return [
            AccountingBankReconciliation::STATUS_IN_PROGRESS => __('main.bank_status_in_progress'),
            AccountingBankReconciliation::STATUS_RECONCILED => __('main.bank_status_reconciled'),
            AccountingBankReconciliation::STATUS_CLOSED => __('main.bank_status_closed'),
        ];
    }

    private function accountingBankStatementLineStatusLabels(): array
    {
        return [
            AccountingBankStatementLine::STATUS_UNMATCHED => __('main.bank_line_status_unmatched'),
            AccountingBankStatementLine::STATUS_MATCHED => __('main.bank_line_status_matched'),
            AccountingBankStatementLine::STATUS_IGNORED => __('main.bank_line_status_ignored'),
        ];
    }

    private function readAccountingBankStatementCsv(UploadedFile $file, AccountingBankReconciliation $reconciliation): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle) ?: '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, null, $delimiter) ?: [];
        $headers = array_map(fn ($header): string => $this->normaliseBankCsvHeader((string) $header), $headers);
        $dateIndex = $this->bankCsvColumnIndex($headers, ['date', 'date operation', 'date transaction']);
        $descriptionIndex = $this->bankCsvColumnIndex($headers, ['libelle', 'description', 'motif']);
        $referenceIndex = $this->bankCsvColumnIndex($headers, ['reference', 'ref', 'numero']);
        $debitIndex = $this->bankCsvColumnIndex($headers, ['debit', 'sortie']);
        $creditIndex = $this->bankCsvColumnIndex($headers, ['credit', 'entree']);
        $amountIndex = $this->bankCsvColumnIndex($headers, ['montant', 'amount']);
        $directionIndex = $this->bankCsvColumnIndex($headers, ['direction', 'sens', 'type']);
        $rows = [];

        if ($dateIndex === null || $descriptionIndex === null || (($debitIndex === null || $creditIndex === null) && $amountIndex === null)) {
            fclose($handle);

            throw ValidationException::withMessages([
                'statement_file' => __('main.bank_statement_import_columns'),
            ]);
        }

        while (($values = fgetcsv($handle, null, $delimiter)) !== false) {
            $date = $this->bankCsvDate($values[$dateIndex] ?? '');
            $description = trim((string) ($values[$descriptionIndex] ?? ''));
            $debit = $debitIndex === null ? 0.0 : $this->bankCsvAmount($values[$debitIndex] ?? '');
            $credit = $creditIndex === null ? 0.0 : $this->bankCsvAmount($values[$creditIndex] ?? '');
            $amount = $amountIndex === null ? max($debit, $credit) : $this->bankCsvAmount($values[$amountIndex] ?? '');
            $direction = $credit > 0
                ? AccountingBankStatementLine::DIRECTION_INFLOW
                : AccountingBankStatementLine::DIRECTION_OUTFLOW;

            if ($amountIndex !== null && $directionIndex !== null) {
                $rawDirection = $this->normaliseBankCsvHeader((string) ($values[$directionIndex] ?? ''));
                $direction = in_array($rawDirection, ['credit', 'entree', 'inflow'], true)
                    ? AccountingBankStatementLine::DIRECTION_INFLOW
                    : AccountingBankStatementLine::DIRECTION_OUTFLOW;
            }

            if (! $date || $description === '' || $amount <= 0 || $date->lt($reconciliation->period_start) || $date->gt($reconciliation->period_end)) {
                continue;
            }

            $rows[] = [
                'transaction_date' => $date->format('Y-m-d'),
                'bank_reference' => $referenceIndex === null ? null : trim((string) ($values[$referenceIndex] ?? '')),
                'description' => $description,
                'direction' => $direction,
                'amount' => $amount,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function normaliseBankCsvHeader(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', Str::ascii(Str::lower($value)))) ?: '');
    }

    private function bankCsvColumnIndex(array $headers, array $possibilities): ?int
    {
        foreach ($possibilities as $possibility) {
            $index = array_search($possibility, $headers, true);

            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    private function bankCsvAmount(string $value): float
    {
        $normalised = str_replace(["\xc2\xa0", ' '], '', trim($value));

        if (str_contains($normalised, ',') && str_contains($normalised, '.')) {
            $normalised = str_replace('.', '', $normalised);
        }

        $normalised = str_replace(',', '.', $normalised);

        return is_numeric($normalised) ? abs((float) $normalised) : 0.0;
    }

    private function bankCsvDate(string $value): ?Carbon
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function accountingTreasuryForecast(CompanySite $site, string $currency): array
    {
        $receivables = (float) AccountingSalesInvoice::query()
            ->where('company_site_id', $site->id)
            ->where('currency', $currency)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED, AccountingSalesInvoice::STATUS_CREDITED])
            ->sum('balance_due')
            + (float) AccountingDebtor::query()
                ->where('company_site_id', $site->id)
                ->where('currency', $currency)
                ->where('status', '!=', AccountingDebtor::STATUS_INACTIVE)
                ->selectRaw('COALESCE(SUM(initial_amount - received_amount), 0) as remaining')
                ->value('remaining');

        $debts = (float) AccountingPurchase::query()
            ->where('company_site_id', $site->id)
            ->where('currency', $currency)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [AccountingPurchase::STATUS_DRAFT, AccountingPurchase::STATUS_CANCELLED])
            ->sum('balance_due')
            + (float) AccountingCreditor::query()
                ->where('company_site_id', $site->id)
                ->where('currency', $currency)
                ->where('status', '!=', AccountingCreditor::STATUS_INACTIVE)
                ->selectRaw('COALESCE(SUM(initial_amount - paid_amount), 0) as remaining')
                ->value('remaining');

        return [
            'receivables' => round($receivables, 2),
            'debts' => round($debts, 2),
        ];
    }

    private function accountingTreasuryPeriodData(CompanySite $site, string $currency, string $period): array
    {
        $buckets = $this->accountingDashboardBuckets($period);

        return [
            'labels' => $buckets->map(fn (array $bucket) => $bucket['label'])->all(),
            'inflows' => $buckets->map(fn (array $bucket) => round((float) AccountingTreasuryMovement::query()
                ->where('company_site_id', $site->id)
                ->where('currency', $currency)
                ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
                ->where('direction', AccountingTreasuryMovement::DIRECTION_INFLOW)
                ->whereBetween('movement_date', [$bucket['start']->toDateString(), $bucket['end']->toDateString()])
                ->sum('amount'), 2))->all(),
            'outflows' => $buckets->map(fn (array $bucket) => round((float) AccountingTreasuryMovement::query()
                ->where('company_site_id', $site->id)
                ->where('currency', $currency)
                ->where('status', AccountingTreasuryMovement::STATUS_VALIDATED)
                ->where('direction', AccountingTreasuryMovement::DIRECTION_OUTFLOW)
                ->whereBetween('movement_date', [$bucket['start']->toDateString(), $bucket['end']->toDateString()])
                ->sum('amount'), 2))->all(),
        ];
    }

    private function accountingTreasuryMovementTypeLabels(): array
    {
        return [
            AccountingTreasuryMovement::TYPE_SALES_PAYMENT => __('main.treasury_type_sales_payment'),
            AccountingTreasuryMovement::TYPE_OTHER_INCOME => __('main.treasury_type_other_income'),
            AccountingTreasuryMovement::TYPE_RECEIVABLE_PAYMENT => __('main.treasury_type_receivable_payment'),
            AccountingTreasuryMovement::TYPE_PURCHASE_PAYMENT => __('main.treasury_type_purchase_payment'),
            AccountingTreasuryMovement::TYPE_EXPENSE => __('main.treasury_type_expense'),
            AccountingTreasuryMovement::TYPE_DEBT_PAYMENT => __('main.treasury_type_debt_payment'),
            AccountingTreasuryMovement::TYPE_BANK_ADJUSTMENT => __('main.treasury_type_bank_adjustment'),
        ];
    }

    private function accountingTreasuryDirectionLabels(): array
    {
        return [
            AccountingTreasuryMovement::DIRECTION_INFLOW => __('main.treasury_direction_inflow'),
            AccountingTreasuryMovement::DIRECTION_OUTFLOW => __('main.treasury_direction_outflow'),
        ];
    }

    private function accountingTreasuryStatusLabels(): array
    {
        return [
            AccountingTreasuryMovement::STATUS_VALIDATED => __('main.treasury_status_validated'),
            AccountingTreasuryMovement::STATUS_CANCELLED => __('main.treasury_status_cancelled'),
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

    private function expenseRules(CompanySite $site): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'expense_category_id' => [
                'required',
                'integer',
                Rule::exists('accounting_expense_categories', 'id')
                    ->where('company_site_id', $site->id)
                    ->where('status', AccountingExpenseCategory::STATUS_ACTIVE),
            ],
            'label' => ['required', 'string', 'max:255'],
            'beneficiary' => ['nullable', 'string', 'max:255'],
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
            'status' => ['required', Rule::in([AccountingExpense::STATUS_DRAFT, AccountingExpense::STATUS_VALIDATED])],
        ];
    }

    private function expensePayload(array $validated, User&Authenticatable $user, bool $withCreator = true): array
    {
        $payload = [
            'expense_date' => $validated['expense_date'],
            'expense_category_id' => $validated['expense_category_id'],
            'label' => $validated['label'],
            'beneficiary' => $validated['beneficiary'] ?? null,
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

    private function changeAccountingExpenseStatus(Company $company, CompanySite $site, AccountingExpense $expense, string $status, string $message): RedirectResponse
    {
        $access = $this->accountingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $expense->company_site_id !== (int) $site->id || ! $this->sitePermissionFlags($user, $site)['can_update']) {
            return redirect()->route('main.accounting.expenses', [$company, $site]);
        }

        if ($status === AccountingExpense::STATUS_VALIDATED && ! $expense->isDraft()) {
            return redirect()->route('main.accounting.expenses', [$company, $site]);
        }

        if ($status === AccountingExpense::STATUS_CANCELLED && ! $expense->isValidated()) {
            return redirect()->route('main.accounting.expenses', [$company, $site]);
        }

        $expense->update(['status' => $status]);

        return redirect()
            ->route('main.accounting.expenses', [$company, $site])
            ->with('success', $message);
    }

    private function expenseStatusLabels(): array
    {
        return [
            AccountingExpense::STATUS_DRAFT => __('main.expense_status_draft'),
            AccountingExpense::STATUS_VALIDATED => __('main.expense_status_validated'),
            AccountingExpense::STATUS_CANCELLED => __('main.expense_status_cancelled'),
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

    private function accountingTaskQueryForUser(CompanySite $site, User&Authenticatable $user)
    {
        return AccountingTask::query()
            ->where('company_site_id', $site->id)
            ->when($user->isUser(), fn ($query) => $query->where(function ($accessQuery) use ($user): void {
                $accessQuery->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            }));
    }

    private function ensureAccountingTaskAccessible(CompanySite $site, AccountingTask $task, User&Authenticatable $user): void
    {
        if ((int) $task->company_site_id !== (int) $site->id) {
            abort(404);
        }

        if ($user->isUser() && (int) $task->assigned_to !== (int) $user->id && (int) $task->created_by !== (int) $user->id) {
            abort(404);
        }
    }

    private function accountingTaskRules(Company $company, CompanySite $site): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'type' => ['required', Rule::in(AccountingTask::types())],
            'priority' => ['required', Rule::in(AccountingTask::priorities())],
            'status' => ['required', Rule::in(AccountingTask::statuses())],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('subscription_id', $company->subscription_id)->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER]))],
            'client_id' => ['nullable', Rule::exists('accounting_clients', 'id')->where(fn ($query) => $query->where('company_site_id', $site->id))],
            'supplier_id' => ['nullable', Rule::exists('accounting_suppliers', 'id')->where(fn ($query) => $query->where('company_site_id', $site->id))],
            'source_key' => ['nullable', 'string', 'max:80'],
            'completion_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function accountingTaskPayload(array $validated, CompanySite $site, User&Authenticatable $user, ?AccountingTask $task = null): array
    {
        $document = $task?->is_automatic
            ? [
                'source_type' => $task->source_type,
                'source_id' => $task->source_id,
                'source_reference' => $task->source_reference,
                'source_label' => $task->source_label,
            ]
            : $this->resolveAccountingTaskDocument($site, $validated['source_key'] ?? null);
        $status = $validated['status'];
        $payload = [
            'assigned_to' => $user->isUser() ? $user->id : ($validated['assigned_to'] ?? null),
            'created_by' => $task?->created_by ?: $user->id,
            'client_id' => $validated['client_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'status' => $status,
            'due_date' => $validated['due_date'] ?? null,
            'completion_notes' => $validated['completion_notes'] ?? null,
        ] + $document;

        if ($status === AccountingTask::STATUS_COMPLETED) {
            $payload['completed_by'] = $user->id;
            $payload['completed_at'] = $task?->completed_at ?: now();
        } else {
            $payload['completed_by'] = null;
            $payload['completed_at'] = null;
        }

        return $payload;
    }

    private function resolveAccountingTaskDocument(CompanySite $site, ?string $sourceKey): array
    {
        $empty = ['source_type' => null, 'source_id' => null, 'source_reference' => null, 'source_label' => null];

        if (! filled($sourceKey) || ! str_contains($sourceKey, ':')) {
            return $empty;
        }

        [$type, $id] = explode(':', $sourceKey, 2);

        if (! ctype_digit($id)) {
            return $empty;
        }

        if ($type === AccountingTask::SOURCE_SALES_INVOICE) {
            $invoice = AccountingSalesInvoice::query()->where('company_site_id', $site->id)->findOrFail((int) $id);

            return [
                'source_type' => $type,
                'source_id' => $invoice->id,
                'source_reference' => $invoice->reference,
                'source_label' => __('main.sales_invoice'),
            ];
        }

        if ($type === AccountingTask::SOURCE_PAYMENT_REMINDER) {
            $reminder = AccountingPaymentReminder::query()->where('company_site_id', $site->id)->findOrFail((int) $id);

            return [
                'source_type' => $type,
                'source_id' => $reminder->id,
                'source_reference' => $reminder->reference,
                'source_label' => __('main.payment_reminder_letter'),
            ];
        }

        return $empty;
    }

    private function accountingTaskDocumentOptions(CompanySite $site): array
    {
        return [
            __('main.sales_invoices') => AccountingSalesInvoice::query()
                ->where('company_site_id', $site->id)
                ->latest('id')
                ->limit(30)
                ->get()
                ->mapWithKeys(fn (AccountingSalesInvoice $invoice) => [
                    AccountingTask::SOURCE_SALES_INVOICE.':'.$invoice->id => $invoice->reference,
                ])
                ->all(),
            __('main.payment_reminders') => AccountingPaymentReminder::query()
                ->where('company_site_id', $site->id)
                ->latest('id')
                ->limit(30)
                ->get()
                ->mapWithKeys(fn (AccountingPaymentReminder $reminder) => [
                    AccountingTask::SOURCE_PAYMENT_REMINDER.':'.$reminder->id => $reminder->reference,
                ])
                ->all(),
        ];
    }

    private function accountingTaskDocumentUrl(AccountingTask $task, Company $company, CompanySite $site): ?string
    {
        return match ($task->source_type) {
            AccountingTask::SOURCE_SALES_INVOICE => $task->source_id
                ? route('main.accounting.sales-invoices.print', [$company, $site, $task->source_id])
                : null,
            AccountingTask::SOURCE_PAYMENT_REMINDER => $task->source_id
                ? route('main.accounting.payment-reminders.letter', [$company, $site, $task->source_id])
                : null,
            AccountingTask::SOURCE_PAYMENT_PROMISE => route('main.accounting.payment-reminders', [$company, $site]),
            default => null,
        };
    }

    private function syncAccountingTasks(CompanySite $site, User&Authenticatable $user): void
    {
        AccountingSalesInvoice::query()
            ->with('client')
            ->where('company_site_id', $site->id)
            ->where('status', AccountingSalesInvoice::STATUS_OVERDUE)
            ->where('balance_due', '>', 0)
            ->get()
            ->each(function (AccountingSalesInvoice $invoice) use ($site, $user): void {
                $task = AccountingTask::query()->firstOrCreate(
                    ['company_site_id' => $site->id, 'automation_key' => 'overdue_invoice:'.$invoice->id],
                    [
                        'created_by' => $user->id,
                        'client_id' => $invoice->client_id,
                        'title' => __('main.task_overdue_invoice_title', ['reference' => $invoice->reference]),
                        'description' => __('main.task_overdue_invoice_description', [
                            'amount' => number_format((float) $invoice->balance_due, 2, ',', ' '),
                            'currency' => $invoice->currency,
                        ]),
                        'type' => AccountingTask::TYPE_REMINDER,
                        'priority' => AccountingTask::PRIORITY_HIGH,
                        'status' => AccountingTask::STATUS_TODO,
                        'due_date' => $invoice->due_date,
                        'source_type' => AccountingTask::SOURCE_SALES_INVOICE,
                        'source_id' => $invoice->id,
                        'source_reference' => $invoice->reference,
                        'source_label' => __('main.sales_invoice'),
                        'is_automatic' => true,
                    ]
                );
                $this->recordAutomaticAccountingTaskCreation($task, $user);
            });

        AccountingPaymentPromise::query()
            ->with('reminder')
            ->whereHas('reminder', fn ($query) => $query->where('company_site_id', $site->id))
            ->where('status', AccountingPaymentPromise::STATUS_BROKEN)
            ->get()
            ->each(function (AccountingPaymentPromise $promise) use ($site, $user): void {
                $reminder = $promise->reminder;
                $task = AccountingTask::query()->firstOrCreate(
                    ['company_site_id' => $site->id, 'automation_key' => 'broken_promise:'.$promise->id],
                    [
                        'created_by' => $user->id,
                        'client_id' => $reminder?->client_id,
                        'title' => __('main.task_broken_promise_title', ['reference' => $reminder?->reference]),
                        'description' => __('main.task_broken_promise_description'),
                        'type' => AccountingTask::TYPE_PAYMENT,
                        'priority' => AccountingTask::PRIORITY_URGENT,
                        'status' => AccountingTask::STATUS_TODO,
                        'due_date' => $promise->promised_date,
                        'source_type' => AccountingTask::SOURCE_PAYMENT_PROMISE,
                        'source_id' => $promise->id,
                        'source_reference' => $reminder?->reference,
                        'source_label' => __('main.payment_promises'),
                        'is_automatic' => true,
                    ]
                );
                $this->recordAutomaticAccountingTaskCreation($task, $user);
            });

        AccountingTask::query()
            ->where('company_site_id', $site->id)
            ->where('is_automatic', true)
            ->where('source_type', AccountingTask::SOURCE_SALES_INVOICE)
            ->whereNotIn('status', [AccountingTask::STATUS_COMPLETED, AccountingTask::STATUS_CANCELLED])
            ->get()
            ->each(function (AccountingTask $task) use ($user): void {
                $invoice = AccountingSalesInvoice::find($task->source_id);

                if ($invoice && (float) $invoice->balance_due > 0) {
                    return;
                }

                $task->update([
                    'status' => AccountingTask::STATUS_COMPLETED,
                    'completed_by' => $user->id,
                    'completed_at' => now(),
                    'completion_notes' => __('main.task_automatically_settled'),
                ]);
                $task->activities()->create([
                    'created_by' => $user->id,
                    'action_type' => AccountingTaskActivity::TYPE_COMPLETED,
                    'to_status' => AccountingTask::STATUS_COMPLETED,
                    'notes' => __('main.task_automatically_settled'),
                ]);
            });
    }

    private function recordAutomaticAccountingTaskCreation(AccountingTask $task, User&Authenticatable $user): void
    {
        if (! $task->wasRecentlyCreated) {
            return;
        }

        $task->activities()->create([
            'created_by' => $user->id,
            'action_type' => AccountingTaskActivity::TYPE_AUTOMATIC_CREATED,
            'to_status' => $task->status,
        ]);
    }

    private function accountingTaskTypeLabels(): array
    {
        return [
            AccountingTask::TYPE_CALL => __('main.task_type_call'),
            AccountingTask::TYPE_REMINDER => __('main.task_type_reminder'),
            AccountingTask::TYPE_PAYMENT => __('main.task_type_payment'),
            AccountingTask::TYPE_DELIVERY => __('main.task_type_delivery'),
            AccountingTask::TYPE_CONTROL => __('main.task_type_control'),
            AccountingTask::TYPE_ADMINISTRATIVE => __('main.task_type_administrative'),
            AccountingTask::TYPE_OTHER => __('main.other'),
        ];
    }

    private function accountingTaskPriorityLabels(): array
    {
        return [
            AccountingTask::PRIORITY_LOW => __('main.priority_low'),
            AccountingTask::PRIORITY_NORMAL => __('main.priority_normal'),
            AccountingTask::PRIORITY_HIGH => __('main.priority_high'),
            AccountingTask::PRIORITY_URGENT => __('main.priority_urgent'),
        ];
    }

    private function accountingTaskStatusLabels(): array
    {
        return [
            AccountingTask::STATUS_TODO => __('main.task_status_todo'),
            AccountingTask::STATUS_IN_PROGRESS => __('main.task_status_in_progress'),
            AccountingTask::STATUS_COMPLETED => __('main.task_status_completed'),
            AccountingTask::STATUS_CANCELLED => __('main.task_status_cancelled'),
        ];
    }

    private function accountingTaskActivityLabels(): array
    {
        return [
            AccountingTaskActivity::TYPE_CREATED => __('main.task_activity_created'),
            AccountingTaskActivity::TYPE_UPDATED => __('main.task_activity_updated'),
            AccountingTaskActivity::TYPE_COMPLETED => __('main.task_activity_completed'),
            AccountingTaskActivity::TYPE_CANCELLED => __('main.task_activity_cancelled'),
            AccountingTaskActivity::TYPE_AUTOMATIC_CREATED => __('main.task_activity_automatic_created'),
        ];
    }

    private function resolveAccountingPaymentReminderSource(CompanySite $site, string $type, int $id): array
    {
        if ($type === 'invoice') {
            $invoice = AccountingSalesInvoice::query()
                ->where('company_site_id', $site->id)
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', [AccountingSalesInvoice::STATUS_DRAFT, AccountingSalesInvoice::STATUS_CANCELLED, AccountingSalesInvoice::STATUS_CREDITED])
                ->findOrFail($id);

            return ['type' => 'invoice', 'model' => $invoice, 'client_id' => $invoice->client_id];
        }

        $debtor = AccountingDebtor::query()
            ->where('company_site_id', $site->id)
            ->where('status', '!=', AccountingDebtor::STATUS_INACTIVE)
            ->findOrFail($id);

        if ($debtor->balanceReceivable() <= 0) {
            abort(404);
        }

        return ['type' => 'receivable', 'model' => $debtor, 'client_id' => null];
    }

    private function ensureAccountingPaymentReminderBelongsToSite(CompanySite $site, AccountingPaymentReminder $reminder): void
    {
        if ((int) $reminder->company_site_id !== (int) $site->id) {
            abort(404);
        }
    }

    private function accountingPaymentReminderBalance(AccountingPaymentReminder $reminder): float
    {
        $reminder->loadMissing(['salesInvoice', 'debtor']);

        return $reminder->salesInvoice
            ? max(0, (float) $reminder->salesInvoice->balance_due)
            : max(0, (float) $reminder->debtor?->balanceReceivable());
    }

    private function accountingPaymentReminderCurrency(AccountingPaymentReminder $reminder): string
    {
        $reminder->loadMissing(['salesInvoice', 'debtor']);

        return (string) ($reminder->salesInvoice?->currency ?: $reminder->debtor?->currency ?: 'CDF');
    }

    private function syncAccountingPaymentReminderStatuses(CompanySite $site, User&Authenticatable $user): void
    {
        AccountingPaymentReminder::query()
            ->with(['salesInvoice', 'debtor', 'promises'])
            ->where('company_site_id', $site->id)
            ->get()
            ->each(function (AccountingPaymentReminder $reminder) use ($user): void {
                if ($this->accountingPaymentReminderBalance($reminder) <= 0 && $reminder->status !== AccountingPaymentReminder::STATUS_SETTLED) {
                    $reminder->update(['status' => AccountingPaymentReminder::STATUS_SETTLED]);
                    $reminder->actions()->create([
                        'created_by' => $user->id,
                        'action_type' => AccountingPaymentReminderAction::TYPE_SETTLED,
                        'action_at' => now(),
                    ]);

                    return;
                }

                $reminder->promises()
                    ->where('status', AccountingPaymentPromise::STATUS_PENDING)
                    ->whereDate('promised_date', '<', now()->toDateString())
                    ->update(['status' => AccountingPaymentPromise::STATUS_BROKEN]);
            });
    }

    private function accountingPaymentReminderLevelLabels(): array
    {
        return [
            AccountingPaymentReminder::LEVEL_FRIENDLY => __('main.reminder_level_friendly'),
            AccountingPaymentReminder::LEVEL_FIRST => __('main.reminder_level_first'),
            AccountingPaymentReminder::LEVEL_SECOND => __('main.reminder_level_second'),
            AccountingPaymentReminder::LEVEL_FORMAL_NOTICE => __('main.reminder_level_formal_notice'),
        ];
    }

    private function accountingPaymentReminderChannelLabels(): array
    {
        return [
            AccountingPaymentReminder::CHANNEL_EMAIL => __('main.reminder_channel_email'),
            AccountingPaymentReminder::CHANNEL_PHONE => __('main.reminder_channel_phone'),
            AccountingPaymentReminder::CHANNEL_LETTER => __('main.reminder_channel_letter'),
            AccountingPaymentReminder::CHANNEL_OTHER => __('main.other'),
        ];
    }

    private function accountingPaymentReminderStatusLabels(): array
    {
        return [
            'due' => __('main.reminder_status_due'),
            'overdue' => __('main.reminder_status_overdue'),
            AccountingPaymentReminder::STATUS_SENT => __('main.reminder_status_sent'),
            AccountingPaymentReminder::STATUS_PROMISE => __('main.reminder_status_promise'),
            AccountingPaymentReminder::STATUS_SETTLED => __('main.settled'),
            AccountingPaymentReminder::STATUS_DISPUTED => __('main.reminder_status_disputed'),
            AccountingPaymentReminder::STATUS_SUSPENDED => __('main.reminder_status_suspended'),
        ];
    }

    private function accountingPaymentReminderActionLabels(): array
    {
        return [
            AccountingPaymentReminderAction::TYPE_REMINDER_SENT => __('main.reminder_action_sent'),
            AccountingPaymentReminderAction::TYPE_PROMISE => __('main.reminder_action_promise'),
            AccountingPaymentReminderAction::TYPE_DISPUTED => __('main.reminder_action_disputed'),
            AccountingPaymentReminderAction::TYPE_SUSPENDED => __('main.reminder_action_suspended'),
            AccountingPaymentReminderAction::TYPE_SETTLED => __('main.reminder_action_settled'),
        ];
    }

    private function accountingPaymentPromiseStatusLabels(): array
    {
        return [
            AccountingPaymentPromise::STATUS_PENDING => __('main.promise_status_pending'),
            AccountingPaymentPromise::STATUS_HONORED => __('main.promise_status_honored'),
            AccountingPaymentPromise::STATUS_BROKEN => __('main.promise_status_broken'),
            AccountingPaymentPromise::STATUS_CANCELLED => __('main.promise_status_cancelled'),
        ];
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

    private function purchaseOrderStatusLabels(): array
    {
        return [
            AccountingPurchaseOrder::STATUS_DRAFT => __('main.purchase_order_status_draft'),
            AccountingPurchaseOrder::STATUS_SENT => __('main.purchase_order_status_sent'),
            AccountingPurchaseOrder::STATUS_CONFIRMED => __('main.purchase_order_status_confirmed'),
            AccountingPurchaseOrder::STATUS_PARTIALLY_RECEIVED => __('main.purchase_order_status_partially_received'),
            AccountingPurchaseOrder::STATUS_RECEIVED => __('main.purchase_order_status_received'),
            AccountingPurchaseOrder::STATUS_CONVERTED => __('main.purchase_order_status_converted'),
            AccountingPurchaseOrder::STATUS_CANCELLED => __('main.purchase_order_status_cancelled'),
        ];
    }

    private function purchaseOrderLineTypeLabels(): array
    {
        return [
            AccountingPurchaseOrderLine::TYPE_ITEM => __('main.proforma_line_item'),
            AccountingPurchaseOrderLine::TYPE_SERVICE => __('main.proforma_line_service'),
            AccountingPurchaseOrderLine::TYPE_FREE => __('main.proforma_line_free'),
        ];
    }

    private function purchaseOrderRules(CompanySite $site, bool $updating = false): array
    {
        $rules = $this->purchaseRules($site, $updating);

        $rules['supplier_reference'] = ['nullable', 'string', 'max:255'];
        $rules['order_date'] = $rules['purchase_date'];
        $rules['expected_delivery_date'] = ['nullable', 'date', 'after_or_equal:order_date'];
        $rules['status'] = [$updating ? 'required' : 'nullable', Rule::in(AccountingPurchaseOrder::statuses())];

        unset($rules['supplier_invoice_reference'], $rules['purchase_date'], $rules['due_date']);

        return $rules;
    }

    private function purchaseOrderPayload(array $validated, User&Authenticatable $user, array $totals, bool $withCreator = true): array
    {
        $payload = [
            'supplier_id' => $validated['supplier_id'],
            'supplier_reference' => $validated['supplier_reference'] ?? null,
            'title' => $validated['title'] ?? null,
            'order_date' => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'currency' => $validated['currency'],
            'status' => $validated['status'] ?? AccountingPurchaseOrder::STATUS_DRAFT,
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

    private function calculatePurchaseOrderTotals(array $lines, float $taxRate): array
    {
        return $this->calculatePurchaseTotals($lines, $taxRate);
    }

    private function syncPurchaseOrderLines(AccountingPurchaseOrder $purchaseOrder, CompanySite $site, User&Authenticatable $user, array $lines, string $currency): void
    {
        $purchaseOrder->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountType = $this->purchaseOrderLineDiscountType($line);
            $discountValue = (float) ($line['discount_amount'] ?? 0);
            $rawTotal = $quantity * $unitPrice;
            $discount = $this->purchaseOrderLineDiscountAmount($line, $rawTotal);
            $lineType = $line['line_type'];
            $itemId = ($lineType === AccountingPurchaseOrderLine::TYPE_ITEM) ? ($line['item_id'] ?? null) : null;

            if ($lineType === AccountingPurchaseOrderLine::TYPE_FREE && (bool) ($line['create_stock_item'] ?? false)) {
                $item = $this->createStockItemFromFreeLine($site, $user, [
                    'description' => $line['description'],
                    'details' => $line['details'] ?? null,
                    'cost_price' => $line['unit_price'],
                    'unit_price' => $line['unit_price'],
                ], $currency);
                $lineType = AccountingPurchaseOrderLine::TYPE_ITEM;
                $itemId = $item->id;
            }

            $purchaseOrder->lines()->create([
                'line_type' => $lineType,
                'item_id' => $itemId,
                'service_id' => ($lineType === AccountingPurchaseOrderLine::TYPE_SERVICE) ? ($line['service_id'] ?? null) : null,
                'description' => $line['description'],
                'details' => $line['details'] ?? null,
                'quantity' => $quantity,
                'received_quantity' => (float) ($line['received_quantity'] ?? 0),
                'unit_price' => $unitPrice,
                'discount_type' => $discountType,
                'discount_amount' => $discountValue,
                'line_total' => max(0, $rawTotal - $discount),
            ]);
        }
    }

    private function purchaseOrderLineDiscountAmount(array $line, float $rawTotal): float
    {
        $discountType = $this->purchaseOrderLineDiscountType($line);
        $discountValue = max(0, (float) ($line['discount_amount'] ?? 0));

        if ($discountType === AccountingPurchaseOrderLine::DISCOUNT_PERCENT) {
            return round(min($discountValue, 100) * $rawTotal / 100, 2);
        }

        return round(min($discountValue, $rawTotal), 2);
    }

    private function purchaseOrderLineDiscountType(array $line): string
    {
        $discountType = $line['discount_type'] ?? AccountingPurchaseOrderLine::DISCOUNT_FIXED;

        return in_array($discountType, AccountingPurchaseOrderLine::discountTypes(), true)
            ? $discountType
            : AccountingPurchaseOrderLine::DISCOUNT_FIXED;
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

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\GmaoActivity;
use App\Models\GmaoEquipment;
use App\Models\GmaoEquipmentCategory;
use App\Models\GmaoLocation;
use App\Models\GmaoMaintenanceRoute;
use App\Models\GmaoMaintenanceTask;
use App\Models\GmaoPreventivePlan;
use App\Models\GmaoSparePart;
use App\Models\GmaoTechnician;
use App\Models\GmaoWorkOrder;
use App\Models\GmaoWorkOrderPart;
use App\Models\GmaoWorkRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GmaoController extends Controller
{
    public function dashboard(Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $equipment = $site->gmaoEquipment()->with(['location', 'equipmentCategory'])->get();
        $orders = $site->gmaoWorkOrders()->with(['equipment', 'technician'])->latest('planned_at')->get();
        $requests = $site->gmaoWorkRequests()->with('equipment')->latest('requested_at')->get();
        $plans = $site->gmaoPreventivePlans()->with('equipment')->orderBy('next_due_at')->get();
        $parts = $site->gmaoSpareParts()->orderBy('stock_quantity')->get();

        $totalEquipment = $equipment->count();
        $availableEquipment = $equipment->whereNotIn('status', [GmaoEquipment::STATUS_DOWN, GmaoEquipment::STATUS_MAINTENANCE])->count();
        $availability = $totalEquipment > 0 ? round(($availableEquipment / $totalEquipment) * 100) : 0;
        $doneOrders = $orders->where('status', GmaoWorkOrder::STATUS_DONE)->filter(fn (GmaoWorkOrder $order): bool => $order->started_at && $order->completed_at);
        $mttr = $doneOrders->count() > 0
            ? round($doneOrders->avg(fn (GmaoWorkOrder $order): float => $order->started_at->diffInMinutes($order->completed_at) / 60), 1)
            : 0;
        $activeOrders = $orders->whereIn('status', [GmaoWorkOrder::STATUS_PLANNED, GmaoWorkOrder::STATUS_IN_PROGRESS]);
        $urgentRequests = $requests
            ->where('priority', 'urgent')
            ->whereNotIn('status', [GmaoWorkRequest::STATUS_REJECTED, GmaoWorkRequest::STATUS_CONVERTED]);
        $lowParts = $parts->filter(fn (GmaoSparePart $part): bool => (float) $part->stock_quantity <= (float) $part->minimum_quantity);
        $overduePlans = $plans->filter(fn (GmaoPreventivePlan $plan): bool => $plan->next_due_at && $plan->next_due_at->isPast());
        $completionRate = $orders->count() > 0
            ? round(($orders->where('status', GmaoWorkOrder::STATUS_DONE)->count() / $orders->count()) * 100)
            : 0;
        $preventiveCompliance = $plans->count() > 0
            ? max(0, round((($plans->count() - $overduePlans->count()) / $plans->count()) * 100))
            : 100;

        $dashboard = [
            'metrics' => [
                ['label' => __('main.gmao_equipment'), 'value' => $totalEquipment, 'meta' => __('main.gmao_availability_value', ['value' => $availability]), 'icon' => 'bi-cpu', 'tone' => 'blue', 'progress' => $availability],
                ['label' => __('main.gmao_open_requests'), 'value' => $requests->whereNotIn('status', [GmaoWorkRequest::STATUS_REJECTED, GmaoWorkRequest::STATUS_CONVERTED])->count(), 'meta' => __('main.gmao_urgent_value', ['value' => $urgentRequests->count()]), 'icon' => 'bi-exclamation-diamond', 'tone' => 'red', 'progress' => min(100, $urgentRequests->count() * 25)],
                ['label' => __('main.gmao_active_orders'), 'value' => $activeOrders->count(), 'meta' => __('main.gmao_mttr_value', ['value' => $mttr]), 'icon' => 'bi-clipboard2-check', 'tone' => 'green', 'progress' => $completionRate],
                ['label' => __('main.gmao_due_preventive'), 'value' => $plans->where('next_due_at', '<=', now()->addDays(7)->toDateString())->count(), 'meta' => __('main.gmao_low_stock_value', ['value' => $lowParts->count()]), 'icon' => 'bi-calendar2-week', 'tone' => 'purple', 'progress' => $preventiveCompliance],
            ],
            'availability' => $availability,
            'completionRate' => $completionRate,
            'preventiveCompliance' => $preventiveCompliance,
            'riskCards' => [
                ['label' => __('main.gmao_down_assets'), 'value' => $equipment->where('status', GmaoEquipment::STATUS_DOWN)->count(), 'icon' => 'bi-x-octagon', 'tone' => 'danger'],
                ['label' => __('main.gmao_degraded_assets'), 'value' => $equipment->where('status', GmaoEquipment::STATUS_DEGRADED)->count(), 'icon' => 'bi-exclamation-triangle', 'tone' => 'warning'],
                ['label' => __('main.gmao_open_urgent_requests'), 'value' => $urgentRequests->count(), 'icon' => 'bi-lightning-charge', 'tone' => 'danger'],
                ['label' => __('main.gmao_overdue_preventive'), 'value' => $overduePlans->count(), 'icon' => 'bi-calendar-x', 'tone' => 'warning'],
            ],
            'recentOrders' => $orders->take(5),
            'activeOrders' => $activeOrders->take(5),
            'urgentRequests' => $urgentRequests->take(5),
            'criticalEquipment' => $equipment->whereIn('criticality', ['high', 'critical'])->take(5),
            'duePlans' => $plans->take(5),
            'lowParts' => $lowParts->take(5),
            'activities' => $this->activityQuery($site)->take(5)->get(),
            'statusRows' => $equipment->groupBy('status')->map(fn (Collection $rows, string $status): array => [
                'label' => $this->equipmentStatusLabels()[$status] ?? $status,
                'count' => $rows->count(),
                'status' => $status,
            ])->values(),
            'workloadRows' => $orders
                ->whereIn('status', [GmaoWorkOrder::STATUS_PLANNED, GmaoWorkOrder::STATUS_IN_PROGRESS])
                ->groupBy(fn (GmaoWorkOrder $order): string => $order->technician?->name ?? __('main.unassigned'))
                ->map(fn (Collection $rows, string $technician): array => [
                    'technician' => $technician,
                    'count' => $rows->count(),
                    'hours' => number_format((float) $rows->sum('estimated_hours'), 1, ',', ' '),
                ])
                ->sortByDesc('count')
                ->values()
                ->take(5),
            'locationRows' => $equipment
                ->groupBy(fn (GmaoEquipment $equipment): string => $equipment->location?->name ?? __('main.unassigned'))
                ->map(fn (Collection $rows, string $location): array => [
                    'location' => $location,
                    'count' => $rows->count(),
                    'critical' => $rows->whereIn('criticality', ['high', 'critical'])->count(),
                ])
                ->sortByDesc('count')
                ->values()
                ->take(5),
        ];

        return view('main.modules.gmao.dashboard', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'dashboard' => $dashboard,
            'equipmentStatusLabels' => $this->equipmentStatusLabels(),
            'orderStatusLabels' => $this->orderStatusLabels(),
        ]));
    }

    public function locations(Request $request, Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $allLocations = $site->gmaoLocations()
            ->withCount(['children', 'equipment'])
            ->orderBy('name')
            ->get();
        $locationsById = $allLocations->keyBy('id');
        $query = $site->gmaoLocations()
            ->with('parent')
            ->withCount(['children', 'equipment'])
            ->latest();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('type', 'like', '%'.$search.'%')
                    ->orWhere('building', 'like', '%'.$search.'%')
                    ->orWhere('floor', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $locations = $query->paginate(5)->withQueryString();
        $locationTypes = $this->locationTypeLabels();

        return view('main.modules.gmao.locations', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'activeGmaoPage' => 'locations',
            'locations' => $locations,
            'search' => $search,
            'parentOptions' => $allLocations->where('status', GmaoLocation::STATUS_ACTIVE)->values(),
            'typeLabels' => $locationTypes,
            'parentRules' => $this->locationParentRules(),
            'statusLabels' => $this->statusLabels(),
            'pathResolver' => fn (GmaoLocation $location): string => $this->gmaoLocationPath($location, $locationsById),
            'depthResolver' => fn (GmaoLocation $location): int => $this->gmaoLocationDepth($location, $locationsById),
        ]));
    }

    public function storeLocation(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateLocation($request, $site);
        $validated['company_site_id'] = $site->id;
        $validated['reference'] = blank($validated['reference'] ?? null)
            ? $this->nextReference(GmaoLocation::class, 'GML')
            : $validated['reference'];

        $location = GmaoLocation::query()->create($validated);
        $this->logActivity($site, $user, $location, 'location_created', __('main.gmao_location_created_description', ['reference' => $location->reference, 'name' => $location->name]));

        return redirect()
            ->route('main.gmao.locations', [$company, $site])
            ->with('success', __('main.gmao_location_created'));
    }

    public function updateLocation(Request $request, Company $company, CompanySite $site, GmaoLocation $location): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($location->company_site_id === $site->id, 404);

        $validated = $this->validateLocation($request, $site, $location);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $location->reference : $validated['reference'];
        $location->update($validated);
        $this->logActivity($site, $user, $location, 'location_updated', __('main.gmao_location_updated_description', ['reference' => $location->reference, 'name' => $location->name]));

        return redirect()
            ->route('main.gmao.locations', [$company, $site])
            ->with('success', __('main.gmao_location_updated'));
    }

    public function destroyLocation(Company $company, CompanySite $site, GmaoLocation $location): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($location->company_site_id === $site->id, 404);

        if ($location->children()->exists() || $location->equipment()->exists()) {
            return redirect()
                ->route('main.gmao.locations', [$company, $site])
                ->with('success', __('main.gmao_location_delete_blocked'))
                ->with('toast_type', 'danger');
        }

        $reference = $location->reference;
        $name = $location->name;
        $this->logActivity($site, $user, $location, 'location_deleted', __('main.gmao_location_deleted_description', ['reference' => $reference, 'name' => $name]));
        $location->delete();

        return redirect()
            ->route('main.gmao.locations', [$company, $site])
            ->with('success', __('main.gmao_location_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    public function equipment(Request $request, Company $company, CompanySite $site)
    {
        $equipmentStatusLabels = $this->equipmentStatusLabels();
        $criticalityLabels = $this->criticalityLabels();
        $equipmentCategories = $site->gmaoEquipmentCategories()
            ->where('status', GmaoEquipmentCategory::STATUS_ACTIVE)
            ->orderBy('family')
            ->orderBy('name')
            ->get(['id', 'reference', 'name', 'family', 'default_criticality']);

        return $this->resourcePage($request, $company, $site, [
            'active' => 'equipment',
            'title' => __('main.gmao_equipment'),
            'subtitle' => __('main.gmao_equipment_subtitle'),
            'icon' => 'bi-cpu',
            'search' => __('main.gmao_search_equipment'),
            'columns' => [__('main.reference'), __('main.equipment'), __('main.category'), __('main.location'), __('main.gmao_dates'), __('main.status')],
            'query' => GmaoEquipment::query()->where('company_site_id', $site->id)->with(['location', 'equipmentCategory'])->latest(),
            'searchColumns' => ['reference', 'name', 'category', 'brand', 'model', 'serial_number'],
            'mapper' => fn (GmaoEquipment $equipment): array => [
                $equipment->reference,
                ['strong' => $equipment->name, 'small' => trim(collect([$equipment->brand, $equipment->model])->filter()->implode(' · '))],
                ($equipment->equipmentCategory?->name ?? $equipment->category).' · '.($this->criticalityLabels()[$equipment->criticality] ?? ucfirst($equipment->criticality)),
                $equipment->location?->name ?? '-',
                ['strong' => $equipment->commissioned_at?->format('d/m/Y') ?? '-', 'small' => __('main.gmao_warranty_until_short', ['date' => $equipment->warranty_until?->format('d/m/Y') ?? '-'])],
                ['badge' => $this->equipmentStatusLabels()[$equipment->status] ?? $equipment->status, 'class' => $equipment->status],
            ],
            'actions' => 'equipment',
            'extra' => [
                'equipmentLocations' => $site->gmaoLocations()->where('status', 'active')->orderBy('name')->get(['id', 'reference', 'name', 'type']),
                'equipmentCategories' => $equipmentCategories,
                'equipmentStatusLabels' => $equipmentStatusLabels,
                'criticalityLabels' => $criticalityLabels,
            ],
        ]);
    }

    public function storeEquipment(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateEquipment($request, $site);
        $validated = $this->normalizeEquipmentValues($validated);
        $category = GmaoEquipmentCategory::query()
            ->where('company_site_id', $site->id)
            ->where('status', GmaoEquipmentCategory::STATUS_ACTIVE)
            ->findOrFail($validated['gmao_equipment_category_id']);
        $validated['company_site_id'] = $site->id;
        $validated['category'] = $category->name;
        $validated['reference'] = blank($validated['reference'] ?? null)
            ? $this->nextReference(GmaoEquipment::class, 'EQP')
            : $validated['reference'];

        $equipment = GmaoEquipment::query()->create($validated);
        $this->logActivity($site, $user, $equipment, 'equipment_created', __('main.gmao_equipment_created_description', ['reference' => $equipment->reference, 'name' => $equipment->name]));

        return redirect()
            ->route('main.gmao.equipment', [$company, $site])
            ->with('success', __('main.gmao_equipment_created'));
    }

    public function updateEquipment(Request $request, Company $company, CompanySite $site, GmaoEquipment $equipment): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($equipment->company_site_id === $site->id, 404);

        $validated = $this->validateEquipment($request, $site, $equipment);
        $validated = $this->normalizeEquipmentValues($validated);
        $category = GmaoEquipmentCategory::query()
            ->where('company_site_id', $site->id)
            ->where('status', GmaoEquipmentCategory::STATUS_ACTIVE)
            ->findOrFail($validated['gmao_equipment_category_id']);
        $validated['category'] = $category->name;
        $validated['reference'] = blank($validated['reference'] ?? null) ? $equipment->reference : $validated['reference'];
        $equipment->update($validated);
        $this->logActivity($site, $user, $equipment, 'equipment_updated', __('main.gmao_equipment_updated_description', ['reference' => $equipment->reference, 'name' => $equipment->name]));

        return redirect()
            ->route('main.gmao.equipment', [$company, $site])
            ->with('success', __('main.gmao_equipment_updated'));
    }

    public function destroyEquipment(Company $company, CompanySite $site, GmaoEquipment $equipment): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($equipment->company_site_id === $site->id, 404);

        if ($equipment->workRequests()->exists() || $equipment->workOrders()->exists() || $equipment->preventivePlans()->exists()) {
            return redirect()
                ->route('main.gmao.equipment', [$company, $site])
                ->with('success', __('main.gmao_equipment_delete_blocked'))
                ->with('toast_type', 'danger');
        }

        $reference = $equipment->reference;
        $name = $equipment->name;
        $this->logActivity($site, $user, $equipment, 'equipment_deleted', __('main.gmao_equipment_deleted_description', ['reference' => $reference, 'name' => $name]));
        $equipment->delete();

        return redirect()
            ->route('main.gmao.equipment', [$company, $site])
            ->with('success', __('main.gmao_equipment_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    public function equipmentCategories(Request $request, Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $query = GmaoEquipmentCategory::query()
            ->where('company_site_id', $site->id)
            ->withCount('equipment')
            ->latest();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('family', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $categories = $query->paginate(5)->withQueryString();

        return view('main.modules.gmao.equipment-categories', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'activeGmaoPage' => 'equipment-categories',
            'categories' => $categories,
            'search' => $search,
            'criticalityLabels' => $this->criticalityLabels(),
            'statusLabels' => $this->statusLabels(),
        ]));
    }

    public function storeEquipmentCategory(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateEquipmentCategory($request, $site);
        $validated['company_site_id'] = $site->id;
        $validated['reference'] = blank($validated['reference'] ?? null)
            ? $this->nextReference(GmaoEquipmentCategory::class, 'CAT')
            : $validated['reference'];

        $category = GmaoEquipmentCategory::query()->create($validated);
        $this->logActivity($site, $user, $category, 'equipment_category_created', __('main.gmao_equipment_category_created_description', ['reference' => $category->reference, 'name' => $category->name]));

        return redirect()
            ->route('main.gmao.equipment-categories', [$company, $site])
            ->with('success', __('main.gmao_equipment_category_created'));
    }

    public function updateEquipmentCategory(Request $request, Company $company, CompanySite $site, GmaoEquipmentCategory $category): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($category->company_site_id === $site->id, 404);

        $validated = $this->validateEquipmentCategory($request, $site, $category);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $category->reference : $validated['reference'];
        $category->update($validated);
        $category->equipment()->update(['category' => $category->name]);
        $this->logActivity($site, $user, $category, 'equipment_category_updated', __('main.gmao_equipment_category_updated_description', ['reference' => $category->reference, 'name' => $category->name]));

        return redirect()
            ->route('main.gmao.equipment-categories', [$company, $site])
            ->with('success', __('main.gmao_equipment_category_updated'));
    }

    public function destroyEquipmentCategory(Company $company, CompanySite $site, GmaoEquipmentCategory $category): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($category->company_site_id === $site->id, 404);

        if ($category->equipment()->exists()) {
            return redirect()
                ->route('main.gmao.equipment-categories', [$company, $site])
                ->with('success', __('main.gmao_equipment_category_delete_blocked'))
                ->with('toast_type', 'danger');
        }

        $reference = $category->reference;
        $name = $category->name;
        $this->logActivity($site, $user, $category, 'equipment_category_deleted', __('main.gmao_equipment_category_deleted_description', ['reference' => $reference, 'name' => $name]));
        $category->delete();

        return redirect()
            ->route('main.gmao.equipment-categories', [$company, $site])
            ->with('success', __('main.gmao_equipment_category_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    public function requests(Request $request, Company $company, CompanySite $site)
    {
        return $this->resourcePage($request, $company, $site, [
            'active' => 'requests',
            'title' => __('main.gmao_requests'),
            'subtitle' => __('main.gmao_requests_subtitle'),
            'icon' => 'bi-exclamation-diamond',
            'search' => __('main.gmao_search_requests'),
            'columns' => [__('main.reference'), __('main.request'), __('main.equipment'), __('main.priority'), __('main.status')],
            'query' => GmaoWorkRequest::query()->where('company_site_id', $site->id)->with('equipment')->latest('requested_at'),
            'searchColumns' => ['reference', 'title', 'requester_name', 'description', 'priority', 'status'],
            'mapper' => fn (GmaoWorkRequest $request): array => [
                $request->reference,
                ['strong' => $request->title, 'small' => $request->requester_name ?: '-'],
                $request->equipment?->name ?? '-',
                ['badge' => $this->priorityLabels()[$request->priority] ?? $request->priority, 'class' => 'priority-'.$request->priority],
                ['badge' => $this->requestStatusLabels()[$request->status] ?? $request->status, 'class' => $request->status],
            ],
        ]);
    }

    public function workOrders(Request $request, Company $company, CompanySite $site)
    {
        return $this->resourcePage($request, $company, $site, [
            'active' => 'work-orders',
            'title' => __('main.gmao_work_orders'),
            'subtitle' => __('main.gmao_work_orders_subtitle'),
            'icon' => 'bi-clipboard2-check',
            'search' => __('main.gmao_search_work_orders'),
            'columns' => [__('main.reference'), __('main.intervention'), __('main.equipment'), __('main.assigned_to'), __('main.status')],
            'query' => GmaoWorkOrder::query()->where('company_site_id', $site->id)->with(['equipment', 'technician'])->latest('planned_at'),
            'searchColumns' => ['reference', 'title', 'type', 'priority', 'status', 'description'],
            'mapper' => fn (GmaoWorkOrder $order): array => [
                $order->reference,
                ['strong' => $order->title, 'small' => ucfirst($order->type).' · '.($order->planned_at?->format('d/m/Y H:i') ?? '-')],
                $order->equipment?->name ?? '-',
                $order->technician?->name ?? '-',
                ['badge' => $this->orderStatusLabels()[$order->status] ?? $order->status, 'class' => $order->status],
            ],
        ]);
    }

    public function preventive(Request $request, Company $company, CompanySite $site)
    {
        return $this->resourcePage($request, $company, $site, [
            'active' => 'preventive',
            'title' => __('main.gmao_preventive'),
            'subtitle' => __('main.gmao_preventive_subtitle'),
            'icon' => 'bi-calendar2-week',
            'search' => __('main.gmao_search_preventive'),
            'columns' => [__('main.reference'), __('main.plan'), __('main.equipment'), __('main.frequency'), __('main.next_due')],
            'query' => GmaoPreventivePlan::query()->where('company_site_id', $site->id)->with('equipment')->orderBy('next_due_at'),
            'searchColumns' => ['reference', 'title', 'frequency', 'status', 'instructions'],
            'mapper' => fn (GmaoPreventivePlan $plan): array => [
                $plan->reference,
                ['strong' => $plan->title, 'small' => $plan->instructions],
                $plan->equipment?->name ?? '-',
                $this->frequencyLabels()[$plan->frequency] ?? $plan->frequency,
                ['badge' => $plan->next_due_at?->format('d/m/Y') ?? '-', 'class' => $plan->next_due_at && $plan->next_due_at->lte(now()->addDays(7)) ? 'urgent' : 'active'],
            ],
        ]);
    }

    public function maintenanceRoutes(Request $request, Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $query = GmaoMaintenanceRoute::query()
            ->where('company_site_id', $site->id)
            ->with(['equipmentCategory', 'tasks'])
            ->withCount('preventivePlans')
            ->latest();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('frequency', 'like', '%'.$search.'%')
                    ->orWhere('instructions', 'like', '%'.$search.'%');
            });
        }

        $routes = $query->paginate(5)->withQueryString();

        return view('main.modules.gmao.maintenance-routes', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'activeGmaoPage' => 'maintenance-routes',
            'routes' => $routes,
            'search' => $search,
            'equipmentCategories' => $site->gmaoEquipmentCategories()
                ->where('status', GmaoEquipmentCategory::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'reference', 'name']),
            'frequencyLabels' => $this->frequencyLabels(),
            'statusLabels' => $this->statusLabels(),
        ]));
    }

    public function storeMaintenanceRoute(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateMaintenanceRoute($request, $site);
        $tasks = $this->parseMaintenanceTasks((string) ($validated['tasks'] ?? ''));
        unset($validated['tasks']);
        $validated['company_site_id'] = $site->id;
        $validated['reference'] = blank($validated['reference'] ?? null)
            ? $this->nextReference(GmaoMaintenanceRoute::class, 'GAM')
            : $validated['reference'];

        $route = GmaoMaintenanceRoute::query()->create($validated);
        $this->syncMaintenanceTasks($route, $tasks);
        $this->logActivity($site, $user, $route, 'maintenance_route_created', __('main.gmao_maintenance_route_created_description', ['reference' => $route->reference, 'title' => $route->title]));

        return redirect()
            ->route('main.gmao.maintenance-routes', [$company, $site])
            ->with('success', __('main.gmao_maintenance_route_created'));
    }

    public function updateMaintenanceRoute(Request $request, Company $company, CompanySite $site, GmaoMaintenanceRoute $route): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($route->company_site_id === $site->id, 404);

        $validated = $this->validateMaintenanceRoute($request, $site, $route);
        $tasks = $this->parseMaintenanceTasks((string) ($validated['tasks'] ?? ''));
        unset($validated['tasks']);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $route->reference : $validated['reference'];
        $route->update($validated);
        $this->syncMaintenanceTasks($route, $tasks);
        $this->logActivity($site, $user, $route, 'maintenance_route_updated', __('main.gmao_maintenance_route_updated_description', ['reference' => $route->reference, 'title' => $route->title]));

        return redirect()
            ->route('main.gmao.maintenance-routes', [$company, $site])
            ->with('success', __('main.gmao_maintenance_route_updated'));
    }

    public function destroyMaintenanceRoute(Company $company, CompanySite $site, GmaoMaintenanceRoute $route): RedirectResponse
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        abort_unless($route->company_site_id === $site->id, 404);

        if ($route->preventivePlans()->exists()) {
            return redirect()
                ->route('main.gmao.maintenance-routes', [$company, $site])
                ->with('success', __('main.gmao_maintenance_route_delete_blocked'))
                ->with('toast_type', 'danger');
        }

        $reference = $route->reference;
        $title = $route->title;
        $this->logActivity($site, $user, $route, 'maintenance_route_deleted', __('main.gmao_maintenance_route_deleted_description', ['reference' => $reference, 'title' => $title]));
        $route->delete();

        return redirect()
            ->route('main.gmao.maintenance-routes', [$company, $site])
            ->with('success', __('main.gmao_maintenance_route_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    public function technicians(Request $request, Company $company, CompanySite $site)
    {
        return $this->resourcePage($request, $company, $site, [
            'active' => 'technicians',
            'title' => __('main.gmao_technicians'),
            'subtitle' => __('main.gmao_technicians_subtitle'),
            'icon' => 'bi-person-gear',
            'search' => __('main.gmao_search_technicians'),
            'columns' => [__('main.reference'), __('main.name'), __('main.specialty'), __('main.contact'), __('main.status')],
            'query' => GmaoTechnician::query()->where('company_site_id', $site->id)->latest(),
            'searchColumns' => ['reference', 'name', 'specialty', 'phone', 'email', 'status'],
            'mapper' => fn (GmaoTechnician $technician): array => [
                $technician->reference,
                ['strong' => $technician->name, 'small' => $technician->email],
                $technician->specialty ?: '-',
                $technician->phone ?: '-',
                ['badge' => $this->technicianStatusLabels()[$technician->status] ?? $technician->status, 'class' => $technician->status],
            ],
        ]);
    }

    public function spareParts(Request $request, Company $company, CompanySite $site)
    {
        return $this->resourcePage($request, $company, $site, [
            'active' => 'spare-parts',
            'title' => __('main.gmao_spare_parts'),
            'subtitle' => __('main.gmao_spare_parts_subtitle'),
            'icon' => 'bi-nut',
            'search' => __('main.gmao_search_spare_parts'),
            'columns' => [__('main.reference'), __('main.part'), __('main.category'), __('main.stock'), __('main.status')],
            'query' => GmaoSparePart::query()->where('company_site_id', $site->id)->orderBy('stock_quantity'),
            'searchColumns' => ['reference', 'name', 'category', 'unit', 'status'],
            'mapper' => fn (GmaoSparePart $part): array => [
                $part->reference,
                ['strong' => $part->name, 'small' => number_format((float) $part->unit_cost, 2, ',', ' ').' '.$part->currency],
                $part->category ?: '-',
                number_format((float) $part->stock_quantity, 2, ',', ' ').' '.$part->unit,
                ['badge' => ((float) $part->stock_quantity <= (float) $part->minimum_quantity) ? __('main.gmao_low_stock') : __('main.active'), 'class' => ((float) $part->stock_quantity <= (float) $part->minimum_quantity) ? 'urgent' : 'active'],
            ],
        ]);
    }

    public function traceability(Request $request, Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $query = $this->activityQuery($site)->with('actor');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('action', 'like', '%'.$search.'%');
            });
        }

        $activities = $query->paginate(5)->withQueryString();

        return view('main.modules.gmao.traceability', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'activities' => $activities,
            'search' => $search,
            'actionLabels' => $this->activityActionLabels(),
        ]));
    }

    public function reports(Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.gmao.reports', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), $this->reportData($site)));
    }

    public function printReport(Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return Pdf::loadView('main.modules.gmao.pdf.reports', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), $this->reportData($site)))
            ->setPaper('a4')
            ->download('rapport-gmao-'.$site->id.'.pdf');
    }

    public function notifications(Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.gmao.notifications', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'notifications' => $this->activityQuery($site)->with('actor')->paginate(10),
            'actionLabels' => $this->activityActionLabels(),
        ]));
    }

    public function showNotification(Company $company, CompanySite $site, GmaoActivity $notification)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        abort_unless($notification->company_site_id === $site->id, 404);

        [$user, $moduleMeta] = $access;

        return view('main.modules.gmao.notification-show', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'notification' => $notification->load('actor'),
            'actionLabels' => $this->activityActionLabels(),
        ]));
    }

    public function settings(Company $company, CompanySite $site)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.gmao.settings', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'menuKeys' => request()->attributes->get('gmao_visible_menu_keys', []),
        ]));
    }

    private function resourcePage(Request $request, Company $company, CompanySite $site, array $config)
    {
        $access = $this->gmaoAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $query = $config['query'];

        if ($search !== '') {
            $query->where(function (Builder $query) use ($config, $search): void {
                foreach ($config['searchColumns'] as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        /** @var LengthAwarePaginator $items */
        $items = $query->paginate(5)->withQueryString();
        $records = $items->getCollection()->values();
        $rows = $items->through($config['mapper']);

        return view('main.modules.gmao.resource', array_merge($this->baseViewData($company, $site, $user, $moduleMeta), [
            'activeGmaoPage' => $config['active'],
            'title' => $config['title'],
            'subtitle' => $config['subtitle'],
            'icon' => $config['icon'],
            'searchPlaceholder' => $config['search'],
            'columns' => $config['columns'],
            'rows' => $rows,
            'records' => $records,
            'search' => $search,
            'resourceActions' => $config['actions'] ?? null,
            ...($config['extra'] ?? []),
        ]));
    }

    private function validateEquipment(Request $request, CompanySite $site, ?GmaoEquipment $equipment = null): array
    {
        return $request->validate([
            'gmao_location_id' => [
                'nullable',
                Rule::exists('gmao_locations', 'id')->where('company_site_id', $site->id),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('gmao_equipment', 'reference')
                    ->where('company_site_id', $site->id)
                    ->ignore($equipment?->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'asset_code' => ['nullable', 'string', 'max:80'],
            'gmao_equipment_category_id' => [
                'required',
                Rule::exists('gmao_equipment_categories', 'id')
                    ->where('company_site_id', $site->id)
                    ->where('status', GmaoEquipmentCategory::STATUS_ACTIVE),
            ],
            'criticality' => ['required', Rule::in(array_keys($this->criticalityLabels()))],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'supplier' => ['nullable', 'string', 'max:160'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'expense_type' => ['nullable', Rule::in(['capex', 'opex'])],
            'cost_center' => ['nullable', 'string', 'max:80'],
            'meter_unit' => ['nullable', 'string', 'max:40'],
            'current_meter' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'last_meter_read_at' => ['nullable', 'date'],
            'expected_lifetime_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'commissioned_at' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys($this->equipmentStatusLabels()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function normalizeEquipmentValues(array $validated): array
    {
        $validated['acquisition_cost'] = $validated['acquisition_cost'] ?? 0;
        $validated['expense_type'] = $validated['expense_type'] ?? 'capex';
        $validated['current_meter'] = $validated['current_meter'] ?? 0;

        return $validated;
    }

    private function validateLocation(Request $request, CompanySite $site, ?GmaoLocation $location = null): array
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('gmao_locations', 'id')->where('company_site_id', $site->id),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('gmao_locations', 'reference')
                    ->where('company_site_id', $site->id)
                    ->ignore($location?->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(array_keys($this->locationTypeLabels()))],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
        ]);

        if ($location && (int) ($validated['parent_id'] ?? 0) === (int) $location->id) {
            throw ValidationException::withMessages(['parent_id' => __('main.gmao_location_invalid_parent')]);
        }

        if ($location && ! empty($validated['parent_id']) && $this->isDescendantLocation($site, $location, (int) $validated['parent_id'])) {
            throw ValidationException::withMessages(['parent_id' => __('main.gmao_location_invalid_parent')]);
        }

        $expectedParentType = $this->locationParentRules()[$validated['type']] ?? null;
        $parentId = $validated['parent_id'] ?? null;

        if ($expectedParentType === null && ! empty($parentId)) {
            throw ValidationException::withMessages(['parent_id' => __('main.gmao_location_root_only')]);
        }

        if ($expectedParentType !== null && empty($parentId)) {
            throw ValidationException::withMessages(['parent_id' => __('main.gmao_location_parent_required')]);
        }

        if ($expectedParentType !== null && ! empty($parentId)) {
            $parent = $site->gmaoLocations()->find($parentId);

            if (! $parent || $parent->type !== $expectedParentType) {
                throw ValidationException::withMessages(['parent_id' => __('main.gmao_location_invalid_parent_type')]);
            }
        }

        return $validated;
    }

    private function isDescendantLocation(CompanySite $site, GmaoLocation $location, int $candidateParentId): bool
    {
        $childrenByParent = $site->gmaoLocations()->get(['id', 'parent_id'])->groupBy('parent_id');
        $stack = collect($childrenByParent->get($location->id, []))->pluck('id')->all();

        while ($stack !== []) {
            $currentId = (int) array_pop($stack);

            if ($currentId === $candidateParentId) {
                return true;
            }

            foreach ($childrenByParent->get($currentId, []) as $child) {
                $stack[] = (int) $child->id;
            }
        }

        return false;
    }

    private function validateEquipmentCategory(Request $request, CompanySite $site, ?GmaoEquipmentCategory $category = null): array
    {
        return $request->validate([
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('gmao_equipment_categories', 'reference')
                    ->where('company_site_id', $site->id)
                    ->ignore($category?->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('gmao_equipment_categories', 'code')
                    ->where('company_site_id', $site->id)
                    ->ignore($category?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('gmao_equipment_categories', 'name')
                    ->where('company_site_id', $site->id)
                    ->ignore($category?->id),
            ],
            'family' => ['nullable', 'string', 'max:80'],
            'default_criticality' => ['required', Rule::in(array_keys($this->criticalityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateMaintenanceRoute(Request $request, CompanySite $site, ?GmaoMaintenanceRoute $route = null): array
    {
        return $request->validate([
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('gmao_maintenance_routes', 'reference')
                    ->where('company_site_id', $site->id)
                    ->ignore($route?->id),
            ],
            'title' => ['required', 'string', 'max:160'],
            'gmao_equipment_category_id' => [
                'nullable',
                Rule::exists('gmao_equipment_categories', 'id')
                    ->where('company_site_id', $site->id)
                    ->where('status', GmaoEquipmentCategory::STATUS_ACTIVE),
            ],
            'frequency' => ['required', Rule::in(array_keys($this->frequencyLabels()))],
            'estimated_duration_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'tasks' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function parseMaintenanceTasks(string $tasks): array
    {
        return collect(preg_split('/\R+/', $tasks) ?: [])
            ->map(fn (string $task): string => trim($task))
            ->filter()
            ->values()
            ->map(fn (string $task, int $index): array => [
                'position' => $index + 1,
                'title' => Str::limit($task, 160, ''),
                'instructions' => $task,
                'estimated_minutes' => 0,
            ])
            ->all();
    }

    private function syncMaintenanceTasks(GmaoMaintenanceRoute $route, array $tasks): void
    {
        $route->tasks()->delete();

        foreach ($tasks as $task) {
            $route->tasks()->create($task);
        }
    }

    private function gmaoLocationPath(GmaoLocation $location, Collection $locationsById): string
    {
        $segments = [$location->name];
        $parentId = $location->parent_id;
        $guard = 0;

        while ($parentId && $guard < 20) {
            $parent = $locationsById->get($parentId);

            if (! $parent) {
                break;
            }

            array_unshift($segments, $parent->name);
            $parentId = $parent->parent_id;
            $guard++;
        }

        return implode(' / ', $segments);
    }

    private function gmaoLocationDepth(GmaoLocation $location, Collection $locationsById): int
    {
        $depth = 0;
        $parentId = $location->parent_id;
        $guard = 0;

        while ($parentId && $guard < 20) {
            $parent = $locationsById->get($parentId);

            if (! $parent) {
                break;
            }

            $depth++;
            $parentId = $parent->parent_id;
            $guard++;
        }

        return $depth;
    }

    private function nextReference(string $modelClass, string $prefix): string
    {
        $nextId = ((int) ($modelClass::query()->max('id') ?? 0)) + 1;

        do {
            $reference = $prefix.'-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while ($modelClass::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function logActivity(CompanySite $site, ?User $actor, object $subject, string $action, string $description): void
    {
        GmaoActivity::query()->create([
            'company_site_id' => $site->id,
            'user_id' => $actor?->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id ?? null,
            'reference' => $subject->reference ?? null,
            'action' => $action,
            'title' => $this->activityActionLabels()[$action] ?? $action,
            'description' => $description,
        ]);
    }

    private function reportData(CompanySite $site): array
    {
        $equipment = $site->gmaoEquipment()->with('location')->get();
        $orders = $site->gmaoWorkOrders()->with(['equipment', 'technician', 'parts'])->latest('planned_at')->get();
        $requests = $site->gmaoWorkRequests()->with('equipment')->latest('requested_at')->get();
        $plans = $site->gmaoPreventivePlans()->with(['equipment', 'maintenanceRoute'])->orderBy('next_due_at')->get();
        $parts = $site->gmaoSpareParts()->orderBy('stock_quantity')->get();
        $routes = $site->gmaoMaintenanceRoutes()->withCount('tasks')->get();

        $totalEquipment = $equipment->count();
        $availableEquipment = $equipment->whereNotIn('status', [GmaoEquipment::STATUS_DOWN, GmaoEquipment::STATUS_MAINTENANCE])->count();
        $availability = $totalEquipment > 0 ? round(($availableEquipment / $totalEquipment) * 100) : 0;
        $doneOrders = $orders->where('status', GmaoWorkOrder::STATUS_DONE);
        $ordersWithRepairTime = $doneOrders->filter(fn (GmaoWorkOrder $order): bool => $order->started_at && $order->completed_at);
        $mttr = $ordersWithRepairTime->count() > 0
            ? round($ordersWithRepairTime->avg(fn (GmaoWorkOrder $order): float => $order->started_at->diffInMinutes($order->completed_at) / 60), 1)
            : 0;
        $correctiveFailures = $doneOrders->where('type', 'corrective')->count();
        $oldestCommissioning = $equipment->pluck('commissioned_at')->filter()->sort()->first();
        $observedHours = $oldestCommissioning
            ? max(1, $oldestCommissioning->diffInHours(now()) * max(1, $totalEquipment))
            : max(1, 24 * 30 * max(1, $totalEquipment));
        $mtbf = $correctiveFailures > 0 ? round($observedHours / $correctiveFailures, 1) : $observedHours;
        $backlog = $orders->whereNotIn('status', [GmaoWorkOrder::STATUS_DONE, GmaoWorkOrder::STATUS_CANCELLED])->count()
            + $requests->whereNotIn('status', [GmaoWorkRequest::STATUS_REJECTED, GmaoWorkRequest::STATUS_CONVERTED])->count();
        $maintenanceCost = (float) $orders->sum(fn (GmaoWorkOrder $order): float => (float) $order->labor_cost + (float) $order->external_cost + (float) $order->capex_amount + (float) $order->opex_amount)
            + (float) $orders->sum(fn (GmaoWorkOrder $order): float => $order->parts->sum(fn (GmaoWorkOrderPart $part): float => (float) $part->quantity * (float) $part->unit_cost));
        $capex = (float) $equipment->where('expense_type', 'capex')->sum('acquisition_cost') + (float) $orders->sum('capex_amount');
        $opex = (float) $orders->sum('opex_amount') + (float) $orders->sum('labor_cost') + (float) $orders->sum('external_cost');

        $metrics = [
            ['label' => __('main.gmao_availability'), 'value' => $availability.'%', 'meta' => __('main.gmao_assets_monitored'), 'icon' => 'bi-activity', 'tone' => 'blue'],
            ['label' => 'MTTR', 'value' => $mttr.' h', 'meta' => __('main.gmao_mean_repair_time'), 'icon' => 'bi-stopwatch', 'tone' => 'green'],
            ['label' => 'MTBF', 'value' => number_format((float) $mtbf, 1, ',', ' ').' h', 'meta' => __('main.gmao_mean_failure_time'), 'icon' => 'bi-shield-check', 'tone' => 'purple'],
            ['label' => __('main.gmao_backlog'), 'value' => $backlog, 'meta' => __('main.gmao_pending_workload'), 'icon' => 'bi-inboxes', 'tone' => 'amber'],
        ];

        return [
            'metrics' => $metrics,
            'financialSummary' => [
                'maintenance_cost' => $maintenanceCost,
                'capex' => $capex,
                'opex' => $opex,
                'currency' => $site->currency ?: 'USD',
            ],
            'equipmentRows' => $equipment->groupBy('status')->map(fn (Collection $rows, string $status): array => [
                'label' => $this->equipmentStatusLabels()[$status] ?? $status,
                'count' => $rows->count(),
                'critical' => $rows->whereIn('criticality', ['high', 'critical'])->count(),
                'status' => $status,
            ])->values(),
            'orderRows' => $orders->groupBy('status')->map(fn (Collection $rows, string $status): array => [
                'label' => $this->orderStatusLabels()[$status] ?? $status,
                'count' => $rows->count(),
                'hours' => number_format((float) $rows->sum('actual_hours'), 1, ',', ' '),
                'status' => $status,
            ])->values(),
            'requestRows' => $requests->take(5),
            'preventiveRows' => $plans->take(5),
            'stockRows' => $parts->take(5),
            'routeRows' => $routes->take(5),
            'frequencyLabels' => $this->frequencyLabels(),
        ];
    }

    private function baseViewData(Company $company, CompanySite $site, User&Authenticatable $user, array $moduleMeta): array
    {
        $notifications = $this->headerNotifications($site);

        return [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'moduleMeta' => $moduleMeta,
            'pdfSettings' => $site->accountingModuleSetting,
            'notificationModuleGroup' => CompanySite::MODULE_GMAO,
            'accountingNotifications' => $notifications,
            'accountingUnreadNotificationsCount' => count($notifications),
        ];
    }

    private function gmaoAccess(Company $company, CompanySite $site): array|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $user->canManageCompany($company, 'can_view')) {
            return redirect()->route('main');
        }

        if (! in_array(CompanySite::MODULE_GMAO, $this->availableSiteModulesForUser($user, $site), true)) {
            return redirect()->route('main.companies.sites.show', [$company, $site]);
        }

        return [$user, $this->moduleMeta()];
    }

    private function availableSiteModulesForUser(User&Authenticatable $user, CompanySite $site): array
    {
        $siteModules = $site->modules ?? [];

        if ($user->isAdmin() || $user->isSuperadmin()) {
            return $siteModules;
        }

        $assignedSite = $user->sites()->whereKey($site->getKey())->first();
        $permissions = $assignedSite?->pivot?->module_permissions;

        if (blank($permissions)) {
            return $siteModules;
        }

        $decoded = json_decode((string) $permissions, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($permission): bool => $permission === true || (is_array($permission) && collect($permission)->contains(true)))
            ->keys()
            ->intersect($siteModules)
            ->values()
            ->all();
    }

    private function moduleMeta(): array
    {
        return [
            'label' => __('main.module_gmao'),
            'description' => __('main.module_gmao_description'),
            'icon' => 'bi-tools',
            'tone' => 'cyan',
            'class' => 'module-gmao',
        ];
    }

    private function activityQuery(CompanySite $site)
    {
        return GmaoActivity::query()
            ->where('company_site_id', $site->id)
            ->latest();
    }

    private function headerNotifications(CompanySite $site): array
    {
        return $this->activityQuery($site)
            ->with('actor')
            ->take(10)
            ->get()
            ->map(fn (GmaoActivity $activity): array => [
                'id' => $activity->id,
                'actor' => $activity->actor?->name ?? __('main.system'),
                'action' => $this->activityActionLabels()[$activity->action] ?? $activity->title,
                'reference' => $activity->reference,
                'time' => $activity->created_at?->diffForHumans(),
                'icon' => $this->activityIcon($activity->action),
                'is_read' => false,
            ])
            ->all();
    }

    private function activityIcon(string $action): string
    {
        if (Str::contains($action, 'equipment')) {
            return 'bi-cpu';
        }

        if (Str::contains($action, 'route')) {
            return 'bi-list-check';
        }

        if (Str::contains($action, 'location')) {
            return 'bi-geo-alt';
        }

        if (Str::contains($action, 'order')) {
            return 'bi-clipboard2-check';
        }

        if (Str::contains($action, 'stock')) {
            return 'bi-nut';
        }

        return 'bi-tools';
    }

    private function statusLabels(): array
    {
        return [
            'active' => __('main.active'),
            'inactive' => __('main.inactive'),
        ];
    }

    private function locationTypeLabels(): array
    {
        return [
            'room' => __('main.gmao_location_type_room'),
            'zone' => __('main.gmao_location_type_zone'),
            'rack' => __('main.gmao_location_type_rack'),
            'shelf' => __('main.gmao_location_type_shelf'),
            'position' => __('main.gmao_location_type_position'),
        ];
    }

    private function locationParentRules(): array
    {
        return [
            'room' => null,
            'zone' => 'room',
            'rack' => 'zone',
            'shelf' => 'rack',
            'position' => 'shelf',
        ];
    }

    private function equipmentStatusLabels(): array
    {
        return [
            GmaoEquipment::STATUS_OPERATIONAL => __('main.gmao_status_operational'),
            GmaoEquipment::STATUS_DEGRADED => __('main.gmao_status_degraded'),
            GmaoEquipment::STATUS_DOWN => __('main.gmao_status_down'),
            GmaoEquipment::STATUS_MAINTENANCE => __('main.gmao_status_maintenance'),
        ];
    }

    private function criticalityLabels(): array
    {
        return [
            'low' => __('main.gmao_criticality_low'),
            'medium' => __('main.gmao_criticality_medium'),
            'high' => __('main.gmao_criticality_high'),
            'critical' => __('main.gmao_criticality_critical'),
        ];
    }

    private function requestStatusLabels(): array
    {
        return [
            GmaoWorkRequest::STATUS_NEW => __('main.gmao_status_new'),
            GmaoWorkRequest::STATUS_APPROVED => __('main.gmao_status_approved'),
            GmaoWorkRequest::STATUS_REJECTED => __('main.gmao_status_rejected'),
            GmaoWorkRequest::STATUS_CONVERTED => __('main.gmao_status_converted'),
        ];
    }

    private function orderStatusLabels(): array
    {
        return [
            GmaoWorkOrder::STATUS_PLANNED => __('main.gmao_status_planned'),
            GmaoWorkOrder::STATUS_IN_PROGRESS => __('main.gmao_status_in_progress'),
            GmaoWorkOrder::STATUS_DONE => __('main.gmao_status_done'),
            GmaoWorkOrder::STATUS_CANCELLED => __('main.gmao_status_cancelled'),
        ];
    }

    private function technicianStatusLabels(): array
    {
        return [
            'available' => __('main.gmao_status_available'),
            'busy' => __('main.gmao_status_busy'),
            'inactive' => __('main.inactive'),
        ];
    }

    private function priorityLabels(): array
    {
        return [
            'low' => __('main.priority_low'),
            'normal' => __('main.priority_normal'),
            'high' => __('main.priority_high'),
            'urgent' => __('main.priority_urgent'),
        ];
    }

    private function frequencyLabels(): array
    {
        return [
            'daily' => __('main.frequency_daily'),
            'weekly' => __('main.frequency_weekly'),
            'monthly' => __('main.frequency_monthly'),
            'quarterly' => __('main.frequency_quarterly'),
            'yearly' => __('main.frequency_yearly'),
        ];
    }

    private function activityActionLabels(): array
    {
        return [
            'equipment_created' => __('main.gmao_action_equipment_created'),
            'equipment_updated' => __('main.gmao_action_equipment_updated'),
            'equipment_deleted' => __('main.gmao_action_equipment_deleted'),
            'equipment_category_created' => __('main.gmao_action_equipment_category_created'),
            'equipment_category_updated' => __('main.gmao_action_equipment_category_updated'),
            'equipment_category_deleted' => __('main.gmao_action_equipment_category_deleted'),
            'location_created' => __('main.gmao_action_location_created'),
            'location_updated' => __('main.gmao_action_location_updated'),
            'location_deleted' => __('main.gmao_action_location_deleted'),
            'maintenance_route_created' => __('main.gmao_action_maintenance_route_created'),
            'maintenance_route_updated' => __('main.gmao_action_maintenance_route_updated'),
            'maintenance_route_deleted' => __('main.gmao_action_maintenance_route_deleted'),
            'request_created' => __('main.gmao_action_request_created'),
            'work_order_planned' => __('main.gmao_action_work_order_planned'),
            'work_order_completed' => __('main.gmao_action_work_order_completed'),
            'preventive_due' => __('main.gmao_action_preventive_due'),
            'stock_alert' => __('main.gmao_action_stock_alert'),
        ];
    }
}

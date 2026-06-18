<?php

namespace App\Http\Controllers;

use App\Models\AccountingMenuPermission;
use App\Models\AccountingModuleSetting;
use App\Models\AccountingNotification;
use App\Models\ArchiveActivity;
use App\Models\ArchiveBox;
use App\Models\ArchiveCabinet;
use App\Models\ArchiveCompartment;
use App\Models\ArchiveContainer;
use App\Models\ArchiveLocation;
use App\Models\ArchiveMovement;
use App\Models\ArchiveRack;
use App\Models\ArchiveRecord;
use App\Models\ArchiveRetentionRule;
use App\Models\ArchiveRoom;
use App\Models\ArchiveShelf;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\User;
use App\Support\ArchivingModuleNavigation;
use App\Support\AccountingActivityFeed;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArchivingController extends Controller
{
    public function dashboard(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.archiving.dashboard', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'dashboard' => $this->dashboardData($site),
        ]);
    }

    public function locations(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));

        return view('main.modules.archiving.locations', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'rooms' => $this->archivePhysicalQuery($site->archiveRooms(), $search, ['reference', 'name', 'code', 'status'])->paginate(5, ['*'], 'rooms_page')->withQueryString(),
            'racks' => $this->archivePhysicalQuery($site->archiveRacks()->with('room'), $search, ['reference', 'name', 'code', 'status'])->paginate(5, ['*'], 'racks_page')->withQueryString(),
            'cabinets' => $this->archivePhysicalQuery($site->archiveCabinets()->with('rack.room'), $search, ['reference', 'name', 'code', 'status'])->paginate(5, ['*'], 'cabinets_page')->withQueryString(),
            'shelves' => $this->archivePhysicalQuery($site->archiveShelves()->with('cabinet.rack.room'), $search, ['reference', 'name', 'code', 'status'])->paginate(5, ['*'], 'shelves_page')->withQueryString(),
            'compartments' => $this->archivePhysicalQuery($site->archiveCompartments()->with('shelf.cabinet.rack.room'), $search, ['reference', 'name', 'code', 'status'])->paginate(5, ['*'], 'compartments_page')->withQueryString(),
            'boxes' => $this->archivePhysicalQuery($site->archiveBoxes()->with(['shelf.cabinet.rack.room', 'compartment.shelf.cabinet.rack.room'])->withCount(['containers', 'records']), $search, ['reference', 'name', 'code', 'status'])->paginate(5, ['*'], 'boxes_page')->withQueryString(),
            'roomOptions' => $site->archiveRooms()->orderBy('name')->get(),
            'rackOptions' => $site->archiveRacks()->with('room')->orderBy('name')->get(),
            'cabinetOptions' => $site->archiveCabinets()->with('rack.room')->orderBy('name')->get(),
            'shelfOptions' => $site->archiveShelves()->with('cabinet.rack.room')->orderBy('name')->get(),
            'compartmentOptions' => $site->archiveCompartments()->with('shelf.cabinet.rack.room')->orderBy('name')->get(),
            'search' => $search,
            'typeLabels' => $this->locationTypeLabels(),
            'statusLabels' => $this->locationStatusLabels(),
        ]);
    }

    public function storeLocation(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateLocation($request, $site);
        $location = $this->createStructuredLocation($site, $user, $validated);

        $this->logActivity($site, $user, $location, 'location_created', null, $location->status, __('main.archive_activity_location_created'));

        return redirect()->route('main.archiving.locations', [$company, $site])
            ->with('success', __('main.archive_location_created'));
    }

    public function updateLocation(Request $request, Company $company, CompanySite $site, string $type, int $location): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $model = $this->resolveStructuredLocation($site, $type, $location);

        if (! $model) {
            abort(404);
        }

        $validated = $this->validateLocationUpdate($request);
        $this->ensureStructuredLocationUpdateCapacity($site, $type, $model, $validated['capacity'] ?? null);

        $previousStatus = $model->status;
        $model->update($validated);

        $this->logActivity($site, $user, $model, 'location_updated', $previousStatus, $model->status, __('main.archive_activity_location_updated'));

        return redirect()->route('main.archiving.locations', [$company, $site, 'tab' => $this->locationTabFromType($type)])
            ->with('success', __('main.archive_location_updated'));
    }

    public function destroyLocation(Company $company, CompanySite $site, string $type, int $location): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $model = $this->resolveStructuredLocation($site, $type, $location);

        if (! $model) {
            abort(404);
        }

        if ($this->structuredLocationChildrenCount($type, $model) > 0) {
            return back()->withErrors(['location' => __('main.archive_location_delete_blocked')]);
        }

        $this->logActivity($site, $user, $model, 'location_deleted', $model->status, null, __('main.archive_activity_location_deleted'));
        $model->delete();

        return redirect()->route('main.archiving.locations', [$company, $site, 'tab' => $this->locationTabFromType($type)])
            ->with('success', __('main.archive_location_deleted'));
    }

    public function containers(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $containers = $site->archiveContainers()
            ->with(['box.shelf.cabinet.rack.room', 'box.compartment.shelf.cabinet.rack.room', 'location.parent'])
            ->withCount('records')
            ->when($search !== '', fn ($query) => $this->archiveSearch($query, $search, ['reference', 'title', 'category', 'owner_service', 'period_label', 'status']))
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.archiving.containers', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'containers' => $containers,
            'boxOptions' => $this->boxOptions($site),
            'search' => $search,
            'confidentialityLabels' => $this->confidentialityLabels(),
            'containerStatusLabels' => $this->containerStatusLabels(),
        ]);
    }

    public function storeContainer(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateContainer($request, $site);
        $container = $site->archiveContainers()->create(array_merge($validated, [
            'created_by' => $user->id,
            'reference' => $this->nextReference(ArchiveContainer::class, ArchiveContainer::REFERENCE_PREFIX),
        ]));

        $this->logActivity($site, $user, $container, 'container_created', null, $container->status, __('main.archive_activity_container_created'));

        return redirect()->route('main.archiving.containers', [$company, $site])
            ->with('success', __('main.archive_container_created'));
    }

    public function updateContainer(Request $request, Company $company, CompanySite $site, ArchiveContainer $container): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless((int) $container->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);

        $validated = $this->validateContainerUpdate($request);
        $recordsCount = $container->records()->count();

        if (($validated['capacity'] ?? null) !== null && (int) $validated['capacity'] < $recordsCount) {
            throw ValidationException::withMessages([
                'capacity' => __('main.archive_container_capacity_below_records', [
                    'requested' => (int) $validated['capacity'],
                    'records' => $recordsCount,
                ]),
            ]);
        }

        $previousStatus = $container->status;
        $container->update($validated);

        $this->logActivity($site, $user, $container, 'container_updated', $previousStatus, $container->status, __('main.archive_activity_container_updated'));

        return redirect()->route('main.archiving.containers', [$company, $site])
            ->with('success', __('main.archive_container_updated'));
    }

    public function destroyContainer(Company $company, CompanySite $site, ArchiveContainer $container): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless((int) $container->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);

        if ($container->records()->exists()) {
            return back()->withErrors(['container' => __('main.archive_container_delete_blocked')]);
        }

        $this->logActivity($site, $user, $container, 'container_deleted', $container->status, null, __('main.archive_activity_container_deleted'));
        $container->delete();

        return redirect()->route('main.archiving.containers', [$company, $site])
            ->with('success', __('main.archive_container_deleted'));
    }

    public function records(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $records = $site->archiveRecords()
            ->with(['container.box.shelf.cabinet.rack.room', 'container.box.compartment.shelf.cabinet.rack.room', 'box.shelf.cabinet.rack.room', 'box.compartment.shelf.cabinet.rack.room', 'location'])
            ->when($search !== '', fn ($query) => $this->archiveSearch($query, $search, ['reference', 'title', 'document_type', 'category', 'owner_service', 'status']))
            ->latest('archived_at')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.archiving.records', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'records' => $records,
            'boxOptions' => $this->boxOptions($site),
            'containerOptions' => $this->containerOptions($site),
            'search' => $search,
            'confidentialityLabels' => $this->confidentialityLabels(),
            'recordStatusLabels' => $this->recordStatusLabels(),
        ]);
    }

    public function storeRecord(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateRecord($request, $site);
        $validated['file_path'] = $this->storeArchiveFile($request);
        unset($validated['file']);

        if (filled($validated['archive_container_id'] ?? null) && blank($validated['archive_box_id'] ?? null)) {
            $validated['archive_box_id'] = ArchiveContainer::query()
                ->where('company_site_id', $site->id)
                ->whereKey($validated['archive_container_id'])
                ->value('archive_box_id');
        }

        $record = $site->archiveRecords()->create(array_merge($validated, [
            'created_by' => $user->id,
            'reference' => $this->nextReference(ArchiveRecord::class, ArchiveRecord::REFERENCE_PREFIX),
        ]));

        $this->logActivity($site, $user, $record, 'record_archived', null, $record->status, __('main.archive_activity_record_created'));

        return redirect()->route('main.archiving.records', [$company, $site])
            ->with('success', __('main.archive_record_created'));
    }

    public function updateRecord(Request $request, Company $company, CompanySite $site, ArchiveRecord $record): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless((int) $record->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);

        $validated = $this->validateRecordMetadata($request);
        $oldStatus = $record->status;
        $changes = $this->recordChangeSummary($record, $validated);

        $record->update($validated);

        $this->logActivity(
            $site,
            $user,
            $record,
            'record_updated',
            $oldStatus,
            $record->status,
            $changes !== '' ? __('main.archive_activity_record_updated_with_changes', ['changes' => $changes]) : __('main.archive_activity_record_updated')
        );

        return redirect()->route('main.archiving.records', [$company, $site])
            ->with('success', __('main.archive_record_updated'));
    }

    public function attachRecordFile(Request $request, Company $company, CompanySite $site, ArchiveRecord $record): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless((int) $record->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);

        if ($record->file_path) {
            return back()->withErrors(['file' => __('main.archive_record_file_already_attached')]);
        }

        $this->validateRecordFile($request, required: true);
        $filePath = $this->storeArchiveFile($request);

        $record->update(['file_path' => $filePath]);

        $this->logActivity($site, $user, $record, 'record_file_attached', $record->status, $record->status, __('main.archive_activity_record_file_attached'));

        return redirect()->route('main.archiving.records', [$company, $site])
            ->with('success', __('main.archive_record_file_attached'));
    }

    public function replaceRecordFile(Request $request, Company $company, CompanySite $site, ArchiveRecord $record): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless((int) $record->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);

        if (! $record->file_path) {
            return back()->withErrors(['file' => __('main.archive_record_file_missing_for_replace')]);
        }

        $validated = $this->validateRecordFileReplacement($request);
        $oldFilePath = $record->file_path;
        $newFilePath = $this->storeArchiveFile($request);

        DB::table('archive_record_file_revisions')->insert([
            'archive_record_id' => $record->id,
            'changed_by' => $user->id,
            'old_file_path' => $oldFilePath,
            'new_file_path' => $newFilePath,
            'reason' => $validated['replacement_reason'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record->update(['file_path' => $newFilePath]);

        $this->logActivity(
            $site,
            $user,
            $record,
            'record_file_replaced',
            $record->status,
            $record->status,
            __('main.archive_activity_record_file_replaced', ['reason' => $validated['replacement_reason']])
        );

        return redirect()->route('main.archiving.records', [$company, $site])
            ->with('success', __('main.archive_record_file_replaced'));
    }

    public function movements(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $movements = $site->archiveMovements()
            ->with([
                'record',
                'container',
                'fromBox.shelf.cabinet.rack.room',
                'fromBox.compartment.shelf.cabinet.rack.room',
                'toBox.shelf.cabinet.rack.room',
                'toBox.compartment.shelf.cabinet.rack.room',
                'actor',
            ])
            ->latest('moved_at')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.archiving.movements', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'movements' => $movements,
            'recordOptions' => $this->recordOptions($site),
            'containerOptions' => $this->containerOptions($site),
            'boxOptions' => $this->boxOptions($site),
        ]);
    }

    public function storeMovement(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $request->validate([
            'archive_record_id' => ['nullable', 'integer', Rule::exists('archive_records', 'id')->where('company_site_id', $site->id)],
            'archive_container_id' => ['nullable', 'integer', Rule::exists('archive_containers', 'id')->where('company_site_id', $site->id)],
            'from_archive_box_id' => ['nullable', 'integer', Rule::exists('archive_boxes', 'id')->where('company_site_id', $site->id)],
            'to_archive_box_id' => ['required', 'integer', Rule::exists('archive_boxes', 'id')->where('company_site_id', $site->id)],
            'moved_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (blank($validated['archive_record_id'] ?? null) && blank($validated['archive_container_id'] ?? null)) {
            return back()->withErrors(['archive_record_id' => __('main.archive_movement_target_required')])->withInput();
        }

        DB::transaction(function () use ($site, $user, $validated): void {
            if (blank($validated['from_archive_box_id'] ?? null)) {
                $validated['from_archive_box_id'] = filled($validated['archive_record_id'] ?? null)
                    ? ArchiveRecord::query()
                        ->where('company_site_id', $site->id)
                        ->whereKey($validated['archive_record_id'])
                        ->value('archive_box_id')
                    : ArchiveContainer::query()
                        ->where('company_site_id', $site->id)
                        ->whereKey($validated['archive_container_id'])
                        ->value('archive_box_id');
            }

            $movement = $site->archiveMovements()->create(array_merge($validated, [
                'actor_id' => $user->id,
                'reference' => $this->nextReference(ArchiveMovement::class, ArchiveMovement::REFERENCE_PREFIX),
                'moved_at' => $validated['moved_at'] ?? now(),
            ]));

            if ($movement->record) {
                $movement->record->update(['archive_box_id' => $movement->to_archive_box_id]);
                $this->logActivity($site, $user, $movement->record, 'record_moved', null, null, __('main.archive_activity_record_moved'));
            }

            if ($movement->container) {
                $movement->container->update(['archive_box_id' => $movement->to_archive_box_id]);
                $this->logActivity($site, $user, $movement->container, 'container_moved', null, null, __('main.archive_activity_container_moved'));
            }
        });

        return redirect()->route('main.archiving.movements', [$company, $site])
            ->with('success', __('main.archive_movement_created'));
    }

    public function retention(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $today = now()->toDateString();
        $next90Days = now()->addDays(90)->toDateString();
        $rulesQuery = $site->archiveRetentionRules();
        $expiringQuery = $site->archiveRecords()
            ->whereNotNull('retention_until')
            ->where('retention_until', '<=', $next90Days)
            ->where('status', '!=', ArchiveRecord::STATUS_DESTROYED);

        $rules = $site->archiveRetentionRules()
            ->orderBy('category')
            ->paginate(5)
            ->withQueryString();
        $expiringRecords = (clone $expiringQuery)
            ->orderBy('retention_until')
            ->limit(5)
            ->get();
        $nextExpiry = $site->archiveRecords()
            ->whereNotNull('retention_until')
            ->where('retention_until', '>=', $today)
            ->where('status', '!=', ArchiveRecord::STATUS_DESTROYED)
            ->orderBy('retention_until')
            ->first();

        return view('main.modules.archiving.retention', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'rules' => $rules,
            'expiringRecords' => $expiringRecords,
            'retentionStats' => [
                'rules' => (clone $rulesQuery)->count(),
                'activeRules' => (clone $rulesQuery)->where('status', ArchiveRetentionRule::STATUS_ACTIVE)->count(),
                'expiringSoon' => (clone $expiringQuery)->where('retention_until', '>=', $today)->count(),
                'expiredRecords' => $site->archiveRecords()
                    ->whereNotNull('retention_until')
                    ->where('retention_until', '<', $today)
                    ->where('status', '!=', ArchiveRecord::STATUS_DESTROYED)
                    ->count(),
                'nextExpiry' => $nextExpiry,
            ],
        ]);
    }

    public function storeRetentionRule(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'retention_years' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in([ArchiveRetentionRule::STATUS_ACTIVE, ArchiveRetentionRule::STATUS_INACTIVE])],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $site->archiveRetentionRules()->updateOrCreate(
            ['category' => $validated['category']],
            $validated,
        );

        return redirect()->route('main.archiving.retention', [$company, $site])
            ->with('success', __('main.archive_retention_saved'));
    }

    public function traceability(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $activities = $site->archiveActivities()
            ->with('actor:id,name,email')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.archiving.traceability', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'activities' => $activities,
            'activityActionLabels' => $this->archiveActivityActionLabels(),
            'activityStatusLabels' => array_merge(
                $this->locationStatusLabels(),
                $this->containerStatusLabels(),
                $this->recordStatusLabels(),
                [
                    ArchiveRetentionRule::STATUS_ACTIVE => __('main.status_active'),
                    ArchiveRetentionRule::STATUS_INACTIVE => __('main.status_inactive'),
                ],
            ),
        ]);
    }

    public function reports(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.archiving.reports', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
        ], $this->reportData($request, $site)));
    }

    public function printReport(Request $request, Company $company, CompanySite $site): Response|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        return Pdf::loadView('main.modules.archiving.pdf.reports', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
        ], $this->reportData($request, $site)))
            ->setPaper('a4', 'landscape')
            ->stream('rapport-archivage-'.$site->id.'-'.now()->format('Ymd').'.pdf');
    }

    public function notifications(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        AccountingActivityFeed::syncSite($site, CompanySite::MODULE_ARCHIVING);

        $status = $request->query('status', 'all');
        $notifications = AccountingNotification::query()
            ->with(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('company_site_id', $site->id)
            ->whereIn('module_key', AccountingActivityFeed::moduleKeys(CompanySite::MODULE_ARCHIVING))
            ->when($status === 'unread', fn ($query) => $query->whereDoesntHave('reads', fn ($readQuery) => $readQuery->where('user_id', $user->id)->whereNotNull('read_at')))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('main.modules.archiving.notifications', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'activeArchivingPage' => 'notifications',
            'notifications' => $notifications,
            'status' => $status,
        ]);
    }

    public function showNotification(Company $company, CompanySite $site, AccountingNotification $notification): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        abort_unless((int) $notification->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);
        abort_unless(in_array($notification->module_key, AccountingActivityFeed::moduleKeys(CompanySite::MODULE_ARCHIVING), true), Response::HTTP_NOT_FOUND);

        $notification->load(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]);
        $notification->markReadBy($user);

        return view('main.modules.archiving.notification-show', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'activeArchivingPage' => 'notifications',
            'notification' => $notification->fresh(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]),
            'moduleLabel' => $this->archivingModuleLabel($notification->module_key),
            'moduleUrl' => ArchivingModuleNavigation::urlForKey($notification->module_key, $company, $site),
        ]);
    }

    public function settings(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        abort_unless($user->isAdmin() || $user->isSuperadmin(), Response::HTTP_FORBIDDEN);

        $settings = AccountingModuleSetting::query()
            ->firstOrNew(['company_site_id' => $site->id], AccountingModuleSetting::defaults());
        $managedUsers = $site->users()
            ->where('users.role', User::ROLE_USER)
            ->where(fn ($query) => $query
                ->whereNull('company_site_user.module_permissions')
                ->orWhere('company_site_user.module_permissions', '')
                ->orWhere('company_site_user.module_permissions', 'like', '%"'.CompanySite::MODULE_ARCHIVING.'"%'))
            ->orderBy('users.name')
            ->paginate(1, ['users.*'], 'users_page')
            ->withQueryString();
        $menuKeys = ArchivingModuleNavigation::keys();
        $savedMenuPermissions = AccountingMenuPermission::query()
            ->where('company_site_id', $site->id)
            ->whereIn('user_id', $managedUsers->getCollection()->pluck('id'))
            ->whereIn('menu_key', $menuKeys)
            ->get()
            ->groupBy('user_id');
        $menuSelections = $managedUsers->getCollection()->mapWithKeys(function (User $account) use ($menuKeys, $savedMenuPermissions): array {
            $permissionRows = $savedMenuPermissions->get($account->id, collect());

            return [
                $account->id => $permissionRows->isEmpty()
                    ? $menuKeys
                    : $permissionRows->where('is_allowed', true)->pluck('menu_key')->all(),
            ];
        });

        return view('main.modules.archiving.settings', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_ARCHIVING,
            'moduleMeta' => $moduleMeta,
            'settings' => $settings,
            'managedUsers' => $managedUsers,
            'menuSelections' => $menuSelections,
            'menuGroups' => $this->settingsMenuGroups(),
        ]);
    }

    public function updateSettings(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->archivingAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless($user->isAdmin() || $user->isSuperadmin(), Response::HTTP_FORBIDDEN);

        $menuKeys = ArchivingModuleNavigation::keys();
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
                ->whereIn('menu_key', $menuKeys)
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

        return redirect()->route('main.archiving.settings', [$company, $site])
            ->with('success', __('main.archive_settings_saved'));
    }

    private function archivingAccess(Company $company, CompanySite $site): array|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $user->canManageCompany($company, 'can_view')) {
            return redirect()->route('main');
        }

        if (! in_array(CompanySite::MODULE_ARCHIVING, $this->availableSiteModulesForUser($user, $site), true)) {
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

    private function validateLocation(Request $request, CompanySite $site): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys($this->locationTypeLabels()))],
            'archive_room_id' => ['nullable', 'integer', Rule::exists('archive_rooms', 'id')->where('company_site_id', $site->id)],
            'archive_rack_id' => ['nullable', 'integer', Rule::exists('archive_racks', 'id')->where('company_site_id', $site->id)],
            'archive_cabinet_id' => ['nullable', 'integer', Rule::exists('archive_cabinets', 'id')->where('company_site_id', $site->id)],
            'archive_shelf_id' => ['nullable', 'integer', Rule::exists('archive_shelves', 'id')->where('company_site_id', $site->id)],
            'archive_compartment_id' => ['nullable', 'integer', Rule::exists('archive_compartments', 'id')->where('company_site_id', $site->id)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'status' => ['required', Rule::in(array_keys($this->locationStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateLocationUpdate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'status' => ['required', Rule::in(array_keys($this->locationStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateContainer(Request $request, CompanySite $site): array
    {
        return $request->validate([
            'archive_box_id' => ['required', 'integer', Rule::exists('archive_boxes', 'id')->where('company_site_id', $site->id)],
            'archive_location_id' => ['nullable', 'integer', Rule::exists('archive_locations', 'id')->where('company_site_id', $site->id)],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'owner_service' => ['nullable', 'string', 'max:100'],
            'period_label' => ['nullable', 'string', 'max:80'],
            'confidentiality_level' => ['required', Rule::in(array_keys($this->confidentialityLabels()))],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'status' => ['required', Rule::in(array_keys($this->containerStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateContainerUpdate(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'owner_service' => ['nullable', 'string', 'max:100'],
            'period_label' => ['nullable', 'string', 'max:80'],
            'confidentiality_level' => ['required', Rule::in(array_keys($this->confidentialityLabels()))],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'status' => ['required', Rule::in(array_keys($this->containerStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateRecord(Request $request, CompanySite $site): array
    {
        $validated = $request->validate([
            'archive_location_id' => ['nullable', 'integer', Rule::exists('archive_locations', 'id')->where('company_site_id', $site->id)],
            'archive_container_id' => ['nullable', 'integer', Rule::exists('archive_containers', 'id')->where('company_site_id', $site->id)],
            'archive_box_id' => ['nullable', 'integer', Rule::exists('archive_boxes', 'id')->where('company_site_id', $site->id)],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
            'owner_service' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date'],
            'archived_at' => ['nullable', 'date'],
            'retention_until' => ['nullable', 'date'],
            'confidentiality_level' => ['required', Rule::in(array_keys($this->confidentialityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->recordStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->validateRecordFile($request);

        return $validated;
    }

    private function validateRecordMetadata(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
            'owner_service' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date'],
            'retention_until' => ['nullable', 'date'],
            'confidentiality_level' => ['required', Rule::in(array_keys($this->confidentialityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->recordStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateRecordFile(Request $request, bool $required = false): array
    {
        return $request->validate([
            'file' => [
                $required ? 'required' : 'nullable',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png',
            ],
        ]);
    }

    private function validateRecordFileReplacement(Request $request): array
    {
        return $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png'],
            'replacement_reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);
    }

    private function storeArchiveFile(Request $request): ?string
    {
        return $request->hasFile('file')
            ? $request->file('file')->store('archives', 'public')
            : null;
    }

    private function recordChangeSummary(ArchiveRecord $record, array $validated): string
    {
        $labels = [
            'title' => __('main.archive_title_label'),
            'document_type' => __('main.document_type'),
            'category' => __('main.category'),
            'owner_service' => __('main.owner_service'),
            'document_date' => __('main.document_date'),
            'retention_until' => __('main.expiration_date'),
            'confidentiality_level' => __('main.confidentiality'),
            'status' => __('main.status'),
            'description' => __('main.description'),
        ];

        return collect($validated)
            ->filter(fn ($value, string $field): bool => ($this->formatRecordChangeValue($field, $record->getAttribute($field)) ?? '') !== ($this->formatRecordChangeValue($field, $value) ?? ''))
            ->map(fn ($value, string $field): string => ($labels[$field] ?? $field).' : '.($this->formatRecordChangeValue($field, $record->getAttribute($field)) ?: '-').' -> '.($this->formatRecordChangeValue($field, $value) ?: '-'))
            ->implode(' ; ');
    }

    private function formatRecordChangeValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($field, ['document_date', 'retention_until'], true)) {
            return rescue(fn () => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y'), (string) $value, report: false);
        }

        if ($field === 'status') {
            return $this->recordStatusLabels()[$value] ?? (string) $value;
        }

        if ($field === 'confidentiality_level') {
            return $this->confidentialityLabels()[$value] ?? (string) $value;
        }

        return Str::limit((string) $value, 80);
    }

    private function archiveSearch($query, string $search, array $columns): void
    {
        $like = '%'.$search.'%';

        $query->where(function ($subQuery) use ($columns, $like): void {
            foreach ($columns as $column) {
                $subQuery->orWhere($column, 'like', $like);
            }
        });
    }

    private function archivePhysicalQuery($query, string $search, array $columns)
    {
        return $query
            ->when($search !== '', fn ($builder) => $this->archiveSearch($builder, $search, $columns))
            ->orderBy('name');
    }

    private function createStructuredLocation(CompanySite $site, User $user, array $validated): object
    {
        $this->ensureStructuredLocationCapacity($site, $validated);

        $payload = [
            'created_by' => $user->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ];

        return match ($validated['type']) {
            ArchiveLocation::TYPE_ROOM => $site->archiveRooms()->create(array_merge($payload, [
                'reference' => $this->nextReference(ArchiveRoom::class, ArchiveRoom::REFERENCE_PREFIX),
            ])),
            ArchiveLocation::TYPE_ZONE => $site->archiveRacks()->create(array_merge($payload, [
                'archive_room_id' => $validated['archive_room_id'] ?: throw ValidationException::withMessages(['archive_room_id' => __('main.archive_room_required')]),
                'reference' => $this->nextReference(ArchiveRack::class, ArchiveRack::REFERENCE_PREFIX),
            ])),
            ArchiveLocation::TYPE_CABINET => $site->archiveCabinets()->create(array_merge($payload, [
                'archive_rack_id' => $validated['archive_rack_id'] ?: throw ValidationException::withMessages(['archive_rack_id' => __('main.archive_rack_required')]),
                'reference' => $this->nextReference(ArchiveCabinet::class, ArchiveCabinet::REFERENCE_PREFIX),
            ])),
            ArchiveLocation::TYPE_SHELF => $site->archiveShelves()->create(array_merge($payload, [
                'archive_cabinet_id' => $validated['archive_cabinet_id'] ?: throw ValidationException::withMessages(['archive_cabinet_id' => __('main.archive_cabinet_required')]),
                'reference' => $this->nextReference(ArchiveShelf::class, ArchiveShelf::REFERENCE_PREFIX),
            ])),
            ArchiveLocation::TYPE_COMPARTMENT => $site->archiveCompartments()->create(array_merge($payload, [
                'archive_shelf_id' => $validated['archive_shelf_id'] ?: throw ValidationException::withMessages(['archive_shelf_id' => __('main.archive_shelf_required')]),
                'reference' => $this->nextReference(ArchiveCompartment::class, ArchiveCompartment::REFERENCE_PREFIX),
            ])),
            ArchiveLocation::TYPE_BOX => $site->archiveBoxes()->create(array_merge($payload, [
                'archive_shelf_id' => $validated['archive_compartment_id'] ? null : ($validated['archive_shelf_id'] ?: throw ValidationException::withMessages(['archive_shelf_id' => __('main.archive_box_parent_required')])),
                'archive_compartment_id' => $validated['archive_compartment_id'] ?: null,
                'reference' => $this->nextReference(ArchiveBox::class, ArchiveBox::REFERENCE_PREFIX),
            ])),
        };
    }

    private function ensureStructuredLocationCapacity(CompanySite $site, array $validated): void
    {
        $capacity = $validated['capacity'] ?? null;

        if (blank($capacity) || $validated['type'] === ArchiveLocation::TYPE_ROOM) {
            return;
        }

        $capacity = (int) $capacity;

        match ($validated['type']) {
            ArchiveLocation::TYPE_ZONE => $this->ensureParentCapacity(
                ArchiveRoom::query()->where('company_site_id', $site->id)->find($validated['archive_room_id']),
                __('main.archive_room'),
                __('main.archive_rack'),
                $capacity,
                ArchiveRack::query()->where('company_site_id', $site->id)->where('archive_room_id', $validated['archive_room_id'])->sum('capacity'),
                'archive_room_id',
            ),
            ArchiveLocation::TYPE_CABINET => $this->ensureParentCapacity(
                ArchiveRack::query()->where('company_site_id', $site->id)->find($validated['archive_rack_id']),
                __('main.archive_rack'),
                __('main.archive_cabinet'),
                $capacity,
                ArchiveCabinet::query()->where('company_site_id', $site->id)->where('archive_rack_id', $validated['archive_rack_id'])->sum('capacity'),
                'archive_rack_id',
            ),
            ArchiveLocation::TYPE_SHELF => $this->ensureParentCapacity(
                ArchiveCabinet::query()->where('company_site_id', $site->id)->find($validated['archive_cabinet_id']),
                __('main.archive_cabinet'),
                __('main.archive_shelf'),
                $capacity,
                ArchiveShelf::query()->where('company_site_id', $site->id)->where('archive_cabinet_id', $validated['archive_cabinet_id'])->sum('capacity'),
                'archive_cabinet_id',
            ),
            ArchiveLocation::TYPE_COMPARTMENT => $this->ensureParentCapacity(
                ArchiveShelf::query()->where('company_site_id', $site->id)->find($validated['archive_shelf_id']),
                __('main.archive_shelf'),
                __('main.archive_compartment'),
                $capacity,
                $this->usedShelfCapacity($site, (int) $validated['archive_shelf_id']),
                'archive_shelf_id',
            ),
            ArchiveLocation::TYPE_BOX => filled($validated['archive_compartment_id'] ?? null)
                ? $this->ensureParentCapacity(
                    ArchiveCompartment::query()->where('company_site_id', $site->id)->find($validated['archive_compartment_id']),
                    __('main.archive_compartment'),
                    __('main.archive_box'),
                    $capacity,
                    ArchiveBox::query()->where('company_site_id', $site->id)->where('archive_compartment_id', $validated['archive_compartment_id'])->sum('capacity'),
                    'archive_compartment_id',
                )
                : $this->ensureParentCapacity(
                    ArchiveShelf::query()->where('company_site_id', $site->id)->find($validated['archive_shelf_id']),
                    __('main.archive_shelf'),
                    __('main.archive_box'),
                    $capacity,
                    $this->usedShelfCapacity($site, (int) $validated['archive_shelf_id']),
                    'archive_shelf_id',
                ),
            default => null,
        };
    }

    private function ensureStructuredLocationUpdateCapacity(CompanySite $site, string $type, object $model, mixed $capacity): void
    {
        if (blank($capacity)) {
            return;
        }

        $capacity = (int) $capacity;
        $childrenCapacity = $this->structuredLocationChildrenCapacity($site, $type, $model);

        if ($capacity < $childrenCapacity) {
            throw ValidationException::withMessages([
                'capacity' => __('main.archive_capacity_below_children', [
                    'children' => $childrenCapacity,
                    'requested' => $capacity,
                ]),
            ]);
        }

        match ($type) {
            ArchiveLocation::TYPE_ZONE => $this->ensureParentCapacity(
                $model->room,
                __('main.archive_room'),
                __('main.archive_rack'),
                $capacity,
                ArchiveRack::query()
                    ->where('company_site_id', $site->id)
                    ->where('archive_room_id', $model->archive_room_id)
                    ->whereKeyNot($model->id)
                    ->sum('capacity'),
                'capacity',
            ),
            ArchiveLocation::TYPE_CABINET => $this->ensureParentCapacity(
                $model->rack,
                __('main.archive_rack'),
                __('main.archive_cabinet'),
                $capacity,
                ArchiveCabinet::query()
                    ->where('company_site_id', $site->id)
                    ->where('archive_rack_id', $model->archive_rack_id)
                    ->whereKeyNot($model->id)
                    ->sum('capacity'),
                'capacity',
            ),
            ArchiveLocation::TYPE_SHELF => $this->ensureParentCapacity(
                $model->cabinet,
                __('main.archive_cabinet'),
                __('main.archive_shelf'),
                $capacity,
                ArchiveShelf::query()
                    ->where('company_site_id', $site->id)
                    ->where('archive_cabinet_id', $model->archive_cabinet_id)
                    ->whereKeyNot($model->id)
                    ->sum('capacity'),
                'capacity',
            ),
            ArchiveLocation::TYPE_COMPARTMENT => $this->ensureParentCapacity(
                $model->shelf,
                __('main.archive_shelf'),
                __('main.archive_compartment'),
                $capacity,
                $this->usedShelfCapacity($site, (int) $model->archive_shelf_id, $model->id, null),
                'capacity',
            ),
            ArchiveLocation::TYPE_BOX => $model->archive_compartment_id
                ? $this->ensureParentCapacity(
                    $model->compartment,
                    __('main.archive_compartment'),
                    __('main.archive_box'),
                    $capacity,
                    ArchiveBox::query()
                        ->where('company_site_id', $site->id)
                        ->where('archive_compartment_id', $model->archive_compartment_id)
                        ->whereKeyNot($model->id)
                        ->sum('capacity'),
                    'capacity',
                )
                : $this->ensureParentCapacity(
                    $model->shelf,
                    __('main.archive_shelf'),
                    __('main.archive_box'),
                    $capacity,
                    $this->usedShelfCapacity($site, (int) $model->archive_shelf_id, null, $model->id),
                    'capacity',
                ),
            default => null,
        };
    }

    private function ensureParentCapacity($parent, string $parentLabel, string $childLabel, int $capacity, int|float|string|null $usedCapacity, string $errorKey): void
    {
        if (! $parent || blank($parent->capacity)) {
            return;
        }

        $available = max(0, (int) $parent->capacity - (int) $usedCapacity);

        if ($capacity <= $available) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => __('main.archive_capacity_exceeded', [
                'child' => $childLabel,
                'parent' => $parentLabel,
                'available' => $available,
                'requested' => $capacity,
            ]),
        ]);
    }

    private function usedShelfCapacity(CompanySite $site, int $shelfId, ?int $exceptCompartmentId = null, ?int $exceptBoxId = null): int
    {
        return (int) ArchiveCompartment::query()
            ->where('company_site_id', $site->id)
            ->where('archive_shelf_id', $shelfId)
            ->when($exceptCompartmentId, fn ($query) => $query->whereKeyNot($exceptCompartmentId))
            ->sum('capacity')
            + (int) ArchiveBox::query()
                ->where('company_site_id', $site->id)
                ->where('archive_shelf_id', $shelfId)
                ->whereNull('archive_compartment_id')
                ->when($exceptBoxId, fn ($query) => $query->whereKeyNot($exceptBoxId))
                ->sum('capacity');
    }

    private function resolveStructuredLocation(CompanySite $site, string $type, int $location): ?object
    {
        return match ($type) {
            ArchiveLocation::TYPE_ROOM => ArchiveRoom::query()->where('company_site_id', $site->id)->find($location),
            ArchiveLocation::TYPE_ZONE => ArchiveRack::query()->with('room')->where('company_site_id', $site->id)->find($location),
            ArchiveLocation::TYPE_CABINET => ArchiveCabinet::query()->with('rack')->where('company_site_id', $site->id)->find($location),
            ArchiveLocation::TYPE_SHELF => ArchiveShelf::query()->with('cabinet')->where('company_site_id', $site->id)->find($location),
            ArchiveLocation::TYPE_COMPARTMENT => ArchiveCompartment::query()->with('shelf')->where('company_site_id', $site->id)->find($location),
            ArchiveLocation::TYPE_BOX => ArchiveBox::query()->with(['shelf', 'compartment'])->where('company_site_id', $site->id)->find($location),
            default => null,
        };
    }

    private function structuredLocationChildrenCount(string $type, object $model): int
    {
        return match ($type) {
            ArchiveLocation::TYPE_ROOM => $model->racks()->count(),
            ArchiveLocation::TYPE_ZONE => $model->cabinets()->count(),
            ArchiveLocation::TYPE_CABINET => $model->shelves()->count(),
            ArchiveLocation::TYPE_SHELF => $model->compartments()->count() + $model->boxes()->count(),
            ArchiveLocation::TYPE_COMPARTMENT => $model->boxes()->count(),
            ArchiveLocation::TYPE_BOX => $model->containers()->count() + $model->records()->count(),
            default => 0,
        };
    }

    private function structuredLocationChildrenCapacity(CompanySite $site, string $type, object $model): int
    {
        return match ($type) {
            ArchiveLocation::TYPE_ROOM => (int) ArchiveRack::query()->where('company_site_id', $site->id)->where('archive_room_id', $model->id)->sum('capacity'),
            ArchiveLocation::TYPE_ZONE => (int) ArchiveCabinet::query()->where('company_site_id', $site->id)->where('archive_rack_id', $model->id)->sum('capacity'),
            ArchiveLocation::TYPE_CABINET => (int) ArchiveShelf::query()->where('company_site_id', $site->id)->where('archive_cabinet_id', $model->id)->sum('capacity'),
            ArchiveLocation::TYPE_SHELF => $this->usedShelfCapacity($site, $model->id),
            ArchiveLocation::TYPE_COMPARTMENT => (int) ArchiveBox::query()->where('company_site_id', $site->id)->where('archive_compartment_id', $model->id)->sum('capacity'),
            ArchiveLocation::TYPE_BOX => $model->containers()->count() + $model->records()->count(),
            default => 0,
        };
    }

    private function locationTabFromType(string $type): string
    {
        return match ($type) {
            ArchiveLocation::TYPE_ZONE => 'rack',
            ArchiveLocation::TYPE_ROOM => 'room',
            ArchiveLocation::TYPE_CABINET => 'cabinet',
            ArchiveLocation::TYPE_SHELF => 'shelf',
            ArchiveLocation::TYPE_COMPARTMENT => 'compartment',
            ArchiveLocation::TYPE_BOX => 'box',
            default => 'room',
        };
    }

    private function physicalPathForBox(?ArchiveBox $box): string
    {
        if (! $box) {
            return '-';
        }

        $shelf = $box->compartment?->shelf ?? $box->shelf;
        $cabinet = $shelf?->cabinet;
        $rack = $cabinet?->rack;
        $room = $rack?->room;

        return collect([
            $room?->name,
            $rack?->name,
            $cabinet?->name,
            $shelf?->name,
            $box->compartment?->name,
            $box->name,
        ])->filter()->implode(' / ');
    }

    private function dashboardData(CompanySite $site): array
    {
        $records = $site->archiveRecords()->with(['container.box.shelf.cabinet.rack.room', 'box.shelf.cabinet.rack.room', 'box.compartment.shelf.cabinet.rack.room'])->latest()->get();
        $rooms = $site->archiveRooms()->get();
        $racks = $site->archiveRacks()->get();
        $cabinets = $site->archiveCabinets()->get();
        $shelves = $site->archiveShelves()->get();
        $compartments = $site->archiveCompartments()->get();
        $boxes = $site->archiveBoxes()->withCount(['containers', 'records'])->get();
        $locationCount = $rooms->count() + $racks->count() + $cabinets->count() + $shelves->count() + $compartments->count() + $boxes->count();
        $containers = $site->archiveContainers()->withCount('records')->get();
        $expiring = $records->filter(fn (ArchiveRecord $record): bool => $record->retention_until !== null
            && $record->retention_until->between(now()->startOfDay(), now()->addDays(90)));
        $capacity = (int) $boxes->sum('capacity');
        $occupied = (int) $boxes->sum(fn (ArchiveBox $box): int => $box->containers_count + $box->records_count);

        return [
            'metrics' => [
                ['label' => __('main.archive_total_records'), 'value' => $records->count(), 'meta' => __('main.archive_records_archived'), 'icon' => 'bi-archive', 'tone' => 'blue'],
                ['label' => __('main.archive_physical_locations'), 'value' => $locationCount, 'meta' => __('main.archive_rooms_and_boxes'), 'icon' => 'bi-building', 'tone' => 'green'],
                ['label' => __('main.archive_containers'), 'value' => $containers->count(), 'meta' => __('main.archive_classers'), 'icon' => 'bi-folder2-open', 'tone' => 'amber'],
                ['label' => __('main.archive_expiring_soon'), 'value' => $expiring->count(), 'meta' => __('main.archive_next_90_days'), 'icon' => 'bi-hourglass-split', 'tone' => 'rose'],
            ],
            'recentRecords' => $records->take(5),
            'locations' => $boxes,
            'containers' => $containers,
            'expiringRecords' => $expiring->take(5),
            'priorityLocations' => $boxes->sortBy('name')->take(6),
            'recentActivities' => $site->archiveActivities()->with('actor')->latest()->take(5)->get(),
            'recordStatusRows' => $records->groupBy('status')
                ->map(fn ($rows, string $status): array => [
                    'label' => $this->recordStatusLabels()[$status] ?? $status,
                    'count' => $rows->count(),
                    'status' => $status,
                ])->values(),
            'locationTypeRows' => collect([
                ['label' => __('main.archive_rooms'), 'count' => $rooms->count(), 'type' => ArchiveLocation::TYPE_ROOM],
                ['label' => __('main.archive_racks'), 'count' => $racks->count(), 'type' => ArchiveLocation::TYPE_ZONE],
                ['label' => __('main.archive_cabinets'), 'count' => $cabinets->count(), 'type' => ArchiveLocation::TYPE_CABINET],
                ['label' => __('main.archive_shelves'), 'count' => $shelves->count(), 'type' => ArchiveLocation::TYPE_SHELF],
                ['label' => __('main.archive_compartments'), 'count' => $compartments->count(), 'type' => ArchiveLocation::TYPE_COMPARTMENT],
                ['label' => __('main.archive_boxes'), 'count' => $boxes->count(), 'type' => ArchiveLocation::TYPE_BOX],
            ]),
            'capacity' => [
                'total' => $capacity,
                'occupied' => $occupied,
                'percent' => $capacity > 0 ? min(100, (int) round(($occupied / $capacity) * 100)) : 0,
            ],
            'risks' => [
                'fullLocations' => $boxes->where('status', ArchiveRoom::STATUS_FULL)->count(),
                'sealedContainers' => $containers->where('status', ArchiveContainer::STATUS_SEALED)->count(),
                'expiredRecords' => $records->where('status', ArchiveRecord::STATUS_EXPIRED)->count(),
            ],
            'locationTypeLabels' => $this->locationTypeLabels(),
            'locationStatusLabels' => $this->locationStatusLabels(),
            'recordStatusLabels' => $this->recordStatusLabels(),
        ];
    }

    private function reportData(Request $request, CompanySite $site): array
    {
        $records = $site->archiveRecords()->with(['container.box', 'box'])->get();
        $boxes = $site->archiveBoxes()->withCount(['containers', 'records'])->with(['shelf.cabinet.rack.room', 'compartment.shelf.cabinet.rack.room'])->get();
        $containers = $site->archiveContainers()->withCount('records')->get();

        return [
            'pdfSettings' => AccountingModuleSetting::query()
                ->firstOrNew(['company_site_id' => $site->id], AccountingModuleSetting::defaults()),
            'metrics' => $this->dashboardData($site)['metrics'],
            'typeRows' => $records->groupBy(fn (ArchiveRecord $record): string => $record->document_type ?: __('main.not_defined'))
                ->map(fn ($rows, string $label): array => ['label' => $label, 'count' => $rows->count()])
                ->values(),
            'statusRows' => $records->groupBy('status')
                ->map(fn ($rows, string $status): array => [
                    'label' => $this->recordStatusLabels()[$status] ?? $status,
                    'count' => $rows->count(),
                    'status' => $status,
                ])
                ->values(),
            'locationRows' => $boxes->map(fn (ArchiveBox $box): array => [
                'label' => $box->physical_path,
                'name' => $box->name,
                'type' => __('main.archive_box'),
                'containers' => $box->containers_count,
                'records' => $box->records_count,
                'status' => $this->locationStatusLabels()[$box->status] ?? $box->status,
                'status_key' => $box->status,
            ]),
            'containerRows' => $containers->map(fn (ArchiveContainer $container): array => [
                'label' => $container->title,
                'category' => $container->category ?: '-',
                'records' => $container->records_count,
                'status' => $this->containerStatusLabels()[$container->status] ?? $container->status,
                'status_key' => $container->status,
            ]),
        ];
    }

    private function locationOptions(CompanySite $site)
    {
        return $site->archiveLocations()->orderBy('type')->orderBy('name')->get();
    }

    private function boxOptions(CompanySite $site)
    {
        return $site->archiveBoxes()
            ->with(['shelf.cabinet.rack.room', 'compartment.shelf.cabinet.rack.room'])
            ->orderBy('name')
            ->get()
            ->map(function (ArchiveBox $box): ArchiveBox {
                $box->setAttribute('physical_path', $this->physicalPathForBox($box));

                return $box;
            });
    }

    private function hierarchicalLocationRows(CompanySite $site, string $search = '')
    {
        $locations = $site->archiveLocations()
            ->with('parent')
            ->withCount(['children', 'containers', 'records'])
            ->get();

        $byParent = $locations->groupBy(fn (ArchiveLocation $location): int => (int) ($location->parent_id ?: 0));
        $typeOrder = $this->locationTypeOrder();
        $rows = collect();
        $visited = [];

        $sortLocations = fn ($items) => $items
            ->sortBy(fn (ArchiveLocation $location): string => str_pad((string) ($typeOrder[$location->type] ?? 99), 2, '0', STR_PAD_LEFT).'|'.mb_strtolower($location->name))
            ->values();

        $walk = function (int $parentId, int $depth, array $trail) use (&$walk, &$rows, &$visited, $byParent, $sortLocations): void {
            foreach ($sortLocations($byParent->get($parentId, collect())) as $location) {
                if (isset($visited[$location->id])) {
                    continue;
                }

                $visited[$location->id] = true;
                $path = array_merge($trail, [$location->name]);

                $location->setAttribute('tree_depth', $depth);
                $location->setAttribute('tree_path', implode(' / ', $path));
                $location->setAttribute('tree_parent_path', $trail === [] ? __('main.archive_root_location') : implode(' / ', $trail));

                $rows->push($location);
                $walk((int) $location->id, $depth + 1, $path);
            }
        };

        $walk(0, 0, []);

        $locations->whereNotIn('id', array_keys($visited))->each(function (ArchiveLocation $location) use ($rows): void {
            $location->setAttribute('tree_depth', 0);
            $location->setAttribute('tree_path', $location->name);
            $location->setAttribute('tree_parent_path', __('main.archive_orphan_location'));
            $rows->push($location);
        });

        if ($search === '') {
            return $rows->values();
        }

        $needle = Str::lower($search);

        return $rows
            ->filter(fn (ArchiveLocation $location): bool => Str::contains(Str::lower(implode(' ', [
                $location->reference,
                $location->name,
                $location->code,
                $location->type,
                $location->status,
                $location->tree_path,
            ])), $needle))
            ->values();
    }

    private function allowedParentTypesByLocationType(): array
    {
        return [
            ArchiveLocation::TYPE_ROOM => [],
            ArchiveLocation::TYPE_ZONE => [ArchiveLocation::TYPE_ROOM],
            ArchiveLocation::TYPE_CABINET => [ArchiveLocation::TYPE_ZONE],
            ArchiveLocation::TYPE_SHELF => [ArchiveLocation::TYPE_CABINET],
            ArchiveLocation::TYPE_COMPARTMENT => [ArchiveLocation::TYPE_SHELF],
            ArchiveLocation::TYPE_BOX => [ArchiveLocation::TYPE_SHELF, ArchiveLocation::TYPE_COMPARTMENT],
        ];
    }

    private function locationTypeOrder(): array
    {
        return [
            ArchiveLocation::TYPE_ROOM => 1,
            ArchiveLocation::TYPE_ZONE => 2,
            ArchiveLocation::TYPE_CABINET => 3,
            ArchiveLocation::TYPE_SHELF => 4,
            ArchiveLocation::TYPE_COMPARTMENT => 5,
            ArchiveLocation::TYPE_BOX => 6,
        ];
    }

    private function containerOptions(CompanySite $site)
    {
        return $site->archiveContainers()->orderBy('title')->get();
    }

    private function recordOptions(CompanySite $site)
    {
        return $site->archiveRecords()->orderBy('title')->get();
    }

    private function locationTypeLabels(): array
    {
        return [
            ArchiveLocation::TYPE_ROOM => __('main.archive_location_type_room'),
            ArchiveLocation::TYPE_ZONE => __('main.archive_location_type_zone'),
            ArchiveLocation::TYPE_CABINET => __('main.archive_location_type_cabinet'),
            ArchiveLocation::TYPE_SHELF => __('main.archive_location_type_shelf'),
            ArchiveLocation::TYPE_COMPARTMENT => __('main.archive_location_type_compartment'),
            ArchiveLocation::TYPE_BOX => __('main.archive_location_type_box'),
        ];
    }

    private function locationStatusLabels(): array
    {
        return [
            ArchiveLocation::STATUS_ACTIVE => __('main.status_active'),
            ArchiveLocation::STATUS_FULL => __('main.archive_status_full'),
            ArchiveLocation::STATUS_INACTIVE => __('main.status_inactive'),
        ];
    }

    private function confidentialityLabels(): array
    {
        return [
            ArchiveContainer::CONFIDENTIALITY_PUBLIC => __('main.archive_confidentiality_public'),
            ArchiveContainer::CONFIDENTIALITY_INTERNAL => __('main.archive_confidentiality_internal'),
            ArchiveContainer::CONFIDENTIALITY_CONFIDENTIAL => __('main.archive_confidentiality_confidential'),
            ArchiveContainer::CONFIDENTIALITY_SECRET => __('main.archive_confidentiality_secret'),
        ];
    }

    private function containerStatusLabels(): array
    {
        return [
            ArchiveContainer::STATUS_ACTIVE => __('main.status_active'),
            ArchiveContainer::STATUS_COMPLETE => __('main.archive_status_complete'),
            ArchiveContainer::STATUS_SEALED => __('main.archive_status_sealed'),
            ArchiveContainer::STATUS_TRANSFERRED => __('main.archive_status_transferred'),
            ArchiveContainer::STATUS_DESTROYED => __('main.archive_status_destroyed'),
        ];
    }

    private function recordStatusLabels(): array
    {
        return [
            ArchiveRecord::STATUS_ARCHIVED => __('main.archive_status_archived'),
            ArchiveRecord::STATUS_REVIEW => __('main.archive_status_review'),
            ArchiveRecord::STATUS_EXPIRED => __('main.archive_status_expired'),
            ArchiveRecord::STATUS_DESTROYED => __('main.archive_status_destroyed'),
            ArchiveRecord::STATUS_MISTAKEN => __('main.archive_status_mistaken'),
        ];
    }

    private function archiveActivityActionLabels(): array
    {
        return [
            'location_created' => __('main.archive_action_location_created'),
            'location_updated' => __('main.archive_action_location_updated'),
            'location_deleted' => __('main.archive_action_location_deleted'),
            'container_created' => __('main.archive_action_container_created'),
            'container_updated' => __('main.archive_action_container_updated'),
            'container_deleted' => __('main.archive_action_container_deleted'),
            'record_archived' => __('main.archive_action_record_archived'),
            'record_updated' => __('main.archive_action_record_updated'),
            'record_file_attached' => __('main.archive_action_record_file_attached'),
            'record_file_replaced' => __('main.archive_action_record_file_replaced'),
            'record_moved' => __('main.archive_action_record_moved'),
            'container_moved' => __('main.archive_action_container_moved'),
        ];
    }

    private function archivingModuleLabel(?string $moduleKey): string
    {
        return match ($moduleKey) {
            'archive-locations' => __('main.archive_locations'),
            'archive-containers' => __('main.archive_containers'),
            'archive-records' => __('main.archive_records'),
            'archive-movements' => __('main.archive_movements'),
            'archive-retention' => __('main.archive_retention'),
            'archive-traceability' => __('main.archive_traceability'),
            default => $moduleKey ? str($moduleKey)->replace('-', ' ')->headline()->toString() : '-',
        };
    }

    private function settingsMenuGroups(): array
    {
        $labels = [
            'archive-dashboard' => __('main.dashboard'),
            'archive-locations' => __('main.archive_locations'),
            'archive-containers' => __('main.archive_containers'),
            'archive-records' => __('main.archive_records'),
            'archive-movements' => __('main.archive_movements'),
            'archive-retention' => __('main.archive_retention'),
            'archive-traceability' => __('main.archive_traceability'),
            'archive-reports' => __('main.archive_reports'),
        ];
        $groupLabels = [
            'physical' => __('main.archive_physical_structure'),
            'records' => __('main.archive_records_management'),
            'governance' => __('main.archive_governance'),
        ];

        return collect(ArchivingModuleNavigation::GROUPS)
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

    private function nextReference(string $modelClass, string $prefix): string
    {
        $nextId = ((int) ($modelClass::query()->max('id') ?? 0)) + 1;

        do {
            $reference = $prefix.'-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while ($modelClass::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function logActivity(CompanySite $site, ?User $actor, object $subject, string $action, ?string $fromStatus, ?string $toStatus, ?string $comment): void
    {
        ArchiveActivity::query()->create([
            'company_site_id' => $site->id,
            'actor_id' => $actor?->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id ?? null,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
        ]);
    }

    private function moduleMeta(): array
    {
        return [
            'label' => __('main.module_archiving'),
            'description' => __('main.module_archiving_description'),
            'icon' => 'bi-archive',
            'tone' => 'amber',
            'class' => 'module-archiving',
        ];
    }
}

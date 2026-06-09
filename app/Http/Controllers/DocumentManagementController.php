<?php

namespace App\Http\Controllers;

use App\Models\AccountingMenuPermission;
use App\Models\AccountingModuleSetting;
use App\Models\AccountingNotification;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\DocumentManagementActivity;
use App\Models\DocumentManagementFolder;
use App\Models\DocumentManagementRecord;
use App\Models\DocumentManagementValidationCircuit;
use App\Models\DocumentManagementValidationRequest;
use App\Models\User;
use App\Support\AccountingActivityFeed;
use App\Support\DocumentManagementModuleNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class DocumentManagementController extends Controller
{
    public function dashboard(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.document-management.dashboard', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'documentManagementDashboard' => $this->dashboardData($site),
        ]);
    }

    public function incoming(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $filters = $this->incomingFilters($request);
        $folders = $site->documentManagementFolders()
            ->where('status', DocumentManagementFolder::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
        $assignees = $this->siteAssignees($site);

        $records = $site->documentManagementRecords()
            ->with(['folder', 'assignee:id,name,email'])
            ->where('record_type', DocumentManagementRecord::TYPE_INCOMING)
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['q'].'%';

                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('reference', 'like', $search)
                        ->orWhere('subject', 'like', $search)
                        ->orWhere('sender', 'like', $search)
                        ->orWhere('category', 'like', $search);
                });
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['priority'] !== 'all', fn ($query) => $query->where('priority', $filters['priority']))
            ->when($filters['folder'] !== 'all', fn ($query) => $query->where('document_management_folder_id', $filters['folder']))
            ->when($filters['assignee'] !== 'all', function ($query) use ($filters): void {
                $filters['assignee'] === 'unassigned'
                    ? $query->whereNull('assigned_to')
                    : $query->where('assigned_to', $filters['assignee']);
            })
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('received_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('received_at', '<=', $filters['date_to']))
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.incoming', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'records' => $records,
            'filters' => $filters,
            'folders' => $folders,
            'assignees' => $assignees,
            'metrics' => $this->incomingMetrics($site),
            'statusLabels' => $this->incomingStatusLabels(),
            'priorityLabels' => $this->priorityLabels(),
        ]);
    }

    public function storeIncoming(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateIncoming($request, $site);
        $validated['file_path'] = $this->storeAttachment($request);
        unset($validated['attachment']);

        $record = $site->documentManagementRecords()->create(array_merge($validated, [
            'created_by' => $user->id,
            'reference' => $this->nextDocumentReference(),
            'record_type' => DocumentManagementRecord::TYPE_INCOMING,
            'direction' => DocumentManagementRecord::TYPE_INCOMING,
            'recipient' => $site->name,
        ]));

        $this->logRecordActivity($record, $user, 'registered', null, $record->status, __('main.ged_activity_incoming_registered'));

        return redirect()
            ->route('main.document-management.incoming', [$company, $site])
            ->with('success', __('main.ged_incoming_created'));
    }

    public function updateIncoming(Request $request, Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->incomingRecordBelongsToSite($record, $site)) {
            abort(404);
        }

        $previousStatus = $record->status;
        $validated = $this->validateIncoming($request, $site, $record);
        $validated['file_path'] = $this->storeAttachment($request, $record);
        unset($validated['attachment']);

        $record->update($validated);

        $this->logRecordActivity(
            $record,
            $user,
            $previousStatus !== $record->status ? 'status_changed' : 'updated',
            $previousStatus,
            $record->status,
            $previousStatus !== $record->status ? __('main.ged_activity_status_changed') : __('main.ged_activity_incoming_updated')
        );

        return redirect()
            ->route('main.document-management.incoming', [$company, $site])
            ->with('success', __('main.ged_incoming_updated'));
    }

    public function destroyIncoming(Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->incomingRecordBelongsToSite($record, $site)) {
            abort(404);
        }

        if ($record->file_path) {
            Storage::disk('public')->delete($record->file_path);
        }

        $record->delete();

        return redirect()
            ->route('main.document-management.incoming', [$company, $site])
            ->with('success', __('main.ged_incoming_deleted'))
            ->with('toast_type', 'danger');
    }

    public function outgoing(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $folders = $site->documentManagementFolders()
            ->where('status', DocumentManagementFolder::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
        $assignees = $this->siteAssignees($site);
        $records = $site->documentManagementRecords()
            ->with(['folder', 'assignee:id,name,email'])
            ->where('record_type', DocumentManagementRecord::TYPE_OUTGOING)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.outgoing', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'records' => $records,
            'folders' => $folders,
            'assignees' => $assignees,
            'statusLabels' => $this->outgoingStatusLabels(),
            'priorityLabels' => $this->priorityLabels(),
        ]);
    }

    public function storeOutgoing(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateOutgoing($request, $site);
        $validated['file_path'] = $this->storeAttachment($request, null, 'outgoing');
        unset($validated['attachment']);

        $record = $site->documentManagementRecords()->create(array_merge($validated, [
            'created_by' => $user->id,
            'reference' => $this->nextDocumentReference(),
            'record_type' => DocumentManagementRecord::TYPE_OUTGOING,
            'direction' => DocumentManagementRecord::TYPE_OUTGOING,
            'sender' => ($validated['sender'] ?? null) ?: $site->name,
        ]));

        $this->logRecordActivity($record, $user, 'registered', null, $record->status, __('main.ged_activity_outgoing_registered'));

        return redirect()
            ->route('main.document-management.outgoing', [$company, $site])
            ->with('success', __('main.ged_outgoing_created'));
    }

    public function updateOutgoing(Request $request, Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->outgoingRecordBelongsToSite($record, $site)) {
            abort(404);
        }

        $previousStatus = $record->status;
        $validated = $this->validateOutgoing($request, $site, $record);
        $validated['file_path'] = $this->storeAttachment($request, $record, 'outgoing');
        $validated['sender'] = ($validated['sender'] ?? null) ?: $site->name;
        unset($validated['attachment']);

        $record->update($validated);

        $this->logRecordActivity(
            $record,
            $user,
            $previousStatus !== $record->status ? 'status_changed' : 'updated',
            $previousStatus,
            $record->status,
            $previousStatus !== $record->status ? __('main.ged_activity_status_changed') : __('main.ged_activity_outgoing_updated')
        );

        return redirect()
            ->route('main.document-management.outgoing', [$company, $site])
            ->with('success', __('main.ged_outgoing_updated'));
    }

    public function destroyOutgoing(Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->outgoingRecordBelongsToSite($record, $site)) {
            abort(404);
        }

        if ($record->file_path) {
            Storage::disk('public')->delete($record->file_path);
        }

        $record->delete();

        return redirect()
            ->route('main.document-management.outgoing', [$company, $site])
            ->with('success', __('main.ged_outgoing_deleted'))
            ->with('toast_type', 'danger');
    }

    public function internal(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $folders = $site->documentManagementFolders()
            ->where('status', DocumentManagementFolder::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
        $assignees = $this->siteAssignees($site);
        $records = $site->documentManagementRecords()
            ->with(['folder', 'assignee:id,name,email'])
            ->where('record_type', DocumentManagementRecord::TYPE_INTERNAL)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.internal', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'records' => $records,
            'folders' => $folders,
            'assignees' => $assignees,
            'statusLabels' => $this->internalStatusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'documentTypes' => $this->internalDocumentTypes(),
        ]);
    }

    public function storeInternal(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateInternal($request, $site);
        $validated['file_path'] = $this->storeAttachment($request, null, 'internal');
        $validated['recipient'] = 'Interne';
        unset($validated['attachment']);

        $record = $site->documentManagementRecords()->create(array_merge($validated, [
            'created_by' => $user->id,
            'reference' => $this->nextDocumentReference(),
            'record_type' => DocumentManagementRecord::TYPE_INTERNAL,
            'direction' => DocumentManagementRecord::TYPE_INTERNAL,
            'sender' => ($validated['sender'] ?? null) ?: $site->name,
        ]));

        $this->logRecordActivity($record, $user, 'registered', null, $record->status, __('main.ged_activity_internal_registered'));

        return redirect()
            ->route('main.document-management.internal', [$company, $site])
            ->with('success', __('main.ged_internal_created'));
    }

    public function updateInternal(Request $request, Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->internalRecordBelongsToSite($record, $site)) {
            abort(404);
        }

        $previousStatus = $record->status;
        $validated = $this->validateInternal($request, $site, $record);
        $validated['file_path'] = $this->storeAttachment($request, $record, 'internal');
        $validated['sender'] = ($validated['sender'] ?? null) ?: $site->name;
        $validated['recipient'] = 'Interne';
        unset($validated['attachment']);

        $record->update($validated);

        $this->logRecordActivity(
            $record,
            $user,
            $previousStatus !== $record->status ? 'status_changed' : 'updated',
            $previousStatus,
            $record->status,
            $previousStatus !== $record->status ? __('main.ged_activity_status_changed') : __('main.ged_activity_internal_updated')
        );

        return redirect()
            ->route('main.document-management.internal', [$company, $site])
            ->with('success', __('main.ged_internal_updated'));
    }

    public function destroyInternal(Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->internalRecordBelongsToSite($record, $site)) {
            abort(404);
        }

        if ($record->file_path) {
            Storage::disk('public')->delete($record->file_path);
        }

        $record->delete();

        return redirect()
            ->route('main.document-management.internal', [$company, $site])
            ->with('success', __('main.ged_internal_deleted'))
            ->with('toast_type', 'danger');
    }

    public function folders(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $folders = $site->documentManagementFolders()
            ->with(['creator:id,name,email'])
            ->withCount('records')
            ->withMax('records', 'updated_at')
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($subQuery) use ($like): void {
                    $subQuery
                        ->where('reference', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.folders', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'folders' => $folders,
            'search' => $search,
            'folderStatusLabels' => $this->folderStatusLabels(),
        ]);
    }

    public function showFolder(Company $company, CompanySite $site, DocumentManagementFolder $folder): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        if (! $this->folderBelongsToSite($folder, $site)) {
            abort(404);
        }

        $folder->load(['creator:id,name,email']);
        $records = $folder->records()
            ->with(['assignee:id,name,email'])
            ->orderByDesc('updated_at')
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.folder-show', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'folder' => $folder,
            'records' => $records,
            'folderStatusLabels' => $this->folderStatusLabels(),
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function storeFolder(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateFolder($request);

        $site->documentManagementFolders()->create(array_merge($validated, [
            'created_by' => $user->id,
            'reference' => $this->nextFolderReference(),
        ]));

        return redirect()
            ->route('main.document-management.folders', [$company, $site])
            ->with('success', __('main.ged_folder_created'));
    }

    public function updateFolder(Request $request, Company $company, CompanySite $site, DocumentManagementFolder $folder): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->folderBelongsToSite($folder, $site)) {
            abort(404);
        }

        $folder->update($this->validateFolder($request));

        return redirect()
            ->route('main.document-management.folders', [$company, $site])
            ->with('success', __('main.ged_folder_updated'));
    }

    public function destroyFolder(Company $company, CompanySite $site, DocumentManagementFolder $folder): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->folderBelongsToSite($folder, $site)) {
            abort(404);
        }

        if ($folder->records()->exists()) {
            return redirect()
                ->route('main.document-management.folders', [$company, $site])
                ->with('success', __('main.ged_folder_delete_blocked'))
                ->with('toast_type', 'danger');
        }

        $folder->delete();

        return redirect()
            ->route('main.document-management.folders', [$company, $site])
            ->with('success', __('main.ged_folder_deleted'))
            ->with('toast_type', 'danger');
    }

    public function assignments(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $assignees = $this->siteAssignees($site);
        $records = $site->documentManagementRecords()
            ->with(['folder', 'assignee:id,name,email'])
            ->whereNotIn('status', [DocumentManagementRecord::STATUS_ARCHIVED])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('assigned_to')
                    ->orWhereIn('status', [
                        DocumentManagementRecord::STATUS_REGISTERED,
                        DocumentManagementRecord::STATUS_ASSIGNED,
                        DocumentManagementRecord::STATUS_IN_REVIEW,
                        DocumentManagementRecord::STATUS_VALIDATED,
                    ]);
            })
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->orderByDesc('updated_at')
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.assignments', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'records' => $records,
            'assignees' => $assignees,
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function updateAssignment(Request $request, Company $company, CompanySite $site, DocumentManagementRecord $record): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if ((int) $record->company_site_id !== (int) $site->id) {
            abort(404);
        }

        $previousStatus = $record->status;
        $previousAssignee = $record->assigned_to;
        $validated = $this->validateAssignment($request, $site);
        $comment = $validated['assignment_comment'] ?? null;
        unset($validated['assignment_comment']);

        $record->update($validated);

        $action = $previousAssignee !== $record->assigned_to
            ? 'assigned'
            : ($previousStatus !== $record->status ? 'status_changed' : 'assignment_updated');

        $this->logRecordActivity(
            $record,
            $user,
            $action,
            $previousStatus,
            $record->status,
            $comment ?: __('main.ged_activity_assignment_updated')
        );

        return redirect()
            ->route('main.document-management.assignments', [$company, $site])
            ->with('success', __('main.ged_assignment_updated'));
    }

    public function traceability(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $search = trim((string) $request->query('q', ''));
        $siteActivitiesQuery = DocumentManagementActivity::query()
            ->whereHas('record', fn ($query) => $query->where('company_site_id', $site->id));
        $activitiesQuery = (clone $siteActivitiesQuery)
            ->with(['record.folder', 'record.assignee:id,name,email', 'actor:id,name,email'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($subQuery) use ($like): void {
                    $subQuery
                        ->where('action', 'like', $like)
                        ->orWhere('comment', 'like', $like)
                        ->orWhere('from_status', 'like', $like)
                        ->orWhere('to_status', 'like', $like)
                        ->orWhereHas('record', function ($recordQuery) use ($like): void {
                            $recordQuery
                                ->where('reference', 'like', $like)
                                ->orWhere('subject', 'like', $like)
                                ->orWhere('sender', 'like', $like)
                                ->orWhere('recipient', 'like', $like);
                        })
                        ->orWhereHas('actor', function ($actorQuery) use ($like): void {
                            $actorQuery
                                ->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            });
        $activities = $activitiesQuery
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('main.modules.document-management.traceability', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'activities' => $activities,
            'search' => $search,
            'actionLabels' => $this->traceabilityActionLabels(),
            'statusLabels' => $this->statusLabels(),
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function reports(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.document-management.reports', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
        ], $this->documentManagementReportData($request, $site)));
    }

    public function printReport(Request $request, Company $company, CompanySite $site): Response|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        return Pdf::loadView('main.modules.document-management.pdf.reports', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
        ], $this->documentManagementReportData($request, $site)))
            ->setPaper('a4', 'landscape')
            ->stream('rapport-ged-'.$site->id.'-'.now()->format('Ymd').'.pdf');
    }

    public function notifications(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        AccountingActivityFeed::syncSite($site, CompanySite::MODULE_DOCUMENT_MANAGEMENT);

        $status = $request->query('status', 'all');
        $notifications = AccountingNotification::query()
            ->with(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('company_site_id', $site->id)
            ->whereIn('module_key', AccountingActivityFeed::moduleKeys(CompanySite::MODULE_DOCUMENT_MANAGEMENT))
            ->when($status === 'unread', fn ($query) => $query->whereDoesntHave('reads', fn ($readQuery) => $readQuery->where('user_id', $user->id)->whereNotNull('read_at')))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('main.modules.document-management.notifications', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'activeDocumentManagementPage' => 'notifications',
            'notifications' => $notifications,
            'status' => $status,
        ]);
    }

    public function showNotification(Company $company, CompanySite $site, AccountingNotification $notification): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        abort_unless((int) $notification->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);
        abort_unless(in_array($notification->module_key, AccountingActivityFeed::moduleKeys(CompanySite::MODULE_DOCUMENT_MANAGEMENT), true), Response::HTTP_NOT_FOUND);

        $notification->load(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]);
        $notification->markReadBy($user);

        return view('main.modules.document-management.notification-show', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'activeDocumentManagementPage' => 'notifications',
            'notification' => $notification->fresh(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]),
            'moduleLabel' => $this->documentManagementModuleLabel($notification->module_key),
            'moduleUrl' => DocumentManagementModuleNavigation::urlForKey($notification->module_key, $company, $site),
        ]);
    }

    public function settings(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

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
                ->orWhere('company_site_user.module_permissions', 'like', '%"'.CompanySite::MODULE_DOCUMENT_MANAGEMENT.'"%'))
            ->orderBy('users.name')
            ->paginate(1, ['users.*'], 'users_page')
            ->withQueryString();
        $menuKeys = DocumentManagementModuleNavigation::keys();
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

        return view('main.modules.document-management.settings', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'activeDocumentManagementPage' => 'settings',
            'settings' => $settings,
            'managedUsers' => $managedUsers,
            'menuSelections' => $menuSelections,
            'menuGroups' => $this->documentManagementSettingsMenuGroups(),
        ]);
    }

    public function updateSettings(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless($user->isAdmin() || $user->isSuperadmin(), Response::HTTP_FORBIDDEN);

        $menuKeys = DocumentManagementModuleNavigation::keys();
        $managedUserIds = $site->users()
            ->where('users.role', User::ROLE_USER)
            ->where(fn ($query) => $query
                ->whereNull('company_site_user.module_permissions')
                ->orWhere('company_site_user.module_permissions', '')
                ->orWhere('company_site_user.module_permissions', 'like', '%"'.CompanySite::MODULE_DOCUMENT_MANAGEMENT.'"%'))
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

        return redirect()
            ->route('main.document-management.settings', [$company, $site])
            ->with('success', __('main.ged_settings_saved'));
    }

    public function validationCircuits(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $assignees = $this->siteAssignees($site);
        $circuits = $site->documentManagementValidationCircuits()
            ->with(['steps.validator:id,name,email'])
            ->withCount(['steps', 'validationRequests'])
            ->orderBy('name')
            ->paginate(5, ['*'], 'circuits_page')
            ->withQueryString();
        $validationRequests = DocumentManagementValidationRequest::query()
            ->with(['record.folder', 'record.assignee:id,name,email', 'circuit', 'currentStep.validator:id,name,email'])
            ->whereHas('record', fn ($query) => $query->where('company_site_id', $site->id))
            ->whereIn('status', [DocumentManagementValidationRequest::STATUS_PENDING, DocumentManagementValidationRequest::STATUS_IN_PROGRESS])
            ->latest()
            ->paginate(5, ['*'], 'validations_page')
            ->withQueryString();
        $circuitsForValidation = $site->documentManagementValidationCircuits()
            ->with(['steps' => fn ($query) => $query->orderBy('step_order')])
            ->where('status', DocumentManagementValidationCircuit::STATUS_ACTIVE)
            ->whereHas('steps')
            ->orderBy('name')
            ->get();
        $documentsForValidation = $site->documentManagementRecords()
            ->with('folder')
            ->whereNotIn('status', [DocumentManagementRecord::STATUS_CLOSED, DocumentManagementRecord::STATUS_ARCHIVED])
            ->whereDoesntHave('validationRequests', function ($query): void {
                $query->whereIn('status', [DocumentManagementValidationRequest::STATUS_PENDING, DocumentManagementValidationRequest::STATUS_IN_PROGRESS]);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        return view('main.modules.document-management.validation-circuits', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_DOCUMENT_MANAGEMENT,
            'moduleMeta' => $moduleMeta,
            'circuits' => $circuits,
            'validationRequests' => $validationRequests,
            'circuitsForValidation' => $circuitsForValidation,
            'documentsForValidation' => $documentsForValidation,
            'assignees' => $assignees,
            'documentTypeLabels' => $this->validationDocumentTypeLabels(),
            'circuitStatusLabels' => $this->validationCircuitStatusLabels(),
            'requestStatusLabels' => $this->validationRequestStatusLabels(),
            'typeLabels' => $this->typeLabels(),
        ]);
    }

    public function storeValidationCircuit(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateValidationCircuit($request, $site);

        DB::transaction(function () use ($site, $user, $validated): void {
            $circuit = $site->documentManagementValidationCircuits()->create([
                'created_by' => $user->id,
                'reference' => $this->nextValidationCircuitReference(),
                'name' => $validated['name'],
                'document_type' => $validated['document_type'],
                'service_owner' => $validated['service_owner'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]);

            $this->syncValidationCircuitSteps($circuit, $validated);
        });

        return redirect()
            ->route('main.document-management.validation-circuits', [$company, $site])
            ->with('success', __('main.ged_validation_circuit_created'));
    }

    public function updateValidationCircuit(Request $request, Company $company, CompanySite $site, DocumentManagementValidationCircuit $circuit): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->validationCircuitBelongsToSite($circuit, $site)) {
            abort(404);
        }

        $validated = $this->validateValidationCircuit($request, $site);

        DB::transaction(function () use ($circuit, $validated): void {
            $circuit->update([
                'name' => $validated['name'],
                'document_type' => $validated['document_type'],
                'service_owner' => $validated['service_owner'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]);

            $this->syncValidationCircuitSteps($circuit, $validated);
        });

        return redirect()
            ->route('main.document-management.validation-circuits', [$company, $site])
            ->with('success', __('main.ged_validation_circuit_updated'));
    }

    public function destroyValidationCircuit(Company $company, CompanySite $site, DocumentManagementValidationCircuit $circuit): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->validationCircuitBelongsToSite($circuit, $site)) {
            abort(404);
        }

        if ($circuit->validationRequests()->exists()) {
            return redirect()
                ->route('main.document-management.validation-circuits', [$company, $site])
                ->with('success', __('main.ged_validation_circuit_delete_blocked'))
                ->with('toast_type', 'danger');
        }

        $circuit->delete();

        return redirect()
            ->route('main.document-management.validation-circuits', [$company, $site])
            ->with('success', __('main.ged_validation_circuit_deleted'))
            ->with('toast_type', 'danger');
    }

    public function storeValidationRequest(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateValidationRequestLaunch($request, $site);
        $record = DocumentManagementRecord::query()
            ->where('company_site_id', $site->id)
            ->findOrFail($validated['document_management_record_id']);
        $circuit = DocumentManagementValidationCircuit::query()
            ->with(['steps' => fn ($query) => $query->orderBy('step_order')])
            ->where('company_site_id', $site->id)
            ->where('status', DocumentManagementValidationCircuit::STATUS_ACTIVE)
            ->findOrFail($validated['document_management_validation_circuit_id']);

        if ($circuit->document_type !== DocumentManagementValidationCircuit::TYPE_ALL && $circuit->document_type !== $record->record_type) {
            throw ValidationException::withMessages([
                'document_management_validation_circuit_id' => __('main.ged_validation_circuit_type_mismatch'),
            ]);
        }

        $firstStep = $circuit->steps->first();

        if (! $firstStep) {
            throw ValidationException::withMessages([
                'document_management_validation_circuit_id' => __('main.ged_validation_circuit_without_steps'),
            ]);
        }

        if ($record->validationRequests()->whereIn('status', [DocumentManagementValidationRequest::STATUS_PENDING, DocumentManagementValidationRequest::STATUS_IN_PROGRESS])->exists()) {
            throw ValidationException::withMessages([
                'document_management_record_id' => __('main.ged_validation_request_duplicate'),
            ]);
        }

        DB::transaction(function () use ($record, $circuit, $firstStep, $user, $validated): void {
            $previousStatus = $record->status;

            $record->validationRequests()->create([
                'document_management_validation_circuit_id' => $circuit->id,
                'current_step_id' => $firstStep->id,
                'requested_by' => $user->id,
                'status' => DocumentManagementValidationRequest::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'due_at' => $firstStep->due_days === null ? null : now()->addDays($firstStep->due_days)->toDateString(),
                'comment' => $validated['comment'] ?? null,
            ]);

            $record->update(['status' => DocumentManagementRecord::STATUS_IN_REVIEW]);

            $this->logRecordActivity(
                $record,
                $user,
                'validation_started',
                $previousStatus,
                $record->status,
                __('main.ged_activity_validation_started', ['circuit' => $circuit->name])
            );
        });

        return redirect()
            ->to(route('main.document-management.validation-circuits', [$company, $site]).'#validationRequestsTable')
            ->with('success', __('main.ged_validation_request_created'));
    }

    public function approveValidationRequest(Request $request, Company $company, CompanySite $site, DocumentManagementValidationRequest $validationRequest): RedirectResponse
    {
        return $this->processValidationRequest($request, $company, $site, $validationRequest, 'approved');
    }

    public function rejectValidationRequest(Request $request, Company $company, CompanySite $site, DocumentManagementValidationRequest $validationRequest): RedirectResponse
    {
        return $this->processValidationRequest($request, $company, $site, $validationRequest, 'rejected');
    }

    private function processValidationRequest(Request $request, Company $company, CompanySite $site, DocumentManagementValidationRequest $validationRequest, string $decision): RedirectResponse
    {
        $access = $this->documentManagementAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->validationRequestBelongsToSite($validationRequest, $site)) {
            abort(404);
        }

        $validationRequest->load(['record', 'circuit.steps', 'currentStep.validator']);

        if (! $this->canProcessValidationRequest($user, $company, $validationRequest)) {
            return redirect()
                ->to(route('main.document-management.validation-circuits', [$company, $site]).'#validationRequestsTable')
                ->with('success', __('main.ged_validation_request_not_allowed'))
                ->with('toast_type', 'danger');
        }

        if (! in_array($validationRequest->status, [DocumentManagementValidationRequest::STATUS_PENDING, DocumentManagementValidationRequest::STATUS_IN_PROGRESS], true)) {
            return redirect()
                ->to(route('main.document-management.validation-circuits', [$company, $site]).'#validationRequestsTable')
                ->with('success', __('main.ged_validation_request_already_processed'))
                ->with('toast_type', 'danger');
        }

        $validated = $this->validateValidationRequestAction($request);
        $successKey = $decision === 'approved'
            ? 'main.ged_validation_request_approved'
            : 'main.ged_validation_request_rejected';

        DB::transaction(function () use ($validationRequest, $user, $decision, $validated): void {
            $validationRequest->refresh()->load(['record', 'circuit.steps', 'currentStep']);
            $record = $validationRequest->record;
            $currentStep = $validationRequest->currentStep;
            $comment = $validated['comment'] ?? null;

            $validationRequest->actions()->create([
                'document_management_validation_step_id' => $currentStep?->id,
                'actor_id' => $user->id,
                'action' => $decision,
                'status' => $decision,
                'comment' => $comment,
            ]);

            if ($decision === 'rejected') {
                $previousStatus = $record?->status;

                $validationRequest->update([
                    'status' => DocumentManagementValidationRequest::STATUS_REJECTED,
                    'completed_at' => now(),
                    'comment' => $comment ?: $validationRequest->comment,
                ]);

                $record?->update(['status' => DocumentManagementRecord::STATUS_IN_REVIEW]);

                if ($record) {
                    $this->logRecordActivity(
                        $record,
                        $user,
                        'validation_rejected',
                        $previousStatus,
                        $record->status,
                        $comment ?: __('main.ged_activity_validation_rejected')
                    );
                }

                return;
            }

            $steps = $validationRequest->circuit->steps->sortBy('step_order')->values();
            $currentOrder = $currentStep?->step_order ?? 0;
            $nextStep = $steps->first(fn ($step): bool => $step->step_order > $currentOrder);

            if ($nextStep) {
                $validationRequest->update([
                    'status' => DocumentManagementValidationRequest::STATUS_IN_PROGRESS,
                    'current_step_id' => $nextStep->id,
                    'started_at' => $validationRequest->started_at ?: now(),
                    'due_at' => $nextStep->due_days === null ? null : now()->addDays($nextStep->due_days)->toDateString(),
                    'comment' => $comment ?: $validationRequest->comment,
                ]);

                if ($record) {
                    $this->logRecordActivity(
                        $record,
                        $user,
                        'validation_step_approved',
                        $record->status,
                        $record->status,
                        __('main.ged_activity_validation_step_approved', ['step' => $currentStep?->name ?? '-'])
                    );
                }

                return;
            }

            $previousStatus = $record?->status;

            $validationRequest->update([
                'status' => DocumentManagementValidationRequest::STATUS_APPROVED,
                'completed_at' => now(),
                'due_at' => null,
                'comment' => $comment ?: $validationRequest->comment,
            ]);

            $record?->update(['status' => DocumentManagementRecord::STATUS_VALIDATED]);

            if ($record) {
                $this->logRecordActivity(
                    $record,
                    $user,
                    'validation_approved',
                    $previousStatus,
                    $record->status,
                    $comment ?: __('main.ged_activity_validation_approved')
                );
            }
        });

        return redirect()
            ->to(route('main.document-management.validation-circuits', [$company, $site]).'#validationRequestsTable')
            ->with('success', __($successKey));
    }

    private function documentManagementAccess(Company $company, CompanySite $site): array|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $this->canAccessCompanySite($user, $company, $site)) {
            return $this->redirectMainArea($user);
        }

        if (! in_array(CompanySite::MODULE_DOCUMENT_MANAGEMENT, $this->availableSiteModulesForUser($user, $site), true)) {
            return redirect()->route('main.companies.sites.show', [$company, $site]);
        }

        $company->load('subscription');
        $site->load('responsible');

        return [$user, $this->documentManagementModuleMeta()];
    }

    private function dashboardData(CompanySite $site): array
    {
        $records = $site->documentManagementRecords()
            ->with(['folder', 'assignee:id,name,email', 'creator:id,name,email'])
            ->latest()
            ->get();
        $openStatuses = [
            DocumentManagementRecord::STATUS_REGISTERED,
            DocumentManagementRecord::STATUS_ASSIGNED,
            DocumentManagementRecord::STATUS_IN_REVIEW,
            DocumentManagementRecord::STATUS_VALIDATED,
        ];
        $incoming = $records->where('record_type', DocumentManagementRecord::TYPE_INCOMING);
        $outgoing = $records->where('record_type', DocumentManagementRecord::TYPE_OUTGOING);
        $internal = $records->where('record_type', DocumentManagementRecord::TYPE_INTERNAL);
        $openRecords = $records->whereIn('status', $openStatuses);
        $urgentRecords = $records
            ->whereIn('priority', [DocumentManagementRecord::PRIORITY_HIGH, DocumentManagementRecord::PRIORITY_URGENT])
            ->whereIn('status', $openStatuses);
        $overdueRecords = $records
            ->filter(fn (DocumentManagementRecord $record): bool => $record->due_at !== null
                && $record->due_at->isPast()
                && ! in_array($record->status, [DocumentManagementRecord::STATUS_CLOSED, DocumentManagementRecord::STATUS_ARCHIVED], true));
        $todayRecords = $records
            ->filter(fn (DocumentManagementRecord $record): bool => $record->created_at?->isToday() || $record->received_at?->isToday());
        $folders = $site->documentManagementFolders()
            ->withCount('records')
            ->orderBy('name')
            ->get();
        $recentActivities = $records
            ->load('activities.actor')
            ->flatMap(fn (DocumentManagementRecord $record) => $record->activities->map(fn ($activity) => [
                'record' => $record,
                'activity' => $activity,
            ]))
            ->sortByDesc(fn (array $item) => $item['activity']->created_at)
            ->take(5)
            ->values();

        return [
            'metrics' => [
                [
                    'label' => __('main.ged_incoming_mail'),
                    'value' => $incoming->count(),
                    'meta' => __('main.ged_registered_today', ['count' => $todayRecords->where('record_type', DocumentManagementRecord::TYPE_INCOMING)->count()]),
                    'icon' => 'bi-inbox',
                    'tone' => 'blue',
                ],
                [
                    'label' => __('main.ged_pending_processing'),
                    'value' => $openRecords->count(),
                    'meta' => __('main.ged_urgent_records', ['count' => $urgentRecords->count()]),
                    'icon' => 'bi-hourglass-split',
                    'tone' => 'amber',
                ],
                [
                    'label' => __('main.ged_overdue_records'),
                    'value' => $overdueRecords->count(),
                    'meta' => __('main.ged_due_followup'),
                    'icon' => 'bi-exclamation-triangle',
                    'tone' => 'rose',
                ],
                [
                    'label' => __('main.ged_folders'),
                    'value' => $folders->count(),
                    'meta' => __('main.ged_active_folders', ['count' => $folders->where('status', DocumentManagementFolder::STATUS_ACTIVE)->count()]),
                    'icon' => 'bi-folder2-open',
                    'tone' => 'green',
                ],
            ],
            'records' => $records,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'internal' => $internal,
            'openRecords' => $openRecords,
            'urgentRecords' => $urgentRecords,
            'overdueRecords' => $overdueRecords,
            'folders' => $folders,
            'recentRecords' => $records->take(6),
            'recentActivities' => $recentActivities,
            'statusLabels' => $this->statusLabels(),
            'priorityLabels' => $this->priorityLabels(),
            'typeLabels' => $this->typeLabels(),
        ];
    }

    private function statusLabels(): array
    {
        return [
            DocumentManagementRecord::STATUS_REGISTERED => __('main.ged_status_registered'),
            DocumentManagementRecord::STATUS_ASSIGNED => __('main.ged_status_assigned'),
            DocumentManagementRecord::STATUS_IN_REVIEW => __('main.ged_status_in_review'),
            DocumentManagementRecord::STATUS_VALIDATED => __('main.ged_status_validated'),
            DocumentManagementRecord::STATUS_SENT => __('main.ged_status_sent'),
            DocumentManagementRecord::STATUS_CLOSED => __('main.ged_status_closed'),
            DocumentManagementRecord::STATUS_ARCHIVED => __('main.ged_status_archived'),
        ];
    }

    private function incomingStatusLabels(): array
    {
        $statuses = $this->statusLabels();
        unset($statuses[DocumentManagementRecord::STATUS_SENT]);

        return $statuses;
    }

    private function outgoingStatusLabels(): array
    {
        return [
            DocumentManagementRecord::STATUS_REGISTERED => __('main.ged_status_draft'),
            DocumentManagementRecord::STATUS_IN_REVIEW => __('main.ged_status_in_review'),
            DocumentManagementRecord::STATUS_VALIDATED => __('main.ged_status_validated'),
            DocumentManagementRecord::STATUS_SENT => __('main.ged_status_sent'),
            DocumentManagementRecord::STATUS_CLOSED => __('main.ged_status_closed'),
            DocumentManagementRecord::STATUS_ARCHIVED => __('main.ged_status_archived'),
        ];
    }

    private function internalStatusLabels(): array
    {
        return [
            DocumentManagementRecord::STATUS_REGISTERED => __('main.ged_status_draft'),
            DocumentManagementRecord::STATUS_IN_REVIEW => __('main.ged_status_in_review'),
            DocumentManagementRecord::STATUS_VALIDATED => __('main.ged_status_validated'),
            DocumentManagementRecord::STATUS_SENT => __('main.ged_status_published'),
            DocumentManagementRecord::STATUS_CLOSED => __('main.ged_status_obsolete'),
            DocumentManagementRecord::STATUS_ARCHIVED => __('main.ged_status_archived'),
        ];
    }

    private function internalDocumentTypes(): array
    {
        return [
            'Note de service',
            'Procedure',
            'Decision interne',
            'Rapport interne',
            'PV de reunion',
            'Politique interne',
            'Memo',
            'Instruction',
            'Autre',
        ];
    }

    private function validationDocumentTypeLabels(): array
    {
        return [
            DocumentManagementValidationCircuit::TYPE_ALL => __('main.ged_validation_type_all'),
            DocumentManagementValidationCircuit::TYPE_INCOMING => __('main.ged_record_type_incoming'),
            DocumentManagementValidationCircuit::TYPE_OUTGOING => __('main.ged_record_type_outgoing'),
            DocumentManagementValidationCircuit::TYPE_INTERNAL => __('main.ged_record_type_internal'),
        ];
    }

    private function documentManagementReportFilters(Request $request): array
    {
        $period = (string) $request->query('period', 'month');
        $allowedPeriods = ['today', 'week', 'month', 'year', 'custom'];
        $period = in_array($period, $allowedPeriods, true) ? $period : 'month';
        $today = now();

        [$dateFrom, $dateTo] = match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [
                $this->safeReportDate($request->query('date_from'), $today->copy()->startOfMonth()),
                $this->safeReportDate($request->query('date_to'), $today),
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
        ];
    }

    private function documentManagementReportData(Request $request, CompanySite $site): array
    {
        $filters = $this->documentManagementReportFilters($request);
        $dateFrom = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
        $dateTo = \Carbon\Carbon::parse($filters['date_to'])->endOfDay();
        $statusLabels = $this->statusLabels();
        $typeLabels = $this->typeLabels();
        $validationStatusLabels = $this->validationRequestStatusLabels();
        $records = $this->applyDocumentManagementReportPeriod(
            $site->documentManagementRecords()->with(['folder', 'assignee:id,name,email']),
            $dateFrom,
            $dateTo
        )
            ->latest()
            ->get();
        $openStatuses = [
            DocumentManagementRecord::STATUS_REGISTERED,
            DocumentManagementRecord::STATUS_ASSIGNED,
            DocumentManagementRecord::STATUS_IN_REVIEW,
            DocumentManagementRecord::STATUS_VALIDATED,
        ];
        $openRecords = $records->whereIn('status', $openStatuses);
        $urgentRecords = $records->whereIn('priority', [
            DocumentManagementRecord::PRIORITY_HIGH,
            DocumentManagementRecord::PRIORITY_URGENT,
        ]);
        $overdueRecords = $records->filter(fn (DocumentManagementRecord $record): bool => $record->due_at !== null
            && $record->due_at->endOfDay()->isPast()
            && ! in_array($record->status, [DocumentManagementRecord::STATUS_CLOSED, DocumentManagementRecord::STATUS_ARCHIVED], true));
        $activities = DocumentManagementActivity::query()
            ->with(['record.folder', 'actor:id,name,email'])
            ->whereHas('record', fn ($query) => $query->where('company_site_id', $site->id))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->get();
        $validationRequests = DocumentManagementValidationRequest::query()
            ->with(['record.folder', 'circuit'])
            ->whereHas('record', fn ($query) => $query->where('company_site_id', $site->id))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->get();
        $typeRows = collect([
            DocumentManagementRecord::TYPE_INCOMING,
            DocumentManagementRecord::TYPE_OUTGOING,
            DocumentManagementRecord::TYPE_INTERNAL,
        ])->map(function (string $type) use ($records, $typeLabels, $openStatuses): array {
            $typeRecords = $records->where('record_type', $type);

            return [
                'type' => $type,
                'label' => $typeLabels[$type] ?? $type,
                'count' => $typeRecords->count(),
                'open' => $typeRecords->whereIn('status', $openStatuses)->count(),
                'validated' => $typeRecords->where('status', DocumentManagementRecord::STATUS_VALIDATED)->count(),
            ];
        });
        $statusRows = collect($statusLabels)
            ->map(fn (string $label, string $status): array => [
                'status' => $status,
                'label' => $label,
                'count' => $records->where('status', $status)->count(),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values();
        $folderRows = $records
            ->groupBy(fn (DocumentManagementRecord $record): string => (string) ($record->folder?->id ?? 'none'))
            ->map(function ($folderRecords): array {
                $firstRecord = $folderRecords->first();

                return [
                    'name' => $firstRecord?->folder?->name ?? __('main.ged_without_folder'),
                    'category' => $firstRecord?->folder?->category ?? '-',
                    'count' => $folderRecords->count(),
                    'urgent' => $folderRecords->whereIn('priority', [DocumentManagementRecord::PRIORITY_HIGH, DocumentManagementRecord::PRIORITY_URGENT])->count(),
                    'last_activity' => $folderRecords->max('updated_at'),
                ];
            })
            ->sortByDesc('count')
            ->values();
        $validationRows = collect($validationStatusLabels)
            ->map(fn (string $label, string $status): array => [
                'status' => $status,
                'label' => $label,
                'count' => $validationRequests->where('status', $status)->count(),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values();

        return [
            'filters' => $filters,
            'periodLabel' => $this->documentManagementReportPeriodLabel($filters['period']),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'pdfSettings' => AccountingModuleSetting::query()
                ->firstOrNew(['company_site_id' => $site->id], AccountingModuleSetting::defaults()),
            'metrics' => [
                [
                    'label' => __('main.ged_report_total_documents'),
                    'value' => $records->count(),
                    'meta' => __('main.ged_report_period_documents'),
                    'icon' => 'bi-files',
                    'tone' => 'blue',
                ],
                [
                    'label' => __('main.ged_pending_processing'),
                    'value' => $openRecords->count(),
                    'meta' => __('main.ged_report_open_documents'),
                    'icon' => 'bi-hourglass-split',
                    'tone' => 'amber',
                ],
                [
                    'label' => __('main.ged_report_urgent_documents'),
                    'value' => $urgentRecords->count(),
                    'meta' => __('main.ged_report_priority_followup'),
                    'icon' => 'bi-exclamation-triangle',
                    'tone' => 'rose',
                ],
                [
                    'label' => __('main.ged_trace_total_events'),
                    'value' => $activities->count(),
                    'meta' => __('main.ged_report_traced_actions'),
                    'icon' => 'bi-activity',
                    'tone' => 'green',
                ],
            ],
            'typeRows' => $typeRows,
            'statusRows' => $statusRows,
            'folderRows' => $folderRows,
            'validationRows' => $validationRows,
            'recentActivities' => $activities->take(5),
            'records' => $records,
            'overdueRecords' => $overdueRecords,
            'statusLabels' => $statusLabels,
            'typeLabels' => $typeLabels,
        ];
    }

    private function safeReportDate(mixed $value, \Carbon\Carbon $fallback): \Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse((string) $value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function documentManagementReportPeriodLabel(string $period): string
    {
        return match ($period) {
            'today' => __('main.hr_period_today'),
            'week' => __('admin.week'),
            'year' => __('admin.year'),
            'custom' => __('main.hr_period_custom'),
            default => __('admin.month'),
        };
    }

    private function applyDocumentManagementReportPeriod($query, \Carbon\Carbon $dateFrom, \Carbon\Carbon $dateTo)
    {
        return $query->where(function ($dateQuery) use ($dateFrom, $dateTo): void {
            $dateQuery
                ->whereBetween('received_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->orWhereBetween('sent_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->orWhereBetween('created_at', [$dateFrom, $dateTo]);
        });
    }

    private function validationCircuitStatusLabels(): array
    {
        return [
            DocumentManagementValidationCircuit::STATUS_ACTIVE => __('main.active'),
            DocumentManagementValidationCircuit::STATUS_INACTIVE => __('main.inactive'),
        ];
    }

    private function validationRequestStatusLabels(): array
    {
        return [
            DocumentManagementValidationRequest::STATUS_PENDING => __('main.ged_validation_status_pending'),
            DocumentManagementValidationRequest::STATUS_IN_PROGRESS => __('main.ged_validation_status_in_progress'),
            DocumentManagementValidationRequest::STATUS_APPROVED => __('main.ged_validation_status_approved'),
            DocumentManagementValidationRequest::STATUS_REJECTED => __('main.ged_validation_status_rejected'),
            DocumentManagementValidationRequest::STATUS_CANCELLED => __('main.ged_validation_status_cancelled'),
        ];
    }

    private function priorityLabels(): array
    {
        return [
            DocumentManagementRecord::PRIORITY_LOW => __('main.ged_priority_low'),
            DocumentManagementRecord::PRIORITY_NORMAL => __('main.ged_priority_normal'),
            DocumentManagementRecord::PRIORITY_HIGH => __('main.ged_priority_high'),
            DocumentManagementRecord::PRIORITY_URGENT => __('main.ged_priority_urgent'),
        ];
    }

    private function typeLabels(): array
    {
        return [
            DocumentManagementRecord::TYPE_INCOMING => __('main.ged_record_type_incoming'),
            DocumentManagementRecord::TYPE_OUTGOING => __('main.ged_record_type_outgoing'),
            DocumentManagementRecord::TYPE_INTERNAL => __('main.ged_record_type_internal'),
        ];
    }

    private function folderStatusLabels(): array
    {
        return [
            DocumentManagementFolder::STATUS_ACTIVE => __('main.active'),
            DocumentManagementFolder::STATUS_ARCHIVED => __('main.ged_status_archived'),
        ];
    }

    private function traceabilityActionLabels(): array
    {
        return [
            'registered' => __('main.ged_trace_action_registered'),
            'updated' => __('main.ged_trace_action_updated'),
            'status_changed' => __('main.ged_trace_action_status_changed'),
            'assigned' => __('main.ged_trace_action_assigned'),
            'assignment_updated' => __('main.ged_trace_action_assignment_updated'),
            'validation_started' => __('main.ged_trace_action_validation_started'),
            'validation_step_approved' => __('main.ged_trace_action_validation_step_approved'),
            'validation_approved' => __('main.ged_trace_action_validation_approved'),
            'validation_rejected' => __('main.ged_trace_action_validation_rejected'),
        ];
    }

    private function incomingFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'all'),
            'priority' => (string) $request->query('priority', 'all'),
            'folder' => (string) $request->query('folder', 'all'),
            'assignee' => (string) $request->query('assignee', 'all'),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];
    }

    private function incomingMetrics(CompanySite $site): array
    {
        $records = $site->documentManagementRecords()
            ->where('record_type', DocumentManagementRecord::TYPE_INCOMING)
            ->get();

        $openStatuses = [
            DocumentManagementRecord::STATUS_REGISTERED,
            DocumentManagementRecord::STATUS_ASSIGNED,
            DocumentManagementRecord::STATUS_IN_REVIEW,
            DocumentManagementRecord::STATUS_VALIDATED,
        ];

        return [
            [
                'label' => __('main.ged_metric_incoming_total'),
                'value' => $records->count(),
                'icon' => 'bi-inbox',
                'tone' => 'blue',
            ],
            [
                'label' => __('main.ged_metric_incoming_open'),
                'value' => $records->whereIn('status', $openStatuses)->count(),
                'icon' => 'bi-hourglass-split',
                'tone' => 'amber',
            ],
            [
                'label' => __('main.ged_metric_incoming_urgent'),
                'value' => $records->whereIn('priority', [DocumentManagementRecord::PRIORITY_HIGH, DocumentManagementRecord::PRIORITY_URGENT])->count(),
                'icon' => 'bi-exclamation-diamond',
                'tone' => 'rose',
            ],
            [
                'label' => __('main.ged_metric_incoming_overdue'),
                'value' => $records
                    ->filter(fn (DocumentManagementRecord $record): bool => $record->due_at !== null
                        && $record->due_at->isPast()
                        && ! in_array($record->status, [DocumentManagementRecord::STATUS_CLOSED, DocumentManagementRecord::STATUS_ARCHIVED], true))
                    ->count(),
                'icon' => 'bi-calendar-x',
                'tone' => 'green',
            ],
        ];
    }

    private function validateIncoming(Request $request, CompanySite $site, ?DocumentManagementRecord $record = null): array
    {
        $siteUserIds = $this->siteAssignees($site)->pluck('id')->all();

        return $request->validate([
            'document_management_folder_id' => [
                'nullable',
                Rule::exists('document_management_folders', 'id')->where('company_site_id', $site->id),
            ],
            'assigned_to' => ['nullable', 'integer', Rule::in($siteUserIds)],
            'subject' => ['required', 'string', 'max:255'],
            'sender' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'priority' => ['required', Rule::in(array_keys($this->priorityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->incomingStatusLabels()))],
            'received_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:received_at'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
    }

    private function validateOutgoing(Request $request, CompanySite $site, ?DocumentManagementRecord $record = null): array
    {
        $siteUserIds = $this->siteAssignees($site)->pluck('id')->all();

        return $request->validate([
            'document_management_folder_id' => [
                'nullable',
                Rule::exists('document_management_folders', 'id')->where('company_site_id', $site->id),
            ],
            'assigned_to' => ['nullable', 'integer', Rule::in($siteUserIds)],
            'subject' => ['required', 'string', 'max:255'],
            'recipient' => ['required', 'string', 'max:255'],
            'sender' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'priority' => ['required', Rule::in(array_keys($this->priorityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->outgoingStatusLabels()))],
            'sent_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
    }

    private function validateInternal(Request $request, CompanySite $site, ?DocumentManagementRecord $record = null): array
    {
        $siteUserIds = $this->siteAssignees($site)->pluck('id')->all();

        return $request->validate([
            'document_management_folder_id' => [
                'nullable',
                Rule::exists('document_management_folders', 'id')->where('company_site_id', $site->id),
            ],
            'assigned_to' => ['nullable', 'integer', Rule::in($siteUserIds)],
            'subject' => ['required', 'string', 'max:255'],
            'sender' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'decision' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', Rule::in(array_keys($this->priorityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->internalStatusLabels()))],
            'received_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
    }

    private function validateFolder(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(array_keys($this->folderStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function validateAssignment(Request $request, CompanySite $site): array
    {
        $siteUserIds = $this->siteAssignees($site)->pluck('id')->all();

        return $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::in($siteUserIds)],
            'priority' => ['required', Rule::in(array_keys($this->priorityLabels()))],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
            'due_at' => ['nullable', 'date'],
            'assignment_comment' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateValidationCircuit(Request $request, CompanySite $site): array
    {
        $siteUserIds = $this->siteAssignees($site)->pluck('id')->all();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in(array_keys($this->validationDocumentTypeLabels()))],
            'service_owner' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys($this->validationCircuitStatusLabels()))],
            'description' => ['nullable', 'string', 'max:2000'],
            'step_names' => ['required', 'array', 'min:1', 'max:3'],
            'step_names.*' => ['nullable', 'string', 'max:120'],
            'step_role_names' => ['nullable', 'array', 'max:3'],
            'step_role_names.*' => ['nullable', 'string', 'max:120'],
            'step_validator_ids' => ['nullable', 'array', 'max:3'],
            'step_validator_ids.*' => ['nullable', 'integer', Rule::in($siteUserIds)],
            'step_due_days' => ['nullable', 'array', 'max:3'],
            'step_due_days.*' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $hasStep = collect($validated['step_names'] ?? [])
            ->filter(fn ($stepName): bool => trim((string) $stepName) !== '')
            ->isNotEmpty();

        if (! $hasStep) {
            throw ValidationException::withMessages([
                'step_names.0' => __('main.ged_validation_step_required'),
            ]);
        }

        return $validated;
    }

    private function validateValidationRequestLaunch(Request $request, CompanySite $site): array
    {
        return $request->validate([
            'document_management_record_id' => [
                'required',
                'integer',
                Rule::exists('document_management_records', 'id')->where('company_site_id', $site->id),
            ],
            'document_management_validation_circuit_id' => [
                'required',
                'integer',
                Rule::exists('document_management_validation_circuits', 'id')
                    ->where('company_site_id', $site->id)
                    ->where('status', DocumentManagementValidationCircuit::STATUS_ACTIVE),
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateValidationRequestAction(Request $request): array
    {
        return $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function syncValidationCircuitSteps(DocumentManagementValidationCircuit $circuit, array $validated): void
    {
        $circuit->steps()->delete();

        $stepNames = array_values($validated['step_names'] ?? []);
        $roleNames = array_values($validated['step_role_names'] ?? []);
        $validatorIds = array_values($validated['step_validator_ids'] ?? []);
        $dueDays = array_values($validated['step_due_days'] ?? []);
        $order = 1;

        foreach ($stepNames as $index => $stepName) {
            $stepName = trim((string) $stepName);

            if ($stepName === '') {
                continue;
            }

            $validatorId = $validatorIds[$index] ?? null;

            $circuit->steps()->create([
                'step_order' => $order++,
                'name' => $stepName,
                'role_name' => trim((string) ($roleNames[$index] ?? '')) ?: null,
                'validator_id' => $validatorId ?: null,
                'due_days' => ($dueDays[$index] ?? '') === '' ? null : (int) $dueDays[$index],
                'is_required' => true,
            ]);
        }
    }

    private function siteAssignees(CompanySite $site)
    {
        return $site->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get();
    }

    private function storeAttachment(Request $request, ?DocumentManagementRecord $record = null, string $directory = 'incoming'): ?string
    {
        if (! $request->hasFile('attachment')) {
            return $record?->file_path;
        }

        if ($record?->file_path) {
            Storage::disk('public')->delete($record->file_path);
        }

        return $request->file('attachment')->store('document-management/'.$directory, 'public');
    }

    private function nextDocumentReference(): string
    {
        $nextId = ((int) (DocumentManagementRecord::query()->max('id') ?? 0)) + 1;

        do {
            $reference = 'GED-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while (DocumentManagementRecord::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function nextFolderReference(): string
    {
        $nextId = ((int) (DocumentManagementFolder::query()->max('id') ?? 0)) + 1;

        do {
            $reference = 'DOS-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while (DocumentManagementFolder::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function nextValidationCircuitReference(): string
    {
        $nextId = ((int) (DocumentManagementValidationCircuit::query()->max('id') ?? 0)) + 1;

        do {
            $reference = 'VAL-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while (DocumentManagementValidationCircuit::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function incomingRecordBelongsToSite(DocumentManagementRecord $record, CompanySite $site): bool
    {
        return (int) $record->company_site_id === (int) $site->id
            && $record->record_type === DocumentManagementRecord::TYPE_INCOMING;
    }

    private function outgoingRecordBelongsToSite(DocumentManagementRecord $record, CompanySite $site): bool
    {
        return (int) $record->company_site_id === (int) $site->id
            && $record->record_type === DocumentManagementRecord::TYPE_OUTGOING;
    }

    private function internalRecordBelongsToSite(DocumentManagementRecord $record, CompanySite $site): bool
    {
        return (int) $record->company_site_id === (int) $site->id
            && $record->record_type === DocumentManagementRecord::TYPE_INTERNAL;
    }

    private function folderBelongsToSite(DocumentManagementFolder $folder, CompanySite $site): bool
    {
        return (int) $folder->company_site_id === (int) $site->id;
    }

    private function validationCircuitBelongsToSite(DocumentManagementValidationCircuit $circuit, CompanySite $site): bool
    {
        return (int) $circuit->company_site_id === (int) $site->id;
    }

    private function validationRequestBelongsToSite(DocumentManagementValidationRequest $validationRequest, CompanySite $site): bool
    {
        $validationRequest->loadMissing('record');

        return (int) $validationRequest->record?->company_site_id === (int) $site->id;
    }

    private function canProcessValidationRequest(User&Authenticatable $user, Company $company, DocumentManagementValidationRequest $validationRequest): bool
    {
        if ($this->canManageCompanyRecord($user, $company)) {
            return true;
        }

        $validatorId = $validationRequest->currentStep?->validator_id;

        return $validatorId === null || (int) $validatorId === (int) $user->id;
    }

    private function logRecordActivity(DocumentManagementRecord $record, ?User $actor, string $action, ?string $fromStatus, ?string $toStatus, ?string $comment): void
    {
        $record->activities()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
        ]);
    }

    private function documentManagementModuleLabel(?string $moduleKey): string
    {
        return match ($moduleKey) {
            'ged-incoming' => __('main.ged_incoming_mail'),
            'ged-outgoing' => __('main.ged_outgoing_mail'),
            'ged-internal' => __('main.ged_internal_documents'),
            'ged-folders' => __('main.ged_folders'),
            'ged-assignments' => __('main.ged_assignments'),
            'ged-validation' => __('main.ged_validation_circuits'),
            'ged-history' => __('main.ged_traceability'),
            default => $moduleKey ? str($moduleKey)->replace('-', ' ')->headline()->toString() : '-',
        };
    }

    private function documentManagementSettingsMenuGroups(): array
    {
        $labels = [
            'ged-dashboard' => __('main.dashboard'),
            'ged-incoming' => __('main.ged_incoming_mail'),
            'ged-outgoing' => __('main.ged_outgoing_mail'),
            'ged-internal' => __('main.ged_internal_documents'),
            'ged-assignments' => __('main.ged_assignments'),
            'ged-validation' => __('main.ged_validation_circuits'),
            'ged-history' => __('main.ged_traceability'),
            'ged-folders' => __('main.ged_folders'),
            'ged-reports' => __('main.ged_reports'),
        ];
        $groupLabels = [
            'registry_office' => __('main.ged_registry_office'),
            'processing' => __('main.ged_processing'),
            'classification' => __('main.ged_classification'),
            'reports' => __('main.reports'),
        ];

        return collect(DocumentManagementModuleNavigation::GROUPS)
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

    private function documentManagementModuleMeta(): array
    {
        return [
            'label' => __('main.module_document_management'),
            'description' => __('main.module_document_management_description'),
            'icon' => 'bi-file-earmark-text',
            'tone' => 'green',
            'class' => 'module-document-management',
        ];
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

    private function canManageCompanyRecord(User&Authenticatable $user, Company $company): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdmin()
            && $user->subscription_id !== null
            && $company->subscription_id === $user->subscription_id;
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
}

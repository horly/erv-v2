<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\AccountingCurrency;
use App\Models\AccountingMenuPermission;
use App\Models\AccountingModuleSetting;
use App\Models\AccountingNotification;
use App\Models\HumanResourceAttendance;
use App\Models\HumanResourceContract;
use App\Models\HumanResourceDepartment;
use App\Models\HumanResourceEmployee;
use App\Models\HumanResourceLeaveRequest;
use App\Models\HumanResourcePayrollEntry;
use App\Models\HumanResourceProfileRecord;
use App\Models\User;
use App\Support\AccountingActivityFeed;
use App\Support\HumanResourcesModuleNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class HumanResourcesController extends Controller
{
    private const TABLE_ROWS_PER_PAGE = 5;

    public function dashboard(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.human-resources.dashboard', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'humanResourcesDashboard' => $this->dashboardData($site),
        ]);
    }

    public function employees(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $employees = $site->humanResourceEmployees()
            ->with(['department', 'activeContract'])
            ->whereNull('user_id')
            ->latest()
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $departmentOptions = $site->humanResourceDepartments()
            ->where('status', HumanResourceDepartment::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);
        $hasEmployeeErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingEmployee = old('form_mode') === 'edit' && old('employee_id');
        $employeeFormAction = $isEditingEmployee
            ? route('main.human-resources.employees.update', [$company, $site, old('employee_id')])
            : route('main.human-resources.employees.store', [$company, $site]);

        return view('main.modules.human-resources.employees', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'employees',
            'employees' => $employees,
            'departmentOptions' => $departmentOptions,
            'employeeFormAction' => $employeeFormAction,
            'isEditingEmployee' => $isEditingEmployee,
            'hasEmployeeErrors' => $hasEmployeeErrors,
            'employeeStatuses' => $this->employeeStatuses(),
            'employmentTypes' => $this->employmentTypes(),
        ]);
    }

    public function storeEmployee(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateEmployee($request, $site);

        $site->humanResourceEmployees()->create(array_merge($validated, [
            'user_id' => null,
            'created_by' => $user->id,
            'employee_number' => blank($validated['employee_number'] ?? null) ? null : $validated['employee_number'],
        ]));

        return redirect()
            ->route('main.human-resources.employees', [$company, $site])
            ->with('success', __('main.hr_employee_created'));
    }

    public function updateEmployee(Request $request, Company $company, CompanySite $site, HumanResourceEmployee $employee): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if ($employee->company_site_id !== $site->id || filled($employee->user_id)) {
            return redirect()->route('main.human-resources.employees', [$company, $site]);
        }

        $validated = $this->validateEmployee($request, $site, $employee);
        $validated['employee_number'] = blank($validated['employee_number'] ?? null) ? null : $validated['employee_number'];

        $employee->update($validated);

        return redirect()
            ->route('main.human-resources.employees', [$company, $site])
            ->with('success', __('main.hr_employee_updated'));
    }

    public function destroyEmployee(Company $company, CompanySite $site, HumanResourceEmployee $employee): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if ($employee->company_site_id !== $site->id || filled($employee->user_id)) {
            return redirect()->route('main.human-resources.employees', [$company, $site]);
        }

        $employeeName = $employee->full_name;
        $employee->delete();

        return redirect()
            ->route('main.human-resources.employees', [$company, $site])
            ->with('success', __('main.hr_employee_deleted', ['name' => $employeeName]))
            ->with('toast_type', 'danger');
    }

    private function validateEmployee(Request $request, CompanySite $site, ?HumanResourceEmployee $employee = null): array
    {
        return $request->validate([
            'employee_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('human_resource_employees', 'employee_number')->ignore($employee?->id),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'human_resource_department_id' => [
                'nullable',
                Rule::exists('human_resource_departments', 'id')->where('company_site_id', $site->id),
            ],
            'professional_email' => ['nullable', 'email', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(array_keys($this->employmentTypes()))],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'status' => ['required', Rule::in(array_keys($this->employeeStatuses()))],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function departments(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        $departments = $site->humanResourceDepartments()
            ->with('manager')
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $managerOptions = $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->where('status', HumanResourceEmployee::STATUS_ACTIVE)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name', 'job_title']);
        $hasDepartmentErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingDepartment = old('form_mode') === 'edit' && old('department_id');
        $departmentFormAction = $isEditingDepartment
            ? route('main.human-resources.departments.update', [$company, $site, old('department_id')])
            : route('main.human-resources.departments.store', [$company, $site]);

        return view('main.modules.human-resources.departments', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'departments',
            'departments' => $departments,
            'managerOptions' => $managerOptions,
            'departmentFormAction' => $departmentFormAction,
            'isEditingDepartment' => $isEditingDepartment,
            'hasDepartmentErrors' => $hasDepartmentErrors,
        ]);
    }

    public function storeDepartment(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        $validated = $this->validateDepartment($request, $site);

        $site->humanResourceDepartments()->create(array_merge($validated, [
            'status' => $validated['status'] ?? HumanResourceDepartment::STATUS_ACTIVE,
        ]));

        return redirect()
            ->route('main.human-resources.departments', [$company, $site])
            ->with('success', __('main.hr_department_created'));
    }

    public function updateDepartment(Request $request, Company $company, CompanySite $site, HumanResourceDepartment $department): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if ($department->company_site_id !== $site->id) {
            return redirect()->route('main.human-resources.departments', [$company, $site]);
        }

        $department->update($this->validateDepartment($request, $site, $department));

        return redirect()
            ->route('main.human-resources.departments', [$company, $site])
            ->with('success', __('main.hr_department_updated'));
    }

    public function destroyDepartment(Company $company, CompanySite $site, HumanResourceDepartment $department): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if ($department->company_site_id !== $site->id) {
            return redirect()->route('main.human-resources.departments', [$company, $site]);
        }

        $departmentName = $department->name;
        $department->delete();

        return redirect()
            ->route('main.human-resources.departments', [$company, $site])
            ->with('success', __('main.hr_department_deleted', ['name' => $departmentName]))
            ->with('toast_type', 'danger');
    }

    private function validateDepartment(Request $request, CompanySite $site, ?HumanResourceDepartment $department = null): array
    {
        return $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('human_resource_departments', 'code')
                    ->where('company_site_id', $site->id)
                    ->ignore($department?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'manager_employee_id' => [
                'nullable',
                Rule::exists('human_resource_employees', 'id')
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in([HumanResourceDepartment::STATUS_ACTIVE, HumanResourceDepartment::STATUS_INACTIVE])],
        ]);
    }

    private function employeeStatuses(): array
    {
        return [
            HumanResourceEmployee::STATUS_ACTIVE => __('main.hr_employee_status_active'),
            HumanResourceEmployee::STATUS_ON_LEAVE => __('main.hr_employee_status_on_leave'),
            HumanResourceEmployee::STATUS_SUSPENDED => __('main.hr_employee_status_suspended'),
            HumanResourceEmployee::STATUS_TERMINATED => __('main.hr_employee_status_terminated'),
        ];
    }

    private function employmentTypes(): array
    {
        return [
            HumanResourceEmployee::EMPLOYMENT_FULL_TIME => __('main.hr_employment_type_full_time'),
            HumanResourceEmployee::EMPLOYMENT_PART_TIME => __('main.hr_employment_type_part_time'),
            HumanResourceEmployee::EMPLOYMENT_CONTRACTOR => __('main.hr_employment_type_contractor'),
            HumanResourceEmployee::EMPLOYMENT_INTERN => __('main.hr_employment_type_intern'),
        ];
    }

    public function attendance(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $employeeIds = $this->standaloneEmployeeIds($site);
        [$period, $dateFrom, $dateTo] = $this->attendancePeriod(request());
        $attendances = HumanResourceAttendance::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->latest('work_date')
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $employeeOptions = $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name']);
        $allPeriodAttendances = HumanResourceAttendance::query()
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->get();
        $attendanceReport = $this->attendanceReportData($allPeriodAttendances);
        $hasAttendanceErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingAttendance = old('form_mode') === 'edit' && old('attendance_id');
        $attendanceFormAction = $isEditingAttendance
            ? route('main.human-resources.attendance.update', [$company, $site, old('attendance_id')])
            : route('main.human-resources.attendance.store', [$company, $site]);

        return view('main.modules.human-resources.attendance', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'attendance',
            'attendances' => $attendances,
            'employeeOptions' => $employeeOptions,
            'attendanceReport' => $attendanceReport,
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'attendanceFormAction' => $attendanceFormAction,
            'isEditingAttendance' => $isEditingAttendance,
            'hasAttendanceErrors' => $hasAttendanceErrors,
            'attendanceStatuses' => $this->attendanceStatuses(),
        ]);
    }

    public function printAttendanceReport(Request $request, Company $company, CompanySite $site): Response|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $employeeIds = $this->standaloneEmployeeIds($site);
        [$period, $dateFrom, $dateTo] = $this->attendancePeriod($request);
        $attendances = HumanResourceAttendance::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->orderBy('work_date')
            ->orderBy('human_resource_employee_id')
            ->get();

        return Pdf::loadView('main.modules.human-resources.pdf.attendance-report', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'attendances' => $attendances,
            'attendanceReport' => $this->attendanceReportData($attendances),
            'attendanceStatuses' => $this->attendanceStatuses(),
            'periodLabel' => $this->attendancePeriodLabel($period),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ])->setPaper('a4', 'landscape')
            ->stream('rapport-presences-'.$site->id.'-'.$dateFrom.'-'.$dateTo.'.pdf');
    }

    public function storeAttendance(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        $validated = $this->validateAttendance($request, $site);
        $validated['worked_hours'] = $this->attendanceWorkedHours($validated);

        HumanResourceAttendance::query()->create($validated);

        return redirect()
            ->route('main.human-resources.attendance', [$company, $site])
            ->with('success', __('main.hr_attendance_created'));
    }

    public function updateAttendance(Request $request, Company $company, CompanySite $site, HumanResourceAttendance $attendance): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->attendanceBelongsToSite($attendance, $site)) {
            return redirect()->route('main.human-resources.attendance', [$company, $site]);
        }

        $validated = $this->validateAttendance($request, $site, $attendance);
        $validated['worked_hours'] = $this->attendanceWorkedHours($validated);
        $attendance->update($validated);

        return redirect()
            ->route('main.human-resources.attendance', [$company, $site])
            ->with('success', __('main.hr_attendance_updated'));
    }

    public function destroyAttendance(Company $company, CompanySite $site, HumanResourceAttendance $attendance): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->attendanceBelongsToSite($attendance, $site)) {
            return redirect()->route('main.human-resources.attendance', [$company, $site]);
        }

        $attendance->delete();

        return redirect()
            ->route('main.human-resources.attendance', [$company, $site])
            ->with('success', __('main.hr_attendance_deleted'))
            ->with('toast_type', 'danger');
    }

    public function importAttendance(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        $request->validate([
            'attendance_file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx'],
        ]);

        $rows = $this->attendanceImportRows($request->file('attendance_file')->getRealPath(), $request->file('attendance_file')->getClientOriginalExtension());
        $employees = $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->get()
            ->keyBy('employee_number');
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $employeeNumber = trim((string) ($row['matricule'] ?? $row['employee_number'] ?? $row['numero'] ?? ''));
            $employee = $employees->get($employeeNumber);

            if (! $employee) {
                $errors[] = __('main.hr_import_error_employee', ['line' => $line, 'value' => $employeeNumber ?: '-']);
                continue;
            }

            try {
                $workDate = Carbon::parse($row['date'] ?? $row['work_date'] ?? null)->toDateString();
            } catch (\Throwable) {
                $errors[] = __('main.hr_import_error_date', ['line' => $line]);
                continue;
            }

            $status = $this->normalizeAttendanceStatus((string) ($row['statut'] ?? $row['status'] ?? HumanResourceAttendance::STATUS_PRESENT));
            $payload = [
                'human_resource_employee_id' => $employee->id,
                'work_date' => $workDate,
                'check_in_at' => $this->normalizeTime($row['arrivee'] ?? $row['check_in'] ?? null),
                'check_out_at' => $this->normalizeTime($row['depart'] ?? $row['check_out'] ?? null),
                'status' => $status,
                'notes' => $row['note'] ?? $row['notes'] ?? null,
            ];
            $payload['worked_hours'] = $this->attendanceWorkedHours($payload, (float) str_replace(',', '.', (string) ($row['heures'] ?? $row['worked_hours'] ?? 0)));
            $record = HumanResourceAttendance::query()->updateOrCreate(
                ['human_resource_employee_id' => $employee->id, 'work_date' => $workDate],
                $payload
            );
            $record->wasRecentlyCreated ? $created++ : $updated++;
        }

        $message = __('main.hr_attendance_imported', ['created' => $created, 'updated' => $updated]);

        if ($errors !== []) {
            $message .= ' '.__('main.hr_attendance_import_errors', ['count' => count($errors)]);
        }

        return redirect()
            ->route('main.human-resources.attendance', [$company, $site])
            ->with('success', $message)
            ->with('hr_import_errors', array_slice($errors, 0, 8));
    }

    private function validateAttendance(Request $request, CompanySite $site, ?HumanResourceAttendance $attendance = null): array
    {
        return $request->validate([
            'human_resource_employee_id' => [
                'required',
                Rule::exists('human_resource_employees', 'id')
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id'),
            ],
            'work_date' => [
                'required',
                'date',
                Rule::unique('human_resource_attendances', 'work_date')
                    ->where('human_resource_employee_id', $request->integer('human_resource_employee_id'))
                    ->ignore($attendance?->id),
            ],
            'check_in_at' => ['nullable', 'date_format:H:i'],
            'check_out_at' => ['nullable', 'date_format:H:i'],
            'worked_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'status' => ['required', Rule::in(array_keys($this->attendanceStatuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function attendanceBelongsToSite(HumanResourceAttendance $attendance, CompanySite $site): bool
    {
        return $attendance->employee()
            ->where('company_site_id', $site->id)
            ->whereNull('user_id')
            ->exists();
    }

    private function standaloneEmployeeIds(CompanySite $site)
    {
        return $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->pluck('id');
    }

    private function attendanceStatuses(): array
    {
        return [
            HumanResourceAttendance::STATUS_PRESENT => __('main.hr_attendance_status_present'),
            HumanResourceAttendance::STATUS_LATE => __('main.hr_attendance_status_late'),
            HumanResourceAttendance::STATUS_ABSENT => __('main.hr_attendance_status_absent'),
            HumanResourceAttendance::STATUS_REMOTE => __('main.hr_attendance_status_remote'),
            HumanResourceAttendance::STATUS_ON_LEAVE => __('main.hr_attendance_status_on_leave'),
        ];
    }

    private function attendanceWorkedHours(array $data, float $fallback = 0): float
    {
        if (! empty($data['worked_hours'])) {
            return (float) $data['worked_hours'];
        }

        if (empty($data['check_in_at']) || empty($data['check_out_at'])) {
            return $fallback;
        }

        $start = Carbon::createFromFormat('H:i', substr((string) $data['check_in_at'], 0, 5));
        $end = Carbon::createFromFormat('H:i', substr((string) $data['check_out_at'], 0, 5));

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    private function attendancePeriod(Request $request): array
    {
        $period = $request->query('period', 'today');
        $today = today();

        return match ($period) {
            'week' => ['week', $today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'month' => ['month', $today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'year' => ['year', $today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            'custom' => [
                'custom',
                Carbon::parse($request->query('date_from', $today->toDateString()))->toDateString(),
                Carbon::parse($request->query('date_to', $today->toDateString()))->toDateString(),
            ],
            default => ['today', $today->toDateString(), $today->toDateString()],
        };
    }

    private function attendancePeriodLabel(string $period): string
    {
        return match ($period) {
            'week' => __('main.hr_period_week'),
            'month' => __('main.hr_period_month'),
            'year' => __('main.hr_period_year'),
            'custom' => __('main.hr_period_custom'),
            default => __('main.hr_period_today'),
        };
    }

    private function attendanceReportData($attendances): array
    {
        return [
            'present' => $attendances->where('status', HumanResourceAttendance::STATUS_PRESENT)->count(),
            'late' => $attendances->where('status', HumanResourceAttendance::STATUS_LATE)->count(),
            'absent' => $attendances->where('status', HumanResourceAttendance::STATUS_ABSENT)->count(),
            'remote' => $attendances->where('status', HumanResourceAttendance::STATUS_REMOTE)->count(),
            'on_leave' => $attendances->where('status', HumanResourceAttendance::STATUS_ON_LEAVE)->count(),
            'worked_hours' => $attendances->sum('worked_hours'),
            'total' => $attendances->count(),
        ];
    }

    private function normalizeAttendanceStatus(string $status): string
    {
        $status = str($status)->lower()->ascii()->replace([' ', '-'], '_')->toString();

        return match ($status) {
            'present', 'present(e)', 'present_' => HumanResourceAttendance::STATUS_PRESENT,
            'retard', 'en_retard', 'late' => HumanResourceAttendance::STATUS_LATE,
            'absent', 'absence' => HumanResourceAttendance::STATUS_ABSENT,
            'distance', 'a_distance', 'remote' => HumanResourceAttendance::STATUS_REMOTE,
            'conge', 'en_conge', 'leave', 'on_leave' => HumanResourceAttendance::STATUS_ON_LEAVE,
            default => HumanResourceAttendance::STATUS_PRESENT,
        };
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function attendanceImportRows(string $path, string $extension): array
    {
        return strtolower($extension) === 'xlsx'
            ? $this->attendanceXlsxRows($path)
            : $this->attendanceCsvRows($path);
    }

    private function attendanceCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            if (count($line) === 1 && str_contains($line[0], ',')) {
                $line = str_getcsv($line[0], ',');
            }

            if ($headers === null) {
                $headers = array_map(fn ($value) => $this->normalizeImportHeader((string) $value), $line);
                continue;
            }

            $rows[] = array_combine($headers, array_pad($line, count($headers), null)) ?: [];
        }

        fclose($handle);

        return $rows;
    }

    private function attendanceXlsxRows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['attendance_file' => __('main.hr_xlsx_not_supported')]);
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            foreach ($shared->si ?? [] as $string) {
                $sharedStrings[] = (string) ($string->t ?? $string->r->t ?? '');
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $headers = null;
        $rows = [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $value = (string) $cell->v;
                $values[] = ((string) $cell['t']) === 's'
                    ? ($sharedStrings[(int) $value] ?? '')
                    : $value;
            }

            if ($headers === null) {
                $headers = array_map(fn ($value) => $this->normalizeImportHeader((string) $value), $values);
                continue;
            }

            $rows[] = array_combine($headers, array_pad($values, count($headers), null)) ?: [];
        }

        return $rows;
    }

    private function normalizeImportHeader(string $header): string
    {
        return str($header)
            ->lower()
            ->ascii()
            ->replace([' ', '-', '.', "'", '’'], '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    public function contracts(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $employeeIds = $this->standaloneEmployeeIds($site);
        $contracts = HumanResourceContract::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->latest('start_date')
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $employeeOptions = $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name']);
        $hasContractErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingContract = old('form_mode') === 'edit' && old('contract_id');
        $contractFormAction = $isEditingContract
            ? route('main.human-resources.contracts.update', [$company, $site, old('contract_id')])
            : route('main.human-resources.contracts.store', [$company, $site]);

        return view('main.modules.human-resources.contracts', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'contracts',
            'contracts' => $contracts,
            'employeeOptions' => $employeeOptions,
            'contractFormAction' => $contractFormAction,
            'isEditingContract' => $isEditingContract,
            'hasContractErrors' => $hasContractErrors,
            'contractTypes' => $this->contractTypes(),
            'contractStatuses' => $this->contractStatuses(),
            'currencyOptions' => $this->hrCurrencyOptions($site),
        ]);
    }

    public function storeContract(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateContract($request, $site);
        $validated['created_by'] = $user->id;
        $validated['reference'] = blank($validated['reference'] ?? null) ? $this->nextContractReference() : $validated['reference'];

        HumanResourceContract::query()->create($validated);

        return redirect()
            ->route('main.human-resources.contracts', [$company, $site])
            ->with('success', __('main.hr_contract_created'));
    }

    public function updateContract(Request $request, Company $company, CompanySite $site, HumanResourceContract $contract): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->contractBelongsToSite($contract, $site)) {
            return redirect()->route('main.human-resources.contracts', [$company, $site]);
        }

        $validated = $this->validateContract($request, $site, $contract);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $contract->reference : $validated['reference'];

        $contract->update($validated);

        return redirect()
            ->route('main.human-resources.contracts', [$company, $site])
            ->with('success', __('main.hr_contract_updated'));
    }

    public function destroyContract(Company $company, CompanySite $site, HumanResourceContract $contract): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->contractBelongsToSite($contract, $site)) {
            return redirect()->route('main.human-resources.contracts', [$company, $site]);
        }

        $reference = $contract->reference;
        $contract->delete();

        return redirect()
            ->route('main.human-resources.contracts', [$company, $site])
            ->with('success', __('main.hr_contract_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    private function validateContract(Request $request, CompanySite $site, ?HumanResourceContract $contract = null): array
    {
        $validated = $request->validate([
            'human_resource_employee_id' => [
                'required',
                Rule::exists('human_resource_employees', 'id')
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id'),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('human_resource_contracts', 'reference')->ignore($contract?->id),
            ],
            'type' => ['required', Rule::in(array_keys($this->contractTypes()))],
            'status' => ['required', Rule::in(array_keys($this->contractStatuses()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys($this->hrCurrencyOptions($site)))],
            'monthly_salary' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === HumanResourceContract::STATUS_ACTIVE) {
            $activeContractExists = HumanResourceContract::query()
                ->where('human_resource_employee_id', $validated['human_resource_employee_id'])
                ->where('status', HumanResourceContract::STATUS_ACTIVE)
                ->when($contract, fn ($query) => $query->whereKeyNot($contract->id))
                ->exists();

            if ($activeContractExists) {
                throw ValidationException::withMessages([
                    'status' => __('main.hr_contract_active_exists'),
                ]);
            }
        }

        return $validated;
    }

    private function nextContractReference(): string
    {
        $nextId = ((int) (HumanResourceContract::query()->max('id') ?? 0)) + 1;

        do {
            $reference = HumanResourceContract::referenceFromId($nextId++);
        } while (HumanResourceContract::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function hrCurrencyOptions(CompanySite $site): array
    {
        $currencies = AccountingCurrency::query()
            ->where('company_site_id', $site->id)
            ->where('status', AccountingCurrency::STATUS_ACTIVE)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (AccountingCurrency $currency): array => [
                $currency->code => trim($currency->name.' ('.$currency->code.($currency->symbol ? ' - '.$currency->symbol : '').')'),
            ])
            ->all();

        return ['USD' => $currencies['USD'] ?? 'Dollar américain (USD - $)'] + $currencies;
    }

    private function contractBelongsToSite(HumanResourceContract $contract, CompanySite $site): bool
    {
        return $contract->employee()
            ->where('company_site_id', $site->id)
            ->whereNull('user_id')
            ->exists();
    }

    private function contractTypes(): array
    {
        return [
            HumanResourceContract::TYPE_PERMANENT => __('main.hr_contract_type_permanent'),
            HumanResourceContract::TYPE_FIXED_TERM => __('main.hr_contract_type_fixed_term'),
            HumanResourceContract::TYPE_CONSULTANT => __('main.hr_contract_type_consultant'),
            HumanResourceContract::TYPE_INTERNSHIP => __('main.hr_contract_type_internship'),
        ];
    }

    private function contractStatuses(): array
    {
        return [
            HumanResourceContract::STATUS_DRAFT => __('main.hr_contract_status_draft'),
            HumanResourceContract::STATUS_ACTIVE => __('main.hr_contract_status_active'),
            HumanResourceContract::STATUS_EXPIRED => __('main.hr_contract_status_expired'),
            HumanResourceContract::STATUS_TERMINATED => __('main.hr_contract_status_terminated'),
        ];
    }

    public function leave(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $employeeIds = $this->standaloneEmployeeIds($site);
        $leaves = HumanResourceLeaveRequest::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->latest('start_date')
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $employeeOptions = $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name']);
        $hasLeaveErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingLeave = old('form_mode') === 'edit' && old('leave_id');
        $leaveFormAction = $isEditingLeave
            ? route('main.human-resources.leave.update', [$company, $site, old('leave_id')])
            : route('main.human-resources.leave.store', [$company, $site]);

        return view('main.modules.human-resources.leave', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'leave',
            'leaves' => $leaves,
            'employeeOptions' => $employeeOptions,
            'leaveFormAction' => $leaveFormAction,
            'isEditingLeave' => $isEditingLeave,
            'hasLeaveErrors' => $hasLeaveErrors,
            'leaveTypes' => $this->leaveTypes(),
            'leaveStatuses' => $this->leaveStatuses(),
        ]);
    }

    public function storeLeave(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateLeave($request, $site);
        $validated['created_by'] = $user->id;
        $validated['reference'] = blank($validated['reference'] ?? null) ? $this->nextLeaveReference() : $validated['reference'];
        $validated['days_count'] = $this->leaveDaysCount($validated['start_date'], $validated['end_date']);
        $validated['approved_by'] = $validated['status'] === HumanResourceLeaveRequest::STATUS_APPROVED ? $user->id : null;
        $validated['approved_at'] = $validated['status'] === HumanResourceLeaveRequest::STATUS_APPROVED ? now() : null;

        HumanResourceLeaveRequest::query()->create($validated);

        return redirect()
            ->route('main.human-resources.leave', [$company, $site])
            ->with('success', __('main.hr_leave_created'));
    }

    public function updateLeave(Request $request, Company $company, CompanySite $site, HumanResourceLeaveRequest $leave): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        if (! $this->leaveBelongsToSite($leave, $site)) {
            return redirect()->route('main.human-resources.leave', [$company, $site]);
        }

        $validated = $this->validateLeave($request, $site, $leave);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $leave->reference : $validated['reference'];
        $validated['days_count'] = $this->leaveDaysCount($validated['start_date'], $validated['end_date']);
        $validated['approved_by'] = $validated['status'] === HumanResourceLeaveRequest::STATUS_APPROVED ? ($leave->approved_by ?: $user->id) : null;
        $validated['approved_at'] = $validated['status'] === HumanResourceLeaveRequest::STATUS_APPROVED ? ($leave->approved_at ?: now()) : null;

        $leave->update($validated);

        return redirect()
            ->route('main.human-resources.leave', [$company, $site])
            ->with('success', __('main.hr_leave_updated'));
    }

    public function destroyLeave(Company $company, CompanySite $site, HumanResourceLeaveRequest $leave): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->leaveBelongsToSite($leave, $site)) {
            return redirect()->route('main.human-resources.leave', [$company, $site]);
        }

        $reference = $leave->reference;
        $leave->delete();

        return redirect()
            ->route('main.human-resources.leave', [$company, $site])
            ->with('success', __('main.hr_leave_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    private function validateLeave(Request $request, CompanySite $site, ?HumanResourceLeaveRequest $leave = null): array
    {
        $validated = $request->validate([
            'human_resource_employee_id' => [
                'required',
                Rule::exists('human_resource_employees', 'id')
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id'),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('human_resource_leave_requests', 'reference')->ignore($leave?->id),
            ],
            'type' => ['required', Rule::in(array_keys($this->leaveTypes()))],
            'status' => ['required', Rule::in(array_keys($this->leaveStatuses()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if (in_array($validated['status'], [HumanResourceLeaveRequest::STATUS_PENDING, HumanResourceLeaveRequest::STATUS_APPROVED], true)) {
            $overlapExists = HumanResourceLeaveRequest::query()
                ->where('human_resource_employee_id', $validated['human_resource_employee_id'])
                ->whereIn('status', [HumanResourceLeaveRequest::STATUS_PENDING, HumanResourceLeaveRequest::STATUS_APPROVED])
                ->whereDate('start_date', '<=', $validated['end_date'])
                ->whereDate('end_date', '>=', $validated['start_date'])
                ->when($leave, fn ($query) => $query->whereKeyNot($leave->id))
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'start_date' => __('main.hr_leave_overlap_exists'),
                ]);
            }
        }

        return $validated;
    }

    private function nextLeaveReference(): string
    {
        $nextId = ((int) (HumanResourceLeaveRequest::query()->max('id') ?? 0)) + 1;

        do {
            $reference = HumanResourceLeaveRequest::referenceFromId($nextId++);
        } while (HumanResourceLeaveRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function leaveDaysCount(string $startDate, string $endDate): float
    {
        return (float) (Carbon::parse($startDate)->startOfDay()->diffInDays(Carbon::parse($endDate)->startOfDay()) + 1);
    }

    private function leaveBelongsToSite(HumanResourceLeaveRequest $leave, CompanySite $site): bool
    {
        return $leave->employee()
            ->where('company_site_id', $site->id)
            ->whereNull('user_id')
            ->exists();
    }

    private function leaveTypes(): array
    {
        return [
            HumanResourceLeaveRequest::TYPE_ANNUAL => __('main.hr_leave_type_annual'),
            HumanResourceLeaveRequest::TYPE_SICK => __('main.hr_leave_type_sick'),
            HumanResourceLeaveRequest::TYPE_PERSONAL => __('main.hr_leave_type_personal'),
            HumanResourceLeaveRequest::TYPE_MATERNITY => __('main.hr_leave_type_maternity'),
            HumanResourceLeaveRequest::TYPE_OTHER => __('main.hr_leave_type_other'),
        ];
    }

    private function leaveStatuses(): array
    {
        return [
            HumanResourceLeaveRequest::STATUS_PENDING => __('main.hr_leave_status_pending'),
            HumanResourceLeaveRequest::STATUS_APPROVED => __('main.hr_leave_status_approved'),
            HumanResourceLeaveRequest::STATUS_REJECTED => __('main.hr_leave_status_rejected'),
            HumanResourceLeaveRequest::STATUS_CANCELLED => __('main.hr_leave_status_cancelled'),
        ];
    }

    public function payroll(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $employeeIds = $this->standaloneEmployeeIds($site);
        $payrollEntries = HumanResourcePayrollEntry::query()
            ->with(['employee.department', 'contract'])
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->latest('period_month')
            ->latest('id')
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $employeeOptions = $site->humanResourceEmployees()
            ->with('activeContract')
            ->whereNull('user_id')
            ->where('status', HumanResourceEmployee::STATUS_ACTIVE)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name']);
        $employeePayrollDefaults = $employeeOptions->mapWithKeys(fn (HumanResourceEmployee $employee): array => [
            $employee->id => [
                'contract_id' => $employee->activeContract?->id,
                'currency' => $employee->activeContract?->currency ?: 'USD',
                'gross_salary' => number_format((float) ($employee->activeContract?->monthly_salary ?? 0), 2, '.', ''),
            ],
        ]);
        $hasPayrollErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingPayroll = old('form_mode') === 'edit' && old('payroll_id');
        $payrollFormAction = $isEditingPayroll
            ? route('main.human-resources.payroll.update', [$company, $site, old('payroll_id')])
            : route('main.human-resources.payroll.store', [$company, $site]);

        return view('main.modules.human-resources.payroll', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'payroll',
            'payrollEntries' => $payrollEntries,
            'employeeOptions' => $employeeOptions,
            'employeePayrollDefaults' => $employeePayrollDefaults,
            'payrollFormAction' => $payrollFormAction,
            'isEditingPayroll' => $isEditingPayroll,
            'hasPayrollErrors' => $hasPayrollErrors,
            'payrollStatuses' => $this->payrollStatuses(),
            'currencyOptions' => $this->hrCurrencyOptions($site),
            'currentPeriod' => now()->startOfMonth()->toDateString(),
        ]);
    }

    public function storePayroll(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validatePayroll($request, $site);
        $validated['created_by'] = $user->id;
        $validated['reference'] = blank($validated['reference'] ?? null) ? $this->nextPayrollReference() : $validated['reference'];
        $validated = $this->preparePayrollAmounts($validated);

        HumanResourcePayrollEntry::query()->create($validated);

        return redirect()
            ->route('main.human-resources.payroll', [$company, $site])
            ->with('success', __('main.hr_payroll_created'));
    }

    public function updatePayroll(Request $request, Company $company, CompanySite $site, HumanResourcePayrollEntry $payroll): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->payrollBelongsToSite($payroll, $site)) {
            return redirect()->route('main.human-resources.payroll', [$company, $site]);
        }

        $validated = $this->validatePayroll($request, $site, $payroll);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $payroll->reference : $validated['reference'];
        $validated = $this->preparePayrollAmounts($validated);

        $payroll->update($validated);

        return redirect()
            ->route('main.human-resources.payroll', [$company, $site])
            ->with('success', __('main.hr_payroll_updated'));
    }

    public function destroyPayroll(Company $company, CompanySite $site, HumanResourcePayrollEntry $payroll): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->payrollBelongsToSite($payroll, $site)) {
            return redirect()->route('main.human-resources.payroll', [$company, $site]);
        }

        $reference = $payroll->reference;
        $payroll->delete();

        return redirect()
            ->route('main.human-resources.payroll', [$company, $site])
            ->with('success', __('main.hr_payroll_deleted', ['reference' => $reference]))
            ->with('toast_type', 'danger');
    }

    private function validatePayroll(Request $request, CompanySite $site, ?HumanResourcePayrollEntry $payroll = null): array
    {
        $validated = $request->validate([
            'human_resource_employee_id' => [
                'required',
                Rule::exists('human_resource_employees', 'id')
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id'),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('human_resource_payroll_entries', 'reference')->ignore($payroll?->id),
            ],
            'period_month' => ['required', 'date'],
            'payment_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys($this->payrollStatuses()))],
            'currency' => ['required', 'string', 'size:3', Rule::in(array_keys($this->hrCurrencyOptions($site)))],
            'gross_salary' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'allowances' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'deductions' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $periodMonth = Carbon::parse($validated['period_month'])->startOfMonth()->toDateString();
        $duplicateExists = HumanResourcePayrollEntry::query()
            ->where('human_resource_employee_id', $validated['human_resource_employee_id'])
            ->whereDate('period_month', $periodMonth)
            ->when($payroll, fn ($query) => $query->whereKeyNot($payroll->id))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'period_month' => __('main.hr_payroll_period_exists'),
            ]);
        }

        $validated['period_month'] = $periodMonth;
        $validated['human_resource_contract_id'] = HumanResourceContract::query()
            ->where('human_resource_employee_id', $validated['human_resource_employee_id'])
            ->where('status', HumanResourceContract::STATUS_ACTIVE)
            ->latest('start_date')
            ->value('id');

        return $validated;
    }

    private function preparePayrollAmounts(array $validated): array
    {
        $validated['allowances'] = (float) ($validated['allowances'] ?? 0);
        $validated['deductions'] = (float) ($validated['deductions'] ?? 0);
        $validated['gross_salary'] = (float) $validated['gross_salary'];
        $validated['net_salary'] = max(0, $validated['gross_salary'] + $validated['allowances'] - $validated['deductions']);
        $validated['paid_at'] = $validated['status'] === HumanResourcePayrollEntry::STATUS_PAID ? now() : null;

        return $validated;
    }

    private function nextPayrollReference(): string
    {
        $nextId = ((int) (HumanResourcePayrollEntry::query()->max('id') ?? 0)) + 1;

        do {
            $reference = HumanResourcePayrollEntry::referenceFromId($nextId++);
        } while (HumanResourcePayrollEntry::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function payrollBelongsToSite(HumanResourcePayrollEntry $payroll, CompanySite $site): bool
    {
        return $payroll->employee()
            ->where('company_site_id', $site->id)
            ->whereNull('user_id')
            ->exists();
    }

    private function payrollStatuses(): array
    {
        return [
            HumanResourcePayrollEntry::STATUS_DRAFT => __('main.hr_payroll_status_draft'),
            HumanResourcePayrollEntry::STATUS_VALIDATED => __('main.hr_payroll_status_validated'),
            HumanResourcePayrollEntry::STATUS_PAID => __('main.hr_payroll_status_paid'),
            HumanResourcePayrollEntry::STATUS_CANCELLED => __('main.hr_payroll_status_cancelled'),
        ];
    }

    private function listPage(Company $company, CompanySite $site, string $activePage, string $title, string $subtitle, string $icon, array $columns, $rows): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.human-resources.index', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => $activePage,
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'pageIcon' => $icon,
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }

    private function humanResourcesAccess(Company $company, CompanySite $site): array|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($site->company_id !== $company->id || ! $this->canAccessCompanySite($user, $company, $site)) {
            return $this->redirectMainArea($user);
        }

        if (! in_array(CompanySite::MODULE_HUMAN_RESOURCES, $this->availableSiteModulesForUser($user, $site), true)) {
            return redirect()->route('main.companies.sites.show', [$company, $site]);
        }

        $company->load('subscription');
        $site->load('responsible');

        return [$user, $this->humanResourcesModuleMeta()];
    }

    public function reports(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        return view('main.modules.human-resources.reports', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'reports',
            'leaveStatuses' => $this->leaveStatuses(),
            'payrollStatuses' => $this->payrollStatuses(),
        ], $this->humanResourcesReportData($request, $site)));
    }

    public function printReport(Request $request, Company $company, CompanySite $site): Response|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        return Pdf::loadView('main.modules.human-resources.pdf.reports', array_merge([
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'attendanceStatuses' => $this->attendanceStatuses(),
            'leaveStatuses' => $this->leaveStatuses(),
            'payrollStatuses' => $this->payrollStatuses(),
        ], $this->humanResourcesReportData($request, $site)))
            ->setPaper('a4', 'landscape')
            ->stream('rapport-rh-'.$site->id.'-'.now()->format('Ymd').'.pdf');
    }

    private function humanResourcesReportData(Request $request, CompanySite $site): array
    {
        [$period, $dateFrom, $dateTo] = $this->attendancePeriod($request);

        if (Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $employeeIds = $this->standaloneEmployeeIds($site);
        $employees = $site->humanResourceEmployees()
            ->with('department')
            ->whereNull('user_id')
            ->get();
        $departments = $site->humanResourceDepartments()
            ->withCount(['employees' => fn ($query) => $query->whereNull('user_id')])
            ->orderBy('name')
            ->get();
        $attendances = HumanResourceAttendance::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('work_date', [$dateFrom, $dateTo])
            ->orderBy('work_date')
            ->get();
        $contracts = HumanResourceContract::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereDate('start_date', '<=', $dateTo)
            ->where(fn ($query) => $query
                ->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $dateFrom))
            ->get();
        $leaves = HumanResourceLeaveRequest::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereDate('start_date', '<=', $dateTo)
            ->whereDate('end_date', '>=', $dateFrom)
            ->latest('start_date')
            ->get();
        $payrollEntries = HumanResourcePayrollEntry::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('period_month', [
                Carbon::parse($dateFrom)->startOfMonth()->toDateString(),
                Carbon::parse($dateTo)->endOfMonth()->toDateString(),
            ])
            ->latest('period_month')
            ->get();
        $attendanceReport = $this->attendanceReportData($attendances);
        $activeEmployees = $employees->where('status', HumanResourceEmployee::STATUS_ACTIVE);
        $activeContracts = $contracts->where('status', HumanResourceContract::STATUS_ACTIVE);
        $pendingLeaves = $leaves->where('status', HumanResourceLeaveRequest::STATUS_PENDING);
        $approvedLeaves = $leaves->where('status', HumanResourceLeaveRequest::STATUS_APPROVED);
        $payrollNetByCurrency = $payrollEntries
            ->groupBy('currency')
            ->map(fn ($entries) => $entries->sum('net_salary'));

        $departmentRows = $departments->map(function (HumanResourceDepartment $department) use ($employees, $contracts): array {
            $departmentEmployees = $employees->where('human_resource_department_id', $department->id);
            $departmentEmployeeIds = $departmentEmployees->pluck('id');

            return [
                'name' => $department->name,
                'employees' => $departmentEmployees->count(),
                'active_employees' => $departmentEmployees->where('status', HumanResourceEmployee::STATUS_ACTIVE)->count(),
                'active_contracts' => $contracts
                    ->whereIn('human_resource_employee_id', $departmentEmployeeIds)
                    ->where('status', HumanResourceContract::STATUS_ACTIVE)
                    ->count(),
            ];
        });

        return [
            'period' => $period,
            'periodLabel' => $this->attendancePeriodLabel($period),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => [
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'metrics' => [
                [
                    'label' => __('main.hr_employees'),
                    'value' => $employees->count(),
                    'meta' => __('main.hr_active_employees').': '.$activeEmployees->count(),
                    'icon' => 'bi-people',
                    'tone' => 'blue',
                ],
                [
                    'label' => __('main.hr_attendance'),
                    'value' => $attendanceReport['total'],
                    'meta' => __('main.hr_worked_hours').': '.number_format((float) $attendanceReport['worked_hours'], 2, ',', ' '),
                    'icon' => 'bi-calendar-week',
                    'tone' => 'green',
                ],
                [
                    'label' => __('main.hr_leave'),
                    'value' => $leaves->count(),
                    'meta' => __('main.hr_leave_status_pending').': '.$pendingLeaves->count(),
                    'icon' => 'bi-calendar-check',
                    'tone' => 'violet',
                ],
                [
                    'label' => __('main.hr_monthly_payroll'),
                    'value' => $payrollEntries->count(),
                    'meta' => $this->formatCurrencyTotals($payrollNetByCurrency),
                    'icon' => 'bi-cash-stack',
                    'tone' => 'amber',
                ],
            ],
            'attendanceReport' => $attendanceReport,
            'employees' => $employees,
            'departments' => $departments,
            'contracts' => $contracts,
            'activeContracts' => $activeContracts,
            'leaves' => $leaves,
            'approvedLeaves' => $approvedLeaves,
            'payrollEntries' => $payrollEntries,
            'payrollNetByCurrency' => $payrollNetByCurrency,
            'departmentRows' => $departmentRows,
        ];
    }

    private function formatCurrencyTotals($totals): string
    {
        if ($totals->isEmpty()) {
            return '0,00 USD';
        }

        return $totals
            ->map(fn ($amount, string $currency): string => number_format((float) $amount, 2, ',', ' ').' '.$currency)
            ->values()
            ->implode(' / ');
    }

    public function settings(Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

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
                ->orWhere('company_site_user.module_permissions', 'like', '%"'.CompanySite::MODULE_HUMAN_RESOURCES.'"%'))
            ->orderBy('users.name')
            ->paginate(1, ['users.*'], 'users_page')
            ->withQueryString();
        $menuKeys = HumanResourcesModuleNavigation::keys();
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

        return view('main.modules.human-resources.settings', [
            'user' => $user,
            'company' => $company->load('subscription'),
            'site' => $site->load('responsible'),
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'settings',
            'settings' => $settings,
            'managedUsers' => $managedUsers,
            'menuSelections' => $menuSelections,
            'menuGroups' => $this->humanResourcesSettingsMenuGroups(),
        ]);
    }

    public function updateSettings(Request $request, Company $company, CompanySite $site): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;

        abort_unless($user->isAdmin() || $user->isSuperadmin(), Response::HTTP_FORBIDDEN);

        $menuKeys = HumanResourcesModuleNavigation::keys();
        $managedUserIds = $site->users()
            ->where('users.role', User::ROLE_USER)
            ->where(fn ($query) => $query
                ->whereNull('company_site_user.module_permissions')
                ->orWhere('company_site_user.module_permissions', '')
                ->orWhere('company_site_user.module_permissions', 'like', '%"'.CompanySite::MODULE_HUMAN_RESOURCES.'"%'))
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
            ->route('main.human-resources.settings', [$company, $site])
            ->with('success', __('main.hr_settings_saved'));
    }

    public function resourcePage(Request $request, Company $company, CompanySite $site, string $hrResource): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        $meta = $this->humanResourcesResourceMeta($hrResource);

        if ($meta === null) {
            return redirect()->route('main.human-resources.dashboard', [$company, $site]);
        }

        $records = HumanResourceProfileRecord::query()
            ->with('employee.department')
            ->where('company_site_id', $site->id)
            ->where('record_type', $hrResource)
            ->latest('date_from')
            ->latest('id')
            ->paginate(self::TABLE_ROWS_PER_PAGE)
            ->withQueryString();
        $employeeOptions = $site->humanResourceEmployees()
            ->whereNull('user_id')
            ->where('status', HumanResourceEmployee::STATUS_ACTIVE)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'employee_number', 'first_name', 'last_name']);
        $hasResourceErrors = session('errors')?->getBag('default')?->any() ?? false;
        $isEditingResource = old('form_mode') === 'edit' && old('record_id');
        $resourceFormAction = $isEditingResource
            ? route('main.human-resources.resources.update', [$company, $site, $hrResource, old('record_id')])
            : route('main.human-resources.resources.store', [$company, $site, $hrResource]);

        return view('main.modules.human-resources.resource', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => $hrResource,
            'resourceKey' => $hrResource,
            'resourceMeta' => $meta,
            'records' => $records,
            'employeeOptions' => $employeeOptions,
            'resourceFormAction' => $resourceFormAction,
            'isEditingResource' => $isEditingResource,
            'hasResourceErrors' => $hasResourceErrors,
            'currencyOptions' => $this->hrCurrencyOptions($site),
        ]);
    }

    public function storeResource(Request $request, Company $company, CompanySite $site, string $hrResource): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user] = $access;
        $validated = $this->validateResourceRecord($request, $site, $hrResource);
        $validated['company_site_id'] = $site->id;
        $validated['record_type'] = $hrResource;
        $validated['created_by'] = $user->id;
        $validated['reference'] = blank($validated['reference'] ?? null) ? $this->nextResourceReference($hrResource) : $validated['reference'];

        HumanResourceProfileRecord::query()->create($validated);

        return redirect()
            ->route('main.human-resources.resources', [$company, $site, $hrResource])
            ->with('success', __('main.hr_resource_created'));
    }

    public function updateResource(Request $request, Company $company, CompanySite $site, string $hrResource, HumanResourceProfileRecord $record): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->resourceBelongsToSite($record, $site, $hrResource)) {
            return redirect()->route('main.human-resources.resources', [$company, $site, $hrResource]);
        }

        $validated = $this->validateResourceRecord($request, $site, $hrResource, $record);
        $validated['reference'] = blank($validated['reference'] ?? null) ? $record->reference : $validated['reference'];
        $record->update($validated);

        return redirect()
            ->route('main.human-resources.resources', [$company, $site, $hrResource])
            ->with('success', __('main.hr_resource_updated'));
    }

    public function destroyResource(Company $company, CompanySite $site, string $hrResource, HumanResourceProfileRecord $record): RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        if (! $this->resourceBelongsToSite($record, $site, $hrResource)) {
            return redirect()->route('main.human-resources.resources', [$company, $site, $hrResource]);
        }

        $record->delete();

        return redirect()
            ->route('main.human-resources.resources', [$company, $site, $hrResource])
            ->with('success', __('main.hr_resource_deleted'))
            ->with('toast_type', 'danger');
    }

    private function validateResourceRecord(Request $request, CompanySite $site, string $hrResource, ?HumanResourceProfileRecord $record = null): array
    {
        abort_unless($this->humanResourcesResourceMeta($hrResource) !== null, Response::HTTP_NOT_FOUND);

        return $request->validate([
            'human_resource_employee_id' => [
                'nullable',
                Rule::exists('human_resource_employees', 'id')
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id'),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('human_resource_profile_records', 'reference')->ignore($record?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'string', 'max:40'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(array_keys($this->hrCurrencyOptions($site)))],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function nextResourceReference(string $hrResource): string
    {
        $prefix = str($hrResource)->ascii()->upper()->replace('-', '')->substr(0, 4)->toString();
        $nextId = ((int) (HumanResourceProfileRecord::query()->max('id') ?? 0)) + 1;

        do {
            $reference = $prefix.'-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while (HumanResourceProfileRecord::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function resourceBelongsToSite(HumanResourceProfileRecord $record, CompanySite $site, string $hrResource): bool
    {
        return (int) $record->company_site_id === (int) $site->id && $record->record_type === $hrResource;
    }

    private function humanResourcesResourceMeta(string $hrResource): ?array
    {
        return [
            HumanResourceProfileRecord::TYPE_DOCUMENT => [
                'title' => __('main.hr_documents'),
                'subtitle' => __('main.hr_documents_subtitle'),
                'new' => __('main.hr_new_document'),
                'icon' => 'bi-folder2-open',
                'show_employee' => true,
                'show_amount' => false,
                'show_score' => false,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.expiration_date'),
            ],
            HumanResourceProfileRecord::TYPE_SALARY_ADVANCE => [
                'title' => __('main.hr_salary_advances'),
                'subtitle' => __('main.hr_salary_advances_subtitle'),
                'new' => __('main.hr_new_salary_advance'),
                'icon' => 'bi-wallet2',
                'show_employee' => true,
                'show_amount' => true,
                'show_score' => false,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.repayment_date'),
            ],
            HumanResourceProfileRecord::TYPE_PAYROLL_ADJUSTMENT => [
                'title' => __('main.hr_payroll_adjustments'),
                'subtitle' => __('main.hr_payroll_adjustments_subtitle'),
                'new' => __('main.hr_new_payroll_adjustment'),
                'icon' => 'bi-plus-slash-minus',
                'show_employee' => true,
                'show_amount' => true,
                'show_score' => false,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.period'),
            ],
            HumanResourceProfileRecord::TYPE_SCHEDULE => [
                'title' => __('main.hr_schedules'),
                'subtitle' => __('main.hr_schedules_subtitle'),
                'new' => __('main.hr_new_schedule'),
                'icon' => 'bi-clock-history',
                'show_employee' => true,
                'show_amount' => false,
                'show_score' => false,
                'date_from_label' => __('main.start_date'),
                'date_to_label' => __('main.end_date'),
            ],
            HumanResourceProfileRecord::TYPE_EVALUATION => [
                'title' => __('main.hr_evaluations'),
                'subtitle' => __('main.hr_evaluations_subtitle'),
                'new' => __('main.hr_new_evaluation'),
                'icon' => 'bi-clipboard-data',
                'show_employee' => true,
                'show_amount' => false,
                'show_score' => true,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.next_review_date'),
            ],
            HumanResourceProfileRecord::TYPE_TRAINING => [
                'title' => __('main.hr_trainings'),
                'subtitle' => __('main.hr_trainings_subtitle'),
                'new' => __('main.hr_new_training'),
                'icon' => 'bi-mortarboard',
                'show_employee' => true,
                'show_amount' => true,
                'show_score' => false,
                'date_from_label' => __('main.start_date'),
                'date_to_label' => __('main.end_date'),
            ],
            HumanResourceProfileRecord::TYPE_SANCTION => [
                'title' => __('main.hr_sanctions'),
                'subtitle' => __('main.hr_sanctions_subtitle'),
                'new' => __('main.hr_new_sanction'),
                'icon' => 'bi-exclamation-triangle',
                'show_employee' => true,
                'show_amount' => false,
                'show_score' => false,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.end_date'),
            ],
            HumanResourceProfileRecord::TYPE_RECRUITMENT => [
                'title' => __('main.hr_recruitment'),
                'subtitle' => __('main.hr_recruitment_subtitle'),
                'new' => __('main.hr_new_recruitment'),
                'icon' => 'bi-person-plus',
                'show_employee' => false,
                'show_amount' => true,
                'show_score' => true,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.end_date'),
            ],
            HumanResourceProfileRecord::TYPE_SETTING => [
                'title' => __('main.hr_settings'),
                'subtitle' => __('main.hr_settings_subtitle'),
                'new' => __('main.hr_new_setting'),
                'icon' => 'bi-sliders',
                'show_employee' => false,
                'show_amount' => false,
                'show_score' => false,
                'date_from_label' => __('main.date'),
                'date_to_label' => __('main.end_date'),
            ],
        ][$hrResource] ?? null;
    }

    public function notifications(Request $request, Company $company, CompanySite $site): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;
        AccountingActivityFeed::syncSite($site, CompanySite::MODULE_HUMAN_RESOURCES);

        $status = $request->query('status', 'all');
        $notifications = AccountingNotification::query()
            ->with(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)])
            ->where('company_site_id', $site->id)
            ->whereIn('module_key', AccountingActivityFeed::moduleKeys(CompanySite::MODULE_HUMAN_RESOURCES))
            ->when($status === 'unread', fn ($query) => $query->whereDoesntHave('reads', fn ($readQuery) => $readQuery->where('user_id', $user->id)->whereNotNull('read_at')))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('main.modules.human-resources.notifications', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'notifications',
            'notifications' => $notifications,
            'status' => $status,
        ]);
    }

    public function showNotification(Company $company, CompanySite $site, AccountingNotification $notification): View|RedirectResponse
    {
        $access = $this->humanResourcesAccess($company, $site);

        if ($access instanceof RedirectResponse) {
            return $access;
        }

        [$user, $moduleMeta] = $access;

        abort_unless((int) $notification->company_site_id === (int) $site->id, Response::HTTP_NOT_FOUND);
        abort_unless(in_array($notification->module_key, AccountingActivityFeed::moduleKeys(CompanySite::MODULE_HUMAN_RESOURCES), true), Response::HTTP_NOT_FOUND);

        $notification->load(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]);
        $notification->markReadBy($user);

        return view('main.modules.human-resources.notification-show', [
            'user' => $user,
            'company' => $company,
            'site' => $site,
            'module' => CompanySite::MODULE_HUMAN_RESOURCES,
            'moduleMeta' => $moduleMeta,
            'activeHumanResourcesPage' => 'notifications',
            'notification' => $notification->fresh(['actor:id,name,email', 'reads' => fn ($query) => $query->where('user_id', $user->id)]),
            'moduleLabel' => $this->humanResourcesModuleLabel($notification->module_key),
            'moduleUrl' => $this->humanResourcesModuleUrl($notification->module_key, $company, $site),
        ]);
    }

    private function humanResourcesModuleUrl(?string $moduleKey, Company $company, CompanySite $site): ?string
    {
        return match ($moduleKey) {
            'hr-employees' => route('main.human-resources.employees', [$company, $site]),
            'hr-contracts' => route('main.human-resources.contracts', [$company, $site]),
            'hr-leave' => route('main.human-resources.leave', [$company, $site]),
            'hr-payroll' => route('main.human-resources.payroll', [$company, $site]),
            default => null,
        };
    }

    private function humanResourcesModuleLabel(?string $moduleKey): string
    {
        return match ($moduleKey) {
            'hr-employees' => __('main.hr_employees'),
            'hr-contracts' => __('main.hr_contracts'),
            'hr-leave' => __('main.hr_leave'),
            'hr-payroll' => __('main.hr_payroll'),
            default => $moduleKey ? str($moduleKey)->replace('-', ' ')->headline()->toString() : '-',
        };
    }

    private function humanResourcesSettingsMenuGroups(): array
    {
        $labels = [
            'hr-dashboard' => __('main.dashboard'),
            'hr-employees' => __('main.hr_employees'),
            'hr-departments' => __('main.hr_departments'),
            'hr-documents' => __('main.hr_documents'),
            'hr-attendance' => __('main.hr_attendance'),
            'hr-contracts' => __('main.hr_contracts'),
            'hr-leave' => __('main.hr_leave'),
            'hr-payroll' => __('main.hr_payroll'),
            'hr-salary-advances' => __('main.hr_salary_advances'),
            'hr-payroll-adjustments' => __('main.hr_payroll_adjustments'),
            'hr-schedules' => __('main.hr_schedules'),
            'hr-evaluations' => __('main.hr_evaluations'),
            'hr-trainings' => __('main.hr_trainings'),
            'hr-sanctions' => __('main.hr_sanctions'),
            'hr-recruitment' => __('main.hr_recruitment'),
            'hr-reports' => __('main.hr_reports'),
        ];
        $groupLabels = [
            'people' => __('main.hr_people'),
            'administration' => __('main.hr_administration'),
            'development' => __('main.hr_development'),
            'governance' => __('main.hr_governance'),
        ];

        return collect(HumanResourcesModuleNavigation::GROUPS)
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

    private function dashboardData(CompanySite $site): array
    {
        $employees = $site->humanResourceEmployees()
            ->with(['department', 'activeContract'])
            ->whereNull('user_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $employeeIds = $employees->pluck('id');
        $departments = $site->humanResourceDepartments()
            ->withCount(['employees' => fn ($query) => $query->whereNull('user_id')])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $contracts = HumanResourceContract::query()
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->get();
        $leaveRequests = HumanResourceLeaveRequest::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->latest()
            ->limit(5)
            ->get();
        $todayAttendances = HumanResourceAttendance::query()
            ->with('employee')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereDate('work_date', today())
            ->get();
        $payrollEntries = HumanResourcePayrollEntry::query()
            ->with('employee.department')
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('period_month', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->latest('period_month')
            ->latest('id')
            ->limit(5)
            ->get();
        $allMonthlyPayrollEntries = HumanResourcePayrollEntry::query()
            ->whereIn('human_resource_employee_id', $employeeIds)
            ->whereBetween('period_month', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->get();
        $profileRecords = HumanResourceProfileRecord::query()
            ->where('company_site_id', $site->id)
            ->get();
        $latestResourceRecords = HumanResourceProfileRecord::query()
            ->with('employee.department')
            ->where('company_site_id', $site->id)
            ->latest()
            ->limit(5)
            ->get();

        $activeEmployees = $employees->where('status', HumanResourceEmployee::STATUS_ACTIVE);
        $activeContracts = $contracts->where('status', HumanResourceContract::STATUS_ACTIVE);
        $pendingLeaves = $leaveRequests->where('status', HumanResourceLeaveRequest::STATUS_PENDING);
        $validatedPayroll = $allMonthlyPayrollEntries->whereIn('status', [
            HumanResourcePayrollEntry::STATUS_VALIDATED,
            HumanResourcePayrollEntry::STATUS_PAID,
        ]);
        $presentToday = $todayAttendances
            ->whereIn('status', [
                HumanResourceAttendance::STATUS_PRESENT,
                HumanResourceAttendance::STATUS_LATE,
                HumanResourceAttendance::STATUS_REMOTE,
            ])
            ->count();
        $payrollTotalByCurrency = $validatedPayroll->isNotEmpty()
            ? $validatedPayroll->groupBy('currency')->map(fn ($entries) => $entries->sum('net_salary'))
            : $activeContracts->groupBy('currency')->map(fn ($entries) => $entries->sum('monthly_salary'));
        $payrollMeta = $validatedPayroll->isNotEmpty()
            ? __('main.hr_payroll_entries_validated', ['count' => $validatedPayroll->count()])
            : __('main.hr_active_contracts', ['count' => $activeContracts->count()]);

        $profileCompletion = $employees->isEmpty()
            ? 0
            : (int) round($employees->avg(function (HumanResourceEmployee $employee): float {
                $filledFields = collect([
                    $employee->first_name,
                    $employee->last_name,
                    $employee->professional_email ?: $employee->personal_email,
                    $employee->phone,
                    $employee->job_title,
                    $employee->hire_date,
                    $employee->human_resource_department_id,
                ])->filter(fn ($value) => filled($value))->count();

                return ($filledFields / 7) * 100;
            }));

        return [
            'kpis' => [
                [
                    'label' => __('main.hr_active_employees'),
                    'value' => $activeEmployees->count(),
                    'icon' => 'bi-people',
                    'tone' => 'blue',
                    'meta' => __('main.hr_total_employees', ['count' => $employees->count()]),
                ],
                [
                    'label' => __('main.hr_today_presence'),
                    'value' => $presentToday.'/'.$activeEmployees->count(),
                    'icon' => 'bi-calendar-week',
                    'tone' => 'green',
                    'meta' => __('main.hr_absent_today', ['count' => $todayAttendances->where('status', HumanResourceAttendance::STATUS_ABSENT)->count()]),
                ],
                [
                    'label' => __('main.hr_pending_leaves'),
                    'value' => $pendingLeaves->count(),
                    'icon' => 'bi-calendar-check',
                    'tone' => 'violet',
                    'meta' => __('main.hr_leave_requests_waiting'),
                ],
                [
                    'label' => __('main.hr_monthly_payroll'),
                    'value' => $this->formatCurrencyTotals($payrollTotalByCurrency),
                    'icon' => 'bi-cash-stack',
                    'tone' => 'amber',
                    'meta' => $payrollMeta,
                ],
            ],
            'employees' => $employees,
            'departments' => $departments,
            'contracts' => $contracts,
            'leaveRequests' => $leaveRequests,
            'todayAttendances' => $todayAttendances,
            'payrollEntries' => $payrollEntries,
            'profileRecords' => $profileRecords,
            'latestResourceRecords' => $latestResourceRecords,
            'presentToday' => $presentToday,
            'profileCompletion' => $profileCompletion,
            'responsible' => $site->responsible,
            'foundation' => [
                [
                    'label' => __('main.hr_documents'),
                    'status' => __('main.hr_records_count', ['count' => $profileRecords->where('record_type', HumanResourceProfileRecord::TYPE_DOCUMENT)->count()]),
                    'icon' => 'bi-person-lines-fill',
                ],
                [
                    'label' => __('main.hr_salary_advances'),
                    'status' => __('main.hr_records_count', ['count' => $profileRecords->where('record_type', HumanResourceProfileRecord::TYPE_SALARY_ADVANCE)->count()]),
                    'icon' => 'bi-wallet2',
                ],
                [
                    'label' => __('main.hr_trainings'),
                    'status' => __('main.hr_records_count', ['count' => $profileRecords->where('record_type', HumanResourceProfileRecord::TYPE_TRAINING)->count()]),
                    'icon' => 'bi-mortarboard',
                ],
            ],
        ];
    }

    private function humanResourcesModuleMeta(): array
    {
        return [
            'label' => __('main.module_human_resources'),
            'description' => __('main.module_human_resources_description'),
            'icon' => 'bi-people',
            'tone' => 'violet',
            'class' => 'module-human-resources',
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

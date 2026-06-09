<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\HumanResourceAttendance;
use App\Models\HumanResourceContract;
use App\Models\HumanResourceDepartment;
use App\Models\HumanResourceEmployee;
use App\Models\HumanResourceLeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class HumanResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sites = CompanySite::query()
            ->whereJsonContains('modules', CompanySite::MODULE_HUMAN_RESOURCES)
            ->with('company')
            ->get();

        if ($sites->isEmpty()) {
            $site = $this->ensureDemoHumanResourcesSite();

            if ($site) {
                $sites = collect([$site->load('company')]);
            }
        }

        $sites->each(fn (CompanySite $site) => $this->seedSite($site));
    }

    private function ensureDemoHumanResourcesSite(): ?CompanySite
    {
        $company = Company::query()->with('subscription')->first();

        if (! $company) {
            return null;
        }

        $admin = User::query()
            ->where('subscription_id', $company->subscription_id)
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->first();

        $site = CompanySite::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'KIN-RH',
            ],
            [
                'responsible_id' => $admin?->id,
                'name' => 'EXAD Kinshasa',
                'type' => CompanySite::TYPE_OFFICE,
                'city' => 'Kinshasa',
                'phone' => null,
                'email' => $company->email,
                'address' => $company->address,
                'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES],
                'currency' => 'USD',
                'status' => CompanySite::STATUS_ACTIVE,
            ],
        );

        $modules = collect($site->modules ?? [])
            ->merge([CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES])
            ->unique()
            ->values()
            ->all();

        $site->forceFill(['modules' => $modules])->save();

        $users = User::query()
            ->where('subscription_id', $company->subscription_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
            ->get();

        foreach ($users as $user) {
            $site->users()->syncWithoutDetaching([
                $user->id => [
                    'module_permissions' => json_encode([
                        CompanySite::MODULE_ACCOUNTING => true,
                        CompanySite::MODULE_HUMAN_RESOURCES => true,
                    ]),
                    'can_create' => $user->isAdmin(),
                    'can_update' => $user->isAdmin(),
                    'can_delete' => $user->isAdmin(),
                ],
            ]);
        }

        return $site;
    }

    private function seedSite(CompanySite $site): void
    {
        $users = $site->users()->orderBy('users.name')->get();
        $admin = $users->first(fn (User $user) => $user->isAdmin())
            ?? User::query()->where('subscription_id', $site->company?->subscription_id)->where('role', User::ROLE_ADMIN)->first();

        $departments = collect([
            ['code' => 'ADM', 'name' => 'Administration', 'description' => 'Coordination administrative et support interne.'],
            ['code' => 'FIN', 'name' => 'Finance', 'description' => 'Comptabilité, facturation et contrôle financier.'],
            ['code' => 'OPS', 'name' => 'Opérations', 'description' => 'Suivi opérationnel et exécution terrain.'],
        ])->mapWithKeys(function (array $department) use ($site, $admin) {
            $model = HumanResourceDepartment::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'code' => $department['code']],
                [
                    'manager_user_id' => null,
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'status' => HumanResourceDepartment::STATUS_ACTIVE,
                ],
            );

            return [$department['code'] => $model];
        });

        $employees = [];
        foreach ($users as $index => $user) {
            $nameParts = preg_split('/\s+/', trim($user->name), 2);
            $firstName = $nameParts[0] ?: 'Collaborateur';
            $lastName = $nameParts[1] ?? strtoupper($user->role);
            $department = $departments->values()->get($index % max(1, $departments->count()));

            $employees[] = HumanResourceEmployee::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'user_id' => $user->id],
                [
                    'human_resource_department_id' => $department?->id,
                    'created_by' => $admin?->id,
                    'employee_number' => HumanResourceEmployee::employeeNumberFromId(($site->id * 1000) + $user->id),
                    'first_name' => ucfirst($firstName),
                    'last_name' => ucfirst($lastName),
                    'gender' => null,
                    'professional_email' => $user->email,
                    'personal_email' => $user->email,
                    'phone' => $user->phone_number,
                    'job_title' => $user->isAdmin() ? 'Responsable administratif' : 'Collaborateur',
                    'employment_type' => HumanResourceEmployee::EMPLOYMENT_FULL_TIME,
                    'hire_date' => now()->subMonths(6 + $index)->toDateString(),
                    'status' => HumanResourceEmployee::STATUS_ACTIVE,
                    'emergency_contact_name' => 'Contact '.$user->name,
                    'emergency_contact_phone' => $user->phone_number,
                ],
            );
        }

        $employees = collect($employees)
            ->filter(fn (HumanResourceEmployee $employee): bool => blank($employee->user_id))
            ->values()
            ->all();

        if ($employees === []) {
            $department = $departments->get('ADM');
            $employees[] = HumanResourceEmployee::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'employee_number' => HumanResourceEmployee::employeeNumberFromId($site->id * 1000)],
                [
                    'human_resource_department_id' => $department?->id,
                    'created_by' => $admin?->id,
                    'first_name' => 'Employé',
                    'last_name' => 'Démo',
                    'professional_email' => 'rh-'.$site->id.'@erp.loc',
                    'job_title' => 'Assistant RH',
                    'employment_type' => HumanResourceEmployee::EMPLOYMENT_FULL_TIME,
                    'hire_date' => now()->subMonths(4)->toDateString(),
                    'status' => HumanResourceEmployee::STATUS_ACTIVE,
                ],
            );
        }

        $departments->each(function (HumanResourceDepartment $department) use ($employees): void {
            $manager = collect($employees)
                ->first(fn (HumanResourceEmployee $employee): bool => $employee->human_resource_department_id === $department->id)
                ?? collect($employees)->first();

            $department->forceFill([
                'manager_employee_id' => $manager?->id,
            ])->save();
        });

        foreach ($employees as $index => $employee) {
            HumanResourceContract::query()->updateOrCreate(
                ['human_resource_employee_id' => $employee->id, 'reference' => HumanResourceContract::referenceFromId(($site->id * 1000) + $employee->id)],
                [
                    'created_by' => $admin?->id,
                    'type' => HumanResourceContract::TYPE_PERMANENT,
                    'status' => HumanResourceContract::STATUS_ACTIVE,
                    'start_date' => optional($employee->hire_date)->toDateString() ?? now()->subMonths(6)->toDateString(),
                    'end_date' => null,
                    'probation_end_date' => now()->subMonths(3)->toDateString(),
                    'currency' => $site->currency ?: 'USD',
                    'monthly_salary' => 850 + ($index * 125),
                    'notes' => 'Contrat de démonstration RH.',
                ],
            );

            HumanResourceAttendance::query()->updateOrCreate(
                ['human_resource_employee_id' => $employee->id, 'work_date' => now()->toDateString()],
                [
                    'check_in_at' => $index % 3 === 0 ? '08:18:00' : '08:00:00',
                    'check_out_at' => null,
                    'worked_hours' => 0,
                    'status' => $index % 3 === 0 ? HumanResourceAttendance::STATUS_LATE : HumanResourceAttendance::STATUS_PRESENT,
                    'notes' => null,
                ],
            );
        }

        $leaveEmployee = $employees[0] ?? null;

        if ($leaveEmployee) {
            HumanResourceLeaveRequest::query()->updateOrCreate(
                ['human_resource_employee_id' => $leaveEmployee->id, 'reference' => HumanResourceLeaveRequest::referenceFromId(($site->id * 1000) + $leaveEmployee->id)],
                [
                    'approved_by' => null,
                    'type' => HumanResourceLeaveRequest::TYPE_ANNUAL,
                    'status' => HumanResourceLeaveRequest::STATUS_PENDING,
                    'start_date' => now()->addWeek()->toDateString(),
                    'end_date' => now()->addWeek()->addDays(4)->toDateString(),
                    'days_count' => 5,
                    'reason' => 'Congé annuel planifié.',
                    'approved_at' => null,
                ],
            );
        }
    }
}

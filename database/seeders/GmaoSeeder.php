<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\GmaoActivity;
use App\Models\GmaoEquipment;
use App\Models\GmaoEquipmentCategory;
use App\Models\GmaoInterventionReport;
use App\Models\GmaoLocation;
use App\Models\GmaoMaintenanceRoute;
use App\Models\GmaoMaintenanceTask;
use App\Models\GmaoPreventivePlan;
use App\Models\GmaoSparePart;
use App\Models\GmaoTechnician;
use App\Models\GmaoWorkOrder;
use App\Models\GmaoWorkOrderPart;
use App\Models\GmaoWorkRequest;
use App\Models\HumanResourceEmployee;
use App\Models\User;
use Illuminate\Database\Seeder;

class GmaoSeeder extends Seeder
{
    public function run(): void
    {
        $sites = CompanySite::query()
            ->whereJsonContains('modules', CompanySite::MODULE_GMAO)
            ->with('company')
            ->get();

        if ($sites->isEmpty()) {
            $site = $this->ensureDemoSite();

            if ($site) {
                $sites = collect([$site->load('company')]);
            }
        }

        $sites->each(fn (CompanySite $site) => $this->seedSite($site));
    }

    private function ensureDemoSite(): ?CompanySite
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
            ['company_id' => $company->id, 'code' => 'KIN-RH'],
            [
                'responsible_id' => $admin?->id,
                'name' => 'EXAD Kinshasa',
                'type' => CompanySite::TYPE_OFFICE,
                'city' => 'Kinshasa',
                'phone' => null,
                'email' => $company->email,
                'address' => $company->address,
                'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES, CompanySite::MODULE_GMAO],
                'currency' => 'USD',
                'status' => CompanySite::STATUS_ACTIVE,
            ],
        );

        $site->forceFill([
            'modules' => collect($site->modules ?? [])
                ->merge([CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES, CompanySite::MODULE_DOCUMENT_MANAGEMENT, CompanySite::MODULE_ARCHIVING, CompanySite::MODULE_GMAO])
                ->unique()
                ->values()
                ->all(),
        ])->save();

        $users = User::query()
            ->where('subscription_id', $company->subscription_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
            ->get();

        foreach ($users as $user) {
            $permissions = [
                CompanySite::MODULE_ACCOUNTING => true,
                CompanySite::MODULE_HUMAN_RESOURCES => true,
                CompanySite::MODULE_DOCUMENT_MANAGEMENT => true,
                CompanySite::MODULE_ARCHIVING => true,
                CompanySite::MODULE_GMAO => true,
            ];

            $site->users()->syncWithoutDetaching([
                $user->id => [
                    'module_permissions' => json_encode($permissions),
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
        $admin = User::query()
            ->where('subscription_id', $site->company?->subscription_id)
            ->where('role', User::ROLE_ADMIN)
            ->first();

        $room = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-001'],
            ['name' => 'Salle technique RDC', 'type' => 'room', 'parent_id' => null, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Local technique principal avec énergie, réseau et climatisation.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $energyZone = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-003'],
            ['name' => 'Zone énergie', 'type' => 'zone', 'parent_id' => $room->id, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Onduleurs, batteries et groupe de secours.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $hvacZone = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-004'],
            ['name' => 'Zone climatisation', 'type' => 'zone', 'parent_id' => $room->id, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Unités de refroidissement de précision.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $networkZone = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-005'],
            ['name' => 'Zone réseau', 'type' => 'zone', 'parent_id' => $room->id, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Distribution, transmission et commutation réseau.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $networkRack = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-002'],
            ['name' => 'Rack réseau principal', 'type' => 'rack', 'parent_id' => $networkZone->id, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Baie réseau et équipements de commutation.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $networkShelf = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-006'],
            ['name' => 'Étagère réseau U10-U20', 'type' => 'shelf', 'parent_id' => $networkRack->id, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Niveau de baie réservé aux équipements actifs.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $networkPosition = GmaoLocation::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'GML-007'],
            ['name' => 'Position U12', 'type' => 'position', 'parent_id' => $networkShelf->id, 'building' => 'Bloc A', 'floor' => 'RDC', 'description' => 'Position précise dans la baie réseau principale.', 'status' => GmaoLocation::STATUS_ACTIVE],
        );

        $locations = collect([
            'GML-001' => $room,
            'GML-002' => $networkRack,
            'GML-003' => $energyZone,
            'GML-004' => $hvacZone,
            'GML-005' => $networkZone,
            'GML-006' => $networkShelf,
            'GML-007' => $networkPosition,
        ]);

        $categories = collect([
            ['reference' => 'CAT-001', 'code' => 'ENERGIE', 'name' => 'Energie', 'family' => 'Infrastructure critique', 'default_criticality' => 'critical', 'description' => 'Onduleurs, batteries, groupes electrogenes et alimentation secourue.'],
            ['reference' => 'CAT-002', 'code' => 'RESEAU', 'name' => 'Reseau et transmission', 'family' => 'Telecom', 'default_criticality' => 'critical', 'description' => 'Switchs, routeurs, coeurs reseau, liens de transmission et equipements IP.'],
            ['reference' => 'CAT-003', 'code' => 'CVC', 'name' => 'Climatisation et froid', 'family' => 'Infrastructure critique', 'default_criticality' => 'high', 'description' => 'Climatiseurs de precision, ventilation et refroidissement des salles techniques.'],
            ['reference' => 'CAT-004', 'code' => 'SECURITE', 'name' => 'Securite et controle d acces', 'family' => 'Surete', 'default_criticality' => 'high', 'description' => 'Controle d acces, videosurveillance, detection intrusion et incendie.'],
            ['reference' => 'CAT-005', 'code' => 'BANKING', 'name' => 'Automates bancaires et terminaux', 'family' => 'Banque', 'default_criticality' => 'critical', 'description' => 'DAB, TPE, kiosques, terminaux et equipements bancaires de front-office.'],
        ])->mapWithKeys(fn (array $row) => [
            $row['reference'] => GmaoEquipmentCategory::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'name' => $row['name']],
                $row + ['status' => GmaoEquipmentCategory::STATUS_ACTIVE],
            ),
        ]);

        $equipment = collect([
            ['reference' => 'EQP-001', 'asset_code' => 'VDC-KIN-UPS-001', 'name' => 'Onduleur salle technique', 'category' => $categories['CAT-001']->name, 'gmao_equipment_category_id' => $categories['CAT-001']->id, 'criticality' => 'critical', 'brand' => 'APC', 'model' => 'Smart-UPS SRT', 'serial_number' => 'UPS-KIN-2024-001', 'supplier' => 'Schneider Electric', 'acquisition_cost' => 8200, 'expense_type' => 'capex', 'cost_center' => 'KIN-NOC', 'meter_unit' => 'heures', 'current_meter' => 2860, 'last_meter_read_at' => now()->subDays(2), 'expected_lifetime_months' => 84, 'gmao_location_id' => $locations['GML-003']->id, 'status' => GmaoEquipment::STATUS_DEGRADED],
            ['reference' => 'EQP-002', 'asset_code' => 'VDC-KIN-GEN-001', 'name' => 'Groupe électrogène secours', 'category' => $categories['CAT-001']->name, 'gmao_equipment_category_id' => $categories['CAT-001']->id, 'criticality' => 'high', 'brand' => 'FG Wilson', 'model' => 'P88', 'serial_number' => 'GEN-FGW-088-KIN', 'supplier' => 'Congo Energy Services', 'acquisition_cost' => 38000, 'expense_type' => 'capex', 'cost_center' => 'KIN-FACILITY', 'meter_unit' => 'heures', 'current_meter' => 640, 'last_meter_read_at' => now()->subDay(), 'expected_lifetime_months' => 120, 'gmao_location_id' => $locations['GML-003']->id, 'status' => GmaoEquipment::STATUS_OPERATIONAL],
            ['reference' => 'EQP-003', 'asset_code' => 'VDC-KIN-NET-CORE-001', 'name' => 'Switch coeur réseau', 'category' => $categories['CAT-002']->name, 'gmao_equipment_category_id' => $categories['CAT-002']->id, 'criticality' => 'critical', 'brand' => 'Cisco', 'model' => 'Catalyst', 'serial_number' => 'CSC-CORE-001', 'supplier' => 'Cisco Partner RDC', 'acquisition_cost' => 14500, 'expense_type' => 'capex', 'cost_center' => 'KIN-IT', 'meter_unit' => 'heures', 'current_meter' => 11800, 'last_meter_read_at' => now()->subDays(3), 'expected_lifetime_months' => 72, 'gmao_location_id' => $locations['GML-007']->id, 'status' => GmaoEquipment::STATUS_OPERATIONAL],
            ['reference' => 'EQP-004', 'asset_code' => 'VDC-KIN-CVC-001', 'name' => 'Climatiseur précision', 'category' => $categories['CAT-003']->name, 'gmao_equipment_category_id' => $categories['CAT-003']->id, 'criticality' => 'high', 'brand' => 'Liebert', 'model' => 'PXR', 'serial_number' => 'LBT-PXR-4421', 'supplier' => 'Vertiv Service', 'acquisition_cost' => 11200, 'expense_type' => 'capex', 'cost_center' => 'KIN-FACILITY', 'meter_unit' => 'heures', 'current_meter' => 5100, 'last_meter_read_at' => now()->subDays(4), 'expected_lifetime_months' => 96, 'gmao_location_id' => $locations['GML-004']->id, 'status' => GmaoEquipment::STATUS_MAINTENANCE],
        ])->mapWithKeys(fn (array $row) => [
            $row['reference'] => GmaoEquipment::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                $row + [
                    'commissioned_at' => now()->subYears(2)->toDateString(),
                    'warranty_until' => now()->addMonths(8)->toDateString(),
                    'notes' => 'Equipement critique suivi par la maintenance.',
                ],
            ),
        ]);

        GmaoEquipmentCategory::query()
            ->where('company_site_id', $site->id)
            ->where('reference', 'like', 'CAT-000%')
            ->whereDoesntHave('equipment')
            ->update(['status' => GmaoEquipmentCategory::STATUS_INACTIVE]);

        $employees = HumanResourceEmployee::query()
            ->where('company_site_id', $site->id)
            ->where('status', HumanResourceEmployee::STATUS_ACTIVE)
            ->take(3)
            ->get();

        $technicians = collect([
            ['reference' => 'TEC-001', 'name' => $employees->get(0)?->full_name ?? 'Technicien énergie', 'specialty' => 'Energie et UPS', 'email' => $employees->get(0)?->professional_email, 'human_resource_employee_id' => $employees->get(0)?->id],
            ['reference' => 'TEC-002', 'name' => $employees->get(1)?->full_name ?? 'Technicien réseau', 'specialty' => 'Réseau et infrastructure', 'email' => $employees->get(1)?->professional_email, 'human_resource_employee_id' => $employees->get(1)?->id],
            ['reference' => 'TEC-003', 'name' => $employees->get(2)?->full_name ?? 'Technicien CVC', 'specialty' => 'Climatisation', 'email' => $employees->get(2)?->professional_email, 'human_resource_employee_id' => $employees->get(2)?->id],
        ])->mapWithKeys(fn (array $row) => [
            $row['reference'] => GmaoTechnician::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                $row + ['status' => GmaoTechnician::STATUS_AVAILABLE],
            ),
        ]);

        $requests = collect([
            ['reference' => 'DM-001', 'title' => 'Autonomie UPS en baisse', 'gmao_equipment_id' => $equipment['EQP-001']->id, 'priority' => 'urgent', 'status' => GmaoWorkRequest::STATUS_CONVERTED, 'description' => 'L’autonomie est passée sous le seuil de sécurité.'],
            ['reference' => 'DM-002', 'title' => 'Vibration anormale climatiseur', 'gmao_equipment_id' => $equipment['EQP-004']->id, 'priority' => 'high', 'status' => GmaoWorkRequest::STATUS_APPROVED, 'description' => 'Bruit intermittent et vibration au démarrage.'],
            ['reference' => 'DM-003', 'title' => 'Contrôle mensuel groupe électrogène', 'gmao_equipment_id' => $equipment['EQP-002']->id, 'priority' => 'normal', 'status' => GmaoWorkRequest::STATUS_NEW, 'description' => 'Demande de vérification de routine avant fin de mois.'],
        ])->mapWithKeys(fn (array $row) => [
            $row['reference'] => GmaoWorkRequest::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                $row + [
                    'created_by' => $admin?->id,
                    'requester_name' => $admin?->name ?? 'Admin',
                    'requested_at' => now()->subDays(3),
                    'due_at' => now()->addDays(4),
                ],
            ),
        ]);

        $orders = collect([
            ['reference' => 'OT-001', 'title' => 'Remplacement batteries UPS', 'gmao_work_request_id' => $requests['DM-001']->id, 'gmao_equipment_id' => $equipment['EQP-001']->id, 'gmao_technician_id' => $technicians['TEC-001']->id, 'type' => 'corrective', 'priority' => 'urgent', 'status' => GmaoWorkOrder::STATUS_IN_PROGRESS, 'workflow_stage' => 'execution', 'estimated_hours' => 4, 'actual_hours' => 1.5, 'failure_started_at' => now()->subHours(6), 'planned_at' => now()->subDay(), 'started_at' => now()->subHours(3), 'completed_at' => null, 'downtime_minutes' => 180, 'labor_cost' => 120, 'external_cost' => 0, 'capex_amount' => 0, 'opex_amount' => 180],
            ['reference' => 'OT-002', 'title' => 'Nettoyage filtres climatisation', 'gmao_work_request_id' => $requests['DM-002']->id, 'gmao_equipment_id' => $equipment['EQP-004']->id, 'gmao_technician_id' => $technicians['TEC-003']->id, 'type' => 'corrective', 'priority' => 'high', 'status' => GmaoWorkOrder::STATUS_PLANNED, 'workflow_stage' => 'planning', 'estimated_hours' => 2, 'actual_hours' => 0, 'failure_started_at' => now()->subHours(10), 'planned_at' => now()->addDay(), 'started_at' => null, 'completed_at' => null, 'downtime_minutes' => 0, 'labor_cost' => 0, 'external_cost' => 0, 'capex_amount' => 0, 'opex_amount' => 45],
            ['reference' => 'OT-003', 'title' => 'Inspection switch coeur réseau', 'gmao_work_request_id' => null, 'gmao_equipment_id' => $equipment['EQP-003']->id, 'gmao_technician_id' => $technicians['TEC-002']->id, 'type' => 'preventive', 'priority' => 'normal', 'status' => GmaoWorkOrder::STATUS_DONE, 'workflow_stage' => 'closed', 'estimated_hours' => 1.5, 'actual_hours' => 1.25, 'failure_started_at' => null, 'planned_at' => now()->subDays(5), 'started_at' => now()->subDays(5)->setTime(9, 0), 'completed_at' => now()->subDays(5)->setTime(10, 15), 'downtime_minutes' => 0, 'labor_cost' => 75, 'external_cost' => 0, 'capex_amount' => 0, 'opex_amount' => 0],
        ])->mapWithKeys(fn (array $row) => [
            $row['reference'] => GmaoWorkOrder::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                $row + [
                    'created_by' => $admin?->id,
                    'description' => 'Intervention générée pour suivi GMAO.',
                ],
            ),
        ]);

        $parts = collect([
            ['reference' => 'PDR-001', 'name' => 'Batterie UPS 12V', 'category' => 'Energie', 'stock_quantity' => 4, 'minimum_quantity' => 6, 'unit_cost' => 85],
            ['reference' => 'PDR-002', 'name' => 'Filtre climatiseur précision', 'category' => 'CVC', 'stock_quantity' => 8, 'minimum_quantity' => 4, 'unit_cost' => 22],
            ['reference' => 'PDR-003', 'name' => 'Disjoncteur modulaire', 'category' => 'Electricité', 'stock_quantity' => 3, 'minimum_quantity' => 3, 'unit_cost' => 18],
            ['reference' => 'PDR-004', 'name' => 'Ventilateur rack 120mm', 'category' => 'Réseau', 'stock_quantity' => 12, 'minimum_quantity' => 5, 'unit_cost' => 12],
        ])->mapWithKeys(fn (array $row) => [
            $row['reference'] => GmaoSparePart::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                $row + ['unit' => 'piece', 'currency' => $site->currency ?: 'USD', 'status' => 'active'],
            ),
        ]);

        GmaoWorkOrderPart::query()->updateOrCreate(
            ['gmao_work_order_id' => $orders['OT-001']->id, 'gmao_spare_part_id' => $parts['PDR-001']->id],
            ['quantity' => 2, 'unit_cost' => $parts['PDR-001']->unit_cost],
        );

        $routes = collect([
            [
                'reference' => 'GAM-001',
                'title' => 'Test autonomie UPS',
                'category' => $categories['CAT-001'],
                'frequency' => 'monthly',
                'estimated_duration_hours' => 2,
                'instructions' => 'Contrôle de charge, autonomie réelle et état des batteries.',
                'tasks' => ['Vérifier les alarmes et journaux UPS', 'Tester l’autonomie en charge contrôlée', 'Consigner tension, charge et température batterie'],
            ],
            [
                'reference' => 'GAM-002',
                'title' => 'Essai groupe électrogène',
                'category' => $categories['CAT-001'],
                'frequency' => 'weekly',
                'estimated_duration_hours' => 1.5,
                'instructions' => 'Essai fonctionnel du groupe, carburant, huile et démarrage.',
                'tasks' => ['Contrôler carburant, huile et liquide de refroidissement', 'Démarrer à vide puis sous charge simulée', 'Relever compteur horaire et anomalies'],
            ],
            [
                'reference' => 'GAM-003',
                'title' => 'Inspection rack réseau',
                'category' => $categories['CAT-002'],
                'frequency' => 'quarterly',
                'estimated_duration_hours' => 1,
                'instructions' => 'Vérification physique du rack, alimentation et ventilation.',
                'tasks' => ['Contrôler alimentation redondante', 'Vérifier brassage et étiquetage', 'Nettoyer les filtres et contrôler la ventilation'],
            ],
        ])->mapWithKeys(function (array $row) use ($site): array {
            $taskLines = $row['tasks'];
            unset($row['tasks']);

            $route = GmaoMaintenanceRoute::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                [
                    'gmao_equipment_category_id' => $row['category']->id,
                    'title' => $row['title'],
                    'frequency' => $row['frequency'],
                    'estimated_duration_hours' => $row['estimated_duration_hours'],
                    'instructions' => $row['instructions'],
                    'status' => 'active',
                ],
            );

            GmaoMaintenanceTask::query()->where('gmao_maintenance_route_id', $route->id)->delete();

            foreach ($taskLines as $index => $task) {
                GmaoMaintenanceTask::query()->create([
                    'gmao_maintenance_route_id' => $route->id,
                    'position' => $index + 1,
                    'title' => $task,
                    'instructions' => $task,
                    'estimated_minutes' => 20,
                ]);
            }

            return [$route->reference => $route];
        });

        foreach ([
            ['reference' => 'PMP-001', 'title' => 'Test autonomie UPS', 'gmao_equipment_id' => $equipment['EQP-001']->id, 'gmao_maintenance_route_id' => $routes['GAM-001']->id, 'frequency' => 'monthly', 'trigger_type' => 'frequency', 'meter_interval' => null, 'alert_days' => 7, 'last_done_at' => now()->subMonth()->toDateString(), 'next_due_at' => now()->addDays(5)->toDateString(), 'instructions' => 'Tester la charge et l’autonomie réelle.'],
            ['reference' => 'PMP-002', 'title' => 'Essai groupe électrogène', 'gmao_equipment_id' => $equipment['EQP-002']->id, 'gmao_maintenance_route_id' => $routes['GAM-002']->id, 'frequency' => 'weekly', 'trigger_type' => 'meter', 'meter_interval' => 50, 'alert_days' => 3, 'last_done_at' => now()->subWeek()->toDateString(), 'next_due_at' => now()->addDays(2)->toDateString(), 'instructions' => 'Démarrage à vide puis contrôle carburant.'],
            ['reference' => 'PMP-003', 'title' => 'Inspection rack réseau', 'gmao_equipment_id' => $equipment['EQP-003']->id, 'gmao_maintenance_route_id' => $routes['GAM-003']->id, 'frequency' => 'quarterly', 'trigger_type' => 'frequency', 'meter_interval' => null, 'alert_days' => 10, 'last_done_at' => now()->subMonths(2)->toDateString(), 'next_due_at' => now()->addMonth()->toDateString(), 'instructions' => 'Vérifier câblage, ventilation et alimentation.'],
        ] as $row) {
            GmaoPreventivePlan::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference']],
                $row + ['status' => 'active'],
            );
        }

        GmaoInterventionReport::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'reference' => 'RINT-001'],
            [
                'gmao_work_order_id' => $orders['OT-003']->id,
                'gmao_technician_id' => $technicians['TEC-002']->id,
                'diagnosis' => 'Aucun incident détecté.',
                'work_done' => 'Inspection visuelle, contrôle alimentation et ventilation.',
                'recommendations' => 'Maintenir le cycle trimestriel.',
                'result' => 'resolved',
                'reported_at' => now()->subDays(5)->setTime(10, 20),
            ],
        );

        foreach ([
            ['reference' => 'EQP-001', 'action' => 'equipment_created', 'title' => 'Equipement suivi en GMAO', 'description' => 'Onduleur salle technique ajoute au parc.'],
            ['reference' => 'DM-001', 'action' => 'request_created', 'title' => 'Demande de maintenance créée', 'description' => 'Autonomie UPS en baisse.'],
            ['reference' => 'OT-001', 'action' => 'work_order_planned', 'title' => 'Ordre de travail planifié', 'description' => 'Remplacement batteries UPS.'],
            ['reference' => 'OT-003', 'action' => 'work_order_completed', 'title' => 'Intervention clôturée', 'description' => 'Inspection switch coeur réseau terminée.'],
            ['reference' => 'GAM-001', 'action' => 'maintenance_route_created', 'title' => 'Gamme préventive créée', 'description' => 'Test autonomie UPS structuré en tâches.'],
            ['reference' => 'PMP-002', 'action' => 'preventive_due', 'title' => 'Préventif proche', 'description' => 'Essai groupe électrogène à réaliser.'],
            ['reference' => 'PDR-001', 'action' => 'stock_alert', 'title' => 'Stock critique', 'description' => 'Batteries UPS sous le stock minimum.'],
        ] as $index => $row) {
            GmaoActivity::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'reference' => $row['reference'], 'action' => $row['action']],
                $row + [
                    'user_id' => $admin?->id,
                    'metadata' => ['seed' => true],
                    'created_at' => now()->subHours(12 - $index),
                    'updated_at' => now()->subHours(12 - $index),
                ],
            );
        }
    }
}


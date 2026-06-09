<?php

namespace Database\Seeders;

use App\Models\ArchiveActivity;
use App\Models\ArchiveBox;
use App\Models\ArchiveCabinet;
use App\Models\ArchiveCompartment;
use App\Models\ArchiveContainer;
use App\Models\ArchiveLocation;
use App\Models\ArchiveRack;
use App\Models\ArchiveRecord;
use App\Models\ArchiveRetentionRule;
use App\Models\ArchiveRoom;
use App\Models\ArchiveShelf;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArchivingSeeder extends Seeder
{
    public function run(): void
    {
        $sites = CompanySite::query()
            ->whereJsonContains('modules', CompanySite::MODULE_ARCHIVING)
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
            ['company_id' => $company->id, 'code' => 'KIN-ARCH'],
            [
                'responsible_id' => $admin?->id,
                'name' => 'EXAD Kinshasa',
                'type' => CompanySite::TYPE_ARCHIVE,
                'city' => 'Kinshasa',
                'email' => $company->email,
                'address' => $company->address,
                'modules' => [CompanySite::MODULE_ARCHIVING],
                'currency' => 'USD',
                'status' => CompanySite::STATUS_ACTIVE,
            ],
        );

        $site->forceFill([
            'modules' => collect($site->modules ?? [])
                ->merge([CompanySite::MODULE_ARCHIVING])
                ->unique()
                ->values()
                ->all(),
        ])->save();

        User::query()
            ->where('subscription_id', $company->subscription_id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
            ->get()
            ->each(function (User $user) use ($site): void {
                $site->users()->syncWithoutDetaching([
                    $user->id => [
                        'module_permissions' => json_encode([CompanySite::MODULE_ARCHIVING => true]),
                        'can_create' => $user->isAdmin(),
                        'can_update' => $user->isAdmin(),
                        'can_delete' => $user->isAdmin(),
                    ],
                ]);
            });

        return $site;
    }

    private function seedSite(CompanySite $site): void
    {
        $admin = $site->users()->where('users.role', User::ROLE_ADMIN)->first()
            ?? User::query()->where('subscription_id', $site->company?->subscription_id)->where('role', User::ROLE_ADMIN)->first();

        $this->normalizeLegacySeedData($site);

        $room = $this->room($site, $admin, 'Salle Archives RDC', 'SAR-RDC', 5000);
        $rayonFinance = $this->rack($site, $admin, $room, 'Rayon Finance', 'FIN', 1800);
        $rayonAdmin = $this->rack($site, $admin, $room, 'Rayon Administration', 'ADM', 1200);

        $cabinetFinance = $this->cabinet($site, $admin, $rayonFinance, 'Armoire Finance A01', 'A01', 600);
        $shelfFinance = $this->shelf($site, $admin, $cabinetFinance, 'Étagère A01-02', 'A01-02', 150);
        $compartmentFinance = $this->compartment($site, $admin, $shelfFinance, 'Casier A01-02-C1', 'A01-02-C1', 80);
        $boxFinance = $this->box($site, $admin, $shelfFinance, $compartmentFinance, 'Boîte FIN-2026-001', 'FIN-2026-001', 50);

        $cabinetAdmin = $this->cabinet($site, $admin, $rayonAdmin, 'Armoire Administrative B01', 'B01', 500);
        $shelfAdmin = $this->shelf($site, $admin, $cabinetAdmin, 'Étagère B01-01', 'B01-01', 120);
        $boxAdmin = $this->box($site, $admin, $shelfAdmin, null, 'Boîte ADM-2026-001', 'ADM-2026-001', 40);

        $financeClasser = $this->container($site, $admin, $boxFinance, 'Classeur factures clients 2026', 'Finance', 'Comptabilité', '2026');
        $contractClasser = $this->container($site, $admin, $boxAdmin, 'Classeur contrats fournisseurs', 'Juridique', 'Administration', '2026');

        $this->record($site, $admin, $boxFinance, $financeClasser, 'Facture client FAC-000145 validée', 'Facture', 'Finance', 'Comptabilité', now()->subDays(20), now()->addYears(10));
        $this->record($site, $admin, $boxFinance, $financeClasser, 'Bordereau de paiement client Orange RDC', 'Paiement', 'Finance', 'Comptabilité', now()->subDays(10), now()->addYears(10));
        $this->record($site, $admin, $boxAdmin, $contractClasser, 'Contrat fournisseur maintenance réseau', 'Contrat', 'Juridique', 'Administration', now()->subMonth(), now()->addYears(5));

        foreach ([
            ['Finance', 10, 'Factures, paiements, pièces comptables et justificatifs financiers.'],
            ['Juridique', 5, 'Contrats, conventions, avenants et correspondances juridiques.'],
            ['Administration', 5, 'Notes, décisions internes et dossiers administratifs.'],
        ] as [$category, $years, $description]) {
            ArchiveRetentionRule::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'category' => $category],
                ['retention_years' => $years, 'status' => ArchiveRetentionRule::STATUS_ACTIVE, 'description' => $description],
            );
        }
    }

    private function normalizeLegacySeedData(CompanySite $site): void
    {
        foreach ([
            'Ãƒâ€°tagÃƒÂ¨re A01-02' => 'Étagère A01-02',
            'Ã‰tagÃ¨re A01-02' => 'Étagère A01-02',
            'BoÃƒÂ®te FIN-2026-001' => 'Boîte FIN-2026-001',
            'BoÃ®te FIN-2026-001' => 'Boîte FIN-2026-001',
            'BoÃƒÂ®te ADM-2026-001' => 'Boîte ADM-2026-001',
            'BoÃ®te ADM-2026-001' => 'Boîte ADM-2026-001',
        ] as $oldName => $newName) {
            ArchiveLocation::query()
                ->where('company_site_id', $site->id)
                ->where('name', $oldName)
                ->update(['name' => $newName]);
        }

        foreach ([
            'Facture client FAC-000145 validÃƒÂ©e' => 'Facture client FAC-000145 validée',
            'Facture client FAC-000145 validÃ©e' => 'Facture client FAC-000145 validée',
            'Contrat fournisseur maintenance rÃƒÂ©seau' => 'Contrat fournisseur maintenance réseau',
            'Contrat fournisseur maintenance rÃ©seau' => 'Contrat fournisseur maintenance réseau',
        ] as $oldTitle => $newTitle) {
            ArchiveRecord::query()
                ->where('company_site_id', $site->id)
                ->where('title', $oldTitle)
                ->update(['title' => $newTitle]);
        }
    }

    private function room(CompanySite $site, ?User $admin, string $name, string $code, int $capacity): ArchiveRoom
    {
        return ArchiveRoom::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'name' => $name],
            [
                'created_by' => $admin?->id,
                'reference' => 'SAL-'.str_pad((string) ($site->id * 1000 + crc32($code) % 900), 6, '0', STR_PAD_LEFT),
                'code' => $code,
                'capacity' => $capacity,
                'status' => ArchiveRoom::STATUS_ACTIVE,
                'description' => 'Emplacement physique '.$name.'.',
            ],
        );
    }

    private function rack(CompanySite $site, ?User $admin, ArchiveRoom $room, string $name, string $code, int $capacity): ArchiveRack
    {
        return ArchiveRack::query()->updateOrCreate(['company_site_id' => $site->id, 'name' => $name], [
            'archive_room_id' => $room->id,
            'created_by' => $admin?->id,
            'reference' => 'RAY-'.str_pad((string) ($site->id * 1000 + crc32($code) % 900), 6, '0', STR_PAD_LEFT),
            'code' => $code,
            'capacity' => $capacity,
            'status' => ArchiveRoom::STATUS_ACTIVE,
            'description' => 'Rayon physique '.$name.'.',
        ]);
    }

    private function cabinet(CompanySite $site, ?User $admin, ArchiveRack $rack, string $name, string $code, int $capacity): ArchiveCabinet
    {
        return ArchiveCabinet::query()->updateOrCreate(['company_site_id' => $site->id, 'name' => $name], [
            'archive_rack_id' => $rack->id,
            'created_by' => $admin?->id,
            'reference' => 'ARM-'.str_pad((string) ($site->id * 1000 + crc32($code) % 900), 6, '0', STR_PAD_LEFT),
            'code' => $code,
            'capacity' => $capacity,
            'status' => ArchiveRoom::STATUS_ACTIVE,
            'description' => 'Armoire physique '.$name.'.',
        ]);
    }

    private function shelf(CompanySite $site, ?User $admin, ArchiveCabinet $cabinet, string $name, string $code, int $capacity): ArchiveShelf
    {
        return ArchiveShelf::query()->updateOrCreate(['company_site_id' => $site->id, 'name' => $name], [
            'archive_cabinet_id' => $cabinet->id,
            'created_by' => $admin?->id,
            'reference' => 'ETA-'.str_pad((string) ($site->id * 1000 + crc32($code) % 900), 6, '0', STR_PAD_LEFT),
            'code' => $code,
            'capacity' => $capacity,
            'status' => ArchiveRoom::STATUS_ACTIVE,
            'description' => 'Étagère physique '.$name.'.',
        ]);
    }

    private function compartment(CompanySite $site, ?User $admin, ArchiveShelf $shelf, string $name, string $code, int $capacity): ArchiveCompartment
    {
        return ArchiveCompartment::query()->updateOrCreate(['company_site_id' => $site->id, 'name' => $name], [
            'archive_shelf_id' => $shelf->id,
            'created_by' => $admin?->id,
            'reference' => 'CAS-'.str_pad((string) ($site->id * 1000 + crc32($code) % 900), 6, '0', STR_PAD_LEFT),
            'code' => $code,
            'capacity' => $capacity,
            'status' => ArchiveRoom::STATUS_ACTIVE,
            'description' => 'Casier physique '.$name.'.',
        ]);
    }

    private function box(CompanySite $site, ?User $admin, ArchiveShelf $shelf, ?ArchiveCompartment $compartment, string $name, string $code, int $capacity): ArchiveBox
    {
        return ArchiveBox::query()->updateOrCreate(['company_site_id' => $site->id, 'name' => $name], [
            'archive_shelf_id' => $compartment ? null : $shelf->id,
            'archive_compartment_id' => $compartment?->id,
            'created_by' => $admin?->id,
            'reference' => 'BOI-'.str_pad((string) ($site->id * 1000 + crc32($code) % 900), 6, '0', STR_PAD_LEFT),
            'code' => $code,
            'capacity' => $capacity,
            'status' => ArchiveRoom::STATUS_ACTIVE,
            'description' => 'Boîte physique '.$name.'.',
        ]);
    }

    private function container(CompanySite $site, ?User $admin, ArchiveBox $box, string $title, string $category, string $service, string $period): ArchiveContainer
    {
        return ArchiveContainer::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'title' => $title],
            [
                'archive_box_id' => $box->id,
                'created_by' => $admin?->id,
                'reference' => 'CLS-'.str_pad((string) ($site->id * 1000 + crc32($title) % 900), 6, '0', STR_PAD_LEFT),
                'category' => $category,
                'owner_service' => $service,
                'period_label' => $period,
                'confidentiality_level' => ArchiveContainer::CONFIDENTIALITY_INTERNAL,
                'capacity' => 80,
                'status' => ArchiveContainer::STATUS_ACTIVE,
                'description' => 'Classeur physique affecté à '.$box->name.'.',
            ],
        );
    }

    private function record(CompanySite $site, ?User $admin, ArchiveBox $box, ArchiveContainer $container, string $title, string $type, string $category, string $service, $date, $retentionUntil): void
    {
        $record = ArchiveRecord::query()->updateOrCreate(
            ['company_site_id' => $site->id, 'title' => $title],
            [
                'archive_container_id' => $container->id,
                'archive_box_id' => $box->id,
                'created_by' => $admin?->id,
                'reference' => 'ARC-'.str_pad((string) ($site->id * 1000 + crc32($title) % 900), 6, '0', STR_PAD_LEFT),
                'document_type' => $type,
                'category' => $category,
                'owner_service' => $service,
                'document_date' => $date->toDateString(),
                'archived_at' => now()->toDateString(),
                'retention_until' => $retentionUntil->toDateString(),
                'confidentiality_level' => ArchiveContainer::CONFIDENTIALITY_INTERNAL,
                'status' => ArchiveRecord::STATUS_ARCHIVED,
                'description' => 'Document finalisé conservé physiquement dans '.$container->title.'.',
            ],
        );

        ArchiveActivity::query()->firstOrCreate(
            ['company_site_id' => $site->id, 'subject_type' => ArchiveRecord::class, 'subject_id' => $record->id, 'action' => 'record_archived'],
            ['actor_id' => $admin?->id, 'to_status' => $record->status, 'comment' => 'Document archivé par le seeder.'],
        );
    }
}

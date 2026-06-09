<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\DocumentManagementActivity;
use App\Models\DocumentManagementFolder;
use App\Models\DocumentManagementRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentManagementSeeder extends Seeder
{
    public function run(): void
    {
        $sites = CompanySite::query()
            ->whereJsonContains('modules', CompanySite::MODULE_DOCUMENT_MANAGEMENT)
            ->with('company')
            ->get();

        if ($sites->isEmpty()) {
            $site = $this->ensureDemoDocumentManagementSite();

            if ($site) {
                $sites = collect([$site->load('company')]);
            }
        }

        $sites->each(fn (CompanySite $site) => $this->seedSite($site));
    }

    private function ensureDemoDocumentManagementSite(): ?CompanySite
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
                'code' => 'KIN-GED',
            ],
            [
                'responsible_id' => $admin?->id,
                'name' => 'EXAD Kinshasa',
                'type' => CompanySite::TYPE_OFFICE,
                'city' => 'Kinshasa',
                'phone' => null,
                'email' => $company->email,
                'address' => $company->address,
                'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_DOCUMENT_MANAGEMENT],
                'currency' => 'USD',
                'status' => CompanySite::STATUS_ACTIVE,
            ],
        );

        $modules = collect($site->modules ?? [])
            ->merge([CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_DOCUMENT_MANAGEMENT])
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
                        CompanySite::MODULE_DOCUMENT_MANAGEMENT => true,
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
        $assignee = $users->first(fn (User $user) => $user->isUser()) ?? $admin;

        $folders = collect([
            ['name' => 'Bureau d’ordre', 'category' => 'Courrier', 'description' => 'Enregistrement officiel des courriers entrants et sortants.'],
            ['name' => 'Direction générale', 'category' => 'Décisions', 'description' => 'Notes, décisions et documents stratégiques.'],
            ['name' => 'Contrats et conventions', 'category' => 'Juridique', 'description' => 'Contrats, conventions et pièces annexes.'],
        ])->values()->mapWithKeys(function (array $folder, int $index) use ($site, $admin) {
            $model = DocumentManagementFolder::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'name' => $folder['name']],
                [
                    'created_by' => $admin?->id,
                    'reference' => 'DOS-'.str_pad((string) (($site->id * 1000) + $index + 1), 6, '0', STR_PAD_LEFT),
                    'category' => $folder['category'],
                    'status' => DocumentManagementFolder::STATUS_ACTIVE,
                    'description' => $folder['description'],
                ],
            );

            return [$folder['name'] => $model];
        });

        $records = [
            [
                'subject' => 'Demande de validation du contrat fournisseur',
                'record_type' => DocumentManagementRecord::TYPE_INCOMING,
                'direction' => 'incoming',
                'sender' => 'Fournisseur Central',
                'recipient' => $site->name,
                'category' => 'Contrat',
                'priority' => DocumentManagementRecord::PRIORITY_HIGH,
                'status' => DocumentManagementRecord::STATUS_ASSIGNED,
                'received_at' => now()->subDays(2)->toDateString(),
                'due_at' => now()->addDays(2)->toDateString(),
                'folder' => 'Contrats et conventions',
                'summary' => 'Courrier entrant demandant la validation d’un avenant fournisseur.',
            ],
            [
                'subject' => 'Note interne sur la procédure de classement',
                'record_type' => DocumentManagementRecord::TYPE_INTERNAL,
                'direction' => 'internal',
                'sender' => $site->name,
                'recipient' => 'Tous services',
                'category' => 'Procédure',
                'priority' => DocumentManagementRecord::PRIORITY_NORMAL,
                'status' => DocumentManagementRecord::STATUS_VALIDATED,
                'received_at' => now()->subDays(5)->toDateString(),
                'due_at' => now()->addWeek()->toDateString(),
                'folder' => 'Direction générale',
                'summary' => 'Document interne validé pour harmoniser le classement des pièces.',
            ],
            [
                'subject' => 'Réponse au courrier client sur le dossier EXD-24',
                'record_type' => DocumentManagementRecord::TYPE_OUTGOING,
                'direction' => 'outgoing',
                'sender' => $site->name,
                'recipient' => 'Client institutionnel',
                'category' => 'Réponse officielle',
                'priority' => DocumentManagementRecord::PRIORITY_URGENT,
                'status' => DocumentManagementRecord::STATUS_IN_REVIEW,
                'received_at' => now()->subDay()->toDateString(),
                'due_at' => now()->addDay()->toDateString(),
                'folder' => 'Bureau d’ordre',
                'summary' => 'Projet de réponse sortante en attente de validation.',
            ],
            [
                'subject' => 'Archivage des pièces administratives mensuelles',
                'record_type' => DocumentManagementRecord::TYPE_INTERNAL,
                'direction' => 'internal',
                'sender' => 'Administration',
                'recipient' => 'Archives',
                'category' => 'Classement',
                'priority' => DocumentManagementRecord::PRIORITY_LOW,
                'status' => DocumentManagementRecord::STATUS_CLOSED,
                'received_at' => now()->subDays(12)->toDateString(),
                'closed_at' => now()->subDays(4),
                'folder' => 'Bureau d’ordre',
                'summary' => 'Lot de documents clôturé et prêt pour archivage.',
            ],
        ];

        foreach ($records as $index => $record) {
            $folder = $folders->get($record['folder']);
            unset($record['folder']);

            $model = DocumentManagementRecord::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'subject' => $record['subject']],
                array_merge($record, [
                    'document_management_folder_id' => $folder?->id,
                    'created_by' => $admin?->id,
                    'assigned_to' => $assignee?->id,
                    'reference' => 'GED-'.str_pad((string) (($site->id * 1000) + $index + 1), 6, '0', STR_PAD_LEFT),
                ]),
            );

            DocumentManagementActivity::query()->firstOrCreate(
                [
                    'document_management_record_id' => $model->id,
                    'action' => 'registered',
                ],
                [
                    'actor_id' => $admin?->id,
                    'to_status' => $model->status,
                    'comment' => 'Document enregistré dans la GED.',
                ],
            );
        }
    }
}

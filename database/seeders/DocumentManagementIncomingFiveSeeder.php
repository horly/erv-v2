<?php

namespace Database\Seeders;

use App\Models\CompanySite;
use App\Models\DocumentManagementActivity;
use App\Models\DocumentManagementFolder;
use App\Models\DocumentManagementRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentManagementIncomingFiveSeeder extends Seeder
{
    public function run(): void
    {
        $sites = CompanySite::query()
            ->whereJsonContains('modules', CompanySite::MODULE_DOCUMENT_MANAGEMENT)
            ->with('company')
            ->get();

        foreach ($sites as $site) {
            $this->seedIncomingRecords($site);
        }
    }

    private function seedIncomingRecords(CompanySite $site): void
    {
        $users = $site->users()->orderBy('users.name')->get();
        $admin = $users->first(fn (User $user): bool => $user->isAdmin())
            ?? User::query()->where('subscription_id', $site->company?->subscription_id)->where('role', User::ROLE_ADMIN)->first();
        $assignee = $users->first(fn (User $user): bool => $user->isUser()) ?? $admin;

        $folderNames = [
            'Bureau d’ordre' => 'Courrier',
            'Direction générale' => 'Décisions',
            'Contrats et conventions' => 'Juridique',
        ];

        $folders = collect($folderNames)->mapWithKeys(function (string $category, string $name) use ($site, $admin) {
            $folder = DocumentManagementFolder::query()->firstOrCreate(
                ['company_site_id' => $site->id, 'name' => $name],
                [
                    'created_by' => $admin?->id,
                    'reference' => $this->nextFolderReference(),
                    'category' => $category,
                    'status' => DocumentManagementFolder::STATUS_ACTIVE,
                    'description' => 'Dossier GED créé automatiquement pour les courriers.',
                ],
            );

            return [$name => $folder];
        });

        $records = [
            [
                'subject' => 'Transmission du rapport mensuel de conformité',
                'sender' => 'Direction Provinciale',
                'category' => 'Rapport',
                'priority' => DocumentManagementRecord::PRIORITY_NORMAL,
                'status' => DocumentManagementRecord::STATUS_REGISTERED,
                'received_at' => now()->subDay()->toDateString(),
                'due_at' => now()->addDays(5)->toDateString(),
                'folder' => 'Bureau d’ordre',
                'summary' => 'Courrier entrant transmettant le rapport mensuel pour exploitation interne.',
            ],
            [
                'subject' => 'Invitation à une réunion de coordination',
                'sender' => 'Partenaire Institutionnel',
                'category' => 'Invitation',
                'priority' => DocumentManagementRecord::PRIORITY_HIGH,
                'status' => DocumentManagementRecord::STATUS_ASSIGNED,
                'received_at' => now()->subDays(3)->toDateString(),
                'due_at' => now()->addDay()->toDateString(),
                'folder' => 'Direction générale',
                'summary' => 'Invitation officielle à une réunion de coordination avec présence requise.',
            ],
            [
                'subject' => 'Dépôt de facture pour traitement administratif',
                'sender' => 'Logistique Plus',
                'category' => 'Facture',
                'priority' => DocumentManagementRecord::PRIORITY_NORMAL,
                'status' => DocumentManagementRecord::STATUS_IN_REVIEW,
                'received_at' => now()->subDays(4)->toDateString(),
                'due_at' => now()->addDays(3)->toDateString(),
                'folder' => 'Bureau d’ordre',
                'summary' => 'Courrier accompagnant une facture fournisseur à vérifier avant transmission.',
            ],
            [
                'subject' => 'Demande de copie certifiée du dossier administratif',
                'sender' => 'Client Institutionnel',
                'category' => 'Demande',
                'priority' => DocumentManagementRecord::PRIORITY_URGENT,
                'status' => DocumentManagementRecord::STATUS_ASSIGNED,
                'received_at' => now()->subDays(6)->toDateString(),
                'due_at' => now()->addDays(2)->toDateString(),
                'folder' => 'Contrats et conventions',
                'summary' => 'Demande urgente de copie certifiée liée à un dossier administratif.',
            ],
            [
                'subject' => 'Notification de mise à jour des exigences documentaires',
                'sender' => 'Autorité de Régulation',
                'category' => 'Réglementaire',
                'priority' => DocumentManagementRecord::PRIORITY_HIGH,
                'status' => DocumentManagementRecord::STATUS_VALIDATED,
                'received_at' => now()->subDays(8)->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'folder' => 'Direction générale',
                'summary' => 'Notification officielle sur la mise à jour des exigences documentaires.',
            ],
        ];

        foreach ($records as $record) {
            $folder = $folders->get($record['folder']);
            unset($record['folder']);

            $model = DocumentManagementRecord::query()->firstOrNew([
                'company_site_id' => $site->id,
                'subject' => $record['subject'],
            ]);

            $model->fill(array_merge($record, [
                'document_management_folder_id' => $folder?->id,
                'created_by' => $model->created_by ?? $admin?->id,
                'assigned_to' => $model->assigned_to ?? $assignee?->id,
                'reference' => $model->reference ?? $this->nextRecordReference(),
                'record_type' => DocumentManagementRecord::TYPE_INCOMING,
                'direction' => DocumentManagementRecord::TYPE_INCOMING,
                'recipient' => $site->name,
            ]));
            $model->save();

            DocumentManagementActivity::query()->firstOrCreate(
                [
                    'document_management_record_id' => $model->id,
                    'action' => 'registered',
                ],
                [
                    'actor_id' => $admin?->id,
                    'to_status' => $model->status,
                    'comment' => 'Courrier entrant ajouté dans la GED.',
                ],
            );
        }
    }

    private function nextFolderReference(): string
    {
        $nextId = ((int) (DocumentManagementFolder::query()->max('id') ?? 0)) + 1;

        do {
            $reference = 'DOS-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while (DocumentManagementFolder::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function nextRecordReference(): string
    {
        $nextId = ((int) (DocumentManagementRecord::query()->max('id') ?? 0)) + 1;

        do {
            $reference = 'GED-'.str_pad((string) $nextId++, 6, '0', STR_PAD_LEFT);
        } while (DocumentManagementRecord::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

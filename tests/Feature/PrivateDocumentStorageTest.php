<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\DocumentManagementRecord;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    private const FILE_PATH = 'document-management/incoming/test-confidentiel.pdf';

    private User $superadmin;

    private Company $company;

    private CompanySite $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::query()->create([
            'name' => 'Storage Tester',
            'email' => 'storage-tester@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::query()->create([
            'name' => 'Test plan',
            'code' => 'TEST-PLAN',
            'type' => 'pro',
            'status' => 'active',
            'company_limit' => 5,
        ]);

        $this->company = Company::query()->create([
            'subscription_id' => $subscription->id,
            'created_by' => $this->superadmin->id,
            'name' => 'Storage Company',
            'email' => 'storage-company@example.test',
            'country' => 'CD',
        ]);

        $this->site = CompanySite::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Storage Site',
            'type' => 'billing',
            'modules' => [CompanySite::MODULE_DOCUMENT_MANAGEMENT],
            'currency' => 'USD',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        DocumentManagementRecord::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'reference' => 'GED-TEST-0001',
            'record_type' => DocumentManagementRecord::TYPE_INCOMING,
            'direction' => 'incoming',
            'subject' => 'Document confidentiel',
            'file_path' => self::FILE_PATH,
        ]);

        Storage::disk('local')->put(self::FILE_PATH, '%PDF-1.4 test');
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->delete(self::FILE_PATH);

        parent::tearDown();
    }

    public function test_guest_cannot_download_private_document(): void
    {
        $this->get(private_storage_url(self::FILE_PATH))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_download_private_document(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(private_storage_url(self::FILE_PATH))
            ->assertOk();

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_user_without_site_access_cannot_download_private_document(): void
    {
        $otherSubscription = Subscription::query()->create([
            'name' => 'Other plan',
            'code' => 'OTHER-PLAN',
            'type' => 'pro',
            'status' => 'active',
            'company_limit' => 5,
        ]);

        $outsider = User::query()->create([
            'name' => 'Outsider',
            'email' => 'outsider@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
            'subscription_id' => $otherSubscription->id,
        ]);

        $this->actingAs($outsider)
            ->get(private_storage_url(self::FILE_PATH))
            ->assertNotFound();

        $unassignedUser = User::query()->create([
            'name' => 'Unassigned',
            'email' => 'unassigned@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($unassignedUser)
            ->get(private_storage_url(self::FILE_PATH))
            ->assertNotFound();
    }

    public function test_assigned_user_can_download_private_document(): void
    {
        $member = User::query()->create([
            'name' => 'Site Member',
            'email' => 'member@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);
        $member->sites()->attach($this->site->id);

        $this->actingAs($member)
            ->get(private_storage_url(self::FILE_PATH))
            ->assertOk();
    }

    public function test_unknown_or_untracked_path_is_not_served(): void
    {
        Storage::disk('local')->put('document-management/incoming/orphan.pdf', 'orphan');

        $this->actingAs($this->superadmin)
            ->get(private_storage_url('document-management/incoming/orphan.pdf'))
            ->assertNotFound();

        $this->actingAs($this->superadmin)
            ->get(private_storage_url('autre-dossier/fichier.txt'))
            ->assertNotFound();

        Storage::disk('local')->delete('document-management/incoming/orphan.pdf');
    }
}

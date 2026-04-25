<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $subscription = Subscription::updateOrCreate(
            ['code' => 'EXAD-DEFAULT'],
            [
                'name' => 'Abonnement EXAD par defaut',
                'type' => 'standard',
                'company_limit' => 1,
                'status' => 'active',
                'expires_at' => now()->addYear()->toDateString(),
            ],
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@erp.loc'],
            [
                'subscription_id' => $subscription->id,
                'name' => 'admin',
                'password' => 'ATRbhgdfbgf@#154dsgfd',
                'role' => User::ROLE_ADMIN,
            ],
        );

        $user = User::updateOrCreate(
            ['email' => 'user1@erp.loc'],
            [
                'subscription_id' => $subscription->id,
                'name' => 'user1',
                'password' => 'tYhdhsfe154@sh#sgfd',
                'role' => User::ROLE_USER,
            ],
        );

        User::updateOrCreate(
            ['email' => 'superadmin@erp.loc'],
            [
                'subscription_id' => null,
                'name' => 'superadmin',
                'password' => 'H@mshyef@#154dsgfd',
                'role' => User::ROLE_SUPERADMIN,
            ],
        );

        $company = Company::updateOrCreate(
            ['email' => 'contact@prestavice.com'],
            [
                'subscription_id' => $subscription->id,
                'created_by' => $admin->id,
                'name' => 'Prestavice',
                'phone_number' => null,
                'address' => null,
            ],
        );

        $company->users()->syncWithoutDetaching([
            $user->id => [
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
        ]);
    }
}

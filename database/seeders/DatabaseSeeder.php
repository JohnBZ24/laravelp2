<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
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
        $permissions = [
            'conversations.view',
            'conversations.create',
            'conversations.view.any',
            'conversations.update.any',
            'conversations.delete.any',
            'knowledge_bases.view',
            'knowledge_bases.manage',
            'documents.view',
            'documents.manage',
            'orders.view',
            'tickets.create',
            'users.view.any',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate(['name' => $permissionName], ['label' => $permissionName]);
        }

        $adminRole = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $memberRole = Role::query()->firstOrCreate(['name' => 'member'], ['label' => 'Member']);

        $adminRole->permissions()->sync(Permission::query()->pluck('id'));
        $memberRole->permissions()->sync(Permission::query()->whereIn('name', [
            'conversations.view',
            'conversations.create',
            'knowledge_bases.view',
            'documents.view',
            'orders.view',
            'tickets.create',
        ])->pluck('id'));

        $adminUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@example.com',
        ])->roles()->syncWithoutDetaching([$memberRole->id]);
    }
}

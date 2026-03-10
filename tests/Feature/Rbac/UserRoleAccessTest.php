<?php

namespace Tests\Feature\Rbac;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_access_users_index_but_cannot_manage_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        /** @var User $member */
        $member = User::factory()->create();
        /** @var User $target */
        $target = User::factory()->create();
        $member->syncRoles(['member']);

        $this->actingAs($member)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($member)
            ->get('/admin/users/create')
            ->assertForbidden();

        $this->actingAs($member)
            ->get("/admin/users/{$target->id}/edit")
            ->assertForbidden();

        $this->actingAs($member);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit($target));
        $this->assertFalse(UserResource::canDelete($target));
    }

    public function test_admin_can_manage_users_and_roles_resource_is_admin_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create();
        /** @var User $member */
        $member = User::factory()->create();
        $admin->syncRoles(['admin']);
        $member->syncRoles(['member']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/users/{$member->id}/edit")
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/roles')
            ->assertOk();

        $this->actingAs($admin);
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(UserResource::canEdit($member));
        $this->assertTrue(UserResource::canDelete($member));
        $this->assertTrue(RoleResource::canViewAny());

        $this->actingAs($member)
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_member_receives_new_permissions_after_role_permissions_change(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        /** @var User $member */
        $member = User::factory()->create();
        $member->syncRoles(['member']);

        $this->assertFalse($member->fresh()->can('users.create'));
        $this->assertFalse($member->fresh()->can('users.update'));
        $this->assertFalse($member->fresh()->can('users.delete'));

        $memberRole = Role::findByName('member', 'web');
        $memberRole->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($member->fresh()->can('users.create'));
        $this->assertTrue($member->fresh()->can('users.update'));
        $this->assertTrue($member->fresh()->can('users.delete'));
    }
}

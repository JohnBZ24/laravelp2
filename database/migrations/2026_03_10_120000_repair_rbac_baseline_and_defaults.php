<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        if (! is_array($tableNames) || empty($tableNames)) {
            return;
        }

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        if (Schema::hasTable($rolesTable) && Schema::hasColumn($rolesTable, 'guard_name')) {
            DB::table($rolesTable)->whereNull('guard_name')->update(['guard_name' => 'web']);
            DB::table($rolesTable)->where('guard_name', '')->update(['guard_name' => 'web']);
        }

        if (Schema::hasTable($permissionsTable) && Schema::hasColumn($permissionsTable, 'guard_name')) {
            DB::table($permissionsTable)->whereNull('guard_name')->update(['guard_name' => 'web']);
            DB::table($permissionsTable)->where('guard_name', '')->update(['guard_name' => 'web']);
        }

        if (Schema::hasTable($rolesTable) && Schema::hasColumn($rolesTable, 'name') && Schema::hasColumn($rolesTable, 'guard_name')) {
            try {
                Schema::table($rolesTable, function (Blueprint $table): void {
                    $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
                });
            } catch (Throwable) {
            }
        }

        if (Schema::hasTable($permissionsTable) && Schema::hasColumn($permissionsTable, 'name') && Schema::hasColumn($permissionsTable, 'guard_name')) {
            try {
                Schema::table($permissionsTable, function (Blueprint $table): void {
                    $table->unique(['name', 'guard_name'], 'permissions_name_guard_name_unique');
                });
            } catch (Throwable) {
            }
        }

        if (! Schema::hasTable($permissionsTable) || ! Schema::hasTable($rolesTable) || ! Schema::hasTable($roleHasPermissionsTable)) {
            return;
        }

        $now = now();
        $permissions = ['users.view', 'users.create', 'users.update', 'users.delete', 'roles.manage'];

        foreach ($permissions as $permissionName) {
            DB::table($permissionsTable)->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }

        foreach (['member', 'admin'] as $roleName) {
            DB::table($rolesTable)->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }

        $memberRoleId = DB::table($rolesTable)->where('name', 'member')->where('guard_name', 'web')->value('id');
        $adminRoleId = DB::table($rolesTable)->where('name', 'admin')->where('guard_name', 'web')->value('id');

        $permissionIdsByName = DB::table($permissionsTable)
            ->where('guard_name', 'web')
            ->whereIn('name', $permissions)
            ->pluck('id', 'name');

        if ($memberRoleId) {
            DB::table($roleHasPermissionsTable)->where($pivotRole, $memberRoleId)->delete();

            $memberViewPermissionId = $permissionIdsByName['users.view'] ?? null;

            if ($memberViewPermissionId) {
                DB::table($roleHasPermissionsTable)->insertOrIgnore([
                    $pivotPermission => $memberViewPermissionId,
                    $pivotRole => $memberRoleId,
                ]);
            }
        }

        if ($adminRoleId) {
            DB::table($roleHasPermissionsTable)->where($pivotRole, $adminRoleId)->delete();

            $adminPermissionRows = [];

            foreach ($permissionIdsByName as $permissionId) {
                $adminPermissionRows[] = [
                    $pivotPermission => $permissionId,
                    $pivotRole => $adminRoleId,
                ];
            }

            if ($adminPermissionRows !== []) {
                DB::table($roleHasPermissionsTable)->insertOrIgnore($adminPermissionRows);
            }
        }

        if ($memberRoleId && Schema::hasTable('users') && Schema::hasTable($modelHasRolesTable)) {
            $userIdsWithRole = DB::table($modelHasRolesTable)
                ->where('model_type', User::class)
                ->pluck($modelMorphKey)
                ->all();

            $usersWithoutRole = DB::table('users')
                ->when($userIdsWithRole !== [], fn ($query) => $query->whereNotIn('id', $userIdsWithRole))
                ->pluck('id')
                ->all();

            $rows = [];

            foreach ($usersWithoutRole as $userId) {
                $rows[] = [
                    $pivotRole => $memberRoleId,
                    'model_type' => User::class,
                    $modelMorphKey => $userId,
                ];
            }

            if ($rows !== []) {
                DB::table($modelHasRolesTable)->insertOrIgnore($rows);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally left blank: this migration repairs/normalizes RBAC data.
    }
};

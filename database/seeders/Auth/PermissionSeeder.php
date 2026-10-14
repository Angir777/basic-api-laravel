<?php

namespace Database\Seeders\Auth;

use App\Enums\EPermissionGroupName;
use App\Enums\EPermissionName;
use App\Enums\ERoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Reset cached roles and permissions
         */
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * Creating permissions by group
         */
        $this->createAccessPermissions();
        $this->createUserPermissions();
        $this->createRolePermissions();
        $this->createPermissionPermissions();

        /**
         * Assign permission to roles
         */
        $this->prepareSuperAdminRole();
        $this->prepareAdminRole();
        $this->prepareTeacherRole();
        $this->prepareStudentRole();
    }

    /**
     * Creating permissions by group
     */
    private function createAccessPermissions(): void
    {
        $permissions = [
            EPermissionName::BASE_SUPER_ADMIN_ACCESS,
            EPermissionName::BASE_ADMIN_ACCESS,
            EPermissionName::BASE_TEACHER_ACCESS,
            EPermissionName::BASE_STUDENT_ACCESS,
        ];

        $this->createPermissions($permissions, EPermissionGroupName::ACCESS->value);
    }

    private function createUserPermissions(): void
    {
        $permissions = [
            EPermissionName::USERS_VIEW,
            EPermissionName::USERS_CREATE,
            EPermissionName::USERS_UPDATE,
            EPermissionName::USERS_DELETE,
        ];

        $this->createPermissions($permissions, EPermissionGroupName::USERS->value);
    }

    private function createRolePermissions(): void
    {
        $permissions = [
            EPermissionName::ROLES_VIEW,
            EPermissionName::ROLES_CREATE,
            EPermissionName::ROLES_UPDATE,
            EPermissionName::ROLES_DELETE,
        ];

        $this->createPermissions($permissions, EPermissionGroupName::ROLES->value);
    }

    private function createPermissionPermissions(): void
    {
        $permissions = [
            EPermissionName::PERMISSIONS_VIEW,
            EPermissionName::PERMISSIONS_CREATE,
            EPermissionName::PERMISSIONS_UPDATE,
            EPermissionName::PERMISSIONS_DELETE,
        ];

        $this->createPermissions($permissions, EPermissionGroupName::PERMISSIONS->value);
    }

    private function createPermissions(array $permissions, string $group): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'api',
                ],
                [
                    'group_name' => $group,
                ]
            );
        }
    }

    /**
     * Assign permission to roles
     */
    private function prepareSuperAdminRole(): void
    {
        $role = Role::where('name', ERoleName::SUPERADMIN)->firstOrFail();

        $role->syncPermissions(Permission::all());
    }

    private function prepareAdminRole(): void
    {
        $role = Role::where('name', ERoleName::ADMIN)->firstOrFail();

        $role->syncPermissions([
            EPermissionName::BASE_ADMIN_ACCESS,

            EPermissionName::USERS_VIEW,
            EPermissionName::USERS_CREATE,
            EPermissionName::USERS_UPDATE,
            EPermissionName::USERS_DELETE,

            EPermissionName::ROLES_VIEW,

            EPermissionName::PERMISSIONS_VIEW,
        ]);
    }

    private function prepareTeacherRole(): void
    {
        $role = Role::where('name', ERoleName::TEACHER)->firstOrFail();

        $role->syncPermissions([
            EPermissionName::BASE_TEACHER_ACCESS,
        ]);
    }

    private function prepareStudentRole(): void
    {
        $role = Role::where('name', ERoleName::STUDENT)->firstOrFail();

        $role->syncPermissions([
            EPermissionName::BASE_STUDENT_ACCESS,
        ]);
    }
}

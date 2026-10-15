<?php

namespace App\Services\User;

use App\Enums\EHistoryActionType;
use App\Events\RoleChanged;
use App\Helpers\Access\RefreshUserTokenHelper;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
            ]);

            $permissionNames = collect($data['permissions'])->filter()->values()->all();

            $permissionIds = Permission::query()->whereIn('name', $permissionNames)->pluck('id')->all();

            $role->syncPermissions($permissionIds);

            // Entry in the change history.
            $newData = Role::where('id', '=', $role->id)->first();
            event(
                new RoleChanged(
                    null,
                    $newData,
                    EHistoryActionType::CREATE
                )
            );

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $oldData = Role::with('permissions')->where('id', '=', $role->id)->first();

            $role->update([
                'name' => $data['name'],
            ]);

            $permissionNames = collect($data['permissions'])->filter()->values()->all();

            $permissionIds = Permission::query()->whereIn('name', $permissionNames)->pluck('id')->all();

            // Automatic logout.
            $role->users()
                ->pluck('users.id')
                ->each(
                    fn ($userId) => RefreshUserTokenHelper::shouldLogoutOnNextRequest($userId)
                );

            $role->syncPermissions($permissionIds);

            // Entry in the change history.
            $newData = Role::with('permissions')->where('id', '=', $role->id)->first();
            event(
                new RoleChanged(
                    $oldData,
                    $newData,
                    EHistoryActionType::UPDATE
                )
            );

            return $role;
        });
    }

    public function delete(Role $role): Role
    {
        return DB::transaction(function () use ($role) {
            $oldData = Role::with('permissions')->where('id', '=', $role->id)->first();

            $role->syncPermissions([]);

            $role->delete();

            // Entry in the change history.
            $newData = null;
            event(
                new RoleChanged(
                    $oldData,
                    $newData,
                    EHistoryActionType::DELETE
                )
            );

            return $role;
        });
    }
}

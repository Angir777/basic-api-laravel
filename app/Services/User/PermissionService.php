<?php

namespace App\Services\User;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function create(array $data): Permission
    {
        return DB::transaction(function () use ($data) {
            $permission = Permission::create([
                'name' => $data['name'],
                'group_name' => $data['groupName'],
            ]);

            return $permission;
        });
    }

    public function update(Permission $permission, array $data): Permission
    {
        return DB::transaction(function () use ($permission, $data) {
            $permission->update([
                'name' => $data['name'],
                'group_name' => $data['groupName'],
            ]);

            return $permission;
        });
    }

    public function delete(Permission $permission): Permission
    {
        return DB::transaction(function () use ($permission) {
            $permission->delete();

            return $permission;
        });
    }
}

<?php

namespace Database\Seeders\Auth;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['guard' => 'api', 'name' => config('access.roles.superadmin')],
            ['guard' => 'api', 'name' => config('access.roles.admin')],
            ['guard' => 'api', 'name' => config('access.roles.teacher')],
            ['guard' => 'api', 'name' => config('access.roles.student')],
        ];

        foreach ($roles as $role) {
            $r = Role::where('name', '=', $role['name'])->first();
            if ($r == null) {
                Role::create([
                    'name' => $role['name'],
                    'guard_name' => $role['guard'],
                ]);
            }
        }
    }
}

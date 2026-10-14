<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(50)->create();
        foreach ($users as $user) {
            $user->assignRole(config('access.roles.teacher'));
        }

        $users = User::factory(50)->create();
        foreach ($users as $user) {
            $user->assignRole(config('access.roles.student'));
        }
    }
}

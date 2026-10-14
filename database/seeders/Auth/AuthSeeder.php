<?php

namespace Database\Seeders\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);

        $superAdmin = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'superadmin@app.local',
            'password' => Hash::make('root12'),
            'email_verified_at' => Carbon::now(),
            'confirmed' => true,
            'confirmation_code' => md5(uniqid(mt_rand(), true)),
        ]);
        $superAdmin->assignRole(config('access.roles.superadmin'));

        $admin = User::create([
            'first_name' => 'Anakin',
            'last_name' => 'Skywalker',
            'email' => 'admin@app.local',
            'password' => Hash::make('root12'),
            'email_verified_at' => Carbon::now(),
            'confirmed' => true,
            'confirmation_code' => md5(uniqid(mt_rand(), true)),
        ]);
        $admin->assignRole(config('access.roles.admin'));

        $user = User::create([
            'first_name' => 'Sherlock',
            'last_name' => 'Holmes',
            'email' => 'teacher@app.local',
            'password' => Hash::make('root12'),
            'email_verified_at' => Carbon::now(),
            'confirmed' => true,
            'confirmation_code' => md5(uniqid(mt_rand(), true)),
        ]);
        $user->assignRole(config('access.roles.teacher'));

        $user = User::create([
            'first_name' => 'Obi-Wan',
            'last_name' => 'Kenobi',
            'email' => 'student@app.local',
            'password' => Hash::make('root12'),
            'email_verified_at' => Carbon::now(),
            'confirmed' => false,
            'confirmation_code' => null,
        ]);
        $user->assignRole(config('access.roles.student'));
    }
}

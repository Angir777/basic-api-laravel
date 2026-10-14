<?php

namespace Database\Seeders;

use Database\Seeders\Auth\PermissionSeeder;
use Illuminate\Database\Seeder;

class RefreshPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
    }
}

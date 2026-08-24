<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Only bootstraps the global permission catalog RBAC depends on — this
     * runs on every install, including production. A demo company with
     * realistic data (§53-54) is a separate, explicit opt-in: run
     * `php artisan db:seed --class=DemoDataSeeder`.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Demo company/users/work orders (spec §53-54) are seeded in Fase 26
     * (Production Readiness). For now this only bootstraps the global
     * permission catalog RBAC depends on.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Defaults every site needs. Demo content is seeded only outside production.
     */
    public function run(): void
    {
        $this->call(DefaultsSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}

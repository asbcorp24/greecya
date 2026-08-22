<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoMediaSeeder::class,
            SiteSettingsSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
            CrmSeeder::class,
            TrainingSeeder::class,
        ]);
    }
}

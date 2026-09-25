<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Run with:
     *   php artisan db:seed
     *   php artisan migrate:fresh --seed   (drops all tables first)
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  🌱  Database Seeding Started');
        $this->command->info('═══════════════════════════════════════');

        $this->call([
            // ── Users & Auth ──────────────────────────────
            AdminUserSeeder::class,

            // ── Add more seeders below as you create them ─
            // ProductCategorySeeder::class,
            // ProductSeeder::class,
            // FeatureSeeder::class,
            // FaqSeeder::class,
            // BlogSeeder::class,
            // SettingSeeder::class,
            // MenuSeeder::class,
            // HomeBannerSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  ✅  Database Seeding Completed');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('');
        $this->command->info('  Admin login credentials:');
        $this->command->info('    Email:    admin@bssshop.com');
        $this->command->info('    Password: admin123');
        $this->command->info('');
    }
}
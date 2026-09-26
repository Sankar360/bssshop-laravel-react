<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Because User model has a setPasswordAttribute mutator,
        // pass PLAIN TEXT — it will be hashed once automatically.
        $admin = User::updateOrCreate(
            ['email' => 'admin@bssshop.com'],
            [
                'name'     => 'Admin',
                'password' => 'admin123',   // plain text — mutator hashes it
                'role'     => 'admin',
                'status'   => 'active',
                'phone'    => '1234567890',
            ]
        );

        $this->command->info('✅ Admin user seeded:');
        $this->command->info('   Email:    admin@bssshop.com');
        $this->command->info('   Password: admin123');
        $this->command->info('   ID:       ' . $admin->id);
        $this->command->info('   Role:     ' . $admin->role);
        $this->command->info('   Status:   ' . $admin->status);
    }
}
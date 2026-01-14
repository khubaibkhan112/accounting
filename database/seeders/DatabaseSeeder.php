<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Create Accountant User
        User::firstOrCreate(
            ['email' => 'accountant@example.com'],
            [
                'name' => 'Accountant',
                'password' => Hash::make('password'),
                'role' => 'accountant',
                'is_active' => true,
            ]
        );

        // Create Driver User
        User::firstOrCreate(
            ['email' => 'driver@example.com'],
            [
                'name' => 'Driver',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'is_active' => true,
            ]
        );

        $this->command->info('Default users created:');
        $this->command->info('Admin: admin@example.com / password');
        $this->command->info('Accountant: accountant@example.com / password');
        $this->command->info('Driver: driver@example.com / password');

        // Seed default chart of accounts
        $this->call(AccountSeeder::class);
        
        // Seed default settings
        $this->call(SettingsSeeder::class);
    }
}

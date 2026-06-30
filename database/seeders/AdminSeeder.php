<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@bsu.com',
                'password' => 'password',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Administrator',
                'email' => 'admin@bsu.com',
                'password' => 'password',
                'role' => 'admin',
            ],
        ];

        foreach ($admins as $adminData) {
            $user = User::where('email', $adminData['email'])->first();

            if ($user) {
                $this->command->info("Admin account already exists: {$adminData['email']}");
                continue;
            }

            User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => Hash::make($adminData['password']),
                'role' => $adminData['role'],
            ]);

            $this->command->info("Created admin account: {$adminData['email']} ({$adminData['role']})");
        }
    }
}

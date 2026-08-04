<?php

namespace Database\Seeders;

use App\Models\Student;
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
        // Default Admin Account
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator Guru',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Sample Students for Testing
        Student::updateOrCreate(
            ['nisn' => '0051234567'],
            [
                'name' => 'Ahmad Rizki',
                'class_name' => 'XII-RPL-1',
            ]
        );

        Student::updateOrCreate(
            ['nisn' => '0057654321'],
            [
                'name' => 'Siti Nurhaliza',
                'class_name' => 'XII-RPL-1',
            ]
        );
    }
}

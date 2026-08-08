<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
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

        // Sample Subjects
        $mtk = Subject::updateOrCreate(
            ['code' => 'MTK-01'],
            ['name' => 'Matematika']
        );

        $prog = Subject::updateOrCreate(
            ['code' => 'PROG-01'],
            ['name' => 'Pemrograman Web']
        );

        // Sample Schedules for XII-RPL-1
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        foreach ($days as $day) {
            Schedule::updateOrCreate(
                [
                    'class_name' => 'XII-RPL-1',
                    'day_of_week' => $day,
                    'subject_id' => $mtk->id,
                ],
                [
                    'teacher_name' => 'Pak Budi, S.Pd',
                    'start_time' => '07:00:00',
                    'end_time' => '17:00:00',
                ]
            );
        }
    }
}

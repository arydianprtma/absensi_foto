<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectAttendanceController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

// Public Student Attendance Webcam Route
Route::get('/', [AttendanceController::class, 'index'])->name('home');
Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi.index');
Route::post('/absensi/verifikasi', [AttendanceController::class, 'verify'])->name('absensi.verify');
Route::post('/absensi/verifikasi-otomatis', [AttendanceController::class, 'autoVerify'])->name('absensi.auto_verify');
Route::post('/absensi/bypass-satpam', [AttendanceController::class, 'bypassSatpam'])->name('absensi.bypass_satpam');
Route::get('/absensi/tts-audio', [AttendanceController::class, 'ttsAudio'])->name('absensi.tts_audio');

// Authenticated Dashboard & Management Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');

    // Students CRUD
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Teachers & User Roles CRUD
    Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
    Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

    // Attendance Settings
    Route::get('/attendance-settings', [AttendanceSettingController::class, 'edit'])->name('attendance-settings.edit');
    Route::post('/attendance-settings', [AttendanceSettingController::class, 'update'])->name('attendance-settings.update');

    // Attendance Reports & Export
    Route::get('/reports', [AttendanceController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export-excel', [AttendanceController::class, 'exportExcel'])->name('reports.export_excel');
    Route::put('/attendances/{attendance}', [AttendanceController::class, 'updateStatus'])->name('attendances.update_status');

    // School Holidays Management
    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

    // Schedules & Subjects Management
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [ScheduleController::class, 'storeSchedule'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroySchedule'])->name('schedules.destroy');
    Route::post('/subjects', [ScheduleController::class, 'storeSubject'])->name('subjects.store');
    Route::delete('/subjects/{subject}', [ScheduleController::class, 'destroySubject'])->name('subjects.destroy');

    // Classroom Subject Attendance
    Route::get('/absensi-mapel', [SubjectAttendanceController::class, 'index'])->name('subject_attendance.index');
    Route::post('/absensi-mapel/verifikasi', [SubjectAttendanceController::class, 'verify'])->name('subject_attendance.verify');
    Route::post('/absensi-mapel/status', [SubjectAttendanceController::class, 'updateStatus'])->name('subject_attendance.update_status');
});

require __DIR__.'/settings.php';

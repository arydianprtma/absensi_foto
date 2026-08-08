<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\StudentController;
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
});

require __DIR__.'/settings.php';

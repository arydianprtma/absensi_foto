<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingController extends Controller
{
    public function edit(): Response
    {
        $settings = AttendanceSetting::getSettings();

        return Inertia::render('settings/Attendance', [
            'settings' => [
                'check_in_start' => substr((string) $settings->check_in_start, 0, 5),
                'check_in_end' => substr((string) $settings->check_in_end, 0, 5),
                'check_out_start' => substr((string) $settings->check_out_start, 0, 5),
                'check_out_end' => substr((string) $settings->check_out_end, 0, 5),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'check_in_start' => 'required|date_format:H:i',
            'check_in_end' => 'required|date_format:H:i',
            'check_out_start' => 'required|date_format:H:i',
            'check_out_end' => 'required|date_format:H:i',
        ]);

        $settings = AttendanceSetting::getSettings();
        $settings->update([
            'check_in_start' => $validated['check_in_start'].':00',
            'check_in_end' => $validated['check_in_end'].':00',
            'check_out_start' => $validated['check_out_start'].':00',
            'check_out_end' => $validated['check_out_end'].':00',
        ]);

        return redirect()->back()->with('success', 'Pengaturan jam absensi berhasil diperbarui!');
    }
}

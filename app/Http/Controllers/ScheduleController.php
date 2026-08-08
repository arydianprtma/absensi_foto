<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    /**
     * Display schedule and subject management page.
     */
    public function index(): Response
    {
        $subjects = Subject::orderBy('code')->get();
        $schedules = Schedule::with('subject')
            ->orderBy('class_name')
            ->orderBy('start_time')
            ->get();

        $classes = Student::select('class_name')
            ->distinct()
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->toArray();

        return Inertia::render('Schedules/Index', [
            'subjects' => $subjects,
            'schedules' => $schedules,
            'classes' => $classes,
        ]);
    }

    /**
     * Store new Subject.
     */
    public function storeSubject(Request $request): RedirectResponse
    {
        if ($request->user() && $request->user()->role === 'teacher') {
            return back()->with('error', 'Hanya Admin / Staff TU yang dapat mengelola mata pelajaran.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        Subject::create($validated);

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    /**
     * Delete Subject.
     */
    public function destroySubject(Request $request, Subject $subject): RedirectResponse
    {
        if ($request->user() && $request->user()->role === 'teacher') {
            return back()->with('error', 'Hanya Admin / Staff TU yang dapat mengelola mata pelajaran.');
        }

        $subject->delete();

        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }

    /**
     * Store new Schedule.
     */
    public function storeSchedule(Request $request): RedirectResponse
    {
        if ($request->user() && $request->user()->role === 'teacher') {
            return back()->with('error', 'Hanya Admin / Staff TU yang dapat mengelola jadwal pelajaran.');
        }

        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_name' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'string'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
        ]);

        Schedule::create($validated);

        return back()->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    /**
     * Delete Schedule.
     */
    public function destroySchedule(Request $request, Schedule $schedule): RedirectResponse
    {
        if ($request->user() && $request->user()->role === 'teacher') {
            return back()->with('error', 'Hanya Admin / Staff TU yang dapat mengelola jadwal pelajaran.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
}

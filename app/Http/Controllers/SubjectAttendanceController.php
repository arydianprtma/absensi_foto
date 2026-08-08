<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectAttendance;
use App\Services\InsightFaceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubjectAttendanceController extends Controller
{
    public function __construct(protected InsightFaceService $insightFaceService) {}

    /**
     * Classroom Attendance Page per Subject.
     */
    public function index(Request $request): Response
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentDayEnglish = $now->format('l'); // Monday, Tuesday, etc.
        $currentTimeStr = $now->format('H:i:s');

        /** @var array<int, string> $classes */
        $classes = Student::select('class_name')
            ->distinct()
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->toArray();

        $selectedClass = (string) $request->query('class_name', $classes[0] ?? '');
        $selectedScheduleId = $request->query('schedule_id');

        // Fetch all schedules for current class
        $schedules = Schedule::with('subject')
            ->where('class_name', $selectedClass)
            ->get();

        // Detect active schedule for current time & day
        $activeSchedule = null;
        if ($selectedScheduleId && is_numeric($selectedScheduleId)) {
            $activeSchedule = Schedule::with('subject')->find((int) $selectedScheduleId);
        } else {
            /** @var Schedule|null $activeSchedule */
            $activeSchedule = Schedule::with('subject')
                ->where('class_name', $selectedClass)
                ->where(function ($query) use ($currentDayEnglish) {
                    $query->where('day_of_week', $currentDayEnglish)
                        ->orWhere('day_of_week', $this->mapDayToIndonesian($currentDayEnglish));
                })
                ->where('start_time', '<=', $currentTimeStr)
                ->where('end_time', '>=', $currentTimeStr)
                ->first();

            if (! $activeSchedule && $schedules->isNotEmpty()) {
                $activeSchedule = $schedules->first();
            }
        }

        // Students in current class
        $students = Student::where('class_name', $selectedClass)
            ->orderBy('name')
            ->get()
            ->map(function (Student $s) {
                return [
                    'id' => $s->id,
                    'nisn' => $s->nisn,
                    'rfid_uid' => $s->rfid_uid,
                    'nis' => $s->nis,
                    'name' => $s->name,
                    'class_name' => $s->class_name,
                    'photo_url' => $s->photo_path ? Storage::url($s->photo_path) : null,
                ];
            });

        // Today's subject attendance logs for active schedule
        $todayAttendances = [];
        if ($activeSchedule instanceof Schedule) {
            $todayAttendances = SubjectAttendance::with('student')
                ->where('schedule_id', $activeSchedule->id)
                ->where('date', $today)
                ->get()
                ->keyBy('student_id')
                ->toArray();
        }

        return Inertia::render('SubjectAttendance/Index', [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'schedules' => $schedules,
            'activeSchedule' => $activeSchedule,
            'students' => $students,
            'todayAttendances' => $todayAttendances,
            'todayDate' => $today,
        ]);
    }

    /**
     * Verify student face for Classroom Subject Attendance.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'image' => ['required', 'string'],
        ]);

        /** @var Schedule $schedule */
        $schedule = Schedule::with('subject')->findOrFail($validated['schedule_id']);
        $subjectName = $schedule->subject->name ?? 'Mata Pelajaran';

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        // Fetch students in the schedule's class with embeddings
        $students = Student::where('class_name', $schedule->class_name)
            ->whereNotNull('face_embedding')
            ->get();

        $studentsPayload = $students->map(function (Student $s) {
            $embedding = is_array($s->face_embedding) ? $s->face_embedding : null;

            return [
                'id' => $s->id,
                'name' => $s->name,
                'nisn' => $s->nisn,
                'face_embedding' => $embedding,
            ];
        })->toArray();

        // 1-to-N Identify Face against Python InsightFace API
        $identifyResult = $this->insightFaceService->identifyFace($validated['image'], $studentsPayload, 0.50);

        if (! ($identifyResult['matched'] ?? false)) {
            return response()->json([
                'success' => false,
                'is_spoof' => $identifyResult['is_spoof'] ?? false,
                'message' => $identifyResult['message'] ?? 'Wajah tidak terdeteksi atau kemiripan di bawah 50.0%.',
                'similarity' => $identifyResult['similarity'] ?? 0,
            ], 200);
        }

        $matchedStudentId = $identifyResult['student_id'] ?? null;
        $highestSimilarity = (float) ($identifyResult['similarity'] ?? 0);

        /** @var Student|null $student */
        $student = Student::find($matchedStudentId);

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan di database.',
            ], 200);
        }

        // Step 3: Check if already attended for this schedule today
        $existing = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'already_attended' => true,
                'message' => "Siswa {$student->name} SUDAH melakukan presensi mapel {$subjectName} hari ini jam {$existing->check_in_time} WIB.",
                'student' => [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'class_name' => $student->class_name,
                ],
                'attendance' => [
                    'check_in_time' => $existing->check_in_time,
                    'status' => ucfirst($existing->status),
                ],
            ]);
        }

        // Save photo snapshot
        $base64Data = (string) preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']);
        $imageBytes = base64_decode($base64Data);
        $studentFolder = Str::slug($student->name, '_');
        $fileName = 'subject_attendances/'.$today.'/'.$schedule->id.'/'.$studentFolder.'/'.time().'.jpg';

        Storage::disk('public')->put($fileName, $imageBytes);

        // Calculate status (if check-in > start_time + 15 mins -> terlambat)
        $startTime = Carbon::createFromTimeString($schedule->start_time);
        $lateThresholdTime = (clone $startTime)->addMinutes(15)->format('H:i:s');
        $status = $currentTime > $lateThresholdTime ? 'terlambat' : 'hadir';

        $attendance = SubjectAttendance::create([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'date' => $today,
            'check_in_time' => $currentTime,
            'status' => $status,
            'verified_by' => 'ai_face',
            'photo_path' => $fileName,
            'similarity_score' => $highestSimilarity,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Presensi Mapel {$subjectName} BERHASIL! {$student->name} ({$student->nisn}).",
            'student' => [
                'name' => $student->name,
                'nisn' => $student->nisn,
                'class_name' => $student->class_name,
            ],
            'attendance' => [
                'id' => $attendance->id,
                'check_in_time' => $currentTime,
                'status' => ucfirst($status),
                'similarity_percentage' => round($highestSimilarity * 100, 1),
                'photo_url' => Storage::url($fileName),
            ],
        ]);
    }

    /**
     * Teacher manual update for student subject attendance status.
     */
    public function updateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'student_id' => ['required', 'exists:students,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:hadir,terlambat,izin,sakit,alpa'],
        ]);

        $now = Carbon::now('Asia/Jakarta')->format('H:i:s');

        SubjectAttendance::updateOrCreate(
            [
                'schedule_id' => $validated['schedule_id'],
                'student_id' => $validated['student_id'],
                'date' => $validated['date'],
            ],
            [
                'check_in_time' => $now,
                'status' => $validated['status'],
                'verified_by' => 'manual_teacher',
            ]
        );

        return back()->with('success', 'Status presensi mapel siswa berhasil diperbarui.');
    }

    private function mapDayToIndonesian(string $englishDay): string
    {
        return match ($englishDay) {
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            default => $englishDay,
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Student;
use App\Services\InsightFaceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(protected InsightFaceService $insightFaceService) {}

    /**
     * Public / Student Webcam Attendance Page.
     */
    public function index(): Response
    {
        $students = Student::select('id', 'nisn', 'name', 'class_name', 'photo_path')
            ->whereNotNull('face_embedding')
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'class_name' => $student->class_name,
                    'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
                ];
            });

        // Get today's attendance logs in WIB (Asia/Jakarta)
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $todayLogs = Attendance::with('student')
            ->where('date', $today)
            ->latest('check_in_time')
            ->get()
            ->map(function ($att) {
                return [
                    'id' => $att->id,
                    'student_name' => $att->student?->name ?? 'Siswa',
                    'nisn' => $att->student?->nisn ?? '-',
                    'class_name' => $att->student?->class_name ?? '-',
                    'check_in_time' => $att->check_in_time,
                    'check_out_time' => $att->check_out_time,
                    'status' => $att->status,
                    'similarity_score' => round($att->similarity_score * 100, 1),
                    'photo_url' => $att->photo_path ? Storage::url($att->photo_path) : null,
                ];
            });

        $settings = AttendanceSetting::getSettings();

        return Inertia::render('Absensi/Index', [
            'students' => $students,
            'todayLogs' => $todayLogs,
            'settings' => [
                'check_in_start' => substr((string) $settings->check_in_start, 0, 5),
                'check_in_end' => substr((string) $settings->check_in_end, 0, 5),
                'check_out_start' => substr((string) $settings->check_out_start, 0, 5),
                'check_out_end' => substr((string) $settings->check_out_end, 0, 5),
            ],
        ]);
    }

    /**
     * Handle Face Verification by Student ID.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'image' => 'required|string',
        ]);

        $student = Student::findOrFail($request->student_id);

        if (empty($student->face_embedding)) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa belum memiliki data wajah terdaftar. Hubungi Admin/Guru.',
            ], 200);
        }

        $cleanEmbedding = array_values(array_map('floatval', (array) $student->face_embedding));

        // Threshold diset minimal 50% (0.50)
        $verificationResult = $this->insightFaceService->verifyFace(
            $request->image,
            $cleanEmbedding,
            0.50
        );

        if (! ($verificationResult['matched'] ?? false)) {
            return response()->json([
                'success' => false,
                'matched' => false,
                'similarity' => $verificationResult['similarity'] ?? 0,
                'message' => $verificationResult['message'] ?? 'Verifikasi wajah tidak sah. Kemiripan wajah di bawah 50.0%.',
            ], 200);
        }

        return $this->processAttendance($student, $request->image, $verificationResult['similarity'] ?? 0.0);
    }

    /**
     * Automatic 1-to-N Face Recognition & Verification without selecting name/NISN first.
     */
    public function autoVerify(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        $students = Student::whereNotNull('face_embedding')
            ->get()
            ->filter(fn ($s) => ! empty($s->face_embedding))
            ->map(function ($s) {
                $raw = is_array($s->face_embedding) ? $s->face_embedding : json_decode($s->face_embedding, true);

                return [
                    'id' => (int) $s->id,
                    'name' => (string) $s->name,
                    'embedding' => array_values(array_map('floatval', (array) $raw)),
                ];
            })
            ->values()
            ->toArray();

        if (empty($students)) {
            return response()->json([
                'success' => false,
                'matched' => false,
                'message' => 'Belum ada foto wajah siswa terdaftar di database. Silakan daftarkan foto wajah siswa terlebih dahulu di menu Data Siswa.',
            ], 200);
        }

        // Threshold diset minimal 50% (0.50)
        $result = $this->insightFaceService->identifyFace($request->image, $students, 0.50);

        if (! ($result['matched'] ?? false)) {
            return response()->json([
                'success' => false,
                'matched' => false,
                'similarity' => $result['similarity'] ?? 0,
                'message' => $result['message'] ?? 'Verifikasi wajah tidak sah. Kemiripan wajah di bawah 50.0%.',
            ], 200);
        }

        $student = Student::findOrFail($result['student_id']);

        return $this->processAttendance($student, $request->image, $result['similarity'] ?? 0.0);
    }

    /**
     * Shared logic to record attendance (Masuk / Pulang) for a recognized student.
     */
    protected function processAttendance(Student $student, string $imageBase64, float $similarity = 0.0): JsonResponse
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $settings = AttendanceSetting::getSettings();
        $isCheckOutTime = $currentTime >= $settings->check_out_start;

        $existingAttendance = Attendance::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        // Check-Out Logic (Absen Pulang)
        if ($isCheckOutTime) {
            if ($existingAttendance && $existingAttendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'already_attended' => true,
                    'message' => "{$student->name} ({$student->nisn}) SUDAH melakukan absensi PULANG hari ini pada jam {$existingAttendance->check_out_time} WIB.",
                    'student' => [
                        'name' => $student->name,
                        'nisn' => $student->nisn,
                        'class_name' => $student->class_name,
                    ],
                    'attendance' => [
                        'type' => 'pulang',
                        'check_out_time' => $existingAttendance->check_out_time,
                        'status' => ucfirst($existingAttendance->status),
                    ],
                ]);
            }

            // Save snapshot
            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64);
            $imageBytes = base64_decode($base64Data);
            $fileName = 'attendances/'.$today.'/checkout-'.Str::slug($student->nisn.'-'.$student->name).'-'.time().'.jpg';
            Storage::disk('public')->put($fileName, $imageBytes);

            if ($existingAttendance) {
                $existingAttendance->update([
                    'check_out_time' => $currentTime,
                ]);
                $attendance = $existingAttendance;
            } else {
                $attendance = Attendance::create([
                    'student_id' => $student->id,
                    'date' => $today,
                    'check_in_time' => $currentTime,
                    'check_out_time' => $currentTime,
                    'status' => 'hadir',
                    'photo_path' => $fileName,
                    'similarity_score' => $similarity,
                ]);
            }

            return response()->json([
                'success' => true,
                'matched' => true,
                'message' => "Absensi PULANG BERHASIL! Selamat jalan, {$student->name} ({$student->nisn}).",
                'student' => [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'class_name' => $student->class_name,
                    'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
                ],
                'attendance' => [
                    'id' => $attendance->id,
                    'type' => 'pulang',
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $currentTime,
                    'status' => ucfirst($attendance->status),
                    'similarity_percentage' => round($similarity * 100, 1),
                    'photo_url' => Storage::url($fileName),
                ],
            ]);
        }

        // Check-In Logic (Absen Masuk)
        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'already_attended' => true,
                'message' => "{$student->name} ({$student->nisn}) SUDAH melakukan absensi MASUK hari ini pada jam {$existingAttendance->check_in_time} WIB.",
                'student' => [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'class_name' => $student->class_name,
                ],
                'attendance' => [
                    'type' => 'masuk',
                    'check_in_time' => $existingAttendance->check_in_time,
                    'status' => ucfirst($existingAttendance->status),
                ],
            ]);
        }

        // Save attendance photo snapshot
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64);
        $imageBytes = base64_decode($base64Data);
        $fileName = 'attendances/'.$today.'/checkin-'.Str::slug($student->nisn.'-'.$student->name).'-'.time().'.jpg';

        Storage::disk('public')->put($fileName, $imageBytes);

        $lateThreshold = substr((string) $settings->check_in_end, 0, 5);
        $status = $now->format('H:i') > $lateThreshold ? 'terlambat' : 'hadir';

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'date' => $today,
            'check_in_time' => $currentTime,
            'status' => $status,
            'photo_path' => $fileName,
            'similarity_score' => $similarity,
        ]);

        return response()->json([
            'success' => true,
            'matched' => true,
            'message' => "Absensi MASUK BERHASIL! Selamat datang, {$student->name} ({$student->nisn}).",
            'student' => [
                'name' => $student->name,
                'nisn' => $student->nisn,
                'class_name' => $student->class_name,
                'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
            ],
            'attendance' => [
                'id' => $attendance->id,
                'type' => 'masuk',
                'check_in_time' => $currentTime,
                'status' => ucfirst($status),
                'similarity_percentage' => round($similarity * 100, 1),
                'photo_url' => Storage::url($fileName),
            ],
        ]);
    }

    /**
     * Dashboard Overview & Statistics.
     */
    public function dashboard(): Response
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        $totalStudents = Student::count();
        $registeredFacesCount = Student::whereNotNull('face_embedding')->count();

        $todayAttendances = Attendance::with('student')
            ->where('date', $today)
            ->latest('check_in_time')
            ->get();

        $presentTodayCount = $todayAttendances->where('status', 'hadir')->count();
        $lateTodayCount = $todayAttendances->where('status', 'terlambat')->count();
        $totalAttendedToday = $todayAttendances->count();
        $absentTodayCount = max(0, $totalStudents - $totalAttendedToday);

        $recentLogs = $todayAttendances->take(10)->map(function ($att) {
            return [
                'id' => $att->id,
                'student_name' => $att->student?->name ?? 'Siswa',
                'nisn' => $att->student?->nisn ?? '-',
                'class_name' => $att->student?->class_name ?? '-',
                'check_in_time' => $att->check_in_time,
                'check_out_time' => $att->check_out_time,
                'status' => $att->status,
                'similarity_percentage' => round($att->similarity_score * 100, 1),
                'photo_url' => $att->photo_path ? Storage::url($att->photo_path) : null,
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'registered_faces' => $registeredFacesCount,
                'present_today' => $presentTodayCount,
                'late_today' => $lateTodayCount,
                'absent_today' => $absentTodayCount,
                'attendance_rate' => $totalStudents > 0 ? round(($totalAttendedToday / $totalStudents) * 100, 1) : 0,
            ],
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Attendance Report Page.
     */
    public function reports(Request $request): Response
    {
        $date = $request->query('date', Carbon::now('Asia/Jakarta')->format('Y-m-d'));
        $className = $request->query('class_name', 'all');
        $status = $request->query('status', 'all');

        $query = Attendance::with('student')->where('date', $date);

        if ($className !== 'all') {
            $query->whereHas('student', function ($q) use ($className) {
                $q->where('class_name', $className);
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reports = $query->latest('check_in_time')->get()->map(function ($att) {
            return [
                'id' => $att->id,
                'date' => $att->date->format('Y-m-d'),
                'student_name' => $att->student?->name ?? '-',
                'nisn' => $att->student?->nisn ?? '-',
                'class_name' => $att->student?->class_name ?? '-',
                'check_in_time' => $att->check_in_time,
                'check_out_time' => $att->check_out_time,
                'status' => $att->status,
                'similarity_percentage' => round($att->similarity_score * 100, 1),
                'photo_url' => $att->photo_path ? Storage::url($att->photo_path) : null,
            ];
        });

        $classes = Student::distinct()->pluck('class_name')->filter()->values();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'classes' => $classes,
            'filters' => [
                'date' => $date,
                'class_name' => $className,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Update Attendance Status by Admin.
     */
    public function updateStatus(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:hadir,terlambat,izin,sakit,alpa',
        ]);

        $attendance->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Status absensi berhasil diperbarui!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\InsightFaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(protected InsightFaceService $insightFaceService) {}

    public function index(): Response
    {
        $students = Student::withCount('attendances')
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'nisn' => $student->nisn,
                    'rfid_uid' => $student->rfid_uid,
                    'nis' => $student->nis,
                    'name' => $student->name,
                    'class_name' => $student->class_name,
                    'address' => $student->address,
                    'school_origin' => $student->school_origin,
                    'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
                    'has_face_registered' => ! empty($student->face_embedding),
                    'total_attendances' => $student->attendances_count,
                    'created_at' => $student->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Students/Index', [
            'students' => $students,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Students/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => 'required|string|unique:students,nisn',
            'rfid_uid' => 'nullable|string|unique:students,rfid_uid',
            'nis' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'class_name' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'school_origin' => 'nullable|string|max:255',
            'photo_base64' => 'required|string',
        ]);

        // Extract embedding via InsightFace Service
        $extractResult = $this->insightFaceService->extractEmbedding($validated['photo_base64']);

        if (! ($extractResult['success'] ?? false)) {
            return back()->withErrors([
                'photo_base64' => $extractResult['message'] ?? 'Wajah tidak terdeteksi pada foto referensi yang diberikan.',
            ])->withInput();
        }

        // Save image to storage
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $validated['photo_base64']);
        $imageBytes = base64_decode($base64Data);
        $fileName = 'students/'.Str::slug($validated['nisn'].'-'.$validated['name']).'-'.time().'.jpg';

        Storage::disk('public')->put($fileName, $imageBytes);

        Student::create([
            'nisn' => $validated['nisn'],
            'rfid_uid' => $validated['rfid_uid'] ?? null,
            'nis' => $validated['nis'] ?? null,
            'name' => $validated['name'],
            'class_name' => $validated['class_name'],
            'address' => $validated['address'] ?? null,
            'school_origin' => $validated['school_origin'] ?? null,
            'photo_path' => $fileName,
            'face_embedding' => $extractResult['embedding'],
        ]);

        return redirect()->route('students.index')->with('success', 'Siswa dan Kartu RFID berhasil didaftarkan!');
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('Students/Edit', [
            'student' => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'rfid_uid' => $student->rfid_uid,
                'nis' => $student->nis,
                'name' => $student->name,
                'class_name' => $student->class_name,
                'address' => $student->address,
                'school_origin' => $student->school_origin,
                'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
                'has_face_registered' => ! empty($student->face_embedding),
            ],
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => 'required|string|unique:students,nisn,'.$student->id,
            'rfid_uid' => 'nullable|string|unique:students,rfid_uid,'.$student->id,
            'nis' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'class_name' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'school_origin' => 'nullable|string|max:255',
            'photo_base64' => 'nullable|string',
        ]);

        $updateData = [
            'nisn' => $validated['nisn'],
            'rfid_uid' => $validated['rfid_uid'] ?? null,
            'nis' => $validated['nis'] ?? null,
            'name' => $validated['name'],
            'class_name' => $validated['class_name'],
            'address' => $validated['address'] ?? null,
            'school_origin' => $validated['school_origin'] ?? null,
        ];

        // If a new face photo is provided, extract embedding and replace old photo
        if (! empty($validated['photo_base64'])) {
            $extractResult = $this->insightFaceService->extractEmbedding($validated['photo_base64']);

            if (! ($extractResult['success'] ?? false)) {
                return back()->withErrors([
                    'photo_base64' => $extractResult['message'] ?? 'Wajah tidak terdeteksi pada foto baru yang diberikan.',
                ])->withInput();
            }

            // Delete old photo if exists
            if ($student->photo_path && Storage::disk('public')->exists($student->photo_path)) {
                Storage::disk('public')->delete($student->photo_path);
            }

            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $validated['photo_base64']);
            $imageBytes = base64_decode($base64Data);
            $fileName = 'students/'.Str::slug($validated['nisn'].'-'.$validated['name']).'-'.time().'.jpg';

            Storage::disk('public')->put($fileName, $imageBytes);

            $updateData['photo_path'] = $fileName;
            $updateData['face_embedding'] = $extractResult['embedding'];
        }

        $student->update($updateData);

        return redirect()->route('students.index')->with('success', 'Data siswa & RFID berhasil diperbarui!');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->photo_path && Storage::disk('public')->exists($student->photo_path)) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Printable Official RFID Student ID Card View.
     */
    public function card(Student $student): Response
    {
        return Inertia::render('Students/Card', [
            'student' => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'rfid_uid' => $student->rfid_uid,
                'nis' => $student->nis,
                'name' => $student->name,
                'class_name' => $student->class_name,
                'address' => $student->address,
                'school_origin' => $student->school_origin,
                'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
            ],
        ]);
    }

    /**
     * Batch printable RFID PVC sticker sheet layout (5 students / 10 cards per A4 page).
     */
    public function batchCards(Request $request): Response
    {
        $selectedClass = $request->query('class_name');

        $query = Student::whereNotNull('rfid_uid')->where('rfid_uid', '!=', '');

        if (! empty($selectedClass)) {
            $query->where('class_name', $selectedClass);
        }

        $students = $query->orderBy('class_name')->orderBy('name')->get()->map(function ($student) {
            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'rfid_uid' => $student->rfid_uid,
                'nis' => $student->nis,
                'name' => $student->name,
                'class_name' => $student->class_name,
                'address' => $student->address,
                'school_origin' => $student->school_origin,
                'photo_url' => $student->photo_path ? Storage::url($student->photo_path) : null,
            ];
        });

        $classes = Student::select('class_name')->distinct()->orderBy('class_name')->pluck('class_name');

        return Inertia::render('Students/BatchCards', [
            'students' => $students,
            'classes' => $classes,
            'selectedClass' => $selectedClass ?? '',
        ]);
    }
}

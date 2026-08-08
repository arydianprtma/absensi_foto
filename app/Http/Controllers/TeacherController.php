<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Display teacher accounts management page.
     */
    public function index(): Response
    {
        $teachers = User::orderBy('name')
            ->get()
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'rfid_uid' => $u->rfid_uid,
                    'role' => $u->role ?? 'teacher',
                    'nip' => $u->nip,
                    'phone' => $u->phone,
                    'created_at' => $u->created_at ? $u->created_at->format('d M Y') : '-',
                ];
            });

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store new Teacher/Admin account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'rfid_uid' => ['nullable', 'string', 'max:100', 'unique:users,rfid_uid'],
            'nip' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['admin', 'teacher'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rfid_uid' => $validated['rfid_uid'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Akun guru berhasil dibuat.');
    }

    /**
     * Update Teacher/Admin account.
     */
    public function update(Request $request, User $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->id)],
            'rfid_uid' => ['nullable', 'string', 'max:100', Rule::unique('users', 'rfid_uid')->ignore($teacher->id)],
            'nip' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['admin', 'teacher'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rfid_uid' => $validated['rfid_uid'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $teacher->update($updateData);

        return back()->with('success', 'Data akun guru berhasil diperbarui.');
    }

    /**
     * Handle Quick RFID Card Login.
     */
    public function rfidLogin(Request $request): JsonResponse
    {
        $request->validate([
            'rfid_uid' => ['required', 'string'],
        ]);

        $rfidUid = trim($request->rfid_uid);

        $user = User::where('rfid_uid', $rfidUid)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => "Kartu ({$rfidUid}) belum terdaftar pada akun Guru atau Staf.",
            ], 200);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => "Login berhasil! Selamat datang, {$user->name}.",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Delete Teacher account.
     */
    public function destroy(Request $request, User $teacher): RedirectResponse
    {
        if ($request->user() && $request->user()->id === $teacher->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $teacher->delete();

        return back()->with('success', 'Akun guru berhasil dihapus.');
    }
}

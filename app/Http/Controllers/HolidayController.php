<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(): Response
    {
        $holidays = Holiday::orderBy('date', 'desc')->get()->map(function ($h) {
            return [
                'id' => $h->id,
                'date' => $h->date->format('Y-m-d'),
                'name' => $h->name,
                'description' => $h->description,
            ];
        });

        return Inertia::render('Holidays/Index', [
            'holidays' => $holidays,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Holiday::create($validated);

        return back()->with('success', 'Hari libur berhasil ditambahkan!');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Hari libur berhasil dihapus!');
    }
}

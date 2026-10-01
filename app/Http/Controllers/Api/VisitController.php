<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function index(): JsonResponse
    {
        $visits = Visit::with('patient')->latest('visited_at')->get();

        return response()->json(['data' => $visits]);
    }

    public function store(StoreVisitRequest $request): JsonResponse
    {
        $patient = Patient::where('no_rm', $request->validated('no_rm'))->firstOrFail();
        $visit = $patient->visits()->create(['visited_at' => now()]);

        return response()->json([
            'message' => 'Kunjungan berhasil dibuat.',
            'data' => [
                'visit' => $visit,
                'patient' => $patient->loadCount('visits'),
            ],
        ], 201);
    }
}

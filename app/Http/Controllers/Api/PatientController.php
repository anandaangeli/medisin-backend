<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index(): JsonResponse
    {
        $patients = Patient::withCount('visits')->orderBy('id')->get();

        return response()->json(['data' => $patients]);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = DB::transaction(fn () => Patient::create($request->validated()));

        return response()->json([
            'message' => 'Pasien berhasil didaftarkan.',
            'data' => $patient->loadCount('visits'),
        ], 201);
    }

    public function show(string $noRm): JsonResponse
    {
        $patient = Patient::withCount('visits')->where('no_rm', strtoupper($noRm))->first();

        if (! $patient) {
            return response()->json(['message' => 'Pasien dengan No Rekam Medik tersebut tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $patient]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeTypeRequest;
use App\Http\Requests\UpdatePracticeTypeRequest;
use App\Models\PracticeType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class PracticeTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PracticeType::class);
        return response()->json([
            'data' => PracticeType::orderBy('name')->get(),
        ]);
    }

    public function store(StorePracticeTypeRequest $request): JsonResponse
    {
        $practiceType = PracticeType::create($request->validated());
        return response()->json([
            'message' => 'Tipo pratica creato.',
            'data' => $practiceType,
        ], 201);
    }

    public function update(UpdatePracticeTypeRequest $request, PracticeType $practice_type): JsonResponse
    {
        $practice_type->update($request->validated());
        return response()->json([
            'message' => 'Tipo pratica aggiornato.',
            'data' => $practice_type,
        ]);
    }

    public function destroy(PracticeType $practice_type): JsonResponse
    {
        $this->authorize('delete', $practice_type);
        $practice_type->delete();
        return response()->json([
            'message' => 'Tipo pratica eliminato.',
        ]);
    }
}

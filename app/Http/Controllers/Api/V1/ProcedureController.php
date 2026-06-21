<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcedureRequest;
use App\Http\Requests\UpdateProcedureRequest;
use App\Models\Procedure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcedureController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('viewAny', Procedure::class)) {
            abort(403);
        }

        $procedures = Procedure::with('practiceType')->get();

        return response()->json($procedures);
    }

    public function store(StoreProcedureRequest $request): JsonResponse
    {
        $procedure = Procedure::create($request->validated());

        return response()->json([
            'message' => 'Procedure created.',
            'data'    => $procedure->load('practiceType'),
        ], 201);
    }

    public function show(Procedure $procedure): JsonResponse
    {
        $this->authorize('view', $procedure);

        $procedure->load('practiceType');
        $procedure->loadCount('practices');

        return response()->json([
            'data' => $procedure,
        ]);
    }

    public function update(UpdateProcedureRequest $request, Procedure $procedure): JsonResponse
    {
        $procedure->update($request->validated());

        return response()->json([
            'message' => 'Procedure updated.',
            'data'    => $procedure->fresh('practiceType'),
        ]);
    }

    public function destroy(Procedure $procedure): JsonResponse
    {
        $this->authorize('delete', $procedure);

        if ($procedure->practices()->exists()) {
            return response()->json([
                'message' => 'Cannot delete procedure with attached practices.',
            ], 409);
        }

        $procedure->delete();

        return response()->json([
            'message' => 'Procedure deleted.',
        ]);
    }
}

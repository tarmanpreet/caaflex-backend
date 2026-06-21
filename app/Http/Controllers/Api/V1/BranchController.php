<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        return response()->json([
            'data' => Branch::with('employees')
                ->select('id', 'name', 'address', 'city', 'province', 'postal_code', 'phone', 'vat_number', 'is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        return response()->json([
            'data' => $branch->load('employees'),
        ]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $this->authorize('create', Branch::class);

        $branch = Branch::create($request->validated());

        return response()->json([
            'message' => 'Filiale creata.',
            'data' => $branch,
        ], 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $branch->update($request->validated());

        return response()->json([
            'message' => 'Filiale aggiornata.',
            'data' => $branch,
        ]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return response()->json([
            'message' => 'Filiale eliminata.',
        ]);
    }

    /**
     * Get active branches for dropdowns (no employees relation).
     */
    public function active(): JsonResponse
    {
        return response()->json([
            'data' => Branch::active()
                ->select('id', 'name', 'address', 'city', 'province', 'postal_code', 'phone')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Assign/remove employees to/from a branch.
     */
    public function syncEmployees(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $branch->employees()->sync($request->user_ids);

        return response()->json([
            'message' => 'Employee assegnati alla filiale.',
            'data' => $branch->load('employees'),
        ]);
    }
}

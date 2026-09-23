<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Procedure\SyncProcedureDeadlineTemplatesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcedureRequest;
use App\Http\Requests\UpdateProcedureRequest;
use App\Models\Procedure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProcedureController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('viewAny', Procedure::class)) {
            abort(403);
        }

        $procedures = Procedure::query()
            ->with(['practiceType', 'deadlineTemplates'])
            ->when($request->filled('procedure_type_id'), fn ($query) => $query->where('procedure_type_id', $request->integer('procedure_type_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('default_notes', 'like', $search);
                });
            })
            ->orderBy('name')
            ->get();

        return response()->json($procedures);
    }

    public function store(StoreProcedureRequest $request, SyncProcedureDeadlineTemplatesAction $syncTemplates): JsonResponse
    {
        $data = $request->validated();
        $templates = Arr::pull($data, 'deadline_templates', []) ?? [];
        $procedure = DB::transaction(function () use ($data, $syncTemplates, $templates): Procedure {
            $procedure = Procedure::query()->create($data);
            $syncTemplates->execute($procedure, $templates);

            return $procedure;
        });

        return response()->json([
            'message' => 'Procedure created.',
            'data' => $procedure->load(['practiceType', 'deadlineTemplates']),
        ], 201);
    }

    public function show(Procedure $procedure): JsonResponse
    {
        $this->authorize('view', $procedure);

        $procedure->load(['practiceType', 'deadlineTemplates']);
        $procedure->loadCount('practices');

        return response()->json([
            'data' => $procedure,
        ]);
    }

    public function update(UpdateProcedureRequest $request, Procedure $procedure, SyncProcedureDeadlineTemplatesAction $syncTemplates): JsonResponse
    {
        $data = $request->validated();
        $shouldSyncTemplates = $request->has('deadline_templates');
        $templates = Arr::pull($data, 'deadline_templates', []) ?? [];

        DB::transaction(function () use ($data, $procedure, $shouldSyncTemplates, $syncTemplates, $templates): void {
            $procedure->update($data);

            if ($shouldSyncTemplates) {
                $syncTemplates->execute($procedure, $templates);
            }
        });

        return response()->json([
            'message' => 'Procedure updated.',
            'data' => $procedure->fresh(['practiceType', 'deadlineTemplates']),
        ]);
    }

    public function destroy(Procedure $procedure): JsonResponse
    {
        $this->authorize('delete', $procedure);

        $procedure->delete();

        return response()->json([
            'message' => 'Procedure deleted.',
        ]);
    }
}

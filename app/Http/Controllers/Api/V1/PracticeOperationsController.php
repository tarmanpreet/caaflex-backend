<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPracticesRequest;
use App\Http\Requests\RenewPracticeRequest;
use App\Models\Practice;
use App\Services\PracticeOperationsService;
use App\Services\PracticeWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeOperationsController extends Controller
{
    public function __construct(private PracticeOperationsService $operations) {}

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json(['data' => $this->operations->search($request->user(), $data['q'])]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['branch_id' => ['nullable', 'integer', 'exists:branches,id']]);

        return response()->json(['data' => $this->operations->board($request->user(), $data['branch_id'] ?? null)]);
    }

    public function bulk(BulkPracticesRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->operations->bulk($request->user(), $request->validated())]);
    }

    public function renew(RenewPracticeRequest $request, Practice $practice, PracticeWorkspaceService $workspace): JsonResponse
    {
        $renewal = $this->operations->renew($practice, $request->user(), $request->validated('reference_year'));

        return response()->json(['data' => $workspace->data($renewal, $request->user())], 201);
    }
}

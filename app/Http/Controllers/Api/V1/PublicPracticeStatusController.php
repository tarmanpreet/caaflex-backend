<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LookupPracticeStatusRequest;
use App\Models\Practice;
use Illuminate\Http\JsonResponse;

class PublicPracticeStatusController extends Controller
{
    public function __invoke(LookupPracticeStatusRequest $request): JsonResponse
    {
        $trackingCode = $request->validated('code');
        $practice = Practice::query()
            ->select(['tracking_code', 'status'])
            ->where('tracking_code', $trackingCode)
            ->first();

        if (! $practice) {
            return response()->json([
                'message' => 'Nessuna pratica trovata con questo codice. Controllalo e riprova.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => [
                'code' => $practice->tracking_code,
                'status' => $practice->status,
            ],
        ]);
    }
}

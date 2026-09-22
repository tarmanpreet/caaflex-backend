<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\BuildDashboardDataAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildDashboardDataAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute($request->user()),
        ]);
    }
}

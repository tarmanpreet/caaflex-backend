<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\BuildDashboardNoticesAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardNoticeController extends Controller
{
    public function index(BuildDashboardNoticesAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute(auth()->user()),
        ]);
    }
}
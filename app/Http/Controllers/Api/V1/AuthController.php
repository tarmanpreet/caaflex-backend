<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $roleName = $user->getRoleNames()->first() ?? 'employee';
        $role = $roleName === 'cliente' ? 'citizenapp' : 'adminapp';

        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'role'      => $role,
            'role_name' => $roleName,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}

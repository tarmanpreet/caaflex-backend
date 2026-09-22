<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Spatie\Permission\Models\Permission;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->loadMissing(['roles:id,name', 'permissions:id,name']);
        $roles = $user->getRoleNames()->values();
        $roleName = $roles->first() ?? 'employee';
        $role = $roleName === 'cliente' ? 'citizenapp' : 'adminapp';
        $permissionNames = $user->getAllPermissions()->pluck('name')->sort()->values();
        $capabilities = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->reduce(function (array $capabilities, string $permission) use ($permissionNames): array {
                data_set($capabilities, $permission, $permissionNames->contains($permission));

                return $capabilities;
            }, []);
        $branches = Branch::query()
            ->whereIn('id', $user->accessibleBranchIds())
            ->select(['id', 'parent_id', 'name', 'city', 'province', 'is_active'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'role_name' => $roleName,
            'roles' => $roles,
            'permissions' => $permissionNames,
            'capabilities' => $capabilities,
            'branches' => $branches,
            'branding' => [
                'name' => config('branding.name'),
                'logo_url' => config('branding.logo_url'),
                'mark_url' => config('branding.mark_url'),
                'favicon_url' => config('branding.favicon_url'),
            ],
            'realtime' => [
                'enabled' => config('broadcasting.default') === 'reverb'
                    && filled(config('broadcasting.connections.reverb.key')),
                'broadcaster' => 'reverb',
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => (int) config('broadcasting.connections.reverb.options.port', 443),
                'scheme' => config('broadcasting.connections.reverb.options.scheme', 'https'),
            ],
            'features' => config('mobile.features'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->token();

        abort_unless($token instanceof AccessToken && isset($token->oauth_access_token_id), 401);

        $token->refreshToken?->revoke();
        $token->revoke();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}

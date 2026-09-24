<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MobileSyncGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $operationId = $request->header('X-Operation-Id');
        if (! $operationId) {
            return $next($request);
        }
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $operationId)) {
            return response()->json(['message' => 'Invalid operation ID.'], 422);
        }

        $userId = $request->user()->id;

        return Cache::lock("mobile-sync:{$userId}:{$operationId}", 30)->block(10, function () use ($request, $next, $userId, $operationId): Response {
            $previous = DB::table('mobile_sync_operations')->where('user_id', $userId)->where('operation_id', $operationId)->first();
            if ($previous) {
                return response($previous->response_body, $previous->http_status, ['Content-Type' => 'application/json']);
            }

            $version = $request->header('If-Match');
            $record = $this->versionedRecord($request);
            $canViewRecord = $record && ($request->user()->can('view', $record) || $request->user()->can('viewAny', $record::class));
            if ($version && $canViewRecord && $record->updated_at?->toISOString() !== $version) {
                return response()->json([
                    'message' => 'This record changed on the server.',
                    'current' => $record->fresh(),
                ], 409);
            }

            $response = $next($request);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && method_exists($response, 'getContent')) {
                DB::table('mobile_sync_operations')->insert([
                    'user_id' => $userId,
                    'operation_id' => $operationId,
                    'http_status' => $response->getStatusCode(),
                    'response_body' => $response->getContent() ?: '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $response;
        });
    }

    private function versionedRecord(Request $request): ?Model
    {
        foreach (array_reverse($request->route()->parameters()) as $parameter) {
            if ($parameter instanceof Model && $parameter->usesTimestamps()) {
                return $parameter;
            }
        }

        return null;
    }
}

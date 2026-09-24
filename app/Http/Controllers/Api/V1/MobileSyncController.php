<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AutoConfirmSlot;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
        ]);
        $after = (int) ($validated['after'] ?? 0);
        $changes = DB::table('mobile_sync_changes')->where('id', '>', $after)->orderBy('id')->limit(200)->get();
        $visible = $changes->filter(function ($change) use ($request): bool {
            $class = match ($change->resource) {
                'clients' => ClientProfile::class,
                'practices' => Practice::class,
                'appointments' => Appointment::class,
                'procedures' => Procedure::class,
                'practice-types' => PracticeType::class,
                'branches' => Branch::class,
                'users' => User::class,
                'auto-confirm-slots' => AutoConfirmSlot::class,
                default => null,
            };
            if (! $class) {
                return false;
            }
            if ($change->resource === 'auto-confirm-slots') {
                return $request->user()->hasPermissionTo('auto-confirm-slots.manage');
            }
            if ($change->deleted) {
                if ($change->branch_id && ! $request->user()->canAccessBranchId($change->branch_id)) {
                    return false;
                }
                if ($change->resource === 'clients' && $change->owner_user_id === $request->user()->id) {
                    return true;
                }

                return $request->user()->can('viewAny', $class);
            }
            $model = $class::find($change->resource_id);

            if (in_array($change->resource, ['practice-types', 'branches'], true)) {
                return $model && $request->user()->can('viewAny', $class);
            }

            return $model && $request->user()->can('view', $model);
        })->values()->map(fn ($change) => [
            'id' => $change->id,
            'resource' => $change->resource,
            'resource_id' => $change->resource_id,
            'deleted' => (bool) $change->deleted,
            'version' => $change->record_updated_at,
        ]);

        return response()->json([
            'data' => $visible,
            'cursor' => $changes->last()->id ?? $after,
            'has_more' => $changes->count() === 200,
        ]);
    }
}

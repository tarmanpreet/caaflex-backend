<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\AutoConfirmSlot;
use App\Models\Branch;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\PracticeDocument;
use App\Models\PracticeNote;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use App\Models\UserAvailability;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MobileSyncObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void
    {
        $this->record($model, false);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, true);
    }

    private function record(Model $model, bool $deleted): void
    {
        [$resource, $resourceId] = match (true) {
            $model instanceof ClientProfile => ['clients', $model->getKey()],
            $model instanceof Practice => ['practices', $model->getKey()],
            $model instanceof Appointment => ['appointments', $model->getKey()],
            $model instanceof Procedure => ['procedures', $model->getKey()],
            $model instanceof PracticeType => ['practice-types', $model->getKey()],
            $model instanceof Branch => ['branches', $model->getKey()],
            $model instanceof User => ['users', $model->getKey()],
            $model instanceof AutoConfirmSlot => ['auto-confirm-slots', $model->getKey()],
            $model instanceof UserAvailability => ['users', $model->user_id],
            $model instanceof ClientDocument => ['clients', $model->client_profile_id],
            $model instanceof PracticeDocument, $model instanceof PracticeDeadline, $model instanceof PracticeNote => ['practices', $model->practice_id],
            default => [null, null],
        };
        if (! $resource) {
            return;
        }
        $parent = match (true) {
            $model instanceof ClientDocument => ClientProfile::find($model->client_profile_id),
            $model instanceof PracticeDocument, $model instanceof PracticeDeadline, $model instanceof PracticeNote => Practice::find($model->practice_id),
            default => null,
        };
        $scoped = $parent ?? $model;
        DB::table('mobile_sync_changes')->insert([
            'resource' => $resource,
            'resource_id' => $resourceId,
            'branch_id' => $scoped->branch_id ?? null,
            'owner_user_id' => $scoped instanceof ClientProfile ? $scoped->user_id : null,
            'deleted' => $deleted,
            'record_updated_at' => $model->updated_at,
            'created_at' => now(),
        ]);
    }
}

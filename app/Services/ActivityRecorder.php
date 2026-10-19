<?php

namespace App\Services;

use App\Models\ActivityEntry;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityRecorder
{
    public function record(?User $actor, Model $model, string $action, array $metadata = []): void
    {
        if (! $actor) {
            return;
        }
        $practice = $model instanceof Practice ? $model : ($model instanceof PracticeDocument ? $model->practice : null);
        $clientId = $model instanceof ClientProfile ? $model->id : ($model instanceof ClientDocument ? $model->client_profile_id : $practice?->client_profile_id);
        ActivityEntry::query()->create(['user_id' => $actor->id, 'practice_id' => $practice?->exists ? $practice->id : null, 'client_profile_id' => $model instanceof ClientProfile && ! $model->exists ? null : $clientId, 'action' => $action, 'metadata' => ['resource' => class_basename($model), 'resource_id' => $model->id] + $metadata]);
    }
}

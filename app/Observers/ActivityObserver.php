<?php

namespace App\Observers;

use App\Services\ActivityRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityObserver
{
    public function __construct(private ActivityRecorder $recorder) {}

    public function created(Model $model): void
    {
        $this->recorder->record(Auth::user(), $model, $this->resource($model).'.created');
    }

    public function updated(Model $model): void
    {
        $this->recorder->record(Auth::user(), $model, $this->resource($model).'.updated', ['fields' => array_values(array_diff(array_keys($model->getChanges()), ['updated_at']))]);
    }

    public function deleted(Model $model): void
    {
        $this->recorder->record(Auth::user(), $model, $this->resource($model).'.deleted');
    }

    private function resource(Model $model): string
    {
        return match (class_basename($model)) {
            'Practice' => 'practice','ClientProfile' => 'client',default => 'document'
        };
    }
}

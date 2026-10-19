<?php

namespace App\Services;

use App\Actions\Practice\StorePracticeAction;
use App\Actions\Practice\UpdatePracticeAction;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeDocumentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PracticeOperationsService
{
    public function __construct(private PracticeAccess $access, private StorePracticeAction $store, private UpdatePracticeAction $update, private PracticeWorkspaceService $workspace) {}

    public function renew(Practice $source, User $actor, int $year): Practice
    {
        abort_unless($this->access->staff($actor, $source) && $actor->can('create', Practice::class), 403);

        return DB::transaction(function () use ($source, $actor, $year) {
            $originId = $source->renewal_of_id ?? $source->id;
            Practice::query()->whereKey($originId)->lockForUpdate()->firstOrFail();
            $source = $source->fresh();
            if (Practice::query()->where('renewal_of_id', $originId)->where('reference_year', $year)->exists()) {
                throw ValidationException::withMessages(['reference_year' => 'Esiste già un rinnovo per questo anno.']);
            }
            $ids = $source->assignedUsers()->where('is_active', true)->get()->filter(fn ($user) => $user->canAccessBranchId($source->branch_id))->pluck('id')->all();
            if (! $actor->can('assign', $source)) {
                $ids = in_array($actor->id, $ids, true) ? [$actor->id] : [];
            }
            $practice = $this->store->execute($source->only(['client_profile_id', 'practice_type_id', 'type', 'procedure_id', 'branch_id']) + ['renewal_of_id' => $originId, 'reference_year' => $year, 'status' => 'nuova', 'notes' => null, 'user_ids' => $ids], $actor->id);
            foreach (PracticeDocumentRequest::query()->where('practice_id', $source->id)->get() as $request) {
                PracticeDocumentRequest::query()->create($request->only(['title', 'instructions']) + ['practice_id' => $practice->id, 'requested_by' => $actor->id]);
            }
            $this->workspace->audit($source, $actor, 'practice.renewed', ['renewal_id' => $practice->id, 'reference_year' => $year]);
            $this->workspace->audit($practice, $actor, 'practice.created_from_renewal', ['source_id' => $source->id]);

            return $practice;
        });
    }

    public function bulk(User $actor, array $data): array
    {
        return DB::transaction(function () use ($actor, $data) {
            $practices = Practice::query()->whereKey($data['ids'])->orderBy('id')->lockForUpdate()->get();
            abort_unless($practices->count() === count($data['ids']), 404);
            $users = $data['action'] === 'assign' ? User::query()->whereKey($data['user_ids'])->get() : collect();
            foreach ($practices as $practice) {
                abort_unless($this->access->staff($actor, $practice) && $actor->can($data['action'] === 'assign' ? 'assign' : 'update', $practice), 403);
                foreach ($users as $user) {
                    if (! $user->is_active || ! $user->canAccessBranchId($practice->branch_id)) {
                        throw ValidationException::withMessages(['user_ids' => 'Un operatore non può accedere a tutte le filiali selezionate.']);
                    }
                }
            }
            foreach ($practices as $practice) {
                $this->update->execute($data['action'] === 'assign' ? ['user_ids' => $data['user_ids']] : ['status' => $data['status']], $practice, $actor->id);
                $this->workspace->audit($practice, $actor, 'practice.bulk_'.$data['action']);
            }

            return ['count' => $practices->count(), 'ids' => $practices->pluck('id')];
        });
    }

    public function search(User $actor, string $term): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $like = '%'.addcslashes($term, '%_\\').'%';
        $practices = $this->access->query($actor);
        $staffClients = ClientProfile::query();
        if (! $actor->can('clients.view-any')) {
            $staffClients->whereRaw('1=0');
        } elseif (\App\Models\Branch::query()->exists()) {
            $staffClients->whereIn('branch_id', $actor->accessibleBranchIds());
        }
        $fiscalMatches = function ($query) use ($actor, $like, $staffClients) {
            $query->where('fiscal_code', 'like', $like)->where(function ($permitted) use ($actor, $staffClients) {
                $permitted->whereIn('id', (clone $staffClients)->select('client_profiles.id'));
                if ($actor->can('clients.view-own')) {
                    $permitted->orWhere('user_id', $actor->id);
                }
            });
        };
        $results = (clone $practices)->where(fn ($q) => $q->where('type', 'like', $like)->orWhere('tracking_code', 'like', $like)->orWhereHas('client', fn ($client) => $client->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere($fiscalMatches)))->with('client:id,first_name,last_name')->latest('id')->limit(10)->get()->map(fn ($p) => ['resource' => 'practice', 'id' => $p->id, 'title' => $p->type.' · '.$p->tracking_code, 'subtitle' => $p->client->first_name.' '.$p->client->last_name])->all();
        $clients = ClientProfile::query()->where(function ($q) use ($actor, $staffClients) {
            $q->whereIn('id', (clone $staffClients)->select('client_profiles.id'))->orWhereIn('id', \App\Models\ClientDelegation::activeFor($actor)->select('client_profile_id'));
            if ($actor->can('clients.view-own')) {
                $q->orWhere('user_id', $actor->id);
            }
        })->where(fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere($fiscalMatches))->latest('id')->limit(10)->get()->map(fn ($c) => ['resource' => 'client', 'id' => $c->id, 'title' => $c->first_name.' '.$c->last_name, 'subtitle' => (($actor->can('clients.view-any') && $actor->can('view', $c)) || ($actor->can('clients.view-own') && $c->user_id === $actor->id)) ? ($c->fiscal_code ?? '') : ''])->all();
        $staffDocumentPractices = $this->access->staffQuery($actor)->when(! $actor->can('practice-documents.download'), fn ($q) => $q->whereRaw('1=0'));
        $publicDocumentPractices = (clone $practices)->where(function ($q) use ($actor) {
            $q->whereIn('client_profile_id', \App\Models\ClientDelegation::activeFor($actor, 'documents')->select('client_profile_id'));
            if ($actor->can('clients.view-own')) {
                $q->orWhereHas('client', fn ($client) => $client->where('user_id', $actor->id));
            }
        });
        $docs = PracticeDocument::query()->where('original_name', 'like', $like)->where(function ($visible) use ($staffDocumentPractices, $publicDocumentPractices) {
            $visible->whereIn('practice_id', $staffDocumentPractices->select('practices.id'))->orWhere(function ($public) use ($publicDocumentPractices) {
                $public->whereIn('practice_id', $publicDocumentPractices->select('practices.id'))->where(function ($documents) {
                    $documents->whereNotNull('document_request_id')->orWhereIn('id', \App\Models\PracticeMessage::query()->where('visibility', 'public')->whereNotNull('document_id')->select('document_id'));
                });
            });
        })->latest('id')->limit(10)->get()->map(fn ($d) => ['resource' => 'document', 'id' => $d->id, 'practice_id' => $d->practice_id, 'title' => $d->original_name, 'subtitle' => 'Documento pratica'])->all();

        return [...$results, ...$clients, ...$docs];
    }

    public function board(User $actor, ?int $branchId = null): array
    {
        abort_unless($actor->can('practices.view-any') || $actor->can('practices.view-own'), 403);
        if ($branchId) {
            abort_unless($actor->canAccessBranchId($branchId), 403);
        }
        $visible = $this->access->staffQuery($actor)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $operators = $actor->can('users.view-any') ? User::query()->assignable()->where('is_active', true)->select('id', 'name')->orderBy('name')->get() : collect();
        $workload = $actor->can('practices.assign') ? User::query()->assignable()->where('is_active', true)->withCount([
            'assignedPractices as open_count' => fn ($q) => $q->whereIn('practices.id', (clone $visible)->select('practices.id'))->whereNotIn('status', ['completata', 'annullata']),
            'assignedPractices as overdue_count' => fn ($q) => $q->whereIn('practices.id', (clone $visible)->select('practices.id'))->whereNotIn('status', ['completata', 'annullata'])->where('deadline_at', '<', now()),
            'assignedPractices as stalled_count' => fn ($q) => $q->whereIn('practices.id', (clone $visible)->select('practices.id'))->whereNotIn('status', ['completata', 'annullata'])->where('updated_at', '<', now()->subDays(7)),
        ])->orderByDesc('open_count')->limit(100)->get(['id', 'name'])->map(fn ($user) => $user->only(['id', 'name', 'open_count', 'overdue_count', 'stalled_count'])) : [];

        return ['branches' => \App\Models\Branch::query()->whereIn('id', $actor->accessibleBranchIds())->orderBy('name')->get(['id', 'name']), 'branch_id' => $branchId, 'can_bulk' => $actor->can('practices.update') || $actor->can('practices.assign'), 'can_assign' => $actor->can('practices.assign'), 'operators' => $operators, 'workload' => $workload, 'practices' => (clone $visible)->select(['id', 'client_profile_id', 'type', 'tracking_code', 'status', 'branch_id', 'deadline_at', 'updated_at'])->with('client:id,first_name,last_name')->latest('id')->paginate(30)];
    }
}

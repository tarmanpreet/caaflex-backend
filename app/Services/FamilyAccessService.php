<?php

namespace App\Services;

use App\Models\ActivityEntry;
use App\Models\ClientDelegation;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyAccessService
{
    public function __construct(private NotificationManager $notifications, private PracticeAccess $practices) {}

    public function owner(User $user, ClientProfile $client): bool
    {
        return $client->user_id === $user->id && $user->can('clients.view-own');
    }

    public function manage(User $user, ClientProfile $client): bool
    {
        return $this->owner($user, $client) || $user->can('update', $client);
    }

    public function allows(User $user, ClientProfile $client, string $scope = 'view'): bool
    {
        if ($this->owner($user, $client)) {
            return true;
        }
        if (ClientDelegation::activeFor($user, $scope)->where('client_profile_id', $client->id)->exists()) {
            return true;
        }
        if (! $user->can('view', $client)) {
            return false;
        }

        return match ($scope) {
            'view' => true,'documents' => $user->can('documents.download'),'book' => $user->can('appointments.create') && $user->can('clients.view-any'),default => false
        };
    }

    public function index(User $user): array
    {
        $ids = ClientDelegation::activeFor($user)->pluck('client_profile_id');
        if ($user->clientProfile) {
            $ids->push($user->clientProfile->id);
        }
        $profiles = ClientProfile::query()->whereKey($ids)->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($client) => $client->only(['id', 'first_name', 'last_name']));
        $invitations = ClientDelegation::query()->where('delegate_user_id', $user->id)->whereNull('revoked_at')->whereNull('accepted_at')->where('expires_at', '>', now())->with('client:id,first_name,last_name')->get()->map(fn ($delegation) => $this->row($delegation));

        return ['profiles' => $profiles, 'invitations' => $invitations, 'own_client_id' => $user->clientProfile?->id];
    }

    public function profile(User $user, ClientProfile $client): array
    {
        abort_unless($this->allows($user, $client), 403);
        $manage = $this->manage($user, $client);

        return ['client' => $client->only(['id', 'first_name', 'last_name']), 'can_manage' => $manage, 'can_book' => $this->allows($user, $client, 'book'), 'can_documents' => $this->allows($user, $client, 'documents'),
            'practices' => $client->practices()->whereIn('practices.id', $this->practices->query($user)->select('practices.id'))->select(['id', 'client_profile_id', 'type', 'tracking_code', 'reference_year', 'status', 'created_at'])->latest('id')->paginate(20, ['*'], 'practice_page'),
            'documents' => $this->allows($user, $client, 'documents') ? $client->documents()->select(['id', 'client_profile_id', 'original_name', 'mime_type', 'file_size', 'created_at', 'expires_on'])->latest('id')->paginate(20, ['*'], 'document_page') : null,
            'activity' => $user->can('update', $client) ? ActivityEntry::query()->where('client_profile_id', $client->id)->where(fn ($visible) => $visible->whereNull('practice_id')->orWhereIn('practice_id', $this->practices->staffQuery($user)->select('practices.id')))->with('actor:id,name')->latest('id')->paginate(30, ['*'], 'activity_page') : null,
            'delegations' => $manage ? ClientDelegation::query()->where('client_profile_id', $client->id)->with(['delegate:id,name,email', 'client:id,first_name,last_name'])->latest('id')->get()->map(fn ($delegation) => $this->row($delegation)) : [],
        ];
    }

    public function grant(User $actor, ClientProfile $client, array $data): ClientDelegation
    {
        abort_unless($this->manage($actor, $client), 403);
        $recipient = User::query()->where('email', mb_strtolower($data['email']))->where('is_active', true)->first();
        if (! $recipient || ! $recipient->hasRole('cliente') || $recipient->id === $client->user_id) {
            throw ValidationException::withMessages(['email' => 'Indica l’email di un altro cliente già registrato e attivo.']);
        }

        return DB::transaction(function () use ($actor, $client, $data, $recipient) {
            ClientProfile::query()->whereKey($client->id)->lockForUpdate()->firstOrFail();
            $delegation = ClientDelegation::query()->updateOrCreate(['client_profile_id' => $client->id, 'delegate_user_id' => $recipient->id], ['granted_by' => $actor->id, 'relationship' => $data['relationship'] ?? null, 'scopes' => $data['scopes'], 'expires_at' => isset($data['expires_on']) ? Carbon::parse($data['expires_on'])->endOfDay() : now()->addYear(), 'accepted_at' => null, 'revoked_at' => null]);
            $this->audit($actor, $client, 'delegation.invited', $delegation);
            $this->notifications->send([$recipient], 'practices.delegation-invited', 'practices', 'Invito a gestire un familiare', 'Apri Familiari e deleghe per verificare e accettare l’invito.', $client, route('family.index', absolute: false), $actor->id);

            return $delegation;
        });
    }

    public function accept(User $actor, ClientDelegation $delegation): void
    {
        abort_unless($delegation->delegate_user_id === $actor->id, 403);
        DB::transaction(function () use ($actor, $delegation) {
            $locked = ClientDelegation::query()->whereKey($delegation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status() !== 'pending') {
                throw ValidationException::withMessages(['delegation' => 'Invito non più disponibile.']);
            }
            $locked->update(['accepted_at' => now()]);
            $this->audit($actor, $locked->client, 'delegation.accepted', $locked);
        });
    }

    public function revoke(User $actor, ClientProfile $client, ClientDelegation $delegation): void
    {
        abort_unless($delegation->client_profile_id === $client->id, 404);
        abort_unless($this->manage($actor, $client) || $delegation->delegate_user_id === $actor->id, 403);
        DB::transaction(function () use ($actor, $client, $delegation) {
            $delegation->update(['revoked_at' => now()]);
            $this->audit($actor, $client, 'delegation.revoked', $delegation);
        });
    }

    private function row(ClientDelegation $delegation): array
    {
        return ['id' => $delegation->id, 'client' => $delegation->client?->only(['id', 'first_name', 'last_name']), 'delegate' => $delegation->relationLoaded('delegate') ? $delegation->delegate?->only(['id', 'name', 'email']) : null, 'relationship' => $delegation->relationship, 'scopes' => $delegation->scopes, 'expires_at' => $delegation->expires_at, 'status' => $delegation->status()];
    }

    private function audit(User $actor, ClientProfile $client, string $action, ClientDelegation $delegation): void
    {
        ActivityEntry::query()->create(['client_profile_id' => $client->id, 'user_id' => $actor->id, 'action' => $action, 'metadata' => ['delegation_id' => $delegation->id, 'scopes' => $delegation->scopes]]);
    }
}

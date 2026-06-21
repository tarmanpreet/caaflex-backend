<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Client\IndexClientAction;
use App\Actions\Client\InviteClientUserAction;
use App\Actions\Client\StoreClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\ClientProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, IndexClientAction $action): JsonResponse
    {
        $this->authorize('viewAny', ClientProfile::class);

        $clients = $action->execute($request);

        return response()->json($clients);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClientProfile::class);

        $q       = $request->get('q', '');
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));
        $page    = max(1, (int) $request->get('page', 1));
        $like    = '%' . $q . '%';

        $paginator = ClientProfile::select('id', 'first_name', 'last_name')
            ->when($q, function ($query) use ($like) {
                $query->where(function ($q) use ($like) {
                    $q->where('first_name', 'like', $like)
                      ->orWhere('last_name', 'like', $like)
                      ->orWhere('fiscal_code', 'like', $like);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => collect($paginator->items())->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->last_name . ' ' . $c->first_name,
            ]),
            'hasMore' => $paginator->hasMorePages(),
        ]);
    }

    public function store(StoreClientRequest $request, StoreClientAction $action): JsonResponse
    {
        $profile = $action->execute($request->validated(), $request->user()->id);

        return response()->json([
            'message' => 'Client created successfully.',
            'data' => $profile->load('user'),
        ], 201);
    }

    public function show(ClientProfile $client): JsonResponse
    {
        $this->authorize('view', $client);

        $client->load(['user', 'documents.uploadedBy']);

        return response()->json([
            'data' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, ClientProfile $client, UpdateClientAction $action): JsonResponse
    {
        $action->execute($request->validated(), $client);

        return response()->json([
            'message' => 'Client updated successfully.',
            'data' => $client->fresh(['user', 'documents.uploadedBy']),
        ]);
    }

    public function inviteUser(ClientProfile $client, InviteClientUserAction $action): JsonResponse
    {
        $this->authorize('update', $client);

        if (empty($client->email)) {
            return response()->json([
                'message' => 'Il profilo cliente non ha un indirizzo email.',
                'error'   => 'no_email',
            ], 422);
        }

        $outcome = $action->execute($client, $client->email);

        if ($outcome === 'conflict') {
            $conflictingClient = ClientProfile::whereHas('user', fn ($q) => $q->where('email', $client->email))->first();

            return response()->json([
                'message'            => 'Questo indirizzo email è già associato a un altro profilo cliente.',
                'error'              => 'conflict',
                'conflict_client_id' => $conflictingClient?->id,
            ], 409);
        }

        $client->load('user');

        return response()->json([
            'message' => $outcome === 'created'
                ? 'Account utente creato e collegato al profilo.'
                : 'Utente esistente collegato al profilo.',
            'outcome' => $outcome,
            'data'    => $client,
        ]);
    }

    public function destroy(ClientProfile $client): JsonResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return response()->json([
            'message' => 'Client deleted.',
        ]);
    }
}

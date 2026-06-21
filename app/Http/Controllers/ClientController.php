<?php

namespace App\Http\Controllers;

use App\Actions\Client\IndexClientAction;
use App\Actions\Client\InviteClientUserAction;
use App\Actions\Client\StoreClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\ClientProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, IndexClientAction $action)
    {
        $this->authorize('viewAny', ClientProfile::class);

        $clients = $action->execute($request);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => ['search' => $request->search],
        ]);
    }

    public function search(Request $request)
    {
        $this->authorize('viewAny', ClientProfile::class);

        $q = $request->get('q', '');
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));
        $page = max(1, (int) $request->get('page', 1));
        $like = '%'.$q.'%';

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
            'results' => collect($paginator->items())->map(fn ($c) => [
                'value' => $c->id,
                'label' => $c->last_name.' '.$c->first_name,
            ]),
            'hasMore' => $paginator->hasMorePages(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', ClientProfile::class);

        return Inertia::render('Clients/Create');
    }

    public function store(StoreClientRequest $request, StoreClientAction $action)
    {
        $profile = $action->execute($request->validated(), $request->user()->id);

        return redirect()->route('clients.show', $profile)
            ->with('success', 'Client created successfully.');
    }

    public function show(ClientProfile $client, Request $request)
    {
        $this->authorize('view', $client);

        $client->load(['user', 'documents.uploadedBy']);

        $practiceSearch = $request->practice_search;
        $practiceQuery = $client->practices()->with('assignedUsers');
        if ($practiceSearch) {
            $search = '%'.$practiceSearch.'%';
            $practiceQuery->where(fn ($q) => $q->where('type', 'like', $search)->orWhere('status', 'like', $search));
        }
        $practices = $practiceQuery->orderByDesc('updated_at')->paginate(10, ['*'], 'practice_page')->withQueryString();

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'documents' => $client->documents,
            'practices' => $practices,
            'practiceFilters' => ['search' => $practiceSearch],
            'conflictClientId' => session('conflictClientId'),
        ]);
    }

    public function edit(ClientProfile $client)
    {
        $this->authorize('update', $client);

        $client->load('user');

        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, ClientProfile $client, UpdateClientAction $action)
    {
        $action->execute($request->validated(), $client);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function inviteUser(ClientProfile $client, InviteClientUserAction $action)
    {
        $this->authorize('update', $client);

        if (empty($client->email)) {
            return back()->withErrors([
                'invite_email' => 'Il profilo cliente non ha un indirizzo email.',
            ]);
        }

        $outcome = $action->execute($client, $client->email);

        if ($outcome === 'conflict') {
            $conflictingClient = ClientProfile::whereHas('user', fn ($q) => $q->where('email', $client->email))->first();

            return back()
                ->withErrors(['invite_email' => 'Questo indirizzo email è già associato a un altro profilo cliente.'])
                ->with('conflictClientId', $conflictingClient?->id);
        }

        $message = $outcome === 'created'
            ? 'Account utente creato e collegato al profilo.'
            : 'Utente esistente collegato al profilo.';

        return redirect()->route('clients.show', $client)->with('success', $message);
    }

    public function destroy(ClientProfile $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted.');
    }
}

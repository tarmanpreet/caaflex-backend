<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientDelegationRequest;
use App\Models\ClientDelegation;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Services\ActivityRecorder;
use App\Services\FamilyAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FamilyController extends Controller
{
    public function __construct(private FamilyAccessService $family) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->family->index($request->user())]);
    }

    public function profile(Request $request, ClientProfile $client): JsonResponse
    {
        return response()->json(['data' => $this->family->profile($request->user(), $client)]);
    }

    public function grant(StoreClientDelegationRequest $request, ClientProfile $client): JsonResponse
    {
        $this->family->grant($request->user(), $client, $request->validated());

        return response()->json(['data' => $this->family->profile($request->user(), $client)], 201);
    }

    public function accept(Request $request, ClientDelegation $delegation): JsonResponse
    {
        $this->family->accept($request->user(), $delegation);

        return response()->json(['data' => $this->family->index($request->user())]);
    }

    public function revoke(Request $request, ClientProfile $client, ClientDelegation $delegation): JsonResponse
    {
        $this->family->revoke($request->user(), $client, $delegation);

        return response()->json(['data' => $this->family->index($request->user())]);
    }

    public function download(Request $request, ClientProfile $client, ClientDocument $document, ActivityRecorder $audit): StreamedResponse
    {
        abort_unless($this->family->allows($request->user(), $client, 'documents') && $document->client_profile_id === $client->id, 403);
        abort_unless(Storage::disk('local')->exists($document->disk_path), 404, 'Documento non disponibile.');
        $audit->record($request->user(), $document, 'document.downloaded');

        return Storage::disk('local')->download($document->disk_path,$document->original_name);
    }
}

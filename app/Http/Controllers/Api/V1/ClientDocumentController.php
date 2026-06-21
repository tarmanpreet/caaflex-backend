<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Client\StoreClientDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientDocumentRequest;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ClientDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreClientDocumentRequest $request, ClientProfile $client, StoreClientDocumentAction $action): JsonResponse
    {
        $created = $action->execute(
            $request->file('files'),
            $request->input('descriptions', []),
            $request->user()->id,
            $client
        );

        return response()->json([
            'message' => 'Documents uploaded.',
            'data' => $created,
        ], 201);
    }

    public function download(ClientProfile $client, ClientDocument $document)
    {
        $this->authorize('downloadDocument', [$client, $document]);

        return response()->download(storage_path('app/' . $document->disk_path), $document->original_name);
    }

    public function destroy(ClientProfile $client, ClientDocument $document): JsonResponse
    {
        $this->authorize('deleteDocument', [$client, $document]);

        Storage::disk('local')->delete($document->disk_path);
        $document->delete();

        return response()->json([
            'message' => 'Document deleted.',
        ]);
    }
}

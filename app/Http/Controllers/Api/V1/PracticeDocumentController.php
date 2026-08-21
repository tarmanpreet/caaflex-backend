<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PracticeDocument\StorePracticeDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeDocumentRequest;
use App\Http\Requests\UpdatePracticeDocumentExpirationRequest;
use App\Models\Practice;
use App\Models\PracticeDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PracticeDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(StorePracticeDocumentRequest $request, Practice $practice, StorePracticeDocumentAction $action): JsonResponse
    {
        $created = $action->execute(
            $request->file('files'),
            $request->input('descriptions', []),
            $request->input('expires_on', []),
            $request->user()->id,
            $practice,
        );

        return response()->json([
            'message' => 'Documents uploaded.',
            'data' => $created,
        ], 201);
    }

    public function updateExpiration(UpdatePracticeDocumentExpirationRequest $request, Practice $practice, PracticeDocument $document): JsonResponse
    {
        $document->update($request->validated());

        return response()->json([
            'message' => 'Document expiration updated.',
            'data' => $document->fresh(),
        ]);
    }

    public function download(Practice $practice, PracticeDocument $document)
    {
        $this->authorize('downloadDocument', [$practice, $document]);

        return Storage::disk('local')->download($document->disk_path, $document->original_name);
    }

    public function destroy(Practice $practice, PracticeDocument $document): JsonResponse
    {
        $this->authorize('deleteDocument', [$practice, $document]);

        Storage::disk('local')->delete($document->disk_path);
        $document->delete();

        return response()->json([
            'message' => 'Document deleted.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequestedDocumentRequest;
use App\Http\Requests\StorePracticeDocumentRequestRequest;
use App\Http\Requests\StorePracticeMessageRequest;
use App\Http\Requests\UploadRequestedDocumentRequest;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeDocumentRequest;
use App\Services\PracticeAccess;
use App\Services\PracticeWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PracticeWorkspaceController extends Controller
{
    public function __construct(private PracticeWorkspaceService $workspace, private PracticeAccess $access) {}

    public function index(Request $request, Practice $practice): JsonResponse
    {
        $data = $request->validate(['before' => ['nullable', 'integer', 'min:1'], 'activity_before' => ['nullable', 'integer', 'min:1']]);

        return response()->json(['data' => $this->workspace->data($practice, $request->user(), $data['before'] ?? null, $data['activity_before'] ?? null)]);
    }

    public function message(StorePracticeMessageRequest $request, Practice $practice): JsonResponse
    {
        $this->workspace->message($practice, $request->user(), $request->validated());

        return response()->json(['data' => $this->workspace->data($practice, $request->user())], 201);
    }

    public function storeRequest(StorePracticeDocumentRequestRequest $request, Practice $practice): JsonResponse
    {
        $this->workspace->request($practice, $request->user(), $request->validated());

        return response()->json(['data' => $this->workspace->data($practice, $request->user())], 201);
    }

    public function upload(UploadRequestedDocumentRequest $request, Practice $practice, PracticeDocumentRequest $documentRequest): JsonResponse
    {
        $this->workspace->upload($practice, $documentRequest, $request->user(), $request->validated('file'));

        return response()->json(['data' => $this->workspace->data($practice, $request->user())], 201);
    }

    public function review(ReviewRequestedDocumentRequest $request, Practice $practice, PracticeDocumentRequest $documentRequest, PracticeDocument $document): JsonResponse
    {
        $this->workspace->review($practice, $documentRequest, $document, $request->user(), $request->validated());

        return response()->json(['data' => $this->workspace->data($practice, $request->user())]);
    }

    public function download(Request $request, Practice $practice, PracticeDocument $document): StreamedResponse
    {
        abort_unless($document->practice_id === $practice->id && $this->access->view($request->user(), $practice) && $this->access->documents($request->user(), $practice), 404);
        abort_unless($this->access->staff($request->user(), $practice) || $document->document_request_id !== null || \App\Models\PracticeMessage::query()->where('practice_id', $practice->id)->where('visibility', 'public')->where('document_id', $document->id)->exists(), 403);
        abort_unless(Storage::disk('local')->exists($document->disk_path), 404, 'Documento non disponibile.');
        $this->workspace->audit($practice, $request->user(), 'document.downloaded', ['document_id' => $document->id]);

        return Storage::disk('local')->download($document->disk_path, $document->original_name);
    }
}

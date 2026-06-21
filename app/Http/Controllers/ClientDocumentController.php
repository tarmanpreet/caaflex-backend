<?php

namespace App\Http\Controllers;

use App\Actions\Client\StoreClientDocumentAction;
use App\Http\Requests\StoreClientDocumentRequest;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class ClientDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreClientDocumentRequest $request, ClientProfile $client, StoreClientDocumentAction $action)
    {
        $action->execute(
            $request->file('files'),
            $request->input('descriptions', []),
            $request->user()->id,
            $client
        );

        return redirect()->back()->with('success', 'Documents uploaded.');
    }

    public function download(ClientProfile $client, ClientDocument $document)
    {
        $this->authorize('downloadDocument', [$client, $document]);

        return Storage::disk('local')->download($document->disk_path, $document->original_name);
    }

    public function destroy(ClientProfile $client, ClientDocument $document)
    {
        $this->authorize('deleteDocument', [$client, $document]);

        Storage::disk('local')->delete($document->disk_path);
        $document->delete();

        return redirect()->back()->with('success', 'Document deleted.');
    }
}

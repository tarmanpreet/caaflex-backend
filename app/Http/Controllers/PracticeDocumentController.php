<?php

namespace App\Http\Controllers;

use App\Actions\PracticeDocument\StorePracticeDocumentAction;
use App\Http\Requests\StorePracticeDocumentRequest;
use App\Http\Requests\UpdatePracticeDocumentExpirationRequest;
use App\Models\Practice;
use App\Models\PracticeDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class PracticeDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(StorePracticeDocumentRequest $request, Practice $practice, StorePracticeDocumentAction $action): RedirectResponse
    {
        $action->execute(
            $request->file('files'),
            $request->input('descriptions', []),
            $request->input('expires_on', []),
            $request->user()->id,
            $practice,
        );

        return redirect()->back()->with('success', 'Documenti caricati.');
    }

    public function updateExpiration(UpdatePracticeDocumentExpirationRequest $request, Practice $practice, PracticeDocument $document): RedirectResponse
    {
        $document->update($request->validated());

        return redirect()->back()->with('success', 'Scadenza documento aggiornata.');
    }

    public function download(Practice $practice, PracticeDocument $document)
    {
        $this->authorize('downloadDocument', [$practice, $document]);

        return Storage::disk('local')->download($document->disk_path, $document->original_name);
    }

    public function destroy(Practice $practice, PracticeDocument $document)
    {
        $this->authorize('deleteDocument', [$practice, $document]);

        Storage::disk('local')->delete($document->disk_path);
        $document->delete();

        return redirect()->back()->with('success', 'Documento eliminato.');
    }
}

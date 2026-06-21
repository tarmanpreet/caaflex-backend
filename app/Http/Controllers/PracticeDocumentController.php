<?php

namespace App\Http\Controllers;

use App\Models\Practice;
use App\Models\PracticeDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PracticeDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Practice $practice)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:255',
        ]);

        $this->authorize('uploadDocument', $practice);

        foreach ($request->file('files') as $index => $file) {
            $path = $file->store("practice-documents/{$practice->id}", 'local');

            PracticeDocument::create([
                'practice_id' => $practice->id,
                'uploaded_by' => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'disk_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->input("descriptions.{$index}"),
            ]);
        }

        return redirect()->back()->with('success', 'Documenti caricati.');
    }

    public function download(Practice $practice, PracticeDocument $document)
    {
        $this->authorize('downloadDocument', $practice);

        return Storage::disk('local')->download($document->disk_path, $document->original_name);
    }

    public function destroy(Practice $practice, PracticeDocument $document)
    {
        $this->authorize('deleteDocument', $practice);

        Storage::disk('local')->delete($document->disk_path);
        $document->delete();

        return redirect()->back()->with('success', 'Documento eliminato.');
    }
}

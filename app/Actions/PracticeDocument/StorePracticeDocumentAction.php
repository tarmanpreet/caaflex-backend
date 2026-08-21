<?php

namespace App\Actions\PracticeDocument;

use App\Models\Practice;
use App\Models\PracticeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class StorePracticeDocumentAction
{
    /** @return Collection<int, PracticeDocument> */
    public function execute(array $files, array $descriptions, array $expiresOn, int $uploadedBy, Practice $practice): Collection
    {
        $created = collect();

        foreach ($files as $index => $file) {
            /** @var UploadedFile $file */
            $path = $file->store("practice-documents/{$practice->id}", 'local');

            $created->push(PracticeDocument::create([
                'practice_id' => $practice->id,
                'uploaded_by' => $uploadedBy,
                'original_name' => $file->getClientOriginalName(),
                'disk_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $descriptions[$index] ?? null,
                'expires_on' => $expiresOn[$index] ?? null,
            ]));
        }

        return $created;
    }
}

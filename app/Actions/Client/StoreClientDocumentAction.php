<?php

namespace App\Actions\Client;

use App\Models\ClientDocument;
use App\Models\ClientProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class StoreClientDocumentAction
{
    public function execute(array $files, array $descriptions, int $uploadedBy, ClientProfile $client): Collection
    {
        $created = collect();

        foreach ($files as $index => $file) {
            /** @var UploadedFile $file */
            $path = $file->store("client-documents/{$client->id}", 'local');

            $created->push(ClientDocument::create([
                'client_profile_id' => $client->id,
                'uploaded_by'       => $uploadedBy,
                'original_name'     => $file->getClientOriginalName(),
                'disk_path'         => $path,
                'mime_type'         => $file->getMimeType(),
                'file_size'         => $file->getSize(),
                'description'       => $descriptions[$index] ?? null,
            ]));
        }

        return $created;
    }
}

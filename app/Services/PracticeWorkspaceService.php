<?php

namespace App\Services;

use App\Models\ActivityEntry;
use App\Models\Practice;
use App\Models\PracticeDocument;
use App\Models\PracticeDocumentRequest;
use App\Models\PracticeMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PracticeWorkspaceService
{
    public function __construct(private PracticeAccess $access, private NotificationManager $notifications) {}

    public function data(Practice $practice, User $user, ?int $before = null, ?int $activityBefore = null): array
    {
        abort_unless($this->access->view($user, $practice), 403);
        $staff = $this->access->staff($user, $practice);
        $query = PracticeMessage::query()->where('practice_id', $practice->id)->when(! $staff, fn ($q) => $q->where('visibility', 'public'))->when($before, fn ($q) => $q->where('id', '<', $before))->with(['author:id,name', 'document']);
        $messages = $query->latest('id')->limit(51)->get();
        $more = $messages->count() > 50;
        $messages = $messages->take(50)->reverse()->values();
        $requests = PracticeDocumentRequest::query()->where('practice_id', $practice->id)->with(['documents' => fn ($q) => $q->orderByDesc('id')])->orderBy('id')->get();

        $canDocuments = $this->access->documents($user, $practice);
        $canMessage = $this->access->message($user, $practice);
        $canUpload = $this->access->upload($user, $practice);
        $activity = $staff ? ActivityEntry::query()->where('practice_id', $practice->id)->when($activityBefore, fn ($q) => $q->where('id', '<', $activityBefore))->with('actor:id,name')->latest('id')->limit(51)->get() : collect();

        return [
            'practice' => ['id' => $practice->id, 'type' => $practice->type, 'tracking_code' => $practice->tracking_code, 'reference_year' => $practice->reference_year, 'renewal_of_id' => $practice->renewal_of_id],
            'can_message' => $canMessage, 'can_upload' => $canUpload, 'can_documents' => $canDocuments,
            'can_manage' => $this->access->manage($user, $practice), 'can_renew' => $staff && $user->can('create', Practice::class), 'is_staff' => $staff,
            'messages' => $messages->map(fn ($message) => ['id' => $message->id, 'body' => $message->body, 'visibility' => $message->visibility, 'created_at' => $message->created_at, 'author' => $message->author, 'document' => $message->document && $canDocuments ? $this->document($message->document) : null]),
            'has_older_messages' => $more,
            'requests' => $requests->map(fn ($request) => ['id' => $request->id, 'title' => $request->title, 'instructions' => $request->instructions, 'due_on' => $request->due_on?->format('Y-m-d'), 'status' => $request->status, 'review_reason' => $request->review_reason, 'documents' => $canDocuments ? $request->documents->map(fn ($doc) => $this->document($doc)) : []]),
            'activity' => $activity->take(50)->values(), 'has_older_activity' => $activity->count() > 50,
        ];
    }

    public function message(Practice $practice, User $user, array $data): PracticeMessage
    {
        abort_unless($this->access->message($user, $practice), 403);
        abort_if($data['visibility'] === 'internal' && ! $this->access->staff($user, $practice), 403);

        $file = $data['file'] ?? null;
        abort_if($file && ! $this->access->upload($user, $practice), 403);
        $path = $file?->store('practice-documents/'.$practice->id, 'local');
        try {
            return DB::transaction(function () use ($practice, $user, $data, $file, $path) {
                $doc = $file ? PracticeDocument::query()->create(['practice_id' => $practice->id, 'uploaded_by' => $user->id, 'original_name' => basename($file->getClientOriginalName()), 'disk_path' => $path, 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize()]) : null;
                $message = PracticeMessage::query()->create(['practice_id' => $practice->id, 'user_id' => $user->id, 'body' => $data['body'] ?? '', 'visibility' => $data['visibility'], 'document_id' => $doc?->id]);
                $this->audit($practice, $user, 'message.created', ['message_id' => $message->id, 'visibility' => $message->visibility]);
                $this->notify($practice, $user, 'practices.message', 'Nuovo messaggio nella pratica', $message->visibility === 'public');

                return $message;
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
    }

    public function request(Practice $practice, User $user, array $data): PracticeDocumentRequest
    {
        abort_unless($this->access->manage($user, $practice), 403);

        return DB::transaction(function () use ($practice, $user, $data) {
            $request = PracticeDocumentRequest::query()->create($data + ['practice_id' => $practice->id, 'requested_by' => $user->id]);
            $this->audit($practice, $user, 'document.requested', ['request_id' => $request->id]);
            $this->notify($practice, $user, 'practices.document-requested', 'Sono richiesti nuovi documenti per la pratica', true);

            return $request;
        });
    }

    public function upload(Practice $practice, PracticeDocumentRequest $request, User $user, UploadedFile $file): PracticeDocument
    {
        abort_unless($request->practice_id === $practice->id && $this->access->upload($user, $practice), 404);
        $path = $file->store('practice-documents/'.$practice->id, 'local');
        try {
            return DB::transaction(function () use ($practice, $request, $user, $file, $path) {
                $locked = PracticeDocumentRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'approved') {
                    throw ValidationException::withMessages(['file' => 'Il documento è già stato approvato.']);
                }
                $doc = PracticeDocument::query()->create(['practice_id' => $practice->id, 'document_request_id' => $locked->id, 'uploaded_by' => $user->id, 'original_name' => basename($file->getClientOriginalName()), 'disk_path' => $path, 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'review_status' => 'pending']);
                $locked->update(['status' => 'submitted', 'review_reason' => null]);
                $this->audit($practice, $user, 'document.submitted', ['request_id' => $locked->id, 'document_id' => $doc->id]);
                $this->notify($practice, $user, 'practices.document-submitted', 'Un documento è pronto per la verifica', false);

                return $doc;
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function review(Practice $practice, PracticeDocumentRequest $request, PracticeDocument $document, User $user, array $data): void
    {
        abort_unless($this->access->manage($user, $practice), 403);
        abort_unless($request->practice_id === $practice->id && $document->practice_id === $practice->id && $document->document_request_id === $request->id, 404);
        DB::transaction(function () use ($practice, $request, $document, $user, $data) {
            $locked = PracticeDocumentRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ((int) $locked->documents()->max('id') !== $document->id) {
                throw ValidationException::withMessages(['document' => 'Verifica la versione più recente del documento.']);
            }
            $document->update(['review_status' => $data['status'], 'review_reason' => $data['reason'] ?? null, 'reviewed_by' => $user->id, 'reviewed_at' => now()]);
            $locked->update(['status' => $data['status'], 'review_reason' => $data['reason'] ?? null]);
            $this->audit($practice, $user, 'document.'.$data['status'], ['request_id' => $locked->id, 'document_id' => $document->id]);
            $this->notify($practice, $user, 'practices.document-reviewed', $data['status'] === 'approved' ? 'Documento approvato' : 'Un documento deve essere sostituito', true);
        });
    }

    public function audit(Practice $practice, User $user, string $action, array $metadata = []): ActivityEntry
    {
        return ActivityEntry::query()->create(['practice_id' => $practice->id, 'client_profile_id' => $practice->client_profile_id, 'user_id' => $user->id, 'action' => $action, 'metadata' => $metadata]);
    }

    private function document(PracticeDocument $doc): array
    {
        return $doc->only(['id', 'original_name', 'mime_type', 'file_size', 'review_status', 'review_reason', 'created_at']);
    }

    private function notify(Practice $practice, User $actor, string $event, string $title, bool $client): void
    {
        $practice->loadMissing(['assignedUsers', 'client.user']);
        $recipients = $practice->assignedUsers->concat($client ? [$practice->client?->user] : [])->filter();
        $this->notifications->send($recipients, $event, 'practices', $title, 'Apri la pratica per visualizzare i dettagli.', $practice, route('practices.workspace',$practice,false), $actor->id);
    }
}

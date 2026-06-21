<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Practice;
use App\Models\PracticeNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeNoteController extends Controller
{
    use AuthorizesRequests;

    public function index(Practice $practice): JsonResponse
    {
        return response()->json([
            'data' => $practice->notes()->with('author')->get(),
        ]);
    }

    public function store(Request $request, Practice $practice): JsonResponse
    {
        $this->authorize('createNote', $practice);

        $request->validate([
            'body' => 'required|string',
        ]);

        $note = PracticeNote::create([
            'practice_id' => $practice->id,
            'user_id' => $request->user()?->id,
            'body' => $request->body,
        ]);

        return response()->json([
            'message' => 'Note added.',
            'data' => $note->load('author'),
        ], 201);
    }
}

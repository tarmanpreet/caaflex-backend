<?php

namespace App\Http\Controllers;

use App\Models\Practice;
use App\Models\PracticeNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PracticeNoteController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Practice $practice)
    {
        $this->authorize('createNote', $practice);

        $request->validate([
            'body' => 'required|string',
        ]);

        PracticeNote::create([
            'practice_id' => $practice->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);

        return redirect()->back()->with('success', 'Nota aggiunta.');
    }
}

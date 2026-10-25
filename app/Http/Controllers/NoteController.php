<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Crop;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $notes = $this->ownedNotes($request->user())
            ->with('crop.land:id,name')
            ->orderByDesc('note_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('notes.index', ['notes' => $notes]);
    }

    public function create(Request $request): View
    {
        return view('notes.form', [
            'note' => new Note(['note_date' => today()->toDateString()]),
            'crops' => $this->ownedCrops($request->user())->get(),
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $crop = $this->ownedCrops($request->user())->findOrFail($data['crop_id']);
        unset($data['crop_id']);
        $crop->notes()->create($data);

        return to_route('notes.index')->with('status', 'Catatan berhasil ditambahkan.');
    }

    public function edit(Request $request, Note $note): View
    {
        $note = $this->ownedNotes($request->user())
            ->with('crop.land:id,name')
            ->findOrFail($note->getKey());

        return view('notes.form', [
            'note' => $note,
            'crops' => $this->ownedCrops($request->user())->get(),
        ]);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $note = $this->ownedNotes($request->user())->findOrFail($note->getKey());
        $data = $request->validated();
        $crop = $this->ownedCrops($request->user())->findOrFail($data['crop_id']);
        unset($data['crop_id']);
        $data['crop_id'] = $crop->getKey();
        $note->update($data);

        return to_route('notes.index')->with('status', 'Catatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        $note = $this->ownedNotes($request->user())->findOrFail($note->getKey());
        $note->delete();

        return to_route('notes.index')->with('status', 'Catatan berhasil dihapus.');
    }

    private function ownedNotes(User $user): Builder
    {
        return Note::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }

    private function ownedCrops(User $user): Builder
    {
        return Crop::query()
            ->whereHas('land', fn (Builder $query) => $query->where('user_id', $user->getKey()))
            ->with('land:id,name')
            ->orderBy('name')
            ->orderBy('id');
    }
}

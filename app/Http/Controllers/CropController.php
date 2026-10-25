<?php

namespace App\Http\Controllers;

use App\Http\Requests\CropRequest;
use App\Models\Crop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CropController extends Controller
{
    public function index(Request $request): View
    {
        $crops = $this->ownedCrops($request->user())
            ->with('land:id,name')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10);

        return view('crops.index', ['crops' => $crops]);
    }

    public function create(Request $request): View
    {
        return view('crops.form', [
            'crop' => new Crop(['status' => 'ditanam']),
            'lands' => $request->user()->lands()->orderBy('name')->get(),
        ]);
    }

    public function store(CropRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $land = $request->user()->lands()->findOrFail($data['land_id']);
        unset($data['land_id']);
        $land->crops()->create($data);

        return to_route('crops.index')->with('status', 'Tanaman berhasil ditambahkan.');
    }

    public function edit(Request $request, Crop $crop): View
    {
        $crop = $this->ownedCrops($request->user())->findOrFail($crop->getKey());

        return view('crops.form', [
            'crop' => $crop,
            'lands' => $request->user()->lands()->orderBy('name')->get(),
        ]);
    }

    public function update(CropRequest $request, Crop $crop): RedirectResponse
    {
        $crop = $this->ownedCrops($request->user())->findOrFail($crop->getKey());
        $data = $request->validated();
        $land = $request->user()->lands()->findOrFail($data['land_id']);
        unset($data['land_id']);
        $data['land_id'] = $land->getKey();
        $crop->update($data);

        return to_route('crops.index')->with('status', 'Tanaman berhasil diperbarui.');
    }

    public function destroy(Request $request, Crop $crop): RedirectResponse
    {
        $crop = $this->ownedCrops($request->user())->findOrFail($crop->getKey());

        if (
            $crop->activities()->exists()
            || $crop->expenses()->exists()
            || $crop->harvests()->exists()
            || $crop->notes()->exists()
        ) {
            return to_route('crops.index')
                ->with('error', 'Tanaman tidak dapat dihapus karena masih memiliki aktivitas, pengeluaran, panen, atau catatan terkait.');
        }

        $crop->delete();

        return to_route('crops.index')->with('status', 'Tanaman berhasil dihapus.');
    }

    private function ownedCrops(User $user): Builder
    {
        return Crop::query()
            ->whereHas('land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }
}

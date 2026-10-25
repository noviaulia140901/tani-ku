<?php

namespace App\Http\Controllers;

use App\Http\Requests\HarvestRequest;
use App\Models\Crop;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HarvestController extends Controller
{
    public function index(Request $request): View
    {
        $harvests = $this->ownedHarvests($request->user())
            ->with('crop.land:id,name')
            ->orderByDesc('harvest_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('harvests.index', ['harvests' => $harvests]);
    }

    public function create(Request $request): View
    {
        return view('harvests.form', [
            'harvest' => new Harvest(['harvest_date' => today()->toDateString()]),
            'crops' => $this->ownedCrops($request->user())->get(),
        ]);
    }

    public function store(HarvestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $crop = $this->ownedCrops($request->user())->findOrFail($data['crop_id']);
        unset($data['crop_id']);
        $crop->harvests()->create($data);

        return to_route('harvests.index')->with('status', 'Panen berhasil ditambahkan.');
    }

    public function edit(Request $request, Harvest $harvest): View
    {
        $harvest = $this->ownedHarvests($request->user())->findOrFail($harvest->getKey());

        return view('harvests.form', [
            'harvest' => $harvest,
            'crops' => $this->ownedCrops($request->user())->get(),
        ]);
    }

    public function update(HarvestRequest $request, Harvest $harvest): RedirectResponse
    {
        $harvest = $this->ownedHarvests($request->user())->findOrFail($harvest->getKey());
        $data = $request->validated();
        $crop = $this->ownedCrops($request->user())->findOrFail($data['crop_id']);
        unset($data['crop_id']);
        $data['crop_id'] = $crop->getKey();
        $harvest->update($data);

        return to_route('harvests.index')->with('status', 'Panen berhasil diperbarui.');
    }

    public function destroy(Request $request, Harvest $harvest): RedirectResponse
    {
        $hasSales = DB::transaction(function () use ($request, $harvest): bool {
            $harvest = $this->ownedHarvests($request->user())
                ->lockForUpdate()
                ->findOrFail($harvest->getKey());

            if ($harvest->sales()->exists()) {
                return true;
            }

            $harvest->delete();

            return false;
        });

        if ($hasSales) {
            return to_route('harvests.index')
                ->with('error', 'Panen tidak dapat dihapus karena sudah memiliki data penjualan.');
        }

        return to_route('harvests.index')->with('status', 'Panen berhasil dihapus.');
    }

    /** @return Builder<Harvest> */
    private function ownedHarvests(User $user): Builder
    {
        return Harvest::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }

    /** @return Builder<Crop> */
    private function ownedCrops(User $user): Builder
    {
        return Crop::query()
            ->whereHas('land', fn (Builder $query) => $query->where('user_id', $user->getKey()))
            ->with('land:id,name')
            ->orderBy('name')
            ->orderBy('id');
    }
}

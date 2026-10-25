<?php

namespace App\Http\Controllers;

use App\Http\Requests\LandRequest;
use App\Models\Land;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandController extends Controller
{
    public function index(Request $request): View
    {
        $lands = $request->user()
            ->lands()
            ->latest()
            ->paginate(10);

        return view('lands.index', ['lands' => $lands]);
    }

    public function create(): View
    {
        return view('lands.form', [
            'land' => new Land([
                'area_unit' => 'm²',
                'status' => 'aktif',
            ]),
        ]);
    }

    public function store(LandRequest $request): RedirectResponse
    {
        $request->user()->lands()->create($this->landAttributes($request));

        return to_route('lands.index')->with('status', 'Lahan berhasil ditambahkan.');
    }

    public function edit(Request $request, Land $land): View
    {
        $land = $request->user()->lands()->findOrFail($land->getKey());

        return view('lands.form', ['land' => $land]);
    }

    public function update(LandRequest $request, Land $land): RedirectResponse
    {
        $land = $request->user()->lands()->findOrFail($land->getKey());
        $land->update($this->landAttributes($request));

        return to_route('lands.index')->with('status', 'Lahan berhasil diperbarui.');
    }

    public function destroy(Request $request, Land $land): RedirectResponse
    {
        $land = $request->user()->lands()->findOrFail($land->getKey());

        if ($land->crops()->exists()) {
            return to_route('lands.index')
                ->with('error', 'Lahan tidak dapat dihapus karena masih memiliki tanaman.');
        }

        $land->delete();

        return to_route('lands.index')->with('status', 'Lahan berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function landAttributes(LandRequest $request): array
    {
        $attributes = $request->validated();
        $shouldClearCoordinates = $request->boolean('clear_coordinates');

        unset($attributes['clear_coordinates']);

        if ($shouldClearCoordinates) {
            $attributes['latitude'] = null;
            $attributes['longitude'] = null;
        } elseif (! $request->filled('latitude') || ! $request->filled('longitude')) {
            unset($attributes['latitude'], $attributes['longitude']);
        }

        return $attributes;
    }
}

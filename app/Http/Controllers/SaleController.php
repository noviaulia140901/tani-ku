<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = $this->ownedSales($request->user())
            ->with('harvest.crop.land:id,name')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('sales.index', ['sales' => $sales]);
    }

    public function create(Request $request): View
    {
        return view('sales.form', [
            'sale' => new Sale([
                'sale_date' => today()->toDateString(),
                'payment_status' => 'belum_lunas',
            ]),
            'harvests' => $this->formHarvests($request->user()),
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($data, $user): void {
            $harvest = $this->ownedHarvests($user)
                ->lockForUpdate()
                ->findOrFail($data['harvest_id']);

            $this->ensureAvailableQuantity($harvest, $data['quantity']);
            $data['total'] = $this->calculateTotal($data['quantity'], $data['price_per_unit']);
            $data['unit'] = $harvest->unit;

            $harvest->sales()->create($data);
        });

        return to_route('sales.index')->with('status', 'Penjualan berhasil ditambahkan.');
    }

    public function edit(Request $request, Sale $sale): View
    {
        $sale = $this->ownedSales($request->user())->findOrFail($sale->getKey());

        return view('sales.form', [
            'sale' => $sale,
            'harvests' => $this->formHarvests($request->user()),
        ]);
    }

    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($data, $sale, $user): void {
            $sale = $this->ownedSales($user)
                ->lockForUpdate()
                ->findOrFail($sale->getKey());
            $harvestIds = collect([$sale->harvest_id, $data['harvest_id']])
                ->unique()
                ->sort()
                ->values();
            $harvests = $harvestIds->map(
                fn (int $harvestId): Harvest => $this->ownedHarvests($user)
                    ->lockForUpdate()
                    ->findOrFail($harvestId),
            );
            $harvest = $harvests->first(
                fn (Harvest $lockedHarvest): bool => $lockedHarvest->getKey() === (int) $data['harvest_id'],
            );

            $this->ensureAvailableQuantity($harvest, $data['quantity'], $sale);
            $data['total'] = $this->calculateTotal($data['quantity'], $data['price_per_unit']);
            $data['unit'] = $harvest->unit;

            $sale->update($data);
        });

        return to_route('sales.index')->with('status', 'Penjualan berhasil diperbarui.');
    }

    public function destroy(Request $request, Sale $sale): RedirectResponse
    {
        $sale = $this->ownedSales($request->user())->findOrFail($sale->getKey());
        $sale->delete();

        return to_route('sales.index')->with('status', 'Penjualan berhasil dihapus.');
    }

    /** @return Builder<Sale> */
    private function ownedSales(User $user): Builder
    {
        return Sale::query()
            ->whereHas('harvest.crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }

    /** @return Builder<Harvest> */
    private function ownedHarvests(User $user): Builder
    {
        return Harvest::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }

    /** @return Collection<int, Harvest> */
    private function formHarvests(User $user): Collection
    {
        return $this->ownedHarvests($user)
            ->with('crop.land:id,name')
            ->orderByDesc('harvest_date')
            ->orderByDesc('id')
            ->get();
    }

    private function ensureAvailableQuantity(Harvest $harvest, string $quantity, ?Sale $excludingSale = null): void
    {
        $remainingQuantity = Sale::remainingQuantity($harvest, $excludingSale);

        if ($remainingQuantity === null) {
            throw ValidationException::withMessages([
                'harvest_id' => 'Sisa panen tidak dapat dihitung karena terdapat penjualan dengan satuan yang berbeda.',
            ]);
        }

        if (Sale::quantityExceeds($quantity, $remainingQuantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah penjualan melebihi sisa hasil panen.',
            ]);
        }
    }

    private function calculateTotal(string $quantity, string $pricePerUnit): string
    {
        $total = Sale::calculateTotal($quantity, $pricePerUnit);

        if ($total === null) {
            throw ValidationException::withMessages([
                'price_per_unit' => 'Total penjualan melebihi batas yang dapat disimpan.',
            ]);
        }

        return $total;
    }
}

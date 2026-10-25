<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'harvest_id',
        'buyer_name',
        'sale_date',
        'quantity',
        'unit',
        'price_per_unit',
        'total',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'quantity' => 'decimal:2',
            'price_per_unit' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

    public static function calculateTotal(string $quantity, string $pricePerUnit): ?string
    {
        $quantityInHundredths = self::toHundredths($quantity);
        $priceInHundredths = self::toHundredths($pricePerUnit);

        if ($quantityInHundredths === null || $priceInHundredths === null || $quantityInHundredths < 1 || $priceInHundredths < 1) {
            return null;
        }

        $maximumTotalInHundredths = 999999999999999;
        $maximumPriceInHundredths = intdiv(($maximumTotalInHundredths * 100) + 49, $quantityInHundredths);

        if ($priceInHundredths > $maximumPriceInHundredths) {
            return null;
        }

        $wholePrice = intdiv($priceInHundredths, 100);
        $fractionalPrice = $priceInHundredths % 100;
        $totalInHundredths = ($quantityInHundredths * $wholePrice)
            + intdiv(($quantityInHundredths * $fractionalPrice) + 50, 100);

        if ($totalInHundredths > $maximumTotalInHundredths) {
            return null;
        }

        return self::fromHundredths($totalInHundredths);
    }

    public static function remainingQuantity(Harvest $harvest, ?self $excludingSale = null): ?string
    {
        $harvestQuantity = self::toHundredths($harvest->quantity);

        if ($harvestQuantity === null || $harvestQuantity < 1) {
            return null;
        }

        $sales = $harvest->sales()
            ->when($excludingSale, fn ($query) => $query->where('id', '!=', $excludingSale->getKey()))
            ->get(['quantity', 'unit']);

        $soldQuantity = 0;

        foreach ($sales as $sale) {
            $saleQuantity = self::toHundredths($sale->quantity);

            if ($sale->unit !== $harvest->unit || $saleQuantity === null || $saleQuantity < 1) {
                return null;
            }

            if ($saleQuantity >= $harvestQuantity - $soldQuantity) {
                return '0.00';
            }

            $soldQuantity += $saleQuantity;
        }

        return self::fromHundredths($harvestQuantity - $soldQuantity);
    }

    public static function quantityExceeds(string $quantity, string $availableQuantity): bool
    {
        $quantityInHundredths = self::toHundredths($quantity);
        $availableInHundredths = self::toHundredths($availableQuantity);

        return $quantityInHundredths === null
            || $availableInHundredths === null
            || $quantityInHundredths > $availableInHundredths;
    }

    private static function toHundredths(string $value): ?int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $hundredths = $whole.str_pad($fraction, 2, '0');

        if (strlen($hundredths) > 18) {
            return null;
        }

        return (int) $hundredths;
    }

    private static function fromHundredths(int $value): string
    {
        return intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
    }
}

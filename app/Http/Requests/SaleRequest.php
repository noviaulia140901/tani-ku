<?php

namespace App\Http\Requests;

use App\Models\Crop;
use App\Models\Harvest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        $sale = $this->route('sale');

        if ($sale instanceof Sale && ! $this->ownedSales($user)->whereKey($sale->getKey())->exists()) {
            abort(404);
        }

        return true;
    }

    /** @return array<string, array<int, ValidationRule|string>> */
    public function rules(): array
    {
        $user = $this->user();
        $ownedLandIds = $user instanceof User
            ? $user->lands()->pluck('lands.id')->all()
            : [];
        $ownedCropIds = Crop::query()
            ->whereIn('land_id', $ownedLandIds)
            ->pluck('crops.id')
            ->all();

        return [
            'harvest_id' => [
                'required',
                'integer',
                Rule::exists('harvests', 'id')->whereIn('crop_id', $ownedCropIds),
            ],
            'buyer_name' => ['required', 'string', 'max:255'],
            'sale_date' => ['required', 'date_format:Y-m-d'],
            'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'unit' => ['required', 'string', 'max:255'],
            'price_per_unit' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'],
            'payment_status' => ['required', Rule::in(['lunas', 'belum_lunas'])],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'harvest_id',
                    'buyer_name',
                    'sale_date',
                    'quantity',
                    'unit',
                    'price_per_unit',
                    'payment_status',
                    'notes',
                ])) {
                    return;
                }

                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                $harvest = $this->ownedHarvests($user)->find($this->integer('harvest_id'));

                if (! $harvest instanceof Harvest) {
                    return;
                }

                if ($this->string('unit')->toString() !== $harvest->unit) {
                    $validator->errors()->add('unit', 'Satuan penjualan harus sama dengan satuan hasil panen.');

                    return;
                }

                $sale = $this->route('sale');
                $excludingSale = $sale instanceof Sale
                    ? $this->ownedSales($user)->find($sale->getKey())
                    : null;
                $remainingQuantity = Sale::remainingQuantity($harvest, $excludingSale);

                if ($remainingQuantity === null) {
                    $validator->errors()->add('harvest_id', 'Sisa panen tidak dapat dihitung karena terdapat penjualan dengan satuan yang berbeda.');
                } elseif (Sale::quantityExceeds($this->string('quantity')->toString(), $remainingQuantity)) {
                    $validator->errors()->add('quantity', 'Jumlah penjualan melebihi sisa hasil panen.');
                }

                if (Sale::calculateTotal(
                    $this->string('quantity')->toString(),
                    $this->string('price_per_unit')->toString(),
                ) === null) {
                    $validator->errors()->add('price_per_unit', 'Total penjualan melebihi batas yang dapat disimpan.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'harvest_id.required' => 'Pilih data panen terlebih dahulu.',
            'harvest_id.integer' => 'Data panen yang dipilih tidak valid.',
            'harvest_id.exists' => 'Panen yang dipilih tidak tersedia untuk akun Anda.',
            'buyer_name.required' => 'Nama pembeli wajib diisi.',
            'buyer_name.string' => 'Nama pembeli harus berupa teks.',
            'buyer_name.max' => 'Nama pembeli maksimal 255 karakter.',
            'sale_date.required' => 'Tanggal penjualan wajib diisi.',
            'sale_date.date_format' => 'Tanggal penjualan harus menggunakan format yang valid.',
            'quantity.required' => 'Jumlah penjualan wajib diisi.',
            'quantity.numeric' => 'Jumlah penjualan harus berupa angka.',
            'quantity.decimal' => 'Jumlah penjualan maksimal menggunakan dua angka di belakang koma.',
            'quantity.min' => 'Jumlah penjualan harus lebih besar dari 0.',
            'quantity.max' => 'Jumlah penjualan melebihi batas yang dapat disimpan.',
            'unit.required' => 'Satuan penjualan wajib diisi.',
            'unit.string' => 'Satuan penjualan harus berupa teks.',
            'unit.max' => 'Satuan penjualan maksimal 255 karakter.',
            'price_per_unit.required' => 'Harga per satuan wajib diisi.',
            'price_per_unit.numeric' => 'Harga per satuan harus berupa angka.',
            'price_per_unit.decimal' => 'Harga per satuan maksimal menggunakan dua angka di belakang koma.',
            'price_per_unit.min' => 'Harga per satuan harus lebih besar dari 0.',
            'price_per_unit.max' => 'Harga per satuan melebihi batas yang dapat disimpan.',
            'payment_status.required' => 'Pilih status pembayaran.',
            'payment_status.in' => 'Status pembayaran yang dipilih tidak valid.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 65.535 karakter.',
        ];
    }

    /** @return Builder<Harvest> */
    private function ownedHarvests(User $user): Builder
    {
        return Harvest::query()
            ->whereHas('crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }

    /** @return Builder<Sale> */
    private function ownedSales(User $user): Builder
    {
        return Sale::query()
            ->whereHas('harvest.crop.land', fn (Builder $query) => $query->where('user_id', $user->getKey()));
    }
}

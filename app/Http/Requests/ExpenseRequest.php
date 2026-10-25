<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, ValidationRule|string>> */
    public function rules(): array
    {
        $user = $this->user();
        $ownedLandIds = $user instanceof User
            ? $user->lands()->pluck('lands.id')->all()
            : [];

        return [
            'crop_id' => [
                'nullable',
                'integer',
                Rule::exists('crops', 'id')->whereIn('land_id', $ownedLandIds),
            ],
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('user_id', $user instanceof User ? $user->getKey() : null),
            ],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'],
            'expense_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'crop_id.integer' => 'Tanaman yang dipilih tidak valid.',
            'crop_id.exists' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
            'product_id.integer' => 'Produk yang dipilih tidak valid.',
            'product_id.exists' => 'Produk yang dipilih tidak tersedia untuk akun Anda.',
            'category.required' => 'Kategori pengeluaran wajib diisi.',
            'category.max' => 'Kategori pengeluaran maksimal 255 karakter.',
            'description.required' => 'Deskripsi pengeluaran wajib diisi.',
            'description.max' => 'Deskripsi pengeluaran maksimal 65.535 karakter.',
            'amount.required' => 'Jumlah biaya wajib diisi.',
            'amount.numeric' => 'Jumlah biaya harus berupa angka.',
            'amount.decimal' => 'Jumlah biaya maksimal menggunakan dua angka di belakang koma.',
            'amount.min' => 'Jumlah biaya harus lebih besar dari 0.',
            'amount.max' => 'Jumlah biaya melebihi batas yang dapat disimpan.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date_format' => 'Tanggal pengeluaran harus menggunakan format yang valid.',
        ];
    }
}

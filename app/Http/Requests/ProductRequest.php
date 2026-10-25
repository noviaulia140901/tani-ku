<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in([
                'pupuk',
                'pestisida',
                'herbisida',
                'fungisida',
                'obat_lainnya',
            ])],
            'unit' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'minimum_stock' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'description' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'name.string' => 'Nama produk harus berupa teks.',
            'name.max' => 'Nama produk maksimal 255 karakter.',
            'category.required' => 'Pilih kategori produk.',
            'category.in' => 'Kategori produk yang dipilih tidak valid.',
            'unit.required' => 'Satuan produk wajib diisi.',
            'unit.string' => 'Satuan produk harus berupa teks.',
            'unit.max' => 'Satuan produk maksimal 255 karakter.',
            'stock.required' => 'Stok produk wajib diisi.',
            'stock.numeric' => 'Stok produk harus berupa angka.',
            'stock.decimal' => 'Stok produk maksimal menggunakan dua angka di belakang koma.',
            'stock.min' => 'Stok produk tidak boleh kurang dari 0.',
            'stock.max' => 'Stok produk melebihi batas yang dapat disimpan.',
            'minimum_stock.required' => 'Batas stok minimum wajib diisi.',
            'minimum_stock.numeric' => 'Batas stok minimum harus berupa angka.',
            'minimum_stock.decimal' => 'Batas stok minimum maksimal menggunakan dua angka di belakang koma.',
            'minimum_stock.min' => 'Batas stok minimum tidak boleh kurang dari 0.',
            'minimum_stock.max' => 'Batas stok minimum melebihi batas yang dapat disimpan.',
            'purchase_price.required' => 'Harga beli wajib diisi.',
            'purchase_price.numeric' => 'Harga beli harus berupa angka.',
            'purchase_price.decimal' => 'Harga beli maksimal menggunakan dua angka di belakang koma.',
            'purchase_price.min' => 'Harga beli tidak boleh kurang dari 0.',
            'purchase_price.max' => 'Harga beli melebihi batas yang dapat disimpan.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi maksimal 65.535 karakter.',
        ];
    }
}

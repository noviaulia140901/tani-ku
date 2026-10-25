<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HarvestRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('crops', 'id')->whereIn('land_id', $ownedLandIds),
            ],
            'harvest_date' => ['required', 'date_format:Y-m-d'],
            'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'unit' => ['required', 'string', 'max:255'],
            'quality' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'crop_id.required' => 'Pilih tanaman terlebih dahulu.',
            'crop_id.integer' => 'Tanaman yang dipilih tidak valid.',
            'crop_id.exists' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
            'harvest_date.required' => 'Tanggal panen wajib diisi.',
            'harvest_date.date_format' => 'Tanggal panen harus menggunakan format yang valid.',
            'quantity.required' => 'Jumlah hasil panen wajib diisi.',
            'quantity.numeric' => 'Jumlah hasil panen harus berupa angka.',
            'quantity.decimal' => 'Jumlah hasil panen maksimal menggunakan dua angka di belakang koma.',
            'quantity.min' => 'Jumlah hasil panen harus lebih besar dari 0.',
            'quantity.max' => 'Jumlah hasil panen melebihi batas yang dapat disimpan.',
            'unit.required' => 'Satuan hasil panen wajib diisi.',
            'unit.string' => 'Satuan hasil panen harus berupa teks.',
            'unit.max' => 'Satuan hasil panen maksimal 255 karakter.',
            'quality.string' => 'Kualitas harus berupa teks.',
            'quality.max' => 'Kualitas maksimal 255 karakter.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 65.535 karakter.',
        ];
    }
}

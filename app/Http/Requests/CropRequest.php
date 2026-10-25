<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CropRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, ValidationRule|string>> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'land_id' => [
                'required',
                'integer',
                Rule::exists('lands', 'id')->where('user_id', $user instanceof User ? $user->getKey() : null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'variety' => ['nullable', 'string', 'max:255'],
            'planting_date' => ['required', 'date_format:Y-m-d'],
            'estimated_harvest_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:planting_date'],
            'status' => ['required', Rule::in(['persiapan', 'ditanam', 'perawatan', 'siap_panen', 'selesai'])],
            'seed_quantity' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'seed_unit' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'land_id.required' => 'Pilih lahan terlebih dahulu.',
            'land_id.integer' => 'Lahan yang dipilih tidak valid.',
            'land_id.exists' => 'Lahan yang dipilih tidak tersedia untuk akun Anda.',
            'name.required' => 'Nama tanaman wajib diisi.',
            'name.max' => 'Nama tanaman maksimal 255 karakter.',
            'variety.max' => 'Varietas maksimal 255 karakter.',
            'planting_date.required' => 'Tanggal tanam wajib diisi.',
            'planting_date.date_format' => 'Tanggal tanam harus menggunakan format yang valid.',
            'estimated_harvest_date.date_format' => 'Perkiraan panen harus menggunakan format tanggal yang valid.',
            'estimated_harvest_date.after_or_equal' => 'Perkiraan panen tidak boleh sebelum tanggal tanam.',
            'status.required' => 'Pilih status tanaman.',
            'status.in' => 'Status tanaman yang dipilih tidak valid.',
            'seed_quantity.numeric' => 'Jumlah benih harus berupa angka.',
            'seed_quantity.min' => 'Jumlah benih tidak boleh kurang dari 0.',
            'seed_quantity.max' => 'Jumlah benih maksimal 99.999.999,99.',
            'seed_unit.max' => 'Satuan benih maksimal 255 karakter.',
            'notes.max' => 'Catatan maksimal 65.535 karakter.',
        ];
    }
}

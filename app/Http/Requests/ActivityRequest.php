<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
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
            'activity_type' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['belum_dilakukan', 'selesai', 'dilewati'])],
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
            'activity_type.required' => 'Jenis aktivitas wajib diisi.',
            'activity_type.max' => 'Jenis aktivitas maksimal 255 karakter.',
            'scheduled_date.required' => 'Tanggal aktivitas wajib diisi.',
            'scheduled_date.date_format' => 'Tanggal aktivitas harus menggunakan format yang valid.',
            'scheduled_time.date_format' => 'Waktu harus menggunakan format jam dan menit.',
            'product_name.max' => 'Nama produk maksimal 255 karakter.',
            'dosage.max' => 'Dosis maksimal 255 karakter.',
            'status.required' => 'Pilih status aktivitas.',
            'status.in' => 'Status aktivitas yang dipilih tidak valid.',
            'notes.max' => 'Catatan maksimal 65.535 karakter.',
        ];
    }
}

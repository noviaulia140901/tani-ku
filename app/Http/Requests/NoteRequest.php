<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:65535'],
            'note_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'crop_id.required' => 'Pilih tanaman terlebih dahulu.',
            'crop_id.integer' => 'Tanaman yang dipilih tidak valid.',
            'crop_id.exists' => 'Tanaman yang dipilih tidak tersedia untuk akun Anda.',
            'title.required' => 'Judul catatan wajib diisi.',
            'title.string' => 'Judul catatan harus berupa teks.',
            'title.max' => 'Judul catatan maksimal 255 karakter.',
            'content.required' => 'Isi catatan wajib diisi.',
            'content.string' => 'Isi catatan harus berupa teks.',
            'content.max' => 'Isi catatan maksimal 65.535 karakter.',
            'note_date.required' => 'Tanggal catatan wajib diisi.',
            'note_date.date_format' => 'Tanggal catatan harus menggunakan format yang valid.',
        ];
    }
}

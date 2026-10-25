<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LandRequest extends FormRequest
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
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['exclude_if:clear_coordinates,1', 'nullable', 'numeric', 'decimal:0,7', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['exclude_if:clear_coordinates,1', 'nullable', 'numeric', 'decimal:0,7', 'between:-180,180', 'required_with:latitude'],
            'clear_coordinates' => ['sometimes', 'boolean'],
            'area' => ['required', 'numeric'],
            'area_unit' => ['required', 'string', 'max:255'],
            'soil_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['aktif', 'tidak_aktif', 'persiapan'])],
            'description' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lahan wajib diisi.',
            'name.max' => 'Nama lahan maksimal 255 karakter.',
            'location.required' => 'Lokasi lahan wajib diisi.',
            'location.max' => 'Lokasi lahan maksimal 255 karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.decimal' => 'Latitude maksimal memiliki 7 angka desimal.',
            'latitude.between' => 'Latitude harus berada antara -90 dan 90.',
            'latitude.required_with' => 'Latitude dan longitude harus diisi bersama.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.decimal' => 'Longitude maksimal memiliki 7 angka desimal.',
            'longitude.between' => 'Longitude harus berada antara -180 dan 180.',
            'longitude.required_with' => 'Latitude dan longitude harus diisi bersama.',
            'clear_coordinates.boolean' => 'Tindakan penghapusan koordinat tidak valid.',
            'area.required' => 'Luas lahan wajib diisi.',
            'area.numeric' => 'Luas lahan harus berupa angka.',
            'area_unit.required' => 'Satuan luas wajib diisi.',
            'area_unit.max' => 'Satuan luas maksimal 255 karakter.',
            'soil_type.max' => 'Jenis tanah maksimal 255 karakter.',
            'status.required' => 'Pilih status lahan.',
            'status.in' => 'Status lahan yang dipilih tidak valid.',
            'description.max' => 'Deskripsi maksimal 65.535 karakter.',
        ];
    }
}

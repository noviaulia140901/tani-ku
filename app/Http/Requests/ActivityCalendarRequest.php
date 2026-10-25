<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActivityCalendarRequest extends FormRequest
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
        $cropRule = Rule::exists('crops', 'id')->whereIn('land_id', $ownedLandIds);

        if (filter_var($this->input('land_id'), FILTER_VALIDATE_INT) !== false) {
            $cropRule->where('land_id', (int) $this->input('land_id'));
        }

        return [
            'month' => [
                'nullable',
                'string',
                'date_format:Y-m',
                'regex:/\A(1[0-9]{3}|[2-9][0-9]{3})-(0[1-9]|1[0-2])\z/',
            ],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'land_id' => [
                'nullable',
                'integer',
                Rule::exists('lands', 'id')->where('user_id', $user?->getAuthIdentifier()),
            ],
            'crop_id' => ['nullable', 'integer', $cropRule],
            'status' => [
                'nullable',
                Rule::in(['belum_dilakukan', 'selesai', 'dilewati']),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['month', 'date'])) {
                    return;
                }

                $date = $this->input('date');

                if ($date === null) {
                    return;
                }

                $month = $this->input('month') ?? substr($date, 0, 7);

                if (substr($date, 0, 7) !== $month) {
                    $validator->errors()->add('date', 'Tanggal pilihan harus berada di bulan kalender yang dipilih.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'month.date_format' => 'Bulan harus menggunakan format YYYY-MM.',
            'month.regex' => 'Bulan yang dipilih tidak valid.',
            'date.date_format' => 'Tanggal pilihan harus menggunakan format YYYY-MM-DD.',
            'land_id.exists' => 'Lahan yang dipilih tidak tersedia untuk akun Anda.',
            'crop_id.exists' => 'Tanaman yang dipilih tidak tersedia untuk akun atau lahan Anda.',
            'status.in' => 'Status aktivitas yang dipilih tidak valid.',
        ];
    }
}

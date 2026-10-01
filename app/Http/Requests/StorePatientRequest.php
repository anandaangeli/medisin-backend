<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'nik' => ['required', 'digits:16', function (string $attr, mixed $value, \Closure $fail) {
                if (Patient::where('nik_hash', Patient::hashNik($value))->exists()) {
                    $fail('NIK sudah terdaftar.');
                }
            }],
            'address' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'digits' => ':attribute harus :digits digit angka.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama pasien', 'nik' => 'NIK', 'address' => 'alamat'];
    }
}

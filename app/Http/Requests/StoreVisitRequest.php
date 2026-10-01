<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['no_rm' => strtoupper(trim((string) $this->no_rm))]);
    }

    public function rules(): array
    {
        return [
            'no_rm' => ['required', 'string', 'regex:/^RM\d{4,}$/', 'exists:patients,no_rm'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_rm.required' => ':attribute wajib diisi.',
            'no_rm.regex' => 'Format No Rekam Medik harus RM diikuti angka, contoh RM0001.',
            'no_rm.exists' => 'Pasien dengan No Rekam Medik tersebut tidak ditemukan.',
        ];
    }

    public function attributes(): array
    {
        return ['no_rm' => 'No Rekam Medik'];
    }
}

<?php

namespace App\Http\Requests;

use App\Support\PayloadCrypto;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** user_name & password arrive AES-encrypted; decrypt before validating. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_name' => is_string($this->user_name) ? PayloadCrypto::decrypt($this->user_name) : null,
            'password' => is_string($this->password) ? PayloadCrypto::decrypt($this->password) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required' => 'Username wajib diisi atau payload tidak terenkripsi dengan benar.',
            'password.required' => 'Password wajib diisi atau payload tidak terenkripsi dengan benar.',
        ];
    }
}

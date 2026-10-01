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

    /** username & password arrive AES-encrypted; decrypt before validating. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => is_string($this->username) ? PayloadCrypto::decrypt($this->username) : null,
            'password' => is_string($this->password) ? PayloadCrypto::decrypt($this->password) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi atau payload tidak terenkripsi dengan benar.',
            'password.required' => 'Password wajib diisi atau payload tidak terenkripsi dengan benar.',
        ];
    }
}

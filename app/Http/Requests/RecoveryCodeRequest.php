<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecoveryCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recovery_code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'recovery_code.required' => 'Recovery code is required.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFirstTime2faRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp'    => 'required|string|digits:6',
            'secret' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required'    => 'OTP code is required.',
            'otp.digits'      => 'OTP code must be 6 digits.',
            'secret.required' => 'Secret is required.',
        ];
    }
}
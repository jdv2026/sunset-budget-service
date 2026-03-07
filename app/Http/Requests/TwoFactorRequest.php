<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|string|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'OTP code is required.',
            'otp.digits'   => 'OTP code must be 6 digits.',
        ];
    }
}

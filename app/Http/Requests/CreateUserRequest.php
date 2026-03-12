<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'username'   => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'password'   => 'required|string|min:8|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username is already taken.',
        ];
    }
}

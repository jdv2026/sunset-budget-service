<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetActiveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'min:3', 'max:20', 'unique:budget_active_categories,name'],
            'icon'   => ['required', 'string', 'max:20'],
            'color'  => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

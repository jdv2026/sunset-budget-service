<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetActiveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'        => ['required', 'string', 'min:3', 'max:20', Rule::unique('budget_active_categories', 'name')->ignore($id)],
            'icon'        => ['required', 'string', 'max:20'],
            'color'       => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

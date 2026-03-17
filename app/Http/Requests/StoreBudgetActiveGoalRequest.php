<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetActiveGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->attributes->get('jwt_payload')->sub;

        return [
            'category_id' => ['required', 'integer', 'exists:budget_active_categories,id'],
            'name'        => ['required', 'string', 'min:3', 'max:20', Rule::unique('budget_active_goals', 'name')->where('user_id', $userId)],
            'description' => ['nullable', 'string', 'max:500'],
            'target'      => ['required', 'numeric', 'min:0'],
            'deadline'    => ['nullable', 'date'],
        ];
    }
}

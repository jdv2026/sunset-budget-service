<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetActiveGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id     = $this->route('id');
        $userId = $this->attributes->get('jwt_payload')->sub;

        return [
            'category_id' => ['required', 'integer', Rule::exists('budget_active_categories', 'id')->where('type', 'goals')],
            'name'        => ['required', 'string', 'min:3', 'max:20', Rule::unique('budget_active_goals', 'name')->where('user_id', $userId)->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'deadline'    => ['nullable', 'date'],
        ];
    }
}

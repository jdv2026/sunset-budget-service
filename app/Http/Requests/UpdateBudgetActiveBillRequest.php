<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetActiveBillRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:budget_active_categories,id'],
            'name'        => ['required', 'string', 'min:3', 'max:20', Rule::unique('budget_active_bills', 'name')->where('user_id', $userId)->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'due_date'    => ['required', 'date'],
            'frequency'   => ['required', Rule::in(['monthly', 'weekly', 'yearly'])],
        ];
    }
}

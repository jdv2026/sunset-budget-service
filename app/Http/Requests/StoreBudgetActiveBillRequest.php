<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetActiveBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->attributes->get('jwt_payload')->sub;

        return [
            'category_id' => ['required', 'integer', Rule::exists('budget_active_categories', 'id')->where('type', 'expense')],
            'name'        => ['required', 'string', 'min:3', 'max:20', Rule::unique('budget_active_bills', 'name')->where('user_id', $userId)],
            'description' => ['nullable', 'string', 'max:500'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'paid'        => ['nullable', 'numeric', 'min:0'],
            'due_date'    => ['required', 'date'],
            'frequency'   => ['required', Rule::in(['monthly', 'weekly', 'yearly'])],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetActiveTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->attributes->get('jwt_payload')->sub;

        return [
            'type'        => ['required', Rule::in(['income', 'expense'])],
            'description' => ['nullable', 'string', 'max:500'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'date'        => ['required', 'date'],
            'wallet_id'   => ['required', 'integer', Rule::exists('budget_active_wallets', 'id')->where('user_id', $userId)],
'goal_id'     => ['nullable', 'integer', Rule::exists('budget_active_goals', 'id')->where('user_id', $userId)],
            'bill_id'     => ['nullable', 'integer', Rule::exists('budget_active_bills', 'id')->where('user_id', $userId)],
        ];
    }
}

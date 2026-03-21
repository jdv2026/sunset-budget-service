<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetActiveIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->attributes->get('jwt_payload')->sub;

        return [
            'wallet_id' => ['required', 'integer', Rule::exists('budget_active_wallets', 'id')->where('user_id', $userId)],
            'amount'    => ['required', 'numeric', 'min:0.01'],
        ];
    }
}

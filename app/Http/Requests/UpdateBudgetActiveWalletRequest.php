<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetActiveWalletRequest extends FormRequest
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
            'name'          => ['required', 'string', 'min:3', 'max:20', Rule::unique('budget_active_wallets', 'name')->where('user_id', $userId)->ignore($id)],
            'description'   => ['nullable', 'string', 'max:500'],
            'category_id'    => ['nullable', 'integer', 'exists:budget_active_categories,id'],
            'amount'        => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

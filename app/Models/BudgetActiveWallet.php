<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetActiveWallet extends Model
{
    protected $table = 'budget_active_wallets';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category_name',
        'category_icon',
        'category_color',
        'category_type',
        'amount',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActiveWallet extends Model
{
    protected $table = 'budget_active_wallets';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'amount',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetActiveCategory::class, 'category_id');
    }
}

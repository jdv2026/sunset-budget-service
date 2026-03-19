<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActiveTransaction extends Model
{
    protected $table = 'budget_active_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'date',
        'wallet_id',
        'goal_id',
        'bill_id',
        'wallet',
        'goal',
        'bill',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(BudgetActiveWallet::class, 'wallet_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(BudgetActiveGoal::class, 'goal_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(BudgetActiveBill::class, 'bill_id');
    }
}

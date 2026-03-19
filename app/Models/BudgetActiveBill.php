<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActiveBill extends Model
{
    protected $table = 'budget_active_bills';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'amount',
        'paid',
        'due_date',
        'frequency',
        'status',
        'is_archive',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetActiveCategory::class, 'category_id');
    }
}

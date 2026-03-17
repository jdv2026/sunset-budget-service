<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActiveGoal extends Model
{
    protected $table = 'budget_active_goals';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'target',
        'saved',
        'deadline',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetActiveCategory::class, 'category_id');
    }
}

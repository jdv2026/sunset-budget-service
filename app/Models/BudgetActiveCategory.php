<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetActiveCategory extends Model
{
    protected $table = 'budget_active_categories';

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'color',
        'description',
    ];

    public function goals(): HasMany
    {
        return $this->hasMany(BudgetActiveGoal::class, 'category_id');
    }
}

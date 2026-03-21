<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetActiveGoal extends Model
{
    protected $table = 'budget_active_goals';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category_name',
        'category_icon',
        'category_color',
        'category_type',
        'amount',
        'saved',
        'deadline',
    ];
}

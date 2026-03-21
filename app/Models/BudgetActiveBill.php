<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetActiveBill extends Model
{
    protected $table = 'budget_active_bills';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category_name',
        'category_icon',
        'category_color',
        'category_type',
        'amount',
        'paid',
        'due_date',
        'frequency',
        'status',
    ];
}

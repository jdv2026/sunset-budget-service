<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetActiveTransaction extends Model
{
    protected $table = 'budget_active_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'date',
        'wallet_name',
        'wallet_description',
        'wallet_amount',
        'wallet_category_name',
        'wallet_category_icon',
        'wallet_category_color',
        'wallet_category_type',
        'goal_name',
        'goal_description',
        'goal_amount',
        'goal_saved',
        'goal_deadline',
        'goal_category_name',
        'goal_category_icon',
        'goal_category_color',
        'goal_category_type',
        'bill_name',
        'bill_description',
        'bill_paid',
        'bill_amount',
        'bill_due_date',
        'bill_frequency',
        'bill_status',
        'bill_category_name',
        'bill_category_icon',
        'bill_category_color',
        'bill_category_type',
    ];
}

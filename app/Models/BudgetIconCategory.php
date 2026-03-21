<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetIconCategory extends Model
{
    protected $table = 'budget_icon_categories';

    protected $fillable = [
        'icon_name',
    ];
}

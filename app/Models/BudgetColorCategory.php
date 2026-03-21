<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetColorCategory extends Model
{
    protected $table = 'budget_color_categories';

    protected $fillable = [
        'color_name',
        'hex_code',
    ];
}

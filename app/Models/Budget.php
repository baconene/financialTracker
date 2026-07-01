<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id', 'name', 'month', 'year', 'total_budget',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'name'         => 'encrypted',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function budgetCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }
}

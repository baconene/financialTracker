<?php

namespace App\Models;

use App\Casts\UserEncryptedFloat;
use Illuminate\Database\Eloquent\Model;

class BudgetCategory extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'budget_id', 'user_id', 'category_id', 'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => UserEncryptedFloat::class,
    ];

    public function budget(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

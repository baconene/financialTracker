<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'amount', 'frequency', 'description', 'color', 'category_id', 'is_active',
    ];

    protected $casts = [
        'amount'      => EncryptedFloat::class,
        'is_active'   => 'boolean',
        'name'        => 'encrypted',
        'description' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getMonthlyAmountAttribute(): float
    {
        return round(match ($this->frequency) {
            'weekly'    => $this->amount * 4.33,
            'biweekly'  => $this->amount * 2.17,
            'monthly'   => $this->amount,
            'quarterly' => $this->amount / 3,
            'annually'  => $this->amount / 12,
            default     => $this->amount,
        }, 2);
    }
}

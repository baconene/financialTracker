<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id', 'name', 'amount', 'frequency', 'due_day', 'next_due_date',
        'category', 'payee', 'color', 'icon', 'auto_pay', 'is_active', 'notes',
    ];

    protected $casts = [
        'amount'        => EncryptedFloat::class,
        'next_due_date' => 'date',
        'auto_pay'      => 'boolean',
        'is_active'     => 'boolean',
        'name'          => 'encrypted',
        'payee'         => 'encrypted',
        'notes'         => 'encrypted',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    public function getStatusAttribute(): string
    {
        $today = now()->startOfDay();
        $dueDate = $this->next_due_date;

        if ($dueDate->isPast()) {
            return 'overdue';
        } elseif ($dueDate->diffInDays($today) <= 7) {
            return 'due_soon';
        }
        return 'upcoming';
    }
}

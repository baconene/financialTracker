<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'lender', 'principal_amount', 'remaining_balance',
        'interest_rate', 'interest_type', 'payment_frequency', 'monthly_payment',
        'term_months', 'start_date', 'end_date', 'next_payment_date', 'status', 'notes',
    ];

    protected $casts = [
        'principal_amount'  => EncryptedFloat::class,
        'remaining_balance' => EncryptedFloat::class,
        'interest_rate'     => 'float',
        'monthly_payment'   => EncryptedFloat::class,
        'start_date'        => 'date',
        'end_date'          => 'date',
        'next_payment_date' => 'date',
        'name'              => 'encrypted',
        'lender'            => 'encrypted',
        'notes'             => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return $this->payments->sum('amount');
    }
}

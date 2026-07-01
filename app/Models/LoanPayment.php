<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'loan_id', 'user_id', 'account_id', 'amount', 'principal_portion', 'interest_portion',
        'payment_date', 'reference_number', 'notes',
    ];

    protected $casts = [
        'amount'            => EncryptedFloat::class,
        'principal_portion' => EncryptedFloat::class,
        'interest_portion'  => EncryptedFloat::class,
        'payment_date'      => 'date',
        'reference_number'  => 'encrypted',
        'notes'             => 'encrypted',
    ];

    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

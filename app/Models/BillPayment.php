<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'bill_id', 'user_id', 'account_id', 'amount', 'payment_date', 'reference_number', 'notes',
    ];

    protected $casts = [
        'amount'           => EncryptedFloat::class,
        'payment_date'     => 'date',
        'reference_number' => 'encrypted',
        'notes'            => 'encrypted',
    ];

    public function bill(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bill::class);
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

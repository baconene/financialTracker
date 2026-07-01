<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'type', 'bank_name', 'account_number',
        'balance', 'currency', 'color', 'icon_path', 'is_active', 'qr_code',
    ];

    protected $casts = [
        'balance'        => EncryptedFloat::class,
        'is_active'      => 'boolean',
        'name'           => 'encrypted',
        'bank_name'      => 'encrypted',
        'account_number' => 'encrypted',
    ];

    protected $appends = ['qr_code_url', 'icon_url'];

    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->qr_code ? Storage::url($this->qr_code) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon_path ? Storage::url($this->icon_path) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function billPayments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}

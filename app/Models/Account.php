<?php

namespace App\Models;

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
        'balance', 'currency', 'color', 'is_active', 'qr_code',
    ];

    protected $casts = [
        'balance' => 'float',
        'is_active' => 'boolean',
    ];

    protected $appends = ['qr_code_url'];

    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->qr_code ? Storage::url($this->qr_code) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

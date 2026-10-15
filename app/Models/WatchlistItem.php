<?php

namespace App\Models;

use App\Casts\UserEncryptedFloat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'asset_type', 'symbol', 'ticker', 'name',
        'quantity', 'avg_cost', 'target_above', 'target_below', 'notes',
    ];

    protected $casts = [
        'quantity'     => UserEncryptedFloat::class,
        'avg_cost'     => UserEncryptedFloat::class,
        'target_above' => 'float',
        'target_below' => 'float',
        'notes'        => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

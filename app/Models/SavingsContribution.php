<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;

class SavingsContribution extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'savings_goal_id', 'user_id', 'amount', 'notes', 'contribution_date',
    ];

    protected $casts = [
        'amount'            => EncryptedFloat::class,
        'contribution_date' => 'date',
        'notes'             => 'encrypted',
    ];

    public function savingsGoal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Casts\UserEncryptedFloat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'target_amount', 'current_amount',
        'target_date', 'image_url', 'color', 'icon', 'status', 'priority',
    ];

    protected $casts = [
        'target_amount'  => UserEncryptedFloat::class,
        'current_amount' => UserEncryptedFloat::class,
        'target_date'    => 'date',
        'name'           => 'encrypted',
        'description'    => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(SavingsContribution::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        return min(100, ($this->current_amount / $this->target_amount) * 100);
    }

    public function getRequiredMonthlyContributionAttribute(): ?float
    {
        if (!$this->target_date || $this->current_amount >= $this->target_amount) {
            return null;
        }
        $remaining = $this->target_amount - $this->current_amount;
        $monthsLeft = Carbon::now()->floatDiffInMonths($this->target_date);
        if ($monthsLeft <= 0) {
            return $remaining;
        }
        return round($remaining / $monthsLeft, 2);
    }

    public function getProjectedCompletionDateAttribute(): ?string
    {
        if ($this->current_amount >= $this->target_amount) {
            return Carbon::now()->toDateString();
        }
        $remaining = $this->target_amount - $this->current_amount;
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        $avgMonthly = $this->contributions()
            ->where('contribution_date', '>=', $threeMonthsAgo)
            ->get(['amount'])
            ->sum('amount') / 3;
        if ($avgMonthly <= 0) {
            return null;
        }
        $monthsNeeded = ceil($remaining / $avgMonthly);
        return Carbon::now()->addMonths((int) $monthsNeeded)->format('Y-m-d');
    }

    public function getMonthsRemainingAttribute(): ?int
    {
        if (!$this->target_date) {
            return null;
        }
        $months = (int) ceil(Carbon::now()->floatDiffInMonths($this->target_date, false));
        return max(0, $months);
    }
}

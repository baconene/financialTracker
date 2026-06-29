<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialSetting extends Model
{
    protected $fillable = [
        'user_id',
        'max_monthly_spending',
        'max_spending_percentage',
        'category_limits',
        'spending_warning_threshold',
        'min_monthly_savings',
        'desired_savings_rate',
        'emergency_fund_months',
        'min_remaining_balance',
        'max_debt_to_income',
        'max_bills_stress_score',
        'min_savings_rate',
        'desired_net_cash_flow',
    ];

    protected $casts = [
        'category_limits'          => 'array',
        'max_monthly_spending'     => 'float',
        'max_spending_percentage'  => 'float',
        'spending_warning_threshold' => 'float',
        'min_monthly_savings'      => 'float',
        'desired_savings_rate'     => 'float',
        'emergency_fund_months'    => 'float',
        'min_remaining_balance'    => 'float',
        'max_debt_to_income'       => 'float',
        'max_bills_stress_score'   => 'float',
        'min_savings_rate'         => 'float',
        'desired_net_cash_flow'    => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FinancialSetting;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class FinancialSettingController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();
        $settings = FinancialSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'spending_warning_threshold' => 80,
                'emergency_fund_months'      => 6,
            ]
        );

        $categories = Category::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('is_system', true);
        })->where('type', '!=', 'income')->orderBy('name')->get(['id', 'name', 'color', 'icon']);

        return Inertia::render('Settings/Financial', [
            'settings'   => $settings,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'max_monthly_spending'       => 'nullable|numeric|min:0',
            'max_spending_percentage'    => 'nullable|numeric|min:0|max:100',
            'category_limits'            => 'nullable|array',
            'category_limits.*'          => 'nullable|numeric|min:0',
            'spending_warning_threshold' => 'required|numeric|min:1|max:100',
            'min_monthly_savings'        => 'nullable|numeric|min:0',
            'desired_savings_rate'       => 'nullable|numeric|min:0|max:100',
            'emergency_fund_months'      => 'required|numeric|min:1|max:36',
            'min_remaining_balance'      => 'nullable|numeric|min:0',
            'max_debt_to_income'         => 'nullable|numeric|min:0|max:100',
            'max_bills_stress_score'     => 'nullable|numeric|min:0|max:100',
            'min_savings_rate'           => 'nullable|numeric|min:0|max:100',
            'desired_net_cash_flow'      => 'nullable|numeric',
        ]);

        // Remove empty category limits
        if (isset($validated['category_limits'])) {
            $validated['category_limits'] = array_filter(
                $validated['category_limits'],
                fn($v) => $v !== null && $v !== ''
            );
        }

        FinancialSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return back()->with('success', 'Financial settings saved!');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanPayment;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(): Response
    {
        $loans = Loan::where('user_id', Auth::id())
            ->with(['payments.account:id,name,bank_name,color,type'])
            ->orderBy('next_payment_date')
            ->get()
            ->map(function ($loan) {
                return array_merge($loan->toArray(), [
                    'paid_amount' => $loan->paid_amount,
                ]);
            });

        $accounts = Account::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'type', 'color', 'balance']);

        return Inertia::render('Loans/Index', ['loans' => $loans, 'accounts' => $accounts]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lender' => 'required|string|max:255',
            'principal_amount' => 'required|numeric|min:1',
            'remaining_balance' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_type' => 'required|in:simple,compound',
            'payment_frequency' => 'required|in:weekly,biweekly,monthly,quarterly',
            'monthly_payment' => 'required|numeric|min:0',
            'term_months' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'next_payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        Loan::create($validated);
        return back()->with('success', 'Loan added!');
    }

    public function update(Request $request, Loan $loan): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $loan);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lender' => 'required|string|max:255',
            'remaining_balance' => 'required|numeric|min:0',
            'monthly_payment' => 'required|numeric|min:0',
            'next_payment_date' => 'nullable|date',
            'status' => 'nullable|in:active,paid,defaulted',
            'notes' => 'nullable|string',
        ]);

        $loan->update($validated);
        return back()->with('success', 'Loan updated!');
    }

    public function destroy(Loan $loan): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $loan);
        $loan->delete();
        return back()->with('success', 'Loan deleted!');
    }

    public function addPayment(Request $request, Loan $loan): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $loan);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'principal_portion' => 'required|numeric|min:0',
            'interest_portion' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        LoanPayment::create(array_merge($validated, [
            'loan_id' => $loan->id,
            'user_id' => Auth::id(),
        ]));

        if (!empty($validated['account_id'])) {
            Account::where('id', $validated['account_id'])->decrement('balance', $validated['amount']);
        }

        $loan->decrement('remaining_balance', $validated['principal_portion']);

        if ($loan->remaining_balance <= 0) {
            $loan->update(['status' => 'paid', 'remaining_balance' => 0]);
        }

        return back()->with('success', 'Payment recorded!');
    }

    public function create(): Response
    {
        return Inertia::render('Loans/Create');
    }

    public function show(Loan $loan): Response
    {
        return Inertia::render('Loans/Show', [
            'loan' => array_merge($loan->toArray(), ['paid_amount' => $loan->paid_amount]),
            'payments' => $loan->payments()->orderBy('payment_date', 'desc')->get(),
        ]);
    }

    public function edit(Loan $loan): Response
    {
        return Inertia::render('Loans/Edit', ['loan' => $loan]);
    }
}

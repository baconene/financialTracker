<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        $accounts = Account::where('user_id', Auth::id())
            ->withCount('transactions')
            ->orderBy('balance', 'desc')
            ->get();

        return Inertia::render('Accounts/Index', ['accounts' => $accounts]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,e-wallet,credit,investment',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'balance' => 'required|numeric',
            'color' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        Account::create($validated);
        return back()->with('success', 'Account created!');
    }

    public function update(Request $request, Account $account): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash,e-wallet,credit,investment',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);
        return back()->with('success', 'Account updated!');
    }

    public function destroy(Account $account): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $account);
        $account->delete();
        return back()->with('success', 'Account deleted!');
    }

    public function create(): Response
    {
        return Inertia::render('Accounts/Create');
    }

    public function show(Account $account): Response
    {
        return Inertia::render('Accounts/Show', ['account' => $account]);
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Accounts/Edit', ['account' => $account]);
    }
}

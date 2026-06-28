<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $query = Transaction::where('user_id', $user->id)
            ->with(['category', 'account']);

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => Account::where('user_id', $user->id)->where('is_active', true)->get(),
            'categories' => Category::where('user_id', $user->id)->get(),
            'filters' => $request->only(['search', 'type', 'category_id', 'account_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        $user = Auth::user();
        return Inertia::render('Transactions/Create', [
            'accounts' => Account::where('user_id', $user->id)->where('is_active', true)->get(),
            'categories' => Category::where('user_id', $user->id)->get(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $validated['user_id'] = Auth::id();

        $transaction = Transaction::create($validated);

        // Update account balance
        $account = Account::find($validated['account_id']);
        if ($validated['type'] === 'income') {
            $account->increment('balance', $validated['amount']);
        } elseif ($validated['type'] === 'expense') {
            $account->decrement('balance', $validated['amount']);
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction added successfully!');
    }

    public function update(Request $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
        ]);

        // Reverse old balance change
        $oldAccount = Account::find($transaction->account_id);
        if ($transaction->type === 'income') {
            $oldAccount->decrement('balance', $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $oldAccount->increment('balance', $transaction->amount);
        }

        $transaction->update($validated);

        // Apply new balance change
        $newAccount = Account::find($validated['account_id']);
        if ($validated['type'] === 'income') {
            $newAccount->increment('balance', $validated['amount']);
        } elseif ($validated['type'] === 'expense') {
            $newAccount->decrement('balance', $validated['amount']);
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction updated!');
    }

    public function destroy(Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $transaction);

        // Reverse balance change
        $account = Account::find($transaction->account_id);
        if ($transaction->type === 'income') {
            $account->decrement('balance', $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $account->increment('balance', $transaction->amount);
        }

        $transaction->delete();
        return back()->with('success', 'Transaction deleted!');
    }

    public function show(Transaction $transaction): Response
    {
        return Inertia::render('Transactions/Show', ['transaction' => $transaction->load(['category', 'account'])]);
    }

    public function edit(Transaction $transaction): Response
    {
        $user = Auth::user();
        return Inertia::render('Transactions/Edit', [
            'transaction' => $transaction,
            'accounts' => Account::where('user_id', $user->id)->get(),
            'categories' => Category::where('user_id', $user->id)->get(),
        ]);
    }
}


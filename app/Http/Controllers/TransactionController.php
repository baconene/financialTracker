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

        // Non-text filters applied at the DB level
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

        $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        // Description is encrypted, so text search must be done in PHP after decryption
        if ($request->filled('search')) {
            $search  = mb_strtolower($request->search);
            $perPage = 20;
            $page    = max(1, (int) $request->input('page', 1));

            $all = $query->get()->filter(fn ($t) =>
                str_contains(mb_strtolower($t->description ?? ''), $search)
                || str_contains(mb_strtolower($t->notes ?? ''), $search)
            )->values();

            $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($page, $perPage),
                $all->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $transactions = $query->paginate(20)->withQueryString();
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts'     => Account::where('user_id', $user->id)->where('is_active', true)->get(),
            'categories'   => Category::where('user_id', $user->id)->get(),
            'filters'      => $request->only(['search', 'type', 'category_id', 'account_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect('/transactions?create=1');
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

        // Update account balance (read-modify-save since balance is encrypted)
        $account = Account::find($validated['account_id']);
        if ($validated['type'] === 'income') {
            $account->balance = (float) $account->balance + (float) $validated['amount'];
            $account->save();
        } elseif ($validated['type'] === 'expense') {
            $account->balance = (float) $account->balance - (float) $validated['amount'];
            $account->save();
        }

        return back()->with('success', 'Transaction added successfully!');
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

        // Reverse old balance change (read-modify-save since balance is encrypted)
        $oldAccount = Account::find($transaction->account_id);
        if ($transaction->type === 'income') {
            $oldAccount->balance = (float) $oldAccount->balance - (float) $transaction->amount;
            $oldAccount->save();
        } elseif ($transaction->type === 'expense') {
            $oldAccount->balance = (float) $oldAccount->balance + (float) $transaction->amount;
            $oldAccount->save();
        }

        $transaction->update($validated);

        // Apply new balance change
        $newAccount = Account::find($validated['account_id']);
        if ($validated['type'] === 'income') {
            $newAccount->balance = (float) $newAccount->balance + (float) $validated['amount'];
            $newAccount->save();
        } elseif ($validated['type'] === 'expense') {
            $newAccount->balance = (float) $newAccount->balance - (float) $validated['amount'];
            $newAccount->save();
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction updated!');
    }

    public function destroy(Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $transaction);

        // Reverse balance change (read-modify-save since balance is encrypted)
        $account = Account::find($transaction->account_id);
        if ($transaction->type === 'income') {
            $account->balance = (float) $account->balance - (float) $transaction->amount;
            $account->save();
        } elseif ($transaction->type === 'expense') {
            $account->balance = (float) $account->balance + (float) $transaction->amount;
            $account->save();
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


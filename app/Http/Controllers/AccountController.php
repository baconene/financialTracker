<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
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

    public function show(Account $account): Response
    {
        $this->authorize('view', $account);

        $paginator = Transaction::where('account_id', $account->id)
            ->where('user_id', Auth::id())
            ->with('category')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(30);

        $transactions = [
            'data' => $paginator->items(),
            'links' => [
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ];

        $summary = [
            'income' => (float) Transaction::where('account_id', $account->id)
                ->where('user_id', Auth::id())
                ->where('type', 'income')
                ->sum('amount'),
            'expenses' => (float) Transaction::where('account_id', $account->id)
                ->where('user_id', Auth::id())
                ->where('type', 'expense')
                ->sum('amount'),
            'total' => (int) Transaction::where('account_id', $account->id)
                ->where('user_id', Auth::id())
                ->count(),
        ];

        $categories = Category::where(function ($q) {
            $q->where('user_id', Auth::id())->orWhereNull('user_id');
        })->orderBy('name')->get();

        return Inertia::render('Accounts/Show', [
            'account' => $account,
            'transactions' => $transactions,
            'summary' => $summary,
            'categories' => $categories,
        ]);
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
            'qr_code' => 'nullable|image|max:5120',
            'remove_qr' => 'nullable',
        ]);

        if ($request->hasFile('qr_code')) {
            if ($account->qr_code) {
                Storage::disk('public')->delete($account->qr_code);
            }
            $validated['qr_code'] = $request->file('qr_code')->store('qr-codes', 'public');
        } elseif ($request->input('remove_qr') == '1') {
            if ($account->qr_code) {
                Storage::disk('public')->delete($account->qr_code);
            }
            $validated['qr_code'] = null;
        } else {
            unset($validated['qr_code']);
        }

        unset($validated['remove_qr']);
        $account->update($validated);
        return back()->with('success', 'Account updated!');
    }

    public function destroy(Account $account): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $account);
        $account->delete();
        return redirect('/accounts')->with('success', 'Account deleted!');
    }

    public function create(): Response
    {
        return Inertia::render('Accounts/Create');
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Accounts/Edit', ['account' => $account]);
    }
}

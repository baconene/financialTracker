<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bill;
use App\Models\BillPayment;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BillController extends Controller
{
    public function index(): Response
    {
        $bills = Bill::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('next_due_date')
            ->get()
            ->map(function ($bill) {
                return array_merge($bill->toArray(), ['status' => $bill->status]);
            });

        return Inertia::render('Bills/Index', ['bills' => $bills]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly,annually,one-time',
            'due_day' => 'nullable|integer|min:1|max:31',
            'next_due_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'payee' => 'nullable|string|max:255',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'auto_pay' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        Bill::create($validated);
        return back()->with('success', 'Bill added!');
    }

    public function update(Request $request, Bill $bill): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $bill);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'next_due_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'payee' => 'nullable|string|max:255',
            'auto_pay' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $bill->update($validated);
        return back()->with('success', 'Bill updated!');
    }

    public function destroy(Bill $bill): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $bill);
        $bill->delete();
        return back()->with('success', 'Bill deleted!');
    }

    public function markPaid(Request $request, Bill $bill): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $bill);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string',
        ]);

        BillPayment::create(array_merge($validated, [
            'bill_id' => $bill->id,
            'user_id' => Auth::id(),
        ]));

        // Advance next due date
        $nextDate = Carbon::parse($bill->next_due_date);
        switch ($bill->frequency) {
            case 'weekly':
                $nextDate->addWeek();
                break;
            case 'biweekly':
                $nextDate->addWeeks(2);
                break;
            case 'monthly':
                $nextDate->addMonth();
                break;
            case 'quarterly':
                $nextDate->addMonths(3);
                break;
            case 'annually':
                $nextDate->addYear();
                break;
            default:
                $bill->update(['is_active' => false]);
                return back()->with('success', 'Bill marked as paid!');
        }

        $bill->update(['next_due_date' => $nextDate->toDateString()]);
        return back()->with('success', 'Bill marked as paid!');
    }

    public function create(): Response
    {
        return Inertia::render('Bills/Create');
    }

    public function show(Bill $bill): Response
    {
        return Inertia::render('Bills/Show', [
            'bill' => array_merge($bill->toArray(), ['status' => $bill->status]),
            'payments' => $bill->payments()->orderBy('payment_date', 'desc')->get(),
        ]);
    }

    public function edit(Bill $bill): Response
    {
        return Inertia::render('Bills/Edit', ['bill' => $bill]);
    }
}

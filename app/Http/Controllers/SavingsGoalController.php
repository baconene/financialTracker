<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\SavingsGoal;
use App\Models\SavingsContribution;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    public function index(): Response
    {
        $goals = SavingsGoal::where('user_id', Auth::id())
            ->withCount('contributions')
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('target_date')
            ->get()
            ->map(fn($goal) => array_merge($goal->toArray(), [
                'progress_percentage'            => $goal->progress_percentage,
                'required_monthly_contribution'  => $goal->required_monthly_contribution,
                'projected_completion_date'      => $goal->projected_completion_date,
                'months_remaining'               => $goal->months_remaining,
            ]));

        return Inertia::render('SavingsGoals/Index', ['goals' => $goals]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'target_amount'  => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'target_date'    => 'nullable|date|after:today',
            'color'          => 'nullable|string',
            'icon'           => 'nullable|string',
            'priority'       => 'nullable|in:low,medium,high',
            'image'          => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('savings-goals', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        unset($validated['image']);
        $validated['user_id'] = Auth::id();
        SavingsGoal::create($validated);
        return back()->with('success', 'Savings goal created!');
    }

    public function update(Request $request, SavingsGoal $savingsGoal): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $savingsGoal);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'target_amount'=> 'required|numeric|min:1',
            'target_date'  => 'nullable|date',
            'color'        => 'nullable|string',
            'icon'         => 'nullable|string',
            'priority'     => 'nullable|in:low,medium,high',
            'image'        => 'nullable|image|max:5120',
            'remove_image' => 'nullable',
            'status'       => 'nullable|in:active,completed,cancelled',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteOldImage($savingsGoal->image_url);
            $path = $request->file('image')->store('savings-goals', 'public');
            $validated['image_url'] = Storage::url($path);
        } elseif ($request->input('remove_image') == '1') {
            $this->deleteOldImage($savingsGoal->image_url);
            $validated['image_url'] = null;
        }

        unset($validated['image'], $validated['remove_image']);
        $savingsGoal->update($validated);
        return back()->with('success', 'Goal updated!');
    }

    private function deleteOldImage(?string $imageUrl): void
    {
        if ($imageUrl && str_starts_with($imageUrl, '/storage/')) {
            $path = substr($imageUrl, strlen('/storage/'));
            Storage::disk('public')->delete($path);
        }
    }

    public function destroy(SavingsGoal $savingsGoal): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $savingsGoal);
        $savingsGoal->delete();
        return back()->with('success', 'Goal deleted!');
    }

    public function contribute(Request $request, SavingsGoal $savingsGoal): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $savingsGoal);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'contribution_date' => 'required|date',
        ]);

        SavingsContribution::create([
            'savings_goal_id' => $savingsGoal->id,
            'user_id' => Auth::id(),
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'contribution_date' => $validated['contribution_date'],
        ]);

        $savingsGoal->increment('current_amount', $validated['amount']);

        if ($savingsGoal->current_amount >= $savingsGoal->target_amount) {
            $savingsGoal->update(['status' => 'completed']);
        }

        return back()->with('success', 'Contribution added!');
    }

    public function create(): Response
    {
        return Inertia::render('SavingsGoals/Create');
    }

    public function show(SavingsGoal $savingsGoal): Response
    {
        return Inertia::render('SavingsGoals/Show', [
            'goal' => array_merge($savingsGoal->toArray(), ['progress_percentage' => $savingsGoal->progress_percentage]),
            'contributions' => $savingsGoal->contributions()->orderBy('contribution_date', 'desc')->get(),
        ]);
    }

    public function edit(SavingsGoal $savingsGoal): Response
    {
        return Inertia::render('SavingsGoals/Edit', ['goal' => $savingsGoal]);
    }
}

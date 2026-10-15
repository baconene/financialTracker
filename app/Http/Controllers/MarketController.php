<?php

namespace App\Http\Controllers;

use App\Models\WatchlistItem;
use App\Services\MarketDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MarketController extends Controller
{
    public function __construct(private MarketDataService $market) {}

    public function index(): Response
    {
        $items = WatchlistItem::where('user_id', Auth::id())
            ->orderBy('asset_type')
            ->orderBy('ticker')
            ->get();

        return Inertia::render('Markets/Index', [
            'items'          => $items,
            'refreshSeconds' => max(30, (int) config('services.market.cache_seconds', 60)),
        ]);
    }

    public function quotes(): JsonResponse
    {
        $items = WatchlistItem::where('user_id', Auth::id())->get();

        return response()->json([
            'quotes'     => $this->market->quotes($items),
            'fetched_at' => now()->toAtomString(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'    => 'required|string|min:1|max:50',
            'type' => 'required|in:stock,crypto',
        ]);

        return response()->json([
            'results' => $this->market->search($validated['type'], trim($validated['q'])),
        ]);
    }

    public function history(Request $request, WatchlistItem $watchlistItem): JsonResponse
    {
        abort_if($watchlistItem->user_id !== Auth::id(), 403);
        $validated = $request->validate(['range' => ['nullable', Rule::in(MarketDataService::RANGES)]]);

        return response()->json([
            'points' => $this->market->history(
                $watchlistItem->asset_type,
                $watchlistItem->symbol,
                $validated['range'] ?? '1d',
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateItem($request);
        $validated['symbol'] = $this->normalizeSymbol($validated['asset_type'], $validated['symbol']);

        $exists = WatchlistItem::where('user_id', Auth::id())
            ->where('asset_type', $validated['asset_type'])
            ->where('symbol', $validated['symbol'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['symbol' => 'This symbol is already on your watchlist.']);
        }

        $validated['ticker'] = ($validated['ticker'] ?? null) ?: strtoupper($validated['symbol']);
        WatchlistItem::create(['user_id' => Auth::id(), ...$validated]);

        return back()->with('success', "{$validated['ticker']} added to your watchlist!");
    }

    public function update(Request $request, WatchlistItem $watchlistItem): RedirectResponse
    {
        abort_if($watchlistItem->user_id !== Auth::id(), 403);
        $validated = $this->validateItem($request, updating: true);
        $watchlistItem->update($validated);

        return back()->with('success', 'Watchlist item updated!');
    }

    public function destroy(WatchlistItem $watchlistItem): RedirectResponse
    {
        abort_if($watchlistItem->user_id !== Auth::id(), 403);
        $watchlistItem->delete();

        return back()->with('success', 'Removed from watchlist.');
    }

    private function validateItem(Request $request, bool $updating = false): array
    {
        $rules = [
            'name'         => 'nullable|string|max:255',
            'ticker'       => 'nullable|string|max:32',
            'quantity'     => 'nullable|numeric|min:0',
            'avg_cost'     => 'nullable|numeric|min:0',
            'target_above' => 'nullable|numeric|min:0',
            'target_below' => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ];

        // Symbol and type identify the instrument — they're fixed once added
        if (! $updating) {
            $rules['asset_type'] = 'required|in:stock,crypto';
            $rules['symbol']     = ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9.\-\^=_]+$/'];
        }

        return $request->validate($rules);
    }

    private function normalizeSymbol(string $type, string $symbol): string
    {
        // Yahoo symbols are uppercase (AAPL, ^GSPC, USDPHP=X); CoinGecko ids are lowercase (bitcoin)
        return $type === 'crypto' ? strtolower(trim($symbol)) : strtoupper(trim($symbol));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MarketTest extends TestCase
{
    use RefreshDatabase;

    private function yahooChart(float $price, float $prevClose): array
    {
        return ['chart' => ['result' => [[
            'meta' => [
                'currency' => 'USD', 'symbol' => 'AAPL', 'longName' => 'Apple Inc.',
                'regularMarketPrice' => $price, 'chartPreviousClose' => $prevClose,
                'regularMarketTime' => 1_760_500_000, 'fullExchangeName' => 'NasdaqGS',
            ],
            'timestamp'  => [1_760_490_000, 1_760_490_300, 1_760_490_600],
            'indicators' => ['quote' => [['close' => [$prevClose, null, $price]]]],
        ]], 'error' => null]];
    }

    private function fakeProviders(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/v8/finance/chart/*' => Http::response($this->yahooChart(110, 100)),
            'query1.finance.yahoo.com/v1/finance/search*' => Http::response(['quotes' => [
                ['symbol' => 'AAPL', 'longname' => 'Apple Inc.', 'quoteType' => 'EQUITY', 'exchDisp' => 'NASDAQ'],
                ['symbol' => 'AAPL-NEWS', 'quoteType' => 'NEWS'],
            ]]),
            'api.coingecko.com/api/v3/coins/markets*' => Http::response([[
                'id' => 'bitcoin', 'symbol' => 'btc', 'name' => 'Bitcoin', 'image' => 'https://img/btc.png',
                'current_price' => 60000, 'price_change_24h' => -600, 'price_change_percentage_24h' => -0.99,
                'last_updated' => '2026-10-15T00:00:00Z', 'sparkline_in_7d' => ['price' => range(1, 168)],
            ]]),
            'api.coingecko.com/api/v3/search*' => Http::response(['coins' => [
                ['id' => 'solana', 'symbol' => 'sol', 'name' => 'Solana', 'market_cap_rank' => 5],
            ]]),
            'api.coingecko.com/api/v3/coins/bitcoin/market_chart*' => Http::response(['prices' => [[1000, 1.5], [2000, 2.5]]]),
        ]);
    }

    public function test_user_can_add_watchlist_item_with_encrypted_holdings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/markets', [
            'asset_type' => 'stock', 'symbol' => 'aapl', 'name' => 'Apple Inc.',
            'quantity' => 10, 'avg_cost' => 95.5, 'target_above' => 120,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $item = WatchlistItem::firstOrFail();
        $this->assertSame('AAPL', $item->symbol);
        $this->assertSame('AAPL', $item->ticker);
        $this->assertSame(10.0, $item->quantity);
        $this->assertSame(95.5, $item->avg_cost);

        $raw = DB::table('watchlist_items')->first();
        $this->assertNotSame('10', $raw->quantity);
        $this->assertNotSame('95.5', $raw->avg_cost);

        // Duplicate symbol is rejected
        $this->actingAs($user)->post('/markets', ['asset_type' => 'stock', 'symbol' => 'AAPL'])
            ->assertSessionHasErrors('symbol');
        $this->assertSame(1, WatchlistItem::count());
    }

    public function test_invalid_symbol_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/markets', ['asset_type' => 'stock', 'symbol' => 'AAPL/../x'])
            ->assertSessionHasErrors('symbol');
    }

    public function test_quotes_endpoint_returns_parsed_prices_for_both_providers(): void
    {
        $this->fakeProviders();
        $user  = User::factory()->create();
        $stock = WatchlistItem::create(['user_id' => $user->id, 'asset_type' => 'stock', 'symbol' => 'AAPL', 'ticker' => 'AAPL']);
        $coin  = WatchlistItem::create(['user_id' => $user->id, 'asset_type' => 'crypto', 'symbol' => 'bitcoin', 'ticker' => 'BTC']);
        $missing = WatchlistItem::create(['user_id' => $user->id, 'asset_type' => 'crypto', 'symbol' => 'not-a-coin', 'ticker' => 'NOPE']);

        $res = $this->actingAs($user)->getJson('/markets/quotes')->assertOk();

        $res->assertJsonPath("quotes.{$stock->id}.price", 110.0)
            ->assertJsonPath("quotes.{$stock->id}.change_pct", 10.0)
            ->assertJsonPath("quotes.{$stock->id}.currency", 'USD')
            ->assertJsonPath("quotes.{$stock->id}.sparkline", [100.0, 110.0])
            ->assertJsonPath("quotes.{$coin->id}.price", 60000.0)
            ->assertJsonPath("quotes.{$coin->id}.change_pct", -0.99)
            ->assertJsonPath("quotes.{$missing->id}", null);
        $this->assertCount(48, $res->json("quotes.{$coin->id}.sparkline"));

        // Second call is served from cache
        $this->actingAs($user)->getJson('/markets/quotes')->assertOk();
        Http::assertSentCount(3); // yahoo chart + coingecko markets, then the uncached missing coin retried once
    }

    public function test_quotes_survive_provider_outage(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);
        $user = User::factory()->create();
        $item = WatchlistItem::create(['user_id' => $user->id, 'asset_type' => 'stock', 'symbol' => 'AAPL', 'ticker' => 'AAPL']);

        $this->actingAs($user)->getJson('/markets/quotes')
            ->assertOk()
            ->assertJsonPath("quotes.{$item->id}", null);
    }

    public function test_search_filters_and_normalizes_results(): void
    {
        $this->fakeProviders();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/markets/search?type=stock&q=apple')
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.symbol', 'AAPL')
            ->assertJsonPath('results.0.kind', 'Equity');

        $this->actingAs($user)->getJson('/markets/search?type=crypto&q=sol')
            ->assertOk()
            ->assertJsonPath('results.0.symbol', 'solana')
            ->assertJsonPath('results.0.ticker', 'SOL');
    }

    public function test_history_endpoint_and_ownership(): void
    {
        $this->fakeProviders();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $coin  = WatchlistItem::create(['user_id' => $owner->id, 'asset_type' => 'crypto', 'symbol' => 'bitcoin', 'ticker' => 'BTC']);

        $this->actingAs($owner)->getJson("/markets/{$coin->id}/history?range=1mo")
            ->assertOk()
            ->assertJsonPath('points', [[1000, 1.5], [2000, 2.5]]);

        $this->actingAs($owner)->getJson("/markets/{$coin->id}/history?range=10y")->assertUnprocessable();
        $this->actingAs($other)->getJson("/markets/{$coin->id}/history")->assertForbidden();
        $this->actingAs($other)->delete("/markets/{$coin->id}")->assertForbidden();
        $this->actingAs($other)->put("/markets/{$coin->id}", ['quantity' => 1])->assertForbidden();
    }

    public function test_index_page_only_lists_own_items(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        WatchlistItem::create(['user_id' => $user->id, 'asset_type' => 'stock', 'symbol' => 'VOO', 'ticker' => 'VOO']);
        WatchlistItem::create(['user_id' => $other->id, 'asset_type' => 'stock', 'symbol' => 'TSLA', 'ticker' => 'TSLA']);

        $this->actingAs($user)->get('/markets')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Markets/Index')
                ->has('items', 1)
                ->where('items.0.symbol', 'VOO'));
    }

    public function test_update_and_delete(): void
    {
        $user = User::factory()->create();
        $item = WatchlistItem::create(['user_id' => $user->id, 'asset_type' => 'stock', 'symbol' => 'VOO', 'ticker' => 'VOO']);

        $this->actingAs($user)->put("/markets/{$item->id}", [
            'ticker' => 'VOO', 'quantity' => 2.5, 'target_below' => 400, 'symbol' => 'HACK',
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('VOO', $item->symbol);
        $this->assertSame(2.5, $item->quantity);
        $this->assertSame(400.0, $item->target_below);

        $this->actingAs($user)->delete("/markets/{$item->id}")->assertRedirect();
        $this->assertSame(0, WatchlistItem::count());
    }
}

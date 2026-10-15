<?php

namespace App\Services;

use App\Models\WatchlistItem;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Market data from free, keyless APIs:
 *  - Yahoo Finance (unofficial chart/search endpoints) for stocks, ETFs, indices and forex
 *  - CoinGecko public API for crypto
 * Responses are cached so polling clients stay well under the free rate limits.
 */
class MarketDataService
{
    private const YAHOO     = 'https://query1.finance.yahoo.com';
    private const COINGECKO = 'https://api.coingecko.com/api/v3';
    private const UA        = 'Mozilla/5.0 (compatible; FinanceTracker/1.0)';

    public const RANGES = ['1d', '5d', '1mo', '6mo', '1y'];

    private function ttl(): int
    {
        return (int) config('services.market.cache_seconds', 60);
    }

    private function cryptoCurrency(): string
    {
        return strtolower(config('services.market.crypto_currency', 'usd'));
    }

    /**
     * Latest quotes for a set of watchlist items, keyed by item id (null when unavailable).
     *
     * @param  Collection<int, WatchlistItem>  $items
     */
    public function quotes(Collection $items): array
    {
        $stocks = $this->stockQuotes($items->where('asset_type', 'stock')->pluck('symbol')->unique()->values()->all());
        $crypto = $this->cryptoQuotes($items->where('asset_type', 'crypto')->pluck('symbol')->unique()->values()->all());

        return $items->mapWithKeys(fn (WatchlistItem $item) => [
            $item->id => $item->asset_type === 'crypto'
                ? ($crypto[$item->symbol] ?? null)
                : ($stocks[$item->symbol] ?? null),
        ])->all();
    }

    /** @return array<string, array|null> */
    public function stockQuotes(array $symbols): array
    {
        [$out, $missing] = $this->fromCache('stock', $symbols);
        if (! $missing) return $out;

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $s) => $pool->as($s)
                ->withHeaders(['User-Agent' => self::UA])
                ->timeout(8)
                ->get(self::YAHOO . '/v8/finance/chart/' . rawurlencode($s), ['range' => '1d', 'interval' => '5m']),
            $missing,
        ));

        foreach ($missing as $symbol) {
            $res   = $responses[$symbol] ?? null;
            $quote = $res instanceof Response && $res->successful() ? $this->parseYahooQuote($res->json()) : null;
            if ($quote) Cache::put($this->cacheKey('stock', $symbol), $quote, $this->ttl());
            $out[$symbol] = $quote;
        }

        return $out;
    }

    /** @return array<string, array|null> */
    public function cryptoQuotes(array $ids): array
    {
        [$out, $missing] = $this->fromCache('crypto', $ids);
        if (! $missing) return $out;

        $currency = $this->cryptoCurrency();
        $rows = [];
        try {
            $res = Http::timeout(8)->get(self::COINGECKO . '/coins/markets', [
                'vs_currency'             => $currency,
                'ids'                     => implode(',', $missing),
                'sparkline'               => 'true',
                'price_change_percentage' => '24h',
            ]);
            if ($res->successful()) $rows = collect($res->json())->keyBy('id');
        } catch (\Throwable) {
            // Network failure — leave quotes empty, client keeps last known values
        }

        foreach ($missing as $id) {
            $row   = $rows[$id] ?? null;
            $quote = $row ? $this->parseCoinGeckoRow($row, $currency) : null;
            if ($quote) Cache::put($this->cacheKey('crypto', $id), $quote, $this->ttl());
            $out[$id] = $quote;
        }

        return $out;
    }

    /**
     * Price history as [[timestamp_ms, price], ...].
     */
    public function history(string $assetType, string $symbol, string $range): array
    {
        $key = $this->cacheKey("history:$assetType:$range", $symbol);

        if (($cached = Cache::get($key)) !== null) return $cached;

        try {
            $points = $assetType === 'crypto'
                ? $this->cryptoHistory($symbol, $range)
                : $this->stockHistory($symbol, $range);
        } catch (\Throwable) {
            $points = [];
        }

        if ($points) Cache::put($key, $points, max($this->ttl(), 300));

        return $points;
    }

    public function search(string $assetType, string $query): array
    {
        $key = $this->cacheKey("search:$assetType", strtolower($query));

        return Cache::remember($key, 3600, function () use ($assetType, $query) {
            try {
                return $assetType === 'crypto' ? $this->searchCrypto($query) : $this->searchStocks($query);
            } catch (\Throwable) {
                return [];
            }
        });
    }

    // ── Yahoo Finance ──────────────────────────────────────────────

    private function parseYahooQuote(?array $json): ?array
    {
        $result = $json['chart']['result'][0] ?? null;
        $meta   = $result['meta'] ?? null;
        $price  = $meta['regularMarketPrice'] ?? null;
        if ($price === null) return null;

        $prev   = $meta['chartPreviousClose'] ?? $meta['previousClose'] ?? null;
        $change = $prev ? $price - $prev : null;
        $closes = array_values(array_filter(
            $result['indicators']['quote'][0]['close'] ?? [],
            fn ($v) => $v !== null,
        ));

        return [
            'price'           => (float) $price,
            'change'          => $change !== null ? round($change, 6) : null,
            'change_pct'      => $prev ? round($change / $prev * 100, 2) : null,
            'currency'        => strtoupper($meta['currency'] ?? 'USD'),
            'name'            => $meta['longName'] ?? $meta['shortName'] ?? null,
            'exchange'        => $meta['fullExchangeName'] ?? $meta['exchangeName'] ?? null,
            'sparkline'       => $this->downsample($closes),
            'sparkline_range' => '1D',
            'as_of'           => isset($meta['regularMarketTime'])
                ? date(DATE_ATOM, (int) $meta['regularMarketTime'])
                : now()->toAtomString(),
        ];
    }

    private function stockHistory(string $symbol, string $range): array
    {
        $interval = ['1d' => '5m', '5d' => '30m', '1mo' => '1d', '6mo' => '1d', '1y' => '1wk'][$range] ?? '1d';

        $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(8)
            ->get(self::YAHOO . '/v8/finance/chart/' . rawurlencode($symbol), compact('range', 'interval'));
        if (! $res->successful()) return [];

        $result = $res->json('chart.result.0') ?? [];
        $times  = $result['timestamp'] ?? [];
        $closes = $result['indicators']['quote'][0]['close'] ?? [];

        $points = [];
        foreach ($times as $i => $t) {
            if (isset($closes[$i])) $points[] = [$t * 1000, (float) $closes[$i]];
        }

        return $points;
    }

    private function searchStocks(string $query): array
    {
        $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(8)
            ->get(self::YAHOO . '/v1/finance/search', ['q' => $query, 'quotesCount' => 10, 'newsCount' => 0]);
        if (! $res->successful()) return [];

        return collect($res->json('quotes') ?? [])
            ->filter(fn ($q) => isset($q['symbol']) && in_array($q['quoteType'] ?? '', ['EQUITY', 'ETF', 'INDEX', 'CURRENCY', 'MUTUALFUND', 'FUTURE']))
            ->map(fn ($q) => [
                'symbol'   => $q['symbol'],
                'ticker'   => $q['symbol'],
                'name'     => $q['longname'] ?? $q['shortname'] ?? $q['symbol'],
                'exchange' => $q['exchDisp'] ?? $q['exchange'] ?? null,
                'kind'     => ucfirst(strtolower($q['quoteType'])),
            ])
            ->values()->all();
    }

    // ── CoinGecko ──────────────────────────────────────────────────

    private function parseCoinGeckoRow(array $row, string $currency): ?array
    {
        if (! isset($row['current_price'])) return null;

        return [
            'price'           => (float) $row['current_price'],
            'change'          => isset($row['price_change_24h']) ? (float) $row['price_change_24h'] : null,
            'change_pct'      => isset($row['price_change_percentage_24h']) ? round($row['price_change_percentage_24h'], 2) : null,
            'currency'        => strtoupper($currency),
            'name'            => $row['name'] ?? null,
            'exchange'        => 'CoinGecko',
            'image'           => $row['image'] ?? null,
            'sparkline'       => $this->downsample($row['sparkline_in_7d']['price'] ?? []),
            'sparkline_range' => '7D',
            'as_of'           => $row['last_updated'] ?? now()->toAtomString(),
        ];
    }

    private function cryptoHistory(string $id, string $range): array
    {
        $days = ['1d' => 1, '5d' => 5, '1mo' => 30, '6mo' => 180, '1y' => 365][$range] ?? 1;

        $res = Http::timeout(8)->get(self::COINGECKO . '/coins/' . rawurlencode($id) . '/market_chart', [
            'vs_currency' => $this->cryptoCurrency(),
            'days'        => $days,
        ]);
        if (! $res->successful()) return [];

        return array_map(fn ($p) => [(int) $p[0], (float) $p[1]], $res->json('prices') ?? []);
    }

    private function searchCrypto(string $query): array
    {
        $res = Http::timeout(8)->get(self::COINGECKO . '/search', ['query' => $query]);
        if (! $res->successful()) return [];

        return collect($res->json('coins') ?? [])
            ->take(10)
            ->map(fn ($c) => [
                'symbol'   => $c['id'],
                'ticker'   => strtoupper($c['symbol'] ?? $c['id']),
                'name'     => $c['name'] ?? $c['id'],
                'exchange' => isset($c['market_cap_rank']) ? '#' . $c['market_cap_rank'] : null,
                'kind'     => 'Crypto',
            ])
            ->values()->all();
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function cacheKey(string $scope, string $symbol): string
    {
        return "market:$scope:" . strtolower($symbol);
    }

    /** @return array{0: array<string, array>, 1: string[]} */
    private function fromCache(string $scope, array $symbols): array
    {
        $out = [];
        $missing = [];
        foreach ($symbols as $s) {
            $cached = Cache::get($this->cacheKey($scope, $s));
            $cached !== null ? $out[$s] = $cached : $missing[] = $s;
        }

        return [$out, $missing];
    }

    /** Keep sparklines small — ~48 points is plenty for a mini chart. */
    private function downsample(array $values, int $max = 48): array
    {
        $values = array_values(array_map('floatval', $values));
        $n = count($values);
        if ($n <= $max) return $values;

        $step = ($n - 1) / ($max - 1);
        $out = [];
        for ($i = 0; $i < $max; $i++) $out[] = $values[(int) round($i * $step)];

        return $out;
    }
}

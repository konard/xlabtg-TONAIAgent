# LOGIC-69 — Market-data service caches fallback-provider prices under a key it never reads, so a primary-provider outage re-hits both providers on every call

**Severity:** 🟠 Medium
**Area:** Reliability
**Stage:** Stage 4 — Market data & connector integrity
**Suggested labels:** `bug`, `reliability`, `severity:medium`, `area:market-data`, `stage:4-connector-integrity`, `audit:logic-review-v3`
**Location:** `core/market-data/base/service.ts:159-217` (same defect in getTicker ~:229-276)
**Filed as:** [#517](https://github.com/xlabtg/TONAIAgent/issues/517)

## Problem
Reads check `price:${primaryProvider}:${asset}` but a successful fallback fetch is stored under `price:${fallbackName}:${asset}` — a key no read path ever checks. The fallback result is cached under a key that is never consulted, so it can never produce a cache hit.

## Evidence
```ts
    const cacheKey = `price:${this.config.primaryProvider}:${asset.toUpperCase()}`;

    // Cache hit
    if (this.priceCache.has(cacheKey)) {
      const cached = this.priceCache.get(cacheKey)!;
      this.emitEvent('price.cache_hit', asset, cached.source, { asset, source: cached.source });
      return { price: cached, fromCache: true, usedFallback: false };
    }
```
```ts
      const fallbackCacheKey = `price:${fallbackName}:${asset.toUpperCase()}`;

      if (fallback) {
        try {
          const price = await fallback.getPrice(asset);
          this.priceCache.set(fallbackCacheKey, price);
```

## Impact
With the primary down, every getPrice/getSnapshot again calls the failing primary then the fallback (Binance), providing zero cache benefit and amplifying latency and rate-limit-ban risk exactly when the system is degraded.

## Suggested fix
Use a provider-agnostic cache key (e.g. `price:${asset}`) for read and write, or populate the primary key after a successful fallback fetch.

## Acceptance criteria
- [ ] During a primary outage a second call within TTL is served from cache
- [ ] Both providers are not re-hit on cached reads
- [ ] Regression test: with a failing primary asserts one fallback fetch across two calls
```

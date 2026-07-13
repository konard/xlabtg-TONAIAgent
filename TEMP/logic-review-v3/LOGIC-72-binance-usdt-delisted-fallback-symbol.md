# LOGIC-72 — Binance fallback symbol for USDT is the delisted USDTBUSD pair, so USDT price lookups via the fallback provider always fail

**Severity:** 🟡 Low
**Area:** Reliability
**Stage:** Stage 4 — Market data & connector integrity
**Suggested labels:** `bug`, `reliability`, `severity:low`, `area:market-data`, `stage:4-connector-integrity`, `audit:logic-review-v3`
**Location:** `core/market-data/base/config/assets.ts:60`
**Filed as:** _ready to file_

## Problem
`BINANCE_SYMBOLS['USDT'] = 'USDTBUSD'` maps USDT to a BUSD pair Binance delisted, so any fallback lookup for USDT targets a symbol Binance no longer serves.

## Evidence
```ts
export const BINANCE_SYMBOLS: Record<string, string> = {
  BTC: 'BTCUSDT',
  ETH: 'ETHUSDT',
  TON: 'TONUSDT',
  SOL: 'SOLUSDT',
  USDT: 'USDTBUSD',
};
```

## Impact
When CoinGecko is down and the Binance fallback is used for USDT, the request for USDTBUSD errors and getPrice('USDT') throws ALL_PROVIDERS_FAILED even though USDT is a core stablecoin; in getSnapshot USDT is dropped, so USDT-normalizing strategies lose their reference asset during an outage.

## Suggested fix
Map USDT to a live pair (e.g. USDCUSDT, inverting the rate) or special-case USDT to a constant 1.0 in the fallback path.

## Acceptance criteria
- [ ] USDT price is resolvable via the Binance fallback
- [ ] getSnapshot retains USDT during a primary outage
- [ ] Regression test: asserts a USDT price from the fallback path
```

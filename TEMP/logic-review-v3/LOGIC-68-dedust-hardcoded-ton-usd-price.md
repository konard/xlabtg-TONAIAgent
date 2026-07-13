# LOGIC-68 — DeDust connector prices any TON-quoted token using a hardcoded $5.00 TON/USD constant, producing arbitrarily wrong USD prices

**Severity:** 🔴 High
**Area:** Financial
**Stage:** Stage 4 — Market data & connector integrity
**Suggested labels:** `bug`, `financial`, `severity:high`, `area:market-data`, `stage:4-connector-integrity`, `audit:logic-review-v3`
**Location:** `core/market-data/base/connectors/dedust.ts:486-490`
**Filed as:** _ready to file_

## Problem
In `convertToUsd`, a TON-quoted price is converted with `return price * 5.0` — a literal placeholder TON/USD rate — on the live getPrice path. The constant is never sourced from any real market feed, so every TON-quoted token is valued against a fixed, fictional TON price.

## Evidence
```ts
    // For TON, we'd need another source for TON/USD
    // For now, use a placeholder (in production, integrate with external oracle)
    if (normalizedQuote === 'TON') {
      // This is a simplified placeholder
      // In production, fetch TON/USD from CoinGecko or another reliable source
      return price * 5.0; // Approximate TON price, should be dynamic
    }
```

## Impact
A token priced 0.5 TON is reported as $2.50 regardless of the real TON price (~$3–$7), a 40%+ error in either direction. Every downstream valuation, PnL or trigger comparison consuming this connector for a TON-quoted asset is silently miscalibrated.

## Suggested fix
Fetch TON/USD from the real price service (the CoinGecko provider already exists) or an oracle; if unavailable, return null so the caller treats the price as unavailable rather than fabricated.

## Acceptance criteria
- [ ] TON-quoted conversion uses a live TON/USD rate
- [ ] When no TON/USD source is available getPrice yields null/unavailable rather than a fabricated value
- [ ] Regression test: asserts no hardcoded 5.0 multiplier is used
```

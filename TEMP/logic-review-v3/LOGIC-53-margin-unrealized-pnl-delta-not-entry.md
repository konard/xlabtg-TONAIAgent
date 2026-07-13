# LOGIC-53 — Margin unrealized PnL is recomputed from the previous tick instead of the entry price, understating losses at liquidation

**Severity:** 🔴 High
**Area:** Financial
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:high`, `area:financial`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `services/prime-brokerage/margin-leverage.ts:343-352`
**Filed as:** [#501](https://github.com/xlabtg/TONAIAgent/issues/501)

## Problem
`updatePositionPrice` sets `priceDiff = currentPrice - position.currentPrice` (the delta since the LAST update) then overwrites `position.currentPrice`, so `unrealizedPnL` reflects only the change since the previous tick, not cumulative PnL from `entryPrice`. Because `position.currentPrice` is mutated on every tick, each recomputation loses the prior movement. Liquidation later reads this field for `realizedLoss` and remaining equity.

## Evidence
```ts
    const priceDiff = currentPrice - position.currentPrice; // delta since LAST update, not entryPrice
    position.currentPrice = currentPrice;
    position.unrealizedPnL = position.direction === 'long'
      ? priceDiff * position.size
      : -priceDiff * position.size;                          // PnL only reflects this single tick
    position.notionalValue = currentPrice * position.size;
```

## Impact
Entry 100 size 10; tick to 90 → `unrealizedPnL` -100; tick to 85 → `priceDiff` -5 → `unrealizedPnL` -50 though the true loss from entry is -150. Liquidation records `realizedLoss` 50 and remaining equity is overstated; per-position risk / margin-call logic reading `unrealizedPnL` is wrong after the second update, letting under-collateralized positions escape liquidation.

## Suggested fix
Compute `unrealizedPnL = (currentPrice - position.entryPrice) * size` (sign-adjusted for direction) so the field always reflects PnL from entry.

## Acceptance criteria
- [ ] After two successive price updates `unrealizedPnL` equals `(price - entry) * size`
- [ ] Liquidation `realizedLoss` equals the cumulative loss from entry
- [ ] Regression test: two decreasing ticks assert the cumulative figure

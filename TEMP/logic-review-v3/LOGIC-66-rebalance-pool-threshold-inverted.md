# LOGIC-66 — rebalancePool uses the 0.1 rebalanceThreshold as a low-utilization floor, so the "underutilized → skip" bypass almost never triggers and rebalancing fires for nearly any active pool

**Severity:** 🟡 Low
**Area:** Strategy
**Stage:** Stage 5 — Strategy / backtest / optimizer integrity
**Suggested labels:** `bug`, `reliability`, `severity:low`, `area:strategy`, `stage:5-strategy-integrity`, `audit:logic-review-v3`
**Location:** `core/multi-agent/resources/capital-manager.ts:348-353` (default at `:49`)
**Filed as:** _ready to file_

## Problem
The skip guard `if (utilizationRatio < pool.limits.rebalanceThreshold) return;` uses a default `rebalanceThreshold` of `0.1`. Any pool utilized above 10% (essentially all active pools) fails the "underutilized" test and proceeds to trim allocations by 20%. The threshold value and comparison semantics are inverted relative to a "rebalance only when heavily utilized" intent.

## Evidence
```ts
// core/multi-agent/resources/capital-manager.ts:348-353
const utilizationRatio = totalAllocated / (pool.totalCapital - pool.reservedCapital);

if (utilizationRatio < pool.limits.rebalanceThreshold) {
  // Pool is underutilized, no action needed
  return;
}
```

```ts
// default — core/multi-agent/resources/capital-manager.ts:49
rebalanceThreshold: 0.1,
```

## Impact
Because the guard is nearly a no-op for any normally-utilized pool (>10%), rebalancing runs far more aggressively than intended — allocations are trimmed on essentially every `rebalancePool` call rather than only when a pool is genuinely over-utilized.

## Suggested fix
Either raise `rebalanceThreshold` to the intended high-utilization value (e.g. `0.8`) or invert the comparison, so the constant and the comparison agree with the documented "rebalance only when heavily utilized" intent.

## Acceptance criteria
- [ ] A lightly-utilized pool skips rebalancing while a heavily-utilized pool rebalances (or vice-versa, per the documented intent).
- [ ] The constant and the comparison operator are consistent with each other.
- [ ] Regression test: assert the skip path for the intended utilization band.

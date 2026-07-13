# LOGIC-65 — Monte-Carlo CVaR divides by cvarIndex without the zero guard used elsewhere, producing NaN for small simulation counts or high confidence

**Severity:** 🟠 Medium
**Area:** Strategy
**Stage:** Stage 5 — Strategy / backtest / optimizer integrity
**Suggested labels:** `bug`, `reliability`, `severity:medium`, `area:strategy`, `stage:5-strategy-integrity`, `audit:logic-review-v3`
**Location:** `core/strategies/engine/backtesting.ts:893-900` (contrast the guarded daily-returns CVaR at `:815-817`)
**Filed as:** [#513](https://github.com/xlabtg/TONAIAgent/issues/513)

## Problem
In `runMonteCarlo`, `cvarIndex = Math.floor(distribution.length * (1 - confidenceLevel))`, and the CVaR mean is `distribution.slice(0, cvarIndex).reduce(...) / cvarIndex` with no `cvarIndex > 0` guard. When `cvarIndex` is 0 this divides 0 by 0.

## Evidence
```ts
const varIndex = Math.floor(distribution.length * (1 - config.confidenceLevel));
const cvarIndex = Math.floor(distribution.length * (1 - config.confidenceLevel));

return {
  simulations: config.simulations,
  expectedReturn: distribution.reduce((a, b) => a + b, 0) / distribution.length * 100,
  var95: distribution[varIndex] ?? 0,
  cvar95: distribution.slice(0, cvarIndex).reduce((a, b) => a + b, 0) / cvarIndex,
```

The daily-returns CVaR in the same file guards correctly:
```ts
// core/strategies/engine/backtesting.ts:815-817
const cvar95 = var95Index > 0
  ? sortedReturns.slice(0, var95Index).reduce((a, b) => a + b, 0) / var95Index
  : var95;
```

## Impact
With `simulations: 50` and `confidenceLevel: 0.99`, `cvarIndex = floor(50 * 0.01) = 0`; `slice(0, 0)` sums to 0, divided by 0 → `cvar95` is `NaN`. That `NaN` poisons the `MonteCarloResult` and any downstream risk gating that consumes it. The daily-returns path is guarded; this Monte-Carlo path is not.

## Suggested fix
Guard `cvarIndex` the same way as the daily-returns branch: when `cvarIndex` is 0, fall back to the VaR value (`distribution[varIndex] ?? 0`) or 0 instead of dividing.

## Acceptance criteria
- [ ] Monte-Carlo with 50 simulations at 0.99 confidence returns a finite `cvar95`.
- [ ] No `NaN` appears anywhere in the returned `MonteCarloResult`.
- [ ] Regression test: assert `cvar95` finiteness for small simulation counts / high confidence levels.

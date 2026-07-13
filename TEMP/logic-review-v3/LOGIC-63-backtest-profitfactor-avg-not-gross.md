# LOGIC-63 — Engine backtest reports profitFactor as the average win/loss payoff ratio instead of gross profit / gross loss, ignoring trade counts

**Severity:** 🟠 Medium
**Area:** Strategy
**Stage:** Stage 5 — Strategy / backtest / optimizer integrity
**Suggested labels:** `bug`, `financial`, `severity:medium`, `area:strategy`, `stage:5-strategy-integrity`, `audit:logic-review-v3`
**Location:** `core/strategies/engine/backtesting.ts:803-809` (used at `:843`; correct formula exists at `core/strategies/backtesting/performance-analysis.ts:418-420`)
**Filed as:** _ready to file_

## Problem
The engine backtest computes `profitFactor = avgWin / avgLoss` using per-trade averages instead of the standard `grossProfit / grossLoss`. Because both sides are averaged over their own trade counts, the number of winning vs. losing trades is cancelled out and ignored.

## Evidence
```ts
const avgWin = winningTrades.length > 0
  ? winningTrades.reduce((sum, t) => sum + (t.pnl ?? 0), 0) / winningTrades.length
  : 0;
const avgLoss = losingTrades.length > 0
  ? Math.abs(losingTrades.reduce((sum, t) => sum + (t.pnl ?? 0), 0) / losingTrades.length)
  : 0;
const profitFactor = avgLoss > 0 ? avgWin / avgLoss : avgWin;
```

The sibling implementation already does it correctly:
```ts
// core/strategies/backtesting/performance-analysis.ts:418-420
const grossProfit = winningTrades.reduce((sum, t) => sum + t.pnl, 0);
const grossLoss = Math.abs(losingTrades.reduce((sum, t) => sum + t.pnl, 0));
const profitFactor = grossLoss > 0 ? grossProfit / grossLoss : grossProfit > 0 ? Infinity : 0;
```

## Impact
Consider 8 wins totaling $800 (avgWin 100) and 2 losses totaling $200 (avgLoss 100): the true profit factor is 800/200 = 4.0, but this code reports 1.0 (break-even). It systematically understates profitability whenever winners outnumber losers, misleading strategy ranking and selection driven by `profitFactor`.

## Suggested fix
Compute `grossProfit = sum(winning pnl)`, `grossLoss = |sum(losing pnl)|`, and `profitFactor = grossLoss > 0 ? grossProfit / grossLoss : (grossProfit > 0 ? Infinity : 0)`, matching `performance-analysis.ts`.

## Acceptance criteria
- [ ] `profitFactor` equals `grossProfit / grossLoss`.
- [ ] The 8-win/2-loss example yields 4.0.
- [ ] Regression test: assert the gross-based value for a mixed win/loss trade set.

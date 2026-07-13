# LOGIC-75 — Agent-runtime dailyLoss is never accumulated, so the daily-loss risk limit is completely unenforced

**Severity:** 🔴 High
**Area:** Financial
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:high`, `area:runtime`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `core/agents/agent-runtime/orchestrator.ts:583` (init `:192`)
**Filed as:** _ready to file_

## Problem
`stepValidateRisk` gates trading on `state.dailyLoss < config.riskLimits.maxDailyLossNano`, but `state.dailyLoss` is only ever set to `BigInt(0)` at initialization (:192) and is never incremented anywhere in the runtime. On-chain execution updates `dailyGasUsed` and `dailyTransactionCount` but never touches `dailyLoss`. A repo search of `core/agents/agent-runtime/orchestrator.ts` confirms `dailyLoss` appears only at the init site and the comparison site.

Note: this is a distinct location from the previously-reported risk-engine daily-loss issue (`core/risk-engine/trade-validator.ts`); this is the agent-runtime orchestrator's own risk gate.

## Evidence
```ts
// core/agents/agent-runtime/orchestrator.ts:583-584 — the gate
    const dailyLossOk = state.dailyLoss < config.riskLimits.maxDailyLossNano;
    checks.push({ rule: 'daily_loss_limit', passed: dailyLossOk, reason: dailyLossOk ? undefined : 'Daily loss limit reached' });

// :192 — the only place dailyLoss is ever assigned
      dailyLoss: BigInt(0),
```

## Impact
An agent configured with `maxDailyLossNano` (e.g. "cap losses at 10 TON/day") can sustain arbitrarily large realized losses while the `daily_loss_limit` check always passes, because `dailyLoss` stays at 0 forever. The primary per-agent capital-protection limit provides no protection at all. Under live funds this escalates to Critical.

## Suggested fix
Accumulate realized loss into `state.dailyLoss` after each execution outcome (in `stepExecuteOnchain` / `stepRecordOutcome`), so that realized PnL losses roll into the counter that `stepValidateRisk` checks.

## Acceptance criteria
- [ ] Realized losses increase `state.dailyLoss`.
- [ ] Once cumulative daily loss reaches `maxDailyLossNano`, the `daily_loss_limit` check fails and blocks further trading.
- [ ] Regression test: drive realized losses past the configured limit and assert trading is blocked.

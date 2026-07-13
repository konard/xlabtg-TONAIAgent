# LOGIC-76 — dailyGasUsed and dailyTransactionCount are never reset at a day boundary, turning "daily" limits into permanent lifetime caps that brick the agent

**Severity:** 🟠 Medium
**Area:** Reliability
**Stage:** Stage 3 — Runtime concurrency & daily-limit resets
**Suggested labels:** `bug`, `reliability`, `severity:medium`, `area:runtime`, `stage:3-runtime-concurrency`, `audit:logic-review-v3`
**Location:** `core/agents/agent-runtime/orchestrator.ts:712-713,736-737` (checks `:587`, `:595`)
**Filed as:** _ready to file_

## Problem
`dailyGasUsed` and `dailyTransactionCount` are incremented on every executed cycle (both in the simulation path, :712-713, and the real on-chain path, :736-737) and compared against `maxDailyGasBudgetNano` / `maxTransactionsPerDay` in `stepValidateRisk`. But there is no `dailyReset` / `dailyWindow` field or any reset logic anywhere — a search of the orchestrator finds no assignment that ever zeroes these counters. The values only grow.

## Evidence
```ts
// core/agents/agent-runtime/orchestrator.ts:712-713 — simulation path increments
      state.dailyGasUsed += estimatedGas;
      state.dailyTransactionCount += transactions.length;

// :736-737 — real on-chain path increments
    state.dailyGasUsed += estimatedGas;
    state.dailyTransactionCount += transactions.length;

// :587 — the daily gas budget check
    const gasOk = state.dailyGasUsed < config.riskLimits.maxDailyGasBudgetNano;

// :595 — the daily transaction count check
    const txCountOk = state.dailyTransactionCount < config.riskLimits.maxTransactionsPerDay;
```
No `dailyReset`, `dailyWindow`, or `dailyGasUsed =` / `dailyTransactionCount = 0` reset exists anywhere in the orchestrator.

## Impact
Once cumulative gas or transaction count crosses the configured daily budget, `stepValidateRisk` fails (`RISK_VALIDATION_FAILED`) on every subsequent cycle forever. The "daily" budget never rolls over, so instead of pausing trading for the rest of the day and resuming tomorrow, the agent is permanently bricked.

## Suggested fix
Track a `dailyWindowStart` timestamp on the state and reset `dailyGasUsed`, `dailyTransactionCount` (and `dailyLoss`, see LOGIC-75) to zero at the start of `stepValidateRisk` (or on a scheduler tick) whenever the current UTC day differs from the window start.

## Acceptance criteria
- [ ] Crossing the daily budget blocks trading only for the remainder of the current UTC day.
- [ ] Counters reset at the next UTC day boundary and trading resumes.
- [ ] Regression test: advance the clock past a day boundary and assert the counters reset and trading resumes.

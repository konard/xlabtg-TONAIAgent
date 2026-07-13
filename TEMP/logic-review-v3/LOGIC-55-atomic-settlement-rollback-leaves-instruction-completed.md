# LOGIC-55 — Failed all-or-nothing atomic settlement rolls back leg status but leaves the underlying instruction 'completed', inflating settled-value metrics

**Severity:** 🟠 Medium
**Area:** Financial
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:medium`, `area:financial`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `services/clearing-house/settlement.ts:446-461`
**Filed as:** [#503](https://github.com/xlabtg/TONAIAgent/issues/503)

## Problem
On a failed `allOrNothing` atomic settlement the rollback sets each completed `leg.status = 'cancelled'` but never touches `leg.instruction.status`; the same `SettlementInstruction` object remains `'completed'` in `this.settlements`, which is what metrics aggregate. The leg-level cancellation is invisible to reporting because metrics read the instruction status, not the leg status.

## Evidence
```ts
    if (atomicSettlement.allOrNothing && !allSuccess) {
      // Rollback: cancel all completed legs
      for (const leg of atomicSettlement.legs) {
        if (leg.status === 'completed') {
          // In a real implementation, this would trigger on-chain reversal
          leg.status = 'cancelled';   // only the leg is cancelled; leg.instruction.status stays 'completed'
        }
      }
      atomicSettlement.status = 'failed';
    }
```
```ts
    const completedSettlements = settlements.filter(s => s.status === 'completed');  // still counts rolled-back instructions
    const totalSettledValue = completedSettlements.reduce((sum, s) => sum + s.amount, 0);
```

## Impact
A 3-leg atomic settlement that completes legs 1-2 then fails leg 3 marks the wrapper `failed`, but the legs 1-2 instructions stay `completed`, so `getSettlementMetrics` counts their amounts in `totalSettledValue` for a transaction that was supposed to be atomically reversed — corrupting settlement reporting and reconciliation.

## Suggested fix
On rollback also set each affected `leg.instruction.status = 'cancelled'` (and clear `txHash`/`completedAt`) so metrics and downstream accounting exclude reversed legs.

## Acceptance criteria
- [ ] After a failed atomic settlement no constituent instruction remains `'completed'`
- [ ] `getSettlementMetrics` excludes reversed legs from `totalSettledValue`
- [ ] Regression test: a failing 3rd leg asserts settled value is 0

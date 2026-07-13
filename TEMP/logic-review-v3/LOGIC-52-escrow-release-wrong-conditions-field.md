# LOGIC-52 — Escrow release evaluates the wrong conditions field, so held funds are always released regardless of release conditions

**Severity:** 🔴 High
**Area:** Financial
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:high`, `area:financial`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `services/payments/payment-gateway.ts:755-784`
**Filed as:** _ready to file_

## Problem
`releaseEscrow()` guards on `payment.escrow.releaseConditions.length > 0` then calls `evaluateConditions(paymentId)`, but `evaluateConditions` inspects `payment.conditions` — a different field. `createEscrowPayment` only populates `payment.escrow.releaseConditions`, never `payment.conditions`, so `evaluateConditions` hits the empty-array early return and yields `allConditionsMet: true` unconditionally. The release guard is therefore effectively dead: it passes even when no configured release condition has been met.

## Evidence
```ts
    // Check conditions if they exist
    if (payment.escrow.releaseConditions.length > 0) {   // guards on escrow.releaseConditions
      const evaluation = await this.evaluateConditions(paymentId);  // but this reads payment.conditions
      if (!evaluation.allConditionsMet) {
        throw new Error('Release conditions not met');
      }
    }
```
```ts
  async evaluateConditions(paymentId: string): Promise<ConditionEvaluationResult> {
    const payment = await this.getPaymentOrThrow(paymentId);

    if (!payment.conditions || payment.conditions.length === 0) {  // wrong field: escrow uses escrow.releaseConditions
      return {
        paymentId,
        conditions: [],
        allConditionsMet: true,   // unconditional "met" for escrow payments
        canExecute: true,
      };
    }
```

## Impact
Any caller of `releaseEscrow(paymentId)` passes the guard even when no release condition was ever met, so escrowed funds are released to the recipient with zero condition enforcement. Under real funds this is a direct loss-of-funds / escrow-bypass defect: a counterparty can extract held funds before delivering on the escrow's terms.

## Suggested fix
Evaluate `payment.escrow.releaseConditions` directly (or copy them into `payment.conditions` at creation) and require every condition `status === 'met'` before release.

## Acceptance criteria
- [ ] `releaseEscrow` throws 'Release conditions not met' when an escrow's `releaseConditions` are unmet
- [ ] `releaseEscrow` releases only when all `releaseConditions` are met
- [ ] Regression test: fund an escrow with one unmet condition and assert release is rejected

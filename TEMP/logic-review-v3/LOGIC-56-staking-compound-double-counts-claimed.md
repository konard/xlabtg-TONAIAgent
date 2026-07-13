# LOGIC-56 — Staking compoundRewards adds reinvested rewards to claimedRewards (double counting) and reports a total that includes non-compounded stakes

**Severity:** 🟡 Low
**Area:** Financial
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:low`, `area:financial`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `extended/tokenomics/staking.ts:467-494`
**Filed as:** [#504](https://github.com/xlabtg/TONAIAgent/issues/504)

## Problem
`compoundRewards` folds `pendingRewards` into principal (`stake.amount`) and simultaneously adds the same amount to `stake.claimedRewards`, so the rewards are counted both as future-earning principal and as lifetime claimed payouts. The returned `amount` is `position.pendingRewards` (the sum across ALL stakes) even though non-`autoCompound` stakes were skipped by the loop.

## Evidence
```ts
    // Add pending rewards to stake
    for (const stake of position.stakes) {
      if (stake.autoCompound) {
        stake.amount = (BigInt(stake.amount) + BigInt(stake.pendingRewards)).toString();
        stake.claimedRewards = (BigInt(stake.claimedRewards) + BigInt(stake.pendingRewards)).toString(); // double counts: reinvested AND "claimed"
        stake.pendingRewards = '0';
        stake.updatedAt = new Date();
      }
    }

    const breakdown = await this.calculateRewardBreakdown(userId);

    return {
      success: true,
      amount: position.pendingRewards,   // sum across ALL stakes, including skipped non-autoCompound ones
      rewardBreakdown: breakdown,
    };
```

## Impact
One auto-compound stake (pending 100) plus one non-compound stake (pending 40): compound reinvests 100 but reports `amount` 140 and inflates `claimedRewards` by 100 for a payout that never occurred, corrupting reward analytics and payout reconciliation.

## Suggested fix
Do not increment `claimedRewards` on compound (reinvestment is not a claim); return only the actually-compounded total (sum over `autoCompound` stakes).

## Acceptance criteria
- [ ] `compoundRewards` leaves `claimedRewards` unchanged
- [ ] Returned `amount` equals the sum of compounded (`autoCompound`) pending rewards only
- [ ] Regression test: mixed stakes assert both

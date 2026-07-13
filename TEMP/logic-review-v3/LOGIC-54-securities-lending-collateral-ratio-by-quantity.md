# LOGIC-54 — Securities-lending collateralization ratio divides collateral USD value by loan quantity (token count) instead of loan value, allowing grossly under-collateralized loans

**Severity:** 🔴 High
**Area:** Financial
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:high`, `area:financial`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `services/prime-brokerage/securities-lending.ts:344-352`
**Filed as:** _ready to file_

## Problem
The collateralization check computes `collRatio = collateralValue / params.quantity`, dividing a USD value by a bare token count, while the same module values the loan at `quantity * 100` (a unit price) when accruing interest. The check therefore requires collateral of only `minCollateralizationRatio * quantity` USD against a loan worth roughly `100 * quantity` USD — off by the unit price factor.

## Evidence
```ts
    // Check collateralization ratio
    // Compare collateral USD value against loan quantity (unit: tokens)
    // Ratio = collateralValueUsd / loanQuantity (higher is better)
    const collateralValue = params.collateral.value;
    const collRatio = collateralValue / params.quantity;   // divides USD by token count, not loan value

    if (collRatio < this.config.minCollateralizationRatio) {
      throw new Error(
        `Insufficient collateral: ${collRatio.toFixed(2)} ratio, minimum ${this.config.minCollateralizationRatio}`
      );
    }
```
```ts
    const loanValue = agreement.quantity * 100; // Simplified price — same $100 unit price ignored by the ratio check
```

## Impact
Borrow 10 tokens (loan value ~$1,000 at the module's own $100 price); `collateral.value` 15 gives `collRatio` 1.5, which passes the 150% minimum, though true collateralization is 15/1000 = 1.5%. On default the lender recovers roughly 1.5% of the loan — a direct real-funds loss for the lending side.

## Suggested fix
Divide by loan value: `collRatio = collateralValue / (params.quantity * unitPrice)`, using a single consistent price source for both the check and interest accrual.

## Acceptance criteria
- [ ] `openLoan` rejects when `collateralValue < minRatio * quantity * unitPrice`
- [ ] A loan passing the check is collateralized to at least `minRatio` of loan value
- [ ] Regression test: a 1.5%-collateralized loan is rejected

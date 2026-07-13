# LOGIC-70 — ChangeNOW getEstimate ignores its reverse mode and always calls the direct endpoint, returning wrong amounts for reverse quotes

**Severity:** 🟠 Medium
**Area:** Financial
**Stage:** Stage 4 — Market data & connector integrity
**Suggested labels:** `bug`, `financial`, `severity:medium`, `area:connectors`, `stage:4-connector-integrity`, `audit:logic-review-v3`
**Location:** `services/omnichain/changenow-client.ts:284-345`
**Filed as:** _ready to file_

## Problem
The `_type: 'direct' | 'reverse'` parameter is never referenced; the method always builds the direct `exchange-amount` endpoint, so a reverse quote is computed as if `amount` were the send amount.

## Evidence
```ts
  async getEstimate(
    fromTicker: string,
    toTicker: string,
    amount: string,
    _type: 'direct' | 'reverse' = 'direct'
  ): Promise<ActionResult<ChangeNowEstimate>> {
    const startTime = Date.now();
    try {
      const endpoint = `/${this.config.apiVersion}/exchange-amount/${amount}/${fromTicker.toLowerCase()}_${toTicker.toLowerCase()}`;
```

## Impact
A caller requesting "how much source to receive exactly `amount` of target" (reverse) gets the receive amount for sending `amount` — the inverse of intent — so the order is sized on an inverted quote and over/under-funds the swap.

## Suggested fix
Branch on `_type` to hit ChangeNOW's reverse-estimate endpoint, or remove the reverse option from the signature so callers cannot request an unsupported mode.

## Acceptance criteria
- [ ] A reverse estimate returns the required send amount for the target receive amount (or the reverse option is removed)
- [ ] Direct vs reverse produce different, correct results
- [ ] Regression test: covers a reverse quote
```

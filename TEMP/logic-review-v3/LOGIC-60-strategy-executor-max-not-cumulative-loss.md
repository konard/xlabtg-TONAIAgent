# LOGIC-60 — strategy-executor.tact tracks the largest single loss instead of cumulative loss, letting the max-loss auto-stop be evaded by chunking

**Severity:** 🟠 Medium
**Area:** Security (smart contract)
**Stage:** Stage 1 — Access control & safety re-wiring
**Suggested labels:** `bug`, `security`, `severity:medium`, `area:contracts`, `stage:1-access-control`, `audit:logic-review-v3`
**Location:** `contracts/strategy-executor.tact:341-351`
**Filed as:** _ready to file_

## Problem
On a losing outcome the contract sets `cumulativeLossNano = max(prev, actualLoss)` rather than summing, so the field named "cumulative" only ever holds the single worst loss and the `>= maxLossNano` auto-stop compares against that maximum.

## Evidence
```tact
// contracts/strategy-executor.tact:341-351
        if (msg.actualPnlNano < 0) {
            let actualLoss: Int = -msg.actualPnlNano;
            if (actualLoss > r.cumulativeLossNano) {
                r.cumulativeLossNano = actualLoss;
            }
            // Auto-stop if real loss now exceeds max loss
            if (r.maxLossNano > 0 && r.cumulativeLossNano >= r.maxLossNano && r.status == STATUS_RUNNING) {
                r.status = STATUS_STOPPED;
                r.stoppedAt = now();
            }
        }
```

## Impact
With `maxLossNano` 100, ten reported losses of 90 keep `cumulativeLossNano` pinned at 90 (never `>= 100`), so a bleeding strategy is never auto-stopped despite ~900 realized loss. The circuit breaker is defeated by splitting losses into sub-threshold chunks.

## Suggested fix
Accumulate: `r.cumulativeLossNano = r.cumulativeLossNano + actualLoss;` before the threshold check (optionally net against reported gains if a rolling net loss is intended).

## Acceptance criteria
- [ ] Repeated sub-threshold losses sum until they cross `maxLossNano` and stop the strategy.
- [ ] `cumulativeLossNano` reflects the total of all reported losses, not the single worst.
- [ ] Regression test: a contract test reporting 2×60 loss with `maxLoss` 100 asserts `STATUS_STOPPED`.

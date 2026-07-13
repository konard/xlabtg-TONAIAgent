# LOGIC-62 — Capital-pool accounting only grows on gains and never shrinks on losses, so pool capital drifts above reality

**Severity:** 🔴 High
**Area:** Strategy
**Stage:** Stage 2 — Funds & accounting correctness
**Suggested labels:** `bug`, `financial`, `severity:high`, `area:strategy`, `stage:2-funds-accounting`, `audit:logic-review-v3`
**Location:** `core/multi-agent/resources/capital-manager.ts:215-240`
**Filed as:** _ready to file_

## Problem
`updatePerformance` applies pnl to pool capital only inside `if (pnl > 0)`. Losses update `allocation.performance` (which can go negative) but never decrement `pool.totalCapital` / `pool.availableCapital`. As a result, pool capital only ever ratchets upward and never reflects realized losses.

## Evidence
```ts
async updatePerformance(agentId: string, pnl: number): Promise<void> {
  for (const [, pool] of this.pools) {
    for (const allocation of pool.allocations) {
      if (allocation.agentId === agentId && allocation.status === 'active') {
        allocation.performance += pnl;

        // Update pool based on performance
        if (pnl > 0) {
          pool.totalCapital += pnl;
          pool.availableCapital += pnl;
        }

        pool.lastUpdated = new Date();
```

## Impact
A break-even round trip (pnl `+100` then `-100`) leaves the pool believing it has 100 more capital than it actually does: the `+100` bumps `totalCapital`/`availableCapital`, but the `-100` is swallowed. Repeated losses inflate available capital indefinitely, letting the manager approve allocations against money that has already been lost — over-allocating real funds.

## Suggested fix
Apply pnl symmetrically for both signs by dropping the `pnl > 0` guard, flooring the resulting balances at zero so a loss reduces `pool.totalCapital`/`pool.availableCapital` rather than being ignored.

## Acceptance criteria
- [ ] A `+100` then `-100` sequence leaves `pool.totalCapital` and `pool.availableCapital` unchanged from the starting value.
- [ ] `availableCapital` never exceeds the pool's real capital after a series of losses.
- [ ] Regression test: assert that a `+100` / `-100` round trip nets to zero change in pool balances.

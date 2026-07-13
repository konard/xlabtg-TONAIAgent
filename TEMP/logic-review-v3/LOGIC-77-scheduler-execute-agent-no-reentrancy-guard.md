# LOGIC-77 — Scheduler executeAgent has no re-entrancy guard, so triggerNow during an in-flight cycle double-runs the agent and corrupts the concurrency counter

**Severity:** 🟠 Medium
**Area:** Reliability
**Stage:** Stage 3 — Runtime concurrency & daily-limit resets
**Suggested labels:** `bug`, `reliability`, `severity:medium`, `area:runtime`, `stage:3-runtime-concurrency`, `audit:logic-review-v3`
**Location:** `core/runtime/agent-scheduler.ts:414-470` (triggerNow `:315-327`)
**Filed as:** _ready to file_

## Problem
`executeAgent`'s entry guard checks `scheduled.isPaused` but not `scheduled.isRunning`. It then sets `isRunning = true` and `currentExecutions++`, and in `finally` sets `isRunning = false` and `currentExecutions--`. `triggerNow` clears the pending timer and awaits `executeAgent` without checking whether a cycle is already running for that agent.

## Evidence
```ts
// core/runtime/agent-scheduler.ts:418 — entry guard omits isRunning
    if (!scheduled || !callback || !this.running || scheduled.isPaused) return;

// :428-429 — sets running/concurrency state
    scheduled.isRunning = true;
    this.currentExecutions++;

// :449-451 — finally decrements regardless of who set it
    } finally {
      scheduled.isRunning = false;
      this.currentExecutions--;

// :315-327 — triggerNow clears the timer and awaits executeAgent with no isRunning check
  async triggerNow(agentId: string): Promise<boolean> {
    const callback = this.executionCallbacks.get(agentId);
    if (!callback) return false;

    const scheduled = this.scheduledAgents.get(agentId);
    if (scheduled?.timerId) {
      clearTimeout(scheduled.timerId);
      scheduled.timerId = undefined;
    }

    await this.executeAgent(agentId);
    return true;
  }
```

## Impact
If a timer-driven cycle for agent A is mid-execution, `triggerNow('A')` starts a second concurrent `executeAgent`. The two run in parallel; whichever finishes first sets `isRunning = false`, decrements `currentExecutions`, and reschedules while the other run is still in flight. This corrupts concurrency-limit accounting (double increment/decrement of the shared counter) and double-executes the cycle.

## Suggested fix
Add `if (scheduled.isRunning) return;` (or reschedule/queue) at the top of `executeAgent`, and have `triggerNow` refuse or queue when `isRunning` is already set for the agent.

## Acceptance criteria
- [ ] `triggerNow` during an in-flight cycle does not start a second concurrent run.
- [ ] `currentExecutions` never goes negative and is never double-counted.
- [ ] Regression test: trigger during an in-flight cycle and assert exactly one execution runs.

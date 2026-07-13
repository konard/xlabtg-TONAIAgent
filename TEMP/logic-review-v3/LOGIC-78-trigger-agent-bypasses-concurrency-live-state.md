# LOGIC-78 — triggerAgent bypasses the scheduler's concurrency control and mutates the live shared state object, allowing concurrent executeAgentCycle races

**Severity:** 🟠 Medium
**Area:** Reliability
**Stage:** Stage 3 — Runtime concurrency & daily-limit resets
**Suggested labels:** `bug`, `reliability`, `severity:medium`, `area:runtime`, `stage:3-runtime-concurrency`, `audit:logic-review-v3`
**Location:** `core/runtime/agent-manager.ts:361-372,538-570` (live-ref at `core/runtime/agent-state.ts:364-374`)
**Filed as:** [#526](https://github.com/xlabtg/TONAIAgent/issues/526)

## Problem
`triggerAgent` calls `executeAgentCycle` directly, outside the scheduler that serializes cycles via `isRunning` / `currentExecutions`. `executeAgentCycle` obtains the live stored state via `stateManager.requireAgent`, which returns the object itself (not a copy — contrast `getAgent`, which spreads into a fresh object), and then mutates it (`incrementCycleCounts`, `updatePositions`, `addTradeRecord`) with no in-flight guard.

## Evidence
```ts
// core/runtime/agent-manager.ts:361-372 — triggerAgent calls executeAgentCycle directly
  async triggerAgent(agentId: string): Promise<ExecutionCycleResult> {
    const state = this.stateManager.requireAgent(agentId);

    if (state.state !== 'RUNNING') {
      throw new RuntimeError(
        `Agent ${agentId} is not running (state: ${state.state})`,
        'AGENT_NOT_RUNNING'
      );
    }

    return this.executeAgentCycle(agentId);
  }

// core/runtime/agent-state.ts:356-374 — requireAgent returns the live object; getAgent returns a copy
  getAgent(agentId: string): AgentRuntimeState | undefined {
    const state = this.agents.get(agentId);
    return state ? { ...state } : undefined;
  }
  requireAgent(agentId: string): AgentRuntimeState {
    const state = this.agents.get(agentId);
    if (!state) {
      throw new RuntimeError(
        `Agent ${agentId} not found`,
        'AGENT_NOT_FOUND',
        { agentId }
      );
    }
    return state;
  }

// core/runtime/agent-manager.ts:555,562-564 — mutations on the shared live state
      this.stateManager.incrementCycleCounts(agentId, result.success, result.durationMs);
      ...
          this.stateManager.updatePositions(agentId, result.portfolioUpdate.newPositions);
          this.stateManager.updatePortfolioValue(agentId, result.portfolioUpdate.newValue);
          this.stateManager.addTradeRecord(agentId, result.trade);
```

## Impact
If a scheduled cycle is already running for agent A when `triggerAgent('A')` is invoked (or two `triggerAgent` calls interleave), two `executeAgentCycle` runs mutate the same live state object, producing lost updates and double-counted cycles/trades on shared position/metric fields. This is distinct from the previously-reported single-path telemetry double-count: here the race arises from bypassing the scheduler entirely.

## Suggested fix
Route `triggerAgent` through the scheduler's guarded `triggerNow` path, or add a per-agent in-flight lock in `executeAgentCycle` so that only one cycle mutates a given agent's state at a time.

## Acceptance criteria
- [ ] `triggerAgent` cannot run concurrently with a scheduled cycle for the same agent.
- [ ] State mutations are serialized per agent.
- [ ] Regression test: interleave a `triggerAgent` call with a scheduled cycle and assert no double-count of cycles/trades.

# LOGIC-74 — Health-monitor auto-pause/auto-suspend can never fire because its two heaviest anomaly inputs are dead signals

**Severity:** 🔴 High
**Area:** Reliability
**Stage:** Stage 1 — Access control & safety re-wiring
**Suggested labels:** `bug`, `reliability`, `severity:high`, `area:runtime`, `stage:1-access-control`, `audit:logic-review-v3`
**Location:** `core/agents/lifecycle/lifecycle-orchestrator.ts:640-782` (init `:176-177`, executeJob `:560`, heartbeat init `:1155`)
**Filed as:** [#522](https://github.com/xlabtg/TONAIAgent/issues/522)

## Problem
`runHealthCheck` derives `riskScore` from three anomalies, but the two anomalies carrying the `auto_pause` recommendation and the largest weights can never trigger:

- `execution_failure_spike` (severity `high`, +40) reads `metrics.failedExecutionsLastHour`, which is initialized to 0 (`buildEmptyMetrics`, :177) and never incremented anywhere in the codebase. `executeJob` only ever increments `executionsLastHour` (:560), never the failed counter.
- `network_disconnect` (severity `critical`, +60) requires a non-null `record.runtimeAllocation.lastHeartbeatAt`, but that field is only ever assigned `null` (:1155) and never stamped with a real timestamp, so `heartbeatAge` is always `null` and the check is skipped.

Only `high_latency` (severity `medium`, +25) can ever fire. But `autoPauseRiskThreshold` is 75 (:82) and `autoSuspendRiskThreshold` is 90 (:83), so a maximum reachable `riskScore` of 25 never crosses either threshold.

## Evidence
```ts
// core/agents/lifecycle/lifecycle-orchestrator.ts:650-658 — never fires (failedExecutionsLastHour stays 0)
if (metrics.failedExecutionsLastHour > 5) {
  anomalies.push({
    type: 'execution_failure_spike',
    severity: 'high',
    description: `${metrics.failedExecutionsLastHour} failed executions in the last hour`,
    recommendation: 'auto_pause',
    detectedAt: new Date(),
  });
}

// :670-672 — heartbeatAge is always null because lastHeartbeatAt is never set to a real time
const heartbeatAge = record.runtimeAllocation?.lastHeartbeatAt
  ? Date.now() - record.runtimeAllocation.lastHeartbeatAt.getTime()
  : null;

// :1155 — lastHeartbeatAt only ever assigned null
      lastHeartbeatAt: null,

// :560 — executeJob only increments executionsLastHour, never failedExecutionsLastHour
    metrics.executionsLastHour = (metrics.executionsLastHour ?? 0) + 1;

// :82-83 — thresholds the reachable riskScore (max 25) can never cross
  autoPauseRiskThreshold: 75,        // auto-pause at risk score >= 75
  autoSuspendRiskThreshold: 90,      // auto-suspend at risk score >= 90
```
A repo-wide search confirms `failedExecutionsLastHour` is only declared (`types.ts:230`) and read (`> 5` comparisons), never incremented; `lastHeartbeatAt` is only assigned `null`.

## Impact
An agent whose strategy repeatedly fails, or whose runtime hangs, accrues no failed-execution count and no heartbeat age. Its `riskScore` maxes at 25, which is below both the 75 auto-pause and 90 auto-suspend thresholds, so the agent is never auto-paused or auto-suspended. The health-monitor safety mechanism is effectively disabled — the exact conditions it exists to catch (execution failure spikes, network/heartbeat loss) contribute nothing to the score. (Secondary: `executionsLastHour` is incremented on every job but never reset over an hourly window, so it grows unbounded and does not represent a real "last hour".)

## Suggested fix
Increment `failedExecutionsLastHour` on each failed job/cycle and stamp `lastHeartbeatAt` on each successful cycle/heartbeat, rolling the `*LastHour` counters over a real time window (reset when the hour rolls over). Alternatively, re-weight the thresholds so realistic anomalies can cross them. Ensure the two heaviest signals can actually reach the risk-score computation.

## Acceptance criteria
- [ ] A run of failed executions raises `riskScore` past `autoPauseRiskThreshold` and auto-pauses the agent.
- [ ] A stale/absent heartbeat contributes `network_disconnect` weight to `riskScore`.
- [ ] `failedExecutionsLastHour` and `executionsLastHour` reset over a real hourly window rather than growing unbounded.
- [ ] Regression test: simulate repeated failed executions and assert the agent is auto-paused.

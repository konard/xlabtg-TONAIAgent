# LOGIC-67 — failDelegation retry path resets the task to pending and increments retryCount but never re-delegates, orphaning the task

**Severity:** 🟡 Low
**Area:** Reliability
**Stage:** Stage 5 — Strategy / backtest / optimizer integrity
**Suggested labels:** `bug`, `reliability`, `severity:low`, `area:multi-agent`, `stage:5-strategy-integrity`, `audit:logic-review-v3`
**Location:** `core/multi-agent/delegation/task-queue.ts:476-490`
**Filed as:** [#515](https://github.com/xlabtg/TONAIAgent/issues/515)

## Problem
On failure with retries remaining, `failDelegation` increments `retryCount`, sets `status` to `'pending'`, clears `assigneeId`, and emits `'delegation_retry'` — then deletes the delegation record. It never creates a new delegation or re-enqueues the task for execution, so the "retry" only mutates flags.

## Evidence
```ts
// core/multi-agent/delegation/task-queue.ts:476-490
// Check if can retry
const task = delegation.task;
if (task.retryCount < task.maxRetries) {
  task.retryCount++;
  task.status = 'pending';
  task.assigneeId = undefined;

  this.emitEvent('delegation_retry', {
    delegationId,
    taskId: task.id,
    retryCount: task.retryCount,
  });
} else {
  await this.taskQueue.updateStatus(task.id, 'failed', delegation.result);
}

// Move to history
this.addToHistory(delegation);
this.delegations.delete(delegationId);
```

## Impact
The "retried" task silently stalls forever — it is never re-run and never reaches the terminal `'failed'` state — unless some external orchestration happens to re-scan `pending` tasks and re-delegate them. In effect a retryable failure orphans the task.

## Suggested fix
In the retry branch, actually re-delegate: create a fresh delegation (or re-enqueue the task to an eligible agent) rather than only flipping `status` back to `'pending'`.

## Acceptance criteria
- [ ] A failed-but-retryable task is re-delegated and re-executed.
- [ ] `retryCount` increments across real, observable attempts.
- [ ] Regression test: assert that a retryable failure produces a new delegation.

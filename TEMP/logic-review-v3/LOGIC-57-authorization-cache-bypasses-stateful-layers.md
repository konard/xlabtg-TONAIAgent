# LOGIC-57 — Authorization decision cache short-circuits the rate-limit, daily-limit and anomaly layers, enabling transaction replay

**Severity:** 🔴 High
**Area:** Security
**Stage:** Stage 1 — Access control & safety re-wiring
**Suggested labels:** `bug`, `security`, `severity:high`, `area:security`, `stage:1-access-control`, `audit:logic-review-v3`
**Location:** `core/security/authorization.ts:761-771, 960-971`
**Filed as:** _ready to file_

## Problem
`authorize()` returns a cached prior decision for `cacheDecisionSeconds` (default 60s) before running any layer. The cache key (`buildCacheKey`) is built only from `{type, source, destination, token, amount, agentId, userId, layers}` and omits `usedToday`, rate-limit/session state and `riskContext`, so identical fund-moving requests all hit the cache after the first and skip every stateful layer.

## Evidence
```ts
// core/security/authorization.ts:761-768 — cache-hit short-circuit
    // Check cache for identical requests
    if (this.config.cacheDecisionSeconds > 0) {
      const cacheKey = this.buildCacheKey(request, fullContext);
      const cached = this.authCache.get(cacheKey);
      if (cached && cached.expiresAt > Date.now()) {
        return cached.result;
      }
    }

    // Run each enabled layer
    for (const layer of this.config.enabledLayers) {
```

```ts
// core/security/authorization.ts:960-971 — cache key omits stateful inputs
  private buildCacheKey(request: TransactionRequest, context: AuthorizationContext): string {
    return JSON.stringify({
      type: request.type,
      source: request.source?.address,
      destination: request.destination?.address,
      token: request.amount?.token,
      amount: request.amount?.amount,
      agentId: request.agentId,
      userId: request.userId,
      layers: this.config.enabledLayers,
    });
  }
```

## Impact
An agent (or compromised orchestrator) submits N identical transfers within the window; every one after the first is auto-approved without re-running `rate_limit`, `limit_check` or `anomaly_detection` — bypassing per-window rate limiting and daily-spend accounting and defeating anomaly detection for repeated identical transfers.

## Suggested fix
Never short-circuit stateful layers on a cache hit — cache only deterministic validation/rejection results, or include a nonce/monotonic counter/`usedToday` in the cache key and always re-run `rate_limit`/`limit_check`/`anomaly`.

## Acceptance criteria
- [ ] Two identical approved `authorize()` calls both execute the rate-limit and limit-check layers.
- [ ] Daily-spend accounting reflects every call, not just the first.
- [ ] Regression test: asserts the second identical `authorize()` call is subject to rate limiting (not served from cache).

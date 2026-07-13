# LOGIC-71 — ChangeNOW getCurrencies conflates isFiat with "active" and serves a cache filled under a different active flag, returning an incorrect currency set

**Severity:** 🟠 Medium
**Area:** Data
**Stage:** Stage 4 — Market data & connector integrity
**Suggested labels:** `bug`, `data`, `severity:medium`, `area:connectors`, `stage:4-connector-integrity`, `audit:logic-review-v3`
**Location:** `services/omnichain/changenow-client.ts:144-168`
**Filed as:** [#519](https://github.com/xlabtg/TONAIAgent/issues/519)

## Problem
On a cache hit it returns `active ? currencies.filter(c => !c.isFiat) : currencies`, using `!isFiat` as a stand-in for active; but the cache is populated by whichever `active` value was first requested (the API is called with `active=String(active)`), so the served set does not match the requested activeness.

## Evidence
```ts
        return {
          success: true,
          data: active ? currencies.filter(c => !c.isFiat) : currencies,
          executionTime: Date.now() - startTime,
        };
```
```ts
      const endpoint = `/${this.config.apiVersion}/currencies`;
      const params = new URLSearchParams({ active: String(active) });
```

## Impact
getCurrencies(false) then getCurrencies(true) returns inactive-but-non-fiat coins as tradable; if the cache was first filled active-only, getCurrencies(false) returns only active while claiming to return all. Callers may offer a pair the exchange deactivated. getCurrency/getCurrenciesForPair share this cache.

## Suggested fix
Key the cache by the active flag (or cache the full unfiltered list and derive views from the real active/available field), and stop equating !isFiat with active.

## Acceptance criteria
- [ ] getCurrencies(true) never returns inactive currencies
- [ ] getCurrencies(false) returns the full set
- [ ] Cache is keyed/derived correctly
- [ ] Regression test: interleaves both calls and asserts correct sets
```

# LOGIC-58 — OpenSanctions score-scale mismatch (0–1 vs a 70/85 threshold) silently disables entity sanctions screening (fail-open)

**Severity:** 🔴 High
**Area:** Regulatory
**Stage:** Stage 1 — Access control & safety re-wiring
**Suggested labels:** `bug`, `security`, `severity:high`, `area:regulatory`, `stage:1-access-control`, `audit:logic-review-v3`
**Location:** `services/regulatory/providers/opensanctions.ts:93, 141-162, 180`
**Filed as:** [#506](https://github.com/xlabtg/TONAIAgent/issues/506)

## Problem
OpenSanctions `/match` returns `score` as a float in [0,1], but the provider filters `r.score >= this.minScore` with `minScore` defaulted to 70, and emits `matchScore: Math.round(match.score)` ∈ {0,1} which the consuming screener compares against a default threshold of 85. No real match can pass either comparison.

## Evidence
```ts
// services/regulatory/providers/opensanctions.ts:93 — threshold on wrong scale
    this.minScore = config.minScore ?? 70;
```

```ts
// services/regulatory/providers/opensanctions.ts:141-162 — filter compares 0–1 score against 70
    const matches: OpenSanctionsMatch[] = results
      .filter((r) => r.score >= this.minScore)
      .map((r) => ({
        id: r.id,
        name: r.caption,
        score: r.score,
        datasets: r.datasets,
        properties: {
          name: r.properties['name'],
          alias: r.properties['alias'],
          country: r.properties['country'],
          program: r.properties['program'],
          topics: r.properties['topics'],
          createdAt: r.properties['createdAt'],
        },
      }));

    return {
      query: entityName,
      matches,
      hasHit: matches.length > 0,
    };
```

```ts
// services/regulatory/providers/opensanctions.ts:180 — Math.round collapses 0–1 score to {0,1}
        matchScore: Math.round(match.score),
```

## Impact
For any sanctioned entity the API returns e.g. score `0.95`; `0.95 >= 70` is false so `matches` is empty and `hasHit`/`isMatch` is false. Even with a lower `minScore`, `Math.round(0.95) = 1 >= 85` is false. Entity screening never produces a match — OFAC-SDN-listed parties pass. The fail-closed path only triggers on network errors, never on this silent miss.

## Suggested fix
Normalise to 0–100 (`Math.round(match.score * 100)`) before filtering and before emitting `matchScore`, and default `minScore` on the 0–100 scale; add a test asserting a known SDN name yields `isMatch:true`.

## Acceptance criteria
- [ ] A score of `0.95` from the API produces a match with `matchScore` 95.
- [ ] `screenEntity` returns `isMatch:true` for a known SDN name.
- [ ] Regression test: with a mocked `0.95` response, asserts a hit.

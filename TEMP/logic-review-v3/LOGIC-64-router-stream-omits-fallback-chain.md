# LOGIC-64 — Router stream() omits the configured fallback chain that execute() includes, giving streaming requests weaker provider resilience

**Severity:** 🟠 Medium
**Area:** AI
**Stage:** Stage 5 — Strategy / backtest / optimizer integrity
**Suggested labels:** `bug`, `reliability`, `severity:medium`, `area:ai`, `stage:5-strategy-integrity`, `audit:logic-review-v3`
**Location:** `core/ai/routing/router.ts:611-615` (vs `execute()` `:517-539`)
**Filed as:** _ready to file_

## Problem
`execute()` builds its provider chain from `decision.provider` + `decision.alternatives` and then appends the configured `this.config.fallbackChain` providers. `stream()` builds its chain only from `decision.provider` + `decision.alternatives` and never appends `fallbackChain`, so streaming requests get a strictly shorter resilience chain.

## Evidence
```ts
// stream() — core/ai/routing/router.ts:611-615
// Build fallback chain
const fallbackChain: Array<{ provider: ProviderType; model: string }> = [
  { provider: decision.provider, model: decision.model },
  ...decision.alternatives.map((a) => ({ provider: a.provider, model: a.model })),
];
```

Compare `execute()`, which appends the configured chain:
```ts
// execute() — core/ai/routing/router.ts:523-539
// Add configured fallback chain
if (this.config.fallbackChain) {
  for (const providerType of this.config.fallbackChain) {
    if (!fallbackChain.some((f) => f.provider === providerType)) {
      const provider = this.registry.get(providerType);
      if (provider) {
        const models = await provider.getModels();
        if (models.length > 0) {
          fallbackChain.push({ provider: providerType, model: models[0].id });
        }
      }
    }
  }
}
```

## Impact
With `fallbackChain: ['openai']` configured as a last resort: when the routed provider and every scored alternative is down or circuit-open, `execute()` recovers via OpenAI, but `stream()` exhausts its shorter chain and throws `NO_AVAILABLE_PROVIDERS`. Streaming requests fail in exactly the scenarios where non-streaming requests recover.

## Suggested fix
Extract the "append configured fallback chain" logic from `execute()` into a shared helper and invoke it in `stream()` too, so both code paths build identical fallback chains.

## Acceptance criteria
- [ ] `stream()` includes `config.fallbackChain` providers appended after the scored alternatives.
- [ ] With all scored providers down and a healthy configured fallback, `stream()` succeeds.
- [ ] Regression test: assert `stream()` falls back to the configured provider when scored providers are unavailable.

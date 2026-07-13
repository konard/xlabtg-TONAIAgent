# Logic RE-Review — Ready-to-File Issue Breakdown (v3 / Issue #496)

> Generated as part of Issue [#496](https://github.com/xlabtg/TONAIAgent/issues/496): "Check via Claude".
> Audited version: v2.43.0 · Branch: `issue-496-e3384416256d`
> Companion report: [`AUDIT_REPORT_TONAIAgent_v2.43.0_LOGIC_REVIEW_v3.md`](../../AUDIT_REPORT_TONAIAgent_v2.43.0_LOGIC_REVIEW_v3.md)

This folder contains one **ready-to-file professional issue** per finding from the third TONAIAgent v2.43.0
logic *re-audit*. It is a fresh pass that **does not** re-report the two previous reviews
(LOGIC-01..22, issues [#386–#407](https://github.com/xlabtg/TONAIAgent/issues/386); LOGIC-23..51, issues
[#433–#461](https://github.com/xlabtg/TONAIAgent/issues/433)); numbering continues at **LOGIC-52**. Each file
is self-contained: problem statement, exact code location, evidence, impact, suggested fix, acceptance
criteria, suggested labels, and the implementation stage.

Every finding was confirmed by reading the actual source at the stated path/line range on branch
`issue-496-e3384416256d`. Two candidates were investigated and **dropped** as non-defects: the prior
`key-management.ts` unverified-signature issue (LOGIC-24) is now **fixed** in the current tree (it counts only
verified signatures), and a `services/.../emergency.ts` social-recovery candidate was dropped because the
missing-branch path fails *closed* (recovery is harder to trigger, not easier).

## Severity summary

| Severity | Count |
|----------|:-----:|
| 🔴 High | 9 |
| 🟠 Medium | 13 |
| 🟡 Low | 5 |
| **Total** | **27** |

## High severity

| ID | Title | Area | File |
|----|-------|------|------|
| LOGIC-52 | [Escrow release evaluates the wrong conditions field, so held funds are always released](./LOGIC-52-escrow-release-wrong-conditions-field.md) | Financial | `services/payments/payment-gateway.ts` |
| LOGIC-53 | [Margin unrealized PnL is recomputed from the previous tick, not the entry price, understating liquidation loss](./LOGIC-53-margin-unrealized-pnl-delta-not-entry.md) | Financial | `services/prime-brokerage/margin-leverage.ts` |
| LOGIC-54 | [Securities-lending collateral ratio divides by loan quantity instead of loan value → grossly under-collateralized loans](./LOGIC-54-securities-lending-collateral-ratio-by-quantity.md) | Financial | `services/prime-brokerage/securities-lending.ts` |
| LOGIC-57 | [Authorization decision cache short-circuits the rate-limit / daily-limit / anomaly layers, enabling replay](./LOGIC-57-authorization-cache-bypasses-stateful-layers.md) | Security | `core/security/authorization.ts` |
| LOGIC-58 | [OpenSanctions score-scale mismatch (0–1 vs a 70/85 threshold) silently disables entity sanctions screening](./LOGIC-58-opensanctions-score-scale-mismatch.md) | Regulatory | `services/regulatory/providers/opensanctions.ts` |
| LOGIC-62 | [Capital-pool accounting only grows on gains and never shrinks on losses → phantom available capital](./LOGIC-62-capital-pool-grows-on-gains-never-shrinks.md) | Strategy | `core/multi-agent/resources/capital-manager.ts` |
| LOGIC-68 | [DeDust connector prices TON-quoted tokens with a hardcoded $5.00 TON/USD constant](./LOGIC-68-dedust-hardcoded-ton-usd-price.md) | Financial | `core/market-data/base/connectors/dedust.ts` |
| LOGIC-74 | [Health-monitor auto-pause / auto-suspend can never fire (its two heaviest anomaly inputs are dead signals)](./LOGIC-74-health-monitor-auto-pause-never-fires.md) | Reliability | `core/agents/lifecycle/lifecycle-orchestrator.ts` |
| LOGIC-75 | [Agent-runtime dailyLoss is never accumulated, so the daily-loss risk limit is completely unenforced](./LOGIC-75-agent-runtime-daily-loss-never-accumulated.md) | Financial | `core/agents/agent-runtime/orchestrator.ts` |

## Medium severity

| ID | Title | Area | File |
|----|-------|------|------|
| LOGIC-55 | [Failed atomic settlement rolls back leg status but leaves the instruction 'completed', inflating settled-value metrics](./LOGIC-55-atomic-settlement-rollback-leaves-instruction-completed.md) | Financial | `services/clearing-house/settlement.ts` |
| LOGIC-59 | [CSRF middleware never enforces the double-submit cookie invariant and is not session-bound](./LOGIC-59-csrf-double-submit-not-enforced.md) | Security | `apps/api/src/middleware/chain.ts` |
| LOGIC-60 | [strategy-executor.tact tracks the largest single loss instead of cumulative loss → max-loss auto-stop evaded by chunking](./LOGIC-60-strategy-executor-max-not-cumulative-loss.md) | Security | `contracts/strategy-executor.tact` |
| LOGIC-61 | [API-key scopes are silently ignored for admin and service roles, voiding least-privilege](./LOGIC-61-api-key-scopes-ignored-for-admin-service.md) | Security | `services/auth/auth-service.ts` |
| LOGIC-63 | [Engine backtest reports profitFactor as avg win/loss instead of gross profit / gross loss](./LOGIC-63-backtest-profitfactor-avg-not-gross.md) | Strategy | `core/strategies/engine/backtesting.ts` |
| LOGIC-64 | [Router stream() omits the configured fallback chain that execute() includes → weaker streaming resilience](./LOGIC-64-router-stream-omits-fallback-chain.md) | AI | `core/ai/routing/router.ts` |
| LOGIC-65 | [Monte-Carlo CVaR divides by cvarIndex without the zero guard used elsewhere → NaN for small sims / high confidence](./LOGIC-65-montecarlo-cvar-divide-by-zero.md) | Strategy | `core/strategies/engine/backtesting.ts` |
| LOGIC-69 | [Market-data service caches fallback prices under a key it never reads → primary outage re-hits both providers](./LOGIC-69-market-data-fallback-cache-unread-key.md) | Reliability | `core/market-data/base/service.ts` |
| LOGIC-70 | [ChangeNOW getEstimate ignores its reverse mode and always calls the direct endpoint](./LOGIC-70-changenow-estimate-ignores-reverse.md) | Financial | `services/omnichain/changenow-client.ts` |
| LOGIC-71 | [ChangeNOW getCurrencies conflates isFiat with "active" and serves a cache keyed on a different active flag](./LOGIC-71-changenow-currencies-isfiat-active-cache.md) | Data | `services/omnichain/changenow-client.ts` |
| LOGIC-76 | [dailyGasUsed / dailyTransactionCount are never reset at a day boundary → daily limits become permanent lifetime caps](./LOGIC-76-agent-runtime-daily-counters-never-reset.md) | Reliability | `core/agents/agent-runtime/orchestrator.ts` |
| LOGIC-77 | [Scheduler executeAgent has no re-entrancy guard → triggerNow during an in-flight cycle double-runs the agent](./LOGIC-77-scheduler-execute-agent-no-reentrancy-guard.md) | Reliability | `core/runtime/agent-scheduler.ts` |
| LOGIC-78 | [triggerAgent bypasses scheduler concurrency control and mutates the live shared state object](./LOGIC-78-trigger-agent-bypasses-concurrency-live-state.md) | Reliability | `core/runtime/agent-manager.ts` |

## Low severity

| ID | Title | Area | File |
|----|-------|------|------|
| LOGIC-56 | [Staking compoundRewards adds reinvested rewards to claimedRewards (double count) and reports a cross-stake total](./LOGIC-56-staking-compound-double-counts-claimed.md) | Financial | `extended/tokenomics/staking.ts` |
| LOGIC-66 | [rebalancePool uses the 0.1 threshold as a low-utilization floor (inverted semantics), so it rebalances almost always](./LOGIC-66-rebalance-pool-threshold-inverted.md) | Strategy | `core/multi-agent/resources/capital-manager.ts` |
| LOGIC-67 | [failDelegation retry resets the task to pending and increments retryCount but never re-delegates → orphaned task](./LOGIC-67-fail-delegation-retry-orphans-task.md) | Reliability | `core/multi-agent/delegation/task-queue.ts` |
| LOGIC-72 | [Binance fallback symbol for USDT is the delisted USDTBUSD pair → USDT price lookups via the fallback always fail](./LOGIC-72-binance-usdt-delisted-fallback-symbol.md) | Reliability | `core/market-data/base/config/assets.ts` |
| LOGIC-73 | [OpenSanctions result mapping defaults unknown datasets to ofac_sdn and hardcodes every match as an individual](./LOGIC-73-opensanctions-dataset-default-ofac-individual.md) | Data | `services/regulatory/providers/opensanctions.ts` |

## Suggested labels

The repository lacks severity/area/stage labels and the audit account has `pull`-only (triage-less) access, so
labels can not be applied at filing time (this matched the prior rounds, #386–#407 and #433–#461). Maintainers
should create and apply:

- Severity: `severity:high`, `severity:medium`, `severity:low`
- Area: `area:financial`, `area:security`, `area:regulatory`, `area:strategy`, `area:reliability`, `area:market-data`, `area:runtime`, `area:connectors`, `area:ai`, `area:contracts`, `area:multi-agent`
- Stage: `stage:1-access-control`, `stage:2-funds-accounting`, `stage:3-runtime-concurrency`, `stage:4-connector-integrity`, `stage:5-strategy-integrity`
- Plus the existing `bug` (and `security` for security/regulatory findings) and a grouping label `audit:logic-review-v3`.

Until then, every issue body carries its severity/area/stage as text. The [`issues.json`](./issues.json)
manifest lists every finding with its metadata so maintainers can bulk-create the issues (e.g. via
`gh issue create`) in one pass.

## Priority order & implementation stages

### Stage 1 — Access control & safety re-wiring

| ID | Finding | Severity |
|----|---------|----------|
| LOGIC-57 | [Authorization decision cache short-circuits stateful layers → replay](./LOGIC-57-authorization-cache-bypasses-stateful-layers.md) | High |
| LOGIC-58 | [OpenSanctions score-scale mismatch disables entity screening](./LOGIC-58-opensanctions-score-scale-mismatch.md) | High |
| LOGIC-74 | [Health-monitor auto-pause / auto-suspend can never fire](./LOGIC-74-health-monitor-auto-pause-never-fires.md) | High |
| LOGIC-59 | [CSRF double-submit invariant never enforced / not session-bound](./LOGIC-59-csrf-double-submit-not-enforced.md) | Medium |
| LOGIC-60 | [strategy-executor.tact max-loss auto-stop evaded by loss chunking](./LOGIC-60-strategy-executor-max-not-cumulative-loss.md) | Medium |
| LOGIC-61 | [API-key scopes ignored for admin / service roles](./LOGIC-61-api-key-scopes-ignored-for-admin-service.md) | Medium |

### Stage 2 — Funds & accounting correctness

| ID | Finding | Severity |
|----|---------|----------|
| LOGIC-52 | [Escrow release evaluates the wrong conditions field → always releases](./LOGIC-52-escrow-release-wrong-conditions-field.md) | High |
| LOGIC-53 | [Margin unrealized PnL uses last tick, not entry price](./LOGIC-53-margin-unrealized-pnl-delta-not-entry.md) | High |
| LOGIC-54 | [Securities-lending collateral ratio divides by quantity, not loan value](./LOGIC-54-securities-lending-collateral-ratio-by-quantity.md) | High |
| LOGIC-62 | [Capital pool grows on gains but never shrinks on losses](./LOGIC-62-capital-pool-grows-on-gains-never-shrinks.md) | High |
| LOGIC-75 | [Agent-runtime dailyLoss never accumulated → daily-loss limit unenforced](./LOGIC-75-agent-runtime-daily-loss-never-accumulated.md) | High |
| LOGIC-55 | [Failed atomic settlement leaves instruction 'completed'](./LOGIC-55-atomic-settlement-rollback-leaves-instruction-completed.md) | Medium |
| LOGIC-56 | [Staking compound double-counts claimedRewards](./LOGIC-56-staking-compound-double-counts-claimed.md) | Low |

### Stage 3 — Runtime concurrency & daily-limit resets

| ID | Finding | Severity |
|----|---------|----------|
| LOGIC-76 | [dailyGasUsed / dailyTransactionCount never reset at day boundary](./LOGIC-76-agent-runtime-daily-counters-never-reset.md) | Medium |
| LOGIC-77 | [Scheduler executeAgent has no re-entrancy guard → double-run](./LOGIC-77-scheduler-execute-agent-no-reentrancy-guard.md) | Medium |
| LOGIC-78 | [triggerAgent bypasses scheduler concurrency and mutates live state](./LOGIC-78-trigger-agent-bypasses-concurrency-live-state.md) | Medium |

### Stage 4 — Market data & connector integrity

| ID | Finding | Severity |
|----|---------|----------|
| LOGIC-68 | [DeDust hardcoded $5.00 TON/USD price](./LOGIC-68-dedust-hardcoded-ton-usd-price.md) | High |
| LOGIC-69 | [Market-data fallback cached under a key never read](./LOGIC-69-market-data-fallback-cache-unread-key.md) | Medium |
| LOGIC-70 | [ChangeNOW getEstimate ignores reverse mode](./LOGIC-70-changenow-estimate-ignores-reverse.md) | Medium |
| LOGIC-71 | [ChangeNOW getCurrencies conflates isFiat with active + bad cache](./LOGIC-71-changenow-currencies-isfiat-active-cache.md) | Medium |
| LOGIC-72 | [Binance USDT fallback symbol is the delisted USDTBUSD pair](./LOGIC-72-binance-usdt-delisted-fallback-symbol.md) | Low |
| LOGIC-73 | [OpenSanctions dataset defaults to ofac_sdn / hardcoded individual](./LOGIC-73-opensanctions-dataset-default-ofac-individual.md) | Low |

### Stage 5 — Strategy / backtest / optimizer integrity

| ID | Finding | Severity |
|----|---------|----------|
| LOGIC-63 | [Backtest profitFactor = avg win/loss instead of gross/gross](./LOGIC-63-backtest-profitfactor-avg-not-gross.md) | Medium |
| LOGIC-64 | [Router stream() omits the configured fallback chain](./LOGIC-64-router-stream-omits-fallback-chain.md) | Medium |
| LOGIC-65 | [Monte-Carlo CVaR divide-by-zero → NaN](./LOGIC-65-montecarlo-cvar-divide-by-zero.md) | Medium |
| LOGIC-66 | [rebalancePool threshold semantics inverted](./LOGIC-66-rebalance-pool-threshold-inverted.md) | Low |
| LOGIC-67 | [failDelegation retry orphans the task](./LOGIC-67-fail-delegation-retry-orphans-task.md) | Low |

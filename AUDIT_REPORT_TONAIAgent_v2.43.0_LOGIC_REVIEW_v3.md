# 📄 LOGIC RE-REVIEW & CODE AUDIT (v3) — TONAIAgent v2.43.0

**Audit Type:** Full Application Logic Re-Review (correctness, financial, security, reliability)
**Prepared For:** xlabtg/TONAIAgent (Issue [#496](https://github.com/xlabtg/TONAIAgent/issues/496) — "Check via Claude")
**Audited Version:** v2.43.0 (branch: `issue-496-e3384416256d`)
**Auditor:** Automated AI Logic Audit (konard / AI Issue Solver)

---

## Executive Summary

This is a **third, independent logic-focused pass** over the entire TONAIAgent codebase (~713 TypeScript
files / ~381k LOC, plus Tact contracts), requested by Issue #496. It deliberately **does not re-report** the
first two reviews' findings: LOGIC-01..22 (issues [#386–#407](https://github.com/xlabtg/TONAIAgent/issues/386))
and LOGIC-23..51 (issues [#433–#461](https://github.com/xlabtg/TONAIAgent/issues/433)) were verified before
this pass began (spot-checked: LOGIC-24's threshold signing now counts **only verified** signatures in
`core/security/key-management.ts`). New numbering continues at **LOGIC-52**.

The dominant pattern from the first two reviews — *"built but not wired"* safety controls — **recurs a third
time**, and is now concentrated in the **agent runtime**: a health-monitor whose auto-pause/auto-suspend can
never fire because the two anomaly signals carrying its heaviest weights are never populated (LOGIC-74); a
per-agent `dailyLoss` that gates trading but is never accumulated (LOGIC-75); "daily" gas/transaction budgets
that never reset and silently become permanent lifetime caps (LOGIC-76). Alongside these, this pass surfaces a
fresh cluster of **funds-accounting** defects (escrow that always releases, margin PnL measured from the wrong
reference price, securities-lending collateral checked against token count instead of loan value, a capital
pool that only ever grows), a pair of **access-control fail-open** defects (an authorization cache that
short-circuits the rate-limit/anomaly layers and enables replay; a sanctions provider whose 0–1 score never
crosses its 70/85 threshold), and a set of **market-data / connector integrity** defects (a hardcoded TON/USD
price, a fallback cache stored under a key nothing reads, a reverse-swap estimate that silently runs forward).

**Overall assessment:** ⚠️ **9 High, 13 Medium, 5 Low** genuine logic defects, every one confirmed against the
source. As with the prior reports, severities are rated for the current (largely simulation-default) posture;
several **High** findings escalate to *Critical* under live funds (e.g. LOGIC-52 escrow always-release,
LOGIC-54 under-collateralized loans, LOGIC-68 fabricated USD prices, LOGIC-75 unenforced daily-loss limit).

| Category | High | Medium | Low | Total |
|----------|:----:|:------:|:---:|:-----:|
| Financial / Trading correctness | 6 | 3 | 1 | 10 |
| Security / Access control / Crypto | 1 | 3 | 0 | 4 |
| Regulatory / Compliance | 1 | 0 | 1 | 2 |
| Strategy / Backtest / Optimizer | 0 | 2 | 2 | 4 |
| Reliability / Runtime / Connectors | 1 | 5 | 1 | 7 |
| **Total** | **9** | **13** | **5** | **27** |

---

## Methodology

**Scope:** Full static analysis of the TypeScript source and Tact contracts, partitioned into five subsystems
analysed in parallel (Financial/Trading, Security/Auth/Crypto, AI/Strategies/Backtesting,
Services/Connectors/Regulatory, Runtime/Agents/Concurrency), mirroring the first two reviews' structure.

**Verification:** Every finding includes a file path + line reference, an exact code excerpt, and a concrete
failure scenario. Each agent-surfaced candidate was **re-read against the source before filing**. Two
candidates were dropped after verification: the previously-reported `key-management.ts` unverified-signature
threshold (LOGIC-24) is now **fixed** (it counts only verified signatures), and an `emergency.ts`
social-recovery candidate was dropped because the missing branch fails *closed*, not open.

**De-duplication:** Findings were cross-checked against the LOGIC-01..51 set. Where a new defect lives in an
already-reported file, its distinctness is called out in the finding doc (e.g. LOGIC-75 daily-loss in
`core/agents/agent-runtime/orchestrator.ts` is distinct from LOGIC-01's `core/risk-engine/trade-validator.ts`;
LOGIC-78's concurrent-mutation race is distinct from LOGIC-49's single-path telemetry double-count).

**Limitations:** No dynamic/penetration testing or on-chain execution. This is not a substitute for a
professional human security audit before any real-fund deployment.

---

## Findings Index

Each finding has a self-contained issue document under [`TEMP/logic-review-v3/`](./TEMP/logic-review-v3/) with
acceptance criteria, suggested labels, and an implementation stage. IDs (`LOGIC-NN`) are stable references. See
the [index & stage mapping](./TEMP/logic-review-v3/README.md) and the machine-readable
[`issues.json`](./TEMP/logic-review-v3/issues.json) manifest.

### High severity

| ID | Title | Area | File |
|----|-------|------|------|
| LOGIC-52 | Escrow release evaluates the wrong conditions field, so held funds are always released | Financial | `services/payments/payment-gateway.ts` |
| LOGIC-53 | Margin unrealized PnL is recomputed from the previous tick, not the entry price → understated liquidation loss | Financial | `services/prime-brokerage/margin-leverage.ts` |
| LOGIC-54 | Securities-lending collateral ratio divides by loan quantity instead of loan value → under-collateralized loans | Financial | `services/prime-brokerage/securities-lending.ts` |
| LOGIC-57 | Authorization decision cache short-circuits the rate-limit / daily-limit / anomaly layers, enabling replay | Security | `core/security/authorization.ts` |
| LOGIC-58 | OpenSanctions score-scale mismatch (0–1 vs a 70/85 threshold) silently disables entity sanctions screening | Regulatory | `services/regulatory/providers/opensanctions.ts` |
| LOGIC-62 | Capital-pool accounting only grows on gains and never shrinks on losses → phantom available capital | Strategy | `core/multi-agent/resources/capital-manager.ts` |
| LOGIC-68 | DeDust connector prices any TON-quoted token using a hardcoded $5.00 TON/USD constant | Financial | `core/market-data/base/connectors/dedust.ts` |
| LOGIC-74 | Health-monitor auto-pause / auto-suspend can never fire (its two heaviest anomaly inputs are dead signals) | Reliability | `core/agents/lifecycle/lifecycle-orchestrator.ts` |
| LOGIC-75 | Agent-runtime dailyLoss is never accumulated, so the daily-loss risk limit is completely unenforced | Financial | `core/agents/agent-runtime/orchestrator.ts` |

### Medium severity

| ID | Title | Area | File |
|----|-------|------|------|
| LOGIC-55 | Failed atomic settlement leaves the underlying instruction 'completed', inflating settled-value metrics | Financial | `services/clearing-house/settlement.ts` |
| LOGIC-59 | CSRF middleware never enforces the double-submit cookie invariant and is not session-bound | Security | `apps/api/src/middleware/chain.ts` |
| LOGIC-60 | strategy-executor.tact tracks the largest single loss instead of cumulative loss → max-loss auto-stop evaded | Security | `contracts/strategy-executor.tact` |
| LOGIC-61 | API-key scopes are silently ignored for admin and service roles, voiding least-privilege | Security | `services/auth/auth-service.ts` |
| LOGIC-63 | Engine backtest reports profitFactor as avg win/loss instead of gross profit / gross loss | Strategy | `core/strategies/engine/backtesting.ts` |
| LOGIC-64 | Router stream() omits the configured fallback chain that execute() includes → weaker streaming resilience | AI | `core/ai/routing/router.ts` |
| LOGIC-65 | Monte-Carlo CVaR divides by cvarIndex without the zero guard used elsewhere → NaN for small sims | Strategy | `core/strategies/engine/backtesting.ts` |
| LOGIC-69 | Market-data service caches fallback prices under a key it never reads → primary outage re-hits both providers | Reliability | `core/market-data/base/service.ts` |
| LOGIC-70 | ChangeNOW getEstimate ignores its reverse mode and always calls the direct endpoint | Financial | `services/omnichain/changenow-client.ts` |
| LOGIC-71 | ChangeNOW getCurrencies conflates isFiat with "active" and serves a cache keyed on a different active flag | Data | `services/omnichain/changenow-client.ts` |
| LOGIC-76 | dailyGasUsed / dailyTransactionCount are never reset at a day boundary → daily limits become permanent caps | Reliability | `core/agents/agent-runtime/orchestrator.ts` |
| LOGIC-77 | Scheduler executeAgent has no re-entrancy guard → triggerNow during an in-flight cycle double-runs the agent | Reliability | `core/runtime/agent-scheduler.ts` |
| LOGIC-78 | triggerAgent bypasses scheduler concurrency control and mutates the live shared state object | Reliability | `core/runtime/agent-manager.ts` |

### Low severity

| ID | Title | Area | File |
|----|-------|------|------|
| LOGIC-56 | Staking compoundRewards adds reinvested rewards to claimedRewards (double count) and reports a cross-stake total | Financial | `extended/tokenomics/staking.ts` |
| LOGIC-66 | rebalancePool uses the 0.1 threshold as a low-utilization floor (inverted semantics) → rebalances almost always | Strategy | `core/multi-agent/resources/capital-manager.ts` |
| LOGIC-67 | failDelegation retry resets the task to pending and increments retryCount but never re-delegates → orphaned task | Reliability | `core/multi-agent/delegation/task-queue.ts` |
| LOGIC-72 | Binance fallback symbol for USDT is the delisted USDTBUSD pair → USDT price lookups via the fallback always fail | Reliability | `core/market-data/base/config/assets.ts` |
| LOGIC-73 | OpenSanctions result mapping defaults unknown datasets to ofac_sdn and hardcodes every match as an individual | Data | `services/regulatory/providers/opensanctions.ts` |

---

## Cross-cutting theme: "Built but not wired" — now in the agent runtime

The single highest-leverage observation from the first two reviews holds a third time, and this pass shows it
has migrated into the **agent lifecycle and runtime** layer: the guards exist and are configurable, but the
state they read is never maintained.

- **LOGIC-74** — the health monitor scores three anomalies, but `failedExecutionsLastHour` is never
  incremented (only `executionsLastHour` is) and `lastHeartbeatAt` is only ever set to `null`, so riskScore
  maxes at 25 against auto-pause/suspend thresholds of 75/90. The safety mechanism is dead.
- **LOGIC-75** — trading is gated on `dailyLoss < maxDailyLoss`, but `dailyLoss` is initialised to `0` and
  never accumulated, so the primary per-agent capital-protection limit never trips.
- **LOGIC-76** — the daily gas/transaction budgets *are* accumulated but never reset, so a "daily" limit
  silently becomes a permanent lifetime cap that bricks the agent.
- **LOGIC-57 / LOGIC-58 / LOGIC-52** — an authorization cache that returns a prior decision without re-running
  the stateful layers; a sanctions score that can never cross its threshold; an escrow guard reading a field
  its own creation path never populates. Each is a guard that reads state nothing maintains, or a path that
  returns "safe/approved" on a not-safe condition.

These share a single root cause (a guard reading a field that no code writes, or a success/approve return on a
non-success condition) and should be prioritised together in **Stage 1** and **Stage 2**.

---

## Recommended remediation stages

| Stage | Theme | Findings |
|-------|-------|----------|
| **Stage 1 — Access control & safety re-wiring** | Make existing safety/access controls actually fire | LOGIC-57, LOGIC-58, LOGIC-59, LOGIC-60, LOGIC-61, LOGIC-74 |
| **Stage 2 — Funds & accounting correctness** | Money math, balances, collateral & settlement | LOGIC-52, LOGIC-53, LOGIC-54, LOGIC-55, LOGIC-56, LOGIC-62, LOGIC-75 |
| **Stage 3 — Runtime concurrency & daily-limit resets** | Per-agent windows, re-entrancy & state isolation | LOGIC-76, LOGIC-77, LOGIC-78 |
| **Stage 4 — Market data & connector integrity** | Trustworthy prices, caches & exchange metadata | LOGIC-68, LOGIC-69, LOGIC-70, LOGIC-71, LOGIC-72, LOGIC-73 |
| **Stage 5 — Strategy / backtest / optimizer integrity** | Trustworthy strategy/optimizer/backtest numbers & delegation | LOGIC-63, LOGIC-64, LOGIC-65, LOGIC-66, LOGIC-67 |

Each finding doc contains acceptance criteria scoped to a single PR; items within a stage can be parallelised.

---

## References

- Issue [#496](https://github.com/xlabtg/TONAIAgent/issues/496) — "Check via Claude"
- First logic review: [`AUDIT_REPORT_TONAIAgent_v2.43.0_LOGIC_REVIEW.md`](./AUDIT_REPORT_TONAIAgent_v2.43.0_LOGIC_REVIEW.md) (LOGIC-01..22, #386–#407)
- Second logic review: [`AUDIT_REPORT_TONAIAgent_v2.43.0_LOGIC_REVIEW_v2.md`](./AUDIT_REPORT_TONAIAgent_v2.43.0_LOGIC_REVIEW_v2.md) (LOGIC-23..51, #433–#461)
- Ready-to-file issue documents: [`TEMP/logic-review-v3/`](./TEMP/logic-review-v3/)

---

*This report was generated by automated AI logic analysis. It does not constitute a professional security audit
and should be supplemented with human expert review before any real-fund deployment. Every finding was verified
against the source at the stated path and line range on branch `issue-496-e3384416256d`.*

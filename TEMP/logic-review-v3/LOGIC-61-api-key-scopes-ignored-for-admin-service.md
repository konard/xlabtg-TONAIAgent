# LOGIC-61 — API-key scopes are silently ignored for admin and service roles, voiding least-privilege for the highest-privilege accounts

**Severity:** 🟠 Medium
**Area:** Security
**Stage:** Stage 1 — Access control & safety re-wiring
**Suggested labels:** `bug`, `security`, `severity:medium`, `area:security`, `stage:1-access-control`, `audit:logic-review-v3`
**Location:** `services/auth/auth-service.ts:192-213`
**Filed as:** [#509](https://github.com/xlabtg/TONAIAgent/issues/509)

## Problem
`check()` returns `{ allowed: true }` for `role === 'admin'` and `role === 'service'` before the API-key scope gate, so a deliberately narrow key minted by an admin/service user still passes any action check.

## Evidence
```ts
// services/auth/auth-service.ts:192-213
  check(ctx: AuthContext, action: ApiKeyScope, resourceId?: string): CheckResult {
    const { user } = ctx;

    // Admins can do anything
    if (user.role === 'admin') {
      this.writeAudit(user.id, 'access.granted', action, resourceId);
      return { allowed: true };
    }

    // Service accounts get full programmatic access
    if (user.role === 'service') {
      this.writeAudit(user.id, 'access.granted', action, resourceId);
      return { allowed: true };
    }

    // API key scope check
    if (ctx.source === 'api_key' && ctx.apiKey) {
      if (!this.apiKeyService.hasScope(ctx.apiKey, action)) {
        this.writeAudit(user.id, 'access.denied', action, resourceId, { reason: 'insufficient_scope' });
        return { allowed: false, reason: 'insufficient_scope' };
      }
    }
```

## Impact
A leaked "read-only" admin/service key (e.g. scopes `['agent:read']`) still passes `check(ctx, 'agent:execute', ...)` — scope confinement is void for exactly the highest-privilege accounts, giving a narrow key full capability, the opposite of the intended blast-radius reduction.

## Suggested fix
Evaluate the API-key scope gate before the role bypass (or intersect effective permissions with `ctx.apiKey.scopes`) so a key can only narrow, never widen, its owner's role authority.

## Acceptance criteria
- [ ] An admin-owned key scoped to `'agent:read'` is denied `'agent:execute'`.
- [ ] A key can never grant more than its scopes regardless of owner role.
- [ ] Regression test: asserts a narrow admin key is confined to its scopes.

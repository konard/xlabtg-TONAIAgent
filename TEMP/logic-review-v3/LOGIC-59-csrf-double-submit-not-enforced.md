# LOGIC-59 — CSRF middleware never enforces the double-submit cookie invariant and is not session-bound

**Severity:** 🟠 Medium
**Area:** Security
**Stage:** Stage 1 — Access control & safety re-wiring
**Suggested labels:** `bug`, `security`, `severity:medium`, `area:security`, `stage:1-access-control`, `audit:logic-review-v3`
**Location:** `apps/api/src/middleware/chain.ts:128-168; services/api/middleware/csrf.ts:90-128`
**Filed as:** _ready to file_

## Problem
The CSRF check validates only the HMAC and 24h TTL of the `X-CSRF-Token` header via `verifyCsrfToken`; it never reads the `csrf_token` cookie (`parseCsrfCookie` is unused in the request path) and never compares the token's embedded `sessionId` to the authenticated user. Tokens are issued freely (even on unauthenticated GETs) with a 24h TTL and no per-user binding.

## Evidence
```ts
// apps/api/src/middleware/chain.ts:128-168 — doc claims double-submit, code only verifies the header
  // Double-submit cookie pattern:
  //   1. Client reads csrf_token cookie (HttpOnly=false) issued by GET /healthz.
  //   2. Client echoes cookie value in X-CSRF-Token request header.
  //   3. Server verifies: header present, HMAC signature valid, not expired.
  //
  app.addHook('preHandler', async (req: FastifyRequest, reply: FastifyReply) => {
    if (SAFE_METHODS.has(req.method.toUpperCase())) return;

    // Skip CSRF on /healthz and /readyz (used by infrastructure probes)
    if (req.url.startsWith('/healthz') || req.url.startsWith('/readyz')) return;

    const csrfSecret = process.env['CSRF_SECRET'];
    if (!csrfSecret) {
      if (process.env['NODE_ENV'] === 'production') {
        return reply.code(403).send({
          success: false,
          error: 'CSRF_SECRET is not configured',
          code: 'CSRF_INVALID',
        });
      }
      // Dev mode: allow without token
      return;
    }

    const csrfHeader = req.headers['x-csrf-token'] as string | undefined;
    const result = verifyCsrfToken(csrfHeader, csrfSecret);
    // NOTE: the csrf_token cookie is never read here and no header↔cookie
    // comparison is performed; the authenticated sessionId is never passed in.
```

```ts
// services/api/middleware/csrf.ts:90-128 — verifyCsrfToken validates signature + TTL only,
// never binds the embedded sessionId to the authenticated user
export function verifyCsrfToken(
  token: string | undefined,
  secret: string,
): CsrfVerifyResult {
  if (!token || token.length === 0) {
    return { valid: false, reason: 'missing' };
  }

  const parts = token.split('.');
  if (parts.length !== 4) {
    return { valid: false, reason: 'malformed' };
  }

  const [nonce, issuedAtStr, sessionId, receivedSig] = parts;

  if (!nonce || !issuedAtStr || sessionId === undefined || !receivedSig) {
    return { valid: false, reason: 'malformed' };
  }

  const payload = `${nonce}.${issuedAtStr}.${sessionId}`;
  const expectedSig = sign(payload, secret);

  // Constant-time comparison to prevent timing attacks
  const expectedBuf = Buffer.from(expectedSig, 'utf8');
  const receivedBuf = Buffer.from(receivedSig, 'utf8');
  if (expectedBuf.length !== receivedBuf.length) {
    return { valid: false, reason: 'signature_mismatch' };
  }
  if (!timingSafeEqual(expectedBuf, receivedBuf)) {
    return { valid: false, reason: 'signature_mismatch' };
  }

  const issuedAt = parseInt(issuedAtStr, 10);
  if (isNaN(issuedAt) || Date.now() - issuedAt > TOKEN_TTL_MS) {
    return { valid: false, reason: 'expired' };
  }

  return { valid: true };
}
```

## Impact
The scheme degrades to "possess any valid signed token." The cookie↔header equality that stops a cross-site attacker (who cannot read the victim's cookie) is not enforced, and tokens are not user-bound, so any obtained token can be reused against another user's state-changing request. Outside production, a missing `CSRF_SECRET` skips CSRF entirely.

## Suggested fix
Read the `csrf_token` cookie and `timingSafeEqual`-compare it to the header (true double-submit), pass the authenticated `sessionId`/user into `verifyCsrfToken` so the embedded `sessionId` must match, and bind token issuance to an authenticated session.

## Acceptance criteria
- [ ] A request whose header token does not equal its cookie token is rejected.
- [ ] A token minted for session A is rejected on session B.
- [ ] Regression test: covers both the header≠cookie rejection and the cross-session rejection.

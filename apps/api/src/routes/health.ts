/**
 * Health check routes.
 *
 * GET /healthz  — liveness probe (always 200 while the process is up)
 * GET /readyz   — readiness probe (checks secrets-loader health)
 */

import type { FastifyInstance, FastifyRequest, FastifyReply } from 'fastify';
import { secrets } from '../../../../config/secrets.js';
import { generateCsrfToken } from '../../../../services/api/middleware/csrf.js';

export async function healthRoutes(app: FastifyInstance): Promise<void> {
  // ── GET /healthz — liveness ─────────────────────────────────────────────────
  //
  // Also issues a fresh CSRF cookie bound to the caller's authenticated
  // session. When CSRF_SECRET is not set (dev mode) the Set-Cookie header is
  // omitted rather than crashing.
  //
  app.get('/healthz', async (req: FastifyRequest, reply: FastifyReply) => {
    const csrfSecret = process.env['CSRF_SECRET'];
    const sessionId = req.headers['x-session-id'];
    if (csrfSecret && typeof sessionId === 'string' && sessionId.length > 0) {
      const { cookie } = generateCsrfToken(sessionId, csrfSecret);
      reply.header('Set-Cookie', cookie);
    }
    return reply.code(200).send({ status: 'ok', timestamp: new Date().toISOString() });
  });

  // ── GET /readyz — readiness ─────────────────────────────────────────────────
  app.get('/readyz', async (_req: FastifyRequest, reply: FastifyReply) => {
    const checks: Record<string, string> = {};
    let ready = true;

    // Secrets-loader health
    if (secrets) {
      const health = secrets.getHealth();
      checks['secrets'] = health.healthy ? 'ok' : (health.error ?? 'error');
      if (!health.healthy) ready = false;
    } else {
      checks['secrets'] = 'not_initialized';
      ready = false;
    }

    const statusCode = ready ? 200 : 503;
    return reply.code(statusCode).send({
      status: ready ? 'ok' : 'degraded',
      checks,
      timestamp: new Date().toISOString(),
    });
  });
}

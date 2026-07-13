/**
 * Regression tests for Monte Carlo risk metrics in BacktestingEngine.
 */

import { describe, expect, it } from 'vitest';
import { BacktestingEngine } from '../../core/strategies/engine/backtesting';
import type { BacktestConfig, Strategy } from '../../core/strategies/engine/types';

function makeStrategy(): Strategy {
  return {
    id: 'monte-carlo-test-strategy',
    name: 'Monte Carlo Test Strategy',
    description: '',
    type: 'rule_based',
    version: 1,
    status: 'active',
    userId: 'user1',
    agentId: 'agent1',
    createdAt: new Date(),
    updatedAt: new Date(),
    tags: [],
    metadata: {},
    definition: {
      triggers: [],
      conditions: [],
      actions: [],
      riskControls: [],
      parameters: [],
      capitalAllocation: { type: 'fixed', value: 1000, currency: 'USD' },
    },
  };
}

function makeConfig(): BacktestConfig {
  return {
    strategyId: 'monte-carlo-test-strategy',
    period: {
      start: new Date('2024-01-01'),
      end: new Date('2024-01-10'),
    },
    initialCapital: 10000,
    slippageModel: { type: 'fixed', baseSlippage: 0.001 },
    feeModel: { tradingFee: 0.003, gasCost: 0.05 },
    dataGranularity: '1d',
    monteCarlo: {
      enabled: true,
      simulations: 50,
      confidenceLevel: 0.99,
    },
  };
}

describe('BacktestingEngine — Monte Carlo CVaR (LOGIC-65)', () => {
  it('returns finite metrics when the configured tail contains fewer than one simulation', async () => {
    const result = await new BacktestingEngine().runBacktest(makeStrategy(), makeConfig());

    expect(result.status).toBe('completed');
    expect(result.monteCarlo).toBeDefined();
    expect(result.monteCarlo?.cvar95).toBe(result.monteCarlo?.var95);
    expect(Object.values(result.monteCarlo ?? {}).flat().every(Number.isFinite)).toBe(true);
  });
});

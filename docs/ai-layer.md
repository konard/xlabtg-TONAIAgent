# TONAIAgent - Multi-Provider AI Layer

## Overview

The TONAIAgent AI Layer provides a comprehensive, production-ready multi-provider AI abstraction that enables autonomous agents to leverage the best AI models for each task. Groq is the primary provider, offering ultra-low latency inference, with automatic fallback to Anthropic, OpenAI, Google, xAI, and OpenRouter.

### Key Features

- **Multi-Provider Support**: Groq (primary), Anthropic, OpenAI, Google, xAI, OpenRouter
- **Dynamic Routing**: Intelligent model selection based on task type, cost, and latency
- **Provider Fallback**: Automatic failover with circuit breaker pattern
- **User Model Selection**: Allow users to choose their preferred models
- **Memory System**: Short-term, long-term, and semantic memory
- **Safety Guardrails**: Prompt injection detection, content filtering, risk validation
- **Observability**: Event tracking, metrics, and monitoring

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Architecture](#architecture)
3. [Providers](#providers)
4. [Routing System](#routing-system)
5. [Memory Management](#memory-management)
6. [Safety & Guardrails](#safety--guardrails)
7. [Orchestration Engine](#orchestration-engine)
8. [Configuration](#configuration)
9. [API Reference](#api-reference)
10. [Best Practices](#best-practices)

---

## Quick Start

### Installation

```bash
npm install @tonaiagent/core
```

### Basic Usage

```typescript
import { createAIService } from '@tonaiagent/core';

// Create AI service with Groq as primary
const ai = createAIService({
  providers: {
    groq: { apiKey: process.env.GROQ_API_KEY },
    anthropic: { apiKey: process.env.ANTHROPIC_API_KEY },
    openai: { apiKey: process.env.OPENAI_API_KEY },
  },
});

// Simple chat
const response = await ai.chat([
  { role: 'user', content: 'Hello! How can you help me?' },
]);

console.log(response);
```

### Environment Variables

```bash
# Primary Provider (Groq)
GROQ_API_KEY=your-groq-api-key

# Fallback Providers
ANTHROPIC_API_KEY=your-anthropic-api-key
OPENAI_API_KEY=your-openai-api-key
GOOGLE_API_KEY=your-google-api-key
XAI_API_KEY=your-xai-api-key
OPENROUTER_API_KEY=your-openrouter-api-key
```

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      AI Service Layer                            │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Router    │  │   Memory    │  │    Safety Manager       │  │
│  │             │  │  Manager    │  │                         │  │
│  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘  │
│         │                │                      │                 │
│  ┌──────▼──────────────────────────────────────▼─────────────┐  │
│  │                 Orchestration Engine                        │  │
│  └─────────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────┤
│                      Provider Registry                           │
│  ┌─────┐ ┌─────────┐ ┌──────┐ ┌──────┐ ┌────┐ ┌──────────┐     │
│  │Groq │ │Anthropic│ │OpenAI│ │Google│ │xAI │ │OpenRouter│     │
│  │(P1) │ │  (P2)   │ │ (P3) │ │ (P4) │ │(P5)│ │   (P6)   │     │
│  └─────┘ └─────────┘ └──────┘ └──────┘ └────┘ └──────────┘     │
└─────────────────────────────────────────────────────────────────┘
```

### Core Components

| Component | Purpose |
|-----------|---------|
| **AI Service** | Unified entry point for all AI operations |
| **Provider Registry** | Manages and tracks provider availability |
| **Router** | Intelligent model selection and fallback |
| **Memory Manager** | Context and conversation history |
| **Safety Manager** | Input/output validation and guardrails |
| **Orchestration Engine** | Coordinates all components for agent execution |

---

## Providers

### Groq (Primary)

Groq is the default provider, optimized for ultra-low latency inference.

```typescript
import { createGroqProvider } from '@tonaiagent/core';

const groq = createGroqProvider({
  apiKey: process.env.GROQ_API_KEY,
  defaultModel: 'llama-3.3-70b-versatile',
  timeout: 120000,
  maxRetries: 3,
  rateLimit: {
    requestsPerMinute: 30,
    tokensPerMinute: 100000,
  },
});
```

**Available Models:**
- `llama-3.3-70b-versatile` - Recommended for most tasks
- `llama-3.1-70b-versatile` - Versatile general-purpose model
- `llama-3.1-8b-instant` - Ultra-fast for simple tasks
- `mixtral-8x7b-32768` - Mixture of experts model
- `gemma2-9b-it` - Google Gemma 2 model

### Anthropic

```typescript
import { createAnthropicProvider } from '@tonaiagent/core';

const anthropic = createAnthropicProvider({
  apiKey: process.env.ANTHROPIC_API_KEY,
  defaultModel: 'claude-sonnet-4-20250514',
});
```

**Available Models:**
- `claude-sonnet-4-20250514` - Best balance of speed and capability
- `claude-opus-4-20250514` - Most capable for complex tasks
- `claude-3-5-haiku-20241022` - Fast and efficient

### OpenAI

```typescript
import { createOpenAIProvider } from '@tonaiagent/core';

const openai = createOpenAIProvider({
  apiKey: process.env.OPENAI_API_KEY,
  defaultModel: 'gpt-4o',
});
```

**Available Models:**
- `gpt-4o` - Most capable multimodal model
- `gpt-4o-mini` - Fast and affordable
- `o1` / `o1-mini` - Advanced reasoning models

### Google AI

```typescript
import { createGoogleProvider } from '@tonaiagent/core';

const google = createGoogleProvider({
  apiKey: process.env.GOOGLE_API_KEY,
  defaultModel: 'gemini-2.0-flash',
});
```

**Available Models:**
- `gemini-2.0-flash` - Fast multimodal model
- `gemini-1.5-pro` - 2M context window
- `gemini-1.5-flash` - High-volume tasks

### xAI

```typescript
import { createXAIProvider } from '@tonaiagent/core';

const xai = createXAIProvider({
  apiKey: process.env.XAI_API_KEY,
  defaultModel: 'grok-3-fast',
});
```

**Available Models:**
- `grok-3` - Most capable Grok model
- `grok-3-fast` - Faster inference
- `grok-2` / `grok-2-mini` - Previous generation

### OpenRouter

Access 300+ models through a unified API:

```typescript
import { createOpenRouterProvider } from '@tonaiagent/core';

const openrouter = createOpenRouterProvider({
  apiKey: process.env.OPENROUTER_API_KEY,
  defaultModel: 'meta-llama/llama-3.3-70b-instruct',
  appName: 'TONAIAgent',
  siteUrl: 'https://tonaiagent.com',
});
```

---

## Routing System

The routing system intelligently selects the best model for each task.

### Routing Modes

| Mode | Description |
|------|-------------|
| `fast` | Prioritize speed over capability |
| `balanced` | Balance speed, cost, and capability |
| `quality` | Prioritize capability for complex tasks |
| `cost_optimized` | Minimize cost |

### Task Analysis

The router automatically analyzes requests to determine:

- **Task Type**: code_generation, reasoning, conversation, etc.
- **Complexity**: low, medium, high
- **Requirements**: tools, vision, reasoning

```typescript
import { createAIRouter, ProviderRegistry } from '@tonaiagent/core';

const registry = new ProviderRegistry();
// Register providers...

const router = createAIRouter(registry, {
  mode: 'balanced',
  primaryProvider: 'groq',
  fallbackChain: ['anthropic', 'openai', 'google'],
  maxLatencyMs: 5000,
  maxCostPerRequest: 0.1,
});

// Execute with automatic routing
const response = await router.execute({
  messages: [{ role: 'user', content: 'Write a sorting algorithm' }],
});
```

### Fallback Chain

When a provider fails, the system automatically falls back:

```
Groq → Anthropic → OpenAI → Google → xAI → OpenRouter
```

Circuit breakers prevent cascading failures:

```typescript
// Circuit opens after 5 failures
// Recovers after 30 seconds
// Tests with 3 half-open requests
```

---

## Memory Management

### Short-Term Memory

Session-based conversation history:

```typescript
import { createMemoryManager } from '@tonaiagent/core';

const memory = createMemoryManager({
  shortTermCapacity: 50,
  longTermEnabled: true,
  contextWindowRatio: 0.3,
});

// Add messages to session
memory.addToShortTerm('agent-1', 'session-1', {
  role: 'user',
  content: 'Hello!',
});

// Get conversation history
const history = memory.getShortTerm('agent-1', 'session-1');
```

### Long-Term Memory

Persistent memory with importance scoring:

```typescript
// Store important information
await memory.storeLongTerm(
  'agent-1',
  'User prefers concise responses',
  'preference',
  { source: 'user_statement' },
  0.8 // importance
);

// Retrieve relevant memories
const memories = await memory.retrieve({
  agentId: 'agent-1',
  types: ['preference', 'semantic'],
  minImportance: 0.5,
  limit: 10,
});
```

### Context Building

Automatically build context for requests:

```typescript
const contextMessages = await memory.buildContext(
  'agent-1',
  'session-1',
  'What was the user preference?',
  4000 // max tokens
);
```

---

## Safety & Guardrails

### Input Validation

```typescript
import { createSafetyManager } from '@tonaiagent/core';

const safety = createSafetyManager({
  inputValidation: {
    maxLength: 100000,
    detectPromptInjection: true,
    detectJailbreak: true,
    sanitizeHtml: true,
  },
});

const checks = safety.validateRequest({
  messages: [{ role: 'user', content: userInput }],
});

if (!safety.allPassed(checks)) {
  const severe = safety.getMostSevere(checks);
  console.error(`Blocked: ${severe?.reason}`);
}
```

### Content Filtering

```typescript
// Configure filtered categories
const safety = createSafetyManager({
  contentFiltering: {
    categories: ['hate', 'violence', 'dangerous', 'self_harm'],
    thresholds: {},
  },
});
```

### Financial Risk Validation

```typescript
const result = safety.validateTransaction({
  valueTon: 500,
  dailyTotalTon: 2000,
  transactionType: 'transfer',
  isNewDestination: true,
});

if (result.action === 'escalate') {
  // Require user confirmation
}

if (result.action === 'block') {
  throw new Error(result.reason);
}
```

### PII Detection & Redaction

```typescript
// Automatically detect and redact sensitive data
const redacted = safety.redactOutput(response);
// "Email: [REDACTED_EMAIL], Phone: [REDACTED_PHONE]"
```

---

## Orchestration Engine

Full agent execution with tool support:

```typescript
import { createOrchestrationEngine, DefaultToolExecutor } from '@tonaiagent/core';

// Set up tools
const toolExecutor = new DefaultToolExecutor();
toolExecutor.register('get_price', async (args) => {
  // Fetch token price
  return { price: 5.25, token: args.symbol };
});

// Create engine
const engine = createOrchestrationEngine(registry, {
  maxIterations: 10,
  timeoutMs: 120000,
  enableMemory: true,
  enableSafety: true,
  onEvent: (event) => console.log('Event:', event.type),
}, toolExecutor);

// Execute agent
const result = await engine.execute(
  {
    id: 'trading-agent',
    name: 'Trading Agent',
    userId: 'user-1',
    systemPrompt: 'You are a helpful trading assistant.',
    tools: [
      {
        type: 'function',
        function: {
          name: 'get_price',
          description: 'Get token price',
          parameters: {
            type: 'object',
            properties: {
              symbol: { type: 'string' },
            },
          },
        },
      },
    ],
    maxIterations: 10,
    timeoutMs: 60000,
    routingConfig: { mode: 'balanced' },
    safetyConfig: { enabled: true },
    memoryConfig: { enabled: true },
  },
  [{ role: 'user', content: 'What is the price of TON?' }],
  {
    agentId: 'trading-agent',
    sessionId: 'session-1',
    userId: 'user-1',
    requestId: 'req-1',
    startTime: new Date(),
  }
);

console.log('Success:', result.success);
console.log('Response:', result.response.choices[0].message.content);
console.log('Tool Results:', result.toolResults);
```

---

## Configuration

### Full Configuration Example

```typescript
import { createAIService, AIServiceConfig } from '@tonaiagent/core';

const config: AIServiceConfig = {
  // Provider configurations
  providers: {
    groq: {
      apiKey: process.env.GROQ_API_KEY,
      defaultModel: 'llama-3.3-70b-versatile',
      priority: 1,
      rateLimit: {
        requestsPerMinute: 30,
        tokensPerMinute: 100000,
      },
    },
    anthropic: {
      apiKey: process.env.ANTHROPIC_API_KEY,
      defaultModel: 'claude-sonnet-4-20250514',
      priority: 2,
    },
    openai: {
      apiKey: process.env.OPENAI_API_KEY,
      defaultModel: 'gpt-4o',
      priority: 3,
    },
  },

  // Routing configuration
  routing: {
    mode: 'balanced',
    primaryProvider: 'groq',
    fallbackChain: ['anthropic', 'openai', 'google'],
    maxLatencyMs: 5000,
    maxCostPerRequest: 0.1,
  },

  // Safety configuration
  safety: {
    enabled: true,
    inputValidation: {
      maxLength: 100000,
      detectPromptInjection: true,
      detectJailbreak: true,
    },
    contentFiltering: {
      categories: ['hate', 'violence', 'dangerous'],
    },
    riskThresholds: {
      maxTransactionValueTon: 1000,
      maxDailyTransactionsTon: 5000,
    },
  },

  // Feature flags
  enableMemory: true,
  enableSafety: true,
  enableObservability: true,

  // Event handling
  onEvent: (event) => {
    console.log(`[${event.type}] ${event.timestamp}`);
  },
};

const ai = createAIService(config);
```

---

## API Reference

### AIService

| Method | Description |
|--------|-------------|
| `complete(request)` | Execute a completion request |
| `chat(messages, options?)` | Simple chat interface |
| `stream(request, callback)` | Streaming completion |
| `executeAgent(agent, messages, context)` | Full agent execution |
| `streamAgent(agent, messages, context, callback)` | Streaming agent execution |
| `getProviders()` | List available providers |
| `getModels()` | List all available models |

### CompletionRequest

```typescript
interface CompletionRequest {
  messages: Message[];
  model?: string;
  temperature?: number;
  maxTokens?: number;
  topP?: number;
  tools?: ToolDefinition[];
  toolChoice?: 'auto' | 'none' | { type: 'function'; function: { name: string } };
  stream?: boolean;
}
```

### CompletionResponse

```typescript
interface CompletionResponse {
  id: string;
  provider: ProviderType;
  model: string;
  choices: CompletionChoice[];
  usage: UsageInfo;
  latencyMs: number;
  finishReason: FinishReason;
}
```

---

## Best Practices

### 1. Provider Priority

Configure Groq as primary for best latency:

```typescript
providers: {
  groq: { priority: 1 },     // Primary - fastest
  anthropic: { priority: 2 }, // Fallback - strong reasoning
  openai: { priority: 3 },    // Fallback - wide compatibility
}
```

### 2. Cost Optimization

Use `cost_optimized` mode and set budgets:

```typescript
routing: {
  mode: 'cost_optimized',
  maxCostPerRequest: 0.05,
}
```

### 3. Safety First

Always enable safety for user-facing applications:

```typescript
enableSafety: true,
safety: {
  enabled: true,
  inputValidation: { detectPromptInjection: true },
  riskThresholds: { maxTransactionValueTon: 100 },
}
```

### 4. Error Handling

Handle AI errors gracefully:

```typescript
import { AIError } from '@tonaiagent/core';

try {
  const response = await ai.complete(request);
} catch (error) {
  if (error instanceof AIError) {
    switch (error.code) {
      case 'RATE_LIMIT_EXCEEDED':
        // Wait and retry
        break;
      case 'SAFETY_VIOLATION':
        // Log and inform user
        break;
      case 'NO_AVAILABLE_PROVIDERS':
        // All providers failed
        break;
    }
  }
}
```

### 5. Observability

Enable event tracking for monitoring:

```typescript
onEvent: (event) => {
  // Send to monitoring system
  metrics.track(event.type, {
    provider: event.provider,
    latencyMs: event.latencyMs,
    success: event.success,
  });
}
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 0.1.0 | 2026-02-19 | Initial release with multi-provider support |

---

## License

MIT License - Copyright (c) 2026 TONAIAgent Team

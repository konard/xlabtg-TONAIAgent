# Personal Finance Layer

## Overview

The Personal Finance Layer is an AI-native platform that enables everyday users to automate savings, investments, and financial decisions through intelligent agents on The Open Network (TON).

This module positions the platform as:

- **The default financial assistant** for millions of users
- **A global AI-powered wealth management layer**
- **A bridge between Web2 and decentralized finance**

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     Personal Finance Manager                                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐           │
│  │     AI      │ │   Savings   │ │  Portfolio  │ │Personalization          │
│  │  Assistant  │ │ Automation  │ │   Manager   │ │   Manager   │           │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘           │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐                           │
│  │  Education  │ │Notifications│ │  Dashboard  │                           │
│  │   Manager   │ │  & Nudges   │ │   Manager   │                           │
│  └─────────────┘ └─────────────┘ └─────────────┘                           │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              AI Layer                                        │
│              (Groq Provider, Multi-Model Routing)                           │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Quick Start

### Installation

```typescript
import {
  createPersonalFinanceManager,
  PersonalFinanceConfig,
} from '@tonaiagent/core/personal-finance';
```

### Basic Setup

```typescript
// Create the personal finance manager
const finance = createPersonalFinanceManager();

// Create user profile with life-stage assessment
const profile = await finance.personalization.createProfile({
  userId: 'user-1',
  name: 'John Doe',
  monthlyIncome: 5000,
  monthlyExpenses: 3500,
  totalAssets: 50000,
  totalLiabilities: 10000,
  lifeStageAnswers: {
    employmentStatus: 'employed',
    yearsToRetirement: 30,
    dependents: 0,
    netWorthRange: 'medium',
    investmentExperience: 'beginner',
    primaryGoal: 'grow',
  },
});

// Start AI conversation
const conversation = await finance.aiAssistant.startConversation('user-1');
const response = await finance.aiAssistant.sendMessage(
  conversation.id,
  'Help me save more money'
);

console.log(response.message.content);
console.log('Suggested actions:', response.suggestedActions);
```

## Components

### 1. AI Financial Assistant

Natural language interface for financial guidance powered by Groq.

#### Features

- Portfolio advice and strategy suggestions
- Savings planning and goal setting
- Market insights and risk assessment
- Behavioral coaching
- Transaction assistance

#### Usage

```typescript
const aiAssistant = finance.aiAssistant;

// Start a conversation
const conversation = await aiAssistant.startConversation('user-1');

// Send messages and get AI responses
const response = await aiAssistant.sendMessage(
  conversation.id,
  'How can I prepare for retirement?'
);

// Response includes:
// - AI message with personalized advice
// - Relevant insights based on user profile
// - Suggested actions the user can take
// - Follow-up questions for clarification

// Execute suggested actions
if (response.suggestedActions.length > 0) {
  const action = response.suggestedActions[0];
  const result = await aiAssistant.executeAction(action.id, 'user-1', true);
}
```

### 2. Automated Savings

Smart savings automation with multiple strategies.

#### Savings Rule Types

| Type | Description | Use Case |
|------|-------------|----------|
| `fixed_amount` | Save a fixed amount regularly | Predictable savings |
| `percentage_of_income` | Save a % of each paycheck | Scales with income |
| `round_up` | Round up transactions | Painless micro-savings |
| `surplus` | Save % of monthly surplus | Adaptive to expenses |
| `goal_driven` | Contributions toward goals | Target-based saving |

#### Usage

```typescript
const savings = finance.savings;

// Create automated savings rule
const automation = await savings.createAutomation({
  userId: 'user-1',
  name: 'Weekly Savings',
  type: 'fixed_amount',
  rule: {
    type: 'fixed_amount',
    amount: 100,
    frequency: 'weekly',
    minBalance: 500, // Don't save if balance below this
  },
  allocation: {
    allocations: [
      { goalId: 'emergency-fund', percentage: 60, priority: 1 },
      { goalId: 'vacation', percentage: 40, priority: 2 },
    ],
  },
});

// Get savings suggestions
const suggestion = await savings.suggestSavingsAmount(
  'user-1',
  5000, // Monthly income
  3500  // Monthly expenses
);

// suggestion.recommendedAmount: $750
// suggestion.recommendedPercentage: 15%
// suggestion.impact.fiveYearProjection: $47,200

// Execute pending savings
const results = await savings.executePendingSavings('user-1');

// Get statistics
const stats = await savings.getStatistics('user-1');
```

### 3. Portfolio Manager

Personalized investment management with risk profiling.

#### Portfolio Types

| Type | Description | Risk Level |
|------|-------------|------------|
| `conservative` | Capital preservation focus | Low |
| `balanced` | Growth with stability | Medium |
| `growth` | Long-term appreciation | Medium-High |
| `aggressive` | Maximum growth potential | High |
| `income` | Yield generation focus | Low-Medium |

#### Usage

```typescript
const portfolio = finance.portfolio;

// Assess risk profile
const riskProfile = await portfolio.assessRiskProfile('user-1', {
  investmentExperience: 'moderate',
  investmentHorizon: 'long_term',
  volatilityComfort: 4,
  lossReaction: 'hold',
  incomeStability: 'stable',
  emergencyFund: 'adequate',
  investmentGoal: 'growth',
});

// Get suggested allocation based on risk profile
const allocation = await portfolio.suggestAllocation(riskProfile, 10000);

// Create portfolio
const userPortfolio = await portfolio.createPortfolio({
  userId: 'user-1',
  name: 'Main Portfolio',
  type: 'growth',
  initialInvestment: 10000,
  targetAllocation: allocation.allocations,
  automation: {
    dollarCostAveraging: {
      enabled: true,
      amount: 500,
      frequency: 'monthly',
      assets: [
        { symbol: 'TON', allocation: 40 },
        { symbol: 'ETH', allocation: 30 },
        { symbol: 'USDT', allocation: 30 },
      ],
    },
    autoRebalance: true,
  },
});

// Add holdings
await portfolio.addHolding(userPortfolio.id, {
  asset: 'Toncoin',
  assetClass: 'crypto',
  symbol: 'TON',
  quantity: 1000,
  averageCost: 5.0,
  currentPrice: 5.5,
});

// Check if rebalancing needed
const rebalanceCheck = await portfolio.checkRebalanceNeeded(userPortfolio.id);
if (rebalanceCheck.needsRebalancing) {
  const plan = await portfolio.generateRebalancePlan(userPortfolio.id);
  const result = await portfolio.executeRebalance(userPortfolio.id, plan);
}

// Get portfolio analysis
const analysis = await portfolio.analyzePortfolio(userPortfolio.id);
```

### 4. Life-Stage Personalization

Adapts financial strategies based on user life stage.

#### Life Stages

| Stage | Description | Focus |
|-------|-------------|-------|
| `beginner` | New to investing | Education, small starts |
| `early_career` | Young professional | Aggressive growth |
| `mid_career` | Established career | Balanced growth |
| `advanced` | Experienced investor | Optimization |
| `high_net_worth` | Significant wealth | Preservation |
| `pre_retirement` | Approaching retirement | Income transition |
| `retired` | In retirement | Income, preservation |

#### Usage

```typescript
const personalization = finance.personalization;

// Assess life stage
const lifeStageResult = await personalization.assessLifeStage({
  age: 35,
  employmentStatus: 'employed',
  yearsToRetirement: 30,
  dependents: 2,
  netWorthRange: 'medium',
  investmentExperience: 'intermediate',
  primaryGoal: 'grow',
});

// Get recommendations for life stage
const recommendations = await personalization.getLifeStageRecommendations(
  lifeStageResult.lifeStage
);

// recommendations include:
// - Overview of priorities
// - Suggested asset allocations
// - Recommended goals
// - Tips for this life stage
```

### 5. Behavioral Finance Layer

Reduces emotional decision-making with smart interventions.

#### Intervention Types

| Type | Trigger | Purpose |
|------|---------|---------|
| `panic_sell_prevention` | Selling during crash | Prevent emotional losses |
| `fomo_buy_prevention` | Buying during euphoria | Avoid buying peaks |
| `loss_aversion_coaching` | Excessive risk avoidance | Balance risk perception |
| `overtrading_warning` | High trading frequency | Reduce transaction costs |
| `confirmation_bias_alert` | One-sided research | Encourage diverse views |
| `patience_encouragement` | Impatient trading | Promote long-term thinking |

#### Usage

```typescript
const personalization = finance.personalization;

// Analyze behavior
const analysis = await personalization.analyzeBehavior('user-1');

// Check for intervention triggers
const intervention = await personalization.checkForIntervention('user-1', {
  trigger: 'sell',
  marketCondition: 'bear',
  userAction: 'sell_all',
  emotionalIndicators: ['panic', 'fear'],
  urgency: 'high',
});

if (intervention) {
  // Show intervention message to user
  console.log(intervention.message);
  // "Markets recover over time. Consider your long-term strategy..."
}

// Record decision for pattern detection
await personalization.recordDecision('user-1', {
  type: 'sell',
  asset: 'TON',
  amount: 1000,
  marketCondition: 'down',
  emotionalState: 'anxious',
  reason: 'Market fear',
  timestamp: new Date(),
});

// Detect patterns over time
const patterns = await personalization.detectPatterns('user-1');
```

### 6. Financial Education

Interactive learning with gamification.

#### Module Categories

- **Basics**: Budgeting, saving, compound interest
- **Investing**: Portfolios, diversification, risk
- **Crypto & DeFi**: Blockchain, staking, yield farming
- **Behavioral Finance**: Biases, emotions, psychology

#### Usage

```typescript
const education = finance.education;

// Get recommended modules
const modules = await education.getRecommendedModules('user-1');

// Start a learning session
const session = await education.startModule('user-1', 'mod_basics');

// Complete lessons
await education.completeLesson('user-1', 'mod_basics', 'les_budgeting');

// Take a quiz
const quizSession = await education.startQuiz('user-1', 'mod_basics', 'quiz_basics');
await education.submitQuizAnswer(quizSession.id, 'q1', '20%');
const quizResult = await education.completeQuiz(quizSession.id);

// Run a simulation
const simSession = await education.startSimulation('user-1', 'sim_portfolio');
await education.executeSimulationAction(simSession.id, {
  type: 'buy',
  asset: 'TON',
  amount: 2000,
});
const simResult = await education.completeSimulation(simSession.id);

// Check achievements
const achievements = await education.getAchievements('user-1');

// View leaderboard
const leaderboard = await education.getLeaderboard();
```

### 7. Notifications & Nudges

Smart notification system with behavioral nudges.

#### Notification Types

| Type | Description | Use Case |
|------|-------------|----------|
| `alert` | Important notifications | Market movements |
| `reminder` | Scheduled reminders | Savings reminders |
| `insight` | Financial insights | Progress updates |
| `opportunity` | Investment opportunities | Market conditions |
| `warning` | Risk warnings | Portfolio risks |
| `achievement` | Celebrations | Goal completions |
| `nudge` | Behavioral nudges | Habit building |

#### Usage

```typescript
const notifications = finance.notifications;

// Configure user notifications
await notifications.configureNotifications('user-1', {
  userId: 'user-1',
  enabled: true,
  channels: [
    { type: 'in_app', enabled: true },
    { type: 'push', enabled: true },
    { type: 'email', enabled: false },
  ],
  preferences: {
    marketAlerts: true,
    goalProgress: true,
    savingsReminders: true,
    investmentOpportunities: true,
    riskWarnings: true,
    educationalContent: true,
    behavioralNudges: true,
    weeklyDigest: true,
  },
  quietHours: {
    enabled: true,
    start: '22:00',
    end: '08:00',
    timezone: 'UTC',
    excludeUrgent: true,
  },
});

// Send a notification
await notifications.sendNotification({
  userId: 'user-1',
  type: 'achievement',
  category: 'goal',
  title: 'Goal Achieved!',
  message: 'You reached your emergency fund goal!',
  priority: 'high',
  action: {
    type: 'view_goal',
    label: 'View Goal',
  },
});

// Create a behavioral nudge
const nudge = await notifications.createNudge({
  userId: 'user-1',
  type: 'savings_reminder',
  message: 'You haven\'t saved this week. A small amount can make a big difference!',
  context: { timing: 'weekly' },
  trigger: { type: 'time', condition: 'weekly', parameters: {} },
});

// Send the nudge
await notifications.sendNudge(nudge.id);

// Register automatic triggers
await notifications.registerTrigger({
  userId: 'user-1',
  name: 'Market Drop Alert',
  condition: {
    type: 'market_change',
    parameters: { threshold: 10 }, // 10% drop
  },
  notification: {
    type: 'alert',
    category: 'market',
    title: 'Market Movement Alert',
    message: 'The market has moved significantly.',
    priority: 'high',
  },
  cooldownMinutes: 60 * 24, // Once per day
  enabled: true,
});
```

### 8. Financial Dashboard

Comprehensive financial overview with insights.

#### Dashboard Sections

- **Net Worth**: Total assets, liabilities, trends
- **Cash Flow**: Income, expenses, savings rate
- **Goals**: Progress, status, projections
- **Portfolio**: Holdings, performance, allocation
- **Savings**: Automation status, streaks
- **Risk**: Overall risk score, alerts
- **Insights**: AI-generated observations
- **Recommendations**: Actionable suggestions

#### Usage

```typescript
const dashboard = finance.dashboard;

// Generate full dashboard
const fullDashboard = await dashboard.generateDashboard('user-1');

// Access individual sections
const netWorth = await dashboard.getNetWorthSummary('user-1');
const cashFlow = await dashboard.getCashFlowSummary('user-1');
const goals = await dashboard.getGoalsSummary('user-1');
const portfolio = await dashboard.getPortfolioSummary('user-1');
const savings = await dashboard.getSavingsSummary('user-1');
const risk = await dashboard.getRiskSummary('user-1');

// Get AI-generated insights
const insights = await dashboard.generateInsights('user-1');

// Get actionable recommendations
const recommendations = await dashboard.generateRecommendations('user-1');

// Get historical data
const netWorthHistory = await dashboard.getNetWorthHistory('user-1', '1y');
const performanceHistory = await dashboard.getPerformanceHistory('user-1', '1y');
```

## Configuration

### Full Configuration Example

```typescript
const finance = createPersonalFinanceManager({
  aiAssistant: {
    enabled: true,
    primaryProvider: 'groq',
    personality: 'friendly',
    capabilities: [
      'portfolio_advice',
      'savings_planning',
      'goal_setting',
      'market_insights',
      'risk_assessment',
      'education',
      'behavioral_coaching',
    ],
    constraints: {
      maxSuggestionAmount: 10000,
      requireConfirmation: true,
      riskLevelLimit: 'aggressive',
      allowedAssetClasses: ['crypto', 'stablecoins', 'defi_yield'],
    },
    proactiveInsights: true,
  },
  savings: {
    enabled: true,
    minSaveAmount: 1,
    maxAutomatedSavePercent: 50,
    emergencyFundTarget: 6,
    defaultGoalType: 'savings',
  },
  investment: {
    enabled: true,
    minInvestmentAmount: 10,
    allowedAssetClasses: ['crypto', 'stablecoins', 'defi_yield', 'liquid_staking'],
    maxConcentration: 40,
    rebalanceThreshold: 5,
    dcaEnabled: true,
  },
  education: {
    enabled: true,
    adaptiveLearning: true,
    gamificationEnabled: true,
    simulationsEnabled: true,
  },
  notifications: {
    enabled: true,
    maxDailyNotifications: 10,
    nudgeFrequency: 'medium',
    digestEnabled: true,
  },
  behavioral: {
    enabled: true,
    interventionLevel: 'moderate',
    panicSellProtection: true,
    fomoBuyProtection: true,
  },
  privacy: {
    defaultTransparencyLevel: 'standard',
    dataRetentionDays: 365,
    allowAnonymizedAnalytics: true,
  },
});
```

## Events

Subscribe to events across all components:

```typescript
finance.onEvent((event) => {
  console.log(`[${event.type}] ${event.action}:`, event.details);
});

// Event types:
// - profile_created, profile_updated
// - goal_created, goal_updated, goal_completed
// - savings_automated, savings_executed
// - investment_made, portfolio_rebalanced
// - notification_sent, nudge_sent
// - education_completed
// - ai_interaction
// - behavioral_intervention
// - dashboard_viewed
```

## Security & Privacy

### User Data Control

- Users control what data is shared
- Transparent AI decision explanations
- Data export and deletion rights

### AI Governance

- Explainable AI decisions
- Human oversight for high-value actions
- Safety guardrails on automation

## Best Practices

1. **Start Simple**: Begin with basic savings automation before complex investments
2. **Assess Risk First**: Always complete risk profiling before portfolio creation
3. **Enable Nudges**: Behavioral nudges significantly improve outcomes
4. **Complete Education**: Higher literacy scores lead to better decisions
5. **Review Regularly**: Use the dashboard for regular financial check-ins

## API Reference

For detailed API documentation, see the TypeScript definitions in:

- `src/personal-finance/types.ts` - All type definitions
- `src/personal-finance/index.ts` - Exports and factory functions

/**
 * TON AI Agent - Telegram Mini App
 * Main Application Logic
 */

(function() {
  'use strict';

  // App State
  const state = {
    user: null,
    wallet: null,
    agents: [],
    strategies: [],
    selectedGoal: null,
    selectedStrategy: null,
    currentSection: 'main'
  };

  // Default Strategies (from MVP module)
  const DEFAULT_STRATEGIES = [
    {
      id: 'dca-basic',
      name: 'DCA Basic',
      category: 'dca',
      description: 'Dollar-cost averaging into selected assets',
      minInvestment: 10,
      riskLevel: 'low',
      expectedApy: { min: 5, max: 15 },
      creatorName: 'TON AI Agent'
    },
    {
      id: 'yield-farming',
      name: 'Yield Optimizer',
      category: 'yield',
      description: 'Automatically find and optimize yield farming opportunities',
      minInvestment: 50,
      riskLevel: 'medium',
      expectedApy: { min: 15, max: 40 },
      creatorName: 'TON AI Agent'
    },
    {
      id: 'liquidity-manager',
      name: 'Liquidity Manager',
      category: 'liquidity',
      description: 'Manage liquidity positions across DEXes',
      minInvestment: 100,
      riskLevel: 'medium',
      expectedApy: { min: 20, max: 50 },
      creatorName: 'TON AI Agent'
    },
    {
      id: 'rebalancer',
      name: 'Portfolio Rebalancer',
      category: 'trading',
      description: 'Automatically rebalance portfolio based on targets',
      minInvestment: 25,
      riskLevel: 'low',
      expectedApy: { min: 8, max: 20 },
      creatorName: 'TON AI Agent'
    },
    {
      id: 'arbitrage-bot',
      name: 'DEX Arbitrage',
      category: 'arbitrage',
      description: 'Capture arbitrage opportunities across DEXes',
      minInvestment: 200,
      riskLevel: 'high',
      expectedApy: { min: 30, max: 100 },
      creatorName: 'TON AI Agent'
    }
  ];

  // Sample ranking data
  const SAMPLE_RANKINGS = [
    { rank: 1, name: 'AlphaBot', score: 98.5, details: 'APY: 45.2% | Win Rate: 78%' },
    { rank: 2, name: 'YieldMaster', score: 95.3, details: 'APY: 38.7% | Win Rate: 82%' },
    { rank: 3, name: 'DCA Pro', score: 92.1, details: 'APY: 22.5% | Win Rate: 91%' },
    { rank: 4, name: 'LiquidityKing', score: 89.7, details: 'APY: 35.1% | Win Rate: 75%' },
    { rank: 5, name: 'ArbitrageX', score: 87.2, details: 'APY: 52.3% | Win Rate: 68%' }
  ];

  // DOM Elements
  const elements = {};

  /**
   * Initialize the application
   */
  function init() {
    // Cache DOM elements
    cacheElements();

    // Set up event listeners
    setupEventListeners();

    // Wait for Telegram WebApp to be ready
    window.addEventListener('tg:ready', onTelegramReady);

    // Initialize strategies
    state.strategies = DEFAULT_STRATEGIES;
  }

  /**
   * Cache DOM elements for performance
   */
  function cacheElements() {
    elements.loading = document.getElementById('loading');
    elements.mainContent = document.getElementById('main-content');
    elements.userAvatar = document.getElementById('user-avatar');
    elements.avatarInitials = document.getElementById('avatar-initials');
    elements.userName = document.getElementById('user-name');
    elements.userBalance = document.getElementById('user-balance');
    elements.portfolioAmount = document.getElementById('portfolio-amount');
    elements.portfolioChange = document.getElementById('portfolio-change');
    elements.activeAgents = document.getElementById('active-agents');
    elements.totalYield = document.getElementById('total-yield');
    elements.agentsSection = document.getElementById('agents-section');
    elements.emptyState = document.getElementById('empty-state');
    elements.agentsList = document.getElementById('agents-list');
    elements.agentsCount = document.getElementById('agents-count');
    elements.createAgentModal = document.getElementById('create-agent-modal');
    elements.marketplaceSection = document.getElementById('marketplace-section');
    elements.marketplaceList = document.getElementById('marketplace-list');
    elements.rankingsSection = document.getElementById('rankings-section');
    elements.rankingsList = document.getElementById('rankings-list');

    // Wizard elements
    elements.step1 = document.getElementById('step-1');
    elements.step2 = document.getElementById('step-2');
    elements.step3 = document.getElementById('step-3');
    elements.strategyOptions = document.getElementById('strategy-options');
    elements.strategySummary = document.getElementById('strategy-summary');
    elements.fundAmount = document.getElementById('fund-amount');
  }

  /**
   * Set up event listeners
   */
  function setupEventListeners() {
    // Quick action buttons
    document.getElementById('create-agent-btn')?.addEventListener('click', openCreateAgentModal);
    document.getElementById('empty-create-btn')?.addEventListener('click', openCreateAgentModal);
    document.getElementById('marketplace-btn')?.addEventListener('click', showMarketplace);
    document.getElementById('rankings-btn')?.addEventListener('click', showRankings);
    document.getElementById('settings-btn')?.addEventListener('click', openSettings);

    // Modal controls
    document.getElementById('close-modal-btn')?.addEventListener('click', closeCreateAgentModal);

    // Goal selection
    document.querySelectorAll('.goal-option').forEach(btn => {
      btn.addEventListener('click', () => selectGoal(btn.dataset.goal));
    });

    // Wizard navigation
    document.getElementById('back-to-step-1')?.addEventListener('click', () => showStep(1));
    document.getElementById('back-to-step-2')?.addEventListener('click', () => showStep(2));
    document.getElementById('deploy-agent-btn')?.addEventListener('click', deployAgent);

    // Section back buttons
    document.getElementById('marketplace-back')?.addEventListener('click', showMainSection);
    document.getElementById('rankings-back')?.addEventListener('click', showMainSection);

    // Filters
    document.getElementById('category-filter')?.addEventListener('change', filterMarketplace);
    document.getElementById('risk-filter')?.addEventListener('change', filterMarketplace);

    // Ranking tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => switchRankingTab(btn.dataset.tab));
    });

    // Telegram back button
    window.addEventListener('tg:backButtonClicked', handleBackButton);
  }

  /**
   * Handle Telegram WebApp ready event
   */
  function onTelegramReady(event) {
    const { user } = event.detail;
    state.user = user;

    // Update UI with user data
    updateUserInfo();

    // Hide loading, show main content
    elements.loading.classList.add('hidden');
    elements.mainContent.classList.remove('hidden');

    // Load user data from API (if available)
    loadUserData();
  }

  /**
   * Update user info in UI
   */
  function updateUserInfo() {
    if (!state.user) return;

    const firstName = state.user.first_name || 'User';
    const lastName = state.user.last_name || '';

    // Set name
    elements.userName.textContent = firstName;

    // Set avatar initials
    const initials = (firstName[0] + (lastName[0] || '')).toUpperCase();
    elements.avatarInitials.textContent = initials;
  }

  /**
   * Load user data from API
   */
  async function loadUserData() {
    try {
      // In production, this would call the backend API
      // For demo, we use mock data
      await simulateApiCall();

      // Update portfolio display
      updatePortfolio();
      updateAgentsList();
    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  }

  /**
   * Simulate API call delay
   */
  function simulateApiCall(delay = 500) {
    return new Promise(resolve => setTimeout(resolve, delay));
  }

  /**
   * Update portfolio display
   */
  function updatePortfolio() {
    const totalValue = state.agents.reduce((sum, agent) => sum + agent.value, 0);
    const totalPnl = state.agents.reduce((sum, agent) => sum + agent.pnl, 0);
    const pnlPercentage = totalValue > 0 ? (totalPnl / (totalValue - totalPnl)) * 100 : 0;

    elements.portfolioAmount.textContent = formatCurrency(totalValue);
    elements.portfolioChange.textContent = `${pnlPercentage >= 0 ? '+' : ''}${pnlPercentage.toFixed(2)}%`;
    elements.portfolioChange.className = `portfolio-change ${pnlPercentage >= 0 ? 'positive' : 'negative'}`;

    const activeCount = state.agents.filter(a => a.status === 'active').length;
    elements.activeAgents.textContent = activeCount;

    const avgYield = state.agents.length > 0
      ? state.agents.reduce((sum, a) => sum + a.apy, 0) / state.agents.length
      : 0;
    elements.totalYield.textContent = `${avgYield.toFixed(2)}%`;
  }

  /**
   * Update agents list
   */
  function updateAgentsList() {
    elements.agentsCount.textContent = state.agents.length;

    if (state.agents.length === 0) {
      elements.emptyState.classList.remove('hidden');
      elements.agentsList.classList.add('hidden');
      return;
    }

    elements.emptyState.classList.add('hidden');
    elements.agentsList.classList.remove('hidden');

    elements.agentsList.innerHTML = state.agents.map(agent => `
      <div class="agent-card" data-agent-id="${agent.id}">
        <div class="agent-card-header">
          <span class="agent-name">${escapeHtml(agent.name)}</span>
          <span class="agent-status ${agent.status}">${agent.status}</span>
        </div>
        <div class="agent-strategy">${escapeHtml(agent.strategyName)}</div>
        <div class="agent-stats">
          <div class="agent-stat">
            <span class="agent-stat-label">Value</span>
            <span class="agent-stat-value">$${formatCurrency(agent.value)}</span>
          </div>
          <div class="agent-stat">
            <span class="agent-stat-label">PnL</span>
            <span class="agent-stat-value ${agent.pnl >= 0 ? 'positive' : 'negative'}">
              ${agent.pnl >= 0 ? '+' : ''}$${formatCurrency(Math.abs(agent.pnl))}
            </span>
          </div>
          <div class="agent-stat">
            <span class="agent-stat-label">APY</span>
            <span class="agent-stat-value positive">${agent.apy.toFixed(1)}%</span>
          </div>
        </div>
      </div>
    `).join('');
  }

  /**
   * Open create agent modal
   */
  function openCreateAgentModal() {
    TelegramMiniApp.haptic.impactOccurred('light');
    elements.createAgentModal.classList.remove('hidden');
    showStep(1);
    TelegramMiniApp.showBackButton(closeCreateAgentModal);
  }

  /**
   * Close create agent modal
   */
  function closeCreateAgentModal() {
    elements.createAgentModal.classList.add('hidden');
    state.selectedGoal = null;
    state.selectedStrategy = null;
    TelegramMiniApp.hideBackButton();
  }

  /**
   * Select goal in wizard
   */
  function selectGoal(goal) {
    TelegramMiniApp.haptic.selectionChanged();
    state.selectedGoal = goal;

    // Update UI
    document.querySelectorAll('.goal-option').forEach(btn => {
      btn.classList.toggle('selected', btn.dataset.goal === goal);
    });

    // Filter strategies by goal
    const filteredStrategies = filterStrategiesByGoal(goal);
    renderStrategyOptions(filteredStrategies);

    // Move to step 2
    showStep(2);
  }

  /**
   * Filter strategies by goal
   */
  function filterStrategiesByGoal(goal) {
    const goalToCategory = {
      'passive-income': ['yield', 'dca'],
      'trading': ['trading', 'arbitrage'],
      'dca': ['dca'],
      'liquidity': ['liquidity']
    };

    const categories = goalToCategory[goal] || [];
    return state.strategies.filter(s => categories.includes(s.category));
  }

  /**
   * Render strategy options
   */
  function renderStrategyOptions(strategies) {
    elements.strategyOptions.innerHTML = strategies.map(strategy => `
      <button class="strategy-option" data-strategy-id="${strategy.id}">
        <div class="strategy-info">
          <span class="strategy-name">${escapeHtml(strategy.name)}</span>
          <span class="strategy-apy">APY: ${strategy.expectedApy.min}-${strategy.expectedApy.max}%</span>
        </div>
        <span class="strategy-risk ${strategy.riskLevel}">${strategy.riskLevel}</span>
      </button>
    `).join('');

    // Add click handlers
    elements.strategyOptions.querySelectorAll('.strategy-option').forEach(btn => {
      btn.addEventListener('click', () => selectStrategy(btn.dataset.strategyId));
    });
  }

  /**
   * Select strategy
   */
  function selectStrategy(strategyId) {
    TelegramMiniApp.haptic.selectionChanged();
    state.selectedStrategy = state.strategies.find(s => s.id === strategyId);

    // Update UI
    elements.strategyOptions.querySelectorAll('.strategy-option').forEach(btn => {
      btn.classList.toggle('selected', btn.dataset.strategyId === strategyId);
    });

    // Update strategy summary
    if (state.selectedStrategy) {
      elements.strategySummary.innerHTML = `
        <div><strong>${escapeHtml(state.selectedStrategy.name)}</strong></div>
        <div>${escapeHtml(state.selectedStrategy.description)}</div>
        <div>Expected APY: ${state.selectedStrategy.expectedApy.min}-${state.selectedStrategy.expectedApy.max}%</div>
        <div>Min Investment: ${state.selectedStrategy.minInvestment} TON</div>
      `;
      elements.fundAmount.min = state.selectedStrategy.minInvestment;
      elements.fundAmount.value = Math.max(state.selectedStrategy.minInvestment, 10);
    }

    // Move to step 3
    showStep(3);
  }

  /**
   * Show wizard step
   */
  function showStep(step) {
    elements.step1.classList.toggle('hidden', step !== 1);
    elements.step2.classList.toggle('hidden', step !== 2);
    elements.step3.classList.toggle('hidden', step !== 3);
  }

  /**
   * Deploy agent
   */
  async function deployAgent() {
    if (!state.selectedStrategy) {
      TelegramMiniApp.showAlert('Please select a strategy');
      return;
    }

    const amount = parseFloat(elements.fundAmount.value);
    if (isNaN(amount) || amount < state.selectedStrategy.minInvestment) {
      TelegramMiniApp.showAlert(`Minimum investment is ${state.selectedStrategy.minInvestment} TON`);
      return;
    }

    TelegramMiniApp.haptic.notificationOccurred('success');

    // Show loading state
    const deployBtn = document.getElementById('deploy-agent-btn');
    const originalText = deployBtn.textContent;
    deployBtn.textContent = 'Deploying...';
    deployBtn.disabled = true;

    try {
      // Simulate API call
      await simulateApiCall(1500);

      // Create new agent
      const newAgent = {
        id: `agent-${Date.now()}`,
        name: `Agent ${state.agents.length + 1}`,
        strategyId: state.selectedStrategy.id,
        strategyName: state.selectedStrategy.name,
        status: 'active',
        value: amount * 2.5, // Mock USD value
        pnl: 0,
        apy: state.selectedStrategy.expectedApy.min
      };

      state.agents.push(newAgent);

      // Update UI
      closeCreateAgentModal();
      updatePortfolio();
      updateAgentsList();

      TelegramMiniApp.showAlert('Agent deployed successfully!');
    } catch (error) {
      TelegramMiniApp.showAlert('Failed to deploy agent. Please try again.');
    } finally {
      deployBtn.textContent = originalText;
      deployBtn.disabled = false;
    }
  }

  /**
   * Show marketplace section
   */
  function showMarketplace() {
    TelegramMiniApp.haptic.impactOccurred('light');
    hideAllSections();
    elements.marketplaceSection.classList.remove('hidden');
    state.currentSection = 'marketplace';
    TelegramMiniApp.showBackButton(showMainSection);
    renderMarketplace();
  }

  /**
   * Render marketplace strategies
   */
  function renderMarketplace() {
    const categoryFilter = document.getElementById('category-filter').value;
    const riskFilter = document.getElementById('risk-filter').value;

    let filtered = [...state.strategies];

    if (categoryFilter !== 'all') {
      filtered = filtered.filter(s => s.category === categoryFilter);
    }

    if (riskFilter !== 'all') {
      filtered = filtered.filter(s => s.riskLevel === riskFilter);
    }

    elements.marketplaceList.innerHTML = filtered.map(strategy => `
      <div class="marketplace-card">
        <div class="marketplace-card-header">
          <div class="marketplace-card-info">
            <span class="marketplace-card-name">${escapeHtml(strategy.name)}</span>
            <span class="marketplace-card-creator">by ${escapeHtml(strategy.creatorName)}</span>
          </div>
          <span class="strategy-risk ${strategy.riskLevel}">${strategy.riskLevel}</span>
        </div>
        <p style="font-size: 14px; color: var(--text-secondary); margin-top: 8px;">
          ${escapeHtml(strategy.description)}
        </p>
        <div class="marketplace-card-stats">
          <div class="agent-stat">
            <span class="agent-stat-label">Expected APY</span>
            <span class="agent-stat-value positive">${strategy.expectedApy.min}-${strategy.expectedApy.max}%</span>
          </div>
          <div class="agent-stat">
            <span class="agent-stat-label">Min Investment</span>
            <span class="agent-stat-value">${strategy.minInvestment} TON</span>
          </div>
        </div>
        <div class="marketplace-card-actions">
          <button class="btn primary" style="width: 100%;" onclick="copyStrategy('${strategy.id}')">
            Copy Strategy
          </button>
        </div>
      </div>
    `).join('');
  }

  /**
   * Filter marketplace
   */
  function filterMarketplace() {
    renderMarketplace();
  }

  /**
   * Copy strategy (create agent with it)
   */
  window.copyStrategy = function(strategyId) {
    state.selectedStrategy = state.strategies.find(s => s.id === strategyId);
    showMainSection();
    openCreateAgentModal();
    showStep(3);
  };

  /**
   * Show rankings section
   */
  function showRankings() {
    TelegramMiniApp.haptic.impactOccurred('light');
    hideAllSections();
    elements.rankingsSection.classList.remove('hidden');
    state.currentSection = 'rankings';
    TelegramMiniApp.showBackButton(showMainSection);
    renderRankings();
  }

  /**
   * Render rankings
   */
  function renderRankings() {
    elements.rankingsList.innerHTML = SAMPLE_RANKINGS.map(item => `
      <div class="ranking-item">
        <div class="ranking-position ${item.rank <= 3 ? 'top-3' : ''}">${item.rank}</div>
        <div class="ranking-info">
          <span class="ranking-name">${escapeHtml(item.name)}</span>
          <span class="ranking-details">${escapeHtml(item.details)}</span>
        </div>
        <span class="ranking-score">${item.score}</span>
      </div>
    `).join('');
  }

  /**
   * Switch ranking tab
   */
  function switchRankingTab(tab) {
    TelegramMiniApp.haptic.selectionChanged();
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === tab);
    });
    // In production, this would load different rankings
    renderRankings();
  }

  /**
   * Show main section
   */
  function showMainSection() {
    hideAllSections();
    elements.agentsSection.classList.remove('hidden');
    state.currentSection = 'main';
    TelegramMiniApp.hideBackButton();
  }

  /**
   * Hide all sections
   */
  function hideAllSections() {
    elements.agentsSection.classList.add('hidden');
    elements.marketplaceSection.classList.add('hidden');
    elements.rankingsSection.classList.add('hidden');
  }

  /**
   * Handle back button
   */
  function handleBackButton() {
    if (state.currentSection !== 'main') {
      showMainSection();
    } else if (!elements.createAgentModal.classList.contains('hidden')) {
      closeCreateAgentModal();
    }
  }

  /**
   * Open settings
   */
  function openSettings() {
    TelegramMiniApp.haptic.impactOccurred('light');
    TelegramMiniApp.showPopup({
      title: 'Settings',
      message: 'Settings coming soon!',
      buttons: [{ type: 'ok' }]
    });
  }

  /**
   * Format currency
   */
  function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  }

  /**
   * Escape HTML
   */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

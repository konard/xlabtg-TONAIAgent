/**
 * TON AI Agent - Telegram WebApp Integration
 * Handles Telegram Mini App initialization and authentication
 */

(function() {
  'use strict';

  // Telegram WebApp instance
  const tg = window.Telegram?.WebApp;

  // Configuration
  const CONFIG = {
    apiEndpoint: '/api',
    debug: false
  };

  // State
  const state = {
    initialized: false,
    user: null,
    initData: null,
    theme: 'light'
  };

  /**
   * Initialize the Telegram WebApp
   */
  function init() {
    if (!tg) {
      console.warn('Telegram WebApp not available. Running in standalone mode.');
      initStandaloneMode();
      return;
    }

    // Expand the app to full height
    tg.expand();

    // Enable closing confirmation if needed
    tg.enableClosingConfirmation();

    // Get init data
    state.initData = tg.initData;
    state.user = tg.initDataUnsafe?.user || null;

    // Apply theme
    applyTelegramTheme();

    // Set up event listeners
    setupEventListeners();

    // Mark as initialized
    state.initialized = true;

    // Notify ready
    tg.ready();

    if (CONFIG.debug) {
      console.log('Telegram WebApp initialized:', state);
    }

    // Emit custom event
    window.dispatchEvent(new CustomEvent('tg:ready', { detail: state }));
  }

  /**
   * Initialize standalone mode (for testing outside Telegram)
   */
  function initStandaloneMode() {
    state.initialized = true;
    state.user = {
      id: 'demo_user',
      first_name: 'Demo',
      last_name: 'User',
      username: 'demo'
    };

    // Apply default theme
    document.documentElement.style.setProperty('--tg-theme-bg-color', '#ffffff');
    document.documentElement.style.setProperty('--tg-theme-text-color', '#000000');
    document.documentElement.style.setProperty('--tg-theme-hint-color', '#999999');
    document.documentElement.style.setProperty('--tg-theme-link-color', '#0088CC');
    document.documentElement.style.setProperty('--tg-theme-button-color', '#0088CC');
    document.documentElement.style.setProperty('--tg-theme-button-text-color', '#ffffff');
    document.documentElement.style.setProperty('--tg-theme-secondary-bg-color', '#f0f0f0');

    window.dispatchEvent(new CustomEvent('tg:ready', { detail: state }));
  }

  /**
   * Apply Telegram theme colors to CSS variables
   */
  function applyTelegramTheme() {
    if (!tg?.themeParams) return;

    const params = tg.themeParams;

    // Map Telegram theme params to CSS variables
    const mappings = {
      'bg_color': '--tg-theme-bg-color',
      'text_color': '--tg-theme-text-color',
      'hint_color': '--tg-theme-hint-color',
      'link_color': '--tg-theme-link-color',
      'button_color': '--tg-theme-button-color',
      'button_text_color': '--tg-theme-button-text-color',
      'secondary_bg_color': '--tg-theme-secondary-bg-color'
    };

    Object.entries(mappings).forEach(([param, cssVar]) => {
      if (params[param]) {
        document.documentElement.style.setProperty(cssVar, params[param]);
      }
    });

    // Determine if dark mode
    state.theme = tg.colorScheme || 'light';
    document.documentElement.setAttribute('data-theme', state.theme);
  }

  /**
   * Set up Telegram event listeners
   */
  function setupEventListeners() {
    if (!tg) return;

    // Theme change
    tg.onEvent('themeChanged', () => {
      applyTelegramTheme();
      window.dispatchEvent(new CustomEvent('tg:themeChanged', { detail: { theme: state.theme } }));
    });

    // Viewport change
    tg.onEvent('viewportChanged', (event) => {
      window.dispatchEvent(new CustomEvent('tg:viewportChanged', { detail: event }));
    });

    // Main button click
    tg.onEvent('mainButtonClicked', () => {
      window.dispatchEvent(new CustomEvent('tg:mainButtonClicked'));
    });

    // Back button click
    tg.onEvent('backButtonClicked', () => {
      window.dispatchEvent(new CustomEvent('tg:backButtonClicked'));
    });
  }

  /**
   * Get current user data
   */
  function getUser() {
    return state.user;
  }

  /**
   * Get init data for backend validation
   */
  function getInitData() {
    return state.initData;
  }

  /**
   * Check if running in Telegram
   */
  function isInTelegram() {
    return !!tg;
  }

  /**
   * Show the main button
   */
  function showMainButton(text, callback) {
    if (!tg) return;

    tg.MainButton.setText(text);
    tg.MainButton.show();

    if (callback) {
      tg.MainButton.onClick(callback);
    }
  }

  /**
   * Hide the main button
   */
  function hideMainButton() {
    if (!tg) return;
    tg.MainButton.hide();
  }

  /**
   * Show the back button
   */
  function showBackButton(callback) {
    if (!tg) return;

    tg.BackButton.show();

    if (callback) {
      tg.BackButton.onClick(callback);
    }
  }

  /**
   * Hide the back button
   */
  function hideBackButton() {
    if (!tg) return;
    tg.BackButton.hide();
  }

  /**
   * Show alert
   */
  function showAlert(message) {
    if (tg) {
      tg.showAlert(message);
    } else {
      alert(message);
    }
  }

  /**
   * Show confirm dialog
   */
  function showConfirm(message, callback) {
    if (tg) {
      tg.showConfirm(message, callback);
    } else {
      const result = confirm(message);
      if (callback) callback(result);
    }
  }

  /**
   * Show popup
   */
  function showPopup(params, callback) {
    if (tg) {
      tg.showPopup(params, callback);
    } else {
      // Fallback for standalone mode
      const result = confirm(params.message || params.title);
      if (callback) callback(result ? 'ok' : 'cancel');
    }
  }

  /**
   * Request phone number
   */
  function requestContact(callback) {
    if (tg) {
      tg.requestContact(callback);
    } else {
      callback({ status: 'cancelled' });
    }
  }

  /**
   * Open link
   */
  function openLink(url, options = {}) {
    if (tg) {
      tg.openLink(url, options);
    } else {
      window.open(url, '_blank');
    }
  }

  /**
   * Open Telegram link (e.g., t.me link)
   */
  function openTelegramLink(url) {
    if (tg) {
      tg.openTelegramLink(url);
    } else {
      window.open(url, '_blank');
    }
  }

  /**
   * Close the Mini App
   */
  function close() {
    if (tg) {
      tg.close();
    }
  }

  /**
   * Send data to bot
   */
  function sendData(data) {
    if (tg) {
      tg.sendData(JSON.stringify(data));
    }
  }

  /**
   * Haptic feedback
   */
  const haptic = {
    impactOccurred(style = 'medium') {
      if (tg?.HapticFeedback) {
        tg.HapticFeedback.impactOccurred(style);
      }
    },
    notificationOccurred(type = 'success') {
      if (tg?.HapticFeedback) {
        tg.HapticFeedback.notificationOccurred(type);
      }
    },
    selectionChanged() {
      if (tg?.HapticFeedback) {
        tg.HapticFeedback.selectionChanged();
      }
    }
  };

  /**
   * Make authenticated API request
   */
  async function apiRequest(endpoint, options = {}) {
    const url = `${CONFIG.apiEndpoint}${endpoint}`;

    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    // Include init data for authentication
    if (state.initData) {
      headers['X-Telegram-Init-Data'] = state.initData;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      });

      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('API Request failed:', error);
      throw error;
    }
  }

  // Export to global scope
  window.TelegramMiniApp = {
    init,
    getUser,
    getInitData,
    isInTelegram,
    showMainButton,
    hideMainButton,
    showBackButton,
    hideBackButton,
    showAlert,
    showConfirm,
    showPopup,
    requestContact,
    openLink,
    openTelegramLink,
    close,
    sendData,
    haptic,
    apiRequest,
    get state() { return { ...state }; }
  };

  // Auto-initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

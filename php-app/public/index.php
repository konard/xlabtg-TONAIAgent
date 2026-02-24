<?php
/**
 * TON AI Agent - Main Entry Point
 *
 * This is the public entry point for the application.
 * All requests are routed through this file.
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define paths
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', APP_ROOT . '/storage');

// Load configuration
$config = require APP_PATH . '/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Set error handler based on environment
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Load core files
require APP_PATH . '/db.php';
require APP_PATH . '/security.php';
require APP_PATH . '/router.php';
require APP_PATH . '/telegram.php';
require APP_PATH . '/ai.php';

// Initialize components
Security::init($config['security']);
Database::init($config['database']);

// Set security headers
Security::setSecurityHeaders();

// Create router
$router = new Router();

// ============================================
// API Routes
// ============================================

$router->group('/api', function($router) use ($config) {

    // Health check
    $router->get('/health', function() {
        Response::success([
            'status' => 'healthy',
            'timestamp' => time()
        ]);
    });

    // ----------------------
    // Authentication
    // ----------------------

    // Telegram Mini App authentication
    $router->post('/auth/telegram', function() use ($config) {
        $initData = Request::input('initData');

        if (empty($initData)) {
            Response::error('Missing initData', 400);
        }

        $miniApp = new TelegramMiniApp($config['telegram']['bot_token']);

        if (!$miniApp->authenticate($initData)) {
            Response::unauthorized('Invalid authentication');
        }

        $user = $miniApp->getUser();
        $token = $miniApp->generateSessionToken();

        // Get or create user in database (if database is configured)
        $userData = [
            'telegram_id' => $user['id'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'language_code' => $user['language_code'] ?? 'en',
            'is_premium' => $user['is_premium'] ?? false
        ];

        // Check for referral
        $startParam = $miniApp->getStartParam();
        if ($startParam && strpos($startParam, 'ref_') === 0) {
            $userData['referred_by'] = substr($startParam, 4);
        }

        Response::success([
            'user' => $userData,
            'token' => $token
        ], 'Authentication successful');
    });

    // Verify session token
    $router->post('/auth/verify', function() use ($config) {
        $token = Request::bearerToken();

        if (!$token) {
            Response::unauthorized('Missing token');
        }

        $miniApp = new TelegramMiniApp($config['telegram']['bot_token']);
        $payload = $miniApp->verifySessionToken($token);

        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }

        Response::success(['user_id' => $payload['user_id']]);
    });

    // ----------------------
    // Strategies
    // ----------------------

    $router->get('/strategies', function() {
        // Return available strategy templates
        $strategies = [
            [
                'id' => 'dca',
                'name' => 'Dollar Cost Averaging',
                'description' => 'Automatically invest fixed amounts at regular intervals',
                'risk_level' => 'low',
                'category' => 'accumulation',
                'min_investment' => 10,
                'performance_fee' => 10
            ],
            [
                'id' => 'yield_farming',
                'name' => 'Yield Farming',
                'description' => 'Optimize returns across DeFi protocols',
                'risk_level' => 'medium',
                'category' => 'yield',
                'min_investment' => 100,
                'performance_fee' => 15
            ],
            [
                'id' => 'liquidity_management',
                'name' => 'Liquidity Management',
                'description' => 'Provide liquidity and manage positions across DEXes',
                'risk_level' => 'medium',
                'category' => 'liquidity',
                'min_investment' => 500,
                'performance_fee' => 15
            ],
            [
                'id' => 'rebalancing',
                'name' => 'Portfolio Rebalancing',
                'description' => 'Maintain target allocations automatically',
                'risk_level' => 'low',
                'category' => 'portfolio',
                'min_investment' => 200,
                'performance_fee' => 10
            ],
            [
                'id' => 'arbitrage',
                'name' => 'Simple Arbitrage',
                'description' => 'Exploit price differences across exchanges',
                'risk_level' => 'high',
                'category' => 'trading',
                'min_investment' => 1000,
                'performance_fee' => 20
            ]
        ];

        Response::success($strategies);
    });

    $router->get('/strategies/{id}', function($params) {
        $strategyId = $params['id'] ?? '';

        // Return strategy details
        $strategies = [
            'dca' => [
                'id' => 'dca',
                'name' => 'Dollar Cost Averaging',
                'description' => 'Dollar Cost Averaging (DCA) is an investment strategy where you invest fixed amounts at regular intervals, regardless of market conditions. This approach helps reduce the impact of volatility and removes emotional decision-making.',
                'risk_level' => 'low',
                'category' => 'accumulation',
                'min_investment' => 10,
                'performance_fee' => 10,
                'parameters' => [
                    'investment_amount' => ['type' => 'number', 'min' => 10, 'default' => 100],
                    'frequency' => ['type' => 'select', 'options' => ['daily', 'weekly', 'monthly'], 'default' => 'weekly'],
                    'target_asset' => ['type' => 'select', 'options' => ['TON', 'USDT', 'BTC'], 'default' => 'TON']
                ],
                'expected_apy' => '5-15%',
                'suitable_for' => ['beginners', 'long-term investors', 'risk-averse users']
            ]
        ];

        if (!isset($strategies[$strategyId])) {
            Response::error('Strategy not found', 404);
        }

        Response::success($strategies[$strategyId]);
    });

    // ----------------------
    // AI Assistant
    // ----------------------

    $router->post('/ai/chat', function() use ($config) {
        // Rate limit check
        if (!Security::checkRateLimit()) {
            Response::error('Rate limit exceeded', 429);
        }

        // Validate CSRF for non-API requests
        if (!Request::wantsJson() && !Request::validateCsrf()) {
            Response::error('Invalid CSRF token', 403);
        }

        $message = Security::sanitizeString(Request::input('message'));

        if (empty($message) || strlen($message) < 2) {
            Response::error('Message is required', 400);
        }

        if (strlen($message) > 1000) {
            Response::error('Message too long', 400);
        }

        try {
            $ai = new AIProvider($config['ai']);
            $response = $ai->assistOnboarding($message);

            Response::success(['response' => $response]);
        } catch (Exception $e) {
            error_log('AI Error: ' . $e->getMessage());
            Response::error('AI service temporarily unavailable', 503);
        }
    });

    $router->post('/ai/recommend', function() use ($config) {
        if (!Security::checkRateLimit()) {
            Response::error('Rate limit exceeded', 429);
        }

        $profile = [
            'risk_tolerance' => Security::sanitizeString(Request::input('risk_tolerance', 'moderate')),
            'goal' => Security::sanitizeString(Request::input('goal', 'passive income')),
            'experience' => Security::sanitizeString(Request::input('experience', 'beginner')),
            'capital' => Security::sanitizeFloat(Request::input('capital', 100))
        ];

        try {
            $ai = new AIProvider($config['ai']);
            $recommendation = $ai->getStrategyRecommendations($profile);

            Response::success(['recommendation' => $recommendation]);
        } catch (Exception $e) {
            error_log('AI Error: ' . $e->getMessage());
            Response::error('AI service temporarily unavailable', 503);
        }
    });

    // ----------------------
    // Deep Links
    // ----------------------

    $router->get('/links/referral/{userId}', function($params) use ($config) {
        $userId = $params['userId'] ?? '';
        $bot = new TelegramBot($config['telegram']);
        $link = $bot->generateReferralLink($userId);

        Response::success(['link' => $link]);
    });

    $router->get('/links/strategy/{strategyId}', function($params) use ($config) {
        $strategyId = $params['strategyId'] ?? '';
        $bot = new TelegramBot($config['telegram']);
        $link = $bot->generateStrategyLink($strategyId);

        Response::success(['link' => $link]);
    });

    // ----------------------
    // Webhook
    // ----------------------

    $router->post('/webhook/telegram', function() use ($config) {
        // Validate webhook secret
        $bot = new TelegramBot($config['telegram']);
        if (!$bot->validateWebhookSecret($config['telegram']['webhook_secret'])) {
            Response::forbidden('Invalid secret');
        }

        $update = Request::json();

        // Log webhook for debugging
        if ($config['app']['debug']) {
            file_put_contents(
                STORAGE_PATH . '/logs/webhook.log',
                date('Y-m-d H:i:s') . ' ' . json_encode($update) . "\n",
                FILE_APPEND
            );
        }

        // Handle update
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            // Handle /start command
            if (strpos($text, '/start') === 0) {
                $startParam = trim(substr($text, 6));
                $parsed = $bot->parseStartParam($startParam);

                $welcomeText = "Welcome to TON AI Agent!\n\nYour gateway to autonomous AI-powered trading on TON blockchain.\n\nTap the button below to launch the app:";

                $bot->sendMiniAppButton(
                    $chatId,
                    $welcomeText,
                    'Launch App',
                    $startParam ?: null
                );
            }
        }

        Response::success();
    });

});

// ============================================
// Web Routes (Pages)
// ============================================

// Home page
$router->get('/', function() {
    include PUBLIC_PATH . '/views/home.php';
});

// Mini App page
$router->get('/app', function() {
    include PUBLIC_PATH . '/views/app.php';
});

// Dispatch request
$router->dispatch();

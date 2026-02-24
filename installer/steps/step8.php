<?php
/**
 * Step 8: Admin Dashboard Setup
 *
 * - Create super admin account
 * - Set localization preferences
 * - Configure analytics permissions
 */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegramId = trim($_POST['admin_tg_id'] ?? '');
    $username = trim($_POST['admin_username'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $passwordConfirm = $_POST['admin_password_confirm'] ?? '';
    $locale = trim($_POST['admin_locale'] ?? 'en');
    $timezone = trim($_POST['admin_timezone'] ?? 'UTC');

    // Validate inputs
    $errors = [];

    if (empty($username)) {
        $errors[] = __('error_required');
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('error_invalid_email');
    }

    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = __('error_password_weak');
        }
        if ($password !== $passwordConfirm) {
            $errors[] = __('error_password_mismatch');
        }
    }

    if (!empty($errors)) {
        $_SESSION['installer_error'] = implode(' ', $errors);
        header('Location: ?step=8');
        exit;
    }

    // Hash password if provided
    $passwordHash = !empty($password) ? password_hash($password, PASSWORD_ARGON2ID) : null;

    // Save admin config
    $_SESSION['installer_admin'] = [
        'telegram_id' => $telegramId,
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'locale' => $locale,
        'timezone' => $timezone,
    ];

    // Now finalize installation - generate config files
    $success = finalizeInstallation();

    if ($success) {
        // Mark as installed
        @file_put_contents(APP_ROOT . '/.installed', date('Y-m-d H:i:s'));

        $_SESSION['installer_success'] = __('admin_created');
        header('Location: ?step=9');
        exit;
    } else {
        $_SESSION['installer_error'] = __('error_file_write');
        header('Location: ?step=8');
        exit;
    }
}

/**
 * Generate all configuration files
 */
function finalizeInstallation(): bool {
    $db = $_SESSION['installer_db'] ?? [];
    $tg = $_SESSION['installer_telegram'] ?? [];
    $miniapp = $_SESSION['installer_miniapp'] ?? [];
    $ai = $_SESSION['installer_ai'] ?? [];
    $ton = $_SESSION['installer_ton'] ?? [];
    $sec = $_SESSION['installer_security'] ?? [];
    $admin = $_SESSION['installer_admin'] ?? [];

    // Generate config.php content
    $config = generateConfigFile($db, $tg, $miniapp, $ai, $ton, $sec, $admin);

    // Write config file to telegram-miniapp/app/config.php
    $configPath = APP_ROOT . '/telegram-miniapp/app/config.php';
    if (!@file_put_contents($configPath, $config)) {
        return false;
    }
    @chmod($configPath, 0600);

    // Generate .env file
    $env = generateEnvFile($db, $tg, $miniapp, $ai, $ton, $sec);
    $envPath = APP_ROOT . '/telegram-miniapp/.env';
    @file_put_contents($envPath, $env);
    @chmod($envPath, 0600);

    // Create necessary directories
    $dirs = [
        APP_ROOT . '/telegram-miniapp/logs',
        APP_ROOT . '/telegram-miniapp/cache',
        APP_ROOT . '/telegram-miniapp/storage',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    // Set up webhook if we have a valid bot token
    if (!empty($tg['bot_token']) && !empty($tg['webhook_url'])) {
        $webhookSecret = $sec['webhook_secret'] ?? '';
        $ch = curl_init("https://api.telegram.org/bot{$tg['bot_token']}/setWebhook");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'url' => $tg['webhook_url'],
                'secret_token' => $webhookSecret,
                'allowed_updates' => ['message', 'callback_query', 'inline_query'],
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // Create admin user in database
    if (!empty($db['host']) && !empty($admin['username'])) {
        try {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $prefix = $db['prefix'] ?? 'taa_';
            $telegramId = $admin['telegram_id'] ?: mt_rand(100000000, 999999999);

            // Insert admin user
            $stmt = $pdo->prepare("
                INSERT INTO {$prefix}users (telegram_id, username, first_name, language_code, subscription_tier, created_at)
                VALUES (?, ?, ?, ?, 'institutional', NOW())
                ON DUPLICATE KEY UPDATE username = VALUES(username)
            ");
            $stmt->execute([$telegramId, $admin['username'], 'Admin', $admin['locale'] ?? 'en']);

        } catch (Exception $e) {
            // Continue even if this fails - not critical
            error_log('Admin user creation failed: ' . $e->getMessage());
        }
    }

    return true;
}

/**
 * Generate config.php content
 */
function generateConfigFile($db, $tg, $miniapp, $ai, $ton, $sec, $admin): string {
    $date = date('Y-m-d H:i:s');

    // Escape values for PHP
    $escape = function($val) {
        return addslashes((string)$val);
    };

    return <<<PHP
<?php
/**
 * TON AI Agent - Configuration File
 * Generated by installer on {$date}
 *
 * IMPORTANT: Keep this file secure and outside public web root
 */

// Prevent direct access
if (basename(\$_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Direct access forbidden');
}

return [
    'app' => [
        'name' => 'TON AI Agent',
        'version' => '1.0.0',
        'env' => 'production',
        'debug' => false,
        'url' => '{$escape($miniapp['url'] ?? '')}',
        'timezone' => '{$escape($admin['timezone'] ?? 'UTC')}',
        'secret_key' => '{$escape($sec['app_secret'] ?? '')}',
    ],

    'database' => [
        'driver' => 'mysql',
        'host' => '{$escape($db['host'] ?? 'localhost')}',
        'port' => {$db['port'] ?? 3306},
        'database' => '{$escape($db['database'] ?? '')}',
        'username' => '{$escape($db['username'] ?? '')}',
        'password' => '{$escape($db['password'] ?? '')}',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '{$escape($db['prefix'] ?? 'taa_')}',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ],
    ],

    'telegram' => [
        'bot_token' => '{$escape($tg['bot_token'] ?? '')}',
        'bot_username' => '{$escape($tg['bot_username'] ?? '')}',
        'webhook_secret' => '{$escape($sec['webhook_secret'] ?? '')}',
        'mini_app_url' => '{$escape($miniapp['url'] ?? '')}',
        'verify_signature' => true,
    ],

    'ton' => [
        'network' => '{$escape($ton['network'] ?? 'mainnet')}',
        'rpc_endpoint' => '{$escape($ton['rpc_endpoint'] ?? '')}',
        'api_key' => '{$escape($ton['api_key'] ?? '')}',
        'wallet_version' => '{$escape($ton['wallet_version'] ?? 'v4r2')}',
    ],

    'ai' => [
        'default_provider' => '{$escape($ai['default_provider'] ?? 'groq')}',
        'providers' => [
            'groq' => [
                'api_key' => '{$escape($ai['providers']['groq']['api_key'] ?? '')}',
                'model' => '{$escape($ai['providers']['groq']['model'] ?? 'llama-3.1-70b-versatile')}',
                'max_tokens' => 4096,
            ],
            'openai' => [
                'api_key' => '{$escape($ai['providers']['openai']['api_key'] ?? '')}',
                'model' => '{$escape($ai['providers']['openai']['model'] ?? 'gpt-4-turbo-preview')}',
                'max_tokens' => 4096,
            ],
            'anthropic' => [
                'api_key' => '{$escape($ai['providers']['anthropic']['api_key'] ?? '')}',
                'model' => '{$escape($ai['providers']['anthropic']['model'] ?? 'claude-3-opus-20240229')}',
                'max_tokens' => 4096,
            ],
        ],
        'server_side_only' => true,
    ],

    'security' => [
        'csrf' => [
            'enabled' => {$sec['csrf_enabled'] ? 'true' : 'false'},
            'token_name' => '_csrf_token',
            'token_length' => 32,
        ],
        'rate_limit' => [
            'enabled' => {$sec['rate_limit']['enabled'] ? 'true' : 'false'},
            'max_requests' => {$sec['rate_limit']['max_requests'] ?? 60},
            'time_window' => {$sec['rate_limit']['time_window'] ?? 60},
        ],
        'session' => [
            'name' => 'TAASESSID',
            'lifetime' => {$sec['session']['lifetime'] ?? 7200},
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ],
        'csp' => [
            'enabled' => true,
            'directives' => [
                'default-src' => "'self'",
                'script-src' => "'self' 'unsafe-inline' https://telegram.org",
                'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
                'font-src' => "'self' https://fonts.gstatic.com",
                'img-src' => "'self' data: https:",
                'connect-src' => "'self' https://api.telegram.org https://toncenter.com",
            ],
        ],
    ],

    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'path' => __DIR__ . '/../logs',
        'max_files' => 30,
    ],

    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../cache',
        'ttl' => 3600,
        'prefix' => 'taa_cache_',
    ],

    'revenue' => [
        'performance_fee_percent' => 15,
        'management_fee_percent' => 2,
        'platform_share' => 30,
        'min_withdrawal' => 10,
    ],

    'premium' => [
        'tiers' => [
            'basic' => ['price' => 0, 'max_agents' => 1],
            'pro' => ['price' => 29, 'max_agents' => 5],
            'institutional' => ['price' => 299, 'max_agents' => 50],
        ],
    ],

    'localization' => [
        'default_locale' => '{$escape($admin['locale'] ?? 'en')}',
        'supported_locales' => ['en', 'ru', 'zh', 'ar'],
        'fallback_locale' => 'en',
    ],
];
PHP;
}

/**
 * Generate .env file content
 */
function generateEnvFile($db, $tg, $miniapp, $ai, $ton, $sec): string {
    return <<<ENV
# TON AI Agent - Environment Configuration
# Generated by installer on {date('Y-m-d H:i:s')}
# NEVER commit this file to version control

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL={$miniapp['url']}
APP_SECRET={$sec['app_secret']}
APP_TIMEZONE=UTC

# Database
DB_HOST={$db['host']}
DB_PORT={$db['port']}
DB_DATABASE={$db['database']}
DB_USERNAME={$db['username']}
DB_PASSWORD={$db['password']}
DB_PREFIX={$db['prefix']}

# Telegram Bot
TELEGRAM_BOT_TOKEN={$tg['bot_token']}
TELEGRAM_BOT_USERNAME={$tg['bot_username']}
TELEGRAM_WEBHOOK_SECRET={$sec['webhook_secret']}
TELEGRAM_MINI_APP_URL={$miniapp['url']}

# TON Blockchain
TON_NETWORK={$ton['network']}
TON_RPC_ENDPOINT={$ton['rpc_endpoint']}
TON_API_KEY={$ton['api_key']}

# AI Providers
AI_DEFAULT_PROVIDER={$ai['default_provider']}
GROQ_API_KEY={$ai['providers']['groq']['api_key']}
GROQ_MODEL={$ai['providers']['groq']['model']}
OPENAI_API_KEY={$ai['providers']['openai']['api_key']}
OPENAI_MODEL={$ai['providers']['openai']['model']}
ANTHROPIC_API_KEY={$ai['providers']['anthropic']['api_key']}
ANTHROPIC_MODEL={$ai['providers']['anthropic']['model']}

# Cache
CACHE_DRIVER=file

# Logging
LOG_LEVEL=info
ENV;
}

// Load saved values
$savedAdmin = $_SESSION['installer_admin'] ?? [];

$stepData['admin'] = [
    'telegram_id' => $savedAdmin['telegram_id'] ?? '',
    'username' => $savedAdmin['username'] ?? 'admin',
    'email' => $savedAdmin['email'] ?? '',
    'locale' => $savedAdmin['locale'] ?? 'en',
    'timezone' => $savedAdmin['timezone'] ?? 'UTC',
];

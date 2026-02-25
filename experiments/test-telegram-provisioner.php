<?php
/**
 * Test Script: Telegram Bot Auto-Provisioner
 *
 * This script tests the TelegramBotProvisioner class functionality.
 * Run from command line: php experiments/test-telegram-provisioner.php
 *
 * Usage:
 *   php test-telegram-provisioner.php <bot_token> [webhook_url] [mini_app_url]
 *
 * Examples:
 *   php test-telegram-provisioner.php 123456789:ABC...
 *   php test-telegram-provisioner.php 123456789:ABC... https://example.com/webhook
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load provisioner
require_once __DIR__ . '/../php-app/app/telegram-provisioner.php';

echo "===========================================\n";
echo "  TON AI Agent - Telegram Provisioner Test\n";
echo "===========================================\n\n";

// Get bot token from command line
$botToken = $argv[1] ?? '';
$webhookUrl = $argv[2] ?? null;
$miniAppUrl = $argv[3] ?? null;

if (empty($botToken)) {
    echo "Usage: php test-telegram-provisioner.php <bot_token> [webhook_url] [mini_app_url]\n\n";
    echo "Example:\n";
    echo "  php test-telegram-provisioner.php 123456789:ABCdefGHI...\n";
    echo "  php test-telegram-provisioner.php 123456789:ABCdefGHI... https://example.com/webhook\n";
    exit(1);
}

// Create provisioner with verbose logging
$provisioner = new TelegramBotProvisioner($botToken, true);

// ============================================
// Test 1: Token Validation
// ============================================
echo "Test 1: Token Validation\n";
echo str_repeat('-', 40) . "\n";

$result = $provisioner->validateToken();
printResult($result);

if (!$result->success) {
    echo "\n❌ Token validation failed. Cannot proceed with other tests.\n";
    exit(1);
}

$botInfo = $provisioner->getBotInfo();
echo "Bot Username: @" . ($botInfo['username'] ?? 'unknown') . "\n";
echo "Bot ID: " . ($botInfo['id'] ?? 'unknown') . "\n";
echo "\n";

// ============================================
// Test 2: Get Current Commands
// ============================================
echo "Test 2: Get Current Commands\n";
echo str_repeat('-', 40) . "\n";

$result = $provisioner->getCommands();
printResult($result);

if ($result->success && !empty($result->data['commands'])) {
    echo "Current commands:\n";
    foreach ($result->data['commands'] as $cmd) {
        echo "  /{$cmd['command']} - {$cmd['description']}\n";
    }
}
echo "\n";

// ============================================
// Test 3: Set Default Commands (Multi-Language)
// ============================================
echo "Test 3: Set Default Commands (Multi-Language)\n";
echo str_repeat('-', 40) . "\n";

$result = $provisioner->setDefaultCommands(['en', 'ru']);
printResult($result);
echo "\n";

// ============================================
// Test 4: Webhook Info
// ============================================
echo "Test 4: Get Webhook Info\n";
echo str_repeat('-', 40) . "\n";

$result = $provisioner->getWebhookInfo();
printResult($result);

if ($result->success) {
    $info = $result->data;
    echo "Current webhook URL: " . ($info['url'] ?: '(none)') . "\n";
    echo "Pending updates: " . ($info['pending_update_count'] ?? 0) . "\n";
    echo "Is healthy: " . ($info['is_healthy'] ? 'Yes' : 'No') . "\n";
    if (!empty($info['issues'])) {
        echo "Issues:\n";
        foreach ($info['issues'] as $issue) {
            echo "  - $issue\n";
        }
    }
}
echo "\n";

// ============================================
// Test 5: Webhook Configuration (if URL provided)
// ============================================
if ($webhookUrl) {
    echo "Test 5: Set Webhook\n";
    echo str_repeat('-', 40) . "\n";

    // First validate URL
    $urlResult = $provisioner->validateWebhookUrl($webhookUrl);
    printResult($urlResult);

    if ($urlResult->success || $urlResult->data['url'] ?? false) {
        $secret = bin2hex(random_bytes(32));
        $result = $provisioner->setWebhook($webhookUrl, $secret);
        printResult($result);

        if ($result->success) {
            echo "Webhook secret (save this): $secret\n";
        }
    }
    echo "\n";
}

// ============================================
// Test 6: Menu Button (if Mini App URL provided)
// ============================================
if ($miniAppUrl) {
    echo "Test 6: Set Chat Menu Button\n";
    echo str_repeat('-', 40) . "\n";

    $result = $provisioner->setChatMenuButton($miniAppUrl, 'Open TON AI Agent');
    printResult($result);
    echo "\n";
}

// ============================================
// Test 7: Run Health Checks
// ============================================
echo "Test 7: Health Checks\n";
echo str_repeat('-', 40) . "\n";

$healthChecks = $provisioner->runHealthChecks([]);

foreach ($healthChecks as $check) {
    $statusIcon = match($check->status) {
        'pass' => '✅',
        'fail' => '❌',
        'warn' => '⚠️',
        default => '❓',
    };

    echo "$statusIcon {$check->name}: {$check->message}";
    if ($check->duration > 0) {
        echo " (" . round($check->duration, 2) . "ms)";
    }
    echo "\n";

    if ($check->suggestion) {
        echo "   Suggestion: {$check->suggestion}\n";
    }
}
echo "\n";

// ============================================
// Test 8: Full Provisioning Flow
// ============================================
echo "Test 8: Full Provisioning Flow (Dry Run)\n";
echo str_repeat('-', 40) . "\n";

$config = [
    'set_commands' => true,
    'command_languages' => ['en'],
    'run_health_checks' => true,
];

// Only add webhook if provided
if ($webhookUrl) {
    $config['webhook_url'] = $webhookUrl;
    $config['webhook_secret'] = bin2hex(random_bytes(32));
}

// Only add mini app if provided
if ($miniAppUrl) {
    $config['mini_app_url'] = $miniAppUrl;
    $config['menu_button_text'] = 'Open App';
}

$provisioningResult = $provisioner->provisionBot($config);

echo "Overall Success: " . ($provisioningResult['overall_success'] ? '✅ Yes' : '❌ No') . "\n";
echo "Steps completed:\n";
foreach ($provisioningResult['steps'] as $step => $data) {
    $success = is_array($data) ? ($data['success'] ?? false) : false;
    echo "  - $step: " . ($success ? '✅' : '❌') . "\n";
}
echo "\n";

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "  Test Summary\n";
echo "===========================================\n";
echo "Bot: @" . ($botInfo['username'] ?? 'unknown') . "\n";
echo "All core tests passed: " . ($result->success ? '✅ Yes' : '❌ No') . "\n";
echo "\n";

// Print logs
echo "Provisioner Logs:\n";
foreach ($provisioner->getLogs() as $log) {
    $levelIcon = match($log['level']) {
        'info' => 'ℹ️',
        'warning' => '⚠️',
        'error' => '❌',
        default => '📝',
    };
    echo "  $levelIcon [{$log['timestamp']}] {$log['message']}\n";
}

/**
 * Helper to print ProvisioningResult
 */
function printResult(ProvisioningResult $result): void {
    $icon = $result->success ? '✅' : '❌';
    echo "$icon {$result->message}\n";

    if (!$result->success && $result->errorCode) {
        echo "   Error code: {$result->errorCode}\n";
    }

    if (!empty($result->suggestions)) {
        echo "   Suggestions:\n";
        foreach ($result->suggestions as $suggestion) {
            echo "     - $suggestion\n";
        }
    }
}

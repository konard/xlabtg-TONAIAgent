<?php
/**
 * Test Script: Telegram Authentication System
 *
 * This script tests the TelegramAuth class with HMAC validation,
 * replay protection, and session token management.
 *
 * Run from command line: php experiments/test-telegram-auth.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session for nonce storage
session_start();

// Load auth classes
require_once __DIR__ . '/../php-app/app/telegram-auth.php';

echo "===========================================\n";
echo "  TON AI Agent - Telegram Auth Test\n";
echo "===========================================\n\n";

// Test bot token (use a real one for actual testing)
$botToken = $argv[1] ?? '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';

// Create auth handler
$auth = new TelegramAuth($botToken, [
    'auth_validity_seconds' => 3600,
    'enable_replay_protection' => true,
]);

// ============================================
// Test 1: Generate Test Init Data
// ============================================
echo "Test 1: Generate Test Init Data\n";
echo str_repeat('-', 40) . "\n";

$testUser = [
    'id' => 123456789,
    'first_name' => 'Test',
    'last_name' => 'User',
    'username' => 'testuser',
    'language_code' => 'en',
    'is_premium' => false,
];

$testInitData = generateTestInitData($testUser, $botToken);
echo "Generated init data:\n";
echo "  " . substr($testInitData, 0, 80) . "...\n\n";

// ============================================
// Test 2: Validate Init Data
// ============================================
echo "Test 2: Validate Init Data\n";
echo str_repeat('-', 40) . "\n";

$result = $auth->authenticate($testInitData);
printAuthResult($result);
echo "\n";

// ============================================
// Test 3: Invalid Signature Detection
// ============================================
echo "Test 3: Invalid Signature Detection\n";
echo str_repeat('-', 40) . "\n";

$tamperedData = str_replace('testuser', 'hackeduser', $testInitData);
$result = $auth->authenticate($tamperedData);
printAuthResult($result);
echo "Expected: INVALID_SIGNATURE - " . ($result->errorCode === 'INVALID_SIGNATURE' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// ============================================
// Test 4: Expired Data Detection
// ============================================
echo "Test 4: Expired Data Detection\n";
echo str_repeat('-', 40) . "\n";

$expiredData = generateTestInitData($testUser, $botToken, time() - 7200); // 2 hours ago
$result = $auth->authenticate($expiredData);
printAuthResult($result);
echo "Expected: AUTH_EXPIRED - " . ($result->errorCode === 'AUTH_EXPIRED' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// ============================================
// Test 5: Replay Protection
// ============================================
echo "Test 5: Replay Protection\n";
echo str_repeat('-', 40) . "\n";

// First authentication should succeed
$freshData = generateTestInitData($testUser, $botToken);
$result1 = $auth->authenticate($freshData);
echo "First auth: ";
printAuthResult($result1);

// Second authentication with same data should fail (replay)
$result2 = $auth->authenticate($freshData);
echo "Replay attempt: ";
printAuthResult($result2);
echo "Expected: REPLAY_DETECTED - " . ($result2->errorCode === 'REPLAY_DETECTED' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// ============================================
// Test 6: Session Token Generation
// ============================================
echo "Test 6: Session Token Generation\n";
echo str_repeat('-', 40) . "\n";

// Get fresh auth result
$freshData2 = generateTestInitData($testUser, $botToken);
$authResult = $auth->authenticate($freshData2);

if ($authResult->valid) {
    $sessionToken = $auth->generateSessionToken($authResult, 3600);
    echo "Generated session token:\n";
    echo "  " . substr($sessionToken, 0, 60) . "...\n";

    // Verify the token
    $tokenData = $auth->verifySessionToken($sessionToken);
    echo "Token verified: " . ($tokenData ? '✅ Yes' : '❌ No') . "\n";
    if ($tokenData) {
        echo "  User ID: " . ($tokenData['user_id'] ?? 'unknown') . "\n";
        echo "  Expires: " . date('Y-m-d H:i:s', $tokenData['exp'] ?? 0) . "\n";
    }
} else {
    echo "❌ Cannot test session tokens without valid auth\n";
}
echo "\n";

// ============================================
// Test 7: Session Token Refresh
// ============================================
echo "Test 7: Session Token Refresh\n";
echo str_repeat('-', 40) . "\n";

if (isset($sessionToken)) {
    $refreshedToken = $auth->refreshSessionToken($sessionToken, 7200);
    if ($refreshedToken) {
        echo "Token refreshed: ✅ Yes\n";
        $newData = $auth->verifySessionToken($refreshedToken);
        echo "  New expiration: " . date('Y-m-d H:i:s', $newData['exp'] ?? 0) . "\n";
    } else {
        echo "Token refresh failed: ❌\n";
    }
}
echo "\n";

// ============================================
// Test 8: Invalid Token Verification
// ============================================
echo "Test 8: Invalid Token Verification\n";
echo str_repeat('-', 40) . "\n";

$fakeToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.fake.signature';
$result = $auth->verifySessionToken($fakeToken);
echo "Fake token verified: " . ($result === null ? '✅ Rejected (correct)' : '❌ Accepted (wrong)') . "\n\n";

// ============================================
// Test 9: Start Parameter Parsing
// ============================================
echo "Test 9: Start Parameter Parsing\n";
echo str_repeat('-', 40) . "\n";

$testParams = [
    'ref_user123',
    'strategy_dca_ton',
    'agent_abc456',
    'invite_xyz789',
    'campaign_summer2026',
    'custom_value',
    '',
];

foreach ($testParams as $param) {
    $parsed = $auth->parseStartParam($param);
    echo sprintf("  %-20s => type: %-10s value: %s\n",
        $param ?: '(empty)',
        $parsed['type'],
        $parsed['value'] ?? '(null)'
    );
}
echo "\n";

// ============================================
// Test 10: Nonce Storage Cleanup
// ============================================
echo "Test 10: Nonce Storage Cleanup\n";
echo str_repeat('-', 40) . "\n";

$auth->cleanupNonces();
echo "Nonce cleanup completed: ✅\n\n";

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "  Test Summary\n";
echo "===========================================\n";
echo "All tests completed. Review results above.\n";
echo "\n";
echo "Note: For production testing, use a real bot token\n";
echo "and test with actual Telegram WebApp init data.\n";

/**
 * Generate test init data for authentication testing
 */
function generateTestInitData(array $user, string $botToken, ?int $authDate = null): string {
    $authDate = $authDate ?? time();

    $data = [
        'user' => json_encode($user),
        'auth_date' => (string) $authDate,
        'query_id' => 'AAGdF6IQAAAAAN0XohC3XZQR',
    ];

    // Sort alphabetically
    ksort($data);

    // Build data check string
    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[] = "$key=$value";
    }
    $dataCheckString = implode("\n", $pairs);

    // Calculate secret key
    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

    // Calculate hash
    $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

    // Add hash to data
    $data['hash'] = $hash;

    return http_build_query($data);
}

/**
 * Print TelegramAuthResult
 */
function printAuthResult(TelegramAuthResult $result): void {
    $icon = $result->valid ? '✅' : '❌';
    echo "$icon Valid: " . ($result->valid ? 'Yes' : 'No');

    if ($result->valid && $result->user) {
        echo " (User: " . ($result->user['username'] ?? $result->user['id']) . ")";
    }

    if (!$result->valid && $result->error) {
        echo " - Error: {$result->error}";
    }

    echo "\n";
}

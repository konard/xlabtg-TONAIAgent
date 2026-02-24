<?php
/**
 * Step 3: Telegram Bot Configuration
 *
 * - Validate bot token
 * - Auto-detect bot username
 * - Configure webhook
 * - Set bot commands
 */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $botToken = trim($_POST['bot_token'] ?? '');
    $botUsername = trim($_POST['bot_username'] ?? '');

    if (empty($botToken)) {
        $_SESSION['installer_error'] = __('error_required');
        header('Location: ?step=3');
        exit;
    }

    // Validate bot token via Telegram API
    $response = @file_get_contents("https://api.telegram.org/bot{$botToken}/getMe");

    if (!$response) {
        $_SESSION['installer_error'] = __('tg_invalid');
        header('Location: ?step=3');
        exit;
    }

    $data = json_decode($response, true);

    if (!($data['ok'] ?? false)) {
        $_SESSION['installer_error'] = __('tg_invalid');
        header('Location: ?step=3');
        exit;
    }

    // Auto-detect username if not provided
    if (empty($botUsername)) {
        $botUsername = $data['result']['username'] ?? '';
    }

    // Generate webhook secret
    $webhookSecret = bin2hex(random_bytes(32));

    // Determine app URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;

    // Calculate webhook URL (will be set later)
    $webhookUrl = $baseUrl . '/webhook.php';

    // Set up bot commands
    $commands = [
        ['command' => 'start', 'description' => 'Start the bot and open Mini App'],
        ['command' => 'app', 'description' => 'Open TON AI Agent Mini App'],
        ['command' => 'help', 'description' => 'Get help and documentation'],
        ['command' => 'portfolio', 'description' => 'View your portfolio'],
        ['command' => 'agents', 'description' => 'Manage your AI agents'],
    ];

    // Set bot commands via API
    $ch = curl_init("https://api.telegram.org/bot{$botToken}/setMyCommands");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['commands' => $commands]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    curl_exec($ch);
    curl_close($ch);

    // Save to session
    $_SESSION['installer_telegram'] = [
        'bot_token' => $botToken,
        'bot_username' => $botUsername,
        'webhook_secret' => $webhookSecret,
        'webhook_url' => $webhookUrl,
        'base_url' => $baseUrl,
    ];

    $_SESSION['installer_success'] = __('tg_valid') . ' ' . __('tg_commands_set');
    header('Location: ?step=4');
    exit;
}

// Load saved values
$savedTg = $_SESSION['installer_telegram'] ?? [];

// Detect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $savedTg['base_url'] ?? ($protocol . '://' . $host);

$stepData['telegram'] = [
    'bot_token' => $savedTg['bot_token'] ?? '',
    'bot_username' => $savedTg['bot_username'] ?? '',
    'webhook_url' => $savedTg['webhook_url'] ?? ($baseUrl . '/webhook.php'),
    'base_url' => $baseUrl,
];

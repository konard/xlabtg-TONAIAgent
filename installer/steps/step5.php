<?php
/**
 * Step 5: AI Provider Setup
 *
 * - Configure AI providers (Groq, OpenAI, Anthropic)
 * - Validate API keys
 * - Set up fallback providers
 */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $defaultProvider = trim($_POST['ai_default'] ?? 'groq');
    $groqKey = trim($_POST['groq_api_key'] ?? '');
    $groqModel = trim($_POST['groq_model'] ?? 'llama-3.1-70b-versatile');
    $openaiKey = trim($_POST['openai_api_key'] ?? '');
    $openaiModel = trim($_POST['openai_model'] ?? 'gpt-4-turbo-preview');
    $anthropicKey = trim($_POST['anthropic_api_key'] ?? '');
    $anthropicModel = trim($_POST['anthropic_model'] ?? 'claude-3-opus-20240229');

    // At least one provider should be configured
    if (empty($groqKey) && empty($openaiKey) && empty($anthropicKey)) {
        $_SESSION['installer_error'] = 'At least one AI provider API key is required.';
        header('Location: ?step=5');
        exit;
    }

    // Validate the default provider has a key
    $providerKeys = [
        'groq' => $groqKey,
        'openai' => $openaiKey,
        'anthropic' => $anthropicKey,
    ];

    if (empty($providerKeys[$defaultProvider])) {
        // Switch to first available provider
        foreach ($providerKeys as $provider => $key) {
            if (!empty($key)) {
                $defaultProvider = $provider;
                break;
            }
        }
    }

    // Optional: Validate API keys (quick test)
    $validated = true;
    $validationErrors = [];

    // Validate Groq key
    if (!empty($groqKey)) {
        $ch = curl_init('https://api.groq.com/openai/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $groqKey"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $validationErrors[] = 'Groq API key validation failed. Please check your key.';
        }
    }

    // Save to session (proceed even with validation errors - allow user to fix later)
    $_SESSION['installer_ai'] = [
        'default_provider' => $defaultProvider,
        'providers' => [
            'groq' => [
                'api_key' => $groqKey,
                'model' => $groqModel,
            ],
            'openai' => [
                'api_key' => $openaiKey,
                'model' => $openaiModel,
            ],
            'anthropic' => [
                'api_key' => $anthropicKey,
                'model' => $anthropicModel,
            ],
        ],
    ];

    if (!empty($validationErrors)) {
        $_SESSION['installer_success'] = 'AI providers saved with warnings: ' . implode(' ', $validationErrors);
    } else {
        $_SESSION['installer_success'] = __('ai_test_success');
    }

    header('Location: ?step=6');
    exit;
}

// Load saved values
$savedAi = $_SESSION['installer_ai'] ?? [];

$stepData['ai'] = [
    'default_provider' => $savedAi['default_provider'] ?? 'groq',
    'providers' => $savedAi['providers'] ?? [
        'groq' => ['api_key' => '', 'model' => 'llama-3.1-70b-versatile'],
        'openai' => ['api_key' => '', 'model' => 'gpt-4-turbo-preview'],
        'anthropic' => ['api_key' => '', 'model' => 'claude-3-opus-20240229'],
    ],
];

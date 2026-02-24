<?php
/**
 * Step 5 Template: AI Provider Setup
 */

$ai = $stepData['ai'] ?? [];
$providers = $ai['providers'] ?? [];
$defaultProvider = $ai['default_provider'] ?? 'groq';
?>

<form method="POST" autocomplete="off">
    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Configure at least one AI provider. Groq is recommended for fastest inference.
    </p>

    <div class="form-group">
        <label><?= __('ai_default') ?></label>
        <div class="provider-cards">
            <label class="provider-card <?= $defaultProvider === 'groq' ? 'selected' : '' ?>">
                <input type="radio" name="ai_default" value="groq" <?= $defaultProvider === 'groq' ? 'checked' : '' ?>>
                <h4><?= __('ai_groq') ?></h4>
                <p><?= __('ai_groq_desc') ?></p>
            </label>
            <label class="provider-card <?= $defaultProvider === 'openai' ? 'selected' : '' ?>">
                <input type="radio" name="ai_default" value="openai" <?= $defaultProvider === 'openai' ? 'checked' : '' ?>>
                <h4><?= __('ai_openai') ?></h4>
                <p><?= __('ai_openai_desc') ?></p>
            </label>
            <label class="provider-card <?= $defaultProvider === 'anthropic' ? 'selected' : '' ?>">
                <input type="radio" name="ai_default" value="anthropic" <?= $defaultProvider === 'anthropic' ? 'checked' : '' ?>>
                <h4><?= __('ai_anthropic') ?></h4>
                <p><?= __('ai_anthropic_desc') ?></p>
            </label>
        </div>
    </div>

    <!-- Groq Configuration -->
    <div class="collapsible open">
        <div class="collapsible-header">
            <span>Groq (Recommended)</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="collapsible-content">
            <div class="form-row">
                <div class="form-group">
                    <label for="groq_api_key"><?= __('ai_api_key') ?></label>
                    <input type="password" class="form-control" id="groq_api_key" name="groq_api_key"
                           value="<?= htmlspecialchars($providers['groq']['api_key'] ?? '') ?>"
                           placeholder="gsk_..." autocomplete="new-password">
                    <span class="form-hint">Get from <a href="https://console.groq.com" target="_blank" style="color: var(--primary-light);">console.groq.com</a></span>
                </div>
                <div class="form-group">
                    <label for="groq_model"><?= __('ai_model') ?></label>
                    <select class="form-control" id="groq_model" name="groq_model">
                        <option value="llama-3.1-70b-versatile" <?= ($providers['groq']['model'] ?? '') === 'llama-3.1-70b-versatile' ? 'selected' : '' ?>>Llama 3.1 70B</option>
                        <option value="llama-3.1-8b-instant" <?= ($providers['groq']['model'] ?? '') === 'llama-3.1-8b-instant' ? 'selected' : '' ?>>Llama 3.1 8B (Fast)</option>
                        <option value="mixtral-8x7b-32768" <?= ($providers['groq']['model'] ?? '') === 'mixtral-8x7b-32768' ? 'selected' : '' ?>>Mixtral 8x7B</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- OpenAI Configuration -->
    <div class="collapsible">
        <div class="collapsible-header">
            <span>OpenAI (Fallback)</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="collapsible-content">
            <div class="form-row">
                <div class="form-group">
                    <label for="openai_api_key"><?= __('ai_api_key') ?></label>
                    <input type="password" class="form-control" id="openai_api_key" name="openai_api_key"
                           value="<?= htmlspecialchars($providers['openai']['api_key'] ?? '') ?>"
                           placeholder="sk-..." autocomplete="new-password">
                    <span class="form-hint">Get from <a href="https://platform.openai.com" target="_blank" style="color: var(--primary-light);">platform.openai.com</a></span>
                </div>
                <div class="form-group">
                    <label for="openai_model"><?= __('ai_model') ?></label>
                    <select class="form-control" id="openai_model" name="openai_model">
                        <option value="gpt-4-turbo-preview" <?= ($providers['openai']['model'] ?? '') === 'gpt-4-turbo-preview' ? 'selected' : '' ?>>GPT-4 Turbo</option>
                        <option value="gpt-4o" <?= ($providers['openai']['model'] ?? '') === 'gpt-4o' ? 'selected' : '' ?>>GPT-4o</option>
                        <option value="gpt-3.5-turbo" <?= ($providers['openai']['model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : '' ?>>GPT-3.5 Turbo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Anthropic Configuration -->
    <div class="collapsible">
        <div class="collapsible-header">
            <span>Anthropic (Fallback)</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="collapsible-content">
            <div class="form-row">
                <div class="form-group">
                    <label for="anthropic_api_key"><?= __('ai_api_key') ?></label>
                    <input type="password" class="form-control" id="anthropic_api_key" name="anthropic_api_key"
                           value="<?= htmlspecialchars($providers['anthropic']['api_key'] ?? '') ?>"
                           placeholder="sk-ant-..." autocomplete="new-password">
                    <span class="form-hint">Get from <a href="https://console.anthropic.com" target="_blank" style="color: var(--primary-light);">console.anthropic.com</a></span>
                </div>
                <div class="form-group">
                    <label for="anthropic_model"><?= __('ai_model') ?></label>
                    <select class="form-control" id="anthropic_model" name="anthropic_model">
                        <option value="claude-3-opus-20240229" <?= ($providers['anthropic']['model'] ?? '') === 'claude-3-opus-20240229' ? 'selected' : '' ?>>Claude 3 Opus</option>
                        <option value="claude-3-sonnet-20240229" <?= ($providers['anthropic']['model'] ?? '') === 'claude-3-sonnet-20240229' ? 'selected' : '' ?>>Claude 3 Sonnet</option>
                        <option value="claude-3-haiku-20240307" <?= ($providers['anthropic']['model'] ?? '') === 'claude-3-haiku-20240307' ? 'selected' : '' ?>>Claude 3 Haiku</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="btn-group">
        <a href="?step=4" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <?= __('back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <?= __('continue') ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </button>
    </div>
</form>

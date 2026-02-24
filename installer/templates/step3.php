<?php
/**
 * Step 3 Template: Telegram Bot Configuration
 */

$tg = $stepData['telegram'] ?? [];
?>

<div class="alert alert-info">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="16" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12.01" y2="8"></line>
    </svg>
    <div>
        <strong>How to create a Telegram bot:</strong><br>
        1. Open <a href="https://t.me/BotFather" target="_blank" style="color: var(--primary-light);">@BotFather</a> in Telegram<br>
        2. Send /newbot and follow the instructions<br>
        3. Copy the bot token provided
    </div>
</div>

<form method="POST" autocomplete="off">
    <?= csrfField() ?>
    <div class="form-group">
        <label for="bot_token"><?= __('tg_token') ?> <span class="required">*</span></label>
        <input type="text" class="form-control" id="bot_token" name="bot_token"
               value="<?= htmlspecialchars($tg['bot_token']) ?>"
               placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz" required autocomplete="off">
        <span class="form-hint"><?= __('tg_token_hint') ?></span>
    </div>

    <div class="form-group">
        <label for="bot_username"><?= __('tg_username') ?></label>
        <input type="text" class="form-control" id="bot_username" name="bot_username"
               value="<?= htmlspecialchars($tg['bot_username']) ?>"
               placeholder="your_bot">
        <span class="form-hint"><?= __('tg_username_hint') ?></span>
    </div>

    <div class="collapsible">
        <div class="collapsible-header">
            <span><?= __('tg_webhook') ?></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="collapsible-content">
            <p style="color: var(--text-secondary); margin-bottom: 12px;"><?= __('tg_webhook_hint') ?></p>
            <code style="display: block; background: var(--bg-dark); padding: 12px; border-radius: 6px; font-size: 13px; word-break: break-all;">
                <?= htmlspecialchars($tg['webhook_url']) ?>
            </code>
        </div>
    </div>

    <div class="collapsible" style="margin-top: 12px;">
        <div class="collapsible-header">
            <span><?= __('tg_commands') ?></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="collapsible-content">
            <p style="color: var(--text-secondary); margin-bottom: 12px;"><?= __('tg_commands_hint') ?></p>
            <ul style="margin-left: 20px; color: var(--text-secondary);">
                <li><code>/start</code> - Start the bot and open Mini App</li>
                <li><code>/app</code> - Open TON AI Agent Mini App</li>
                <li><code>/help</code> - Get help and documentation</li>
                <li><code>/portfolio</code> - View your portfolio</li>
                <li><code>/agents</code> - Manage your AI agents</li>
            </ul>
        </div>
    </div>

    <div class="btn-group">
        <a href="?step=2" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <?= __('back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <?= __('tg_validate') ?> & <?= __('continue') ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </button>
    </div>
</form>

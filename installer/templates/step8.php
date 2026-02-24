<?php
/**
 * Step 8 Template: Admin Account Setup
 */

$admin = $stepData['admin'] ?? [];

$timezones = [
    'UTC' => 'UTC',
    'America/New_York' => 'Eastern Time (US)',
    'America/Los_Angeles' => 'Pacific Time (US)',
    'Europe/London' => 'London',
    'Europe/Paris' => 'Paris',
    'Europe/Moscow' => 'Moscow',
    'Asia/Tokyo' => 'Tokyo',
    'Asia/Shanghai' => 'Shanghai',
    'Asia/Dubai' => 'Dubai',
    'Asia/Singapore' => 'Singapore',
];
?>

<form method="POST" autocomplete="off">
    <div class="form-group">
        <label for="admin_tg_id"><?= __('admin_tg_id') ?></label>
        <input type="text" class="form-control" id="admin_tg_id" name="admin_tg_id"
               value="<?= htmlspecialchars($admin['telegram_id']) ?>"
               placeholder="123456789">
        <span class="form-hint"><?= __('admin_tg_id_hint') ?></span>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="admin_username"><?= __('admin_username') ?> <span class="required">*</span></label>
            <input type="text" class="form-control" id="admin_username" name="admin_username"
                   value="<?= htmlspecialchars($admin['username']) ?>" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="admin_email"><?= __('admin_email') ?></label>
            <input type="email" class="form-control" id="admin_email" name="admin_email"
                   value="<?= htmlspecialchars($admin['email']) ?>"
                   placeholder="admin@example.com" autocomplete="off">
            <span class="form-hint"><?= __('admin_email_hint') ?></span>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="admin_password"><?= __('admin_password') ?></label>
            <input type="password" class="form-control" id="admin_password" name="admin_password"
                   placeholder="••••••••••••" autocomplete="new-password">
            <span class="form-hint"><?= __('admin_password_hint') ?></span>
        </div>
        <div class="form-group">
            <label for="admin_password_confirm"><?= __('admin_password_confirm') ?></label>
            <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm"
                   placeholder="••••••••••••" autocomplete="new-password">
        </div>
    </div>

    <hr style="border-color: var(--border); margin: 24px 0;">

    <div class="form-row">
        <div class="form-group">
            <label for="admin_locale"><?= __('admin_locale') ?></label>
            <select class="form-control" id="admin_locale" name="admin_locale">
                <option value="en" <?= $admin['locale'] === 'en' ? 'selected' : '' ?>>English</option>
                <option value="ru" <?= $admin['locale'] === 'ru' ? 'selected' : '' ?>>Русский</option>
                <option value="zh" <?= $admin['locale'] === 'zh' ? 'selected' : '' ?>>中文</option>
                <option value="ar" <?= $admin['locale'] === 'ar' ? 'selected' : '' ?>>العربية</option>
            </select>
        </div>
        <div class="form-group">
            <label for="admin_timezone"><?= __('admin_timezone') ?></label>
            <select class="form-control" id="admin_timezone" name="admin_timezone">
                <?php foreach ($timezones as $value => $label): ?>
                <option value="<?= $value ?>" <?= $admin['timezone'] === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="btn-group">
        <a href="?step=7" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <?= __('back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            Complete Installation
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </button>
    </div>
</form>

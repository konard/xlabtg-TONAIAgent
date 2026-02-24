-- TON AI Agent - Database Schema
-- MySQL 8.0+ / MariaDB 10.3+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Users table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `telegram_id` BIGINT UNSIGNED NOT NULL,
    `username` VARCHAR(255) NULL,
    `first_name` VARCHAR(255) NULL,
    `last_name` VARCHAR(255) NULL,
    `language_code` VARCHAR(10) DEFAULT 'en',
    `is_premium` TINYINT(1) DEFAULT 0,
    `wallet_address` VARCHAR(255) NULL,
    `referred_by` BIGINT UNSIGNED NULL,
    `subscription_tier` ENUM('basic', 'pro', 'institutional') DEFAULT 'basic',
    `subscription_expires_at` DATETIME NULL,
    `privacy_consent` TINYINT(1) DEFAULT 0,
    `privacy_consent_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_telegram_id` (`telegram_id`),
    KEY `idx_username` (`username`),
    KEY `idx_wallet_address` (`wallet_address`),
    KEY `idx_referred_by` (`referred_by`),
    KEY `idx_subscription_tier` (`subscription_tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Agents table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_agents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `strategy_id` VARCHAR(64) NOT NULL,
    `status` ENUM('created', 'active', 'paused', 'stopped') DEFAULT 'created',
    `initial_balance` DECIMAL(24, 8) DEFAULT 0,
    `current_balance` DECIMAL(24, 8) DEFAULT 0,
    `total_pnl` DECIMAL(24, 8) DEFAULT 0,
    `total_trades` INT UNSIGNED DEFAULT 0,
    `winning_trades` INT UNSIGNED DEFAULT 0,
    `parameters` JSON NULL,
    `risk_level` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `max_drawdown` DECIMAL(5, 2) DEFAULT 20.00,
    `activated_at` DATETIME NULL,
    `paused_at` DATETIME NULL,
    `stopped_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_strategy_id` (`strategy_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_agents_user` FOREIGN KEY (`user_id`) REFERENCES `tonai_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Strategies table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_strategies` (
    `id` VARCHAR(64) NOT NULL,
    `creator_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `category` ENUM('accumulation', 'yield', 'liquidity', 'portfolio', 'trading') NOT NULL,
    `risk_level` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `is_template` TINYINT(1) DEFAULT 0,
    `is_public` TINYINT(1) DEFAULT 1,
    `min_investment` DECIMAL(24, 8) DEFAULT 10,
    `performance_fee` DECIMAL(5, 2) DEFAULT 10.00,
    `parameters_schema` JSON NULL,
    `default_parameters` JSON NULL,
    `total_subscribers` INT UNSIGNED DEFAULT 0,
    `total_tvl` DECIMAL(24, 8) DEFAULT 0,
    `avg_apy` DECIMAL(8, 2) DEFAULT 0,
    `avg_rating` DECIMAL(3, 2) DEFAULT 0,
    `total_ratings` INT UNSIGNED DEFAULT 0,
    `status` ENUM('draft', 'active', 'paused', 'deprecated') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_creator_id` (`creator_id`),
    KEY `idx_category` (`category`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_is_public` (`is_public`),
    KEY `idx_total_subscribers` (`total_subscribers`),
    CONSTRAINT `fk_strategies_creator` FOREIGN KEY (`creator_id`) REFERENCES `tonai_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Strategy subscriptions (copy trading)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_strategy_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `strategy_id` VARCHAR(64) NOT NULL,
    `agent_id` BIGINT UNSIGNED NULL,
    `allocation` DECIMAL(24, 8) DEFAULT 0,
    `status` ENUM('active', 'paused', 'cancelled') DEFAULT 'active',
    `subscribed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `cancelled_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_strategy` (`user_id`, `strategy_id`),
    KEY `idx_strategy_id` (`strategy_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `tonai_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subscriptions_strategy` FOREIGN KEY (`strategy_id`) REFERENCES `tonai_strategies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subscriptions_agent` FOREIGN KEY (`agent_id`) REFERENCES `tonai_agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Trades table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_trades` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `agent_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('buy', 'sell', 'swap', 'stake', 'unstake', 'claim') NOT NULL,
    `asset_in` VARCHAR(64) NOT NULL,
    `asset_out` VARCHAR(64) NULL,
    `amount_in` DECIMAL(24, 8) NOT NULL,
    `amount_out` DECIMAL(24, 8) NULL,
    `price` DECIMAL(24, 8) NULL,
    `fee` DECIMAL(24, 8) DEFAULT 0,
    `gas_used` DECIMAL(24, 8) DEFAULT 0,
    `tx_hash` VARCHAR(255) NULL,
    `status` ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
    `error_message` TEXT NULL,
    `executed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_agent_id` (`agent_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_executed_at` (`executed_at`),
    CONSTRAINT `fk_trades_agent` FOREIGN KEY (`agent_id`) REFERENCES `tonai_agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Revenue records
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_revenue` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('performance_fee', 'management_fee', 'subscription', 'referral') NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `agent_id` BIGINT UNSIGNED NULL,
    `strategy_id` VARCHAR(64) NULL,
    `amount` DECIMAL(24, 8) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'TON',
    `platform_share` DECIMAL(24, 8) DEFAULT 0,
    `creator_share` DECIMAL(24, 8) DEFAULT 0,
    `status` ENUM('pending', 'processed', 'paid') DEFAULT 'pending',
    `processed_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_agent_id` (`agent_id`),
    KEY `idx_strategy_id` (`strategy_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Referrals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_referrals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `referrer_id` BIGINT UNSIGNED NOT NULL,
    `referred_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(32) NOT NULL,
    `status` ENUM('pending', 'active', 'rewarded') DEFAULT 'pending',
    `reward_amount` DECIMAL(24, 8) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `activated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_referred_id` (`referred_id`),
    KEY `idx_referrer_id` (`referrer_id`),
    KEY `idx_code` (`code`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_referrals_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `tonai_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referrals_referred` FOREIGN KEY (`referred_id`) REFERENCES `tonai_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Agent rankings (cached/computed)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_agent_rankings` (
    `agent_id` BIGINT UNSIGNED NOT NULL,
    `rank` INT UNSIGNED DEFAULT 0,
    `score` DECIMAL(10, 4) DEFAULT 0,
    `performance_score` DECIMAL(10, 4) DEFAULT 0,
    `stability_score` DECIMAL(10, 4) DEFAULT 0,
    `risk_score` DECIMAL(10, 4) DEFAULT 0,
    `reputation_score` DECIMAL(10, 4) DEFAULT 0,
    `onchain_score` DECIMAL(10, 4) DEFAULT 0,
    `pnl_30d` DECIMAL(24, 8) DEFAULT 0,
    `win_rate` DECIMAL(5, 2) DEFAULT 0,
    `sharpe_ratio` DECIMAL(8, 4) DEFAULT 0,
    `max_drawdown` DECIMAL(5, 2) DEFAULT 0,
    `calculated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`agent_id`),
    KEY `idx_rank` (`rank`),
    KEY `idx_score` (`score`),
    CONSTRAINT `fk_rankings_agent` FOREIGN KEY (`agent_id`) REFERENCES `tonai_agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Audit log
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(64) NOT NULL,
    `entity_type` VARCHAR(64) NULL,
    `entity_id` VARCHAR(64) NULL,
    `details` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Rate limiting
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_rate_limits` (
    `key` VARCHAR(255) NOT NULL,
    `hits` INT UNSIGNED DEFAULT 1,
    `reset_at` DATETIME NOT NULL,
    PRIMARY KEY (`key`),
    KEY `idx_reset_at` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sessions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tonai_sessions` (
    `id` VARCHAR(128) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default strategies
-- --------------------------------------------------------
INSERT INTO `tonai_strategies` (`id`, `name`, `description`, `category`, `risk_level`, `is_template`, `min_investment`, `performance_fee`) VALUES
('dca', 'Dollar Cost Averaging', 'Automatically invest fixed amounts at regular intervals to reduce the impact of volatility.', 'accumulation', 'low', 1, 10, 10.00),
('yield_farming', 'Yield Farming', 'Optimize returns across multiple DeFi protocols by moving funds to highest-yield opportunities.', 'yield', 'medium', 1, 100, 15.00),
('liquidity_management', 'Liquidity Management', 'Provide liquidity to DEXes and manage positions to maximize fees while minimizing impermanent loss.', 'liquidity', 'medium', 1, 500, 15.00),
('rebalancing', 'Portfolio Rebalancing', 'Maintain target portfolio allocations by automatically buying and selling assets.', 'portfolio', 'low', 1, 200, 10.00),
('arbitrage', 'Simple Arbitrage', 'Exploit price differences across exchanges for profit.', 'trading', 'high', 1, 1000, 20.00);

SET FOREIGN_KEY_CHECKS = 1;

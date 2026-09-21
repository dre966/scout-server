-- schema.sql - Import via phpMyAdmin or mysql CLI
-- mysql -u root -p scout_db < schema.sql
-- or: mysql -u root < schema.sql (XAMPP default no password)

CREATE DATABASE IF NOT EXISTS `scout_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `scout_db`;

-- Bots table: one row per BOT_ID / container
CREATE TABLE IF NOT EXISTS `bots` (
  `id` INT NOT NULL PRIMARY KEY,
  `proxy_email` VARCHAR(255) DEFAULT NULL,
  `poll_inbox` VARCHAR(255) DEFAULT NULL,
  `container_id` VARCHAR(255) DEFAULT NULL,
  `state` VARCHAR(64) DEFAULT NULL,
  `sims_count` INT DEFAULT 0,
  `current_url` TEXT DEFAULT NULL,
  `uptime` VARCHAR(64) DEFAULT NULL,
  `heartbeat_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `auth_token` TEXT DEFAULT NULL,
  `token_updated_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration for existing installs (MariaDB 10.2+ supports IF NOT EXISTS)
ALTER TABLE `bots` ADD COLUMN IF NOT EXISTS `auth_token` TEXT DEFAULT NULL;
ALTER TABLE `bots` ADD COLUMN IF NOT EXISTS `token_updated_at` DATETIME DEFAULT NULL;

-- Bot logs: heartbeat history / state changes / free-form logs
CREATE TABLE IF NOT EXISTS `bot_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_id` INT NOT NULL,
  `state` VARCHAR(64) DEFAULT NULL,
  `sims_count` INT DEFAULT NULL,
  `current_url` TEXT DEFAULT NULL,
  `uptime` VARCHAR(64) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `level` VARCHAR(32) DEFAULT 'info',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`bot_id`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Commands: dashboard -> bot
CREATE TABLE IF NOT EXISTS `commands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_id` INT NOT NULL,
  `cmd` VARCHAR(64) NOT NULL,
  `args` TEXT DEFAULT NULL COMMENT 'JSON encoded args',
  `status` ENUM('pending','acked','done','failed') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `acked_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`bot_id`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications: bot -> dashboard / telegram
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_id` INT NOT NULL,
  `type` VARCHAR(64) NOT NULL COMMENT 'NoSimsRegistered|suspended|otp_failed|etc',
  `message` TEXT DEFAULT NULL,
  `details` TEXT DEFAULT NULL COMMENT 'JSON',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`bot_id`),
  INDEX (`type`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

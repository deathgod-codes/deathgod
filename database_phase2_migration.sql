-- Phase 2 Database Schema Updates
-- Add file attachment support to messages table

-- Add columns for file attachments
ALTER TABLE `messages` 
ADD COLUMN `file_url` VARCHAR(500) NULL DEFAULT NULL AFTER `msg`,
ADD COLUMN `file_type` ENUM('image', 'video', 'audio', 'document') NULL DEFAULT NULL AFTER `file_url`,
ADD COLUMN `file_size` INT NULL DEFAULT NULL AFTER `file_type`,
ADD COLUMN `thumbnail_url` VARCHAR(500) NULL DEFAULT NULL AFTER `file_size`,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `thumbnail_url`;

-- Add indexes for better query performance
ALTER TABLE `messages`
ADD INDEX `idx_file_type` (`file_type`),
ADD INDEX `idx_created_at` (`created_at`);

-- Optional: Add link preview support
ALTER TABLE `messages`
ADD COLUMN `link_preview_data` TEXT NULL DEFAULT NULL AFTER `created_at`;

-- Optional: Add reply and forward support
ALTER TABLE `messages`
ADD COLUMN `reply_to_msg_id` INT NULL DEFAULT NULL AFTER `link_preview_data`,
ADD COLUMN `is_forwarded` TINYINT(1) DEFAULT 0 AFTER `reply_to_msg_id`,
ADD INDEX `idx_reply_to` (`reply_to_msg_id`);

-- Create groups table for group chat functionality
CREATE TABLE IF NOT EXISTS `groups` (
  `group_id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(255) NOT NULL,
  `group_description` TEXT NULL,
  `group_icon` VARCHAR(255) NULL,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create group members table
CREATE TABLE IF NOT EXISTS `group_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `role` ENUM('admin', 'member') DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membership` (`group_id`, `user_id`),
  INDEX `idx_group_id` (`group_id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add group support to messages table
ALTER TABLE `messages`
ADD COLUMN `group_id` INT(11) NULL DEFAULT NULL AFTER `incoming_msg_id`,
ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0 AFTER `is_forwarded`,
ADD INDEX `idx_group_id` (`group_id`);

-- Create message status table for read receipts
CREATE TABLE IF NOT EXISTS `message_status` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `msg_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `status` ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_status` (`msg_id`, `user_id`),
  INDEX `idx_msg_id` (`msg_id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `data` TEXT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add theme preference and last seen to users table
ALTER TABLE `users`
ADD COLUMN `theme_preference` ENUM('light', 'dark', 'auto') DEFAULT 'light' AFTER `status`,
ADD COLUMN `last_seen` TIMESTAMP NULL DEFAULT NULL AFTER `theme_preference`,
ADD COLUMN `bio` VARCHAR(500) NULL DEFAULT NULL AFTER `last_seen`;

-- Create push notification subscriptions table
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `endpoint` TEXT NOT NULL,
  `auth` VARCHAR(255) NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Commit changes
COMMIT;

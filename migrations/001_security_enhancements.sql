-- Migration: Security Enhancements
-- This migration adds security-related fields to the users table
-- Run this migration after backing up your database

-- Add new columns to users table for security features
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS bio TEXT NULL,
ADD COLUMN IF NOT EXISTS status_message VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS theme_preference VARCHAR(20) DEFAULT 'light',
ADD COLUMN IF NOT EXISTS notification_sound BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS verification_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL;

-- Add index on email for faster lookups
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Add new columns to messages table for enhanced features
ALTER TABLE messages 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS is_edited BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS message_type ENUM('text', 'image', 'video', 'audio', 'file') DEFAULT 'text',
ADD COLUMN IF NOT EXISTS file_path VARCHAR(500) NULL,
ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS file_size INT NULL,
ADD COLUMN IF NOT EXISTS reply_to_msg_id INT NULL,
ADD COLUMN IF NOT EXISTS is_forwarded BOOLEAN DEFAULT FALSE;

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(incoming_msg_id, outgoing_msg_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_created_at ON messages(created_at);

-- Create message_status table for read receipts
CREATE TABLE IF NOT EXISTS message_status (
  status_id INT PRIMARY KEY AUTO_INCREMENT,
  msg_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
  status_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (msg_id) REFERENCES messages(msg_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_message_status_msg_id (msg_id)
);

-- Create message_reactions table
CREATE TABLE IF NOT EXISTS message_reactions (
  reaction_id INT PRIMARY KEY AUTO_INCREMENT,
  msg_id INT NOT NULL,
  user_id INT NOT NULL,
  reaction VARCHAR(10) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (msg_id) REFERENCES messages(msg_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_message_reaction (msg_id, user_id, reaction)
);

-- Create groups table
CREATE TABLE IF NOT EXISTS groups (
  group_id INT PRIMARY KEY AUTO_INCREMENT,
  group_name VARCHAR(255) NOT NULL,
  group_description TEXT,
  group_icon VARCHAR(255),
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Create group_members table
CREATE TABLE IF NOT EXISTS group_members (
  id INT PRIMARY KEY AUTO_INCREMENT,
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('admin', 'member') DEFAULT 'member',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_group_member (group_id, user_id),
  INDEX idx_group_members_group_id (group_id)
);

-- Add group support to messages table
ALTER TABLE messages ADD COLUMN IF NOT EXISTS group_id INT NULL;
-- Note: Adding foreign key manually after ensuring groups table exists
-- ALTER TABLE messages ADD FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE;

-- Create typing_status table
CREATE TABLE IF NOT EXISTS typing_status (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  conversation_id INT NOT NULL,
  conversation_type ENUM('user', 'group') DEFAULT 'user',
  is_typing BOOLEAN DEFAULT FALSE,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create call_history table
CREATE TABLE IF NOT EXISTS call_history (
  call_id INT PRIMARY KEY AUTO_INCREMENT,
  caller_id INT NOT NULL,
  receiver_id INT NOT NULL,
  call_type ENUM('voice', 'video') NOT NULL,
  call_status ENUM('completed', 'missed', 'rejected', 'failed') NOT NULL,
  duration INT DEFAULT 0,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ended_at TIMESTAMP NULL,
  FOREIGN KEY (caller_id) REFERENCES users(user_id),
  FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

-- Create csrf_tokens table (optional - can also use session storage)
CREATE TABLE IF NOT EXISTS csrf_tokens (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

# Database Schema Documentation

This document describes the database structure for the Realtime Chat Application.

## Overview

**Database Name:** chatapp  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Engine:** InnoDB

---

## Tables

### 1. users

Stores user account information and profile data.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| user_id | INT(11) | NO | AUTO_INCREMENT | Primary key |
| unique_id | INT(255) | NO | - | Unique user identifier for sessions |
| fname | VARCHAR(255) | NO | - | First name |
| lname | VARCHAR(255) | NO | - | Last name |
| email | VARCHAR(255) | NO | - | Email address (unique, indexed) |
| password | VARCHAR(255) | NO | - | Bcrypt hashed password |
| img | VARCHAR(255) | NO | - | Profile image filename |
| status | VARCHAR(255) | NO | - | Online status ("Active now", "Offline now") |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Account creation timestamp |
| last_seen | TIMESTAMP | YES | NULL | Last activity timestamp |
| bio | TEXT | YES | NULL | User biography/about |
| status_message | VARCHAR(255) | YES | NULL | Custom status message |
| theme_preference | VARCHAR(20) | NO | 'light' | Theme preference ("light", "dark") |
| notification_sound | BOOLEAN | NO | TRUE | Notification sound enabled |
| email_verified | BOOLEAN | NO | FALSE | Email verification status |
| verification_token | VARCHAR(255) | YES | NULL | Email verification token |
| failed_login_attempts | INT | NO | 0 | Failed login counter |
| account_locked_until | TIMESTAMP | YES | NULL | Account lock expiration time |

**Indexes:**
- PRIMARY KEY: `user_id`
- INDEX: `email` (for faster lookups)

**Security Notes:**
- Passwords are hashed using bcrypt (PASSWORD_BCRYPT)
- Email verification tokens should be random and expire after 24 hours
- Failed login attempts reset on successful login
- Account locks expire after 15 minutes by default

---

### 2. messages

Stores all chat messages between users and in groups.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| msg_id | INT(11) | NO | AUTO_INCREMENT | Primary key |
| incoming_msg_id | INT(255) | NO | - | Recipient user ID |
| outgoing_msg_id | INT(255) | NO | - | Sender user ID |
| msg | VARCHAR(1000) | NO | - | Message content (XSS protected) |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Message sent timestamp |
| updated_at | TIMESTAMP | YES | NULL | Last edit timestamp |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete timestamp |
| is_edited | BOOLEAN | NO | FALSE | Edit indicator flag |
| message_type | ENUM | NO | 'text' | Message type: text, image, video, audio, file |
| file_path | VARCHAR(500) | YES | NULL | File storage path |
| file_name | VARCHAR(255) | YES | NULL | Original filename |
| file_size | INT | YES | NULL | File size in bytes |
| reply_to_msg_id | INT | YES | NULL | ID of message being replied to |
| is_forwarded | BOOLEAN | NO | FALSE | Forwarded message flag |
| group_id | INT | YES | NULL | Group ID (if group message) |

**Indexes:**
- PRIMARY KEY: `msg_id`
- INDEX: `(incoming_msg_id, outgoing_msg_id, created_at)` (for conversation queries)
- INDEX: `created_at` (for chronological ordering)

**Relationships:**
- `incoming_msg_id` references `users(unique_id)`
- `outgoing_msg_id` references `users(unique_id)`
- `reply_to_msg_id` references `messages(msg_id)`
- `group_id` references `groups(group_id)`

**Notes:**
- Soft deletes: Messages marked as deleted show "[Message deleted]"
- Message content is sanitized with htmlspecialchars before storage
- Maximum message length: 1000 characters

---

### 3. message_status

Tracks message delivery and read status for read receipts.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| status_id | INT | NO | AUTO_INCREMENT | Primary key |
| msg_id | INT | NO | - | Reference to message |
| user_id | INT | NO | - | User who received/read the message |
| status | ENUM | NO | 'sent' | Status: sent, delivered, read |
| status_time | TIMESTAMP | NO | CURRENT_TIMESTAMP | Status update timestamp |

**Indexes:**
- PRIMARY KEY: `status_id`
- INDEX: `msg_id` (for message queries)
- FOREIGN KEY: `msg_id` → `messages(msg_id)` ON DELETE CASCADE
- FOREIGN KEY: `user_id` → `users(user_id)` ON DELETE CASCADE

**Status Flow:**
1. `sent` - Message sent from sender
2. `delivered` - Message delivered to recipient's device
3. `read` - Message read by recipient

---

### 4. message_reactions

Stores emoji reactions to messages.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| reaction_id | INT | NO | AUTO_INCREMENT | Primary key |
| msg_id | INT | NO | - | Reference to message |
| user_id | INT | NO | - | User who reacted |
| reaction | VARCHAR(10) | NO | - | Emoji reaction (UTF-8) |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Reaction timestamp |

**Indexes:**
- PRIMARY KEY: `reaction_id`
- UNIQUE KEY: `(msg_id, user_id, reaction)` (one reaction type per user per message)
- FOREIGN KEY: `msg_id` → `messages(msg_id)` ON DELETE CASCADE
- FOREIGN KEY: `user_id` → `users(user_id)` ON DELETE CASCADE

**Allowed Reactions:**
- 👍 Thumbs up
- ❤️ Heart
- 😂 Laugh
- 😮 Surprised
- 😢 Sad
- 🙏 Pray

---

### 5. groups

Stores group chat information.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| group_id | INT | NO | AUTO_INCREMENT | Primary key |
| group_name | VARCHAR(255) | NO | - | Group name |
| group_description | TEXT | YES | NULL | Group description |
| group_icon | VARCHAR(255) | YES | NULL | Group icon filename |
| created_by | INT | NO | - | Creator user ID |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Group creation timestamp |

**Indexes:**
- PRIMARY KEY: `group_id`
- FOREIGN KEY: `created_by` → `users(user_id)`

---

### 6. group_members

Tracks group membership and roles.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | INT | NO | AUTO_INCREMENT | Primary key |
| group_id | INT | NO | - | Reference to group |
| user_id | INT | NO | - | Reference to user |
| role | ENUM | NO | 'member' | Role: admin, member |
| joined_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Join timestamp |

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `(group_id, user_id)` (prevent duplicate membership)
- INDEX: `group_id` (for group queries)
- FOREIGN KEY: `group_id` → `groups(group_id)` ON DELETE CASCADE
- FOREIGN KEY: `user_id` → `users(user_id)` ON DELETE CASCADE

**Roles:**
- `admin` - Can add/remove members, change settings
- `member` - Regular group member

---

### 7. typing_status

Tracks real-time typing indicators.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | INT | NO | AUTO_INCREMENT | Primary key |
| user_id | INT | NO | - | User who is typing |
| conversation_id | INT | NO | - | User ID or Group ID |
| conversation_type | ENUM | NO | 'user' | Type: user, group |
| is_typing | BOOLEAN | NO | FALSE | Typing status flag |
| updated_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update (ON UPDATE CURRENT_TIMESTAMP) |

**Indexes:**
- PRIMARY KEY: `id`
- FOREIGN KEY: `user_id` → `users(user_id)` ON DELETE CASCADE

**Notes:**
- Typing status expires after 5 seconds of no updates
- Used for real-time "user is typing..." indicators

---

### 8. call_history

Stores voice and video call records.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| call_id | INT | NO | AUTO_INCREMENT | Primary key |
| caller_id | INT | NO | - | User who initiated call |
| receiver_id | INT | NO | - | User who received call |
| call_type | ENUM | NO | - | Type: voice, video |
| call_status | ENUM | NO | - | Status: completed, missed, rejected, failed |
| duration | INT | NO | 0 | Call duration in seconds |
| started_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Call start time |
| ended_at | TIMESTAMP | YES | NULL | Call end time |

**Indexes:**
- PRIMARY KEY: `call_id`
- FOREIGN KEY: `caller_id` → `users(user_id)`
- FOREIGN KEY: `receiver_id` → `users(user_id)`

**Call Status:**
- `completed` - Call was answered and completed
- `missed` - Receiver didn't answer
- `rejected` - Receiver declined the call
- `failed` - Technical failure

---

### 9. csrf_tokens

Stores CSRF tokens (optional - can use session storage instead).

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | INT | NO | AUTO_INCREMENT | Primary key |
| user_id | INT | NO | - | Reference to user |
| token | VARCHAR(255) | NO | - | CSRF token value |
| created_at | TIMESTAMP | NO | CURRENT_TIMESTAMP | Token creation time |
| expires_at | TIMESTAMP | NO | - | Token expiration time |

**Indexes:**
- PRIMARY KEY: `id`
- FOREIGN KEY: `user_id` → `users(user_id)` ON DELETE CASCADE

**Notes:**
- Tokens expire after configured time (default: 1 hour)
- Old tokens should be cleaned up regularly
- Current implementation uses session storage

---

## Relationships Diagram

```
users (1) -----> (N) messages (outgoing)
users (1) -----> (N) messages (incoming)
users (1) -----> (N) message_status
users (1) -----> (N) message_reactions
users (1) -----> (N) groups (creator)
users (1) -----> (N) group_members
users (1) -----> (N) typing_status
users (1) -----> (N) call_history (caller)
users (1) -----> (N) call_history (receiver)

messages (1) -----> (N) message_status
messages (1) -----> (N) message_reactions
messages (N) -----> (1) groups

groups (1) -----> (N) group_members
```

---

## Queries Examples

### Get conversation between two users
```sql
SELECT m.*, u.fname, u.lname, u.img 
FROM messages m
LEFT JOIN users u ON u.unique_id = m.outgoing_msg_id
WHERE (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?)
   OR (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?)
   AND m.deleted_at IS NULL
ORDER BY m.created_at DESC
LIMIT 50;
```

### Get unread message count
```sql
SELECT COUNT(*) as unread
FROM messages m
LEFT JOIN message_status ms ON m.msg_id = ms.msg_id AND ms.user_id = ?
WHERE m.incoming_msg_id = ?
  AND (ms.status IS NULL OR ms.status != 'read');
```

### Get active typing users
```sql
SELECT u.fname, u.lname
FROM typing_status t
JOIN users u ON t.user_id = u.unique_id
WHERE t.conversation_id = ?
  AND t.is_typing = 1
  AND t.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND);
```

### Get message reactions
```sql
SELECT mr.reaction, COUNT(*) as count, 
       GROUP_CONCAT(u.fname) as users
FROM message_reactions mr
JOIN users u ON mr.user_id = u.user_id
WHERE mr.msg_id = ?
GROUP BY mr.reaction;
```

---

## Maintenance

### Cleanup Old Records

```sql
-- Remove expired typing status (older than 1 hour)
DELETE FROM typing_status 
WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Remove old CSRF tokens
DELETE FROM csrf_tokens 
WHERE expires_at < NOW();

-- Archive old messages (older than 1 year)
-- Implement as needed based on retention policy
```

### Database Size Monitoring

```sql
-- Check table sizes
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'chatapp'
ORDER BY (data_length + index_length) DESC;
```

---

## Performance Optimization

### Recommended Indexes (Already Included)
- Users: email
- Messages: (incoming_msg_id, outgoing_msg_id, created_at)
- Message_status: msg_id
- Group_members: group_id

### Query Optimization Tips
1. Use prepared statements (already implemented)
2. Limit result sets with LIMIT
3. Use covering indexes where possible
4. Avoid SELECT * when specific columns are needed
5. Use JOINs efficiently

---

**Last Updated:** January 2026

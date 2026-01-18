# API Documentation

Version: 1.0  
Base URL: `/api/v1/`

## Authentication

All API endpoints require an active PHP session. Users must be logged in to access the API.

### Authentication Headers
```
Cookie: PHPSESSID=<session_id>
```

### Error Responses
All endpoints return JSON responses with the following format:

**Success:**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {}
}
```

**Error:**
```json
{
  "success": false,
  "error": "Error message description"
}
```

---

## Messages API

### Edit Message

Edit an existing message (only the sender can edit).

**Endpoint:** `POST /api/v1/messages/edit.php`

**Request Body:**
```json
{
  "msg_id": 123,
  "new_message": "Updated message text"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Message updated successfully",
  "updated_message": "Updated message text"
}
```

**Errors:**
- `401`: Unauthorized - User not logged in
- `403`: Forbidden - User doesn't own the message
- `404`: Not Found - Message doesn't exist
- `400`: Bad Request - Missing required fields or empty message

---

### Delete Message

Soft delete a message (only the sender can delete).

**Endpoint:** `POST /api/v1/messages/delete.php`

**Request Body:**
```json
{
  "msg_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

**Notes:**
- Messages are soft-deleted (marked as deleted but not removed from database)
- Deleted messages display as "[Message deleted]"

**Errors:**
- `401`: Unauthorized - User not logged in
- `403`: Forbidden - User doesn't own the message
- `404`: Not Found - Message doesn't exist
- `400`: Bad Request - Missing message ID

---

### Add/Remove Reaction

Add or remove an emoji reaction to a message.

**Endpoint:** `POST /api/v1/messages/react.php`

**Request Body:**
```json
{
  "msg_id": 123,
  "reaction": "👍"
}
```

**Allowed Reactions:**
- `👍` - Thumbs up
- `❤️` - Heart
- `😂` - Laugh
- `😮` - Surprised
- `😢` - Sad
- `🙏` - Pray

**Response (Added):**
```json
{
  "success": true,
  "action": "added",
  "message": "Reaction added successfully"
}
```

**Response (Removed - Toggle):**
```json
{
  "success": true,
  "action": "removed",
  "message": "Reaction removed"
}
```

**Notes:**
- Reactions are toggled - adding the same reaction twice removes it
- Users can only have one of each reaction type per message

**Errors:**
- `401`: Unauthorized - User not logged in
- `404`: Not Found - Message doesn't exist
- `400`: Bad Request - Invalid reaction emoji or missing fields

---

## Typing Status API

### Update Typing Status

Update the user's typing status in a conversation.

**Endpoint:** `POST /api/v1/typing/status.php`

**Request Body:**
```json
{
  "conversation_id": 456,
  "is_typing": true,
  "conversation_type": "user"
}
```

**Parameters:**
- `conversation_id` (int, required): The ID of the user or group
- `is_typing` (bool, required): `true` if typing, `false` if stopped
- `conversation_type` (string, optional): `"user"` (default) or `"group"`

**Response:**
```json
{
  "success": true,
  "message": "Typing status updated"
}
```

---

### Get Typing Status

Get who is currently typing in a conversation.

**Endpoint:** `GET /api/v1/typing/status.php?conversation_id=456&conversation_type=user`

**Query Parameters:**
- `conversation_id` (int, required): The ID of the user or group
- `conversation_type` (string, optional): `"user"` (default) or `"group"`

**Response:**
```json
{
  "success": true,
  "is_typing": true,
  "typing_users": ["John Doe", "Jane Smith"]
}
```

**Notes:**
- Typing status expires after 5 seconds of inactivity
- Current user is excluded from typing_users list
- Returns empty array if no one is typing

---

## Rate Limiting

API endpoints are subject to rate limiting to prevent abuse.

**Limits:**
- Login attempts: 5 per 15 minutes per email
- Message sending: No limit currently (to be implemented)
- API requests: 100 per minute (to be implemented)

**Rate Limit Response:**
```json
{
  "success": false,
  "error": "Rate limit exceeded. Please try again later."
}
```

---

## Security

### CSRF Protection

All POST requests must include a valid CSRF token in the session.

### XSS Protection

All user input is sanitized before storage and output.

### SQL Injection Protection

All database queries use prepared statements with parameterized queries.

### Session Security

- Sessions expire after 30 minutes of inactivity
- Session cookies are HTTP-only and secure (in production)

---

## Future Endpoints (Planned)

### User API
- `GET /api/v1/user/profile.php` - Get user profile
- `POST /api/v1/user/update.php` - Update user profile
- `POST /api/v1/user/update-theme.php` - Update theme preference
- `POST /api/v1/user/block.php` - Block a user
- `POST /api/v1/user/unblock.php` - Unblock a user

### Group API
- `POST /api/v1/groups/create.php` - Create a group
- `POST /api/v1/groups/add-member.php` - Add member to group
- `POST /api/v1/groups/remove-member.php` - Remove member from group
- `GET /api/v1/groups/list.php` - List user's groups
- `GET /api/v1/groups/members.php` - Get group members

### File API
- `POST /api/v1/files/upload.php` - Upload a file
- `GET /api/v1/files/download.php` - Download a file
- `DELETE /api/v1/files/delete.php` - Delete a file

### Call API
- `POST /api/v1/calls/initiate.php` - Initiate a call
- `POST /api/v1/calls/accept.php` - Accept a call
- `POST /api/v1/calls/reject.php` - Reject a call
- `POST /api/v1/calls/end.php` - End a call
- `GET /api/v1/calls/history.php` - Get call history

### Notification API
- `GET /api/v1/notifications/list.php` - Get notifications
- `POST /api/v1/notifications/mark-read.php` - Mark notification as read
- `POST /api/v1/notifications/settings.php` - Update notification settings

---

## Testing

Use tools like Postman, cURL, or browser DevTools to test API endpoints.

### Example cURL Request:

```bash
# Edit a message
curl -X POST http://localhost/api/v1/messages/edit.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{"msg_id": 123, "new_message": "Updated text"}'

# Add a reaction
curl -X POST http://localhost/api/v1/messages/react.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{"msg_id": 123, "reaction": "👍"}'

# Get typing status
curl -X GET "http://localhost/api/v1/typing/status.php?conversation_id=456" \
  -H "Cookie: PHPSESSID=your_session_id"
```

---

## Support

For issues or questions about the API, please open an issue on GitHub.

**Version History:**
- v1.0 (January 2026): Initial API release with basic message operations and typing status

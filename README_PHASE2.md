# Phase 2: Real-Time Chat with WebSocket Implementation

## Overview

This phase implements real-time WebSocket communication, replacing the AJAX polling mechanism with efficient bidirectional communication for instant message delivery, typing indicators, and online status updates.

## Features Implemented

### 1. ⚡ WebSocket Real-Time Communication

#### Features:
- **Real-time message delivery** - Messages appear instantly without page refresh
- **Typing indicators** - See when the other person is typing
- **Online/Offline status** - Real-time presence updates
- **Read receipts** - Track when messages are read
- **Automatic reconnection** - Exponential backoff reconnection strategy
- **Message queuing** - Messages sent while offline are queued and sent when reconnected
- **Heartbeat mechanism** - Keeps connections alive

#### Technology Stack:
- **Backend**: Ratchet PHP WebSocket library
- **Frontend**: Native WebSocket API
- **Protocol**: WebSocket (ws://)

## Installation

### Prerequisites

- PHP 7.4 or higher
- Composer
- MySQL/MariaDB
- Web server (Apache/Nginx)

### Setup Instructions

1. **Install Composer Dependencies**
   ```bash
   cd /path/to/deathgod
   composer install
   ```

2. **Database Setup**
   - Import the existing `chatapp.sql` file
   - The existing tables (users, messages) are sufficient for Phase 2 WebSocket implementation

3. **Configure WebSocket Server**
   - Edit `websocket/config.php` if needed
   - Default port: 8080
   - Default host: 0.0.0.0 (all interfaces)

4. **Start WebSocket Server**

   **Option A: Development Mode (Terminal)**
   ```bash
   php websocket/server.php
   ```
   
   You should see:
   ```
   Starting WebSocket Server...
   Host: 0.0.0.0
   Port: 8080
   --------------------------------
   WebSocket server is running!
   Connect to: ws://0.0.0.0:8080
   Press Ctrl+C to stop the server
   --------------------------------
   ```

   **Option B: Production Mode (systemd service)**
   ```bash
   # Copy the service file
   sudo cp websocket/chat-websocket.service /etc/systemd/system/
   
   # Edit the service file to match your installation path
   sudo nano /etc/systemd/system/chat-websocket.service
   
   # Reload systemd
   sudo systemctl daemon-reload
   
   # Enable and start the service
   sudo systemctl enable chat-websocket
   sudo systemctl start chat-websocket
   
   # Check status
   sudo systemctl status chat-websocket
   ```

5. **Firewall Configuration**
   
   If using a firewall, allow WebSocket port:
   ```bash
   # UFW (Ubuntu)
   sudo ufw allow 8080/tcp
   
   # firewalld (CentOS/RHEL)
   sudo firewall-cmd --permanent --add-port=8080/tcp
   sudo firewall-cmd --reload
   ```

6. **Web Server Configuration**

   For production, consider using Nginx as a reverse proxy:
   
   ```nginx
   # Add to your nginx site configuration
   location /ws {
       proxy_pass http://localhost:8080;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "upgrade";
       proxy_set_header Host $host;
       proxy_set_header X-Real-IP $remote_addr;
   }
   ```

## How It Works

### Architecture

```
Client (Browser)
    ↓ WebSocket Connection (ws://host:8080?user_id=123)
WebSocket Server (Ratchet)
    ↓ Database Connection
MySQL Database
```

### Message Flow

1. **User sends message**:
   - Client JS calls `wsClient.sendMessage(recipientId, message)`
   - WebSocket client sends JSON: `{type: 'message', incoming_id: 2, message: 'Hello'}`
   - Server receives message, saves to database
   - Server broadcasts to recipient (if online)
   - Server sends confirmation to sender

2. **Typing indicator**:
   - User types in input field
   - Client sends `{type: 'typing_start', to_user_id: 2}`
   - Server forwards to recipient
   - After 2 seconds of inactivity: `{type: 'typing_stop'}`

3. **Online/Offline status**:
   - User connects → Server updates database status to "Active now"
   - Server broadcasts `{type: 'user_status', user_id: 1, status: 'online'}`
   - User disconnects → Status updated to "Offline"

### WebSocket Events

| Event Type | Direction | Description |
|------------|-----------|-------------|
| `message` | Client → Server | Send a new message |
| `new_message` | Server → Client | Receive a new message |
| `message_sent` | Server → Client | Message delivery confirmation |
| `typing_start` | Client → Server | User started typing |
| `typing_stop` | Client → Server | User stopped typing |
| `typing_indicator` | Server → Client | Show/hide typing indicator |
| `user_status` | Server → Client | User online/offline status |
| `read_receipt` | Bidirectional | Message read confirmation |
| `heartbeat` | Bidirectional | Keep connection alive |

## File Structure

```
deathgod/
├── websocket/
│   ├── server.php              # WebSocket server entry point
│   ├── config.php              # Server configuration
│   ├── Chat.php                # Main WebSocket handler class
│   └── chat-websocket.service # Systemd service file
├── javascript/
│   ├── websocket-client.js    # WebSocket client library
│   └── chat.js                # Updated chat interface (uses WebSocket)
├── php/
│   ├── get-session-user.php   # Get current user session info
│   └── ... (existing files)
├── composer.json              # PHP dependencies
├── .gitignore                # Git ignore rules
└── README_PHASE2.md          # This file
```

## API Reference

### WebSocketClient Class (JavaScript)

```javascript
// Initialize WebSocket client
const wsClient = new WebSocketClient(userId);

// Send message
wsClient.sendMessage(recipientId, messageText);

// Send typing indicators
wsClient.sendTypingStart(recipientId);
wsClient.sendTypingStop(recipientId);

// Send read receipt
wsClient.sendReadReceipt(messageId, conversationWith);

// Event handlers
wsClient.on('new_message', (data) => {
    // Handle incoming message
});

wsClient.on('typing_indicator', (data) => {
    // Show/hide typing indicator
});

wsClient.on('user_status', (data) => {
    // Update user online/offline status
});

// Disconnect
wsClient.disconnect();
```

## Testing

### Test WebSocket Connection

1. Start the WebSocket server:
   ```bash
   php websocket/server.php
   ```

2. Open the chat application in two different browsers (or incognito windows)
3. Log in as different users in each browser
4. Send messages between users - they should appear instantly
5. Start typing in one browser - typing indicator should appear in the other
6. Close one browser - the other should show user as offline

### Test Reconnection

1. Start chatting between two users
2. Stop the WebSocket server (Ctrl+C)
3. Observe the "Reconnecting..." status in the browser
4. Restart the WebSocket server
5. Browser should automatically reconnect
6. Messages sent during disconnection should be delivered

## Troubleshooting

### WebSocket server won't start

**Error**: "Address already in use"
```bash
# Find process using port 8080
sudo lsof -i :8080
# or
sudo netstat -tulpn | grep 8080

# Kill the process
sudo kill -9 <PID>
```

### Connection refused from browser

1. Check WebSocket server is running:
   ```bash
   sudo systemctl status chat-websocket
   # or check manually
   ps aux | grep "websocket/server.php"
   ```

2. Check firewall allows port 8080

3. Verify WebSocket URL in browser console
   - Should connect to: `ws://your-domain:8080?user_id=123`

### Messages not appearing in real-time

1. Open browser console (F12) and check for WebSocket errors
2. Verify connection status indicator shows "Connected"
3. Check WebSocket server logs:
   ```bash
   tail -f websocket/websocket.log
   ```

### Database connection errors

1. Verify database credentials in `websocket/config.php`
2. Ensure database user has proper permissions
3. Check MySQL is running:
   ```bash
   sudo systemctl status mysql
   ```

## Performance Considerations

- **Concurrent connections**: Ratchet can handle thousands of concurrent connections
- **Memory usage**: Approximately 1-2 MB per connection
- **CPU usage**: Very low when idle, spikes during message broadcasts
- **Scalability**: For large deployments, consider:
  - Redis for connection state storage
  - Multiple WebSocket servers with load balancing
  - Message queue (RabbitMQ, Redis Pub/Sub)

## Security Notes

1. **Authentication**: Users are authenticated via session ID in WebSocket URL
2. **Production**: Update `allowed_origins` in `websocket/config.php`
3. **SSL/TLS**: For production, use WSS (WebSocket Secure):
   - Configure SSL certificate
   - Use `wss://` instead of `ws://`
   - Update server to use secure WebSocket

## Next Steps - Phase 2 Continued

The following features are planned for Phase 2:

- [ ] File & media sharing system
- [ ] Group chat functionality
- [ ] Emoji picker & rich text
- [ ] Notification system
- [ ] Dark mode & themes
- [ ] Advanced search
- [ ] Message actions (reply, forward, edit, delete)
- [ ] Progressive Web App (PWA)

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review WebSocket server logs
3. Check browser console for JavaScript errors
4. Open an issue on GitHub

## License

Same as the main project.
